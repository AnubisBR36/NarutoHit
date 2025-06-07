<?php
if(date('Y-m-d H:i:s')>='2010-02-10 00:00:00'){ echo "<script>self.location='?p=home&e=1'</script>"; break; }
if(!isset($_GET['akatsuki'])){ echo "<script>self.location='?p=home&e=1'</script>"; break; }
if($db['vila']==8){ echo "<script>self.location='?p=home&e=3'</script>"; break; }
if($_GET['akatsuki']=='yes') mysql_query("UPDATE usuarios SET config_vila=0, vila=8, renegado='sim' WHERE id=".$db['id']);
if($_GET['akatsuki']=='no') mysql_query("UPDATE usuarios SET config_vila=0, vila=8, renegado='nao' WHERE id=".$db['id']);
echo "<script>self.location='?p=config&msg=2'</script>";
?>