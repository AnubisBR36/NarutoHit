<?php
$inicio='2010-04-04 18:00:00';
$fim='2010-04-06 08:00:00';
$atual=date('Y-m-d H:i:s');
if(($atual>=$inicio)&&($atual<$fim)){ echo "<script>self.location='preparing.php'</script>"; exit; }
?>