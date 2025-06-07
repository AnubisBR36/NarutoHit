<?php
if(isset($_GET['cancel'])){
	mysql_query("UPDATE usuarios SET treino=0, treino_fim='0000-00-00 00:00:00' WHERE id=".$db['id']);
	$db['treino']=0;
	$db['treino_fim']='0000-00-00 00:00:00';
}
?>
<?php require_once('verificar.php'); ?>
<?php
// Verificar se a tabela 'salas' existe, se não, criar (SQLite compatível)
$check_table = mysql_query("SELECT name FROM sqlite_master WHERE type='table' AND name='salas'");
if(mysql_num_rows($check_table) == 0) {
    // Criar tabela salas se não existir (SQLite compatível)
    $create_table = "CREATE TABLE IF NOT EXISTS salas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuarioid INTEGER NOT NULL DEFAULT 0,
        fim TEXT NOT NULL DEFAULT '0000-00-00 00:00:00'
    )";
    mysql_query($create_table);

    // Inserir salas padrão se não existirem
    for($i = 1; $i <= 5; $i++) {
        $check_existing = mysql_query("SELECT id FROM salas WHERE id = $i");
        if(mysql_num_rows($check_existing) == 0) {
            mysql_query("INSERT INTO salas (id, usuarioid, fim) VALUES ($i, 0, '0000-00-00 00:00:00')");
        }
    }
}

$sqls=mysql_query("SELECT s.*,u.usuario FROM salas s LEFT OUTER JOIN usuarios u ON s.usuarioid=u.id ORDER BY s.id ASC");
if(!$sqls) {
    echo '<div class="box_top">Erro</div>';
    echo '<div class="box_middle">Erro ao carregar informações das salas. Erro na consulta: ' . mysql_error() . '</div>';
    echo '<div class="box_bottom"></div>';
    return;
}

$dbs=mysql_fetch_assoc($sqls);
// Verificar se há pelo menos uma sala
if(!$dbs) {
    // Se não há salas, criar salas padrão
    for($i = 1; $i <= 5; $i++) {
        mysql_query("INSERT INTO salas (usuarioid, fim) VALUES (0, '0000-00-00 00:00:00')");
    }
    // Tentar novamente
    mysql_free_result($sqls);
    $sqls=mysql_query("SELECT s.*,u.usuario FROM salas s LEFT OUTER JOIN usuarios u ON s.usuarioid=u.id ORDER BY s.id ASC");
    $dbs=mysql_fetch_assoc($sqls);
}
?>
<?php
$atual=date('Y-m-d H:i:s');
$sqlv=mysql_query("SELECT * FROM salas WHERE usuarioid=".$db['id']." AND fim>'$atual'");
if(!$sqlv) {
    echo '<div class="box_top">Erro</div>';
    echo '<div class="box_middle">Erro ao verificar reservas de sala. Erro na consulta: ' . mysql_error() . '</div>';
    echo '<div class="box_bottom"></div>';
    return;
}
$dbv=mysql_fetch_assoc($sqlv);
if(mysql_num_rows($sqlv)==0){
?>
<div class="box_top">Escola Ninja</div>
<div class="box_middle">Bem-vindo à Escola Ninja! Aqui você poderá aprender novos jutsus, descobrir a natureza do seu chakra, praticar jutsus, entre outros. Escolha uma das salas disponíveis abaixo para usar. Haverá um sensei a sua espera!<br /><b>OBS: Você só poderá ficar na sala em um tempo máximo de 5 minutos.</b><div class="sep"></div>
	<div align="center">
    <div class="aviso"><?php if(isset($_GET['cancel'])) echo 'Treino cancelado!<div class="sep"></div>'; ?><b>Salas disponíveis: <span id="disp"></span> de <?php echo mysql_num_rows($sqls); ?></b></div>
    <div class="sep"></div>
    <table width="100%" border="0" cellpadding="0" cellspacing="1">
      <?php $i=1; $d=0; do{ if(($dbs['usuarioid']==0)or($atual>$dbs['fim'])) $d++; ?>
      <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
        <td>Sala <?php echo $i; ?></td>
        <td><?php if(($dbs['usuarioid']==0)or($atual>$dbs['fim'])) echo 'Ninguém'; else echo '<a href="?p=view&view='.$dbs['usuario'].'">'.$dbs['usuario'].'</a>'; ?></td>
        <td><?php if(($dbs['usuarioid']==0)or($atual>$dbs['fim'])) echo '<span style="color:#33CC00">Disponível</a>'; else echo '<span style="color:#FF9900">Ocupada</span>'; ?></td>
        <td><?php if(($dbs['usuarioid']==0)or($atual>$dbs['fim'])){ ?><a href="?p=room&amp;id=<?php echo $dbs['id']; ?>">Entrar</a><?php } ?></td>
      </tr>
      <?php $i++; } while($dbs=mysql_fetch_assoc($sqls)); ?>
    </table>
    </div>
    <script>document.getElementById('disp').innerHTML='<?php echo $d; ?>';</script>
</div>
<div class="box_bottom"></div>
<?php } else { ?>
<?php
$atual=date('Y-m-d H:i:s');
// Verificar se $dbv é um array válido antes de acessar suas propriedades
if($dbv && is_array($dbv) && isset($dbv['usuarioid']) && $dbv['usuarioid']>0){
	if(isset($dbv['fim']) && $atual<$dbv['fim']){
		$fim=$dbv['fim'];
		$sqltempo_query=mysql_query("SELECT timediff('$fim','$atual') as fim");
		if($sqltempo_query) {
			$sqltempo=mysql_fetch_assoc($sqltempo_query);
			if($sqltempo && isset($sqltempo['fim'])) {
				$fim=$sqltempo['fim'];
				$msg='Você já está com uma sala reservada, e ainda tem <b><span id="sala_tempo" style="color:#FFFFFF">'.$fim.'</span></b> restando.';
			}
		}
	}
}
?>
<script language="javascript" type="text/javascript">
var conc=0;
function calculafim(div,divtotal){
	if(conc==0){
	var navegador=navigator.appName;
	var tmp = document.getElementById(div).innerHTML.split(":");
	var s = tmp[2];
	var m = tmp[1];
	var h = tmp[0];
	s--;
	if (s < 00){ s = 59;	m--; }
	if (m < 00){ m = 59;	h--; };
	s = new String(s); if (s.length < 2) s = "0" + s;
	m = new String(m); if (m.length < 2) m = "0" + m;
	h = new String(h); if (h.length < 2) h = "0" + h;

	var temp = h + ":" + m + ":" + s;

	document.getElementById(div).innerHTML = temp;
	document.getElementById(div).value = temp;
	atualiza(div,divtotal);
	}
}
<?php if($dbv && is_array($dbv) && isset($dbv['fim']) && $atual<$dbv['fim']) echo "window.setInterval('calculafim(\"sala_tempo\",\"mensagem\")',1000);"; ?>
function atualiza(div,divtotal){
  	if((document.getElementById(div).value) < "00:00:01"){
  		self.location="?p=school";
  		conc=1;
	}
}
</script>
<div class="box_top">Escola Ninja</div>
<div class="box_middle"><div class="aviso" id="mensagem"><?php
	if($dbv && is_array($dbv) && isset($dbv['fim']) && $atual<$dbv['fim'])
		echo isset($msg) ? $msg : 'Você tem uma sala reservada.'; 
	else
		echo isset($msgconc) ? $msgconc : 'Sua reserva expirou.';
	?></div>
    <div class="sep"></div>
    <div align="center">Você pode retornar para a sala ou cancelar seu aprendizado a qualquer momento, utilizando os botões abaixo.<div class="sep"></div><input type="button" class="botao" value="Retornar para a Sala" onclick="location.href='?p=room&id=<?php echo ($dbv && isset($dbv['id'])) ? $dbv['id'] : ''; ?>'" />&nbsp;<input type="button" class="botao" value="Cancelar" onclick="self.location='?p=room&leave=true'" /></div>
</div>
<div class="box_bottom"></div>
<?php } ?>
<?php
@mysql_free_result($sqls);
@mysql_free_result($sqlv);
?>