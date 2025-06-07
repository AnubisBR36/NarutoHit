<?php
$atual=date('Y-m-d H:i:s');
if($db['missao']==0){
	echo "<script>self.location='?p=missions'</script>"; exit;
}
if(($db['missao']==999)&&($atual<$db['missao_fim'])){ echo "<script>self.location='?p=logout'</script>"; exit; }
if($db['missao']>0){
	if($atual<$db['missao_fim']){
		$timestamp_fim = strtotime($db['missao_fim']);
		$timestamp_atual = strtotime($atual);
		$segundos_restantes = $timestamp_fim - $timestamp_atual;

		$horas = floor($segundos_restantes / 3600);
		$minutos = floor(($segundos_restantes % 3600) / 60);
		$segundos = $segundos_restantes % 60;

		$tempo_formatado = sprintf("%02d:%02d:%02d", $horas, $minutos, $segundos);

		$msgconc='Sua missão foi concluída! Clique <a href="?p=rewardmission">aqui</a> para receber as recompensas!';
		$msg='Você está realizando uma missão neste momento.<br />Faltam <b><span id="missao_tempo">'.$tempo_formatado.'</span></b> para terminar a missão.';
	} else $msgconc='Sua missão foi concluída! Clique <a href="?p=rewardmission">aqui</a> para receber as recompensas!';
} else { echo "<script>self.location='?p=home'</script>"; exit; }
?>
<div class="box_top">Missão em Andamento</div>
<div class="box_middle">
	<div align="center">
		<img src="_img/missoes/on.png" alt="Missão em andamento" /><br />
		<b>Você está realizando uma missão!</b><br />
		<span class="sub2">Tempo restante: <span id="tempo_restante"></span></span><br /><br />
		<div class="sep"></div>
		<div align="center">Um ninja precisa conhecer seus limites. Você pode cancelar a missão a qualquer momento, caso necessite, clicando no botão abaixo. Lembre-se que ao cancelar, você não ganhará nada como recompensa, pois será considerada como uma missão falha.</div>
		<div class="sep"></div>
		<a href="#" onclick="return confirmarCancelamento();" class="botao">Cancelar Missão</a>
	</div>
</div>
<div class="box_bottom"></div>
<script>
function confirmarCancelamento() {
    // Determinar rank e imagem baseado na missão atual
    var rank = '';
    var imagemRank = '';
    var yensPorHora = 0;

    <?php
    switch($db['missao']) {
        case 905: echo "rank = 'S'; imagemRank = 's'; yensPorHora = 3000;"; break;
        case 904: echo "rank = 'A'; imagemRank = 'a'; yensPorHora = 1800;"; break;
        case 903: echo "rank = 'B'; imagemRank = 'b'; yensPorHora = 1000;"; break;
        case 902: echo "rank = 'C'; imagemRank = 'c'; yensPorHora = 550;"; break;
        case 901: 
        default: echo "rank = 'D'; imagemRank = 'd'; yensPorHora = 250;"; break;
    }

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

    echo "var tempoGastoMinutos = " . $tempo_gasto_minutos . ";";
    ?>

    // Calcular recompensas e mensagens
    var mensagemRecompensa = '';
    var corMensagem = '#8B0000';

    if (tempoGastoMinutos >= 10) {
        // Usar o mesmo cálculo que o backend
        var tempoGastoHoras = tempoGastoMinutos / 60;
        var yensCalculados = Math.floor(yensPorHora * tempoGastoHoras);
        var expCalculada = Math.floor(tempoGastoHoras);

        // Garantir que ganhe pelo menos alguma coisa se ficou mais de 10 minutos
        if (yensCalculados < 1) yensCalculados = Math.floor(yensPorHora / 6); // 10 minutos = 1/6 de hora
        if (expCalculada < 1) expCalculada = 1;

        mensagemRecompensa = 'Você receberá:<br/>💰 ' + yensCalculados.toLocaleString('pt-BR') + ' Yens<br/>⭐ ' + expCalculada + ' ponto' + (expCalculada > 1 ? 's' : '') + ' de experiência';
        corMensagem = '#006400';
    } else {
        mensagemRecompensa = 'Você não receberá recompensas (mínimo 10 minutos).';
        corMensagem = '#8B0000';
    }

    // Remove modal existente se houver
    var modalExistente = document.getElementById('modalCancelamento');
    if (modalExistente) {
        modalExistente.remove();
    }

    // Criar o modal
    var modal = document.createElement('div');
    modal.id = 'modalCancelamento';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
    `;

    modal.innerHTML = `
        <div style="background: url('_img/box/box_middle.jpg'); 
                    width: 450px; 
                    border: 3px solid #8B4513;
                    border-radius: 10px;
                    text-align: center;
                    position: relative;">
            <div style="background: url('_img/box/box_top.jpg'); 
                        height: 30px; 
                        color: white; 
                        font-weight: bold; 
                        padding-top: 8px;
                        border-radius: 7px 7px 0 0;">
                Cancelamento de Missão
            </div>
            <div style="padding: 20px; color: #8B0000;">
                <img src="_img/missoes/rank${imagemRank}.jpg" alt="Rank ${rank}" style="width: 80px; height: 80px; margin-bottom: 15px;" />
                <br />
                <b style="font-size: 16px; color: #8B0000; text-shadow: 1px 1px 0px #000, -1px -1px 0px #000, 1px -1px 0px #000, -1px 1px 0px #000;">Missão Rank ${rank}</b>
                <br /><br />
                <div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 5px; margin: 10px 0;">
                    <span style="font-size: 14px; color: ${corMensagem}; text-shadow: 1px 1px 0px #000, -1px -1px 0px #000, 1px -1px 0px #000, -1px 1px 0px #000; line-height: 1.4;">
                        ${mensagemRecompensa}
                    </span>
                </div>
                <div style="margin-top: 15px;">
                    <input type="button" 
                           onclick="confirmarCancelar()" 
                           value="Confirmar Cancelamento" 
                           style="background: url('_img/fundo_botao.jpg'); 
                                  border: 2px solid #654321; 
                                  color: white; 
                                  font-weight: bold; 
                                  padding: 8px 15px; 
                                  margin: 5px; 
                                  cursor: pointer;
                                  border-radius: 5px;" />
                    <input type="button" 
                           onclick="fecharModalCancelamento()" 
                           value="Continuar na Missão" 
                           style="background: url('_img/fundo_botao.jpg'); 
                                  border: 2px solid #654321; 
                                  color: white; 
                                  font-weight: bold; 
                                  padding: 8px 15px; 
                                  margin: 5px; 
                                  cursor: pointer;
                                  border-radius: 5px;" />
                </div>
            </div>
            <div style="background: url('_img/box/box_bottom.jpg'); 
                        height: 15px; 
                        border-radius: 0 0 7px 7px;">
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Função para confirmar cancelamento
    window.confirmarCancelar = function() {
        window.location.href = '?p=missions&cancel=1';
    };

    // Função para fechar modal
    window.fecharModalCancelamento = function() {
        var modal = document.getElementById('modalCancelamento');
        if (modal) {
            modal.remove();
        }
    };

    // Fechar modal ao clicar fora
    modal.onclick = function(e) {
        if (e.target === modal) {
            fecharModalCancelamento();
        }
    };

    return false; // Impede a navegação padrão
}

function atualizarTempo() {
	var agora = new Date();
	var fim = new Date('<?php echo date('c', strtotime($db['missao_fim'])); ?>'); // Formato ISO 8601 para melhor compatibilidade
	var diff = fim - agora;

	if (diff > 0) {
		var horas = Math.floor(diff / (1000 * 60 * 60));
		var minutos = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
		var segundos = Math.floor((diff % (1000 * 60)) / 1000);

		// Garantir que sempre tenham 2 dígitos
		horas = horas < 10 ? "0" + horas : horas;
		minutos = minutos < 10 ? "0" + minutos : minutos;
		segundos = segundos < 10 ? "0" + segundos : segundos;

		document.getElementById('tempo_restante').innerHTML = 
			horas + ':' + minutos + ':' + segundos;

		// Atualizar título da página
		document.title = '[' + horas + ':' + minutos + ':' + segundos + '] :: narutoHIT - mesmo nome, nova história! ::';

		setTimeout(atualizarTempo, 1000);
	} else {
		document.getElementById('tempo_restante').innerHTML = 'Concluída!';
		document.title = 'narutoHIT - mesmo nome, nova história!';
		setTimeout(function() {
			window.location.href = '?p=rewardmission';
		}, 2000);
	}
}

// Iniciar o contador apenas se a missão ainda não terminou
<?php if($atual < $db['missao_fim']): ?>
atualizarTempo();
<?php else: ?>
document.getElementById('tempo_restante').innerHTML = 'Concluída!';
setTimeout(function() {
	window.location.href = '?p=rewardmission';
}, 2000);
<?php endif; ?>
</script>