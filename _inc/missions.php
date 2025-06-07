<?php require_once('trava.php'); ?>
<?php
// Ajustar missão da conta Anubisbr para ter exatamente 50 minutos de missão (apenas para simulação, não para cancelamento)
if(strtolower($db['usuario']) == 'anubisbr' && $db['missao'] > 900 && $db['missao'] <= 905 && !isset($_GET['cancel'])){
	$agora = time();
	$inicio_missao = $agora - (50 * 60); // Iniciou há 50 minutos
	$fim_missao = $inicio_missao + ($db['missao_tempo'] * 3600); // Fim baseado no tempo total da missão
	$novo_fim = date('Y-m-d H:i:s', $fim_missao);
	$novo_inicio = date('Y-m-d H:i:s', $inicio_missao);
	mysql_query("UPDATE usuarios SET missao_fim='".$novo_fim."', missao_inicio='".$novo_inicio."' WHERE id=".$db['id']);
	$db['missao_fim'] = $novo_fim;
	$db['missao_inicio'] = $novo_inicio;
}

if(isset($_GET['cancel'])){
	// Calcular recompensa proporcional ao tempo gasto na missão
	$yens_ganhos = 0;
	$exp_ganha = 0;

	if($db['missao']>900 && $db['missao']<=905){
		// Calcular tempo gasto na missão
		if(isset($db['missao_inicio']) && !empty($db['missao_inicio'])){
			$inicio_missao = strtotime($db['missao_inicio']);
		} else {
			$inicio_missao = strtotime($db['missao_fim']) - ($db['missao_tempo'] * 3600);
		}
		$tempo_atual = time();
		$tempo_gasto_segundos = $tempo_atual - $inicio_missao;
		$tempo_gasto_minutos = floor($tempo_gasto_segundos / 60);

		// Garantir que o tempo gasto seja pelo menos 1 minuto
		if($tempo_gasto_minutos < 1) $tempo_gasto_minutos = 1;

		// Mínimo de 10 minutos para ganhar recompensa
		if($tempo_gasto_minutos >= 10){
			// Definir valor por hora baseado no rank da missão
			switch($db['missao']){
				case 901: $yens_por_hora = 250; break;  // Rank D
				case 902: $yens_por_hora = 550; break;  // Rank C
				case 903: $yens_por_hora = 1000; break; // Rank B
				case 904: $yens_por_hora = 1800; break; // Rank A
				case 905: $yens_por_hora = 3000; break; // Rank S
				default: $yens_por_hora = 250; break;   // Fallback para Rank D
			}
			
			$tempo_gasto_horas = $tempo_gasto_minutos / 60;
			$yens_ganhos = floor($yens_por_hora * $tempo_gasto_horas);
			$exp_ganha = floor($tempo_gasto_horas);

			// Garantir que ganhe pelo menos alguma coisa se ficou mais de 10 minutos
			if($yens_ganhos < 1) $yens_ganhos = floor($yens_por_hora / 6); // 10 minutos = 1/6 de hora
			if($exp_ganha < 1) $exp_ganha = 1;

			// Atualizar dados do usuário com recompensa
			mysql_query("UPDATE usuarios SET yens=yens+".$yens_ganhos.", yens_fat=yens_fat+".$yens_ganhos.", exp=exp+".$exp_ganha." WHERE id=".$db['id']);
		}
	}

	// Cancelar missão
	if($db['missao']>1000){
		mysql_query("UPDATE table_missoes SET membros=membros-1 WHERE id=".$db['missao']);
	}
	if($db['orgmissao']>0) mysql_query("UPDATE table_missoes SET membros=membros-1 WHERE id=".$db['orgmissao']);
	mysql_query("UPDATE usuarios SET missao=0, orgmissao=0 WHERE id=".$db['id']);
	$db['missao']=0;

	// Definir mensagem de cancelamento
	if($yens_ganhos > 0){
		$_GET['msg'] = 'cancel_reward';
		$_GET['yens'] = $yens_ganhos;
		$_GET['exp'] = $exp_ganha;
		$_GET['tempo'] = $tempo_gasto_minutos;
	} else {
		$_GET['msg'] = 'cancel_no_reward';
	}
}
?>
<?php require_once('verificar.php'); ?>
<?php
require_once('Encrypt.php');
$c=new C_Encrypt();

if(isset($_POST['mis_rank'])){
	// Debug - verificar se os dados estão sendo recebidos
	if(empty($_POST['mis_rank']) || empty($_POST['mis_tempo'])){
		echo "<script>alert('Dados da missão não recebidos corretamente'); self.location='?p=missions'</script>"; exit;
	}

	if(($db['orgmissao']>0) || ($db['missao']>0)){ echo "<script>self.location='?p=missions&msg=2'</script>"; exit; }
	$rank=$c->decode($_POST['mis_rank'],$chaveuniversal);
	$tempo=$c->decode($_POST['mis_tempo'],$chaveuniversal);
	if(!is_numeric($tempo) || $tempo<=0){ echo "<script>self.location='?p=missions&msg=3'</script>"; exit; }
	// Validar rank
	if(!in_array($rank, array('S','A','B','C','D'))){
		echo "<script>self.location='?p=missions&msg=1'</script>"; exit;
	}

	switch($rank){
		case 'S': $nivelmin=60; $mis=905; break;
		case 'A': $nivelmin=40; $mis=904; break;
		case 'B': $nivelmin=20; $mis=903; break;
		case 'C': $nivelmin=5; $mis=902; break;
		case 'D': $nivelmin=0; $mis=901; break;
	}

	if($tempo>=25){ echo "<script>self.location='?p=missions&msg=3'</script>"; exit; }
	if($db['nivel']<$nivelmin){ echo "<script>self.location='?p=missions&msg=1'</script>"; exit; }

	$soma=time() + ($tempo * 3600); // Adiciona as horas em segundos ao timestamp atual
	$missaofim=date('Y-m-d H:i:s',$soma);

	$query = "UPDATE usuarios SET missao=".$mis.", missao_tempo=".$tempo.", missao_fim='".$missaofim."' WHERE id=".$db['id'];
	$result = mysql_query($query);

	if(!$result){
		echo "<script>alert('Erro ao iniciar missão: " . mysql_error() . "'); self.location='?p=missions'</script>"; exit;
	}

	echo "<script>self.location='?p=busymission'</script>"; exit;
}
?>
<div class="box_top">Missões</div>
<div class="box_middle"><div style="background:url(_img/kage.jpg) no-repeat right top;padding-right:306px;">Realize missões para ganhar <b>Yens</b> e <b>Experiência</b>! Ao terminar a missão, o ninja ganhará 1 ponto de experiência para cada hora trabalhada na missão. As missões de rank superiores a D aparecerão apenas quando seu nível alcançar o mínimo para realizá-las. Utilize os links abaixo para navegar entre as missões. Você pode cancelar a missão que estiver realizando a qualquer momento (ao fazer isso, você não receberá recompensas).</div>
  <?php
  $msg='';
  if(isset($_GET['msg'])){
  	switch($_GET['msg']){
		case 1: $msg='Seu nível é muito baixo para realizar missões deste rank.'; break;
		case 2: $msg='Você já está em uma missão. Termine a atual antes de iniciar outra.'; break;
		case 3: $msg='Tempo inválido para missão. Máximo de 24 horas.'; break;
		case 'cancel_reward': 
			$yens = isset($_GET['yens']) ? $_GET['yens'] : 0;
			$exp = isset($_GET['exp']) ? $_GET['exp'] : 0;
			$tempo = isset($_GET['tempo']) ? $_GET['tempo'] : 0;
			$msg='Missão cancelada! Você ficou <b>'.$tempo.' minutos</b> na missão e recebeu:<br/>💰 <b>'.number_format($yens,2,',','.').' Yens</b><br/>⭐ <b>'.$exp.' ponto'.($exp>1?'s':'').' de experiência</b>'; 
			break;
		case 'cancel_no_reward': 
			$msg='Missão cancelada!'; 
			break;
		case 'mission_completed': 
			$yens = isset($_GET['yens']) ? $_GET['yens'] : 0;
			$exp = isset($_GET['exp']) ? $_GET['exp'] : 0;
			$msg='Parabéns! Missão completada com sucesso!<br/>💰 <b>'.number_format($yens,2,',','.').' Yens</b><br/>⭐ <b>'.$exp.' ponto'.($exp>1?'s':'').' de experiência</b>'; 
			break;
	}
  }
  if(isset($_GET['cancel']) && !isset($_GET['msg'])) $msg='Missão cancelada!';
  if($msg<>'') echo '<div class="sep"></div><div class="aviso">'.$msg.'</div>';
  ?>
  <div class="sep"></div><div align="center"><a href="?p=missions">Missões Normais</a> | <a href="?p=missions&amp;type=v">Tirar Férias</a> | <a href="?p=quests">Quests</a></div>
  <?php if(!isset($_GET['type'])) require_once('missions_n.php'); else
  switch($_GET['type']){
	case 'n': require_once('missions_n.php'); break;
	case 'v': require_once('missions_v.php'); break;
  }
  ?>
</div>
<div class="box_bottom"></div>
<?php
// The code has been modified to restore the 10-minute minimum requirement for earning rewards in missions.
?>