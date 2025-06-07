
<?php
// Script básico para configurar o banco de dados
require_once('_inc/conexao.php');

echo "Configurando banco de dados...<br>";

// Criar tabela básica de usuários se não existir
$sql_usuarios = "CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) NOT NULL,
  `senha` varchar(32) NOT NULL,
  `email` varchar(100) NOT NULL,
  `status` varchar(20) DEFAULT 'ativo',
  `avatar` int(11) DEFAULT 0,
  `nivel` int(11) DEFAULT 1,
  `energia` int(11) DEFAULT 100,
  `energiamax` int(11) DEFAULT 100,
  `yens` int(11) DEFAULT 0,
  `yens_fat` int(11) DEFAULT 0,
  `exp` int(11) DEFAULT 0,
  `expmax` int(11) DEFAULT 100,
  `taijutsu` int(11) DEFAULT 0,
  `ninjutsu` int(11) DEFAULT 0,
  `genjutsu` int(11) DEFAULT 0,
  `personagem` int(11) DEFAULT 1,
  `vila` int(11) DEFAULT 1,
  `renegado` int(11) DEFAULT 0,
  `orgid` int(11) DEFAULT 0,
  `doujutsu` int(11) DEFAULT 0,
  `doujutsu_nivel` int(11) DEFAULT 0,
  `doujutsu_exp` int(11) DEFAULT 0,
  `doujutsu_expmax` int(11) DEFAULT 0,
  `vip` datetime DEFAULT '2000-01-01 00:00:00',
  `vip_inicio` datetime DEFAULT '2000-01-01 00:00:00',
  `missao` int(11) DEFAULT 0,
  `missao_fim` datetime DEFAULT '2000-01-01 00:00:00',
  `treino` int(11) DEFAULT 0,
  `hunt` int(11) DEFAULT 0,
  `loginip` varchar(20) DEFAULT '',
  `penalidade_fim` datetime DEFAULT '2000-01-01 00:00:00',
  `config_radio` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";

if ($conexao->query($sql_usuarios)) {
    echo "Tabela usuarios criada com sucesso!<br>";
} else {
    echo "Erro ao criar tabela usuarios: " . $conexao->error . "<br>";
}

// Criar tabela de organizações
$sql_org = "CREATE TABLE IF NOT EXISTS `organizacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `nivel` int(11) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";

if ($conexao->query($sql_org)) {
    echo "Tabela organizacoes criada com sucesso!<br>";
} else {
    echo "Erro ao criar tabela organizacoes: " . $conexao->error . "<br>";
}

// Criar tabela de bloqueio
$sql_block = "CREATE TABLE IF NOT EXISTS `block` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(20) NOT NULL,
  `tentativas` int(11) DEFAULT 1,
  `timestamp` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";

if ($conexao->query($sql_block)) {
    echo "Tabela block criada com sucesso!<br>";
} else {
    echo "Erro ao criar tabela block: " . $conexao->error . "<br>";
}

echo "Configuração básica concluída!<br>";
echo "<a href='index.php'>Voltar ao site</a>";
?>
