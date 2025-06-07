<?php
if(isset($_POST['enq_id'])){
	mysql_query("UPDATE enquetes SET resp_".$_POST['enq_resposta']."=resp_".$_POST['enq_resposta']."+1 WHERE id=".$_POST['enq_id']);
	$_SESSION['enq_'.$_POST['enq_id']]=1;
	echo "<script>self.location='?p=polls&msg=1'</script>";
}
$sqlp=mysql_query("SELECT * FROM enquetes ORDER BY fim ASC");
$dbp=mysql_fetch_assoc($sqlp);
?>
<div class="box_top">Enquetes</div>
<div class="box_middle">Vote em nossas enquetes e ajude-nos a melhorar o jogo! As enquetes com o ícone azul ainda estão abertas para voto. As enquetes de ícone cinza já encerraram, exibindo os resultados finais.
	<?php
	if(isset($_GET['msg'])){
		switch($_GET['msg']){
			case 1: $msg='Voto computado com sucesso! A equipe narutoHIT agradece pelo seu voto.'; break;
			case 2: $msg='Tempo para votar nesta enquete se esgotou.'; break;
			case 3: $msg='Você já votou nesta enquete.'; break;
		}
		echo '<div class="sep"></div><div class="aviso">'.$msg.'</div>';
	}
	?>
	<table width="100%" cellpadding="0" cellspacing="0">
    	<?php if(mysql_num_rows($sqlp)==0) echo '<tr><td colspan="2"><div class="sep"></div><div class="aviso">Nenhuma enquete no momento.</div></td></tr>'; else do{ if((date('Y-m-d H:i:s')>=$dbp['fim'])&&(isset($_SESSION['enq_'.$dbp['id']]))) unset($_SESSION['enq_'.$dbp['id']]); ?>
    	<tr>
        	<td colspan="2"><div class="sep"></div></td>
        </tr>
        <tr style="background:url(_img/gradient.jpg) repeat-y;">
        	<td valign="top" style="padding-top:2px;" align="center" width="20"><img src="_img/equipamentos/<?php if(date('Y-m-d H:i:s')<$dbp['fim']) echo 'm'; else echo 'u'; ?>.png" width="14" height="14" align="absmiddle" /></td>
            <td><a id="enq_<?php echo $dbp['id']; ?>" href="<?php if(date('Y-m-d H:i:s')>=$dbp['fim']) echo 'pollresult.php?id='.$dbp['id']; else { if(isset($_SESSION['enq_'.$dbp['id']])) echo '?p=polls&msg=3'; else echo 'poll.php?id='.$dbp['id']; } ?>"<?php if(!isset($_SESSION['enq_'.$dbp['id']])) echo ' class="modal" rel="modal"'; ?>><?php echo $dbp['pergunta']; ?></a><br /><span class="sub2"><?php if(date('Y-m-d H:i:s')<$dbp['fim']) echo 'Encerra'; else echo 'Encerrou'; ?> em <?php $ex=explode(' ',$dbp['fim']); $data=explode('-',$ex[0]); echo $data[2].'/'.$data[1].'/'.$data[0].', às '.$ex[1]; ?>.</span></td>
        </tr>
        <?php } while($dbp=mysql_fetch_assoc($sqlp)); ?>
    </table>
</div>
<div class="box_bottom"></div>