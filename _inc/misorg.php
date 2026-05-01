<?php
require_once('verificar.php');
if(!isset($_GET['id']) && !isset($_GET['force'])){ echo "<script>self.location='?p=myorg'</script>"; exit; }

// Check for force-start action (leader only)
if(isset($_GET['force'])) {
    $get=$c->decode($_GET['force'],$chaveuniversal);
    $ex=explode(',',$get);
    
    try {
        $stmt = $conexao->prepare("SELECT * FROM organizacoes WHERE id=?");
        $stmt->execute([$db['orgid']]);
        $dbo = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "<script>self.location='?p=myorg&msg=2'</script>"; exit;
    }
    
    // Only leader can force-start
    if($dbo['liderid'] != $db['id']) {
        echo "<script>self.location='?p=myorg&msg=2'</script>"; exit;
    }
    
    try {
        $stmt = $conexao->prepare("SELECT * FROM table_missoes WHERE id=? AND orgid=?");
        $stmt->execute([$ex[0], $ex[1]]);
        $dbm = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "<script>self.location='?p=myorg&msg=2'</script>"; exit;
    }
    
    if(!$dbm) {
        echo "<script>self.location='?p=myorg&msg=2'</script>"; exit;
    }
    
    // Force-start the mission
    $soma=mktime(date('H'), date('i')+($dbm['logo']*20), date('s'));
    $missaofim=date('Y-m-d H:i:s',$soma);
    
    try {
        // If leader is not enrolled yet, enroll them first
        if($db['orgmissao'] != $dbm['id'] && $db['missao'] == 0) {
            $stmt = $conexao->prepare("UPDATE usuarios SET orgmissao=? WHERE id=?");
            $stmt->execute([$dbm['id'], $db['id']]);
            
            $stmt = $conexao->prepare("UPDATE table_missoes SET membros=membros+1 WHERE id=?");
            $stmt->execute([$dbm['id']]);
        }
        
        $stmt = $conexao->prepare("UPDATE table_missoes SET status='andamento' WHERE id=?");
        $stmt->execute([$dbm['id']]);
        
        // Update all enrolled members (including leader now)
        $stmt = $conexao->prepare("UPDATE usuarios SET orgmissao=0, missao=?, missao_fim=? WHERE orgmissao=?");
        $stmt->execute([$dbm['id'], $missaofim, $dbm['id']]);
    } catch (PDOException $e) {
        echo "<script>self.location='?p=myorg&msg=2'</script>"; exit;
    }
    
    echo "<script>self.location='?p=busymission'</script>";
    exit;
}

// Regular mission join
if(($db['orgmissao']>0)or($db['missao']>0)){ echo "<script>self.location='?p=myorg&msg=3'</script>"; exit; }
$get=$c->decode($_GET['id'],$chaveuniversal);
$ex=explode(',',$get);

try {
    $stmt = $conexao->prepare("SELECT * FROM table_missoes WHERE id=?");
    $stmt->execute([$ex[0]]);
    $dbm = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<script>self.location='?p=myorg&msg=2'</script>"; exit;
}

if(!$dbm){ echo "<script>self.location='?p=myorg&msg=2'</script>"; exit; }

try {
    $stmt = $conexao->prepare("UPDATE usuarios SET orgmissao=? WHERE id=?");
    $stmt->execute([$dbm['id'], $db['id']]);
    
    $stmt = $conexao->prepare("UPDATE table_missoes SET membros=membros+1 WHERE id=?");
    $stmt->execute([$dbm['id']]);
} catch (PDOException $e) {
    echo "<script>self.location='?p=myorg&msg=2'</script>"; exit;
}

$dbm['membros']=$dbm['membros']+1;

if($dbm['membros']==$dbm['maximo']){
    $soma=mktime(date('H'), date('i')+($dbm['logo']*20), date('s'));
    $missaofim=date('Y-m-d H:i:s',$soma);
    
    try {
        $stmt = $conexao->prepare("UPDATE table_missoes SET status='andamento' WHERE id=?");
        $stmt->execute([$dbm['id']]);
        
        $stmt = $conexao->prepare("UPDATE usuarios SET orgmissao=0, missao=?, missao_fim=? WHERE orgmissao=?");
        $stmt->execute([$dbm['id'], $missaofim, $dbm['id']]);
    } catch (PDOException $e) {
        // Continue anyway
    }
    
    echo "<script>self.location='?p=busymission'</script>";
    exit;
}

echo "<script>self.location='?p=myorg&msg=4'</script>";
?>
