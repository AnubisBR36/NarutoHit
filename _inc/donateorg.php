<?php
try {
    $stmt = $conexao->prepare("SELECT liderid FROM organizacoes WHERE id=?");
    $stmt->execute([$db['orgid']]);
    $dbo = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dbo = null;
}

if(isset($_POST['don'])){
    if(!csrf_validar()) { echo "<script>self.location='?p=donateorg'</script>"; exit; }
    $yens = (int)floor((float)$_POST['don_yens']);
    if($yens <= 0){ echo "<script>self.location='?p=donateorg&msg=3'</script>"; exit; }
    if($yens > $db['yens']){ echo "<script>self.location='?p=donateorg&msg=2'</script>"; exit; }
    try {
        $conexao->prepare("UPDATE organizacoes SET deposito=deposito+? WHERE id=?")->execute([$yens, $db['orgid']]);
        $conexao->prepare("UPDATE membros SET doado=doado+? WHERE orgid=? AND usuarioid=?")->execute([$yens, $db['orgid'], $db['id']]);
        $conexao->prepare("UPDATE usuarios SET yens=yens-? WHERE id=?")->execute([$yens, $db['id']]);
        echo "<script>self.location='?p=donateorg&msg=1&yens=$yens'</script>";
    } catch (PDOException $e) {
        echo "<script>self.location='?p=donateorg&msg=4'</script>";
    }
    exit;
}
?>
<div class="box_top">Doar Yens</div>
<div class="box_middle"><div align="center"><a href="?p=myorg">Informações</a> | <a href="?p=configorg">Configurar</a> | <a href="?p=addorg">Recrutar</a> | <a href="?p=donateorg">Doar Yens</a></div><div class="sep"></div>Para que o clã cresça, é necessário que os membros ajudem com um empurrãozinho financeiro! Doe yens para seu clã, para que o mesmo possa ampliar suas instalações. Digite a quantidade de yens que deseja doar no campo abaixo.<div class="sep"></div>
    <?php
    if(isset($_GET['msg'])){
        switch((int)$_GET['msg']){
            case 1:
                if(!isset($_GET['yens'])){ echo "<script>self.location='?p=home'</script>"; break; }
                $yens_msg = (int)$_GET['yens'];
                $msg = 'Foram doados <b>'.number_format($yens_msg,2,',','.').' yens</b>!';
                break;
            case 2: $msg='Você não possui a quantia de yens informada.'; break;
            case 3: $msg='Valor inválido para doação.'; break;
            case 4: $msg='Erro ao processar doação. Tente novamente.'; break;
        }
        echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
    }
    ?>
    <div style="padding-left:5px;background:url(_img/gradient.jpg) repeat-y;font-weight:bold;color:#FFFFAA;"><img src="_img/yens.png" width="14" height="14" align="absmiddle" /> Meus yens: <?php echo number_format($db['yens'],2,',','.'); ?> yens</div><div class="sep"></div>
    <form method="post" action="?p=donateorg" onsubmit="subm.value='Carregando...';subm.disabled=true;">
    <?php echo csrf_field(); ?>
    <input type="hidden" id="don" name="don" value="1" />
    <span class="destaque">Yens para Doação:</span><br />
    <input type="text" id="don_yens" name="don_yens" /><br />
    <span class="sub2">Digite a quantidade de yens para doação (apenas números).</span>
    <div class="sep"></div>
    <div align="center"><input type="submit" id="subm" name="subm" class="botao" value="Doar Yens" /></div>
    </form>
</div>
<div class="box_bottom"></div>
