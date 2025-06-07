-- phpMyAdmin SQL Dump
-- version 3.2.0.1
-- http://www.phpmyadmin.net
--
-- Servidor: localhost
-- Tempo de Geração: Mai 24, 2011 as 10:51 PM
-- Versão do Servidor: 5.1.36
-- Versão do PHP: 5.3.0

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Banco de Dados: `narutohit`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `amigos`
--

CREATE TABLE IF NOT EXISTS `amigos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuarioid` int(11) NOT NULL,
  `amigoid` int(11) NOT NULL,
  `status` enum('sim','nao') NOT NULL DEFAULT 'nao',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `amigos`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `atualizacoes`
--

CREATE TABLE IF NOT EXISTS `atualizacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuarioid` int(11) NOT NULL,
  `texto` tinytext NOT NULL,
  `hora` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `atualizacoes`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `book`
--

CREATE TABLE IF NOT EXISTS `book` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuarioid` int(11) NOT NULL,
  `inimigoid` int(11) NOT NULL,
  `ultimo` datetime NOT NULL,
  `yens` int(11) NOT NULL,
  `hoje` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `book`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `cbt`
--

CREATE TABLE IF NOT EXISTS `cbt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` enum('nao','sim') NOT NULL DEFAULT 'nao',
  `chave` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `cbt`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `configuracoes`
--

CREATE TABLE IF NOT EXISTS `configuracoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuarioid` int(11) NOT NULL,
  `config_atk1` int(11) NOT NULL DEFAULT '0',
  `config_atk2` int(11) NOT NULL DEFAULT '0',
  `config_atk3` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `configuracoes`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `contato`
--

CREATE TABLE IF NOT EXISTS `contato` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `assunto` varchar(255) NOT NULL,
  `usuario` varchar(15) NOT NULL,
  `mensagem` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `contato`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `enquetes`
--

CREATE TABLE IF NOT EXISTS `enquetes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pergunta` varchar(255) NOT NULL,
  `respostas` varchar(255) NOT NULL,
  `resp_a` int(11) NOT NULL DEFAULT '0',
  `resp_b` int(11) NOT NULL DEFAULT '0',
  `resp_c` int(11) NOT NULL DEFAULT '0',
  `resp_d` int(11) NOT NULL DEFAULT '0',
  `resp_e` int(11) NOT NULL DEFAULT '0',
  `fim` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `enquetes`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `inventario`
--

CREATE TABLE IF NOT EXISTS `inventario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuarioid` int(11) NOT NULL,
  `itemid` int(11) NOT NULL,
  `status` enum('on','off') NOT NULL DEFAULT 'off',
  `venda` enum('sim','nao') NOT NULL DEFAULT 'nao',
  `valor` int(11) NOT NULL DEFAULT '0',
  `categoria` enum('arma','vestimenta','calcado') NOT NULL,
  `upgrade` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `usuarioid` (`usuarioid`),
  KEY `usuarioid_2` (`usuarioid`),
  KEY `itemid` (`itemid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `inventario`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `jutsus`
--

CREATE TABLE IF NOT EXISTS `jutsus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `usuarioid` int(11) NOT NULL,
  `jutsu` int(11) NOT NULL,
  `nivel` int(1) NOT NULL,
  `exp` int(11) NOT NULL,
  `expmax` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `usuarioid` (`usuarioid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `jutsus`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `membros`
--

CREATE TABLE IF NOT EXISTS `membros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orgid` int(11) NOT NULL,
  `usuarioid` int(11) NOT NULL,
  `posicao` int(11) NOT NULL DEFAULT '3',
  `rank` varchar(255) NOT NULL,
  `doado` int(11) NOT NULL DEFAULT '0',
  `status` enum('sim','nao') NOT NULL DEFAULT 'nao',
  `missoes` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `usuarioid` (`usuarioid`),
  KEY `status` (`status`),
  KEY `orgid` (`orgid`),
  KEY `orgid_2` (`orgid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `membros`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `mensagens`
--

CREATE TABLE IF NOT EXISTS `mensagens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` datetime NOT NULL,
  `origem` int(11) NOT NULL,
  `destino` int(11) NOT NULL,
  `assunto` varchar(60) NOT NULL,
  `msg` text NOT NULL,
  `status` enum('lido','naolido') NOT NULL DEFAULT 'naolido',
  PRIMARY KEY (`id`),
  KEY `origem` (`origem`,`destino`),
  KEY `origem_2` (`origem`),
  KEY `destino` (`destino`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `mensagens`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `natureza`
--

CREATE TABLE IF NOT EXISTS `natureza` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuarioid` int(11) NOT NULL,
  `natureza` enum('fogo','agua','raio','terra','vento','nenhum') NOT NULL,
  `nivel` int(1) NOT NULL,
  `exp` int(11) NOT NULL,
  `expmax` int(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `usuarioid` (`usuarioid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `natureza`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `news`
--

CREATE TABLE IF NOT EXISTS `news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assunto` varchar(255) NOT NULL,
  `texto` text NOT NULL,
  `autor` varchar(255) NOT NULL,
  `data` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `news`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `organizacoes`
--

CREATE TABLE IF NOT EXISTS `organizacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vila` int(11) NOT NULL,
  `sigla` varchar(4) NOT NULL,
  `nome` varchar(20) NOT NULL,
  `nivel` int(11) NOT NULL DEFAULT '1',
  `exp` int(11) NOT NULL DEFAULT '0',
  `expmax` int(11) NOT NULL DEFAULT '10',
  `liderid` int(11) NOT NULL,
  `deposito` int(11) NOT NULL DEFAULT '0',
  `data` datetime NOT NULL,
  `descricao` text NOT NULL,
  `logo` varchar(255) NOT NULL,
  `minimo` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `organizacoes`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `parceiros`
--

CREATE TABLE IF NOT EXISTS `parceiros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `cliques` int(11) NOT NULL DEFAULT '0',
  `envio` enum('dia','semana') NOT NULL DEFAULT 'semana',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11 ;

--
-- Extraindo dados da tabela `parceiros`
--

INSERT INTO `parceiros` (`id`, `site`, `url`, `email`, `cliques`, `envio`) VALUES
(1, 'Anime Monstrosity', 'http://anime-monstrosity.blogspot.com/', '', 855, 'semana'),
(2, 'Naruto Fox', 'http://www.narutofox.com.br/', '', 1217, 'semana'),
(3, 'Liga Naruto', 'http://liganaruto.blogspot.com/2008/04/naruto-shippuuden.html', '', 1064, 'semana'),
(4, 'Anime100', 'http://www.anime100.info/', '', 747, 'semana'),
(5, 'Blitz Mangás', 'http://www.blitzmangas.com.br/', '', 508, 'semana'),
(6, 'Somente Anime', 'http://somenteanime.blogspot.com/', '', 398, 'semana'),
(7, 'Naruto RMVB', 'http://www.narutormvb.net/', 'zeronarutormvb@hotmail.com', 1243, 'semana'),
(8, 'Naruto EX', 'http://www.naruto-ex.com/', '', 1200, 'dia'),
(9, 'gameCONNECTION', 'http://www.game-connection.blogspot.com/', '', 499, 'dia'),
(10, 'AnimesPlus', 'http://www.animesplus.com.br/', 'max@animesplus.com.br', 272, 'semana');

-- --------------------------------------------------------

--
-- Estrutura da tabela `personagens`
--

CREATE TABLE IF NOT EXISTS `personagens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuarioid` int(11) NOT NULL,
  `itachi` int(11) NOT NULL DEFAULT '0',
  `kisame` int(11) NOT NULL DEFAULT '0',
  `sasori` int(11) NOT NULL DEFAULT '0',
  `deidara` int(11) NOT NULL DEFAULT '0',
  `kakuzu` int(11) NOT NULL DEFAULT '0',
  `hidan` int(11) NOT NULL DEFAULT '0',
  `konohamaru` int(11) NOT NULL DEFAULT '0',
  `kiba` int(11) NOT NULL DEFAULT '0',
  `ino` int(11) NOT NULL DEFAULT '0',
  `tenten` int(11) NOT NULL DEFAULT '0',
  `lee` int(11) NOT NULL DEFAULT '0',
  `neji` int(11) NOT NULL DEFAULT '0',
  `hinata` int(11) NOT NULL DEFAULT '0',
  `temari` int(11) NOT NULL DEFAULT '0',
  `shino` int(11) NOT NULL DEFAULT '0',
  `kankurou` int(11) NOT NULL DEFAULT '0',
  `tayuya` int(11) NOT NULL DEFAULT '0',
  `gaara` int(11) NOT NULL DEFAULT '0',
  `shikamaru` int(11) NOT NULL DEFAULT '0',
  `chouji` int(11) NOT NULL DEFAULT '0',
  `haku` int(11) NOT NULL DEFAULT '0',
  `kabuto` int(11) NOT NULL DEFAULT '0',
  `kidoumaru` int(11) NOT NULL DEFAULT '0',
  `iruka` int(11) NOT NULL DEFAULT '0',
  `sai` int(11) NOT NULL DEFAULT '0',
  `zabuza` int(11) NOT NULL DEFAULT '0',
  `jiroubo` int(11) NOT NULL DEFAULT '0',
  `sakon` int(11) NOT NULL DEFAULT '0',
  `kimimaro` int(11) NOT NULL DEFAULT '0',
  `kurenai` int(11) NOT NULL DEFAULT '0',
  `hayate` int(11) NOT NULL DEFAULT '0',
  `hagane` int(11) NOT NULL DEFAULT '0',
  `asuma` int(11) NOT NULL DEFAULT '0',
  `gai` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `usuarioid` (`usuarioid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `personagens`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `quests`
--

CREATE TABLE IF NOT EXISTS `quests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuarioid` int(11) NOT NULL,
  `questid` int(11) NOT NULL,
  `vitorias` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `usuarioid` (`usuarioid`,`questid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `quests`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `ramen`
--

CREATE TABLE IF NOT EXISTS `ramen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuarioid` int(11) NOT NULL,
  `ramenid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `usuarioid` (`usuarioid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `ramen`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `relatorios`
--

CREATE TABLE IF NOT EXISTS `relatorios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` enum('sim','nao') NOT NULL DEFAULT 'nao',
  `data` datetime NOT NULL,
  `usuarioid` int(11) NOT NULL,
  `inimigoid` int(11) NOT NULL,
  `vencedor` int(11) NOT NULL,
  `nivel` varchar(255) NOT NULL,
  `exp` varchar(255) NOT NULL,
  `yens` int(11) NOT NULL,
  `taijutsu` varchar(255) NOT NULL,
  `ninjutsu` varchar(255) NOT NULL,
  `genjutsu` varchar(255) NOT NULL,
  `energia` varchar(255) NOT NULL,
  `chakra` varchar(255) NOT NULL,
  `equips1` varchar(255) NOT NULL,
  `equips2` varchar(255) NOT NULL,
  `doujutsu` varchar(10) NOT NULL,
  `danos` varchar(255) NOT NULL,
  `ip` enum('sim','nao') NOT NULL DEFAULT 'nao',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `relatorios`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `salas`
--

CREATE TABLE IF NOT EXISTS `salas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuarioid` int(11) NOT NULL DEFAULT '0',
  `fim` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `usuarioid` (`usuarioid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11 ;

--
-- Extraindo dados da tabela `salas`
--

INSERT INTO `salas` (`id`, `usuarioid`, `fim`) VALUES
(1, 2560, '2010-04-04 18:03:25'),
(2, 721, '2010-04-04 18:04:59'),
(3, 513, '2010-04-04 18:04:49'),
(4, 0, '0000-00-00 00:00:00'),
(5, 0, '0000-00-00 00:00:00'),
(6, 0, '0000-00-00 00:00:00'),
(7, 0, '0000-00-00 00:00:00'),
(8, 0, '0000-00-00 00:00:00'),
(9, 0, '0000-00-00 00:00:00'),
(10, 0, '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Estrutura da tabela `seguranca`
--

CREATE TABLE IF NOT EXISTS `seguranca` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` datetime NOT NULL,
  `origem` int(11) NOT NULL,
  `destino` int(11) NOT NULL,
  `assunto` varchar(60) NOT NULL,
  `msg` tinytext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `seguranca`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `spam`
--

CREATE TABLE IF NOT EXISTS `spam` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuarioid` int(11) NOT NULL,
  `usuario` varchar(255) NOT NULL,
  `informanteid` int(11) NOT NULL,
  `informante` varchar(255) NOT NULL,
  `mensagem` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `spam`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `table_itens`
--

CREATE TABLE IF NOT EXISTS `table_itens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria` enum('arma','vestimenta','calcado') NOT NULL,
  `vip` enum('sim','nao') NOT NULL DEFAULT 'nao',
  `nome` varchar(255) NOT NULL,
  `descricao` text NOT NULL,
  `reqtai` int(11) NOT NULL DEFAULT '0',
  `reqnin` int(11) NOT NULL DEFAULT '0',
  `reqgen` int(11) NOT NULL DEFAULT '0',
  `valor` int(11) NOT NULL,
  `imagem` varchar(255) NOT NULL,
  `taijutsu` int(11) NOT NULL,
  `ninjutsu` int(11) NOT NULL,
  `genjutsu` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=30 ;

--
-- Extraindo dados da tabela `table_itens`
--

INSERT INTO `table_itens` (`id`, `categoria`, `vip`, `nome`, `descricao`, `reqtai`, `reqnin`, `reqgen`, `valor`, `imagem`, `taijutsu`, `ninjutsu`, `genjutsu`) VALUES
(1, 'vestimenta', 'nao', 'Manto da Akatsuki', 'Manto que só pode ser utilizado por membros da Akatsuki.', 0, 0, 45, 4500, 'manto_akatsuki', 10, 10, 10),
(10, 'vestimenta', 'nao', 'Vestimenta do Naruto', 'Vestimenta simples do Naruto.', 0, 0, 13, 750, 'roupa_naruto', 0, 0, 4),
(2, 'vestimenta', 'nao', 'Vestimenta do Konohamaru', 'Vestimenta simples do Konohamaru.', 0, 0, 5, 250, 'roupa_konohamaru', 0, 0, 2),
(3, 'arma', 'nao', 'Kunai Simples', 'Kunai de batalha, muito utlizada em confrontos físicos.', 5, 0, 0, 400, 'kunai_simples', 1, 0, 0),
(4, 'arma', 'nao', 'Par de Kunais', 'Duas Kunais Simples. Quando utilizadas em conjunto, possui um efeito maior nos atributos de combate.', 12, 0, 0, 780, 'par_kunais', 2, 1, 1),
(5, 'arma', 'nao', 'Shurikens', 'Várias shurikens para ataques em massa.', 17, 0, 0, 1000, 'shurikens', 3, 2, 1),
(6, 'arma', 'sim', 'Fuuma Shuriken', 'Shuriken de tamanho maximizado. Se obtiver sucesso no acerto, chega a matar a vítima.', 23, 0, 0, 1700, 'fuuma_shuriken', 7, 3, 3),
(7, 'arma', 'nao', 'Kunai Tridente', 'Kunai com três pontas. Este tipo de kunai foi muito utilizado pelo Yondaime.', 28, 0, 0, 2500, 'kunai_yondaime', 8, 5, 3),
(8, 'arma', 'nao', 'Par de Kunais Tridente', 'Duas Kunais Tridente. Quando utilizadas em conjunto, provê um bônus enorme em Ninjutsu, mais alguns adicionais nos outros atributos.', 34, 0, 0, 3500, 'par_kunais_yondaime', 13, 8, 4),
(9, 'arma', 'sim', 'Senbons', 'Agulhas super afiadas, capazes de interromper o fluxo de chakra da vítima.', 39, 0, 0, 4000, 'senbons', 14, 7, 7),
(11, 'vestimenta', 'nao', 'Vestimenta do Sasuke', 'Vestimenta simples do Sasuke.', 0, 0, 13, 750, 'roupa_sasuke', 0, 0, 4),
(12, 'vestimenta', 'nao', 'Vestimenta da Sakura', 'Vestimenta simples da Sakura.', 0, 0, 13, 750, 'roupa_sakura', 0, 0, 4),
(13, 'vestimenta', 'nao', 'Vestimenta do Naruto (Shippuuden)', 'Vestimenta avançada do Naruto.', 0, 0, 25, 2200, 'roupa_naruto_shippuuden', 2, 2, 7),
(14, 'vestimenta', 'nao', 'Vestimenta do Sasuke (Shippuuden)', 'Vestimenta avançada do Sasuke.', 0, 0, 25, 2200, 'roupa_sasuke_shippuuden', 2, 2, 7),
(15, 'vestimenta', 'nao', 'Vestimenta da Sakura (Shippuuden)', 'Vestimenta avançada da Sakura.', 0, 0, 25, 2200, 'roupa_sakura_shippuuden', 2, 2, 7),
(16, 'vestimenta', 'nao', 'Vestimenta de Jounnin', 'Vestimenta para jounnins.', 0, 0, 45, 4500, 'roupa_jounin', 10, 10, 10),
(17, 'arma', 'nao', 'Bastão de Combate', 'Bastão para ataques diretos, e de alta velocidade.', 48, 0, 0, 6000, 'bastao_combate', 15, 9, 8),
(18, 'arma', 'sim', 'Lâminas de Chakra', 'Arma extremamente cortante, ainda mais se usada com seu chakra, como catalizador.', 55, 0, 0, 8000, 'laminas_chakra', 17, 12, 9),
(19, 'arma', 'nao', 'Ninjaken', 'Katana utilizada por membros da ANBU, para combates de curtíssima distância.', 70, 0, 0, 9400, 'ninjaken', 19, 13, 10),
(20, 'arma', 'nao', 'Zanbatou', 'Espada grande e larga, características de armas utilizadas por ninjas da névoa.', 87, 0, 0, 10700, 'zanbatou', 20, 14, 13),
(21, 'arma', 'sim', 'Katana', 'Espada utilizada por Sasuke, de tronco simples, e lâmina fortalecida com chakra.', 105, 0, 0, 12500, 'katana_sasuke', 20, 16, 14),
(22, 'arma', 'sim', 'Samehada', 'Famosa espada utilizada por Kisame, que pode roubar o chakra do inimigo.', 125, 0, 0, 15000, 'samehada', 21, 16, 17),
(23, 'vestimenta', 'nao', 'Vestimenta da ANBU', 'Vestimenta simples da ANBU.', 0, 0, 65, 8000, 'roupa_anbu', 15, 15, 15),
(24, 'vestimenta', 'nao', 'Roupa de Kage #1', 'Roupa de Kage, modelo 1.', 0, 0, 90, 11000, 'roupa_kage_especial', 22, 22, 22),
(25, 'vestimenta', 'nao', 'Roupa de Kage #2', 'Roupa de Kage, modelo 2.', 0, 0, 125, 16000, 'roupa_kage_especial2', 28, 28, 28),
(26, 'calcado', 'nao', 'Sandália Ninja Simples', 'Sandália ninja muito simples, utilizada por iniciantes.', 20, 20, 20, 1500, 'sandalia_madeira', 2, 0, 2),
(27, 'calcado', 'sim', 'Sandália Ninja de Madeira', 'Sandália refinada, feita com uma espécie de madeira de ótima qualidade.', 40, 40, 40, 4000, 'sandalia_refinada', 4, 1, 4),
(28, 'calcado', 'nao', 'Calçado Ninja de Combate', 'Calçado utilizado pela maioria dos ninjas, pois facilita os movimentos.', 70, 70, 70, 8000, 'sandalia_simples', 6, 2, 5),
(29, 'calcado', 'sim', 'Calçado Ninja de Proteção', 'Calçado muito raro e eficiente. Facilita qualquer tipo de movimento, e possui uma proteção excelente.', 110, 110, 110, 12000, 'sandalia_protecao', 6, 3, 8);

-- --------------------------------------------------------

--
-- Estrutura da tabela `table_jutsus`
--

CREATE TABLE IF NOT EXISTS `table_jutsus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `natureza` enum('fogo','agua','raio','terra','vento','nenhum') NOT NULL,
  `forca` int(11) NOT NULL,
  `nivel` int(11) NOT NULL,
  `doujutsu` int(11) NOT NULL DEFAULT '0',
  `doujutsu_nivel` int(11) NOT NULL DEFAULT '0',
  `valor` int(11) NOT NULL,
  `texto` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=23 ;

--
-- Extraindo dados da tabela `table_jutsus`
--

INSERT INTO `table_jutsus` (`id`, `nome`, `natureza`, `forca`, `nivel`, `doujutsu`, `doujutsu_nivel`, `valor`, `texto`) VALUES
(1, 'Rasengan', 'nenhum', 4, 4, 0, 0, 300, '$player1 concentrou seu chakra na palma da mão, criando o jutsu $jutsu, e em seguida atacou $player2, $dano'),
(2, 'Fuuton: Rasenshuriken', 'vento', 8, 12, 0, 0, 600, '$player1 concentrou seu chakra do vento na mão, criando o jutsu $jutsu, e atirou a esfera em $player2, $dano'),
(3, 'Katon: Goukakyuu no Jutsu', 'fogo', 8, 12, 0, 0, 600, '$player1 utilizou a natureza de seu chakra para criar o jutsu $jutsu. A enorme bola de fogo foi ao encontro de $player2, $dano'),
(4, 'Suiton: Mizu Bunshin no Jutsu', 'agua', 8, 12, 0, 0, 600, '$player1 criou alguns clones de água poderosos com o jutsu $jutsu, que começaram a atacar $player2, $dano'),
(5, 'Raiton: Chidori', 'raio', 8, 12, 0, 0, 600, '$player1 concentrou seu chakra do raio em sua mão, criando o jutsu $jutsu. Logo em seguida, realizou um ataque contra $player2, $dano'),
(6, 'Doton: Doryuuheki', 'terra', 8, 12, 0, 0, 600, '$player1 realizou alguns selos de mão, criando o jutsu $jutsu na direção de $player2, $dano'),
(7, 'Kage Bunshin no Jutsu', 'nenhum', 2, 2, 0, 0, 150, '$player1 utilizou o jutsu $jutsu, criando vários clones de si mesmo. Os clones rapidamente foram ao encontro de $player2, $dano'),
(8, 'Tajuu Kage Bunshin no Jutsu', 'nenhum', 6, 5, 0, 0, 450, '$player1 multiplicou-se rapidamente com o jutsu $jutsu, criando centenas de clones, que atacaram $player2, $dano'),
(9, 'Raiton: Gian', 'raio', 11, 16, 0, 0, 2600, '$player1 usou o jutsu $jutsu, disparando um enorme raio com um imenso poder de destruição na direção de $player2, $dano'),
(10, 'Katon: Housenka no Jutsu', 'fogo', 11, 16, 0, 0, 2600, '$player1 começa a atirar várias bolas de fogo em $player2, com o jutsu $jutsu, $dano'),
(11, 'Suiton: Suiryuudan no Jutsu', 'agua', 11, 16, 0, 0, 2600, '$player1 realizou alguns selos, e criou um dragão de água com o jutsu $jutsu, que atacou $player2, $dano'),
(12, 'Fuuton: Juuha Shou', 'vento', 11, 16, 0, 0, 2600, '$player1 utilizou o jutsu $jutsu para criar uma lâmina de vento contra $player2, $dano'),
(13, 'Doton: Doryuudan', 'terra', 11, 16, 0, 0, 2600, '$player1 criou um dragão de lama com o jutsu $jutsu, e em seguida atacou $player2 com bolas de lama atiradas pelo dragão, $dano'),
(14, 'Tsukuyomi', 'nenhum', 16, 20, 1, 8, 3500, '<i>"Sua alma vagará eternamente pela escuridão do mundo espiritual."</i><br />$player1 utilizou $jutsu em $player2, $dano'),
(15, 'Amaterasu', 'nenhum', 25, 30, 1, 16, 5000, '<i>"Chamas negras que jamais se apagam, consuma meu inimigo até que ele se torne pó."</i><br />$player1 utilizou $jutsu em $player2, $dano'),
(16, 'Susanoo', 'nenhum', 35, 40, 1, 24, 7000, '<i>"Deus imortal, desintegre a existência daquele que me desafia."</i><br />$player1 utilizou $jutsu em $player2, $dano'),
(17, 'Juukenhou - Hakke Rokujuuyon Shou', 'nenhum', 16, 20, 2, 8, 3500, '<i>"Técnica dos 64 golpes!"</i><br />$player1 utilizou $jutsu em $player2, $dano'),
(18, 'Juukenhou - Hakke Hyakunijuuhachi Shou', 'nenhum', 25, 30, 2, 16, 5000, '<i>"Técnica dos 128 golpes!"</i><br />$player1 utilizou $jutsu em $player2, $dano'),
(19, 'Juukenhou - Hakke Sanbyakurokujuuichi Shisa', 'nenhum', 35, 40, 2, 24, 7000, '<i>"Técnica dos 361 golpes!"</i><br />$player1 utilizou $jutsu em $player2, $dano'),
(20, 'Naraku Kohushi Baski Tensei', 'nenhum', 16, 20, 3, 8, 3500, '<i>"Kohushi Baski, apareça e aterrorize!"</i><br />$player1 utilizou $jutsu em $player2, $dano'),
(21, 'Six Paths of Pein', 'nenhum', 25, 30, 3, 16, 5000, '<i>"Conseguirá escapar aos olhos de 6 corpos?"</i><br />$player1 utilizou $jutsu em $player2, $dano'),
(22, 'Chibaku Tensei', 'nenhum', 35, 40, 3, 24, 7000, '<i>"Técnica suprema: Chibaku Tensei!"</i><br />$player1 utilizou $jutsu em $player2, $dano');

-- --------------------------------------------------------

--
-- Estrutura da tabela `table_missoes`
--

CREATE TABLE IF NOT EXISTS `table_missoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` enum('aguardo','andamento') NOT NULL,
  `orgid` int(11) NOT NULL,
  `membros` int(11) NOT NULL DEFAULT '0',
  `maximo` int(11) NOT NULL,
  `yens` int(11) NOT NULL,
  `exp` int(11) NOT NULL,
  `logo` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `orgid` (`orgid`),
  KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `table_missoes`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `table_personagens`
--

CREATE TABLE IF NOT EXISTS `table_personagens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `personagem` varchar(255) NOT NULL,
  `nivel` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=30 ;

--
-- Extraindo dados da tabela `table_personagens`
--

INSERT INTO `table_personagens` (`id`, `personagem`, `nivel`) VALUES
(1, 'temari', 9),
(2, 'hinata', 8),
(3, 'lee', 6),
(4, 'neji', 7),
(5, 'tenten', 5),
(6, 'kiba', 3),
(7, 'shino', 9),
(8, 'kankurou', 10),
(9, 'tayuya', 10),
(10, 'gaara', 11),
(11, 'ino', 4),
(12, 'shikamaru', 11),
(13, 'chouji', 12),
(14, 'haku', 12),
(15, 'kabuto', 13),
(16, 'konohamaru', 2),
(18, 'kidoumaru', 14),
(19, 'iruka', 15),
(20, 'sai', 16),
(21, 'zabuza', 17),
(22, 'jiroubo', 18),
(23, 'sakon', 19),
(24, 'kimimaro', 20),
(25, 'kurenai', 21),
(26, 'hayate', 22),
(27, 'hagane', 23),
(28, 'asuma', 24),
(29, 'gai', 25);

-- --------------------------------------------------------

--
-- Estrutura da tabela `table_quests`
--

CREATE TABLE IF NOT EXISTS `table_quests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `descricao` text NOT NULL,
  `nivel` int(11) NOT NULL,
  `vitorias` int(11) NOT NULL,
  `yens` int(11) NOT NULL,
  `exp` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

--
-- Extraindo dados da tabela `table_quests`
--

INSERT INTO `table_quests` (`id`, `nome`, `descricao`, `nivel`, `vitorias`, `yens`, `exp`) VALUES
(1, 'Quest Gennin', 'Lute e vença de 20 ninjas.', 1, 20, 1500, 150),
(2, 'Quest Chuunin', 'Lute e vença de 50 ninjas.', 5, 50, 4500, 450),
(3, 'Quest Jounnin', 'Lute e vença de 100 ninjas.', 20, 100, 10000, 1000),
(4, 'Quest Sannin', 'Lute e vença de 200 ninjas.', 40, 200, 22000, 2200),
(5, 'Quest ANBU', 'Lute e vença de 400 ninjas.', 60, 400, 50000, 5000);

-- --------------------------------------------------------

--
-- Estrutura da tabela `table_usaveis`
--

CREATE TABLE IF NOT EXISTS `table_usaveis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `descricao` text NOT NULL,
  `imagem` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Extraindo dados da tabela `table_usaveis`
--

INSERT INTO `table_usaveis` (`id`, `nome`, `descricao`, `imagem`) VALUES
(1, 'Pergaminho da Terra', 'Aumenta em 2% a chance de se obter sucesso em um aprimoramento.', 'pergaminho_terra'),
(2, 'Pergaminho do Céu', 'Aumenta em 5% a chance de se obter sucesso em um aprimoramento.', 'pergaminho_ceu'),
(3, 'Pergaminho Sagrado', 'Aumenta em 10% a chance de se obter sucesso em um aprimoramento.', 'pergaminho_sagrado');

-- --------------------------------------------------------

--
-- Estrutura da tabela `usaveis`
--

CREATE TABLE IF NOT EXISTS `usaveis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuarioid` int(11) NOT NULL,
  `itemid` int(11) NOT NULL,
  `status` enum('off','on') NOT NULL DEFAULT 'off',
  PRIMARY KEY (`id`),
  KEY `usuarioid` (`usuarioid`,`itemid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `usaveis`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios`
--

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` enum('ativo','inativo','banido') NOT NULL DEFAULT 'inativo',
  `usuario` varchar(15) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `personagem` varchar(255) NOT NULL,
  `avatar` int(11) NOT NULL DEFAULT '0',
  `vila` int(11) NOT NULL,
  `reg` datetime NOT NULL,
  `renegado` enum('sim','nao') NOT NULL DEFAULT 'nao',
  `preso` enum('sim','nao') NOT NULL DEFAULT 'nao',
  `vip_inicio` datetime NOT NULL,
  `vip` datetime NOT NULL,
  `alunoid` varchar(15) NOT NULL,
  `senseiid` varchar(15) NOT NULL,
  `orgid` int(11) NOT NULL DEFAULT '0',
  `orgmissao` int(11) NOT NULL DEFAULT '0',
  `nivel` int(11) NOT NULL DEFAULT '1',
  `yens` int(11) NOT NULL DEFAULT '300',
  `yens_fat` int(11) NOT NULL DEFAULT '300',
  `yens_perd` int(11) NOT NULL DEFAULT '0',
  `exp` int(11) NOT NULL DEFAULT '0',
  `expmax` int(11) NOT NULL DEFAULT '5',
  `exptotal` int(11) NOT NULL DEFAULT '0',
  `energia` int(11) NOT NULL DEFAULT '100',
  `energiamax` int(11) NOT NULL DEFAULT '100',
  `taijutsu` int(11) NOT NULL DEFAULT '1',
  `ninjutsu` int(11) NOT NULL DEFAULT '1',
  `genjutsu` int(11) NOT NULL DEFAULT '1',
  `batalhas` int(11) NOT NULL DEFAULT '0',
  `vitorias` int(11) NOT NULL DEFAULT '0',
  `derrotas` int(11) NOT NULL DEFAULT '0',
  `empates` int(11) NOT NULL DEFAULT '0',
  `hunt_restantes` int(11) NOT NULL DEFAULT '8',
  `hunt` int(11) NOT NULL DEFAULT '0',
  `hunt_fim` datetime NOT NULL,
  `missao` int(11) NOT NULL DEFAULT '0',
  `missao_tempo` int(11) NOT NULL,
  `missao_fim` datetime NOT NULL,
  `quest` int(11) NOT NULL DEFAULT '0',
  `quest_vitorias` int(11) NOT NULL DEFAULT '0',
  `treino` int(11) NOT NULL DEFAULT '0',
  `treino_tempo` int(11) NOT NULL,
  `treino_fim` datetime NOT NULL,
  `penalidade_fim` datetime NOT NULL,
  `doujutsu` int(11) NOT NULL DEFAULT '0',
  `doujutsu_nivel` int(11) NOT NULL DEFAULT '0',
  `doujutsu_exp` int(11) NOT NULL DEFAULT '0',
  `doujutsu_expmax` int(11) NOT NULL DEFAULT '150',
  `natureza1` enum('','fogo','agua','vento','raio','terra') NOT NULL,
  `natureza2` enum('','fogo','agua','vento','raio','terra') NOT NULL,
  `natureza3` enum('','fogo','agua','vento','raio','terra') NOT NULL,
  `config_skin` varchar(255) NOT NULL DEFAULT 'naruto',
  `config_apresentacao` text NOT NULL,
  `config_atualizacoes` enum('sim','nao') NOT NULL DEFAULT 'sim',
  `config_twitter` varchar(255) NOT NULL,
  `config_viewtwitter` enum('sim','nao') NOT NULL DEFAULT 'sim',
  `config_oktwitter` enum('sim','nao') NOT NULL DEFAULT 'sim',
  `config_radio` varchar(15) NOT NULL,
  `config_personagem` enum('sim','nao') NOT NULL DEFAULT 'nao',
  `config_avatar` enum('sim','nao') NOT NULL DEFAULT 'nao',
  `config_vila` int(11) NOT NULL DEFAULT '1',
  `config_pergunta` int(11) NOT NULL DEFAULT '0',
  `config_resposta` varchar(255) NOT NULL,
  `config_recuperacao` int(11) NOT NULL DEFAULT '0',
  `pessoal_nome` varchar(100) NOT NULL,
  `pessoal_sexo` enum('','m','f') NOT NULL,
  `pessoal_idade` int(11) NOT NULL,
  `pessoal_pais` varchar(100) NOT NULL,
  `pessoal_uf` varchar(2) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `loginip` varchar(255) NOT NULL,
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `tipo` enum('player','bot') NOT NULL DEFAULT 'player',
  PRIMARY KEY (`id`),
  KEY `vila` (`vila`),
  KEY `usuario` (`usuario`),
  KEY `vip` (`vip`),
  KEY `renegado` (`renegado`),
  KEY `ip` (`ip`),
  KEY `orgid` (`orgid`),
  KEY `orgmissao` (`orgmissao`,`nivel`,`yens_fat`,`yens_perd`,`vitorias`,`derrotas`),
  KEY `orgid_2` (`orgid`,`nivel`,`yens_fat`,`yens_perd`,`vitorias`,`derrotas`,`empates`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `usuarios`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `vendas`
--

CREATE TABLE IF NOT EXISTS `vendas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuarioid` int(11) NOT NULL,
  `valor` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `vendas`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `verificador`
--

CREATE TABLE IF NOT EXISTS `verificador` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` enum('off','on') NOT NULL DEFAULT 'off',
  `usuarioid` int(11) NOT NULL,
  `hora_missao` datetime NOT NULL,
  `hora_ataque` datetime NOT NULL,
  `inimigoid` int(11) NOT NULL,
  `yens` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `verificador`
--


-- --------------------------------------------------------

--
-- Estrutura da tabela `vip`
--

CREATE TABLE IF NOT EXISTS `vip` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` datetime NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `autenticacao` varchar(255) NOT NULL,
  `usuarioid` int(11) NOT NULL,
  `valor` float NOT NULL,
  `meio` enum('ps','pp') NOT NULL,
  `status` enum('analise','entregue','cancelado') NOT NULL DEFAULT 'analise',
  `obs` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Extraindo dados da tabela `vip`
--

