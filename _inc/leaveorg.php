<?php
if($db['orgid']==0){ echo "<script>self.location='?p=home'</script>"; exit; }

try {
    // Verificar se o usuário é o líder do clã
    $stmt = $conexao->prepare("SELECT liderid FROM organizacoes WHERE id=?");
    $stmt->execute([$db['orgid']]);
    $org = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($org && $org['liderid'] == $db['id']) {
        // Se for o líder, destruir o clã completamente
        
        // Remover todos os membros do clã
        $stmt = $conexao->prepare("DELETE FROM membros WHERE orgid=?");
        $stmt->execute([$db['orgid']]);
        
        // Deletar a organização
        $stmt = $conexao->prepare("DELETE FROM organizacoes WHERE id=?");
        $stmt->execute([$db['orgid']]);
        
        // Atualizar todos os usuários que ainda tinham orgid para este clã
        if($db['missao']>=100){
            $stmt = $conexao->prepare("UPDATE usuarios SET orgid=0, orgmissao=0, missao=0 WHERE orgid=?");
            $stmt->execute([$db['orgid']]);
        } else {
            $stmt = $conexao->prepare("UPDATE usuarios SET orgid=0, orgmissao=0 WHERE orgid=?");
            $stmt->execute([$db['orgid']]);
        }
        
        echo "<script>self.location='?p=org&msg=5'</script>";
    } else {
        // Se não for o líder, apenas sair do clã
        if($db['missao']>=100){
            $stmt = $conexao->prepare("UPDATE usuarios SET orgid=0, missao=0 WHERE id=?");
            $stmt->execute([$db['id']]);
        } else {
            $stmt = $conexao->prepare("UPDATE usuarios SET orgid=0 WHERE id=?");
            $stmt->execute([$db['id']]);
        }
        
        $stmt = $conexao->prepare("DELETE FROM membros WHERE usuarioid=?");
        $stmt->execute([$db['id']]);
        
        echo "<script>self.location='?p=org&msg=1'</script>";
    }
} catch (PDOException $e) {
    echo "<script>self.location='?p=home'</script>";
}
?>