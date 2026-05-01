<?php
if(date('Y-m-d H:i:s')>=$db['vip']){ echo "<script>self.location='?p=home'</script>"; return; }
if(!isset($_GET['new'])){ echo "<script>self.location='?p=home'</script>"; return; }
$new=(int)$_GET['new'];
if(($new<1)||($new>3)){ echo "<script>self.location='?p=home'</script>"; return; }
if($new==1){
    $stmt=$conexao->prepare("UPDATE jutsus SET jutsu=14 WHERE usuarioid=? AND jutsu=17 OR usuarioid=? AND jutsu=20"); $stmt->execute([$db['id'],$db['id']]);
    $stmt=$conexao->prepare("UPDATE jutsus SET jutsu=15 WHERE usuarioid=? AND jutsu=18 OR usuarioid=? AND jutsu=21"); $stmt->execute([$db['id'],$db['id']]);
    $stmt=$conexao->prepare("UPDATE jutsus SET jutsu=16 WHERE usuarioid=? AND jutsu=19 OR usuarioid=? AND jutsu=22"); $stmt->execute([$db['id'],$db['id']]);
}
if($new==2){
    $stmt=$conexao->prepare("UPDATE jutsus SET jutsu=17 WHERE usuarioid=? AND jutsu=14 OR usuarioid=? AND jutsu=20"); $stmt->execute([$db['id'],$db['id']]);
    $stmt=$conexao->prepare("UPDATE jutsus SET jutsu=18 WHERE usuarioid=? AND jutsu=15 OR usuarioid=? AND jutsu=21"); $stmt->execute([$db['id'],$db['id']]);
    $stmt=$conexao->prepare("UPDATE jutsus SET jutsu=19 WHERE usuarioid=? AND jutsu=16 OR usuarioid=? AND jutsu=22"); $stmt->execute([$db['id'],$db['id']]);
}
if($new==3){
    $stmt=$conexao->prepare("UPDATE jutsus SET jutsu=20 WHERE usuarioid=? AND jutsu=14 OR usuarioid=? AND jutsu=17"); $stmt->execute([$db['id'],$db['id']]);
    $stmt=$conexao->prepare("UPDATE jutsus SET jutsu=21 WHERE usuarioid=? AND jutsu=15 OR usuarioid=? AND jutsu=18"); $stmt->execute([$db['id'],$db['id']]);
    $stmt=$conexao->prepare("UPDATE jutsus SET jutsu=22 WHERE usuarioid=? AND jutsu=16 OR usuarioid=? AND jutsu=19"); $stmt->execute([$db['id'],$db['id']]);
}
$stmt=$conexao->prepare("UPDATE usuarios SET doujutsu=? WHERE id=?");
$stmt->execute([$new,$db['id']]);
echo "<script>self.location='?p=home'</script>";
?>
