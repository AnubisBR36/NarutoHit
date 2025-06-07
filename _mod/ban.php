<?php
if(isset($_GET['ban'])){
	$ban=$_GET['ban'];
	mysql_query("UPDATE usuarios SET status='banido', orgid=0 WHERE id=$ban");
	mysql_query("DELETE FROM membros WHERE usuarioid=$ban");
	mysql_query("UPDATE inventario SET venda='nao' WHERE usuarioid=$ban");
	mysql_query("DELETE FROM amigos WHERE usuarioid=$ban OR amigoid=$ban");
	echo "<script>self.location='?p=ban&msg=1'</script>";
}
if(isset($_GET['search'])){
	$search=" usuario='".$_GET['search']."' AND";
} else $search='';
if(isset($_GET['ordem'])){
	$ordem=$_GET['ordem'];
	if(($_GET['ordem']<>'usuario')&&($_GET['ordem']<>'email')&&($_GET['ordem']<>'venda')){ echo "<script>self.location='?p=ban'</script>"; }
	if($_GET['ordem']=='venda') $ordem='ORDER BY '.$_GET['ordem'].' DESC';
	else $ordem='ORDER BY '.$_GET['ordem'].' ASC';
} else $ordem='';
if(!isset($_GET['pg'])) $pg=0; else $pg=$_GET['pg'];
$min=$pg*50;
$max=50;
$sql=mysql_query("SELECT id, usuario, email, venda, vip FROM usuarios WHERE".$search." status<>'banido'".$ordem." LIMIT $min,$max");
$db=mysql_fetch_assoc($sql);
?>
<?php if(isset($_GET['msg'])) echo '<div class="aviso">Usuário banido.</div><div class="sep"></div>'; ?>
<div style="margin:20px;">
<form method="get" action="?p=ban">
<input type="hidden" id="p" name="p" value="ban" />
<input type="text" id="search" name="search" value="" />
<input type="submit" value="Buscar" />
</form>
</div>
<table width="100%">
<tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
	<td><a href="?p=ban&pg=<?php echo $pg; ?>&ordem=usuario">Usuario</a></td>
    <td><a href="?p=ban&pg=<?php echo $pg; ?>&ordem=email">Email</a></td>
    <td>VIP</td>
    <td><a href="?p=ban&pg=<?php echo $pg; ?>&ordem=venda">Valor Ganho em Vendas</a></td>
    <td>Link</td>
</tr>
<?php if(mysql_num_rows($sql)==0) echo 'Nenhum usuário.'; else do{ ?>
<tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
	<td><?php echo $db['usuario']; ?></td>
    <td><?php echo $db['email']; ?></td>
    <td><?php echo $db['vip']; ?></td>
    <td><?php echo number_format($db['venda'],2,',','.'); ?> yens</td>
    <td><a href="?p=ban&ban=<?php echo $db['id']; ?>">Banir</a></td>
</tr>
<?php } while($db=mysql_fetch_assoc($sql)); ?>
<tr>
	<td colspan="5" align="center"><?php if($pg>0){ ?><a href="?p=<?php echo $_GET['p']; ?>&pg=<?php echo $pg-1; ?><?php if(isset($_GET['ordem'])) echo '&ordem='.$_GET['ordem']; ?>">Anterior</a> / <?php } ?><a href="?p=<?php echo $_GET['p']; ?>&pg=<?php echo $pg+1; ?><?php if(isset($_GET['ordem'])) echo '&ordem='.$_GET['ordem']; ?>">Próxima</a></td>
</tr>
</table>