<?php
require_once('_inc/conexao.php');
if(!isset($_GET['token'])){ echo "<script>self.location='index.php?p=home'</script>"; break; }
if(!isset($_GET['user'])){ echo "<script>self.location='index.php?p=home'</script>"; break; }
$token=$_GET['token'];
$user=$_GET['user'];
$sqlv=mysql_query("SELECT id, email FROM usuarios WHERE usuario='".$user."'");
$dbv=mysql_fetch_assoc($sqlv);
if(md5($dbv['id'])<>$token){ echo "<script>self.location='index.php?p=home'</script>"; break; }

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
$mensagem='<div align="center"><img src="http://www.narutohit.net/_img/support/minilogo2.jpg" style="border-bottom:1px solid #DDDDDD" /><br /><br /><b>Mensagem Importante</b><br />Sua nova senha:<br /><br /><b>'.$senha.'</b><br /><span style="font-size:10px;">Solicitado por você pelo site narutohit.net</span><br /><br /><b><span style="color:#CC0000">A equipe narutoHIT lhe deseja um bom jogo!</span></b><br /><br />Para mais informações, visite nossa <a href="http://www.orkut.com.br/Main#Community?cmm=95565573">comunidade no orkut</a>.<br /><br />Atenciosamente, equipe narutoHIT.</div>';
$assunto='Nova Senha';
$remetente='narutoHIT <contato@narutohit.net>';
$headers = implode ( "\n",array ( "From: $remetente","Subject: ".$assunto,"Return-Path: $remetente","MIME-Version: 1.0","X-Priority: 3","Content-Type: text/html" ) );
$stmt = $conexao->prepare("UPDATE usuarios SET senha=? WHERE usuario=?");
$stmt->execute([md5($senha), $user]);
mail($dbv['email'],'',$mensagem,$headers);
echo '<div align="center">Nova senha enviada para seu email!</div>';
?>