<?php
function SomarData($data, $dias, $meses, $ano){
   /*www.brunogross.com*/
   //passe a data no formato dd/mm/yyyy 
   $data = explode("/", $data);
   $newData = date("d/m/Y", mktime(0, 0, 0, $data[1] + $meses,
     $data[0] + $dias, $data[2] + $ano) );
   return $newData;
}

if(isset($_GET['accept'])){
	$sql=mysql_query("SELECT * FROM vip WHERE id=".$_GET['accept']);
	$db=mysql_fetch_assoc($sql);
	$sqlu=mysql_query("SELECT vip FROM usuarios WHERE id=".$db['usuarioid']);
	$dbu=mysql_fetch_assoc($sqlu);
	$ex=explode(' ',$dbu['vip']);
	if(date('Y-m-d H:i:s')<$dbu['vip']){
		$hunt=0;
		$data=explode('-',$ex[0]);
	} else {
		$hunt=6;
		$data=explode('-',date('Y-m-d'));
	}
	$dias=$_GET['days'];
	$novadata=SomarData($data[2].'/'.$data[1].'/'.$data[0],$dias,0,0); 
	$data=explode('/',$novadata);
	$vipfim=$data[2].'-'.$data[1].'-'.$data[0].' '.$ex[1];
	mysql_query("UPDATE usuarios SET vip='".$vipfim."', hunt_restantes=hunt_restantes+$hunt WHERE id=".$db['usuarioid']);
	mysql_query("UPDATE vip SET status='entregue', obs='Confirmação válida.<br />VIP de 45 dias entregue ao usuário.<br />Obrigado.' WHERE id=".$_GET['accept']);
	echo "<script>self.location='?p=vip&msg=1'</script>";
}
if(isset($_GET['reject'])){
	mysql_query("UPDATE vip SET status='cancelado', obs='Confirmação não existe.' WHERE id=".$_GET['reject']);
	echo "<script>self.location='?p=vip'</script>";
}
$sql=mysql_query("SELECT * FROM vip WHERE status='analise' ORDER BY id ASC");
$db=mysql_fetch_assoc($sql);
?>
<?php if(isset($_GET['msg'])) echo '<div class="aviso">VIP confirmada.</div><div class="sep"></div>'; ?>
<table width="100%">
<?php if(mysql_num_rows($sql)==0) echo 'Nenhuma confirmação.'; else do{ ?>
<tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
	<td><?php echo $db['autenticacao']; ?></td>
    <td><?php echo $db['usuarioid']; ?></td>
    <td><?php echo $db['meio']; ?></td>
    <td><a href="?p=vip&accept=<?php echo $db['id']; ?>&days=30" onClick="if(confirm('Confirmar?')==false) return false;">Aceitar 30 dias</a></td>
    <td><a href="?p=vip&accept=<?php echo $db['id']; ?>&days=45" onClick="if(confirm('Confirmar?')==false) return false;">Aceitar 45 dias</a></td>
    <td><a href="?p=vip&reject=<?php echo $db['id']; ?>">Recusar</a></td>
</tr>
<?php } while($db=mysql_fetch_assoc($sql)); ?>
</table>