<?php
if(!isset($_GET['act'])){ echo "<script>self.location='?p=home'</script>"; exit; }
if(!isset($_GET['id'])){ echo "<script>self.location='?p=home'</script>"; exit; }

$stmt = $conexao->prepare("SELECT id,amigoid FROM amigos WHERE usuarioid=? AND amigoid=? AND status='nao'");
$stmt->execute([$_GET['id'], $db['id']]);
$dbf = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$dbf){ echo "<script>self.location='?p=home'</script>"; exit; }
if($db['id'] != $dbf['amigoid']){ echo "<script>self.location='?p=home'</script>"; exit; }

if($_GET['act']=='accept'){
    $stmt2 = $conexao->prepare("UPDATE amigos SET status='sim' WHERE id=?");
    $stmt2->execute([$dbf['id']]);
    
    $stmt3 = $conexao->prepare("INSERT INTO mensagens (data,origem,destino,assunto,msg) VALUES (?,0,?,?,?)");
    $assunto = 'Resposta de '.$db['usuario'];
    $msg = 'O usuário <a href=?p=view&view='.strtolower($db['usuario']).' style=color:#444444;>'.$db['usuario'].'</a> aceitou sua proposta de amizade. A partir de agora, ele estará em sua lista de amigos, e você poderá visualizar as atualizações deste usuário, caso tenha permissão.';
    $stmt3->execute([date('Y-m-d H:i:s'), $_GET['id'], $assunto, $msg]);
    
    echo "<script>self.location='?p=friends&msg=2'</script>";
} else {
    $stmt2 = $conexao->prepare("DELETE FROM amigos WHERE id=?");
    $stmt2->execute([$dbf['id']]);
    
    $stmt3 = $conexao->prepare("INSERT INTO mensagens (data,origem,destino,assunto,msg) VALUES (?,0,?,?,?)");
    $assunto = 'Resposta de '.$db['usuario'];
    $msg = 'O usuário <a href=?p=view&view='.strtolower($db['usuario']).' style=color:#444444;>'.$db['usuario'].'</a> rejeitou sua proposta de amizade.';
    $stmt3->execute([date('Y-m-d H:i:s'), $_GET['id'], $assunto, $msg]);
    
    echo "<script>self.location='?p=friends&msg=3'</script>";
}
?>
