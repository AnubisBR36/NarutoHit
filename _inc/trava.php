<?php
if((!isset($_COOKIE['logado']))or(!isset($_SESSION['logado']))){
	//setcookie('logado',1,time()-3600);
	unset($_SESSION['logado']);
	header("Location: index.php?p=login");
	exit();
}
?>