<?php
try {
    // Verificar se o usuário é membro do clã
    $stmt = $conexao->prepare("SELECT posicao FROM membros WHERE usuarioid=? AND orgid=? AND status='sim'");
    $stmt->execute([$db['id'], $db['orgid']]);
    $dbv = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Buscar informações da organização
    $stmt = $conexao->prepare("SELECT * FROM organizacoes WHERE id=?");
    $stmt->execute([$db['orgid']]);
    $dbc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar se o usuário tem permissão (líder da organização OU posição <= 2)
    $isLeader = ($dbc && $dbc['liderid'] == $db['id']);
    $hasPermission = ($dbv && $dbv['posicao'] <= 2);
    
    if(!$dbv || (!$isLeader && !$hasPermission)){ 
        echo "<script>self.location='?p=myorg'</script>"; 
        exit; 
    }
    
    if(isset($_POST['org'])){
        if(!csrf_validar()) { echo "<script>self.location='?p=configorg'</script>"; exit; }
        $logo=$_POST['org_logo'];
        $minimo=$_POST['org_nivel'];
        if($minimo<0){ echo "<script>self.location='?p=home'</script>"; exit; }
        $desc=$_POST['org_desc'];
        
        $stmt = $conexao->prepare("UPDATE organizacoes SET descricao=?, minimo=?, logo=? WHERE id=?");
        $stmt->execute([$desc, $minimo, $logo, $dbc['id']]);
        
        echo "<script>self.location='?p=configorg&msg=1'</script>";
    }
} catch (PDOException $e) {
    echo "<script>self.location='?p=myorg'</script>";
    exit;
}
?>
<div class="box_top">Configurar Clã</div>
<div class="box_middle"><div align="center"><a href="?p=myorg">Informações</a> | <a href="?p=configorg">Configurar</a> | <a href="?p=addorg">Recrutar</a><?php /* | <a href="?p=donateorg">Doar Yens</a>*/ ?></div><div class="sep"></div>
        <?php
        if(isset($_GET['msg'])){
                switch($_GET['msg']){
                        case 1: $msg='Configurações salvas com sucesso!'; break;
                }
        echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';}
        ?>
        <form method="post" action="?p=configorg" onsubmit="subm.value='Carregando...';subm.disabled=true;">
    <?php echo csrf_field(); ?>
    <input type="hidden" id="org" name="org" value="1">
        <fieldset><legend>Configuração do Clã</legend>
    <span class="destaque">Logotipo do Clã:</span><br />
    <input type="text" id="org_logo" name="org_logo" value="<?php echo $dbc['logo']; ?>" size="50"><br />
    <span class="sub2">Digite o endereço de uma imagem para o logotipo do clã (tamanho: 195x140 pixels).</span>
    <div class="sep"></div>
    <span class="destaque">Nível Mínimo:</span><br />
    <input type="text" id="org_nivel" name="org_nivel" value="<?php echo $dbc['minimo']; ?>" maxlength="3" size="6"><br />
    <span class="sub2">Digite o nível mínimo para recrutar membros para o clã.</span>
    <div class="sep"></div>
    </fieldset>
    <fieldset><legend>Descrição do Clã</legend>
    <textarea class="campo" id="org_desc" name="org_desc" style="width:100%;height:250px;"><?php echo $dbc['descricao']; ?></textarea>
    <span class="sub2">Digite a descrição de seu clã.</span>
    </fieldset>
    <div class="sep"></div>
    <div align="center"><input type="submit" id="subm" name="subm" class="botao" value="Salvar Configurações" /></div>
    </form>
</div>
<div class="box_bottom"></div>