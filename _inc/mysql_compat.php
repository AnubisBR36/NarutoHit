<?php
// MySQL to PDO Compatibility Layer
// This file provides mysql_* functions using the PDO connection

global $conexao, $mysql_last_stmt, $mysql_result_cache, $mysql_last_query_type;
$mysql_last_stmt = null;
$mysql_result_cache = [];
$mysql_last_query_type = null;

// ---------------------------------------------------------------------------
// Migrações MySQL (executam apenas uma vez por carregamento; ignoram falhas).
// Corrigem: tipo da coluna `personagem` em `usuarios` e o esquema da tabela
// `personagens` (uma linha por usuário, com colunas booleanas por personagem
// catalogado). Carregadas a partir do catálogo central em personagens_catalogo.php.
// ---------------------------------------------------------------------------
if (isset($conexao) && $conexao instanceof PDO) {
    try {
        $conexao->exec("ALTER TABLE `usuarios` MODIFY COLUMN `personagem` VARCHAR(50) NOT NULL DEFAULT ''");
    } catch (PDOException $e) { /* já era VARCHAR ou não existe — ignora */ }
    // Normaliza apenas valores legados '0'/'1' (de versões muito antigas)
    // para vazio. NÃO sobrescreve mais NULL/'' com 'naruto' — o jogador
    // deve escolher o personagem em reg.php / config_char.php.
    try {
        $conexao->exec("UPDATE `usuarios` SET `personagem`='' WHERE `personagem`='0' OR `personagem`='1'");
    } catch (PDOException $e) {}

    // Garante o esquema correto da tabela `personagens`.
    require_once __DIR__ . '/personagens_catalogo.php';
    try {
        // Tenta detectar se a coluna `usuarioid` existe; se não, faz reset
        // da tabela (estava com layout de catálogo, não de unlock por usuário).
        $tem_usuarioid = false;
        try {
            $r = $conexao->query("SHOW COLUMNS FROM `personagens` LIKE 'usuarioid'");
            $tem_usuarioid = (bool)($r && $r->fetch(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {}

        if (!$tem_usuarioid) {
            // Schema antigo (catálogo) — descartar e recriar com layout per-user.
            try { $conexao->exec("DROP TABLE IF EXISTS `personagens`"); } catch (PDOException $e) {}
            $cols = ["`id` INT AUTO_INCREMENT PRIMARY KEY", "`usuarioid` INT NOT NULL"];
            foreach (personagens_lista_chaves() as $chave) {
                $cols[] = "`{$chave}` TINYINT(1) NOT NULL DEFAULT 0";
            }
            $cols[] = "UNIQUE KEY `uniq_usuario` (`usuarioid`)";
            $sql = "CREATE TABLE `personagens` (" . implode(",\n  ", $cols)
                 . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $conexao->exec($sql);
        } else {
            // Schema correto já existe — só garante que cada coluna do catálogo
            // está presente (cobre upgrades futuros do catálogo).
            $existentes = [];
            try {
                $r = $conexao->query("SHOW COLUMNS FROM `personagens`");
                if ($r) foreach ($r->fetchAll(PDO::FETCH_ASSOC) as $row) $existentes[strtolower($row['Field'])] = true;
            } catch (PDOException $e) {}
            foreach (personagens_lista_chaves() as $chave) {
                if (isset($existentes[strtolower($chave)])) continue;
                try { $conexao->exec("ALTER TABLE `personagens` ADD COLUMN `{$chave}` TINYINT(1) NOT NULL DEFAULT 0"); } catch (PDOException $e) {}
            }
        }
    } catch (PDOException $e) {}

    // ---------------------------------------------------------------------
    // Auto-migração: tag "comprado de X por Y" no inventário.
    // Adiciona colunas em `inventario` para guardar o vendedor e valor pago
    // quando o item for comprado de outro jogador via mercado/loja.
    // ---------------------------------------------------------------------
    try {
        $existentes_inv = [];
        try {
            $r = $conexao->query("SHOW COLUMNS FROM `inventario`");
            if ($r) foreach ($r->fetchAll(PDO::FETCH_ASSOC) as $row) $existentes_inv[strtolower($row['Field'])] = true;
        } catch (PDOException $e) {}

        $migrar_inv = [
            'comprado_de_id'    => 'INT NULL',
            'comprado_de_nome'  => 'VARCHAR(60) NULL',
            'comprado_valor'    => 'DOUBLE NULL',
            'comprado_moeda'    => 'VARCHAR(10) NULL',
            'comprado_data'     => 'DATETIME NULL',
        ];
        foreach ($migrar_inv as $col => $tipo) {
            if (isset($existentes_inv[strtolower($col)])) continue;
            try { $conexao->exec("ALTER TABLE `inventario` ADD COLUMN `{$col}` {$tipo}"); } catch (PDOException $e) {}
        }
    } catch (PDOException $e) {}
}

function mysql_query($query, $link_identifier = null) {
    global $conexao, $mysql_last_stmt, $mysql_last_query_type;
    try {
        $stmt = $conexao->prepare($query);
        $success = $stmt->execute();
        
        if ($success) {
            $mysql_last_stmt = $stmt;
            
            // Determine query type
            $trimmed = trim($query);
            $is_select = stripos($trimmed, 'SELECT') === 0 || 
                         stripos($trimmed, 'SHOW') === 0 || 
                         stripos($trimmed, 'DESCRIBE') === 0 ||
                         stripos($trimmed, 'EXPLAIN') === 0;
            
            $mysql_last_query_type = $is_select ? 'SELECT' : 'WRITE';
            
            // For SELECT queries, return the statement
            // For INSERT/UPDATE/DELETE, return true
            return $is_select ? $stmt : true;
        }
        
        $mysql_last_stmt = false;
        $mysql_last_query_type = null;
        return false;
    } catch (PDOException $e) {
        $mysql_last_stmt = false;
        $mysql_last_query_type = null;
        return false;
    }
}

function mysql_fetch_assoc($result) {
    global $mysql_result_cache, $mysql_last_stmt;
    
    if ($result === false || $result === true) {
        return false;
    }
    
    // Handle case where mysql_query returned true for INSERT/UPDATE/DELETE
    // Use the last statement instead
    if ($result === true && $mysql_last_stmt) {
        $result = $mysql_last_stmt;
    }
    
    try {
        $result_id = spl_object_id($result);
        
        // Load all rows on first access to ensure mysql_num_rows() always has full count
        if (!isset($mysql_result_cache[$result_id]['all_rows'])) {
            $mysql_result_cache[$result_id]['all_rows'] = $result->fetchAll(PDO::FETCH_ASSOC);
            $mysql_result_cache[$result_id]['row_index'] = 0;
        }
        
        $row_index = $mysql_result_cache[$result_id]['row_index'];
        $all_rows = $mysql_result_cache[$result_id]['all_rows'];
        
        if ($row_index < count($all_rows)) {
            $mysql_result_cache[$result_id]['row_index'] = $row_index + 1;
            return $all_rows[$row_index];
        }
        
        return false;
    } catch (Exception $e) {
        return false;
    }
}

function mysql_num_rows($result) {
    global $mysql_result_cache, $mysql_last_stmt;
    
    if ($result === false || $result === true) {
        return 0;
    }
    
    // Handle case where mysql_query returned true for INSERT/UPDATE/DELETE
    if ($result === true && $mysql_last_stmt) {
        $result = $mysql_last_stmt;
    }
    
    try {
        $result_id = spl_object_id($result);
        
        // For SELECT queries, we need to fetch all rows to count them
        if (!isset($mysql_result_cache[$result_id]['all_rows'])) {
            $mysql_result_cache[$result_id]['all_rows'] = $result->fetchAll(PDO::FETCH_ASSOC);
            $mysql_result_cache[$result_id]['row_index'] = 0;
        }
        
        return count($mysql_result_cache[$result_id]['all_rows']);
    } catch (Exception $e) {
        return 0;
    }
}

function mysql_affected_rows($link_identifier = null) {
    global $mysql_last_stmt, $mysql_last_query_type;
    
    // Return 0 for SELECT queries (legacy behavior)
    if ($mysql_last_query_type === 'SELECT') {
        return 0;
    }
    
    if ($mysql_last_stmt === false || $mysql_last_stmt === null) {
        return 0;
    }
    
    try {
        return $mysql_last_stmt->rowCount();
    } catch (Exception $e) {
        return 0;
    }
}

function mysql_insert_id($link_identifier = null) {
    global $conexao;
    return $conexao->lastInsertId();
}

function mysql_real_escape_string($string, $link_identifier = null) {
    global $conexao;
    return trim($conexao->quote($string), "'");
}

function mysql_escape_string($string) {
    return mysql_real_escape_string($string);
}

function mysql_error($link_identifier = null) {
    global $conexao;
    $error = $conexao->errorInfo();
    return $error[2] ?? '';
}

function mysql_errno($link_identifier = null) {
    global $conexao;
    $error = $conexao->errorInfo();
    return $error[1] ?? 0;
}

function mysql_result($result, $row, $field = 0) {
    global $mysql_result_cache;
    
    if ($result === false) {
        return false;
    }
    
    try {
        $result_id = spl_object_id($result);
        
        // Use cached data if available, otherwise fetch all
        if (!isset($mysql_result_cache[$result_id]['all_rows'])) {
            $mysql_result_cache[$result_id]['all_rows'] = $result->fetchAll(PDO::FETCH_ASSOC);
            $mysql_result_cache[$result_id]['row_index'] = 0;
        }
        
        $data = $mysql_result_cache[$result_id]['all_rows'];
        
        if (isset($data[$row])) {
            if (is_numeric($field)) {
                $values = array_values($data[$row]);
                return $values[$field] ?? false;
            } else {
                return $data[$row][$field] ?? false;
            }
        }
        return false;
    } catch (Exception $e) {
        return false;
    }
}

function mysql_free_result($result) {
    global $mysql_result_cache;
    
    if ($result === false || $result === null || !is_object($result)) {
        return false;
    }
    
    try {
        $result_id = spl_object_id($result);
        
        // Clear cache for this result
        if (isset($mysql_result_cache[$result_id])) {
            unset($mysql_result_cache[$result_id]);
        }
        
        $result->closeCursor();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
