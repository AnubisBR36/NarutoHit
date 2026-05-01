<?php
if(!isset($_GET['id'])){ echo "<script>self.location='?p=home'</script>"; exit; }
$id=$_GET['id'];
$stmt = $conexao->prepare("SELECT id FROM usuarios WHERE usuario=?");
$stmt->execute([$id]);
$dbf = $stmt->fetch(PDO::FETCH_ASSOC);
if($dbf) {
    $stmt_check = $conexao->prepare("SELECT id FROM amigos WHERE usuarioid=? AND amigoid=?");
    $stmt_check->execute([$db['id'], $dbf['id']]);
    if(!$stmt_check->fetch()) {
        $stmt2 = $conexao->prepare("INSERT INTO amigos (usuarioid,amigoid,status) VALUES (?,?,'nao')");
        $stmt2->execute([$db['id'], $dbf['id']]);
        $stmt3 = $conexao->prepare("INSERT INTO mensagens (data,origem,destino,assunto,msg) VALUES (?,0,?,?,?)");
        $assunto = $db['usuario']." quer ser seu amigo!";
        $msg = 'O usuário <a href=?p=view&view='.strtolower($db['usuario']).' style=color:#444444;>'.$db['usuario'].'</a> deseja ser seu amigo.<br /><br />Para aceitar, clique <a href=?p=acceptfriend&act=accept&id='.$db['id'].' style=color:#444444>aqui</a>. Para rejeitar, clique <a href=?p=acceptfriend&act=reject&id='.$db['id'].' style=color:#444444;>aqui</a>.<br /><br />Lembre-se que, ao aceitar, este usuário estará apto a visualizar suas atualizações.';
        $stmt3->execute([date('Y-m-d H:i:s'), $dbf['id'], $assunto, $msg]);
    }
}
echo "<script>self.location='?p=friends&msg=1'</script>";
?>
