<?php
if(!isset($db['id'])) {
    echo "<script>location.href='?p=home';</script>"; exit;
}
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>location.href='?p=school';</script>"; exit;
}
if(empty($db['doujutsu'])) {
    echo "<script>location.href='?p=room&id=".(int)$_GET['id']."';</script>"; exit;
}

$sala_id = (int)$_GET['id'];
$atual   = date('Y-m-d H:i:s');
$hoje    = date('Y-m-d');

// Verificar sala válida
try {
    $stmt = $conexao->prepare("SELECT * FROM salas WHERE id=? AND usuarioid=? AND fim>?");
    $stmt->execute([$sala_id, $db['id'], $atual]);
    $dbr = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$dbr) { echo "<script>location.href='?p=school';</script>"; exit; }
    $tempo_restante = strtotime($dbr['fim']) - strtotime($atual);
    if($tempo_restante <= 0) { echo "<script>location.href='?p=school';</script>"; exit; }
    $tempo_fmt = sprintf('%02d:%02d:%02d', floor($tempo_restante/3600), floor(($tempo_restante%3600)/60), $tempo_restante%60);
} catch (PDOException $e) {
    echo "<script>location.href='?p=school';</script>"; exit;
}

// Constantes
define('DOUJ_TREINO_MAX_NIVEL', 30);
define('DOUJ_TREINO_LIMITE_DIA',20); // máx treinos por dia
define('DOUJ_TREINO_EXP',        5); // XP ganho por treino

/**
 * Custo de energia = 5% da energiamax + nível do personagem.
 * Mínimo de 10, máximo de 500.
 */
function calcular_custo_energia(int $energiamax, int $nivel): int {
    $custo = (int)round($energiamax * 0.05) + $nivel;
    return max(10, min(500, $custo));
}

$nome_doujutsu = ['', 'Sharingan', 'Byakugan', 'Rinnegan'];
$img_doujutsu  = ['', '_img/doujutsus/sharingan.jpg', '_img/doujutsus/byakugan.jpg', '_img/doujutsus/rinnegan.jpg'];
$cor_doujutsu  = ['', '#CC2200', '#2255CC', '#7722AA'];

$msg_treino = '';
$msg_ok     = false;

// ─── BUSCAR DADOS FRESCOS ────────────────────────────────────────────────────
$fetch_user = function() use ($conexao, $db) {
    $s = $conexao->prepare("SELECT energia, energiamax, nivel, doujutsu, doujutsu_nivel, doujutsu_exp, doujutsu_expmax, doujutsu_treino_fim, doujutsu_treino_dia, doujutsu_treino_count FROM usuarios WHERE id=?");
    $s->execute([$db['id']]);
    return $s->fetch(PDO::FETCH_ASSOC);
};

// ─── PROCESSAR TREINO POST ───────────────────────────────────────────────────
if(isset($_POST['treinar_doujutsu'])) {
    if(!csrf_validar()) { echo "<script>self.location='?p=home'</script>"; exit; }
    $ufresh = $fetch_user();

    if(!$ufresh) {
        $msg_treino = 'Erro ao buscar dados do usuário.';
    } else {
        $custo_energia = calcular_custo_energia((int)$ufresh['energiamax'], (int)$ufresh['nivel']);

        // Resetar contador diário se for um novo dia
        $count_hoje = ($ufresh['doujutsu_treino_dia'] === $hoje) ? (int)$ufresh['doujutsu_treino_count'] : 0;

        if((int)$ufresh['doujutsu_nivel'] >= DOUJ_TREINO_MAX_NIVEL) {
            $msg_treino = 'Seu doujutsu já está no nível máximo (30)!';

        } elseif(!empty($ufresh['doujutsu_treino_fim']) && $ufresh['doujutsu_treino_fim'] > $atual) {
            $seg = strtotime($ufresh['doujutsu_treino_fim']) - strtotime($atual);
            $msg_treino = 'Aguarde <b>'.ceil($seg).' segundo(s)</b> antes de treinar novamente.';

        } elseif($count_hoje >= DOUJ_TREINO_LIMITE_DIA) {
            $msg_treino = 'Você atingiu o limite diário de <b>'.DOUJ_TREINO_LIMITE_DIA.'</b> treinos. Volte amanhã!';

        } elseif((int)$ufresh['energia'] < $custo_energia) {
            $msg_treino = 'Chakra insuficiente! Este treino custa <b>'.$custo_energia.'</b> de chakra (você tem '.(int)$ufresh['energia'].').';

        } else {
            $nova_energia     = (int)$ufresh['energia'] - $custo_energia;
            $nova_dexp        = (int)$ufresh['doujutsu_exp'] + DOUJ_TREINO_EXP;
            $novo_count       = $count_hoje + 1;

            $conexao->prepare("UPDATE usuarios SET energia=?, doujutsu_exp=?, doujutsu_treino_dia=?, doujutsu_treino_count=? WHERE id=?")
                     ->execute([$nova_energia, $nova_dexp, $hoje, $novo_count, $db['id']]);

            // Atualizar $db em memória
            $db['energia']              = $nova_energia;
            $db['doujutsu_exp']         = $nova_dexp;
            $db['doujutsu_nivel']       = (int)$ufresh['doujutsu_nivel'];
            $db['doujutsu_expmax']      = (int)$ufresh['doujutsu_expmax'];
            $db['doujutsu_treino_dia']  = $hoje;
            $db['doujutsu_treino_count']= $novo_count;

            // Verificar level-up do doujutsu
            $levelups = 0;
            while($db['doujutsu_exp'] >= $db['doujutsu_expmax'] && $db['doujutsu_nivel'] < DOUJ_TREINO_MAX_NIVEL) {
                $difexp              = $db['doujutsu_exp'] - $db['doujutsu_expmax'];
                $novaexpmax          = $db['doujutsu_expmax'] + $db['doujutsu_nivel'] * 10;
                $db['doujutsu_nivel']++;
                $db['doujutsu_exp']    = $difexp;
                $db['doujutsu_expmax'] = $novaexpmax;
                $conexao->prepare("UPDATE usuarios SET doujutsu_nivel=?, doujutsu_exp=?, doujutsu_expmax=? WHERE id=?")
                         ->execute([$db['doujutsu_nivel'], $db['doujutsu_exp'], $db['doujutsu_expmax'], $db['id']]);
                $levelups++;
            }

            $msg_treino = $levelups > 0
                ? 'Seu doujutsu evoluiu para o nível <b>'.$db['doujutsu_nivel'].'</b>!'
                : '+'.DOUJ_TREINO_EXP.' XP de doujutsu obtidos! ('.$novo_count.'/'.DOUJ_TREINO_LIMITE_DIA.' treinos hoje)';
            $msg_ok = true;
        }
    }
}

// ─── RE-BUSCAR ESTADO ATUAL ──────────────────────────────────────────────────
$uf = $fetch_user();
if($uf) {
    $db['energia']               = $uf['energia'];
    $db['energiamax']            = $uf['energiamax'];
    $db['nivel']                 = $uf['nivel'];
    $db['doujutsu_nivel']        = $uf['doujutsu_nivel'];
    $db['doujutsu_exp']          = $uf['doujutsu_exp'];
    $db['doujutsu_expmax']       = $uf['doujutsu_expmax'];
    $db['doujutsu_treino_fim']   = $uf['doujutsu_treino_fim'];
    $db['doujutsu_treino_dia']   = $uf['doujutsu_treino_dia'];
    $db['doujutsu_treino_count'] = $uf['doujutsu_treino_count'];
}

// Custo atual (baseado nos dados atualizados)
$custo_energia   = calcular_custo_energia((int)$db['energiamax'], (int)$db['nivel']);

// Contador diário (reseta se for novo dia)
$count_hoje = ($db['doujutsu_treino_dia'] === $hoje) ? (int)$db['doujutsu_treino_count'] : 0;
$limite_ok  = ($count_hoje < DOUJ_TREINO_LIMITE_DIA);

$nivel_atual  = (int)$db['doujutsu_nivel'];
$exp_atual    = (int)$db['doujutsu_exp'];
$expmax       = (int)$db['doujutsu_expmax'];
$pct          = ($expmax > 0) ? min(100, round(($exp_atual / $expmax) * 100)) : 0;
$pode_treinar = $limite_ok && ($db['energia'] >= $custo_energia) && ($nivel_atual < DOUJ_TREINO_MAX_NIVEL);
$tipo         = (int)$db['doujutsu'];
$cor          = $cor_doujutsu[$tipo] ?? '#888';
$nome         = $nome_doujutsu[$tipo] ?? 'Doujutsu';
$restantes    = max(0, DOUJ_TREINO_LIMITE_DIA - $count_hoje);
?>
<script language="javascript" type="text/javascript">
var conc = 0;

function calculafimSala(div) {
    var el = document.getElementById(div);
    if(!el) return;
    var tmp = el.innerHTML.split(":");
    if(tmp.length != 3) return;
    var s = parseInt(tmp[2]), m = parseInt(tmp[1]), h = parseInt(tmp[0]);
    s--;
    if(s < 0){ s = 59; m--; }
    if(m < 0){ m = 59; h--; }
    if(h < 0){ conc = 1; self.location = "?p=school"; return; }
    el.innerHTML = String(h).padStart(2,'0')+":"+String(m).padStart(2,'0')+":"+String(s).padStart(2,'0');
    if(el.innerHTML <= "00:00:01"){ conc = 1; self.location = "?p=school"; }
}
setInterval(function(){ if(conc==0) calculafimSala('sala_tempo'); }, 1000);

</script>

<div class="box_top">Treinar <?php echo $nome; ?></div>
<div class="box_middle">

<div class="aviso"><b>Tempo Restante na Sala: <span id="sala_tempo" style="color:#FFFFFF"><?php echo $tempo_fmt; ?></span></b></div>
<div class="sep"></div>

<?php if($msg_treino): ?>
<div class="aviso" style="border-color:<?php echo $msg_ok ? '#33aa33' : '#aa3333'; ?>;color:<?php echo $msg_ok ? '#66ff66' : '#ff6666'; ?>;">
    <?php echo $msg_treino; ?>
</div>
<div class="sep"></div>
<?php endif; ?>

<!-- Doujutsu info -->
<table width="100%" cellpadding="4" cellspacing="0">
<tr>
    <td width="110" align="center">
        <img src="<?php echo $img_doujutsu[$tipo] ?? '_img/doujutsus/sharingan.jpg'; ?>" style="width:75px;height:75px;object-fit:cover;border-radius:6px;border:2px solid <?php echo $cor; ?>;" onerror="this.style.display='none';" />
    </td>
    <td>
        <b style="color:<?php echo $cor; ?>;font-size:15px;"><?php echo $nome; ?></b><br>
        <span class="sub2">Nível <?php echo $nivel_atual; ?><?php echo $nivel_atual >= DOUJ_TREINO_MAX_NIVEL ? ' <b style="color:#FFD700">(MÁXIMO)</b>' : ' / 30'; ?></span>
        <?php if($nivel_atual < DOUJ_TREINO_MAX_NIVEL): ?>
        <div class="sep"></div>
        <span class="sub2">Experiência: <b><?php echo $exp_atual; ?> / <?php echo $expmax; ?></b></span><br>
        <div style="background:#333;border-radius:4px;height:10px;margin:4px 0;overflow:hidden;">
            <div style="background:<?php echo $cor; ?>;height:10px;width:<?php echo $pct; ?>%;border-radius:4px;transition:width .4s;"></div>
        </div>
        <span class="sub2"><?php echo $pct; ?>%</span>
        <?php endif; ?>
    </td>
</tr>
</table>
<div class="sep"></div>

<!-- Stats do treino -->
<table width="100%" cellpadding="5" cellspacing="1">
<tr class="table_dados">
    <td align="center">
        <span class="sub2">Chakra necessário</span><br>
        <b style="color:#FF6600;"><?php echo $custo_energia; ?> chakra</b><br>
        <span class="sub2">(5% de <?php echo (int)$db['energiamax']; ?> + nv.<?php echo (int)$db['nivel']; ?>)</span>
    </td>
    <td align="center">
        <span class="sub2">XP ganho</span><br>
        <b style="color:#FFD700;">+<?php echo DOUJ_TREINO_EXP; ?> XP</b>
    </td>
    <td align="center">
        <span class="sub2">Treinos hoje</span><br>
        <b style="color:<?php echo $restantes > 0 ? '#66ff66' : '#ff6666'; ?>"><?php echo $count_hoje; ?> / <?php echo DOUJ_TREINO_LIMITE_DIA; ?></b><br>
        <span class="sub2"><?php echo $restantes; ?> restante(s)</span>
    </td>
</tr>
<tr class="table_dados">
    <td colspan="4" align="center">
        <span class="sub2">Seu chakra:</span>
        <b style="color:<?php echo $db['energia'] >= $custo_energia ? '#66ff66' : '#ff6666'; ?>">
            <?php echo (int)$db['energia']; ?>
        </b>
        <span class="sub2">/ <?php echo (int)$db['energiamax']; ?></span>
    </td>
</tr>
</table>
<div class="sep"></div>

<!-- Botão / estado -->
<?php if($nivel_atual >= DOUJ_TREINO_MAX_NIVEL): ?>
    <div class="aviso">Seu <?php echo $nome; ?> já está no nível máximo. Não é possível treinar mais.</div>

<?php elseif(!$limite_ok): ?>
    <div class="aviso" align="center">
        Você atingiu o limite diário de <b><?php echo DOUJ_TREINO_LIMITE_DIA; ?></b> treinos.<br>
        <span class="sub2">Volte amanhã para continuar treinando.</span>
    </div>

<?php elseif($db['energia'] < $custo_energia): ?>
    <div class="aviso">
        Chakra insuficiente. Este treino custa <b><?php echo $custo_energia; ?></b> de chakra e você tem <b><?php echo (int)$db['energia']; ?></b>.
    </div>

<?php else: ?>
    <div align="center">
        <form method="POST" action="?p=treinar_doujutsu&id=<?php echo $sala_id; ?>" onsubmit="this.querySelector('[type=submit]').disabled=true;this.querySelector('[type=submit]').value='Treinando...';">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="treinar_doujutsu" value="1">
            <input type="submit" class="botao" value="Treinar <?php echo $nome; ?>">
        </form>
        <span class="sub2">Consome <?php echo $custo_energia; ?> de chakra &bull; +<?php echo DOUJ_TREINO_EXP; ?> XP de doujutsu</span>
    </div>
<?php endif; ?>

<div class="sep"></div>
<div align="center">
    <input type="button" class="botao" value="Voltar à Sala" onclick="location.href='?p=room&id=<?php echo $sala_id; ?>'">
</div>

</div>
<div class="box_bottom"></div>
