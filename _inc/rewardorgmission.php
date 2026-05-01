<?php require_once('trava.php'); ?>
<?php
// Só missões de org (911-924)
if($db['missao'] < 911 || $db['missao'] > 924){
    echo "<script>self.location='?p=home'</script>"; exit;
}

$agora = time();
$fim   = strtotime($db['missao_fim']);

if($agora < $fim){
    echo "<script>self.location='?p=busymission'</script>"; exit;
}

// Calcular tempo e recompensa base
if(isset($db['missao_inicio']) && !empty($db['missao_inicio'])){
    $inicio = strtotime($db['missao_inicio']);
} else {
    $inicio = $fim - ($db['missao_tempo'] * 3600);
}
$tempo_total_horas = max(0.1, ($fim - $inicio) / 3600);

$ORG_MIS_RW = [
    911=>[100,  3,  5,'Missão I',   '_img/MClan/52.jpg'],
    912=>[180,  4,  6,'Missão II',  '_img/MClan/53.jpg'],
    913=>[280,  5,  7,'Missão III', '_img/MClan/54.jpg'],
    914=>[400,  6,  8,'Missão IV',  '_img/MClan/55.jpg'],
    915=>[550,  7,  9,'Missão V',   '_img/MClan/85.jpg'],
    916=>[720,  8, 10,'Missão VI',  '_img/MClan/103.jpg'],
    917=>[900,  9, 11,'Missão VII', '_img/MClan/105.jpg'],
    918=>[1100,10, 13,'Missão VIII','_img/MClan/113.jpg'],
    919=>[1350,12, 14,'Missão IX',  '_img/MClan/160.jpg'],
    920=>[1600,13, 16,'Missão X',   '_img/MClan/164.jpg'],
    921=>[1900,15, 17,'Missão XI',  '_img/MClan/166.jpg'],
    922=>[2300,16, 19,'Missão XII', '_img/MClan/168.jpg'],
    923=>[2700,18, 20,'Missão XIII','_img/MClan/171.jpg'],
    924=>[3200,20, 22,'Missão XIV', '_img/MClan/262.jpg'],
];
$rw       = $ORG_MIS_RW[$db['missao']] ?? $ORG_MIS_RW[911];
$yens_hr       = $rw[0];
$equip_chance  = $rw[1];
$cristal_chance= $rw[2];
$rank_label    = $rw[3];
$rank_img      = $rw[4];

$yens_ganhos = max(1, (int)floor($yens_hr * $tempo_total_horas));
$exp_ganha   = max(1, (int)floor($tempo_total_horas));

// Atualizar base: yens + exp + zerar missão + cooldown 20h
try {
    $stmt = $conexao->prepare("UPDATE usuarios SET yens=yens+?, yens_fat=yens_fat+?, exp=exp+?, missao=0, orgmissao=0, ultima_missao_clan=CURRENT_TIMESTAMP WHERE id=?");
    $stmt->execute([$yens_ganhos, $yens_ganhos, $exp_ganha, $db['id']]);
} catch(PDOException $e){
    echo "<script>alert('Erro ao processar recompensa.'); self.location='?p=home'</script>"; exit;
}

// -------------------------------------------------------
// Drops: rolagens independentes
// Equipamento → dá FRAGMENTO (não item completo)
// -------------------------------------------------------
$drop_frag    = null;
$drop_cristal = null;

// Roll: fragmento de equipamento
$roll_equip = rand(1, 100);
if($roll_equip <= $equip_chance){
    try {
        $stmt = $conexao->prepare("SELECT * FROM table_itens ORDER BY RANDOM() LIMIT 1");
        $stmt->execute();
        $item_sorteado = $stmt->fetch(PDO::FETCH_ASSOC);
        if($item_sorteado){
            // Adiciona/incrementa fragmento na tabela fragmentos
            $upsertFrag = Database::isMysql()
                ? "INSERT INTO fragmentos (usuarioid, itemid, quantidade) VALUES (?, ?, 1)
                   ON DUPLICATE KEY UPDATE quantidade = quantidade + 1"
                : "INSERT INTO fragmentos (usuarioid, itemid, quantidade) VALUES (?, ?, 1)
                   ON CONFLICT(usuarioid, itemid) DO UPDATE SET quantidade = quantidade + 1";
            $stmt2 = $conexao->prepare($upsertFrag);
            $stmt2->execute([$db['id'], $item_sorteado['id']]);
            $drop_frag = $item_sorteado;
        }
    } catch(PDOException $e){ $drop_frag = null; }
}

// Roll: cristal/usável (exclui cristais de buff e de craft da rolagem geral)
$roll_cristal = rand(1, 100);
$drop_cristal = null;
if($roll_cristal <= $cristal_chance){
    try {
        $stmt = $conexao->prepare("SELECT * FROM table_usaveis WHERE categoria NOT IN ('cristal_buff','cristal_craft') ORDER BY RANDOM() LIMIT 1");
        $stmt->execute();
        $drop_cristal = $stmt->fetch(PDO::FETCH_ASSOC);
        if($drop_cristal){
            $stmt2 = $conexao->prepare("INSERT INTO usaveis (usuarioid, itemid) VALUES (?, ?)");
            $stmt2->execute([$db['id'], $drop_cristal['id']]);
        }
    } catch(PDOException $e){ $drop_cristal = null; }
}

// Roll separado: Cristal de Buff (5% chance — 50% cristal completo, 50% fragmento)
$roll_buff = rand(1, 100);
$drop_buff  = null;
$drop_buff_eh_fragmento = false;
if($roll_buff <= 5){
    try {
        $stmt = $conexao->prepare("SELECT * FROM table_usaveis WHERE categoria = 'cristal_buff' ORDER BY RANDOM() LIMIT 1");
        $stmt->execute();
        $buff_sorteado = $stmt->fetch(PDO::FETCH_ASSOC);
        if($buff_sorteado){
            if(rand(0,1) === 0){
                // Cristal completo
                $stmt2 = $conexao->prepare("INSERT INTO usaveis (usuarioid, itemid) VALUES (?, ?)");
                $stmt2->execute([$db['id'], $buff_sorteado['id']]);
                $drop_buff = $buff_sorteado;
            } else {
                // Fragmento de buff (armazenado na tabela buff_fragmentos)
                $pkBF = Database::autoIncPK('id');
                $conexao->exec("CREATE TABLE IF NOT EXISTS buff_fragmentos (
                    $pkBF,
                    usuarioid INTEGER NOT NULL,
                    itemid INTEGER NOT NULL,
                    quantidade INTEGER NOT NULL DEFAULT 1,
                    UNIQUE(usuarioid, itemid)
                )");
                $upsertBuffFrag = Database::isMysql()
                    ? "INSERT INTO buff_fragmentos (usuarioid, itemid, quantidade) VALUES (?,?,1)
                       ON DUPLICATE KEY UPDATE quantidade = quantidade + 1"
                    : "INSERT INTO buff_fragmentos (usuarioid, itemid, quantidade) VALUES (?,?,1)
                       ON CONFLICT(usuarioid,itemid) DO UPDATE SET quantidade = quantidade + 1";
                $conexao->prepare($upsertBuffFrag)
                    ->execute([$db['id'], $buff_sorteado['id']]);
                $drop_buff = $buff_sorteado;
                $drop_buff_eh_fragmento = true;
            }
        }
    } catch(PDOException $e){ $drop_buff = null; }
}

// Roll separado: Cristal de Craft (4% chance — 60% cristal completo, 40% fragmento).
// Cristal completo é sorteado de cat='cristal_craft'.
// Fragmento é sorteado de cat='fragmento_craft' (entidades próprias com cristal_alvo_id).
$roll_craft = rand(1, 100);
$drop_craft = null;
$drop_craft_eh_fragmento = false;
if($roll_craft <= 4){
    try {
        $eh_fragmento = (rand(1,10) > 6); // 40% chance de ser fragmento
        if ($eh_fragmento) {
            // Sorteia um fragmento_craft aleatório (precisa ter cristal_alvo_id válido)
            $stmt = $conexao->prepare("
                SELECT f.*
                FROM table_usaveis f
                INNER JOIN table_usaveis c ON f.cristal_alvo_id = c.id AND c.categoria = 'cristal_craft'
                WHERE f.categoria = 'fragmento_craft'
                ORDER BY RANDOM() LIMIT 1
            ");
            $stmt->execute();
            $craft_sorteado = $stmt->fetch(PDO::FETCH_ASSOC);
            if($craft_sorteado){
                // Garante tabela craft_fragmentos
                $pkCF = Database::autoIncPK('id');
                $conexao->exec("CREATE TABLE IF NOT EXISTS craft_fragmentos (
                    $pkCF,
                    usuarioid INTEGER NOT NULL,
                    itemid INTEGER NOT NULL,
                    quantidade INTEGER NOT NULL DEFAULT 0,
                    UNIQUE(usuarioid, itemid)
                )");
                $upsertCraftFrag = Database::isMysql()
                    ? "INSERT INTO craft_fragmentos (usuarioid, itemid, quantidade) VALUES (?,?,1)
                       ON DUPLICATE KEY UPDATE quantidade = quantidade + 1"
                    : "INSERT INTO craft_fragmentos (usuarioid, itemid, quantidade) VALUES (?,?,1)
                       ON CONFLICT(usuarioid,itemid) DO UPDATE SET quantidade = quantidade + 1";
                $conexao->prepare($upsertCraftFrag)
                    ->execute([$db['id'], $craft_sorteado['id']]);
                $drop_craft = $craft_sorteado;
                $drop_craft_eh_fragmento = true;
            }
            // Se não há fragmentos cadastrados, cai no else abaixo (cristal completo)
        }
        if (!$drop_craft) {
            // Cristal completo (caminho padrão se não rolou fragmento ou não há fragmentos cadastrados)
            $stmt = $conexao->prepare("SELECT * FROM table_usaveis WHERE categoria = 'cristal_craft' ORDER BY RANDOM() LIMIT 1");
            $stmt->execute();
            $craft_sorteado = $stmt->fetch(PDO::FETCH_ASSOC);
            if($craft_sorteado){
                $stmt2 = $conexao->prepare("INSERT INTO usaveis (usuarioid, itemid) VALUES (?, ?)");
                $stmt2->execute([$db['id'], $craft_sorteado['id']]);
                $drop_craft = $craft_sorteado;
            }
        }
    } catch(PDOException $e){ $drop_craft = null; }
}
?>
<?php require_once('verificar.php'); ?>

<div class="box_top">✅ Missão de Clã Concluída — Rank <?php echo $rank_label; ?></div>
<div class="box_middle">
<div style="padding:10px;text-align:center;">
    <img src="<?php echo $rank_img; ?>" alt="Rank <?php echo $rank_label; ?>" style="width:80px;height:80px;object-fit:cover;border:2px solid #c07000;border-radius:6px;margin-bottom:10px;" />
    <div class="aviso" style="margin-bottom:10px;">
        Parabéns! Você concluiu uma <b>Missão Secreta do Clã Rank <?php echo $rank_label; ?></b>!
    </div>
    <div style="background:#1a1a00;border:1px solid #c07000;border-radius:4px;padding:6px;margin-bottom:10px;font-size:11px;color:#aaa;">
        ⏳ Cooldown de 20h iniciado — próxima missão de clã disponível após <?php echo date('d/m H:i', strtotime('+20 hours')); ?>
    </div>

    <table width="60%" cellpadding="6" cellspacing="1" align="center" style="margin-bottom:10px;">
        <tr><td colspan="2"><div class="sep"></div></td></tr>
        <tr class="table_dados" style="background:#2a1a00;">
            <td align="left" style="color:#FFD700;font-weight:bold;">💴 Yens recebidos</td>
            <td align="right" style="color:#90EE90;font-weight:bold;"><?php echo number_format($yens_ganhos,2,',','.'); ?></td>
        </tr>
        <tr class="table_dados" style="background:#2a1a00;">
            <td align="left" style="color:#FFD700;font-weight:bold;">⭐ Experiência</td>
            <td align="right" style="color:#87CEEB;font-weight:bold;"><?php echo $exp_ganha; ?> ponto<?php if($exp_ganha>1) echo 's'; ?></td>
        </tr>
    </table>
</div>

<?php if($drop_frag || $drop_cristal): ?>
<div class="sep"></div>
<div align="center" style="color:#FFD700;font-weight:bold;font-size:15px;text-shadow:1px 1px 2px #000;margin:8px 0;">
    🎲 Itens Encontrados durante a Missão!
</div>
<table width="100%" cellpadding="0" cellspacing="1">

<?php if($drop_frag): ?>
<tr><td colspan="2"><div class="sep"></div></td></tr>
<tr class="table_dados" style="background:#1a2a00;">
    <td align="center" width="100" style="padding:8px;">
        <?php if(!empty($drop_frag['imagem'])): ?>
        <div style="position:relative;display:inline-block;">
            <img src="_img/equipamentos/<?php echo htmlspecialchars($drop_frag['imagem']); ?>.png"
                 onerror="this.src='_img/equipamentos/default.png'"
                 style="width:64px;height:64px;object-fit:contain;filter:brightness(0.6) sepia(1) hue-rotate(200deg);" />
            <div style="position:absolute;bottom:-4px;right:-4px;background:#8B6914;color:#FFD700;font-size:9px;font-weight:bold;padding:1px 4px;border-radius:3px;border:1px solid #FFD700;">FRAG</div>
        </div>
        <?php else: ?>
        <span style="font-size:36px;">🧩</span>
        <?php endif; ?>
    </td>
    <td style="padding:8px;">
        <b style="color:#FFD700;">Fragmento de <?php echo htmlspecialchars($drop_frag['nome']); ?></b>
        <span style="background:#6a4800;color:#FFD700;font-size:10px;font-weight:bold;padding:1px 5px;border-radius:3px;margin-left:5px;">FRAGMENTO</span>
        <br/><span class="sub2">Junte 5 fragmentos do mesmo item no Ferreiro para tentar forjá-lo!</span>
        <br/>
        <?php if(($drop_frag['taijutsu'] ?? 0) > 0): ?>
        <img src="_img/equipamentos/up.png" width="12" align="absmiddle" /> <span style="color:#4ea8e8;">[+<?php echo $drop_frag['taijutsu']; ?>] Taijutsu</span><br/>
        <?php endif; ?>
        <?php if(($drop_frag['ninjutsu'] ?? 0) > 0): ?>
        <img src="_img/equipamentos/up.png" width="12" align="absmiddle" /> <span style="color:#c97fd4;">[+<?php echo $drop_frag['ninjutsu']; ?>] Ninjutsu</span><br/>
        <?php endif; ?>
        <?php if(($drop_frag['genjutsu'] ?? 0) > 0): ?>
        <img src="_img/equipamentos/up.png" width="12" align="absmiddle" /> <span style="color:#5ecf6e;">[+<?php echo $drop_frag['genjutsu']; ?>] Genjutsu</span><br/>
        <?php endif; ?>
        <span style="color:#aaa;font-size:11px;">🎲 Rolagem: <?php echo $roll_equip; ?>/100 — necessário ≤ <?php echo $equip_chance; ?></span>
    </td>
</tr>
<?php endif; ?>

<?php if($drop_cristal): ?>
<tr><td colspan="2"><div class="sep"></div></td></tr>
<tr class="table_dados" style="background:#1a001a;">
    <td align="center" width="100" style="padding:8px;">
        <?php if(!empty($drop_cristal['imagem'])): ?>
        <img src="_img/usaveis/<?php echo htmlspecialchars($drop_cristal['imagem']); ?>.png"
             onerror="this.src='_img/equipamentos/default.png'"
             style="width:64px;height:64px;object-fit:contain;" />
        <?php else: ?>
        <span style="font-size:36px;">💎</span>
        <?php endif; ?>
    </td>
    <td style="padding:8px;">
        <b style="color:#DA70D6;"><?php echo htmlspecialchars($drop_cristal['nome']); ?></b>
        <span style="background:#8B008B;color:#fff;font-size:10px;font-weight:bold;padding:1px 5px;border-radius:3px;margin-left:5px;">CRISTAL</span>
        <br/><span class="sub2"><?php echo htmlspecialchars($drop_cristal['descricao'] ?? ''); ?></span>
        <br/><span style="color:#aaa;font-size:11px;">🎲 Rolagem: <?php echo $roll_cristal; ?>/100 — necessário ≤ <?php echo $cristal_chance; ?></span>
    </td>
</tr>
<?php endif; ?>

</table>
<?php endif; ?>

<?php if($drop_buff): ?>
<tr><td colspan="2"><div class="sep"></div></td></tr>
<tr class="table_dados" style="background:#001a00;">
    <td align="center" width="100" style="padding:8px;">
        <div style="position:relative;display:inline-block;">
            <img src="_img/Buff/<?php echo htmlspecialchars($drop_buff['imagem']); ?>"
                 onerror="this.style.display='none'"
                 style="width:64px;height:64px;object-fit:contain;<?php if($drop_buff_eh_fragmento) echo 'filter:brightness(0.5) sepia(1) hue-rotate(80deg);'; ?>" />
            <?php if($drop_buff_eh_fragmento): ?>
            <div style="position:absolute;bottom:-4px;right:-4px;background:#1a5a00;color:#90EE90;font-size:9px;font-weight:bold;padding:1px 4px;border-radius:3px;border:1px solid #90EE90;">FRAG</div>
            <?php endif; ?>
        </div>
    </td>
    <td style="padding:8px;">
        <?php if($drop_buff_eh_fragmento): ?>
        <b style="color:#90EE90;">Fragmento de <?php echo htmlspecialchars($drop_buff['nome']); ?></b>
        <span style="background:#1a5a00;color:#90EE90;font-size:10px;font-weight:bold;padding:1px 5px;border-radius:3px;margin-left:5px;">FRAGMENTO DE BUFF</span>
        <br/><span class="sub2">Junte 3 fragmentos do mesmo cristal no Inventário para formar um cristal completo!</span>
        <?php else: ?>
        <b style="color:#5ecf6e;"><?php echo htmlspecialchars($drop_buff['nome']); ?></b>
        <span style="background:#1a5a00;color:#5ecf6e;font-size:10px;font-weight:bold;padding:1px 5px;border-radius:3px;margin-left:5px;">CRISTAL DE BUFF</span>
        <br/><span class="sub2">+5% de stat por 3 horas! Ative pelo Inventário.</span>
        <?php endif; ?>
        <br/><span style="color:#aaa;font-size:11px;">🎲 Rolagem: <?php echo $roll_buff; ?>/100 — necessário ≤ 5</span>
    </td>
</tr>
<?php endif; ?>

<?php if($drop_craft):
    // Fragmentos têm imagem em _img/Fragmento de Cristal/; cristais em _img/Craft/
    if ($drop_craft_eh_fragmento) {
        $craft_img_src = '_img/Fragmento%20de%20Cristal/' . rawurlencode($drop_craft['imagem'] ?? '');
    } else {
        $craft_img_src = '_img/Craft/' . htmlspecialchars($drop_craft['imagem'] ?? '');
    }
?>
<tr><td colspan="2"><div class="sep"></div></td></tr>
<tr class="table_dados" style="background:#1a001a;">
    <td align="center" width="100" style="padding:8px;">
        <div style="position:relative;display:inline-block;">
            <img src="<?php echo $craft_img_src; ?>"
                 onerror="this.style.display='none'"
                 style="width:64px;height:64px;object-fit:contain;" />
            <?php if($drop_craft_eh_fragmento): ?>
            <div style="position:absolute;bottom:-4px;right:-4px;background:#5a005a;color:#cf6ecf;font-size:9px;font-weight:bold;padding:1px 4px;border-radius:3px;border:1px solid #cf6ecf;">FRAG</div>
            <?php endif; ?>
        </div>
    </td>
    <td style="padding:8px;">
        <?php if($drop_craft_eh_fragmento): ?>
        <b style="color:#cf6ecf;"><?php echo htmlspecialchars($drop_craft['nome']); ?></b>
        <span style="background:#5a005a;color:#cf6ecf;font-size:10px;font-weight:bold;padding:1px 5px;border-radius:3px;margin-left:5px;">FRAGMENTO DE CRAFT</span>
        <br/><span class="sub2">Junte fragmentos do mesmo tipo no Ferreiro para formar o cristal completo.</span>
        <?php else: ?>
        <b style="color:#cf6ecf;"><?php echo htmlspecialchars($drop_craft['nome']); ?></b>
        <span style="background:#5a005a;color:#cf6ecf;font-size:10px;font-weight:bold;padding:1px 5px;border-radius:3px;margin-left:5px;">CRISTAL DE CRAFT</span>
        <br/><span class="sub2">Use no Ferreiro para criar/aprimorar equipamentos.</span>
        <?php endif; ?>
        <br/><span style="color:#aaa;font-size:11px;">🎲 Rolagem: <?php echo $roll_craft; ?>/100 — necessário ≤ 4</span>
    </td>
</tr>
<?php endif; ?>

<?php if(!$drop_frag && !$drop_cristal && !$drop_buff && !$drop_craft): ?>
<div class="sep"></div>
<div align="center" style="color:#888;font-size:12px;padding:6px;">
    🎲 Desta vez nenhum item foi encontrado. Continue tentando — os drops são raros!
    <br/><span style="font-size:11px;">(Frag: <?php echo $equip_chance; ?>% | Cristal: <?php echo $cristal_chance; ?>% | Cristal Buff: 5% | Cristal Craft: 4%)</span>
</div>
<?php endif; ?>

<div class="sep"></div>
<div align="center">
    <a href="?p=orgmissions" class="botao">Nova Missão de Clã</a>
    &nbsp;&nbsp;
    <a href="?p=blacksmith" class="botao">🔨 Ir ao Ferreiro</a>
    &nbsp;&nbsp;
    <a href="?p=home" class="botao">Voltar à Home</a>
</div>
</div>
<div class="box_bottom"></div>
