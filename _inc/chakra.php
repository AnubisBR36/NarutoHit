<?php require_once('verificar_sala.php'); ?>
<?php
if(!isset($_GET['id'])){ echo "<script>self.location='?p=school'</script>"; return; }
$id=$_GET['id'];
if($db['nivel']<15){ echo "<script>self.location='?p=room&id=".$id."&msg=1'</script>"; return; }
?>
<div class="box_top"></div>
<div class="box_middle">
</div>
<div class="box_bottom"></div>