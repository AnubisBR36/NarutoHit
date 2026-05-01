<?php
require_once('_inc/conexao.php');
if(!isset($_GET['token'])){ echo "<script>self.location='index.php?p=home'</script>"; exit; }
if(!isset($_GET['user'])){ echo "<script>self.location='index.php?p=home'</script>"; exit; }
$token=$_GET['token'];
$user=$_GET['user'];
$stmt = $conexao->prepare("SELECT id, email FROM usuarios WHERE usuario = ?");
$stmt->execute([$user]);
$dbv = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$dbv || md5($dbv['id'])<>$token){ echo "<script>self.location='index.php?p=home'</script>"; exit; }

function createRandomPassword() {
    $chars = "bcdfghjkmnpqrstvwxyz023456789";
    srand((double)microtime()*1000000);
    $i = 0;
    $pass = '' ;
    while ($i <= 7) {
        $num = rand() % 33;
        $tmp = substr($chars, $num, 1);
        $pass = $pass . $tmp;
        $i++;
    }
    return $pass;
}

$senha=createRandomPassword();
$mensagem='<div align="center"><img src="http://www.anubisserve.net/_img/support/minilogo2.jpg" style="border-bottom:1px solid #DDDDDD" /><br /><br /><b>Mensagem Importante</b><br />Sua nova senha:<br /><br /><b>'.$senha.'</b><br /><span style="font-size:10px;">Solicitado por você pelo site anubisserve.net</span><br /><br /><b><span style="color:#CC0000">A equipe '.nome_servidor().' lhe deseja um bom jogo!</span></b><br /><br />Atenciosamente, equipe '.nome_servidor().'.</div>';
$assunto='Nova Senha';
$remetente=nome_servidor().' <contato@anubisserve.net>';
$headers = implode ( "\n",array ( "From: $remetente","Subject: ".$assunto,"Return-Path: $remetente","MIME-Version: 1.0","X-Priority: 3","Content-Type: text/html" ) );
$stmt = $conexao->prepare("UPDATE usuarios SET senha=? WHERE usuario=?");
$stmt->execute([senha_hash($senha), $user]);
mail($dbv['email'],'',$mensagem,$headers);
echo '<div align="center">Nova senha enviada para seu email!</div>';
?>