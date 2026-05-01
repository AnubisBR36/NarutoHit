
<?php
if($db['orgid']>0){ echo "<script>self.location='?p=home'</script>"; exit; }
if(!isset($_GET['id'])){ echo "<script>self.location='?p=home'</script>"; exit; }

$get=$c->decode($_GET['id'],$chaveuniversal);
$ex=explode(',',$get);
$id=$ex[0];
$minimo=$ex[1];

if($db['nivel']<$minimo){ echo "<script>self.location='?p=org&msg=4'</script>"; exit; }

try {
    $stmt = $conexao->prepare("SELECT count(id) conta FROM membros WHERE orgid=? AND usuario_id=? AND status='nao'");
    $stmt->execute([$id, $db['id']]);
    $dbv = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($dbv['conta']>0){ echo "<script>self.location='?p=org&msg=3'</script>"; exit; }
    
    $stmt = $conexao->prepare("INSERT INTO membros (orgid, organizacao_id, usuario_id, usuarioid, posicao, rank, doado, status, missoes) VALUES (?, ?, ?, ?, 3, 'Membro', 0, 'nao', 0)");
    $stmt->execute([$id, $id, $db['id'], $db['id']]);
    
    echo "<script>self.location='?p=org&msg=2'</script>";
} catch (PDOException $e) {
    echo "<script>self.location='?p=org&msg=1'</script>";
}
?>
