<?php
/**
 * lib_sql_import.php — Importa um arquivo .sql (dump MySQL) em uma conexão PDO.
 *
 * Função pública:
 *   importar_dump_mysql(PDO $mysql, string $arquivo, array &$log = [], array $skipDataFor = []): void
 *
 * - Faz parsing simples de statements separados por ';' fora de strings/comentários.
 * - Executa cada statement via PDO::exec().
 * - Se uma tabela estiver em $skipDataFor, os INSERTs dela são ignorados
 *   (apenas a estrutura — CREATE TABLE — é aplicada).
 * - Aceita comentários `-- ...` e `/* ... *​/` e ignora linhas em branco.
 */

function importar_dump_mysql(PDO $mysql, string $arquivo, array &$log = [], array $skipDataFor = []): void
{
    if (!is_readable($arquivo)) {
        $log[] = "[ERRO] Arquivo não legível: $arquivo";
        return;
    }

    $skipMap = [];
    foreach ($skipDataFor as $t) {
        $skipMap[strtolower($t)] = true;
    }

    $sql = file_get_contents($arquivo);
    if ($sql === false) {
        $log[] = "[ERRO] Falha ao ler $arquivo";
        return;
    }

    // Divide o dump em statements. Considera strings entre aspas simples (com
    // escape via \\ ou '') para não cortar dentro de literais que contenham ';'.
    $statements = dividir_sql_statements($sql);

    $mysql->exec("SET FOREIGN_KEY_CHECKS=0");
    $mysql->exec("SET UNIQUE_CHECKS=0");

    $okCount = 0;
    $errCount = 0;
    $skipCount = 0;

    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '') continue;

        // Pula comentários puros
        $sample = ltrim($stmt);
        if (str_starts_with($sample, '--') || str_starts_with($sample, '#')) continue;
        if (str_starts_with($sample, '/*') && str_ends_with(rtrim($sample), '*/')) continue;

        // Se for INSERT em tabela presente em $skipDataFor, ignora
        if (preg_match('/^\s*INSERT\s+(?:IGNORE\s+)?INTO\s+`?([A-Za-z0-9_]+)`?/i', $stmt, $m)) {
            if (isset($skipMap[strtolower($m[1])])) {
                $skipCount++;
                continue;
            }
        }

        try {
            $mysql->exec($stmt);
            $okCount++;
        } catch (PDOException $e) {
            $errCount++;
            $resumo = mb_strimwidth(preg_replace('/\s+/', ' ', $stmt), 0, 120, '...');
            $log[] = "[ERRO SQL] " . $e->getMessage() . " | " . $resumo;
        }
    }

    $mysql->exec("SET FOREIGN_KEY_CHECKS=1");
    $mysql->exec("SET UNIQUE_CHECKS=1");

    $log[] = "[OK] Statements executados: $okCount | INSERTs pulados: $skipCount | Erros: $errCount";
}

/**
 * Divide o conteúdo de um dump SQL em statements individuais,
 * respeitando aspas simples ('...') e duplas ("..."), e crases (`...`).
 */
function dividir_sql_statements(string $sql): array
{
    $statements = [];
    $buf = '';
    $len = strlen($sql);
    $i = 0;
    $inSingle = false;
    $inDouble = false;
    $inBacktick = false;
    $inLineComment = false;
    $inBlockComment = false;

    while ($i < $len) {
        $c = $sql[$i];
        $next = ($i + 1 < $len) ? $sql[$i + 1] : '';

        // Fim de comentário de linha
        if ($inLineComment) {
            if ($c === "\n") $inLineComment = false;
            $i++;
            continue;
        }

        // Fim de comentário de bloco
        if ($inBlockComment) {
            if ($c === '*' && $next === '/') { $inBlockComment = false; $i += 2; continue; }
            $i++;
            continue;
        }

        // Detecta início de comentário (apenas fora de strings)
        if (!$inSingle && !$inDouble && !$inBacktick) {
            if ($c === '-' && $next === '-') { $inLineComment = true; $i += 2; continue; }
            if ($c === '#') { $inLineComment = true; $i++; continue; }
            if ($c === '/' && $next === '*') { $inBlockComment = true; $i += 2; continue; }
        }

        // Toggle de delimitadores de string
        if (!$inDouble && !$inBacktick && $c === "'") {
            if ($inSingle) {
                // Escape '' (duplicação)
                if ($next === "'") { $buf .= "''"; $i += 2; continue; }
                // Escape \'
                $prev = $i > 0 ? $sql[$i - 1] : '';
                if ($prev === '\\') { $buf .= $c; $i++; continue; }
                $inSingle = false;
            } else {
                $inSingle = true;
            }
            $buf .= $c;
            $i++;
            continue;
        }

        if (!$inSingle && !$inBacktick && $c === '"') {
            if ($inDouble) {
                if ($next === '"') { $buf .= '""'; $i += 2; continue; }
                $prev = $i > 0 ? $sql[$i - 1] : '';
                if ($prev === '\\') { $buf .= $c; $i++; continue; }
                $inDouble = false;
            } else {
                $inDouble = true;
            }
            $buf .= $c;
            $i++;
            continue;
        }

        if (!$inSingle && !$inDouble && $c === '`') {
            $inBacktick = !$inBacktick;
            $buf .= $c;
            $i++;
            continue;
        }

        // ; fora de strings/comentários encerra o statement
        if ($c === ';' && !$inSingle && !$inDouble && !$inBacktick) {
            $stmt = trim($buf);
            if ($stmt !== '') $statements[] = $stmt;
            $buf = '';
            $i++;
            continue;
        }

        $buf .= $c;
        $i++;
    }

    $stmt = trim($buf);
    if ($stmt !== '') $statements[] = $stmt;

    return $statements;
}
