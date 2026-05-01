<?php
if(!isset($db) || !isset($db['id'])) {
    echo "<div style='color:red;text-align:center;padding:20px;'>Você precisa estar logado para ver os atributos.</div>";
    return;
}

// Recarregar dados do usuário sempre para garantir que os dados estejam atualizados
try {
    $stmt = $conexao->prepare("SELECT taijutsu, ninjutsu, genjutsu, bonus_invasao_tai, bonus_invasao_nin, bonus_invasao_gen, bonus_invasao_pct, doujutsu, doujutsu_nivel FROM usuarios WHERE id = ?");
    $stmt->execute([$db['id']]);
    $user_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    if($user_stats) {
        $tai_base = $user_stats['taijutsu'];
        $nin_base = $user_stats['ninjutsu'];
        $gen_base = $user_stats['genjutsu'];
        $doujutsu = $user_stats['doujutsu'];
        $doujutsu_nivel = $user_stats['doujutsu_nivel'];
        $invasao_bonus_pct_display = (int)($user_stats['bonus_invasao_pct'] ?? 0);

        $db['taijutsu'] = $tai_base;
        $db['ninjutsu'] = $nin_base;
        $db['genjutsu'] = $gen_base;
        $db['doujutsu'] = $doujutsu;
        $db['doujutsu_nivel'] = $doujutsu_nivel;
    } else {
        $tai_base = $db['taijutsu'];
        $nin_base = $db['ninjutsu'];
        $gen_base = $db['genjutsu'];
        $doujutsu = $db['doujutsu'];
        $doujutsu_nivel = $db['doujutsu_nivel'];
        $invasao_bonus_pct_display = 0;
    }
} catch(Exception $e) {
    $tai_base = $db['taijutsu'];
    $nin_base = $db['ninjutsu'];
    $gen_base = $db['genjutsu'];
    $doujutsu = $db['doujutsu'];
    $doujutsu_nivel = $db['doujutsu_nivel'];
    $invasao_bonus_pct_display = 0;
}

// Calcular bônus dos equipamentos (itens equipados)
$tai_equip_bonus = 0;
$nin_equip_bonus = 0;
$gen_equip_bonus = 0;

try {
    $stmt_equip = $conexao->prepare("SELECT i.upgrade, t.taijutsu, t.ninjutsu, t.genjutsu FROM inventario i LEFT OUTER JOIN table_itens t ON i.itemid=t.id WHERE i.usuarioid=? AND i.status='on'");
    $stmt_equip->execute([$db['id']]);

    while($equip = $stmt_equip->fetch(PDO::FETCH_ASSOC)) {
        $tai_equip_bonus += ($equip['taijutsu'] ?? 0) + ($equip['upgrade'] ?? 0);
        $nin_equip_bonus += ($equip['ninjutsu'] ?? 0) + ($equip['upgrade'] ?? 0);
        $gen_equip_bonus += ($equip['genjutsu'] ?? 0) + ($equip['upgrade'] ?? 0);
    }
} catch(Exception $e) {
    $tai_equip_bonus = 0;
    $nin_equip_bonus = 0;
    $gen_equip_bonus = 0;
}

// Calcular bônus do doujutsu
$tai_doujutsu_bonus = 0;
$nin_doujutsu_bonus = 0;
$gen_doujutsu_bonus = 0;

if($doujutsu > 0 && $doujutsu_nivel > 0) {
    if($doujutsu == 2) { // Sharingan - bônus em taijutsu
        $tai_doujutsu_bonus = round($tai_base * ($doujutsu_nivel / 50));
    } elseif($doujutsu == 1) { // Byakugan - bônus em genjutsu
        $gen_doujutsu_bonus = round($gen_base * ($doujutsu_nivel / 50));
    }
}

// Bônus de invasão: recalculado sempre como % dos stats ATUAIS
// O % fica gravado em bonus_invasao_pct — zerado quando nova invasão começa
if($invasao_bonus_pct_display > 0) {
    $pct = $invasao_bonus_pct_display / 100;
    $tai_bonus = (int)round($tai_base * $pct);
    $nin_bonus = (int)round($nin_base * $pct);
    $gen_bonus = (int)round($gen_base * $pct);
} else {
    $tai_bonus = 0;
    $nin_bonus = 0;
    $gen_bonus = 0;
}

// Calcular totais finais
$tai_total = $tai_base + $tai_doujutsu_bonus + $tai_bonus + $tai_equip_bonus;
$nin_total = $nin_base + $nin_doujutsu_bonus + $nin_bonus + $nin_equip_bonus;
$gen_total = $gen_base + $gen_doujutsu_bonus + $gen_bonus + $gen_equip_bonus;

$tem_bonus_invasao = $tai_bonus > 0 || $nin_bonus > 0 || $gen_bonus > 0;
$tem_bonus_doujutsu = $tai_doujutsu_bonus > 0 || $nin_doujutsu_bonus > 0 || $gen_doujutsu_bonus > 0;
$tem_bonus_equipamento = $tai_equip_bonus > 0 || $nin_equip_bonus > 0 || $gen_equip_bonus > 0;
?>

<!-- Aviso de Bônus de Invasão (se ativo) -->
<?php if($tem_bonus_invasao): ?>
<div style="margin: 15px 0; padding: 12px; background: linear-gradient(45deg, #0f4f0f, #1a5f1a); border: 2px solid #00ff00; border-radius: 8px; text-align: center; box-shadow: 0 0 15px rgba(0,255,0,0.3);">
    <div style="color: #00ff00; font-weight: bold; font-size: 14px; text-shadow: 0 0 5px #00ff00;">
        🎉 BÔNUS DE INVASÃO ATIVO! 🎉
    </div>
    <div style="color: #fff; font-size: 11px; margin-top: 8px; line-height: 1.4;">
        Sua vila ganhou +<?php echo $invasao_bonus_pct_display; ?>% em todos os status por derrotar uma invasão!<br>
        <div style="margin-top: 5px;">
            <?php if($tai_bonus > 0): ?>
                <strong style="color: #ffcc00;">Taijutsu:</strong> <span style="color: #00ff00;">+<?php echo number_format($tai_bonus); ?></span>
            <?php endif; ?>
            <?php if($nin_bonus > 0): ?>
                <strong style="color: #ffcc00;">Ninjutsu:</strong> <span style="color: #00ff00;">+<?php echo number_format($nin_bonus); ?></span>
            <?php endif; ?>
            <?php if($gen_bonus > 0): ?>
                <strong style="color: #ffcc00;">Genjutsu:</strong> <span style="color: #00ff00;">+<?php echo number_format($gen_bonus); ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tabela de Atributos -->
<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td width="15%" align="right" style="padding-right:5px;"><b>Taijutsu:</b></td>
        <td><img src="_img/bars/bar_left.jpg" /><img src="_img/bars/bar.png" width="280" height="22" /><img src="_img/bars/bar_right.jpg" /></td>
        <td width="25%" style="padding-left:5px;">
            <b>| <?php echo number_format($tai_total); ?> |</b>
        </td>
    </tr>
    <tr>
        <td align="right" style="padding-right:5px;"><b>Ninjutsu:</b></td>
        <td><img src="_img/bars/bar_left.jpg" /><img src="_img/bars/bar.png" width="280" height="22" /><img src="_img/bars/bar_right.jpg" /></td>
        <td style="padding-left:5px;">
            <b>| <?php echo number_format($nin_total); ?> |</b>
        </td>
    </tr>
    <tr>
        <td align="right" style="padding-right:5px;"><b>Genjutsu:</b></td>
        <td><img src="_img/bars/bar_left.jpg" /><img src="_img/bars/bar.png" width="280" height="22" /><img src="_img/bars/bar_right.jpg" /></td>
        <td style="padding-left:5px;">
            <b>| <?php echo number_format($gen_total); ?> |</b>
        </td>
    </tr>
</table>
