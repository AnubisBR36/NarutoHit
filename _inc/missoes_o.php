<div align="center">
<?php
try {
    $stmt = $conexao->prepare("SELECT * FROM table_missoes WHERE orgid=? AND status='aguardo' ORDER BY id ASC LIMIT 3");
    $stmt->execute([$db['orgid']]);
    $missoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $missoes = [];
}

// Check if user is clan leader
$isLeader = (isset($dbo['liderid']) && $dbo['liderid'] == $db['id']);
?>
        <?php if(count($missoes)==0) echo '<div class="sub2">Nenhuma missão disponível.<br />Novas missões são enviadas para cada clã a cada 4 horas.</div>'; else foreach($missoes as $dbx) { ?>
        <?php
                switch($dbx['logo']){
                        case 1: $rank='D'; break;
                        case 2: $rank='C'; break;
                        case 3: $rank='B'; break;
                        case 4: $rank='A'; break;
                        case 5: $rank='S'; break;
                }
                $texto='<b><h2>Missão Rank '.$rank.'</h2></b><b>Membros Inscritos:</b> '.$dbx['membros'].' de '.$dbx['maximo'].'<br /><b>Recompensa:</b> '.number_format($dbx['yens'],2,',','.').' yens<br /><b>Experiência:</b> '.$dbx['exp'].' ponto';
                if($dbx['exp']>1) $texto.='s';
                $texto.='<br /><b>Duração:</b> '.($dbx['logo']*20).' minutos';
                $texto.='<br />Reputação: '.$dbx['logo'].' ponto';
                if($dbx['logo']>1) $texto.='s';
                if(isset($db['orgmissao']) && $db['orgmissao']==$dbx['id']) $texto.='<br /><b><i>Você já está inscrito para esta missão.<br />Aguarde a formação do time.</i></b>';
                ?>
        <div style="display:inline-block;text-align:center;margin:5px;">
        <a href="?p=misorg&amp;id=<?php echo $c->encode($dbx['id'].','.$dbx['orgid'],$chaveuniversal); ?>"><img src="_img/org/missao_<?php echo strtolower($rank); ?>.jpg" border="0" /></a>
        <?php if($isLeader && $dbx['membros'] > 0) { ?>
        <br /><a href="?p=misorg&amp;force=<?php echo $c->encode($dbx['id'].','.$dbx['orgid'],$chaveuniversal); ?>" class="botao" style="font-size:10px;padding:2px 8px;">Iniciar (<?php echo $dbx['membros']; ?>)</a>
        <?php } ?>
        </div>
    <?php } ?>
<div class="sep"></div>
</div>
