
<?php require_once('trava.php'); ?>
<?php
if(!isset($_GET['filter'])){ echo "<script>self.location='?p=home'</script>"; exit; }
$vilaantiga=$db['vila'];
if($db['renegado']=='sim') $db['vila']=7;
if(!isset($_GET['pg'])) $pg=0; else $pg=$_GET['pg'];
if(($_GET['filter']>7)||($_GET['filter']<0)){ echo "<script>self.location='?p=home'</script>"; exit; }

if (isset($_SESSION['servidor_id'])) {
    $srv_rank = (int)$_SESSION['servidor_id'];
} else {
    $stmt_srv = $conexao->prepare("SELECT servidor_id FROM usuarios WHERE id = ?");
    $stmt_srv->execute([$_SESSION['logado']]);
    $srv_row = $stmt_srv->fetch(PDO::FETCH_ASSOC);
    $srv_rank = (int)($srv_row['servidor_id'] ?? 0);
    $_SESSION['servidor_id'] = $srv_rank;
}
$srv_cond = " AND servidor_id=$srv_rank";
if($_GET['filter']==0) $filtro=" WHERE status<>'banido'".$srv_cond;
if($_GET['filter']==7) $filtro=" WHERE status<>'banido' AND renegado='sim'".$srv_cond;
if(($_GET['filter']==8)or($_GET['filter']>0)&&($_GET['filter']<7)) $filtro=" WHERE status<>'banido' AND renegado='nao' AND vila=".$_GET['filter'].$srv_cond;

if(date('Y-m-d H:i:s')<$db['vip']){
        $posicao=0; $stop=0;
        if(($_GET['filter']==0)or($_GET['filter']==$db['vila'])){
                try {
                        $stmt = $conexao->prepare("SELECT id FROM usuarios".$filtro." ORDER BY nivel DESC, yens_fat DESC, vitorias DESC, derrotas ASC");
                        $stmt->execute();
                        while($dbc = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                if($dbc['id']==$db['id']) {
                                        $stop=1;
                                        break;
                                }
                                $posicao++;
                        }
                } catch (PDOException $e) {
                        // Handle error silently for now
                }
        }
}
$timeout=time()-900;
try {
        $stmt = $conexao->prepare("SELECT usuario, vila, renegado, nivel, vitorias, derrotas, yens_fat, timestamp, personagem, vip FROM usuarios".$filtro." ORDER BY nivel DESC, yens_fat DESC, vitorias DESC, derrotas ASC LIMIT ?,50");
        $stmt->execute([($pg*50)]);
        $dbr = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
        $dbr = false;
}

try {
        $stmt2 = $conexao->prepare("SELECT count(id) as conta FROM usuarios".$filtro);
        $stmt2->execute();
        $dbv = $stmt2->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
        $dbv = ['conta' => 0];
}
?>
<div class="box_top">Ranking</div>
<div class="box_middle">Abaixo está o ranking atual do jogo. Você pode filtrar os ninjas por vila, utilizando o formulário abaixo. Para visualizar sua posição no ranking, utilize o link informado.<div class="sep"></div>
        <div align="center">
    <form method="get" action="?p=rank" onsubmit="subm.value='Carregando...';subm.disabled=true;">
    <input type="hidden" id="p" name="p" value="rank" />
    <select id="filter" name="filter">
        <option value="0">Geral</option>
        <option value="1"<?php if($_GET['filter']==1) echo ' selected="selected"'; ?>>Vila da Folha</option>
        <option value="2"<?php if($_GET['filter']==2) echo ' selected="selected"'; ?>>Vila da Areia</option>
        <option value="3"<?php if($_GET['filter']==3) echo ' selected="selected"'; ?>>Vila do Som</option>
        <option value="4"<?php if($_GET['filter']==4) echo ' selected="selected"'; ?>>Vila da Chuva</option>
        <option value="5"<?php if($_GET['filter']==5) echo ' selected="selected"'; ?>>Vila da Nuvem</option>
        <option value="6"<?php if($_GET['filter']==6) echo ' selected="selected"'; ?>>Vila da Névoa</option>
        <option value="8"<?php if($_GET['filter']==8) echo ' selected="selected"'; ?>>Vila da Pedra</option>
        <option value="7"<?php if($_GET['filter']==7) echo ' selected="selected"'; ?>>Akatsuki</option>
    </select>&nbsp;
    <select id="pg" name="pg">
        <?php $i=1; $pag=0; do{ ?>
        <option value="<?php echo $pag; ?>"<?php if((isset($_GET['pg']))&&($_GET['pg']==$pag)) echo ' selected="selected"'; ?>>De <?php echo $i; ?> à <?php echo $i+49; ?></option>
        <?php $i=$i+50; $pag++; } while($i<=$dbv['conta']); ?>
    </select>&nbsp;
    <input type="submit" id="subm" name="subm" class="botao" value="Filtrar" />
    </form>
    </div>
    <div class="sep"></div>
    <?php if(date('Y-m-d H:i:s')<$db['vip']){ ?>
    <?php if(($_GET['filter']==0)or($_GET['filter']==$db['vila'])){ ?>
    <div class="aviso">Sua Posição: <b><?php echo $posicao; ?>&ordm; lugar</b> [<a href="?p=rank&amp;filter=<?php echo $_GET['filter']; ?>&amp;pg=<?php echo floor($posicao/50); ?>">Ver</a>]</div>
    <div class="sep"></div>
    <?php } ?>
    <?php } ?>
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr class="table_titulo">
                <td width="25">#</td>
            <td align="left" width="120">Ninja</td>
            <td>Vila</td>
            <td>Nível</td>
            <td>Vit.</td>
            <td>Der.</td>
            <td align="left">Yens Fat.</td>
            <td>&nbsp;</td>
            <td width="80">&nbsp;</td>
        </tr>
        <tr>
                <td colspan="9"><div class="sep"></div></td>
        </tr>
        <?php $i=1+(($pg)*50); if(!$dbr) echo '<tr><td colspan="8"><div class="aviso">Nenhum ninja encontrado.</div></div></tr>'; else do{ ?>
        <tr class="table_dados" height="20"<?php if($dbr['usuario']==$db['usuario']) echo ' style="background:url(_img/gradient.jpg)"'; ?>>
                <td><?php echo $i; ?>&ordm;</td>
            <td align="left">&nbsp;<a href="?p=view&amp;view=<?php echo $dbr['usuario']; ?>"><?php echo $dbr['usuario']; ?></a></td>
            <td><img src="_img/rank/<?php if($dbr['vila']==10) echo '1'; else { if($dbr['renegado']=='sim') echo '7'; else echo $dbr['vila']; } ?>.png" /></td>
            <td><b>[<?php echo $dbr['nivel']; ?>]</b></td>
            <td><?php echo $dbr['vitorias']; ?></td>
            <td><?php echo $dbr['derrotas']; ?></td>
            <td align="left"><?php echo number_format($dbr['yens_fat'],2,',','.'); ?></td>
            <td width="18"><img src="_img/<?php if($dbr['timestamp']>=$timeout) echo 'online'; else echo 'offline'; ?>.png" /></td>
            <td align="right"><?php 
                $isVipUser = isset($dbr['vip']) && date('Y-m-d H:i:s') < $dbr['vip'];
            ?><span style="position:relative;display:inline-block;"><img src="_img/rank/<?php echo $dbr['personagem']; ?>.jpg" <?php if($isVipUser) echo 'title="VIP"'; ?> /><?php if($isVipUser): ?><img src="_img/vip.png" alt="VIP" style="position:absolute;top:2px;left:2px;width:18px;height:18px;filter:drop-shadow(0 1px 2px rgba(0,0,0,0.8));pointer-events:none;" /><?php endif; ?></span></td>
        </tr>
        <tr>
                <td colspan="9"><div class="sep"></div></td>
        </tr>
        <?php $i++; } while($dbr=$stmt->fetch(PDO::FETCH_ASSOC)); ?>
    </table>
    <div align="center">
    <form method="get" action="?p=rank" onsubmit="subm.value='Carregando...';subm.disabled=true;">
    <input type="hidden" id="p" name="p" value="rank" />
    <select id="filter" name="filter">
        <option value="0">Geral</option>
        <option value="1"<?php if($_GET['filter']==1) echo ' selected="selected"'; ?>>Vila da Folha</option>
        <option value="2"<?php if($_GET['filter']==2) echo ' selected="selected"'; ?>>Vila da Areia</option>
        <option value="3"<?php if($_GET['filter']==3) echo ' selected="selected"'; ?>>Vila do Som</option>
        <option value="4"<?php if($_GET['filter']==4) echo ' selected="selected"'; ?>>Vila da Chuva</option>
        <option value="5"<?php if($_GET['filter']==5) echo ' selected="selected"'; ?>>Vila da Nuvem</option>
        <option value="6"<?php if($_GET['filter']==6) echo ' selected="selected"'; ?>>Vila da Névoa</option>
        <option value="8"<?php if($_GET['filter']==8) echo ' selected="selected"'; ?>>Vila da Pedra</option>
        <option value="7"<?php if($_GET['filter']==7) echo ' selected="selected"'; ?>>Akatsuki</option>
    </select>&nbsp;
    <select id="pg" name="pg">
        <?php $i=1; $pag=0; do{ ?>
        <option value="<?php echo $pag; ?>"<?php if((isset($_GET['pg']))&&($_GET['pg']==$pag)) echo ' selected="selected"'; ?>>De <?php echo $i; ?> à <?php echo $i+49; ?></option>
        <?php $i=$i+50; $pag++; } while($i<=$dbv['conta']); ?>
    </select>&nbsp;
    <input type="submit" id="subm" name="subm" class="botao" value="Filtrar" />
    </form>
    </div>
</div>
<div class="box_bottom"></div>
