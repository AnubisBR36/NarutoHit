-- ============================================================
-- Dump MySQL gerado a partir de forum.sqlite
-- Data de geração: 2026-04-25 16:04:02
-- Compatível com MySQL 5.7+ / MariaDB 10.x (utf8mb4)
-- Importar com: mysql -u USER -p NOME_DB < forum.sql
-- Ou via phpMyAdmin > Importar.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;
SET sql_mode = '';

-- ------------------------------------------------------------
-- Tabela: `categorias`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `categorias`;
CREATE TABLE `categorias` (
  `id` INT AUTO_INCREMENT,
  `nome` TEXT NOT NULL,
  `descricao` TEXT NOT NULL DEFAULT '',
  `imagem` TEXT NOT NULL DEFAULT 'default.png',
  `ordem` INT NOT NULL DEFAULT 0,
  `vila_id` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categorias` (`id`,`nome`,`descricao`,`imagem`,`ordem`,`vila_id`) VALUES
  (1,'Vila Neutra','Área geral para todos os ninjas. Discussões livres e anúncios.','ferro.png',0,0),
  (2,'Fórum da Vila da Folha','Fórum exclusivo para os ninjas da Vila da Folha.','konoha.png',1,1),
  (3,'Fórum da Vila da Areia','Fórum exclusivo para os ninjas da Vila da Areia.','areia.png',2,2),
  (4,'Fórum da Vila do Som','Fórum exclusivo para os ninjas da Vila do Som.','som.png',3,3),
  (5,'Fórum da Vila da Chuva','Fórum exclusivo para os ninjas da Vila da Chuva.','chuva.png',4,4),
  (6,'Fórum da Vila da Nuvem','Fórum exclusivo para os ninjas da Vila da Nuvem.','nuvem.png',5,5),
  (7,'Fórum da Vila da Névoa','Fórum exclusivo para os ninjas da Vila da Névoa.','nevoa.png',6,6),
  (8,'Fórum da Vila da Pedra','Fórum exclusivo para os ninjas da Vila da Pedra.','rocha.png',7,8),
  (9,'Fórum da Akatsuki','Fórum secreto para os membros da Akatsuki. Apenas renegados.','akatsuki.png',8,999);
-- 9 registros importados em `categorias`.


-- ------------------------------------------------------------
-- Tabela: `curtidas`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `curtidas`;
CREATE TABLE `curtidas` (
  `id` INT AUTO_INCREMENT,
  `postagem_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE (`postagem_id`,`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 0 registros importados em `curtidas`.


-- ------------------------------------------------------------
-- Tabela: `notificacoes`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `notificacoes`;
CREATE TABLE `notificacoes` (
  `id` INT AUTO_INCREMENT,
  `usuario_id` INT NOT NULL,
  `tipo` TEXT NOT NULL,
  `referencia_id` INT,
  `mensagem` TEXT NOT NULL,
  `lida` INT NOT NULL DEFAULT 0,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 0 registros importados em `notificacoes`.


-- ------------------------------------------------------------
-- Tabela: `postagens`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `postagens`;
CREATE TABLE `postagens` (
  `id` INT AUTO_INCREMENT,
  `topico_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `conteudo` TEXT NOT NULL,
  `editado` INT NOT NULL DEFAULT 0,
  `editado_em` DATETIME,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `postagens` (`id`,`topico_id`,`usuario_id`,`conteudo`,`editado`,`editado_em`,`criado_em`) VALUES
  (1,1,9,'Olá Isso é um teste',0,NULL,'2026-04-08 00:19:11');
-- 1 registros importados em `postagens`.


-- ------------------------------------------------------------
-- Tabela: `reacoes`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `reacoes`;
CREATE TABLE `reacoes` (
  `id` INT AUTO_INCREMENT,
  `postagem_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `tipo` TEXT NOT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `reacoes` (`id`,`postagem_id`,`usuario_id`,`tipo`,`criado_em`) VALUES
  (1,1,9,'coracao','2026-04-08 00:19:18');
-- 1 registros importados em `reacoes`.


-- ------------------------------------------------------------
-- Tabela: `seguir_topicos`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `seguir_topicos`;
CREATE TABLE `seguir_topicos` (
  `id` INT AUTO_INCREMENT,
  `topico_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE (`topico_id`,`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `seguir_topicos` (`id`,`topico_id`,`usuario_id`,`criado_em`) VALUES
  (1,1,9,'2026-04-08 00:19:11');
-- 1 registros importados em `seguir_topicos`.


-- ------------------------------------------------------------
-- Tabela: `topicos`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `topicos`;
CREATE TABLE `topicos` (
  `id` INT AUTO_INCREMENT,
  `titulo` TEXT NOT NULL,
  `categoria_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `fixado` INT NOT NULL DEFAULT 0,
  `fechado` INT NOT NULL DEFAULT 0,
  `visualizacoes` INT NOT NULL DEFAULT 0,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `topicos` (`id`,`titulo`,`categoria_id`,`usuario_id`,`fixado`,`fechado`,`visualizacoes`,`criado_em`,`atualizado_em`) VALUES
  (1,'Teste',1,9,0,0,3,'2026-04-08 00:19:11','2026-04-08 00:19:11');
-- 1 registros importados em `topicos`.


-- ------------------------------------------------------------
-- Tabela: `topicos_lidos`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `topicos_lidos`;
CREATE TABLE `topicos_lidos` (
  `id` INT AUTO_INCREMENT,
  `topico_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `lido_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE (`topico_id`,`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `topicos_lidos` (`id`,`topico_id`,`usuario_id`,`lido_em`) VALUES
  (1,1,9,'2026-04-08 00:19:13');
-- 1 registros importados em `topicos_lidos`.


SET FOREIGN_KEY_CHECKS = 1;
