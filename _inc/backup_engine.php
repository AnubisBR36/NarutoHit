<?php
/**
 * Engine de Backup Automático
 * ----------------------------------------------------------------
 * Faz dump dos bancos `naruto` e `forum` usando mysqldump/mariadb-dump
 * e salva em arquivos versionados com timestamp.
 *
 * Configuração vem da tabela backup_config (criada por backup_install_tables).
 * Histórico vai para backup_historico.
 *
 * Pode ser disparado de duas formas:
 *  - Manualmente pelo painel ADM → backup_run_now($pdo)
 *  - Automaticamente a cada page-load via backup_tick_check($pdo)
 *    (apenas executa se proximo_backup <= NOW() e ativo=1)
 */

if (!function_exists('backup_install_tables')) {
function backup_install_tables(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS backup_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        modo VARCHAR(20) NOT NULL DEFAULT 'horas',
        intervalo INT NOT NULL DEFAULT 24,
        dia_semana TINYINT NOT NULL DEFAULT 0,
        hora TINYINT NOT NULL DEFAULT 3,
        minuto TINYINT NOT NULL DEFAULT 0,
        pasta_destino VARCHAR(255) NOT NULL DEFAULT 'backups',
        max_backups INT NOT NULL DEFAULT 30,
        incluir_forum TINYINT NOT NULL DEFAULT 1,
        mysqldump_path VARCHAR(500) NOT NULL DEFAULT '',
        ativo TINYINT NOT NULL DEFAULT 0,
        ultimo_backup DATETIME NULL,
        proximo_backup DATETIME NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Auto-migração: adiciona coluna mysqldump_path em instalações antigas
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM backup_config LIKE 'mysqldump_path'")->fetch(PDO::FETCH_ASSOC);
        if (!$cols) {
            $pdo->exec("ALTER TABLE backup_config ADD COLUMN mysqldump_path VARCHAR(500) NOT NULL DEFAULT '' AFTER incluir_forum");
        }
    } catch (Throwable $e) { /* ignora */ }

    // Garante que pelo menos 1 linha exista
    $count = (int)$pdo->query("SELECT COUNT(*) FROM backup_config")->fetchColumn();
    if ($count === 0) {
        $pdo->exec("INSERT INTO backup_config (modo, intervalo, pasta_destino) VALUES ('horas', 24, 'backups')");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS backup_historico (
        id INT AUTO_INCREMENT PRIMARY KEY,
        arquivo_naruto VARCHAR(255) NULL,
        arquivo_forum  VARCHAR(255) NULL,
        tamanho_bytes  BIGINT NOT NULL DEFAULT 0,
        status         VARCHAR(20) NOT NULL DEFAULT 'sucesso',
        erro_mensagem  TEXT NULL,
        origem         VARCHAR(20) NOT NULL DEFAULT 'manual',
        criado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}}

if (!function_exists('backup_calc_proximo')) {
function backup_calc_proximo(array $cfg, ?DateTime $base = null): DateTime {
    $base = $base ? clone $base : new DateTime('now');
    switch ($cfg['modo']) {
        case 'minutos':
            $n = max(1, min(60, (int)$cfg['intervalo']));
            return $base->modify("+{$n} minutes");
        case 'horas':
            $n = max(1, min(24, (int)$cfg['intervalo']));
            return $base->modify("+{$n} hours");
        case 'semanal':
            // Próxima ocorrência do dia da semana, hora e minuto configurados.
            // Se hoje é o dia e ainda não passou da hora, agenda para hoje;
            // caso contrário, para a próxima semana.
            $alvoDia = max(0, min(6, (int)$cfg['dia_semana'])); // 0=Domingo
            $alvoHora = max(0, min(23, (int)$cfg['hora']));
            $alvoMin  = max(0, min(59, (int)$cfg['minuto']));
            $proximo = clone $base;
            $proximo->setTime($alvoHora, $alvoMin, 0);
            $diaAtual = (int)$proximo->format('w');
            $diff = ($alvoDia - $diaAtual + 7) % 7;
            if ($diff === 0 && $proximo <= $base) {
                $diff = 7;
            }
            if ($diff > 0) {
                $proximo->modify("+{$diff} days");
            }
            return $proximo;
        default:
            return $base->modify('+24 hours');
    }
}}

if (!function_exists('backup_resolve_destino')) {
function backup_resolve_destino(string $pasta): string {
    $pasta = trim($pasta);
    if ($pasta === '') $pasta = 'backups';
    // Normaliza separadores e remove '..'
    $pasta = str_replace('\\', '/', $pasta);
    $partes = array_filter(explode('/', $pasta), function($p){ return $p !== '' && $p !== '..' && $p !== '.'; });
    $rel = implode('/', $partes);
    // Caminho absoluto sob a raiz do projeto
    $base = realpath(__DIR__ . '/..');
    if ($base === false) $base = dirname(__DIR__);
    $abs = $base . '/' . $rel;
    if (!is_dir($abs)) {
        @mkdir($abs, 0755, true);
    }
    return $abs;
}}

if (!function_exists('backup_dump_command')) {
/**
 * Localiza o binário mysqldump/mariadb-dump.
 * Ordem de busca:
 *  1. Caminho customizado (configurado pelo admin no painel) - aceita arquivo OU pasta.
 *  2. PATH do sistema (Linux/Mac/cPanel/hosting típico).
 *  3. Caminhos comuns de XAMPP, WAMP, MAMP no Windows.
 *  4. Caminhos comuns em Linux/Mac.
 */
function backup_dump_command(string $customPath = ''): ?string {
    $customPath = trim($customPath);

    // 1. Caminho customizado (aceita arquivo .exe direto OU pasta contendo o binário)
    if ($customPath !== '') {
        $cp = str_replace('\\', '/', $customPath);
        // Se for arquivo executável, usa direto
        if (is_file($cp)) return $cp;
        // Se for pasta, tenta achar o binário dentro
        if (is_dir($cp)) {
            foreach (['mariadb-dump.exe', 'mysqldump.exe', 'mariadb-dump', 'mysqldump'] as $bin) {
                if (is_file($cp . '/' . $bin)) return $cp . '/' . $bin;
            }
        }
    }

    // 2. PATH do sistema
    foreach (['mariadb-dump', 'mysqldump'] as $bin) {
        $which = trim((string)@shell_exec("command -v " . escapeshellarg($bin) . " 2>/dev/null"));
        if ($which !== '') return $which;
        // Windows fallback (where.exe)
        if (stripos(PHP_OS, 'WIN') === 0) {
            $where = trim((string)@shell_exec("where " . escapeshellarg($bin) . " 2>NUL"));
            if ($where !== '') {
                $first = strtok($where, "\r\n");
                if ($first && is_file($first)) return $first;
            }
        }
    }

    // 3. Caminhos comuns no Windows (XAMPP/WAMP/MAMP/standalone)
    $candidatos = [
        'C:/xampp/mysql/bin/mariadb-dump.exe',
        'C:/xampp/mysql/bin/mysqldump.exe',
        'D:/xampp/mysql/bin/mariadb-dump.exe',
        'D:/xampp/mysql/bin/mysqldump.exe',
        'C:/wamp64/bin/mariadb/mariadb*/bin/mariadb-dump.exe',
        'C:/wamp64/bin/mysql/mysql*/bin/mysqldump.exe',
        'C:/wamp/bin/mysql/mysql*/bin/mysqldump.exe',
        'C:/MAMP/bin/mariadb/bin/mariadb-dump.exe',
        'C:/MAMP/bin/mysql/bin/mysqldump.exe',
        'C:/Program Files/MariaDB*/bin/mariadb-dump.exe',
        'C:/Program Files/MySQL/MySQL Server*/bin/mysqldump.exe',
        // 4. Linux/Mac
        '/usr/bin/mariadb-dump',
        '/usr/bin/mysqldump',
        '/usr/local/bin/mariadb-dump',
        '/usr/local/bin/mysqldump',
        '/usr/local/mysql/bin/mysqldump',
        '/opt/lampp/bin/mysqldump',
        '/Applications/MAMP/Library/bin/mysqldump',
    ];
    foreach ($candidatos as $cand) {
        if (strpos($cand, '*') !== false) {
            $matches = glob($cand);
            if ($matches) {
                foreach ($matches as $m) { if (is_file($m)) return $m; }
            }
        } elseif (is_file($cand)) {
            return $cand;
        }
    }

    return null;
}}

if (!function_exists('backup_one_database')) {
function backup_one_database(string $dump, array $mysqlCfg, string $arquivo): array {
    $socket = getenv('HOME') . '/.mariadb_data/mysql.sock';
    $useSocket = is_file($socket);

    $cmd = escapeshellarg($dump)
        . ' --single-transaction --quick --routines --triggers'
        . ' --default-character-set=utf8mb4'
        . ' --user=' . escapeshellarg($mysqlCfg['user'])
        . (($mysqlCfg['pass'] ?? '') !== '' ? ' --password=' . escapeshellarg($mysqlCfg['pass']) : '');

    if ($useSocket) {
        $cmd .= ' --socket=' . escapeshellarg($socket);
    } else {
        $cmd .= ' --host=' . escapeshellarg($mysqlCfg['host'])
              . ' --port=' . escapeshellarg((string)$mysqlCfg['port']);
    }
    $cmd .= ' ' . escapeshellarg($mysqlCfg['dbname'])
          . ' 2> ' . escapeshellarg($arquivo . '.err')
          . ' > '  . escapeshellarg($arquivo);

    exec($cmd, $out, $rc);
    $size = is_file($arquivo) ? (int)filesize($arquivo) : 0;
    $err  = is_file($arquivo . '.err') ? trim((string)file_get_contents($arquivo . '.err')) : '';
    @unlink($arquivo . '.err');

    if ($rc !== 0 || $size === 0) {
        if (is_file($arquivo) && $size === 0) @unlink($arquivo);
        return ['ok' => false, 'size' => 0, 'erro' => $err !== '' ? $err : "exit code $rc"];
    }
    return ['ok' => true, 'size' => $size, 'erro' => null];
}}

if (!function_exists('backup_run_now')) {
function backup_run_now(PDO $pdo, string $origem = 'manual'): array {
    backup_install_tables($pdo);

    $cfg = $pdo->query("SELECT * FROM backup_config ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$cfg) {
        return ['ok' => false, 'erro' => 'Configuração de backup não encontrada.'];
    }

    $customPath = (string)($cfg['mysqldump_path'] ?? '');
    $dump = backup_dump_command($customPath);
    if ($dump === null) {
        $erro = 'mysqldump/mariadb-dump não encontrado. '
              . 'Configure o caminho completo do executável no campo "Caminho do mysqldump" '
              . 'do painel de backup. '
              . 'Exemplo XAMPP (Windows): C:\\xampp\\mysql\\bin '
              . 'Exemplo Linux: /usr/bin/mysqldump';
        $pdo->prepare("INSERT INTO backup_historico (status, erro_mensagem, origem) VALUES ('erro', ?, ?)")
            ->execute([$erro, $origem]);
        return ['ok' => false, 'erro' => $erro];
    }

    $appCfg = require __DIR__ . '/../config/database.php';
    $destino = backup_resolve_destino($cfg['pasta_destino']);
    $ts = date('Y-m-d_His');

    // Usa o NOME REAL do banco (configurado no install/) ao invés de hardcode
    $dbMain = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($appCfg['mysql']['dbname'] ?? 'banco'));
    if ($dbMain === '') $dbMain = 'banco';
    $arqMain = $destino . '/' . $dbMain . '_' . $ts . '.sql';
    $resN = backup_one_database($dump, $appCfg['mysql'], $arqMain);

    $arqForum = null; $resF = ['ok' => true, 'size' => 0, 'erro' => null];
    if ((int)$cfg['incluir_forum'] === 1 && !empty($appCfg['mysql_forum']['dbname'])) {
        $dbForum = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$appCfg['mysql_forum']['dbname']);
        if ($dbForum === '') $dbForum = 'forum';
        $arqForum = $destino . '/' . $dbForum . '_' . $ts . '.sql';
        $resF = backup_one_database($dump, $appCfg['mysql_forum'], $arqForum);
    }

    $okGeral = $resN['ok'] && $resF['ok'];
    $tamTotal = (int)$resN['size'] + (int)$resF['size'];
    $erro = trim(($resN['erro'] ?? '') . ' ' . ($resF['erro'] ?? ''));

    $pdo->prepare("INSERT INTO backup_historico
        (arquivo_naruto, arquivo_forum, tamanho_bytes, status, erro_mensagem, origem)
        VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([
            $resN['ok'] && is_file($arqMain) ? basename($arqMain) : null,
            $resF['ok'] && $arqForum && is_file($arqForum) ? basename($arqForum) : null,
            $tamTotal,
            $okGeral ? 'sucesso' : 'erro',
            $erro !== '' ? $erro : null,
            $origem,
        ]);

    // Atualiza ultimo_backup e calcula proximo
    $proximo = backup_calc_proximo($cfg);
    $pdo->prepare("UPDATE backup_config SET ultimo_backup = NOW(), proximo_backup = ? WHERE id = ?")
        ->execute([$proximo->format('Y-m-d H:i:s'), $cfg['id']]);

    // Rotação: remove arquivos antigos além do limite max_backups
    backup_rotate($pdo, $destino, (int)$cfg['max_backups']);

    return [
        'ok'    => $okGeral,
        'erro'  => $erro,
        'arquivos' => array_filter([
            $resN['ok'] ? basename($arqMain) : null,
            $resF['ok'] && $arqForum ? basename($arqForum) : null,
        ]),
        'tamanho' => $tamTotal,
        'proximo' => $proximo->format('Y-m-d H:i:s'),
    ];
}}

if (!function_exists('backup_rotate')) {
function backup_rotate(PDO $pdo, string $destino, int $max): void {
    if ($max <= 0) $max = 30;
    // Pega todos os históricos com sucesso, ordenados do mais novo para o mais velho
    $rows = $pdo->query("SELECT id, arquivo_naruto, arquivo_forum FROM backup_historico
        WHERE status = 'sucesso' ORDER BY criado_em DESC")->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) <= $max) return;
    $excedentes = array_slice($rows, $max);
    foreach ($excedentes as $r) {
        foreach (['arquivo_naruto', 'arquivo_forum'] as $campo) {
            if (!empty($r[$campo])) {
                $abs = $destino . '/' . $r[$campo];
                if (is_file($abs)) @unlink($abs);
            }
        }
        $pdo->prepare("DELETE FROM backup_historico WHERE id = ?")->execute([$r['id']]);
    }
}}

if (!function_exists('backup_tick_check')) {
function backup_tick_check(PDO $pdo): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;
    try {
        // Tenta criar tabelas (idempotente). Em caso de falha, apenas ignora silenciosamente.
        backup_install_tables($pdo);
        $row = $pdo->query("SELECT id FROM backup_config
            WHERE ativo = 1
              AND proximo_backup IS NOT NULL
              AND proximo_backup <= NOW()
            LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$row) return;
        backup_run_now($pdo, 'auto');
    } catch (Exception $e) {
        error_log('[backup_tick] ' . $e->getMessage());
    }
}}
