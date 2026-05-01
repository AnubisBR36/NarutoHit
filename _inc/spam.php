<?php
if((!isset($_GET['id']))or(!isset($_GET['name']))){ echo "<script>self.location='?p=home'</script>"; return; }
$id=$c->decode($_GET['id'],$chaveuniversal);
if($id==''){ echo "<script>self.location='?p=home'</script>"; return; }
$name=$c->decode($_GET['name'],$chaveuniversal);
if($name==''){ echo "<script>self.location='?p=home'</script>"; return; }
$stmt = $conexao->prepare("SELECT config_apresentacao FROM usuarios WHERE id = ?");
$stmt->execute([(int)$id]);
$dbs = $stmt->fetch(PDO::FETCH_ASSOC);
$msg = trim((string)($dbs['config_apresentacao'] ?? ''));
// Mesmo sem mensagem de apresentação, registramos a denúncia para revisão.
if($msg===''){ $msg = '(Perfil sem mensagem de apresentação — denúncia genérica)'; }

$chk = $conexao->prepare("SELECT created_at FROM spam WHERE usuarioid = ? AND informanteid = ? ORDER BY id DESC LIMIT 1");
$chk->execute([(int)$id, (int)$db['id']]);
$ultima = $chk->fetch(PDO::FETCH_ASSOC);
if($ultima && (time() - strtotime($ultima['created_at'])) < 86400){
        $restante = 86400 - (time() - strtotime($ultima['created_at']));
        $horas = floor($restante / 3600);
        $minutos = floor(($restante % 3600) / 60);
        echo "<script>alert('Você já denunciou este ninja recentemente. Aguarde ".$horas."h ".$minutos."min para denunciar novamente.');self.location='?p=view&view=".strtolower($name)."'</script>";
        return;
}
try {
        $ins = $conexao->prepare("INSERT INTO spam (usuarioid, usuario, informanteid, informante, mensagem) VALUES (?, ?, ?, ?, ?)");
        $ins->execute([(int)$id, $name, (int)$db['id'], $db['usuario'], $msg]);
} catch (PDOException $e) {
        error_log('Erro ao registrar denúncia: '.$e->getMessage());
}
echo "<script>self.location='?p=view&view=".strtolower($name)."&report=true'</script>";
?>
