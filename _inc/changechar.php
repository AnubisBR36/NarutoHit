<?php
if((!isset($_GET['promo']))or(!isset($_SESSION['promo']))){ echo "<script>self.location='?p=home&t=q'</script>"; break; }
$promo=$_GET['promo'];
if($promo==$_SESSION['promo']){
	unset($_SESSION['promo']);
	mysql_query("UPDATE usuarios SET personagem='".$_SESSION['newchar']."', avatar=1 WHERE id=".$db['id']) or die(mysql_error());
	unset($_SESSION['newchar']);
	echo "<script>self.location='?p=home'</script>";
}
?>