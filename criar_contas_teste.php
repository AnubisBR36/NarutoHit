
<?php
require_once('_inc/conexao.php');

// Criar algumas contas de teste
$contas_teste = [
    ['usuario' => 'teste1', 'senha' => md5('123456'), 'vila' => 1, 'avatar' => 1, 'energia' => 100, 'nivel' => 1, 'vitorias' => 5, 'derrotas' => 2, 'empates' => 1, 'yens_fat' => 150.50, 'yens_perd' => 20.00, 'batalhas' => 8, 'exptotal' => 450, 'personagem' => 1, 'doujutsu' => 0, 'orgid' => 0, 'orgnome' => '', 'config_twitter' => '', 'config_oktwitter' => 'nao', 'config_apresentacao' => 'Ninja da Vila da Folha!'],
    ['usuario' => 'teste2', 'senha' => md5('123456'), 'vila' => 2, 'avatar' => 2, 'energia' => 100, 'nivel' => 2, 'vitorias' => 8, 'derrotas' => 1, 'empates' => 0, 'yens_fat' => 320.75, 'yens_perd' => 15.25, 'batalhas' => 9, 'exptotal' => 780, 'personagem' => 2, 'doujutsu' => 0, 'orgid' => 0, 'orgnome' => '', 'config_twitter' => '', 'config_oktwitter' => 'nao', 'config_apresentacao' => 'Ninja da Vila da Areia!'],
    ['usuario' => 'teste3', 'senha' => md5('123456'), 'vila' => 3, 'avatar' => 3, 'energia' => 100, 'nivel' => 3, 'vitorias' => 12, 'derrotas' => 3, 'empates' => 2, 'yens_fat' => 500.00, 'yens_perd' => 45.75, 'batalhas' => 17, 'exptotal' => 1200, 'personagem' => 3, 'doujutsu' => 0, 'orgid' => 0, 'orgnome' => '', 'config_twitter' => '', 'config_oktwitter' => 'nao', 'config_apresentacao' => 'Ninja da Vila do Som!'],
    ['usuario' => 'teste4', 'senha' => md5('123456'), 'vila' => 1, 'avatar' => 4, 'energia' => 100, 'nivel' => 1, 'renegado' => 'sim', 'vitorias' => 3, 'derrotas' => 5, 'empates' => 0, 'yens_fat' => 80.25, 'yens_perd' => 120.50, 'batalhas' => 8, 'exptotal' => 200, 'personagem' => 4, 'doujutsu' => 0, 'orgid' => 0, 'orgnome' => '', 'config_twitter' => '', 'config_oktwitter' => 'nao', 'config_apresentacao' => 'Ninja renegado!'],
    ['usuario' => 'teste5', 'senha' => md5('123456'), 'vila' => 2, 'avatar' => 5, 'energia' => 100, 'nivel' => 2, 'vitorias' => 6, 'derrotas' => 2, 'empates' => 1, 'yens_fat' => 275.00, 'yens_perd' => 35.00, 'batalhas' => 9, 'exptotal' => 650, 'personagem' => 5, 'doujutsu' => 0, 'orgid' => 0, 'orgnome' => '', 'config_twitter' => '', 'config_oktwitter' => 'nao', 'config_apresentacao' => 'Ninja dedicado da Areia!']
];

foreach($contas_teste as $conta) {
    // Verificar se a conta já existe
    $check = mysql_query("SELECT id FROM usuarios WHERE usuario='".$conta['usuario']."'");
    if(mysql_num_rows($check) == 0) {
        $sql = "INSERT INTO usuarios (usuario, senha, vila, avatar, energia, nivel, renegado, reg, timestamp, vitorias, derrotas, empates, yens_fat, yens_perd, batalhas, exptotal, personagem, doujutsu, orgid, orgnome, config_twitter, config_oktwitter, config_apresentacao) VALUES (
            '".$conta['usuario']."',
            '".$conta['senha']."',
            ".$conta['vila'].",
            ".$conta['avatar'].",
            ".$conta['energia'].",
            ".$conta['nivel'].",
            '".(isset($conta['renegado']) ? $conta['renegado'] : 'nao')."',
            '".date('Y-m-d H:i:s')."',
            ".time().",
            ".(isset($conta['vitorias']) ? $conta['vitorias'] : 0).",
            ".(isset($conta['derrotas']) ? $conta['derrotas'] : 0).",
            ".(isset($conta['empates']) ? $conta['empates'] : 0).",
            ".(isset($conta['yens_fat']) ? $conta['yens_fat'] : 0.00).",
            ".(isset($conta['yens_perd']) ? $conta['yens_perd'] : 0.00).",
            ".(isset($conta['batalhas']) ? $conta['batalhas'] : 0).",
            ".(isset($conta['exptotal']) ? $conta['exptotal'] : 0).",
            ".(isset($conta['personagem']) ? $conta['personagem'] : 1).",
            ".(isset($conta['doujutsu']) ? $conta['doujutsu'] : 0).",
            ".(isset($conta['orgid']) ? $conta['orgid'] : 0).",
            '".(isset($conta['orgnome']) ? $conta['orgnome'] : '')."',
            '".(isset($conta['config_twitter']) ? $conta['config_twitter'] : '')."',
            '".(isset($conta['config_oktwitter']) ? $conta['config_oktwitter'] : 'nao')."',
            '".(isset($conta['config_apresentacao']) ? $conta['config_apresentacao'] : '')."'
        )";
        
        if(mysql_query($sql)) {
            echo "Conta ".$conta['usuario']." criada com sucesso!<br>";
        } else {
            echo "Erro ao criar conta ".$conta['usuario'].": ".mysql_error()."<br>";
        }
    } else {
        echo "Conta ".$conta['usuario']." já existe!<br>";
    }
}

echo "<br>Processo concluído! <a href='index.php'>Voltar ao jogo</a>";
?>
