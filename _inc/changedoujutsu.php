<?php
if(date('Y-m-d H:i:s')>=$db['vip']){ echo "<script>self.location='?p=home'</script>"; break; }
if(!isset($_GET['new'])){ echo "<script>self.location='?p=home'</script>"; break; }
$new=$_GET['new'];
if(($new<1)&&($new>3)){ echo "<script>self.location='?p=home'</script>"; break; }
if($new==1){
	mysql_query("UPDATE jutsus SET jutsu=14 WHERE usuarioid=".$db['id']." AND jutsu=17 OR usuarioid=".$db['id']." AND jutsu=20");
	mysql_query("UPDATE jutsus SET jutsu=15 WHERE usuarioid=".$db['id']." AND jutsu=18 OR usuarioid=".$db['id']." AND jutsu=21");
	mysql_query("UPDATE jutsus SET jutsu=16 WHERE usuarioid=".$db['id']." AND jutsu=19 OR usuarioid=".$db['id']." AND jutsu=22");
}
if($new==2){
	mysql_query("UPDATE jutsus SET jutsu=17 WHERE usuarioid=".$db['id']." AND jutsu=14 OR usuarioid=".$db['id']." AND jutsu=20");
	mysql_query("UPDATE jutsus SET jutsu=18 WHERE usuarioid=".$db['id']." AND jutsu=15 OR usuarioid=".$db['id']." AND jutsu=21");
	mysql_query("UPDATE jutsus SET jutsu=19 WHERE usuarioid=".$db['id']." AND jutsu=16 OR usuarioid=".$db['id']." AND jutsu=22");
}
if($new==3){
	mysql_query("UPDATE jutsus SET jutsu=20 WHERE usuarioid=".$db['id']." AND jutsu=14 OR usuarioid=".$db['id']." AND jutsu=17");
	mysql_query("UPDATE jutsus SET jutsu=21 WHERE usuarioid=".$db['id']." AND jutsu=15 OR usuarioid=".$db['id']." AND jutsu=18");
	mysql_query("UPDATE jutsus SET jutsu=22 WHERE usuarioid=".$db['id']." AND jutsu=16 OR usuarioid=".$db['id']." AND jutsu=19");
}
mysql_query("UPDATE usuarios SET doujutsu=".$new." WHERE id=".$db['id']);
echo "<script>self.location='?p=home'</script>";
?>