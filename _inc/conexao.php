<?php
// Evitar output antes do session_start
ob_start();

if (session_status() == PHP_SESSION_NONE) {
    @session_start();
}

$mysql_banco = 'narutohi_nh2';

// Criar conexão SQLite
$db_file = __DIR__ . '/../database.sqlite';
try {
    $conexao = new PDO('sqlite:' . $db_file);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conexao->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Criar tabelas básicas se não existirem
    $conexao->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario VARCHAR(50) UNIQUE,
        senha VARCHAR(32),
        email VARCHAR(250) DEFAULT '',
        avatar INTEGER DEFAULT 0,
        missao INTEGER DEFAULT 0,
        missao_tempo INTEGER DEFAULT 0,
        missao_fim DATETIME,
        orgmissao INTEGER DEFAULT 0,
        status VARCHAR(20) DEFAULT 'ativo',
        yens INTEGER DEFAULT 1000,
        yens_fat INTEGER DEFAULT 0,
        nivel INTEGER DEFAULT 1,
        orgid INTEGER DEFAULT 0,
        energia INTEGER DEFAULT 100,
        energiamax INTEGER DEFAULT 100,
        taijutsu INTEGER DEFAULT 10,
        ninjutsu INTEGER DEFAULT 10,
        genjutsu INTEGER DEFAULT 10,
        personagem VARCHAR(20) DEFAULT 'naruto',
        renegado VARCHAR(3) DEFAULT 'nao',
        vila INTEGER DEFAULT 1,
        doujutsu INTEGER DEFAULT 0,
        exp INTEGER DEFAULT 0,
        expmax INTEGER DEFAULT 100,
        doujutsu_nivel INTEGER DEFAULT 0,
        doujutsu_exp INTEGER DEFAULT 0,
        doujutsu_expmax INTEGER DEFAULT 100,
        vip_inicio DATETIME,
        vip DATETIME DEFAULT '2000-01-01 00:00:00',
        hunt INTEGER DEFAULT 0,
        hunt_restantes INTEGER DEFAULT 14,
        treino INTEGER DEFAULT 0,
        penalidade_fim DATETIME,
        config_radio INTEGER DEFAULT 1,
        config_atualizacoes VARCHAR(3) DEFAULT 'sim',
        config_twitter VARCHAR(50) DEFAULT '',
        config_viewtwitter VARCHAR(3) DEFAULT 'nao',
        config_oktwitter VARCHAR(3) DEFAULT 'nao',
        loginip INTEGER DEFAULT 0,
        ip INTEGER DEFAULT 0,
        reg DATETIME,
        natureza1 VARCHAR(20) DEFAULT '',
        natureza2 VARCHAR(20) DEFAULT '',
        natureza3 VARCHAR(20) DEFAULT '',
        timestamp INTEGER DEFAULT 0
    )");

    // Adicionar colunas que podem estar faltando (para bancos existentes)
    $columns_to_add = [
        "ALTER TABLE usuarios ADD COLUMN email VARCHAR(250) DEFAULT ''",
        "ALTER TABLE usuarios ADD COLUMN ip INTEGER DEFAULT 0",
        "ALTER TABLE usuarios ADD COLUMN hunt_restantes INTEGER DEFAULT 14",
        "ALTER TABLE usuarios ADD COLUMN reg DATETIME DEFAULT CURRENT_TIMESTAMP",
        "ALTER TABLE usuarios ADD COLUMN natureza1 VARCHAR(20) DEFAULT ''",
        "ALTER TABLE usuarios ADD COLUMN natureza2 VARCHAR(20) DEFAULT ''",
        "ALTER TABLE usuarios ADD COLUMN natureza3 VARCHAR(20) DEFAULT ''",
        "ALTER TABLE usuarios ADD COLUMN adm INTEGER DEFAULT 0",
        "ALTER TABLE usuarios ADD COLUMN ban_motivo TEXT DEFAULT ''",
        "ALTER TABLE usuarios ADD COLUMN ban_fim DATETIME DEFAULT '2000-01-01 00:00:00'",
        "ALTER TABLE usuarios ADD COLUMN missao_tempo INTEGER DEFAULT 0",
        "ALTER TABLE usuarios ADD COLUMN orgmissao INTEGER DEFAULT 0",
        "ALTER TABLE usuarios ADD COLUMN preso INTEGER DEFAULT 0",
        "ALTER TABLE usuarios ADD COLUMN tipo VARCHAR(20) DEFAULT 'normal'",
        "ALTER TABLE usuarios ADD COLUMN vitorias INTEGER DEFAULT 0",
        "ALTER TABLE usuarios ADD COLUMN derrotas INTEGER DEFAULT 0",
        "ALTER TABLE usuarios ADD COLUMN yens_fat REAL DEFAULT 0.00",
        "ALTER TABLE usuarios ADD COLUMN personagem INTEGER DEFAULT 1",
        "ALTER TABLE usuarios ADD COLUMN empates INTEGER DEFAULT 0",
        "ALTER TABLE usuarios ADD COLUMN yens_perd REAL DEFAULT 0.00",
        "ALTER TABLE usuarios ADD COLUMN batalhas INTEGER DEFAULT 0",
        "ALTER TABLE usuarios ADD COLUMN exptotal INTEGER DEFAULT 0",
        "ALTER TABLE usuarios ADD COLUMN energia_ultima_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP",
        "ALTER TABLE usuarios ADD COLUMN doujutsu INTEGER DEFAULT 0",
        "ALTER TABLE usuarios ADD COLUMN orgid INTEGER DEFAULT 0",
        "ALTER TABLE usuarios ADD COLUMN orgnome VARCHAR(50) DEFAULT ''",
        "ALTER TABLE usuarios ADD COLUMN config_twitter VARCHAR(100) DEFAULT ''",
        "ALTER TABLE usuarios ADD COLUMN config_oktwitter VARCHAR(10) DEFAULT 'nao'",
        "ALTER TABLE usuarios ADD COLUMN config_apresentacao TEXT DEFAULT ''",
        "ALTER TABLE usuarios ADD COLUMN config_atualizacoes VARCHAR(10) DEFAULT 'sim'",
        "ALTER TABLE amigos ADD COLUMN status VARCHAR(10) DEFAULT 'sim'",
        "ALTER TABLE inventario ADD COLUMN status VARCHAR(10) DEFAULT 'off'",
        "CREATE TABLE IF NOT EXISTS amigos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuarioid INTEGER,
            amigoid INTEGER,
            timestamp INTEGER DEFAULT 0
        )",
        "CREATE TABLE IF NOT EXISTS book (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuarioid INTEGER,
            inimigoid INTEGER,
            timestamp INTEGER DEFAULT 0
        )",
        "CREATE TABLE IF NOT EXISTS inventario (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuarioid INTEGER,
            itemid INTEGER,
            venda VARCHAR(10) DEFAULT 'nao',
            valor REAL DEFAULT 0.00,
            upgrade INTEGER DEFAULT 0
        )",
        "CREATE TABLE IF NOT EXISTS table_itens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            categoria VARCHAR(50),
            descricao TEXT,
            taijutsu INTEGER DEFAULT 0,
            ninjutsu INTEGER DEFAULT 0,
            genjutsu INTEGER DEFAULT 0,
            nome VARCHAR(100),
            imagem VARCHAR(100),
            valor REAL DEFAULT 0.00
        )",
        "CREATE TABLE IF NOT EXISTS ramen (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuarioid INTEGER,
            ramenid INTEGER
        )",
        "CREATE TABLE IF NOT EXISTS atualizacoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuarioid INTEGER,
            texto TEXT,
            hora DATETIME DEFAULT CURRENT_TIMESTAMP
        )"
    ];

    foreach ($columns_to_add as $sql) {
        try {
            $conexao->exec($sql);
        } catch (PDOException $e) {
            // Coluna já existe, ignorar erro
        }
    }

    $conexao->exec("CREATE TABLE IF NOT EXISTS organizacoes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome VARCHAR(100),
        nivel INTEGER DEFAULT 1
    )");

    $conexao->exec("CREATE TABLE IF NOT EXISTS block (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip VARCHAR(45),
        tentativas INTEGER DEFAULT 1,
        timestamp INTEGER
    )");

    $conexao->exec("CREATE TABLE IF NOT EXISTS mensagens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        origem INTEGER,
        destino INTEGER,
        assunto VARCHAR(60),
        msg TEXT,
        data DATETIME DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(10) DEFAULT 'naolido'
    )");

    $conexao->exec("CREATE TABLE IF NOT EXISTS relatorios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        data DATETIME DEFAULT CURRENT_TIMESTAMP,
        usuarioid INTEGER,
        inimigoid INTEGER,
        vencedor INTEGER,
        exp VARCHAR(20),
        yens REAL,
        taijutsu VARCHAR(50),
        ninjutsu VARCHAR(50),
        genjutsu VARCHAR(50),
        energia VARCHAR(50),
        equips1 VARCHAR(100),
        equips2 VARCHAR(100),
        danos VARCHAR(50),
        nivel VARCHAR(20),
        doujutsu VARCHAR(20),
        status VARCHAR(10) DEFAULT 'nao'
    )");

    $conexao->exec("CREATE TABLE IF NOT EXISTS salas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuarioid INTEGER,
        sala VARCHAR(50),
        fim DATETIME,
        data DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $conexao->exec("CREATE TABLE IF NOT EXISTS jutsus (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuarioid INTEGER,
        jutsu INTEGER
    )");

    $conexao->exec("CREATE TABLE IF NOT EXISTS table_jutsus (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome VARCHAR(100),
        nivel INTEGER DEFAULT 1,
        natureza VARCHAR(20) DEFAULT 'nenhum',
        forca INTEGER DEFAULT 0,
        valor REAL DEFAULT 0.00,
        doujutsu INTEGER DEFAULT 0,
        doujutsu_nivel INTEGER DEFAULT 0
    )");

    $conexao->exec("CREATE TABLE IF NOT EXISTS chat_messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        username VARCHAR(50),
        message TEXT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $conexao->exec("CREATE TABLE IF NOT EXISTS chat_online (
        user_id INTEGER PRIMARY KEY,
        username VARCHAR(50),
        vila VARCHAR(50),
        last_seen DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Funções de compatibilidade com MySQL (apenas as antigas funções mysql_*)
function mysql_query($query, $connection = null) {
    global $conexao;
    try {
        $stmt = $conexao->prepare($query);
        $stmt->execute();
        return $stmt;
    } catch (PDOException $e) {
        error_log("MySQL Query Error: " . $e->getMessage() . " - Query: " . $query);
        return false;
    }
}

function mysql_fetch_assoc($result) {
    if ($result && is_object($result)) {
        return $result->fetch(PDO::FETCH_ASSOC);
    }
    return false;
}

function mysql_fetch_array($result, $result_type = null) {
    if ($result && is_object($result)) {
        if ($result_type === MYSQL_ASSOC) {
            return $result->fetch(PDO::FETCH_ASSOC);
        } elseif ($result_type === MYSQL_NUM) {
            return $result->fetch(PDO::FETCH_NUM);
        } else {
            return $result->fetch(PDO::FETCH_BOTH);
        }
    }
    return false;
}

function mysql_num_rows($result) {
    if ($result && is_object($result)) {
        // Para SELECT, precisamos contar as linhas manualmente
        $count = 0;
        $result->execute();
        while ($result->fetch()) {
            $count++;
        }
        $result->execute(); // Re-executa para poder usar fetch novamente
        return $count;
    }
    return 0;
}

function mysql_free_result($result) {
    if ($result && is_object($result)) {
        $result->closeCursor();
    }
}

function mysql_real_escape_string($string, $connection = null) {
    global $conexao;
    return $conexao->quote($string);
}

function mysql_escape_string($string) {
    global $conexao;
    return $conexao->quote($string);
}

function mysql_error() {
    global $conexao;
    $error = $conexao->errorInfo();
    return isset($error[2]) ? $error[2] : '';
}

function mysql_insert_id() {
    global $conexao;
    return $conexao->lastInsertId();
}

function mysql_affected_rows() {
    global $conexao;
    return $conexao->rowCount();
}

function mysql_select_db($database, $connection = null) {
    // SQLite não precisa selecionar database
    return true;
}

function mysql_connect($host, $user, $password) {
    // Handled by PDO SQLite connection above
    return true;
}

function mysql_pconnect($host, $user, $password) {
    // Handled by PDO SQLite connection above
    return true;
}

// Define MySQL constants for compatibility
if (!defined('MYSQL_ASSOC')) {
    define('MYSQL_ASSOC', PDO::FETCH_ASSOC);
}
if (!defined('MYSQL_NUM')) {
    define('MYSQL_NUM', PDO::FETCH_NUM);
}
if (!defined('MYSQL_BOTH')) {
    define('MYSQL_BOTH', PDO::FETCH_BOTH);
}
?>
</replit_final_file>