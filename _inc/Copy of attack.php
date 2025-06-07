<?php require_once('trava.php'); ?>
<?php require_once('verificar.php'); ?>
<?php require_once('funcoes.php'); ?>
<?php
if(!isset($_SESSION['prepare'])){ echo "<script>self.location='?p=home'</script>"; break; }
$sqli=mysql_query("SELECT u.id, u.usuario, u.yens, u.yens_fat, u.nivel, u.orgid, u.energia, u.energiamax, u.taijutsu, u.ninjutsu, u.genjutsu, u.personagem, u.avatar, u.renegado, u.vila, u.doujutsu, u.doujutsu_nivel, u.doujutsu_exp, u.doujutsu_expmax, u.exp, u.expmax, u.vip, u.missao, o.nivel orgnivel FROM usuarios u LEFT OUTER JOIN organizacoes o ON u.orgid=o.id WHERE u.id=".$_SESSION['prepare']);
$dbi=mysql_fetch_assoc($sqli);
$sqlv=mysql_query("SELECT data FROM relatorios WHERE usuarioid=".$db['id']." AND inimigoid=".$dbi['id']." ORDER BY id DESC LIMIT 1");
$dbv=mysql_fetch_assoc($sqlv);
$soma=mktime(date('H')-12, date('i'), date('s'));
$penalidade=date('Y-m-d H:i:s',$soma);
if($penalidade<$dbv['data']){ echo "<script>self.location='?p=hunt&msg=9'</script>"; break; }
$sqlv=mysql_query("SELECT data FROM relatorios WHERE usuarioid=".$dbi['id']." OR inimigoid=".$dbi['id']." ORDER BY id DESC LIMIT 1");
$dbv=mysql_fetch_assoc($sqlv);
$soma=mktime(date('H'), date('i')-30, date('s'));
$penalidade=date('Y-m-d H:i:s',$soma);
if($penalidade<$dbv['data']){ echo "<script>self.location='?p=hunt&msg=8'</script>"; break; }
if($dbi['missao']==999){ echo "<script>self.location='?p=hunt&msg=10'</script>"; break; }
require_once('verifica_nivelatk.php');
if(mysql_num_rows($sqli)==0){ echo "<script>self.location='?p=hunt&msg=1'</script>"; break; }
if($dbi['energia']<25){ echo "<script>self.location='?p=hunt&msg=2'</script>"; break; }
if($db['energia']<25){ echo "<script>self.location='?p=hunt&msg=13'</script>"; break; }
if($dbi['avatar']==0){ echo "<script>self.location='?p=hunt&msg=12'</script>"; break; }
if(($dbh['renegado']=='sim')&&($db['renegado']=='sim')){ echo "<script>self.location='?p=hunt&msg=11'</script>"; break; }
if(($dbi['vila']==$db['vila'])&&($dbi['renegado']=='nao')&&($db['renegado']=='nao')){ echo "<script>self.location='?p=hunt&msg=11'</script>"; break; }
$sqls=mysql_query("SELECT i.id, t.id itemid, t.nome, t.taijutsu, t.ninjutsu, t.genjutsu, t.imagem, t.categoria FROM inventario i LEFT OUTER JOIN table_itens t ON i.itemid=t.id WHERE i.usuarioid=".$db['id']." AND i.status='on'");
if($db['doujutsu']==2){ $txtdoujutsu1='Byakugan'; $addtai1=round($db['taijutsu']*($db['doujutsu_nivel']/10)); } else $addtai1=0;
if($db['doujutsu']==3){ $txtdoujutsu1='Rinnegan'; $addnin1=round($db['ninjutsu']*($db['doujutsu_nivel']/10)); } else $addnin1=0;
if($db['doujutsu']==1){ $txtdoujutsu1='Sharingan'; $addgen1=round($db['genjutsu']*($db['doujutsu_nivel']/10)); } else $addgen1=0;
if($db['orgnivel']>0){
	$addtai1=$addtai1+$db['orgnivel'];
	$addnin1=$addnin1+$db['orgnivel'];
	$addgen1=$addgen1+$db['orgnivel'];
}
$equips1='';
$equips2='';
while($dbs=mysql_fetch_assoc($sqls)){
	if($equips1=='') $equips1=$dbs['itemid']; else $equips1.=','.$dbs['itemid'];
	if($equips1<>'') substr($equips1,0,strlen($equips1)-1);
	$addtai1=$addtai1+$dbs['taijutsu'];
	$addnin1=$addnin1+$dbs['ninjutsu'];
	$addgen1=$addgen1+$dbs['genjutsu'];
}
$sqls2=mysql_query("SELECT i.id, t.id itemid, t.nome, t.taijutsu, t.ninjutsu, t.genjutsu, t.imagem, t.categoria FROM inventario i LEFT OUTER JOIN table_itens t ON i.itemid=t.id WHERE i.usuarioid=".$dbi['id']." AND i.status='on'");
if($dbi['doujutsu']==2) $addtai2=round($dbi['taijutsu']*($dbi['doujutsu_nivel']/10)); else $addtai2=0;
if($dbi['doujutsu']==3) $addnin2=round($dbi['ninjutsu']*($dbi['doujutsu_nivel']/10)); else $addnin2=0;
if($dbi['doujutsu']==1) $addgen2=round($dbi['genjutsu']*($dbi['doujutsu_nivel']/10)); else $addgen2=0;
if($dbi['orgnivel']>0){
	$addtai2=$addtai2+$dbi['orgnivel'];
	$addnin2=$addnin2+$dbi['orgnivel'];
	$addgen2=$addgen2+$dbi['orgnivel'];
}
while($dbs2=mysql_fetch_assoc($sqls2)){
	if($equips2=='') $equips2=$dbs2['itemid']; else $equips2.=','.$dbs2['itemid'];
	if($equips2<>'') substr($equips2,0,strlen($equips2)-1);
	$addtai2=$addtai2+$dbs2['taijutsu'];
	$addnin2=$addnin2+$dbs2['ninjutsu'];
	$addgen2=$addgen2+$dbs2['genjutsu'];
}
if(mysql_num_rows($sqls)>0) mysql_data_seek($sqls,0); if(mysql_num_rows($sqls2)>0) mysql_data_seek($sqls2,0);

$sqlj=mysql_query("SELECT j.nivel, t.id, t.nome, t.forca, t.texto FROM jutsus j LEFT OUTER JOIN table_jutsus t ON j.jutsu=t.id WHERE j.usuarioid=".$db['id']." AND j.status='ativo' ORDER BY RAND()");
if(mysql_num_rows($sqlj)>0){
	$i=1;
	while($dbj=mysql_fetch_array($sqlj)){
		$idjutsu1[$i]=$dbj['id'];
		$nomejutsu1[$i]=$dbj['nome'];
		$forcajutsu1[$i]=$dbj['forca'];
		$niveljutsu1[$i]=$dbj['nivel'];
		$textojutsu1[$i]=$dbj['texto'];
		$i++;
	}
}

$sqlj2=mysql_query("SELECT j.nivel, t.id, t.nome, t.forca, t.texto FROM jutsus j LEFT OUTER JOIN table_jutsus t ON j.jutsu=t.id WHERE j.usuarioid=".$dbi['id']." AND j.status='ativo' ORDER BY RAND()");
if(mysql_num_rows($sqlj2)>0){
	$i=1;
	while($dbj2=mysql_fetch_array($sqlj2)){
		$idjutsu2[$i]=$dbj2['id'];
		$nomejutsu2[$i]=$dbj2['nome'];
		$forcajutsu2[$i]=$dbj2['forca'];
		$niveljutsu2[$i]=$dbj2['nivel'];
		$textojutsu2[$i]=$dbj2['texto'];
		$i++;
	}
}

$maxj1=mysql_num_rows($sqlj);
$maxj2=mysql_num_rows($sqlj2);
/*do{
	echo $dbj['nome'].'.......';
} while($dbj=mysql_fetch_assoc($sqlj));*/
?>
<?php
switch($db['vila']){
	case 1: $vila='folha'; if($db['renegado']=='sim') $txtvila='Akatsuki (Vila da Folha)'; else $txtvila='Vila da Folha'; break;
	case 2: $vila='areia'; if($db['renegado']=='sim') $txtvila='Akatsuki (Vila da Areia)'; else $txtvila='Vila da Areia'; break;
	case 3: $vila='som'; if($db['renegado']=='sim') $txtvila='Akatsuki (Vila do Som)'; else $txtvila='Vila do Som'; break;
	case 4: $vila='chuva'; if($db['renegado']=='sim') $txtvila='Akatsuki (Vila da Chuva)'; else $txtvila='Vila da Chuva'; break;
	case 5: $vila='nuvem'; if($db['renegado']=='sim') $txtvila='Akatsuki (Vila da Nuvem)'; else $txtvila='Vila da Nuvem'; break;
	case 6: $vila='nevoa'; if($db['renegado']=='sim') $txtvila='Akatsuki (Vila da Névoa)'; else $txtvila='Vila da Névoa'; break;
} ?>
<?php
switch($dbi['vila']){
	case 1: $vilai='folha'; if($dbi['renegado']=='sim') $txtvilai='Akatsuki (Vila da Folha)'; else $txtvilai='Vila da Folha'; break;
	case 2: $vilai='areia'; if($dbi['renegado']=='sim') $txtvilai='Akatsuki (Vila da Areia)'; else $txtvilai='Vila da Areia'; break;
	case 3: $vilai='som'; if($dbi['renegado']=='sim') $txtvilai='Akatsuki (Vila do Som)'; else $txtvilai='Vila do Som'; break;
	case 4: $vilai='chuva'; if($dbi['renegado']=='sim') $txtvilai='Akatsuki (Vila da Chuva)'; else $txtvilai='Vila da Chuva'; break;
	case 5: $vilai='nuvem'; if($dbi['renegado']=='sim') $txtvilai='Akatsuki (Vila da Nuvem)'; else $txtvilai='Vila da Nuvem'; break;
	case 6: $vilai='nevoa'; if($dbi['renegado']=='sim') $txtvilai='Akatsuki (Vila da Névoa)'; else $txtvilai='Vila da Névoa'; break;
} ?>
<?php
$tai=($db['taijutsu']+$addtai1).','.($dbi['taijutsu']+$addtai2);
$nin=($db['ninjutsu']+$addnin1).','.($dbi['ninjutsu']+$addnin2);
$gen=($db['genjutsu']+$addgen1).','.($dbi['genjutsu']+$addgen2);
$ene=$db['energia'].' / '.$db['energiamax'].','.$dbi['energia'].' / '.$dbi['energiamax'];
?>
<div class="box_top"><?php echo $db['usuario']; ?> x <?php echo $dbi['usuario']; ?></div>
<div class="box_middle">
	<table width="100%" cellpadding="0" cellspacing="0">
    	<tr>
        	<td colspan="2" align="center"><b><?php echo $db['usuario']; ?></b></td>
            <td align="center">&nbsp;</td>
            <td colspan="2" align="center"><b><?php echo $dbi['usuario']; ?></b></td>
        </tr>
        <tr>
        	<td colspan="2" align="center"><div class="sep"></div></td>
            <td align="center"></td>
            <td colspan="2" align="center"><div class="sep"></div></td>
        </tr>
    	<tr>
        	<td colspan="2" align="center" width="42%" style="background:url(_img/personagens/no_avatar.jpg) no-repeat center #444444;"><img src="_img/personagens/<?php echo $db['personagem']; ?>/<?php echo $db['avatar']; ?>.jpg" onmouseover="Tip('<div class=tooltip><?php echo fpersonagem($db['personagem']); ?></div>')" onmouseout="UnTip()" /></td>
            <td align="center" width="16%"><img src="_img/versus.jpg" /></td>
            <td colspan="2" align="center" width="42%" style="background:url(_img/personagens/no_avatar.jpg) no-repeat center #444444; position:relative;">
                <img src="_img/personagens/<?php echo $dbi['personagem']; ?>/<?php echo $dbi['avatar']; ?>.jpg"onmouseover="Tip('<div class=t wiooltip><?php echo fpersonagem($dbi['personagem']); ?></div>')" onmouseout="UnTip()" />
                <?php if(is_top1_vila($dbi['id'], $dbi['vila'])): ?>
                    <img src="_img/Chapeu/bg-kage-konoha.jpg" style="position:absolute; top:0; left:0; width:100%; height:100%; z-index:1;" />
                    <img src="_img/Chapeu/Pergaminho.jpg" style="position:absolute; top:0; left:0; width:100%; height:100%; z-index:2;" />
                <?php endif; ?>
            </td>
        </tr>
        <tr>
        	<td colspan="2" bgcolor="#444444" align="center"><img src="_img/NewsVilas/<?php if($db['renegado']=='sim') echo 'akatsuki'; else echo $vila; ?>.jpg" onmouseover="Tip('<div class=tooltip><?php echo $txtvila; ?></div>');" onmouseout="UnTip()" /></td>
            <td align="center">&nbsp;</td>
            <td colspan="2" bgcolor="#444444" align="center"><img src="_img/NewsVilas/<?php if($dbi['renegado']=='sim') echo 'akatsuki'; else echo $vilai; ?>.jpg" onmouseover="Tip('<div class=tooltip><?php echo $txtvilai; ?></div>');" onmouseout="UnTip()" /></td>
        </tr>
        <tr>
        	<td colspan="2" align="center"><div class="sep"></div></td>
            <td align="center"></td>
            <td colspan="2" align="center"><div class="sep"></div></td>
        </tr>
        <tr style="height:17px;">
        	<td width="20%" align="right" style="background:#323232;padding-right:10px;">Nível:</td>
            <td width="22%" style="background:#323232;"><?php if($db['renegado']=='sim') echo 'Nukenin'; else rankNinja($db['nivel']); ?> <b>[Nível <?php echo $db['nivel']; ?>]</b></td>
            <td width="16%"></td>
            <td width="20%" align="right" style="background:#323232;padding-right:10px;">Nível:</td>
            <td width="22%" style="background:#323232;"><?php if($dbi['renegado']=='sim') echo 'Nukenin'; else rankNinja($dbi['nivel']); ?> <b>[Nível <?php echo $dbi['nivel']; ?>]</b></td>
        </tr>
        <tr style="height:17px;">
        	<td align="right" style="background:#2C2C2C;padding-right:10px;">Taijutsu:</td>
            <td style="background:#2C2C2C;"><b>[<?php echo $db['taijutsu']; ?>]</b> <?php if($addtai1>0) echo '+'.$addtai1; ?></td>
            <td></td>
            <td align="right" style="background:#2C2C2C;padding-right:10px;">Taijutsu:</td>
            <td style="background:#2C2C2C;"><b>[<?php echo $dbi['taijutsu']; ?>]</b> <?php if($addtai2>0) echo '+'.$addtai2; ?></td>
        </tr>
        <tr style="height:17px;">
        	<td align="right" style="background:#323232;padding-right:10px;">Ninjutsu:</td>
            <td style="background:#323232;"><b>[<?php echo $db['ninjutsu']; ?>]</b> <?php if($addnin1>0) echo '+'.$addnin1; ?></td>
            <td></td>
            <td align="right" style="background:#323232;padding-right:10px;">Ninjutsu:</td>
            <td style="background:#323232;"><b>[<?php echo $dbi['ninjutsu']; ?>]</b> <?php if($addnin2>0) echo '+'.$addnin2; ?></td>
        </tr>
        <tr style="height:17px;">
        	<td align="right" style="background:#2C2C2C;padding-right:10px;">Genjutsu:</td>
            <td style="background:#2C2C2C;"><b>[<?php echo $db['genjutsu']; ?>]</b> <?php if($addgen1>0) echo '+'.$addgen1; ?></td>
            <td></td>
            <td align="right" style="background:#2C2C2C;padding-right:10px;">Genjutsu:</td>
            <td style="background:#2C2C2C;"><b>[<?php echo $dbi['genjutsu']; ?>]</b> <?php if($addgen2>0) echo '+'.$addgen2; ?></td>
        </tr>
        <tr style="height:17px;">
        	<td align="right" style="background:#323232;padding-right:10px;">Experiência:</td>
            <td style="background:#323232;"><b>[<?php echo $db['exp']; ?> / <?php echo $db['expmax']; ?>]</b></td>
            <td></td>
            <td align="right" style="background:#323232;padding-right:10px;">Experiência:</td>
            <td style="background:#323232;"><b>[<?php echo $dbi['exp']; ?> / <?php echo $dbi['expmax']; ?>]</b></td>
        </tr>
        <tr style="height:17px;">
        	<td align="right" style="background:#2C2C2C;padding-right:10px;">Energia:</td>
            <td style="background:#2C2C2C;"><b>[<?php echo $db['energia']; ?> / <?php echo $db['energiamax']; ?>]</b></td>
            <td></td>
            <td align="right" style="background:#2C2C2C;padding-right:10px;">Energia:</td>
            <td style="background:#2C2C2C;"><b>[<?php echo $dbi['energia']; ?> / <?php echo $dbi['energiamax']; ?>]</b></td>
        </tr>
        <tr>
        	<td colspan="2" align="center"><div class="sep"></div></td>
            <td align="center"></td>
            <td colspan="2" align="center"><div class="sep"></div></td>
        </tr>
        <tr>
        	<td colspan="2" bgcolor="#323232" style="text-align:center">
				<?php $arma1=0; if(mysql_num_rows($sqls)==0) echo 'Nenhum equipamento.'; else do{
					if($arma1==0){
						if($dbs['categoria']=='arma') $arma1=1; else $arma1=0;
					}
					$tip='<b>'.$dbs['nome'].'</b><br />';
					if($dbs['taijutsu']>0) $tip.='[+'.$dbs['taijutsu'].'] em Taijutsu';
					if($dbs['ninjutsu']>0) $tip.='<br />[+'.$dbs['ninjutsu'].'] em Ninjutsu';
					if($dbs['genjutsu']>0) $tip.='<br />[+'.$dbs['genjutsu'].'] em Genjutsu';
					?>
                    <img src="_img/equipamentos/<?php echo $dbs['imagem']; ?>.jpg" width="70" onmouseover="Tip('<div class=tooltip><?php echo $tip; ?></div>')" onmouseout="UnTip()" />
				<?php } while($dbs=mysql_fetch_assoc($sqls)); ?>
             </td>
            <td align="center"></td>
            <td colspan="2" bgcolor="#323232" style="text-align:center">
				<?php $arma2=0; if(mysql_num_rows($sqls2)==0) echo 'Nenhum equipamento.'; else do{
					if($arma2==0){
						if($dbs2['categoria']=='arma') $arma2=1; else $arma2=0;
					}
					$tip='<b>'.$dbs2['nome'].'</b><br />';
					if($dbs2['taijutsu']>0) $tip.='[+'.$dbs2['taijutsu'].'] em Taijutsu';
					if($dbs2['ninjutsu']>0) $tip.='<br />[+'.$dbs2['ninjutsu'].'] em Ninjutsu';
					if($dbs2['genjutsu']>0) $tip.='<br />[+'.$dbs2['genjutsu'].'] em Genjutsu';
					?>
                    <img src="_img/equipamentos/<?php echo $dbs2['imagem']; ?>.jpg" width="70" onmouseover="Tip('<div class=tooltip><?php echo $tip; ?></div>')" onmouseout="UnTip()" />
				<?php } while($dbs2=mysql_fetch_assoc($sqls2)); ?>
            </td>
        </tr>
        <tr>
        	<td colspan="2" align="center"><div class="sep"></div></td>
            <td align="center"></td>
            <td colspan="2" align="center"><div class="sep"></div></td>
        </tr>
        <tr>
        	<td colspan="2" bgcolor="#323232" style="text-align:center"><?php if($db['doujutsu']==0) echo 'Nenhum doujutsu.'; else { ?><img src="_img/doujutsus/<?php echo strtolower($txtdoujutsu1); ?>.jpg" onmouseover="Tip('<div class=tooltip><b><?php echo $txtdoujutsu1; ?></b><br />Nível <?php echo $db['doujutsu_nivel']; ?></div>')" onmouseout="UnTip()" width="200" /><?php } ?></td>
            <td></td>
            <td colspan="2" bgcolor="#323232" style="text-align:center"><?php if($dbi['doujutsu']==0) echo 'Nenhum doujutsu.'; else { ?><img src="_img/doujutsus/<?php echo strtolower($txtdoujutsu2); ?>.jpg" onmouseover="Tip('<div class=tooltip><b><?php echo $txtdoujutsu2; ?></b><br />Nível <?php echo $dbi['doujutsu_nivel']; ?></div>')" onmouseout="UnTip()" width="200" /><?php } ?></td>
        </tr>
        <tr>
        	<td colspan="2" align="center"><div class="sep"></div></td>
            <td align="center"></td>
            <td colspan="2" align="center"><div class="sep"></div></td>
        </tr>
    </table>
</div>
<div class="box_bottom"></div>

<div class="box_top">Relatório Detalhado do Combate</div>
<div class="box_middle">
	<table width="100%" cellpadding="0" cellspacing="0">
    	<?php
		$relatoriofinal='';
		$db['taijutsu']=$db['taijutsu']+$addtai1;
		$db['ninjutsu']=$db['ninjutsu']+$addnin1;
		$db['genjutsu']=$db['genjutsu']+$addgen1;
		$dbi['taijutsu']=$dbi['taijutsu']+$addtai2;
		$dbi['ninjutsu']=$dbi['ninjutsu']+$addnin2;
		$dbi['genjutsu']=$dbi['genjutsu']+$addgen2;
		$i=1;
		$jutsu1=1;
		$jutsu2=1;
		$perdido1=0;
		$dano1=0;
		do{
		?>
        <tr>
        	<td colspan="2"><div class="sep"></div></td>
        </tr>
        <?php
		if($i%2){
			if($jutsu1<=$maxj1){
				$icone='jutsus/'.$idjutsu1[$jutsu1].'.jpg';
				$dano=danojutsu($db['ninjutsu'],$dbi['genjutsu'],($forcajutsu1[$jutsu1]+(floor(($niveljutsu1[$jutsu1]-1)*5))));
				if($dano==1) $msgdano='que perdeu <b>'.floor($dano).' ponto de energia</b>.';
				else if($dano==0) $msgdano='que conseguiu desviar-se.';
				else $msgdano='que perdeu <b>'.floor($dano).' pontos de energia</b>.';
				$msg=str_replace('$player1','<b>'.$db['usuario'].'</b>',$textojutsu1[$jutsu1]);
				$msg=str_replace('$player2','<b>'.$dbi['usuario'].'</b>',$msg);
				$msg=str_replace('$jutsu',$nomejutsu1[$jutsu1],$msg);
				$msg=str_replace('$dano',$msgdano,$msg);
				$jutsu1++;
			} else {
				$icone='';
				$dano=dano($db['taijutsu'],$dbi['genjutsu']);
				if($dano>0){
					$msg='<b>'.$db['usuario'].'</b> atacou '.$dbi['usuario'];
					if(rand(1,3)==1){
						if($arma1==1) $msg.=' com sua arma.'; else $msg.=' com danos físicos.';
					} else $msg.=' com danos físicos.';
					$msg.='<br />'.$dbi['usuario'].' perdeu <b>'.floor($dano).' pontos de energia</b>.';
				} else {
					$msg='<b>'.$db['usuario'].'</b> atacou '.$dbi['usuario'].', mas o inimigo desviou.';
				}
			}
			$dano1=$dano1+floor($dano);
			$dbi['energia']=$dbi['energia']-floor($dano);
		} else {
			if($jutsu2<=$maxj2){
				$icone='jutsus/'.$idjutsu2[$jutsu2].'.jpg';
				$dano=danojutsu($dbi['ninjutsu'],$db['genjutsu'],($forcajutsu2[$jutsu2]+(floor(($niveljutsu2[$jutsu2]-1)*5))));
				if($dano==1) $msgdano='que perdeu <b>'.floor($dano).' ponto de energia</b>.';
				else if($dano==0) $msgdano='que conseguiu desviar-se.';
				else $msgdano='que perdeu <b>'.floor($dano).' pontos de energia</b>.';
				$msg=str_replace('$player1','<b>'.$dbi['usuario'].'</b>',$textojutsu2[$jutsu2]);
				$msg=str_replace('$player2','<b>'.$db['usuario'].'</b>',$msg);
				$msg=str_replace('$jutsu',$nomejutsu2[$jutsu2],$msg);
				$msg=str_replace('$dano',$msgdano,$msg);
				$jutsu2++;
			} else {
				$icone='';
				$dano=dano($dbi['taijutsu'],$db['genjutsu']);
				if($dano>0){
					$msg='<b>'.$dbi['usuario'].'</b> atacou '.$db['usuario'];
					if(rand(1,3)==1){
						if($arma2==1) $msg.=' com sua arma.'; else $msg.=' com danos físicos.';
					} else $msg.=' com danos físicos.';
					$msg.='<br />'.$db['usuario'].' perdeu <b>'.floor($dano).' pontos de energia</b>.';
				} else {
					$msg='<b>'.$dbi['usuario'].'</b> atacou '.$db['usuario'].', mas o inimigo desviou.';
				}
			}
			$perdido1=$perdido1+floor($dano);
			$db['energia']=$db['energia']-floor($dano);
		}
		?>
    	<tr>
        	<td <?php if($icone=='') echo 'colspan="2" '; ?>style="padding-left:5px;padding-right:15px;<?php if($i%2) echo 'background:url(_img/gradient.jpg) repeat-y;'; ?>"><?php echo $msg; $relatoriofinal.=$msg.'<div class="sep"></div>'; ?></td>
            <?php if($icone<>''){ ?><td><img src="_img/<?php echo $icone; ?>" /></td><?php } ?>
        </tr>
        <?php
        $i++;
		} while(($i<=20)and($db['energia']>15)and($dbi['energia']>15)); ?>
    </table>
</div>
<div class="box_bottom"></div>

<div class="box_top">Resultado do Combate</div>
<div class="box_middle">
	<?php
	$a=0;
	if($dbi['energia']<15) $a=1; else
	if($db['energia']<15) $a=2; else
	if($dano1>$perdido1) $a=1; else
	if($perdido>$dano1) $a=2; else
	$a=3;
	if(($dbi['energia']<15)or($dano1>$perdido1)){
		$vencedorid=$db['id'];
		$vencedor=$db['usuario'];
		$perdedor=$dbi['usuario'];
		$yens=floor($dbi['yens']*0.1);
		if($db['nivel']>$dbi['nivel']) $exp_1=1; else
		if($db['nivel']==$dbi['nivel']) $exp_1=2; else
		if($db['nivel']<$dbi['nivel']) $exp_1=3;
		$exp_2=4-($exp_1);
		if(($db['energia']-$perdido1)<0) $energia_1=0; else $energia_1=$db['energia']-$perdido1;
		if(($dbi['energia']-$dano1)<0) $energia_2=0; else $energia_2=$dbi['energia']-$dano1;
		if($db['doujutsu']>0) $expd_1=$exp_1; else $expd_1=0;
		if($dbi['doujutsu']>0) $expd_2=$exp_2; else $expd_2=0;
		if(date('Y-m-d H:i:s')<$db['vip']) $pen=5; else $pen=10;
		$soma=mktime(date('H'), date('i')+($pen), date('s'));
		$penalidade=date('Y-m-d H:i:s',$soma);
		if($db['energia']<0) $db['energia']=0;
		if($dbi['energia']<0) $dbi['energia']=0;
		mysql_query("UPDATE usuarios SET yens=yens+$yens, yens_fat=yens_fat+$yens, exp=exp+$exp_1, exptotal=exptotal+$exp_1, penalidade_fim='$penalidade', vitorias=vitorias+1, batalhas=batalhas+1, doujutsu_exp=doujutsu_exp+$expd_1, energia=".$db['energia']." WHERE id=".$db['id']);
		mysql_query("UPDATE usuarios SET yens=yens-$yens, yens_perd=yens_perd+$yens, exp=exp+$exp_2, exptotal=exptotal+$exp_2, derrotas=derrotas+1, batalhas=batalhas+1, doujutsu_exp=doujutsu_exp+$expd_2, energia=".$dbi['energia']." WHERE id=".$dbi['id']);
	} else
	if(($db['energia']<15)or($perdido1>$dano1)){
		$vencedorid=$dbi['id'];
		$vencedor=$dbi['usuario'];
		$perdedor=$db['usuario'];
		$yens=floor($db['yens']*0.1);
		if($db['nivel']>$dbi['nivel']) $exp_1=1; else
		if($db['nivel']==$dbi['nivel']) $exp_1=2; else
		if($db['nivel']<$dbi['nivel']) $exp_1=3;
		$exp_2=4-$exp_1;
		if(($db['energia']-$perdido1)<0) $energia_1=0; else $energia_1=$db['energia']-$perdido1;
		if(($dbi['energia']-$dano1)<0) $energia_2=0; else $energia_2=$dbi['energia']-$dano1;
		if($db['doujutsu']>0) $expd_1=$exp_1; else $expd_1=0;
		if($dbi['doujutsu']>0) $expd_2=$exp_2; else $expd_2=0;
		if(date('Y-m-d H:i:s')<$db['vip']) $pen=5; else $pen=10;
		$soma=mktime(date('H'), date('i')+($pen), date('s'));
		$penalidade=date('Y-m-d H:i:s',$soma);
		if($db['energia']<0) $db['energia']=0;
		if($dbi['energia']<0) $dbi['energia']=0;
		mysql_query("UPDATE usuarios SET yens=yens-$yens, yens_perd=yens_perd+$yens, exp=exp+$exp_1, exptotal=exptotal+$exp_1, penalidade_fim='$penalidade', derrotas=derrotas+1, batalhas=batalhas+1, doujutsu_exp=doujutsu_exp+$expd_1, energia=".$db['energia']." WHERE id=".$db['id']);
		mysql_query("UPDATE usuarios SET yens=yens+$yens, yens_fat=yens_fat+$yens, exp=exp+$exp_2, exptotal=exptotal+$exp_2, vitorias=vitorias+1, batalhas=batalhas+1, doujutsu_exp=doujutsu_exp+$expd_2, energia=".$dbi['energia']." WHERE id=".$dbi['id']);
	} else
	if($perdido1==$dano1){
		$vencedorid=0;
		$vencedor='Empate';
		$perdedor='Empate';
		$yens=0;
		$exp_1=1;
		$exp_2=1;
		if(($db['energia']-$perdido1)<0) $energia_1=0; else $energia_1=$db['energia']-$perdido1;
		if(($dbi['energia']-$dano1)<0) $energia_2=0; else $energia_2=$dbi['energia']-$dano1;
		if($db['doujutsu']>0) $expd_1=$exp_1; else $expd_1=0;
		if($dbi['doujutsu']>0) $expd_2=$exp_2; else $expd_2=0;
		if(date('Y-m-d H:i:s')<$db['vip']) $pen=5; else $pen=10;
		$soma=mktime(date('H'), date('i')+($pen), date('s'));
		$penalidade=date('Y-m-d H:i:s',$soma);
		if($db['energia']<0) $db['energia']=0;
		if($dbi['energia']<0) $dbi['energia']=0;
		mysql_query("UPDATE usuarios SET exp=exp+$exp_1, exptotal=exptotal+$exp_1, penalidade_fim='$penalidade', empates=empates+1, batalhas=batalhas+1, doujutsu_exp=doujutsu_exp+$expd_1, energia=".$db['energia']." WHERE id=".$db['id']);
		mysql_query("UPDATE usuarios SET exp=exp+$exp_2, exptotal=exptotal+$exp_2, empates=empates+1, batalhas=batalhas+1, doujutsu_exp=doujutsu_exp+$expd_2, energia=".$dbi['energia']." WHERE id=".$dbi['id']);
	}
	$exp=$exp_1.','.(4-$exp_2);
	$danos=$dano1.','.$perdido1;
	$reldoujutsu=$db['doujutsu'].','.$dbi['doujutsu'];
	$nivel=$db['nivel'].','.$dbi['nivel'];
	mysql_query("INSERT INTO relatorios (data, usuarioid, inimigoid, vencedor, exp, yens, taijutsu, ninjutsu, genjutsu, energia, equips1, equips2, danos, nivel, doujutsu) VALUES ('".date('Y-m-d H:i:s')."', ".$db['id'].", ".$dbi['id'].", $vencedorid, '$exp', $yens, '$tai', '$nin', '$gen', '$ene', '$equips1', '$equips2', '$danos', '$nivel', '$reldoujutsu')");
	$relid=mysql_insert_id();
	$fp=fopen('reports/r'.$relid.'.txt','w');
	fwrite($fp,$relatoriofinal);
	fclose($fp);
	?>
	<table width="100%" cellpadding="0" cellspacing="0">
    	<tr style="background:url(_img/gradient.jpg) repeat-y;">
        	<td width="25%"><b><?php echo $db['usuario']; ?></b></td>
            <td>causou <?php echo $dano1; ?> pontos de dano</td>
            <td>perdeu <?php echo $perdido1; ?> pontos de energia</td>
        </tr>
        <tr>
        	<td colspan="3"><div class="sep"></div></td>
        </tr>
        <tr style="background:url(_img/gradient.jpg) repeat-y;">
        	<td><b><?php echo $dbi['usuario']; ?></b></td>
            <td>causou <?php echo $perdido1; ?> pontos de dano</td>
            <td>perdeu <?php echo $dano1; ?> pontos de energia</td>
        </tr>
        <tr>
        	<td colspan="3"><div class="sep"></div></td>
        </tr>
        <tr>
        	<td colspan="3"><div class="aviso"><b><?php echo $vencedor; ?></b> venceu o combate.<br /><span class="sub2"><?php echo $vencedor; ?> recebeu <?php echo number_format($yens,2,',','.'); ?> yen<?php if($yens>1) echo 's'; ?> e adquiriu <?php echo $exp_1; ?> ponto<?php if($exp_1>1) echo 's'; ?> de experiência;<br /><?php echo $perdedor; ?> apenas adquiriu <?php echo $exp_2; ?> ponto<?php if($exp_2>1) echo 's'; ?> de experiência.<?php /*<br /><div id="atk" style="text-align:left;"><a href="javascript:carregaAjax('atk','search_atk.php?value=<?php echo base64_encode($yens); ?>&id=<?php echo base64_encode($db['id']); ?>&name=<?php echo base64_encode($db['usuario']); ?>','n');">Postar em minhas atualizações.</a></div>*/ ?></span></div></td>
        </tr>
    </table>
</div>
<div class="box_bottom"></div>
<?php
@mysql_free_result($sqli);
@mysql_free_result($sqls);
@mysql_free_result($sqls2);
@mysql_free_result($sqlc);
@mysql_free_result($sqlc2);
@mysql_free_result($sqlj);
@mysql_free_result($sqlj2);
?>