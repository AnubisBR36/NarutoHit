<?php
$sqls=mysql_query("SELECT count(id) conta, pessoal_sexo FROM usuarios GROUP BY pessoal_sexo ORDER BY pessoal_sexo DESC");
$dbs=mysql_fetch_assoc($sqls);
?>