<?php
$style_gain = 'color:#FFD700;text-shadow:-1px -1px 0 #000,1px -1px 0 #000,-1px 1px 0 #000,1px 1px 0 #000;';
$style_loss = 'color:#ff3a3a;text-shadow:-1px -1px 0 #000,1px -1px 0 #000,-1px 1px 0 #000,1px 1px 0 #000;';
switch($db['doujutsu']){
        case 1:
            $txtdoujutsu='Sharingan';
            $bonus_val = ($db['doujutsu_nivel']>0) ? max(1, (int)round($db['genjutsu'] * ($db['doujutsu_nivel'] / 50))) : 0;
            $loss_val  = ($db['doujutsu_nivel']>0) ? max(1, (int)round($db['taijutsu'] * ($db['doujutsu_nivel'] / 100))) : 0;
            $habilidades='- Bônus de '.($db['doujutsu_nivel']*2).'% no Genjutsu (<b style="'.$style_gain.'">+' . $bonus_val . ' Genjutsu</b>);<br />'
                        .'- Penalidade de '.($db['doujutsu_nivel']).'% no Taijutsu (<b style="'.$style_loss.'">-' . $loss_val . ' Taijutsu</b>);<br />'
                        .'- 3 Jutsus.';
            break;
        case 2:
            $txtdoujutsu='Byakugan';
            $bonus_val = ($db['doujutsu_nivel']>0) ? max(1, (int)round($db['taijutsu'] * ($db['doujutsu_nivel'] / 50))) : 0;
            $loss_val  = ($db['doujutsu_nivel']>0) ? max(1, (int)round($db['ninjutsu'] * ($db['doujutsu_nivel'] / 100))) : 0;
            $habilidades='- Bônus de '.($db['doujutsu_nivel']*2).'% no Taijutsu (<b style="'.$style_gain.'">+' . $bonus_val . ' Taijutsu</b>);<br />'
                        .'- Penalidade de '.($db['doujutsu_nivel']).'% no Ninjutsu (<b style="'.$style_loss.'">-' . $loss_val . ' Ninjutsu</b>);<br />'
                        .'- 3 Jutsus.';
            break;
        case 3:
            $txtdoujutsu='Rinnegan';
            $bonus_val = ($db['doujutsu_nivel']>0) ? max(1, (int)round($db['ninjutsu'] * ($db['doujutsu_nivel'] / 50))) : 0;
            $loss_val  = ($db['doujutsu_nivel']>0) ? max(1, (int)round($db['taijutsu'] * ($db['doujutsu_nivel'] / 100))) : 0;
            $habilidades='- Bônus de '.($db['doujutsu_nivel']*2).'% no Ninjutsu (<b style="'.$style_gain.'">+' . $bonus_val . ' Ninjutsu</b>);<br />'
                        .'- Penalidade de '.($db['doujutsu_nivel']).'% no Taijutsu (<b style="'.$style_loss.'">-' . $loss_val . ' Taijutsu</b>);<br />'
                        .'- 3 Jutsus.';
            break;
}
?>
<div class="box_top">Meu Doujutsu</div>
<div class="box_middle">Informações do seu doujutsu.<div class="sep"></div>
        <table width="100%" cellpadding="0" cellspacing="1">
    <tr class="table_dados" style="background:#323232;">
        <td width="220"><img src="_img/doujutsus/<?php echo strtolower($txtdoujutsu); ?>.jpg" />
        <?php
        if($db['doujutsu']==1){
                        if($db['doujutsu_nivel']<=6) $txtdoujutsu.=' - Nível 1';
                        if(($db['doujutsu_nivel']>6)&&($db['doujutsu_nivel'])<=12) $txtdoujutsu.=' - Nível 2';
                        if(($db['doujutsu_nivel']>13)&&($db['doujutsu_nivel'])<=18) $txtdoujutsu.=' - Nível 3';
                        if(($db['doujutsu_nivel']>18)&&($db['doujutsu_nivel'])<=24) $txtdoujutsu.=' - Mangekyou Sharingan';
                        if($db['doujutsu_nivel']>24) $txtdoujutsu.=' - Fuumetsu Mangekyou Sharingan';
                }
                ?></td>
        <td width="80"><b>Nível <?php echo $db['doujutsu_nivel']; ?></b><?php if($db['doujutsu_nivel']<30){ ?><br /><span class="sub2">Experiência<br /><?php echo $db['doujutsu_exp'].' / '.$db['doujutsu_expmax']; ?></span><?php } else { ?><br /><span class="sub2">Nível máximo alcançado.</span><?php } ?></td>
        <td><span class="sub2"><?php echo $habilidades; ?><br />
        </span></td>
    </tr>
    </table>
</div>
<div class="box_bottom"></div>
