-- ============================================================
-- Dump MySQL gerado a partir de database.sqlite
-- Data de geração: 2026-04-25 16:04:02
-- Compatível com MySQL 5.7+ / MariaDB 10.x (utf8mb4)
-- Importar com: mysql -u USER -p NOME_DB < database.sql
-- Ou via phpMyAdmin > Importar.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;
-- NO_AUTO_VALUE_ON_ZERO: permite preservar id=0 em colunas AUTO_INCREMENT
-- (necessário para `servidores`, onde o "Servidor 0" representa Konoha).
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

-- ------------------------------------------------------------
-- Tabela: `admin_logs`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `admin_logs`;
CREATE TABLE `admin_logs` (
  `id` INT AUTO_INCREMENT,
  `autor_id` INT NOT NULL,
  `autor_nome` TEXT NOT NULL,
  `acao` TEXT NOT NULL,
  `alvo_id` INT DEFAULT NULL,
  `alvo_nome` TEXT DEFAULT NULL,
  `detalhes` TEXT DEFAULT NULL,
  `data_hora` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `amigos`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `amigos`;
CREATE TABLE `amigos` (
  `id` INT AUTO_INCREMENT,
  `usuarioid` INT,
  `amigoid` INT,
  `timestamp` INT DEFAULT 0,
  `status` VARCHAR(10) DEFAULT 'sim',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `ataques_invasao`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `ataques_invasao`;
CREATE TABLE `ataques_invasao` (
  `id` INT AUTO_INCREMENT,
  `invasao_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `dano_causado` INT NOT NULL,
  `data_ataque` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `atualizacoes`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `atualizacoes`;
CREATE TABLE `atualizacoes` (
  `id` INT AUTO_INCREMENT,
  `usuarioid` INT,
  `texto` TEXT,
  `hora` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `ban_historico`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `ban_historico`;
CREATE TABLE `ban_historico` (
  `id` INT AUTO_INCREMENT,
  `usuario_id` INT NOT NULL,
  `motivo` TEXT,
  `duracao` INT,
  `ban_data` DATETIME,
  `ban_fim` DATETIME,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `banner_invasao_visualizado`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `banner_invasao_visualizado`;
CREATE TABLE `banner_invasao_visualizado` (
  `id` INT AUTO_INCREMENT,
  `usuario_id` INT NOT NULL,
  `invasao_id` INT NOT NULL,
  `tipo_banner` TEXT NOT NULL,
  `data_visualizacao` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE (`usuario_id`,`invasao_id`,`tipo_banner`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `block`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `block`;
CREATE TABLE `block` (
  `id` INT AUTO_INCREMENT,
  `ip` VARCHAR(45),
  `tentativas` INT DEFAULT 1,
  `timestamp` INT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `book`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `book`;
CREATE TABLE `book` (
  `id` INT AUTO_INCREMENT,
  `usuarioid` INT,
  `inimigoid` INT,
  `timestamp` INT DEFAULT 0,
  `nota` TEXT DEFAULT '',
  `snap_tai` INT DEFAULT 0,
  `snap_nin` INT DEFAULT 0,
  `snap_gen` INT DEFAULT 0,
  `snap_nivel` INT DEFAULT 1,
  `snap_vila` INT DEFAULT 1,
  `snap_renegado` VARCHAR(3) DEFAULT 'nao',
  `snap_personagem` VARCHAR(50) DEFAULT '',
  `snap_avatar` INT DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `buff_ativos`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `buff_ativos`;
CREATE TABLE `buff_ativos` (
  `id` INT AUTO_INCREMENT,
  `usuarioid` INT NOT NULL,
  `tipo_buff` VARCHAR(20) NOT NULL,
  `pct` INT NOT NULL DEFAULT 5,
  `expira_em` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `buff_fragmentos`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `buff_fragmentos`;
CREATE TABLE `buff_fragmentos` (
  `id` INT AUTO_INCREMENT,
  `usuarioid` INT NOT NULL,
  `itemid` INT NOT NULL,
  `quantidade` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE (`usuarioid`,`itemid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `chat_bans`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `chat_bans`;
CREATE TABLE `chat_bans` (
  `id` INT AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `username` VARCHAR(50) NOT NULL,
  `ban_type` VARCHAR(10) NOT NULL,
  `banned_until` DATETIME,
  `banned_by` INT NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `chat_messages`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `chat_messages`;
CREATE TABLE `chat_messages` (
  `id` INT AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `username` VARCHAR(50) NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `chat_online`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `chat_online`;
CREATE TABLE `chat_online` (
  `user_id` INT,
  `username` VARCHAR(50) NOT NULL,
  `vila` VARCHAR(50) NOT NULL,
  `last_seen` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `configuracoes`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `configuracoes`;
CREATE TABLE `configuracoes` (
  `id` INT AUTO_INCREMENT,
  `nome` VARCHAR(100) NOT NULL,
  `valor` TEXT NOT NULL,
  `descricao` TEXT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `configuracoes` (`nome`, `valor`, `descricao`) VALUES
('cadastro_aberto', '1', 'Permite novos cadastros no site (1=sim, 0=não)'),
('pvp_ativo',       '1', 'Permite PVP entre jogadores (1=sim, 0=não)');



-- ------------------------------------------------------------
-- Tabela: `criador_refs`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `criador_refs`;
CREATE TABLE `criador_refs` (
  `id` INT AUTO_INCREMENT,
  `criador_id` INT NOT NULL,
  `novo_usuario_id` INT NOT NULL,
  `novo_usuario_nome` VARCHAR(60) NOT NULL,
  `data` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE INDEX `idx_criador_refs_criador` ON `criador_refs` (criador_id);

-- ------------------------------------------------------------
-- Tabela: `floresta_icones`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `floresta_icones`;
CREATE TABLE `floresta_icones` (
  `id` INT AUTO_INCREMENT,
  `mapa` VARCHAR(100) NOT NULL,
  `nome` VARCHAR(150) NOT NULL,
  `x` INT NOT NULL,
  `y` INT NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `descricao` TEXT NOT NULL,
  `ativo` INT DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE INDEX `idx_floresta_icones_mapa` ON `floresta_icones` (mapa);
CREATE INDEX `idx_floresta_icones_posicao` ON `floresta_icones` (mapa, x, y);

-- ------------------------------------------------------------
-- Tabela: `fragmentos`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `fragmentos`;
CREATE TABLE `fragmentos` (
  `id` INT AUTO_INCREMENT,
  `usuarioid` INT NOT NULL,
  `itemid` INT NOT NULL,
  `quantidade` INT DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE (`usuarioid`,`itemid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `gm_permissions`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `gm_permissions`;
CREATE TABLE `gm_permissions` (
  `usuario_id` INT NOT NULL,
  `modulo` VARCHAR(50) NOT NULL,
  `permitido` INT DEFAULT 0,
  PRIMARY KEY (`usuario_id`,`modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `invasoes`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `invasoes`;
CREATE TABLE `invasoes` (
  `id` INT AUTO_INCREMENT,
  `nome_invasor` VARCHAR(100) NOT NULL,
  `imagem_arquivo` VARCHAR(100) NOT NULL,
  `hp_total` INT NOT NULL DEFAULT 2000000,
  `hp_atual` INT NOT NULL DEFAULT 2000000,
  `premio_yens` INT NOT NULL DEFAULT 1000000,
  `players_derrotados` INT NOT NULL DEFAULT 0,
  `data_inicio` DATETIME NOT NULL,
  `data_fim` DATETIME,
  `status` VARCHAR(20) NOT NULL DEFAULT 'ativa',
  `vencedor_id` INT,
  `bonus_vila` INT NOT NULL DEFAULT 10,
  `servidor_id` INT DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `inventario`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `inventario`;
CREATE TABLE `inventario` (
  `id` INT AUTO_INCREMENT,
  `usuarioid` INT,
  `itemid` INT,
  `venda` VARCHAR(10) DEFAULT 'nao',
  `valor` DOUBLE DEFAULT 0.00,
  `upgrade` INT DEFAULT 0,
  `status` VARCHAR(10) DEFAULT 'off',
  `moeda_tipo` VARCHAR(10) NOT NULL DEFAULT 'yens',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `jutsus`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `jutsus`;
CREATE TABLE `jutsus` (
  `id` INT AUTO_INCREMENT,
  `usuarioid` INT,
  `jutsu` INT,
  `nivel` INT DEFAULT 1,
  `exp` INT DEFAULT 0,
  `expmax` INT DEFAULT 50,
  `status` VARCHAR(10) DEFAULT 'ativo',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `mapa_icones`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `mapa_icones`;
CREATE TABLE `mapa_icones` (
  `id` INT AUTO_INCREMENT,
  `vila` INT NOT NULL,
  `nome` VARCHAR(50) NOT NULL,
  `x` INT NOT NULL,
  `y` INT NOT NULL,
  `url` VARCHAR(100) NOT NULL,
  `descricao` VARCHAR(100) NOT NULL,
  `ativo` TINYINT DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `tipo_icone` VARCHAR(20) DEFAULT 'normal',
  `mapa_destino` VARCHAR(50) DEFAULT NULL,
  `x_destino` INT DEFAULT NULL,
  `y_destino` INT DEFAULT NULL,
  `cor_borda` VARCHAR(7) DEFAULT '#FFD700',
  `mapa_origem` VARCHAR(50) DEFAULT NULL,
  `x_saida` INT DEFAULT NULL,
  `y_saida` INT DEFAULT NULL,
  `todos_podem_clicar` INT DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `mapa_salas`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `mapa_salas`;
CREATE TABLE `mapa_salas` (
  `id` INT AUTO_INCREMENT,
  `nome` VARCHAR(100) NOT NULL,
  `descricao` TEXT,
  `vila` INT DEFAULT 1,
  `tipo` VARCHAR(50) DEFAULT 'comum',
  `x` INT DEFAULT 0,
  `y` INT DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `mapa_usuarios`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `mapa_usuarios`;
CREATE TABLE `mapa_usuarios` (
  `usuario_id` INT,
  `sala_id` INT DEFAULT 1,
  `x` INT DEFAULT 0,
  `y` INT DEFAULT 0,
  `last_update` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `mapas`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `mapas`;
CREATE TABLE `mapas` (
  `id` INT AUTO_INCREMENT,
  `nome` VARCHAR(100) NOT NULL,
  `arquivo` VARCHAR(100) NOT NULL,
  `imagem_fundo` VARCHAR(255) NOT NULL,
  `max_x` INT DEFAULT 19,
  `max_y` INT DEFAULT 20,
  `vila_id` INT DEFAULT 0,
  `tipo` VARCHAR(20) DEFAULT 'vila',
  `ativo` INT DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `maps_pages`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `maps_pages`;
CREATE TABLE `maps_pages` (
  `id` INT AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `grid_data` TEXT,
  `north_page_id` INT,
  `south_page_id` INT,
  `east_page_id` INT,
  `west_page_id` INT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `membros`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `membros`;
CREATE TABLE `membros` (
  `id` INT AUTO_INCREMENT,
  `usuario_id` INT NOT NULL,
  `organizacao_id` INT NOT NULL,
  `cargo` VARCHAR(50) DEFAULT 'membro',
  `data_entrada` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `orgid` INT NOT NULL,
  `usuarioid` INT NOT NULL,
  `posicao` INT DEFAULT 3,
  `rank` VARCHAR(255) NOT NULL,
  `doado` INT DEFAULT 0,
  `status` TEXT DEFAULT 'nao',
  `missoes` INT DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `mensagens`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `mensagens`;
CREATE TABLE `mensagens` (
  `id` INT AUTO_INCREMENT,
  `origem` INT,
  `destino` INT,
  `assunto` VARCHAR(60),
  `msg` TEXT,
  `data` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `status` VARCHAR(10) DEFAULT 'naolido',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `missoes`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `missoes`;
CREATE TABLE `missoes` (
  `id` INT AUTO_INCREMENT,
  `nome` VARCHAR(100) NOT NULL,
  `descricao` TEXT,
  `rank` VARCHAR(1) NOT NULL,
  `nivel_min` INT DEFAULT 1,
  `recompensa_exp` INT DEFAULT 0,
  `recompensa_yens` INT DEFAULT 0,
  `duracao` INT DEFAULT 60,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `noticia_lida`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `noticia_lida`;
CREATE TABLE `noticia_lida` (
  `id` INT AUTO_INCREMENT,
  `usuario_id` INT NOT NULL,
  `noticia_id` INT NOT NULL,
  `data_leitura` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE (`usuario_id`,`noticia_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `noticias`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `noticias`;
CREATE TABLE `noticias` (
  `id` INT AUTO_INCREMENT,
  `titulo` VARCHAR(150) NOT NULL,
  `conteudo` TEXT NOT NULL,
  `autor` VARCHAR(80) NOT NULL,
  `data_criacao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` DATETIME DEFAULT NULL,
  `data_expiracao` DATETIME DEFAULT NULL,
  `usar_cores` INT DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `organizacoes`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `organizacoes`;
CREATE TABLE `organizacoes` (
  `id` INT AUTO_INCREMENT,
  `nome` VARCHAR(100),
  `nivel` INT DEFAULT 1,
  `vila` INT DEFAULT 1,
  `sigla` VARCHAR(4) NOT NULL,
  `exp` INT DEFAULT 0,
  `expmax` INT DEFAULT 10,
  `liderid` INT NOT NULL,
  `deposito` INT DEFAULT 0,
  `data` DATETIME NOT NULL,
  `descricao` TEXT,
  `logo` VARCHAR(255),
  `minimo` INT DEFAULT 1,
  `servidor_id` INT DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `personagens`
-- ------------------------------------------------------------
-- Esquema per-user: uma linha por usuário, com uma coluna TINYINT por personagem
-- desbloqueável definido em _inc/personagens_catalogo.php. Os 4 iniciais
-- (naruto/sasuke/sakura/kakashi) NÃO ficam aqui — são liberados pela coluna
-- `usuarios.personagem`. Os demais são marcados como 1 quando desbloqueados
-- por nível (auto, via personagens_unlock_por_nivel) ou liberados manualmente
-- pelo ADM.
DROP TABLE IF EXISTS `personagens`;
CREATE TABLE `personagens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuarioid` INT NOT NULL,
  `iruka` TINYINT(1) NOT NULL DEFAULT 0,
  `konohamaru` TINYINT(1) NOT NULL DEFAULT 0,
  `hayate` TINYINT(1) NOT NULL DEFAULT 0,
  `hagane` TINYINT(1) NOT NULL DEFAULT 0,
  `hinata` TINYINT(1) NOT NULL DEFAULT 0,
  `ino` TINYINT(1) NOT NULL DEFAULT 0,
  `kiba` TINYINT(1) NOT NULL DEFAULT 0,
  `shino` TINYINT(1) NOT NULL DEFAULT 0,
  `chouji` TINYINT(1) NOT NULL DEFAULT 0,
  `shikamaru` TINYINT(1) NOT NULL DEFAULT 0,
  `asuma` TINYINT(1) NOT NULL DEFAULT 0,
  `kurenai` TINYINT(1) NOT NULL DEFAULT 0,
  `tenten` TINYINT(1) NOT NULL DEFAULT 0,
  `lee` TINYINT(1) NOT NULL DEFAULT 0,
  `neji` TINYINT(1) NOT NULL DEFAULT 0,
  `temari` TINYINT(1) NOT NULL DEFAULT 0,
  `kankurou` TINYINT(1) NOT NULL DEFAULT 0,
  `haku` TINYINT(1) NOT NULL DEFAULT 0,
  `gai` TINYINT(1) NOT NULL DEFAULT 0,
  `zabuza` TINYINT(1) NOT NULL DEFAULT 0,
  `gaara` TINYINT(1) NOT NULL DEFAULT 0,
  `kabuto` TINYINT(1) NOT NULL DEFAULT 0,
  `sai` TINYINT(1) NOT NULL DEFAULT 0,
  `tayuya` TINYINT(1) NOT NULL DEFAULT 0,
  `sakon` TINYINT(1) NOT NULL DEFAULT 0,
  `jiroubo` TINYINT(1) NOT NULL DEFAULT 0,
  `kidoumaru` TINYINT(1) NOT NULL DEFAULT 0,
  `kimimaro` TINYINT(1) NOT NULL DEFAULT 0,
  `itachi` TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY `uniq_usuario` (`usuarioid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Sem dados iniciais: cada usuário ganha sua linha automaticamente no registro
-- (_inc/reg.php) e ao subir de nível (_inc/verifica_nivel*.php).


-- ------------------------------------------------------------
-- Tabela: `players_positions`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `players_positions`;
CREATE TABLE `players_positions` (
  `player_id` INT,
  `current_page_id` INT NOT NULL,
  `x` INT NOT NULL DEFAULT 10,
  `y` INT NOT NULL DEFAULT 10,
  `last_move_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `provably_fair_logs`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `provably_fair_logs`;
CREATE TABLE `provably_fair_logs` (
  `id` INT AUTO_INCREMENT,
  `usuario_id` INT NOT NULL,
  `equipment_id` INT,
  `server_seed` TEXT NOT NULL,
  `client_seed` TEXT NOT NULL,
  `nonce` TEXT NOT NULL,
  `hash` TEXT NOT NULL,
  `numero_gerado` INT NOT NULL,
  `chance_percent` INT NOT NULL,
  `sucesso` INT NOT NULL,
  `criado_em` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `provably_fair_seeds`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `provably_fair_seeds`;
CREATE TABLE `provably_fair_seeds` (
  `id` INT AUTO_INCREMENT,
  `usuario_id` INT NOT NULL,
  `server_seed` TEXT NOT NULL,
  `server_seed_hash` TEXT NOT NULL,
  `usado` INT DEFAULT 0,
  `criado_em` TEXT,
  `usado_em` TEXT,
  `nonce_counter` INT DEFAULT 0,
  `hash_revealed` INT DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `ramen`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `ramen`;
CREATE TABLE `ramen` (
  `id` INT AUTO_INCREMENT,
  `usuarioid` INT,
  `ramenid` INT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `relatorios`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `relatorios`;
CREATE TABLE `relatorios` (
  `id` INT AUTO_INCREMENT,
  `data` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `usuarioid` INT,
  `inimigoid` INT,
  `vencedor` INT,
  `exp` VARCHAR(20),
  `yens` DOUBLE,
  `taijutsu` VARCHAR(50),
  `ninjutsu` VARCHAR(50),
  `genjutsu` VARCHAR(50),
  `energia` VARCHAR(50),
  `equips1` VARCHAR(100),
  `equips2` VARCHAR(100),
  `danos` VARCHAR(50),
  `nivel` VARCHAR(20),
  `doujutsu` VARCHAR(20),
  `status` VARCHAR(10) DEFAULT 'nao',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `salas`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `salas`;
CREATE TABLE `salas` (
  `id` INT AUTO_INCREMENT,
  `usuarioid` INT,
  `sala` VARCHAR(50),
  `fim` DATETIME,
  `data` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `servidores`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `servidores`;
CREATE TABLE `servidores` (
  `id` INT AUTO_INCREMENT,
  `nome` VARCHAR(50) NOT NULL,
  `capacidade` INT NOT NULL DEFAULT 100,
  `ativo` INT NOT NULL DEFAULT 1,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




-- ------------------------------------------------------------
-- Tabela: `spam`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `spam`;
CREATE TABLE `spam` (
  `id` INT AUTO_INCREMENT,
  `usuarioid` INT NOT NULL,
  `usuario` VARCHAR(50),
  `informanteid` INT,
  `informante` VARCHAR(50),
  `mensagem` TEXT,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `table_itens`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `table_itens`;
CREATE TABLE `table_itens` (
  `id` INT AUTO_INCREMENT,
  `categoria` VARCHAR(50),
  `descricao` TEXT,
  `taijutsu` INT DEFAULT 0,
  `ninjutsu` INT DEFAULT 0,
  `genjutsu` INT DEFAULT 0,
  `nome` VARCHAR(100),
  `imagem` VARCHAR(100),
  `valor` DOUBLE DEFAULT 0.00,
  `reqtai` INT DEFAULT 0,
  `reqnin` INT DEFAULT 0,
  `reqgen` INT DEFAULT 0,
  `vip` TEXT DEFAULT 'nao',
  `disponivel_shop` TEXT DEFAULT 'sim',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `table_jutsus`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `table_jutsus`;
CREATE TABLE `table_jutsus` (
  `id` INT AUTO_INCREMENT,
  `nome` VARCHAR(100),
  `nivel` INT DEFAULT 1,
  `natureza` VARCHAR(20) DEFAULT 'nenhum',
  `forca` INT DEFAULT 0,
  `valor` DOUBLE DEFAULT 0.00,
  `doujutsu` INT DEFAULT 0,
  `doujutsu_nivel` INT DEFAULT 0,
  `texto` TEXT DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Seed: jutsus padrão
INSERT INTO `table_jutsus` (`id`, `nome`, `nivel`, `natureza`, `forca`, `valor`, `doujutsu`, `doujutsu_nivel`, `texto`) VALUES
(1,  'Clone das Sombras',          1,  'nenhum', 10, 500.00,   0, 0, 'Cria uma cópia ilusória do ninja para confundir o oponente.'),
(2,  'Técnica de Transformação',   2,  'nenhum',  8, 300.00,   0, 0, 'Permite ao ninja transformar-se em qualquer pessoa ou objeto.'),
(3,  'Técnica de Substituição',    3,  'nenhum', 12, 800.00,   0, 0, 'Troca a posição do ninja com um objeto próximo no momento do golpe.'),
(4,  'Kawarimi no Jutsu',          5,  'nenhum', 15, 1200.00,  0, 0, 'Versão avançada da substituição, com menor tempo de reação.'),
(5,  'Bunshin no Jutsu',           8,  'nenhum', 18, 2000.00,  0, 0, 'Gera múltiplos clones de chakra que desorientam o inimigo.'),
(6,  'Henge no Jutsu',            10,  'nenhum', 22, 3000.00,  0, 0, 'Transformação perfeita que engana até os sensores de chakra.'),
(7,  'Katon: Bola de Fogo',       15,  'fogo',   30, 5000.00,  0, 0, 'Expele uma grande esfera de fogo pela boca.'),
(8,  'Katon: Dragão de Fogo',     20,  'fogo',   45, 8000.00,  0, 0, 'Uma corrente de fogo em forma de dragão avança sobre o alvo.'),
(9,  'Katon: Faíscas',            25,  'fogo',   60, 12000.00, 0, 0, 'Dispara múltiplos projéteis de fogo em alta velocidade.'),
(10, 'Katon: Pilares de Fogo',    30,  'fogo',   80, 18000.00, 0, 0, 'Colunas de fogo emergem do chão em torno do oponente.'),
(11, 'Suiton: Bala d''Água',      15,  'agua',   28, 5000.00,  0, 0, 'Dispara uma bala de água comprimida com enorme pressão.'),
(12, 'Suiton: Prisão d''Água',    20,  'agua',   42, 8000.00,  0, 0, 'Envolve o alvo em uma esfera de água que o imobiliza.'),
(13, 'Suiton: Dragão d''Água',    25,  'agua',   58, 12000.00, 0, 0, 'Um dragão de água se ergue e ataca o oponente com força devastadora.'),
(14, 'Suiton: Grande Tsunami',    30,  'agua',   78, 18000.00, 0, 0, 'Uma onda gigantesca avança e arrasa tudo em seu caminho.'),
(15, 'Doton: Muro de Terra',      15,  'terra',  25, 5000.00,  0, 0, 'Levanta uma parede de terra como escudo ou barreira.'),
(16, 'Doton: Espinhos de Terra',  20,  'terra',  40, 8000.00,  0, 0, 'Dispara lanças de pedra do chão em direção ao inimigo.'),
(17, 'Doton: Terremoto',          30,  'terra',  75, 18000.00, 0, 0, 'Faz o chão tremer com força, desequilibrando e danificando o oponente.'),
(18, 'Fuuton: Lâmina de Vento',   15,  'vento',  32, 5000.00,  0, 0, 'Projeta lâminas de ar cortante em direção ao alvo.'),
(19, 'Fuuton: Tempestade',        25,  'vento',  62, 12000.00, 0, 0, 'Invoca um redemoinho que suga e corta o oponente.'),
(20, 'Raiton: Chidori',           20,  'raio',   50, 9000.00,  0, 0, 'Concentra chakra de raio na mão e perfura o alvo com velocidade relâmpago.'),
(21, 'Raiton: Relâmpago',         28,  'raio',   70, 15000.00, 0, 0, 'Canaliza um raio direto do céu sobre o adversário.'),
(22, 'Genjutsu do Sharingan',      5,  'nenhum', 35, 6000.00,  1, 1, 'Usa o Sharingan para prender o alvo em uma ilusão profunda.'),
(23, 'Hakke: 64 Palmas',           5,  'nenhum', 38, 6000.00,  2, 1, 'Golpeia 64 pontos de chakra do oponente bloqueando seu fluxo.'),
(24, 'Caminhos do Rinnegan',       5,  'nenhum', 45, 8000.00,  3, 1, 'Manifesta os poderes dos seis caminhos do Rinnegan.');


-- ------------------------------------------------------------
-- Tabela: `table_missoes`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `table_missoes`;
CREATE TABLE `table_missoes` (
  `id` INT AUTO_INCREMENT,
  `orgid` INT NOT NULL,
  `maximo` INT DEFAULT 3,
  `membros` INT DEFAULT 0,
  `yens` INT DEFAULT 1000,
  `exp` INT DEFAULT 100,
  `logo` INT DEFAULT 1,
  `status` TEXT DEFAULT 'aguardo',
  `rank` TEXT DEFAULT 'D',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `table_usaveis`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `table_usaveis`;
CREATE TABLE `table_usaveis` (
  `id` INT AUTO_INCREMENT,
  `nome` VARCHAR(100) NOT NULL,
  `descricao` TEXT,
  `imagem` VARCHAR(255),
  `categoria` VARCHAR(50) DEFAULT 'cristal',
  `tipo_efeito` VARCHAR(32) NULL DEFAULT NULL,
  `valor_efeito` VARCHAR(64) NULL DEFAULT NULL,
  `imagem_fragmento` VARCHAR(255) NULL DEFAULT NULL,
  `cristal_alvo_id` INTEGER NULL DEFAULT NULL,
  `fragmentos_necessarios` INTEGER NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed inicial de cristais (refinamento + buff). Imagens em
-- `_img/Cristais/` (refinamento) e `_img/Buff/` (buff).
INSERT INTO `table_usaveis` (`id`, `nome`, `descricao`, `imagem`, `categoria`) VALUES
  (1, 'Cristal de Chakra Refinado', 'Cristal usado para aprimorar equipamentos de nível 0 a 6',  'Cristal de Chakra Refinado.png', 'cristal'),
  (2, 'Cristal de Chakra Bruto',    'Cristal usado para aprimorar equipamentos de nível 6 a 12', 'Cristal de Chakra Bruto.png',    'cristal'),
  (3, 'Chakra Forjado',             'Cristal usado para aprimorar equipamentos de nível 12 a 15','Chakra Forjado.png',             'cristal'),
  (4, 'Cristal de Taijutsu',        'Pedra de buff que aumenta Taijutsu em 5% por 3 horas.',     'Taijutsu.png',                   'cristal_buff'),
  (5, 'Cristal de Ninjutsu',        'Pedra de buff que aumenta Ninjutsu em 5% por 3 horas.',     'Ninjutsu.png',                   'cristal_buff'),
  (6, 'Cristal de Genjutsu',        'Pedra de buff que aumenta Genjutsu em 5% por 3 horas.',     'Genjutsu.png',                   'cristal_buff');



-- ------------------------------------------------------------
-- Tabela: `ticket_mensagens`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `ticket_mensagens`;
CREATE TABLE `ticket_mensagens` (
  `id` INT AUTO_INCREMENT,
  `ticket_id` INT NOT NULL,
  `autor_id` INT NOT NULL,
  `autor_tipo` VARCHAR(10) NOT NULL DEFAULT 'player',
  `mensagem` TEXT NOT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE INDEX `idx_ticket_msg_ticket` ON `ticket_mensagens` (ticket_id);

-- ------------------------------------------------------------
-- Tabela: `tickets`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `tickets`;
CREATE TABLE `tickets` (
  `id` INT AUTO_INCREMENT,
  `usuario_id` INT NOT NULL,
  `servidor_id` INT DEFAULT NULL,
  `categoria` VARCHAR(30) NOT NULL DEFAULT 'outros',
  `assunto` VARCHAR(150) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'aberto',
  `prioridade` INT NOT NULL DEFAULT 0,
  `atendente_id` INT DEFAULT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `nao_lido_player` INT NOT NULL DEFAULT 0,
  `nao_lido_staff` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE INDEX `idx_tickets_usuario` ON `tickets` (usuario_id);
CREATE INDEX `idx_tickets_status` ON `tickets` (status);

-- ------------------------------------------------------------
-- Tabela: `usaveis`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `usaveis`;
CREATE TABLE `usaveis` (
  `id` INT AUTO_INCREMENT,
  `usuarioid` INT NOT NULL,
  `itemid` INT NOT NULL,
  `status` VARCHAR(10) DEFAULT 'off',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `usuario_jutsus`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `usuario_jutsus`;
CREATE TABLE `usuario_jutsus` (
  `id` INT AUTO_INCREMENT,
  `usuario_id` INT NOT NULL,
  `jutsu_id` INT NOT NULL,
  `nivel` INT DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `usuarios`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` INT AUTO_INCREMENT,
  `usuario` VARCHAR(50),
  `senha` VARCHAR(32),
  `avatar` INT DEFAULT 0,
  `missao` INT DEFAULT 0,
  `missao_fim` DATETIME,
  `status` VARCHAR(20) DEFAULT 'ativo',
  `yens` INT DEFAULT 1000,
  `yens_fat` INT DEFAULT 0,
  `nivel` INT DEFAULT 1,
  `orgid` INT DEFAULT 0,
  `energia` INT DEFAULT 100,
  `energiamax` INT DEFAULT 100,
  `taijutsu` INT DEFAULT 10,
  `ninjutsu` INT DEFAULT 10,
  `genjutsu` INT DEFAULT 10,
  `personagem` VARCHAR(50) NOT NULL DEFAULT '',
  `renegado` VARCHAR(3) NOT NULL DEFAULT 'nao',
  `vila` INT DEFAULT 1,
  `doujutsu` INT DEFAULT 0,
  `exp` INT DEFAULT 0,
  `expmax` INT DEFAULT 100,
  `doujutsu_nivel` INT DEFAULT 0,
  `doujutsu_exp` INT DEFAULT 0,
  `doujutsu_expmax` INT DEFAULT 100,
  `chakra_regen_ultima` DATETIME DEFAULT NULL,
  `doujutsu_treino_fim` DATETIME DEFAULT NULL,
  `doujutsu_treino_dia` DATE DEFAULT NULL,
  `doujutsu_treino_count` INT DEFAULT 0,
  `vip_inicio` DATETIME,
  `vip` DATETIME DEFAULT '2000-01-01 00:00:00',
  `hunt` INT DEFAULT 0,
  `treino` INT DEFAULT 0,
  `penalidade_fim` DATETIME,
  `config_radio` INT DEFAULT 1,
  `config_personagem` VARCHAR(3) DEFAULT 'nao',
  `loginip` INT DEFAULT 0,
  `timestamp` INT DEFAULT 0,
  `email` VARCHAR(250) DEFAULT '',
  `ip` INT DEFAULT 0,
  `hunt_restantes` INT DEFAULT 14,
  `reg` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `natureza1` VARCHAR(20) DEFAULT '',
  `natureza2` VARCHAR(20) DEFAULT '',
  `natureza3` VARCHAR(20) DEFAULT '',
  `missao_tempo` INT DEFAULT 0,
  `orgmissao` INT DEFAULT 0,
  `preso` INT DEFAULT 0,
  `tipo` VARCHAR(20) DEFAULT 'normal',
  `vitorias` INT DEFAULT 0,
  `derrotas` INT DEFAULT 0,
  `empates` INT DEFAULT 0,
  `config_twitter` VARCHAR(255) DEFAULT '',
  `exptotal` INT DEFAULT 0,
  `yens_perd` DOUBLE DEFAULT 0.00,
  `batalhas` INT DEFAULT 0,
  `config_apresentacao` TEXT DEFAULT '',
  `config_oktwitter` VARCHAR(3) DEFAULT 'nao',
  `orgnome` VARCHAR(100) DEFAULT '',
  `config_atualizacoes` VARCHAR(10) DEFAULT 'sim',
  `adm` INT DEFAULT 0,
  `ban_motivo` TEXT DEFAULT '',
  `ban_fim` DATETIME DEFAULT '2000-01-01 00:00:00',
  `admin` INT DEFAULT 0,
  `missao_inicio` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `ativo` INT DEFAULT 1,
  `bandana_estilo` TEXT DEFAULT "moderno",
  `ultimo_acesso` DATETIME DEFAULT NULL,
  `ban_data` DATETIME DEFAULT NULL,
  `ban_duracao` INT DEFAULT 0,
  `bonus_invasao_tai` INT NOT NULL DEFAULT 0,
  `bonus_invasao_nin` INT NOT NULL DEFAULT 0,
  `bonus_invasao_gen` INT NOT NULL DEFAULT 0,
  `pessoal_nome` TEXT DEFAULT '',
  `pessoal_sexo` TEXT DEFAULT '',
  `pessoal_idade` TEXT DEFAULT '',
  `pessoal_pais` TEXT DEFAULT '',
  `pessoal_uf` TEXT DEFAULT '',
  `config_pergunta` INT DEFAULT 0,
  `config_resposta` TEXT DEFAULT "",
  `config_recuperacao` INT DEFAULT 0,
  `x` INT DEFAULT 15,
  `y` INT DEFAULT 15,
  `floresta_x` INT DEFAULT NULL,
  `floresta_y` INT DEFAULT NULL,
  `floresta_mapa` INT DEFAULT NULL,
  `floresta_principal_x` INT DEFAULT NULL,
  `floresta_principal_y` INT DEFAULT NULL,
  `floresta_chuva_x` INT DEFAULT NULL,
  `floresta_chuva_y` INT DEFAULT NULL,
  `vila_chuva_x` INT DEFAULT NULL,
  `vila_chuva_y` INT DEFAULT NULL,
  `mapa_atual` VARCHAR(50) DEFAULT 'mapa_principal',
  `mapa_atual_id` INT DEFAULT 1,
  `mapa_x` INT DEFAULT 17,
  `mapa_y` INT DEFAULT 18,
  `mundo_x` INT DEFAULT NULL,
  `mundo_y` INT DEFAULT NULL,
  `mundo_mapa` TEXT DEFAULT 'mundo_centro',
  `ultimo_movimento_mundial` DATETIME DEFAULT NULL,
  `selo_tipo` INT DEFAULT 0,
  `selo_nivel` INT DEFAULT 0,
  `selo_vitorias` INT DEFAULT 0,
  `hunt_fim` TEXT DEFAULT NULL,
  `hunt_cancels_today` INT DEFAULT 0,
  `hunt_cancels_date` TEXT DEFAULT NULL,
  `ban_aceite_pendente` INT DEFAULT 0,
  `ban_penalty_ate` DATETIME DEFAULT NULL,
  `servidor_id` INT DEFAULT 1,
  `doujutsu_despertar_hp` INT DEFAULT NULL,
  `doujutsu_despertar_cooldown` DATETIME DEFAULT NULL,
  `doujutsu_proxima_tentativa` DATETIME DEFAULT NULL,
  `energia_travada` INT DEFAULT 0,
  `bonus_invasao_pct` INT DEFAULT 0,
  `treino_tempo` INT DEFAULT 0,
  `treino_fim` DATETIME DEFAULT NULL,
  `ultima_missao_clan` DATETIME DEFAULT NULL,
  `pedra_tai` INT NOT NULL DEFAULT 0,
  `pedra_nin` INT NOT NULL DEFAULT 0,
  `pedra_gen` INT NOT NULL DEFAULT 0,
  `compraloja` INT NOT NULL DEFAULT 0,
  `venda` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `config_viewtwitter` VARCHAR(3) DEFAULT 'nao',
  `config_youtube` VARCHAR(255) DEFAULT '',
  `config_okyoutube` VARCHAR(3) DEFAULT 'nao',
  `criador_conteudo` INT DEFAULT 0,
  `ref_link` VARCHAR(60) DEFAULT NULL,
  `missoes_longas` INT NOT NULL DEFAULT 0,
  `despertar_notificado` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE INDEX `idx_usuarios_floresta_pos` ON `usuarios` (floresta_x, floresta_y);
CREATE INDEX `idx_usuarios_floresta_mapa` ON `usuarios` (floresta_mapa);
CREATE INDEX `idx_usuarios_floresta_principal` ON `usuarios` (floresta_principal_x, floresta_principal_y);
CREATE INDEX `idx_usuarios_floresta_chuva` ON `usuarios` (floresta_chuva_x, floresta_chuva_y);
CREATE INDEX `idx_usuarios_vila_chuva` ON `usuarios` (vila_chuva_x, vila_chuva_y);
CREATE INDEX `idx_usuarios_mapa_atual` ON `usuarios` (mapa_atual);

-- ------------------------------------------------------------
-- Tabela: `vendas`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `vendas`;
CREATE TABLE `vendas` (
  `id` INT AUTO_INCREMENT,
  `usuarioid` INT NOT NULL,
  `valor` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `moeda_tipo` VARCHAR(10) NOT NULL DEFAULT 'yens',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Tabela: `mercado_historico` (registro permanente de vendas)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `mercado_historico`;
CREATE TABLE `mercado_historico` (
  `id` INT AUTO_INCREMENT,
  `vendedor_id` INT NOT NULL,
  `comprador_id` INT NOT NULL,
  `item_id` INT NOT NULL,
  `item_nome` VARCHAR(150) NOT NULL,
  `item_imagem` VARCHAR(150) DEFAULT '',
  `valor` DOUBLE NOT NULL DEFAULT 0,
  `moeda_tipo` VARCHAR(10) NOT NULL DEFAULT 'yens',
  `data` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mh_data` (`data`),
  KEY `idx_mh_moeda` (`moeda_tipo`),
  KEY `idx_mh_vendedor` (`vendedor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `vip`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `vip`;
CREATE TABLE `vip` (
  `id` INT AUTO_INCREMENT,
  `usuarioid` INT NOT NULL,
  `autenticacao` VARCHAR(100),
  `meio` VARCHAR(50),
  `status` VARCHAR(20) DEFAULT 'analise',
  `obs` TEXT,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------
-- Tabela: `backup_config`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `backup_config` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `modo` VARCHAR(20) NOT NULL DEFAULT 'horas',
  `intervalo` INT NOT NULL DEFAULT 24,
  `dia_semana` TINYINT NOT NULL DEFAULT 0,
  `hora` TINYINT NOT NULL DEFAULT 3,
  `minuto` TINYINT NOT NULL DEFAULT 0,
  `pasta_destino` VARCHAR(255) NOT NULL DEFAULT 'backups',
  `max_backups` INT NOT NULL DEFAULT 30,
  `incluir_forum` TINYINT NOT NULL DEFAULT 1,
  `mysqldump_path` VARCHAR(500) NOT NULL DEFAULT '',
  `ativo` TINYINT NOT NULL DEFAULT 0,
  `ultimo_backup` DATETIME NULL,
  `proximo_backup` DATETIME NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: `backup_historico`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `backup_historico` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `arquivo_naruto` VARCHAR(255) NULL,
  `arquivo_forum` VARCHAR(255) NULL,
  `tamanho_bytes` BIGINT NOT NULL DEFAULT 0,
  `status` VARCHAR(20) NOT NULL DEFAULT 'sucesso',
  `erro_mensagem` TEXT NULL,
  `origem` VARCHAR(20) NOT NULL DEFAULT 'manual',
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: `categorias_equipamento`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categorias_equipamento` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(40) NOT NULL UNIQUE,
  `nome` VARCHAR(60) NOT NULL,
  `emoji` VARCHAR(16) DEFAULT '',
  `pasta` VARCHAR(100) NOT NULL,
  `placeholder` VARCHAR(80) DEFAULT '',
  `ordem` INT DEFAULT 100,
  `ativo` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: `contato`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contato` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(80) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `assunto` VARCHAR(100) NOT NULL,
  `usuario` VARCHAR(60) NOT NULL DEFAULT '-',
  `mensagem` TEXT NOT NULL,
  `ip` VARCHAR(45) DEFAULT '',
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lido` TINYINT(1) NOT NULL DEFAULT 0,
  KEY `idx_contato_lido` (`lido`),
  KEY `idx_contato_data` (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: `craft_fragmentos`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `craft_fragmentos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuarioid` INTEGER NOT NULL,
  `itemid` INTEGER NOT NULL,
  `quantidade` INTEGER NOT NULL DEFAULT 0,
  UNIQUE KEY `uniq_usuario_item` (`usuarioid`, `itemid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: `eventos_bonus`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `eventos_bonus` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(120) NOT NULL,
  `descricao` TEXT NOT NULL DEFAULT '',
  `mult_exp` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  `mult_yens` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  `mult_drop` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  `inicio` DATETIME NOT NULL,
  `fim` DATETIME NOT NULL,
  `banner_cor` VARCHAR(20) NOT NULL DEFAULT '#ff6600',
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: `login_streak`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_streak` (
  `id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `usuarioid` INTEGER NOT NULL UNIQUE,
  `streak` INTEGER NOT NULL DEFAULT 1,
  `ultimo_login` DATE NOT NULL,
  `total_logins` INTEGER NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: `login_streak_config`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_streak_config` (
  `dia` TINYINT NOT NULL PRIMARY KEY,
  `yens` INTEGER NOT NULL DEFAULT 0,
  `exp` INTEGER NOT NULL DEFAULT 0,
  `icone` VARCHAR(100) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: `login_tentativas`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_tentativas` (
  `ip` BIGINT UNSIGNED NOT NULL,
  `tentativas` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `primeiro_erro` DATETIME NOT NULL,
  `ultimo_erro` DATETIME NOT NULL,
  `bloqueado_ate` DATETIME DEFAULT NULL,
  PRIMARY KEY (`ip`),
  INDEX `idx_bloqueio` (`bloqueado_ate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: `personagens_catalogo`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `personagens_catalogo` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `chave` VARCHAR(40) NOT NULL,
  `nome` VARCHAR(80) NOT NULL,
  `nivel` INT(11) NOT NULL DEFAULT 1,
  `vip` TINYINT(1) NOT NULL DEFAULT 0,
  `descricao` VARCHAR(255) DEFAULT '',
  `ordem` INT(11) NOT NULL DEFAULT 0,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_chave` (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: `despertar_historico`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `despertar_historico` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `resultado` TINYINT NOT NULL DEFAULT 0,
  `data` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `batalhas` INT NOT NULL DEFAULT 0,
  `vitorias` INT NOT NULL DEFAULT 0,
  `missoes_longas` INT NOT NULL DEFAULT 0,
  INDEX `idx_dh_usuario` (`usuario_id`),
  INDEX `idx_dh_resultado` (`resultado`),
  INDEX `idx_dh_data` (`data`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS = 1;
