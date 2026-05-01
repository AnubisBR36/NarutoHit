<?php
$atual=date('Y-m-d H:i:s');
$hoje=date('Y-m-d');

// Resetar contador de cancelamentos se for um novo dia
if(isset($db['hunt_cancels_date']) && $db['hunt_cancels_date'] != $hoje) {
    try {
        $stmt = $conexao->prepare("UPDATE usuarios SET hunt_cancels_today=0, hunt_cancels_date=? WHERE id=?");
        $stmt->execute([$hoje, $db['id']]);
        $db['hunt_cancels_today'] = 0;
        $db['hunt_cancels_date'] = $hoje;
    } catch (PDOException $e) {
        // Handle error silently
    }
}

// Verificar se é VIP/ADM/GM
$isVip = (date('Y-m-d H:i:s') < $db['vip']);
$isAdmin = (isset($db['adm']) && $db['adm'] == 1) || (isset($db['admin']) && $db['admin'] == 1);
$canCancel = $isVip || $isAdmin;
$cancelLimit = $isAdmin ? 999 : 3; // ADM/GM ilimitado, VIP 3x por dia
$cancelsRemaining = $cancelLimit - (int)($db['hunt_cancels_today'] ?? 0);

// Processar cancelamento
if(isset($_POST['cancel_hunt']) && $canCancel && $db['hunt'] > 0) {
    if($cancelsRemaining > 0 || $isAdmin) {
        // Calcular tempo decorrido
        $hunt_inicio = isset($db['hunt_inicio']) ? strtotime($db['hunt_inicio']) : (strtotime($db['hunt_fim']) - ($db['hunt_duracao'] ?? 600));
        $tempo_total = strtotime($db['hunt_fim']) - $hunt_inicio;
        $tempo_decorrido = strtotime($atual) - $hunt_inicio;
        
        if($tempo_total <= 0) $tempo_total = 600; // fallback 10 minutos
        if($tempo_decorrido < 0) $tempo_decorrido = 0;
        if($tempo_decorrido > $tempo_total) $tempo_decorrido = $tempo_total;
        
        $proporcao = $tempo_decorrido / $tempo_total;
        
        // Calcular recompensas proporcionais (baseado no rewardhunt.php)
        $exp_base = rand(2, 10);
        switch($exp_base){
            case 2: $yens_base = rand(160,240); break;
            case 3: $yens_base = rand(241,330); break;
            case 4: $yens_base = rand(331,420); break;
            case 5: $yens_base = rand(421,510); break;
            case 6: $yens_base = rand(511,600); break;
            case 7: $yens_base = rand(601,690); break;
            case 8: $yens_base = rand(691,780); break;
            case 9: $yens_base = rand(781,870); break;
            case 10: $yens_base = rand(871,960); break;
            default: $yens_base = rand(160,240); break;
        }
        
        // Aplicar proporção
        $exp = floor($exp_base * $proporcao);
        $yens = floor($yens_base * $proporcao);
        
        // Bonus VIP
        if($isVip) $bonus = floor(3 * $proporcao); else $bonus = 0;
        $exp_total = $exp + $bonus;
        
        // Atualizar banco de dados
        try {
            $stmt = $conexao->prepare("UPDATE usuarios SET hunt=0, hunt_fim=NULL, yens=yens+?, yens_fat=yens_fat+?, exp=exp+?, exptotal=exptotal+?, hunt_cancels_today=hunt_cancels_today+1, hunt_cancels_date=? WHERE id=?");
            $stmt->execute([$yens, $yens, $exp_total, $exp_total, $hoje, $db['id']]);
            
            // Redirecionar para página de resultado
            $_SESSION['hunt_cancel_result'] = [
                'exp' => $exp,
                'yens' => $yens,
                'bonus' => $bonus,
                'proporcao' => round($proporcao * 100)
            ];
            echo "<script>self.location='?p=cancelhunt'</script>";
            exit;
        } catch (PDOException $e) {
            $cancelError = "Erro ao cancelar a caça.";
        }
    } else {
        $cancelError = "Você já usou seus 3 cancelamentos diários.";
    }
}

if($db['hunt']>0){
        if($atual<$db['hunt_fim']){
                $fim=$db['hunt_fim'];
                // Calcular diferença de tempo usando PHP 
                $fim_timestamp = strtotime($fim);
                $atual_timestamp = strtotime($atual);
                $diff_seconds = $fim_timestamp - $atual_timestamp;
                if($diff_seconds < 0) $diff_seconds = 0;
                $hours = floor($diff_seconds / 3600);
                $minutes = floor(($diff_seconds % 3600) / 60);
                $seconds = $diff_seconds % 60;
                $fim = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                $msgconc='Sua caça foi concluída! Clique <a href="?p=rewardhunt">aqui</a> para receber as recompensas!';
                $msg='Você está em uma caça neste momento.<br />Faltam <b><span id="hunt_tempo">'.$fim.'</span></b> para terminar a caça.';
        } else $msgconc='Sua caça foi concluída! Clique <a href="?p=rewardhunt">aqui</a> para receber as recompensas!';
} else { echo "<script>self.location='?p=home'</script>"; exit; }
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
        if (s < 00){ s = 59;    m--; }
        if (m < 00){ m = 59;    h--; };
        s = new String(s); if (s.length < 2) s = "0" + s;
        m = new String(m); if (m.length < 2) m = "0" + m;
        h = new String(h); if (h.length < 2) h = "0" + h;
        
        var temp = h + ":" + m + ":" + s;
        
        document.getElementById(div).innerHTML = temp;
        document.getElementById(div).value = temp;
        atualiza(div,divtotal);
        document.title='['+temp+'] :: <?php echo nome_servidor(); ?> - mesmo nome, nova história! ::';
        }
}
<?php if($atual<$db['hunt_fim']) echo "window.setInterval('calculafim(\"hunt_tempo\",\"mensagem\")',1000);"; ?>
function atualiza(div,divtotal){
        if((document.getElementById(div).value) < "00:00:01"){
                self.location="?p=rewardhunt";
                conc=1;
        }
}
function confirmCancel() {
    return confirm('Tem certeza que deseja cancelar a caça? Você receberá recompensas proporcionais ao tempo decorrido.');
}
</script>
<div class="box_top">Ocupado</div>
<div class="box_middle">
<div class="aviso" id="mensagem">
        <?php
        if(($atual<$db['hunt_fim']&&($db['hunt']>0)))
                echo $msg;
        else
                echo $msgconc;
        ?>
</div>
<?php if(isset($cancelError)): ?>
<div class="sep"></div>
<div class="aviso" style="color:#ff6666;"><?php echo $cancelError; ?></div>
<?php endif; ?>
<?php if($canCancel && $atual<$db['hunt_fim'] && $db['hunt']>0): ?>
<div class="sep"></div>
<div style="text-align:center; padding:10px;">
    <form method="post" action="?p=busyhunt" onsubmit="return confirmCancel();">
        <input type="hidden" name="cancel_hunt" value="1" />
        <input type="submit" class="botao" value="Cancelar Caça" style="background:#663333;" />
    </form>
    <div class="sub2" style="margin-top:5px;">
        <?php if($isAdmin): ?>
            <span style="color:#FFD700;">ADM/GM - Cancelamentos ilimitados</span>
        <?php else: ?>
            Cancelamentos restantes hoje: <b><?php echo max(0, $cancelsRemaining); ?>/3</b>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
</div>
<div class="box_bottom"></div>
