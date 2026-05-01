<div class="box_top">Configurações</div>
<div class="box_middle">Utilize esta página para configurar sua conta.<div class="sep"></div>
	<div align="center"><a href="?p=config">Início</a> | <a href="?p=config&amp;type=pass">Senha</a> | <a href="?p=config&amp;type=batt">Batalha</a> | <a href="?p=config&amp;type=conn">Conectividade</a> | <a href="?p=config&amp;type=char">Personagem</a> | <a href="?p=config&amp;type=avat">Avatar</a></div>
    <div class="sep"></div>
    <?php
	if(!isset($_GET['type'])) require_once('config_inicial.php'); else {
		switch($_GET['type']){
			case 'pass': require_once('config_pass.php'); break;
			case 'batt': require_once('config_batt.php'); break;
			case 'conn': require_once('config_conn.php'); break;
			case 'char': require_once('config_char.php'); break;
			case 'avat': require_once('config_avat.php'); break;
		}
	}
	?>
</div>
<div class="box_bottom"></div>