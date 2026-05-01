<?php
/**
 * Database — fábrica única de PDO para MySQL.
 *
 * Uso:
 *   $pdo = Database::conn();
 *
 * Para retrocompatibilidade, conexao.php continua expondo $conexao
 * como o mesmo objeto retornado aqui. Os helpers (isMysql, nowExpr,
 * autoIncPK, etc.) ainda existem para que código antigo siga funcionando
 * — todos resolvem para variantes MySQL.
 */
class Database
{
    private static ?PDO $instance = null;
    private static ?PDO $forumInstance = null;
    private static ?array $cfgCache = null;

    private static function cfg(): array
    {
        if (self::$cfgCache === null) {
            self::$cfgCache = require __DIR__ . '/../config/database.php';
        }
        return self::$cfgCache;
    }

    /**
     * Conexão dedicada ao fórum. Se a config tiver bloco 'mysql_forum',
     * abre uma segunda conexão para esse banco. Caso contrário, retorna
     * a mesma conexão principal (compartilhamento de banco).
     */
    public static function forumConn(): PDO
    {
        if (self::$forumInstance instanceof PDO) {
            return self::$forumInstance;
        }
        $cfg = self::cfg();
        if (!empty($cfg['mysql_forum']['dbname'])) {
            $m = $cfg['mysql_forum'];
            $dsn = "mysql:host={$m['host']};port={$m['port']};dbname={$m['dbname']};charset={$m['charset']}";
            self::$forumInstance = new PDO($dsn, $m['user'], $m['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            return self::$forumInstance;
        }
        // Sem banco separado: reutiliza a conexão principal.
        self::$forumInstance = self::conn();
        return self::$forumInstance;
    }

    public static function conn(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $cfg = self::cfg();

        try {
            $m = $cfg['mysql'];
            $dsn = "mysql:host={$m['host']};port={$m['port']};dbname={$m['dbname']};charset={$m['charset']}";
            self::$instance = new PDO($dsn, $m['user'], $m['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Não vazar credenciais ou caminhos para o cliente
            error_log('[DB] ' . $e->getMessage());
            // Se ainda não foi instalado, manda para o instalador
            if (file_exists(__DIR__ . '/../install/install.php')) {
                header('Location: install/install.php');
                exit;
            }
            if (!file_exists(__DIR__ . '/../setup.php')) {
                http_response_code(500);
                die('Erro de conexão com o banco de dados.');
            }
            header('Location: setup.php');
            exit;
        }

        return self::$instance;
    }

    public static function driver(): string
    {
        return 'mysql';
    }

    public static function isMysql(): bool
    {
        return true;
    }

    /**
     * Helper: retorna expressão SQL para "agora" (CURRENT_TIMESTAMP).
     */
    public static function nowExpr(): string
    {
        return 'CURRENT_TIMESTAMP';
    }

    /**
     * Helper: expressão SQL para "agora ± N <unidade>" usando placeholder ?.
     *   $sign  '+' ou '-'
     *   $unit  'days' | 'hours' | 'minutes' | 'seconds'
     * Retorna DATE_ADD/DATE_SUB(NOW(), INTERVAL ? UNIT).
     */
    public static function dateOffsetParam(string $sign, string $unit): string
    {
        $unit = strtolower($unit);
        $map = ['days' => 'DAY', 'hours' => 'HOUR', 'minutes' => 'MINUTE', 'seconds' => 'SECOND'];
        $u = $map[$unit] ?? strtoupper(rtrim($unit, 's'));
        $func = $sign === '-' ? 'DATE_SUB' : 'DATE_ADD';
        return "$func(NOW(), INTERVAL ? $u)";
    }

    /**
     * Helper: expressão SQL para "agora ± N <unidade>" com valor literal.
     *   ex.: dateOffsetLiteral('-', 24, 'hours')
     */
    public static function dateOffsetLiteral(string $sign, int $n, string $unit): string
    {
        $unit = strtolower($unit);
        $map = ['days' => 'DAY', 'hours' => 'HOUR', 'minutes' => 'MINUTE', 'seconds' => 'SECOND'];
        $u = $map[$unit] ?? strtoupper(rtrim($unit, 's'));
        $func = $sign === '-' ? 'DATE_SUB' : 'DATE_ADD';
        return "$func(NOW(), INTERVAL $n $u)";
    }

    /**
     * Helper: declaração de coluna PRIMARY KEY auto-incremento (MySQL).
     */
    public static function autoIncPK(string $colName = 'id'): string
    {
        return "$colName INT NOT NULL AUTO_INCREMENT PRIMARY KEY";
    }

    /**
     * Helper: verifica se uma tabela existe.
     */
    public static function tableExists(PDO $conexao, string $tableName): bool
    {
        try {
            $stmt = $conexao->prepare(
                "SELECT 1 FROM information_schema.tables 
                 WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1"
            );
            $stmt->execute([$tableName]);
            return (bool) $stmt->fetchColumn();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Helper: lista colunas de uma tabela. Retorna array de nomes de colunas.
     */
    public static function tableColumns(PDO $conexao, string $tableName): array
    {
        try {
            $stmt = $conexao->prepare(
                "SELECT column_name AS name FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = ?"
            );
            $stmt->execute([$tableName]);
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Helper: liga/desliga foreign keys.
     */
    public static function setForeignKeys(PDO $conexao, bool $on): void
    {
        try {
            $conexao->exec("SET FOREIGN_KEY_CHECKS = " . ($on ? '1' : '0'));
        } catch (Exception $e) {}
    }

    /**
     * Helper: lista nomes de tabelas.
     */
    public static function listTables(PDO $conexao): array
    {
        try {
            $rows = $conexao->query(
                "SELECT table_name AS name FROM information_schema.tables 
                 WHERE table_schema = DATABASE() ORDER BY table_name"
            )->fetchAll(PDO::FETCH_ASSOC);
            return array_column($rows, 'name');
        } catch (Exception $e) {
            return [];
        }
    }
}
