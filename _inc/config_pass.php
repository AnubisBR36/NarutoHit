<?php
if(isset($_POST['pass_atual'])){
        if(!csrf_validar()) { echo "<script>self.location='?p=config&type=pass'</script>"; exit; }
        $atual=$_POST['pass_atual'];
        $nova=$_POST['pass_nova'];
        $nova2=$_POST['pass_nova2'];
        if(($_POST['pass_atual']=='')or($_POST['pass_nova']=='')or($_POST['pass_nova2']=='')){ echo "<script>self.location='?p=config&type=pass'</script>"; exit; }
        if(!senha_verificar((string)$atual, $db['senha'] ?? '')){ echo "<script>self.location='?p=config&type=pass&msg=1'</script>"; exit; }
        if($nova<>$nova2){ echo "<script>self.location='?p=config&type=pass&msg=2'</script>"; exit; }
        try {
                $stmt = $conexao->prepare("UPDATE usuarios SET senha=? WHERE id=?");
                $stmt->execute([senha_hash((string)$nova), $db['id']]);
        } catch (PDOException $e) {
                // Handle error silently
        }
        echo "<script>self.location='?p=config&type=pass&msg=3'</script>";
}
if(isset($_GET['msg'])){
        switch($_GET['msg']){
                case 1: $msg='Senha atual não confere com senha cadastrada no sistema.'; break;
                case 2: $msg='Nova senha digitada não confere com a confirmação.'; break;
                case 3: $msg='Senha alterada com sucesso!'; break;
        }
        echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
}
?>
<fieldset><legend>Alterar Senha</legend>
        <form method="post" action="?p=config&amp;type=pass" style="background:url(_img/config_pass.jpg) no-repeat right top;" onsubmit="subm.value='Carregando...';subm.disabled=true;">
        <?php echo csrf_field(); ?>
        <span class="destaque">Senha Atual:</span><br />
        <input type="password" id="pass_atual" name="pass_atual" /><br />
        <span class="sub2">Digite a senha atual.</span><br /><div class="sep" style="width:180px;"></div>
        <span class="destaque">Nova Senha:</span><br />
        <input type="password" id="pass_nova" name="pass_nova" /><br />
        <span class="sub2">Digite a nova senha.</span><br /><div class="sep" style="width:180px;"></div>
        <span class="destaque">Confirmar Nova Senha:</span><br />
        <input type="password" id="pass_nova2" name="pass_nova2" /><br />
        <span class="sub2">Confirme a nova senha.</span>
        <div class="sep"></div>
        <div align="center"><input type="submit" id="subm" name="subm" class="botao" value="Alterar Senha" /></div>
    </form>
</fieldset>