
CREATE DATABASE IF NOT EXISTS narutohi_nh2;
USE narutohi_nh2;

-- Criar tabela de usuários básica
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(32) NOT NULL,
    email VARCHAR(100),
    avatar INT DEFAULT 0,
    nivel INT DEFAULT 1,
    exp INT DEFAULT 0,
    expmax INT DEFAULT 100,
    energia INT DEFAULT 100,
    energiamax INT DEFAULT 100,
    taijutsu INT DEFAULT 10,
    ninjutsu INT DEFAULT 10,
    genjutsu INT DEFAULT 10,
    yens INT DEFAULT 1000,
    yens_fat INT DEFAULT 0,
    personagem INT DEFAULT 1,
    vila INT DEFAULT 1,
    renegado INT DEFAULT 0,
    orgid INT DEFAULT 0,
    doujutsu INT DEFAULT 0,
    doujutsu_nivel INT DEFAULT 0,
    doujutsu_exp INT DEFAULT 0,
    doujutsu_expmax INT DEFAULT 100,
    vip_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    vip DATETIME DEFAULT CURRENT_TIMESTAMP,
    missao INT DEFAULT 0,
    missao_fim DATETIME DEFAULT CURRENT_TIMESTAMP,
    hunt INT DEFAULT 0,
    treino INT DEFAULT 0,
    penalidade_fim DATETIME DEFAULT CURRENT_TIMESTAMP,
    config_radio INT DEFAULT 1,
    loginip BIGINT DEFAULT 0,
    status ENUM('ativo','inativo','banido') DEFAULT 'ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Criar tabela de organizações
CREATE TABLE IF NOT EXISTS organizacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    nivel INT DEFAULT 1,
    lider INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Criar tabela de bloqueios
CREATE TABLE IF NOT EXISTS block (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    tentativas INT DEFAULT 1,
    timestamp INT NOT NULL
);

-- Inserir dados básicos
INSERT IGNORE INTO usuarios (id, usuario, senha, email, avatar, nivel) 
VALUES (1, 'admin', MD5('admin123'), 'admin@narutohit.com', 1, 50);
