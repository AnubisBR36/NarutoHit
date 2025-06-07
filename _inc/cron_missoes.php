<?php
require_once('conexao.php');
$sqlv=mysql_query("SELECT count(id) conta FROM organizacoes");
$dbv=mysql_fetch_assoc($sqlv);
$sqlo=mysql_query("SELECT id, nivel FROM organizacoes ORDER BY RAND()");
$dbo=mysql_fetch_assoc($sqlo);
do{
	if($dbo['nivel']<60){
		if($dbo['nivel']<3) $logo=1; else
		if($dbo['nivel']<7) $logo=2; else
		if($dbo['nivel']<13) $logo=3; else
		if($dbo['nivel']<25) $logo=4; else
		$logo=5;
		$membros=5+($dbo['nivel']*2);
		$max=rand(floor($membros/3),floor($membros/2));
		$yens=($logo*350);
		$exp=$logo*(rand(1,2));
		mysql_query("INSERT INTO table_missoes (orgid, maximo, yens, exp, logo) VALUES (".$dbo['id'].", $max, $yens, $exp, $logo)");
	}
} while($dbo=mysql_fetch_assoc($sqlo));
?>