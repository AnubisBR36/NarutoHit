<?php require_once('trava.php'); ?>
<?php
require_once('Encrypt.php');
$c = new C_Encrypt();

if($db['orgid'] <= 0){
    echo "<script>self.location='?p=home'</script>"; exit;
}

// Tabela mestre das 14 missões de clã [codigo, nivel_min, yens_hr, equip%, cristal%, label, img]
$ORG_MIS = [
    911 => [911,  0,  100,  3,  5, 'Missão I',    '_img/MClan/52.jpg'],
    912 => [912,  5,  180,  4,  6, 'Missão II',   '_img/MClan/53.jpg'],
    913 => [913, 10,  280,  5,  7, 'Missão III',  '_img/MClan/54.jpg'],
    914 => [914, 15,  400,  6,  8, 'Missão IV',   '_img/MClan/55.jpg'],
    915 => [915, 20,  550,  7,  9, 'Missão V',    '_img/MClan/85.jpg'],
    916 => [916, 25,  720,  8, 10, 'Missão VI',   '_img/MClan/103.jpg'],
    917 => [917, 30,  900,  9, 11, 'Missão VII',  '_img/MClan/105.jpg'],
    918 => [918, 35, 1100, 10, 13, 'Missão VIII', '_img/MClan/113.jpg'],
    919 => [919, 40, 1350, 12, 14, 'Missão IX',   '_img/MClan/160.jpg'],
    920 => [920, 45, 1600, 13, 16, 'Missão X',    '_img/MClan/164.jpg'],
    921 => [921, 50, 1900, 15, 17, 'Missão XI',   '_img/MClan/166.jpg'],
    922 => [922, 55, 2300, 16, 19, 'Missão XII',  '_img/MClan/168.jpg'],
    923 => [923, 58, 2700, 18, 20, 'Missão XIII', '_img/MClan/171.jpg'],
    924 => [924, 60, 3200, 20, 22, 'Missão XIV',  '_img/MClan/262.jpg'],
];

// Cancelar missão de org
if(isset($_GET['cancel'])){
    $yens_ganhos = 0;
    $exp_ganha   = 0;

    if($db['missao'] >= 911 && $db['missao'] <= 924 && isset($ORG_MIS[$db['missao']])){
        $m = $ORG_MIS[$db['missao']];
        if(isset($db['missao_inicio']) && !empty($db['missao_inicio'])){
            $inicio_missao = strtotime($db['missao_inicio']);
        } else {
            $inicio_missao = strtotime($db['missao_fim']) - ($db['missao_tempo'] * 3600);
        }
        $tempo_gasto_minutos = max(1, floor((time() - $inicio_missao) / 60));

        if($tempo_gasto_minutos >= 10){
            $tempo_h     = $tempo_gasto_minutos / 60;
            $yens_ganhos = max(1, floor($m[2] * $tempo_h));
            $exp_ganha   = max(1, floor($tempo_h));
            try {
                $stmt = $conexao->prepare("UPDATE usuarios SET yens=yens+?, yens_fat=yens_fat+?, exp=exp+? WHERE id=?");
                $stmt->execute([$yens_ganhos, $yens_ganhos, $exp_ganha, $db['id']]);
            } catch(PDOException $e){}
        }
    }

    try {
        $stmt = $conexao->prepare("UPDATE usuarios SET missao=0, orgmissao=0 WHERE id=?");
        $stmt->execute([$db['id']]);
    } catch(PDOException $e){}
    $db['missao'] = 0;

    if($yens_ganhos > 0){
        $_GET['msg']   = 'cancel_reward';
        $_GET['yens']  = $yens_ganhos;
        $_GET['exp']   = $exp_ganha;
        $_GET['tempo'] = $tempo_gasto_minutos;
    } else {
        $_GET['msg'] = 'cancel_no_reward';
    }
}

// Calcular cooldown de 20h
$cooldown_restante = 0;
$cooldown_fim_str  = '';
if(!empty($db['ultima_missao_clan'])){
    $ultima = strtotime($db['ultima_missao_clan']);
    $cooldown_fim = $ultima + (20 * 3600);
    if(time() < $cooldown_fim){
        $cooldown_restante = $cooldown_fim - time();
        $cooldown_fim_str  = date('d/m H:i', $cooldown_fim);
    }
}

// Iniciar missão de org
if(isset($_POST['org_code'])){
    if(($db['orgmissao'] > 0) || ($db['missao'] > 0)){
        echo "<script>self.location='?p=orgmissions&msg=2'</script>"; exit;
    }
    // Bloquear por cooldown
    if($cooldown_restante > 0){
        echo "<script>self.location='?p=orgmissions&msg=cooldown'</script>"; exit;
    }
    $code  = (int)$c->decode($_POST['org_code'],  $chaveuniversal);
    $tempo = (int)$c->decode($_POST['org_tempo'], $chaveuniversal);

    if($tempo <= 0 || $tempo >= 25){
        echo "<script>self.location='?p=orgmissions&msg=3'</script>"; exit;
    }
    if(!isset($ORG_MIS[$code])){
        echo "<script>self.location='?p=orgmissions&msg=1'</script>"; exit;
    }
    $m = $ORG_MIS[$code];
    if($db['nivel'] < $m[1]){
        echo "<script>self.location='?p=orgmissions&msg=1'</script>"; exit;
    }

    $missaofim    = date('Y-m-d H:i:s', time() + ($tempo * 3600));
    $missaoinicio = date('Y-m-d H:i:s');

    try {
        $stmt = $conexao->prepare("UPDATE usuarios SET missao=?, missao_tempo=?, missao_fim=?, missao_inicio=? WHERE id=?");
        $stmt->execute([$code, $tempo, $missaofim, $missaoinicio, $db['id']]);
    } catch(PDOException $e){
        echo "<script>alert('Erro ao iniciar missão.'); self.location='?p=orgmissions'</script>"; exit;
    }
    echo "<script>self.location='?p=busymission'</script>"; exit;
}
?>
<?php require_once('verificar.php'); ?>
<script>
function confirmarMissaoOrg(label, imgPath, yensHr, equipChance, cristalChance, form) {
    var select    = form.querySelector('select[name="org_tempo"]');
    var horas     = parseInt(select.options[select.selectedIndex].text);
    var yensTotal = yensHr * horas;
    var expTotal  = horas;

    var modalExistente = document.getElementById('modalConfOrg');
    if(modalExistente) modalExistente.remove();

    var modal = document.createElement('div');
    modal.id = 'modalConfOrg';
    modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.75);z-index:99999;display:flex;align-items:center;justify-content:center;';
    modal.innerHTML = `
        <div style="background:url('_img/box/box_middle.jpg');width:460px;border:3px solid #c07000;border-radius:10px;text-align:center;">
            <div style="background:url('_img/box/box_top.jpg');height:30px;color:#FFD700;font-weight:bold;padding-top:8px;border-radius:7px 7px 0 0;text-shadow:1px 1px 2px #000;">
                ⚔️ ${label} — Missão Secreta do Clã
            </div>
            <div style="padding:20px;">
                <img src="${imgPath}" style="width:90px;height:90px;object-fit:cover;border:2px solid #c07000;border-radius:8px;margin-bottom:12px;" />
                <br/>
                <b style="font-size:15px;color:#FFD700;text-shadow:1px 1px 0 #000,-1px -1px 0 #000;">Duração: ${horas} hora${horas>1?'s':''}</b>
                <div style="background:rgba(0,0,0,0.35);padding:12px;border-radius:6px;margin:12px 0;text-align:left;">
                    <div style="color:#FFD700;font-weight:bold;margin-bottom:6px;text-shadow:1px 1px 0 #000;">💰 Recompensas garantidas:</div>
                    <div style="color:#90EE90;text-shadow:1px 1px 0 #000;">💴 ${yensTotal.toLocaleString('pt-BR')} Yens</div>
                    <div style="color:#87CEEB;text-shadow:1px 1px 0 #000;">⭐ ${expTotal} ponto${expTotal>1?'s':''} de experiência</div>
                </div>
                <div style="background:rgba(180,80,0,0.25);padding:10px;border-radius:6px;margin:8px 0;text-align:left;border:1px solid #c07000;">
                    <div style="color:#FFD700;font-weight:bold;margin-bottom:5px;text-shadow:1px 1px 0 #000;">🎲 Drops possíveis ao concluir:</div>
                    <div style="color:#FFB347;text-shadow:1px 1px 0 #000;">🧩 Fragmento de Equip. — <b>${equipChance}%</b></div>
                    <div style="color:#DA70D6;text-shadow:1px 1px 0 #000;">💎 Cristal/Pedra — <b>${cristalChance}%</b></div>
                </div>
                <div style="margin-top:14px;">
                    <input type="button" onclick="confOrg()" value="Iniciar Missão" style="background:url('_img/fundo_botao.jpg');border:2px solid #c07000;color:#FFD700;font-weight:bold;padding:8px 18px;margin:0 6px;cursor:pointer;border-radius:5px;" />
                    <input type="button" onclick="fecharOrg()" value="Cancelar" style="background:url('_img/fundo_botao.jpg');border:2px solid #654321;color:white;font-weight:bold;padding:8px 18px;margin:0 6px;cursor:pointer;border-radius:5px;" />
                </div>
            </div>
            <div style="background:url('_img/box/box_bottom.jpg');height:15px;border-radius:0 0 7px 7px;"></div>
        </div>`;
    document.body.appendChild(modal);

    window.confOrg = function(){
        var btn = form.querySelector('input[type="submit"]');
        btn.value = 'Iniciando...'; btn.disabled = true;
        fecharOrg(); form.submit();
    };
    window.fecharOrg = function(){
        var m = document.getElementById('modalConfOrg');
        if(m) m.remove();
    };
    modal.onclick = function(e){ if(e.target===modal) fecharOrg(); };
    return false;
}
</script>

<div class="box_top">⚔️ Missões Secretas do Clã</div>
<div class="box_middle">
<div>
    Missões exclusivas para membros de organizações. Ao concluir, há chance de obter <b>equipamentos</b> e <b>cristais raros</b> como drops adicionais.
</div>

<?php
$msg = '';
if(isset($_GET['msg'])){
    switch($_GET['msg']){
        case 1: $msg = 'Seu nível é muito baixo para esta missão.'; break;
        case 2: $msg = 'Você já está em uma missão. Termine a atual antes de iniciar outra.'; break;
        case 3: $msg = 'Tempo inválido. Máximo de 24 horas.'; break;
        case 'cooldown': $msg = '⏳ Você ainda está em cooldown! Aguarde 20h entre as missões de clã.'; break;
        case 'cancel_reward':
            $y = (int)($_GET['yens'] ?? 0);
            $e = (int)($_GET['exp']  ?? 0);
            $t = (int)($_GET['tempo'] ?? 0);
            $msg = 'Missão cancelada! Você ficou <b>'.$t.' minutos</b> e recebeu:<br/>💰 <b>'.number_format($y,2,',','.').' Yens</b> &nbsp; ⭐ <b>'.$e.' ponto'.($e>1?'s':'').' de experiência</b>';
            break;
        case 'cancel_no_reward':
            $msg = 'Missão cancelada. Tempo mínimo (10 min) não atingido, nenhuma recompensa recebida.';
            break;
        case 'completed':
            $y = (int)($_GET['yens'] ?? 0);
            $e = (int)($_GET['exp']  ?? 0);
            $msg = '✅ Missão concluída! Você recebeu:<br/>💰 <b>'.number_format($y,2,',','.').' Yens</b> &nbsp; ⭐ <b>'.$e.' ponto'.($e>1?'s':'').' de experiência</b>';
            break;
    }
}
if($msg !== '') echo '<div class="sep"></div><div class="aviso">'.$msg.'</div>';
?>

<?php if($cooldown_restante > 0):
    $h_rest = floor($cooldown_restante / 3600);
    $m_rest = floor(($cooldown_restante % 3600) / 60);
?>
<div class="sep"></div>
<div style="background:#1a0a00;border:2px solid #c07000;border-radius:6px;padding:10px;text-align:center;margin:6px 0;">
    <div style="color:#FFD700;font-weight:bold;font-size:14px;">⏳ Cooldown Ativo</div>
    <div style="color:#aaa;font-size:12px;margin-top:4px;">
        Próxima missão disponível em: <b style="color:#FF6600;"><?php echo $h_rest.'h '.$m_rest.'min'; ?></b>
        &nbsp;(às <?php echo $cooldown_fim_str; ?>)
    </div>
    <div style="color:#888;font-size:11px;margin-top:3px;">O cooldown de 20h inicia ao concluir cada missão de clã.</div>
</div>
<?php endif; ?>

<div class="sep"></div>
<div align="center">
<table width="100%" cellpadding="0" cellspacing="1">
<?php foreach($ORG_MIS as $m): ?>
<?php if($db['nivel'] >= $m[1]): ?>
<tr><td colspan="4"><div class="sep"></div></td></tr>
<tr class="table_dados" style="background:#2a1a00;">
    <td width="90" align="center" style="padding:6px;">
        <img src="<?php echo $m[6]; ?>" style="width:75px;height:75px;object-fit:cover;border:2px solid #c07000;border-radius:6px;" />
    </td>
    <td style="padding:6px 10px;">
        <b style="color:#FFD700;"><?php echo $m[5]; ?></b>
        <br/><span class="sub2">💴 <?php echo number_format($m[2],0,',','.'); ?> yens/hr &nbsp;|&nbsp; ⭐ 1 exp/hr &nbsp;|&nbsp; 🗡️ <?php echo $m[3]; ?>% &nbsp; 💎 <?php echo $m[4]; ?>%</span>
        <?php if($m[1] > 0): ?><br/><span class="sub2" style="color:#aaa;">Nível mínimo: <?php echo $m[1]; ?></span><?php endif; ?>
    </td>
    <td style="padding:6px 8px;" align="right">
        <?php if($cooldown_restante > 0): ?>
        <span style="color:#888;font-size:11px;">Em cooldown</span>
        <?php else: ?>
        <form method="post" action="?p=orgmissions" onsubmit="return confirmarMissaoOrg('<?php echo $m[5]; ?>','<?php echo $m[6]; ?>',<?php echo $m[2]; ?>,<?php echo $m[3]; ?>,<?php echo $m[4]; ?>,this);">
        <input type="hidden" name="org_code" value="<?php echo $c->encode($m[0], $chaveuniversal); ?>">
        <select name="org_tempo">
            <?php for($i=1; $i<=24; $i++): ?>
            <option value="<?php echo $c->encode($i, $chaveuniversal); ?>"><?php echo $i; ?> hora<?php if($i>1) echo 's'; ?></option>
            <?php endfor; ?>
        </select>
        <br/><input type="submit" class="botao" value="Partir">
        </form>
        <?php endif; ?>
    </td>
</tr>
<?php endif; ?>
<?php endforeach; ?>
</table>
</div>

</div>
<div class="box_bottom"></div>
