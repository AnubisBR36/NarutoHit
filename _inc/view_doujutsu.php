<?php
$dj_id    = (int)$db['doujutsu'];
$dj_nomes = [1 => 'Sharingan', 2 => 'Byakugan', 3 => 'Rinnegan'];
$dj_cores = [1 => '#CC2200',   2 => '#5599FF',  3 => '#9933CC'];
$dj_borda = [1 => '#881100',   2 => '#2255AA',  3 => '#660099'];
$dj_bonus = [
    1 => 'Bônus de 2% por nível no Genjutsu.',
    2 => 'Bônus de 2% por nível no Taijutsu.',
    3 => 'Bônus de 2% por nível no Ninjutsu.',
];

$txtdoujutsu = $dj_nomes[$dj_id] ?? 'Desconhecido';
$cor         = $dj_cores[$dj_id] ?? '#888';
$borda       = $dj_borda[$dj_id] ?? '#444';
$habilidade  = $dj_bonus[$dj_id] ?? '';
$img         = '_img/doujutsus/' . strtolower($txtdoujutsu) . '.jpg';

$dj_nivel   = (int)($db['doujutsu_nivel'] ?? 0);
$dj_exp     = (int)($db['doujutsu_exp']   ?? 0);
$dj_expmax  = (int)($db['doujutsu_expmax'] ?? 0);
$dj_pct     = ($dj_expmax > 0) ? min(100, round($dj_exp / $dj_expmax * 100)) : 0;
$max_nivel  = 30;
?>
<div class="box_top" style="color:<?php echo $cor; ?>;">Doujutsu de <?php echo e($db['usuario']); ?></div>
<div class="box_middle">
<table width="100%" cellpadding="4" cellspacing="1">
    <tr class="table_dados" style="background:#1e1e1e;">
        <td width="100" align="center" rowspan="<?php echo $dj_nivel < $max_nivel ? 3 : 2; ?>">
            <img src="<?php echo $img; ?>" style="width:80px;height:80px;object-fit:cover;border-radius:6px;border:2px solid <?php echo $borda; ?>;" onerror="this.style.display='none';" />
        </td>
        <td>
            <b style="color:<?php echo $cor; ?>;font-size:15px;"><?php echo $txtdoujutsu; ?></b>
            <span class="sub2" style="margin-left:8px;">
                Nível <b style="color:<?php echo $cor; ?>;"><?php echo $dj_nivel; ?></b>
                <?php echo $dj_nivel >= $max_nivel ? ' <b style="color:#FFD700;">(MÁXIMO)</b>' : '/ '.$max_nivel; ?>
            </span>
        </td>
    </tr>
    <?php if($dj_nivel < $max_nivel): ?>
    <tr class="table_dados" style="background:#1e1e1e;">
        <td>
            <span class="sub2">XP de Doujutsu: <b><?php echo $dj_exp; ?> / <?php echo $dj_expmax; ?></b></span>
            <div style="background:#333;border-radius:4px;height:8px;margin:4px 0;overflow:hidden;max-width:260px;">
                <div style="background:<?php echo $cor; ?>;height:8px;width:<?php echo $dj_pct; ?>%;border-radius:4px;"></div>
            </div>
            <span class="sub2"><?php echo $dj_pct; ?>% para o próximo nível</span>
        </td>
    </tr>
    <?php endif; ?>
    <tr class="table_dados" style="background:#1e1e1e;">
        <td>
            <span class="sub2"><?php echo $habilidade; ?></span>
        </td>
    </tr>
</table>
</div>
<div class="box_bottom"></div>
