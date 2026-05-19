
<?php require_once('trava.php'); ?>
<?php
// ── Aba PVP — ranking por vitórias em batalhas ────────────────────────────────
if (isset($_GET['filter']) && $_GET['filter'] === 'pvp') {
    if (!isset($_SESSION['servidor_id'])) {
        $stmt_srv = $conexao->prepare("SELECT servidor_id FROM usuarios WHERE id = ?");
        $stmt_srv->execute([$_SESSION['logado']]);
        $srv_row = $stmt_srv->fetch(PDO::FETCH_ASSOC);
        $_SESSION['servidor_id'] = (int)($srv_row['servidor_id'] ?? 0);
    }
    $srv_pvp = (int)$_SESSION['servidor_id'];

    // Período (padrão: todos)
    $pvp_periodo = isset($_GET['pvp_periodo']) ? (int)$_GET['pvp_periodo'] : 0;
    $data_filtro = '';
    if ($pvp_periodo === 7)  $data_filtro = Database::isMysql() ? "AND r.data >= DATE_SUB(NOW(), INTERVAL 7 DAY)"  : "AND r.data >= DATETIME('now','-7 days')";
    if ($pvp_periodo === 30) $data_filtro = Database::isMysql() ? "AND r.data >= DATE_SUB(NOW(), INTERVAL 30 DAY)" : "AND r.data >= DATETIME('now','-30 days')";

    $top_pvp = [];
    $minha_pos_pvp = null;
    try {
        $sql_pvp = "
            SELECT
                u.id,
                u.usuario,
                u.nivel,
                u.vila,
                u.renegado,
                u.personagem,
                u.vip,
                u.timestamp,
                SUM(CASE WHEN r.vencedor = u.id THEN 1 ELSE 0 END)  AS vitorias,
                SUM(CASE WHEN r.vencedor != u.id THEN 1 ELSE 0 END) AS derrotas,
                COUNT(r.id) AS total
            FROM usuarios u
            INNER JOIN relatorios r ON (r.usuarioid = u.id OR r.inimigoid = u.id)
            WHERE u.status <> 'banido'
              AND u.servidor_id = $srv_pvp
              $data_filtro
            GROUP BY u.id, u.usuario, u.nivel, u.vila, u.renegado, u.personagem, u.vip, u.timestamp
            HAVING total > 0
            ORDER BY vitorias DESC, derrotas ASC
            LIMIT 50
        ";
        $stmt_pvp = $conexao->query($sql_pvp);
        $top_pvp = $stmt_pvp->fetchAll(PDO::FETCH_ASSOC);

        // Minha posição
        foreach ($top_pvp as $idx => $r) {
            if ((int)$r['id'] === (int)$db['id']) { $minha_pos_pvp = $idx + 1; break; }
        }
    } catch (Exception $e) { $top_pvp = []; }

    $timeout_pvp = time() - 900;
    $medalhas_pvp = [
        1 => '<img src="_img/star.png" style="width:14px;height:14px;vertical-align:middle;" title="1º">',
        2 => '<b style="color:#aaa;">2</b>',
        3 => '<b style="color:#cd7f32;">3</b>',
    ];
    $vilas_nome = [1=>'Folha',2=>'Areia',3=>'Som',4=>'Chuva',5=>'Nuvem',6=>'Névoa',8=>'Pedra'];
    $filtro_val = $db['renegado'] === 'sim' ? 7 : (int)$db['vila'];
?>
<div class="box_top">Ranking PVP</div>
<div class="box_middle">
    Os ninjas com mais vitórias em batalha deste servidor.<div class="sep"></div>
    <div align="center">
        <a href="?p=rank&filter=<?php echo $filtro_val; ?>">Ranking Geral</a> |
        <a href="?p=rank&filter=atributos">
            <img src="_img/Icones/tai.png" style="width:12px;height:12px;vertical-align:middle;">
            Atributos
        </a> |
        <b>Ranking PVP</b>
    </div>
    <div class="sep"></div>
    <div align="center">
        <form method="get" action="index.php" style="display:inline;">
            <input type="hidden" name="p" value="rank">
            <input type="hidden" name="filter" value="pvp">
            <select name="pvp_periodo" onchange="this.form.submit()">
                <option value="0"  <?php echo $pvp_periodo===0  ? 'selected':'' ?>>Todo o tempo</option>
                <option value="7"  <?php echo $pvp_periodo===7  ? 'selected':'' ?>>Últimos 7 dias</option>
                <option value="30" <?php echo $pvp_periodo===30 ? 'selected':'' ?>>Últimos 30 dias</option>
            </select>
            <noscript><input type="submit" value="Filtrar" class="botao"></noscript>
        </form>
    </div>
    <div class="sep"></div>
    <?php if ($minha_pos_pvp !== null): ?>
    <div class="aviso">Sua posição no PVP: <b><?php echo $minha_pos_pvp; ?>º lugar</b></div>
    <div class="sep"></div>
    <?php endif; ?>
    <?php if (empty($top_pvp)): ?>
    <div class="aviso">Nenhuma batalha registrada neste servidor ainda.</div>
    <?php else: ?>
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr class="table_titulo">
            <td width="25">#</td>
            <td align="left" width="120">Ninja</td>
            <td>Vila</td>
            <td>Nv.</td>
            <td align="center">Vit.</td>
            <td align="center">Der.</td>
            <td align="center">K/D</td>
            <td width="18">&nbsp;</td>
            <td width="40">&nbsp;</td>
        </tr>
        <tr><td colspan="9"><div class="sep"></div></td></tr>
        <?php foreach ($top_pvp as $idx => $r):
            $pos      = $idx + 1;
            $vit      = (int)$r['vitorias'];
            $der      = (int)$r['derrotas'];
            $kd       = $der > 0 ? number_format($vit / $der, 2, '.', '') : $vit . '.00';
            $kd_val   = $der > 0 ? $vit / $der : $vit;
            $kd_cor   = $kd_val >= 3 ? '#FFD700' : ($kd_val >= 1.5 ? '#90EE90' : ($kd_val >= 1 ? '#87CEFA' : '#ff9999'));
            $eu       = ((int)$r['id'] === (int)$db['id']);
            $destaque = $eu ? ' style="background:url(_img/gradient.jpg)"' : '';
            $vila_img = '_img/rank/' . ($r['renegado'] === 'sim' ? '7' : (int)$r['vila']) . '.png';
            $is_vip_r = isset($r['vip']) && date('Y-m-d H:i:s') < $r['vip'];
            $online_r = (int)$r['timestamp'] >= $timeout_pvp;
        ?>
        <tr class="table_dados" height="20"<?php echo $destaque; ?>>
            <td><?php echo isset($medalhas_pvp[$pos]) ? $medalhas_pvp[$pos] : $pos.'º'; ?></td>
            <td align="left">&nbsp;<a href="?p=view&view=<?php echo htmlspecialchars($r['usuario']); ?>"><?php echo htmlspecialchars($r['usuario']); ?></a><?php if($eu): ?> <b style="color:#ff6600;">◄</b><?php endif; ?></td>
            <td><img src="<?php echo htmlspecialchars($vila_img); ?>" /></td>
            <td><b>[<?php echo (int)$r['nivel']; ?>]</b></td>
            <td align="center" style="color:#90EE90;font-weight:bold;"><?php echo $vit; ?></td>
            <td align="center" style="color:#ff9999;"><?php echo $der; ?></td>
            <td align="center" style="color:<?php echo $kd_cor; ?>;font-weight:bold;"><?php echo $kd; ?></td>
            <td><img src="_img/<?php echo $online_r ? 'online' : 'offline'; ?>.png" /></td>
            <td align="right">
                <span style="position:relative;display:inline-block;">
                    <img src="_img/rank/<?php echo htmlspecialchars($r['personagem']); ?>.jpg" <?php if($is_vip_r) echo 'title="VIP"'; ?> />
                    <?php if($is_vip_r): ?><img src="_img/vip.png" alt="VIP" style="position:absolute;top:2px;left:2px;width:18px;height:18px;filter:drop-shadow(0 1px 2px rgba(0,0,0,0.8));pointer-events:none;" /><?php endif; ?>
                </span>
            </td>
        </tr>
        <tr><td colspan="9"><div class="sep"></div></td></tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>
<div class="box_bottom"></div>
<?php
    return;
}

// ── Aba de atributos — top 10 por Tai/Nin/Gen ────────────────────────────────
if (isset($_GET['filter']) && $_GET['filter'] === 'atributos') {
    if (!isset($_SESSION['servidor_id'])) {
        $stmt_srv = $conexao->prepare("SELECT servidor_id FROM usuarios WHERE id = ?");
        $stmt_srv->execute([$_SESSION['logado']]);
        $srv_row = $stmt_srv->fetch(PDO::FETCH_ASSOC);
        $_SESSION['servidor_id'] = (int)($srv_row['servidor_id'] ?? 0);
    }
    $srv_atr = (int)$_SESSION['servidor_id'];
    $cond_atr = "status <> 'banido' AND servidor_id = $srv_atr";

    function _rank_atr_top($conexao, $col, $cond) {
        $stmt = $conexao->prepare("SELECT usuario, personagem, vila, renegado, nivel, $col AS valor FROM usuarios WHERE $cond ORDER BY $col DESC, nivel DESC LIMIT 10");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    $top_tai = _rank_atr_top($conexao, 'taijutsu', $cond_atr);
    $top_nin = _rank_atr_top($conexao, 'ninjutsu', $cond_atr);
    $top_gen = _rank_atr_top($conexao, 'genjutsu', $cond_atr);

    $medalhas_img = [
        1 => '<img src="_img/star.png" style="width:14px;height:14px;vertical-align:middle;" title="1º">',
        2 => '<b style="color:#aaa;">2</b>',
        3 => '<b style="color:#cd7f32;">3</b>',
    ];

    function _rank_atr_tabela($top, $db, $col_label, $col_img, $medalhas_img) {
        echo '<table width="100%" cellpadding="0" cellspacing="0">';
        echo '<tr class="table_titulo">';
        echo '<td width="22">#</td>';
        echo '<td align="left">Ninja</td>';
        echo '<td>Vila</td>';
        echo '<td>Nv.</td>';
        echo '<td align="center"><img src="'.$col_img.'" style="width:16px;height:16px;vertical-align:middle;" title="'.$col_label.'"></td>';
        echo '</tr><tr><td colspan="5"><div class="sep"></div></td></tr>';
        foreach ($top as $i => $r) {
            $pos = $i + 1;
            $destaque = ($r['usuario'] === $db['usuario']) ? ' style="background:url(_img/gradient.jpg)"' : '';
            $vila_img = '_img/rank/' . ($r['renegado'] === 'sim' ? '7' : (int)$r['vila']) . '.png';
            echo '<tr class="table_dados"'.$destaque.'>';
            echo '<td>'.($medalhas_img[$pos] ?? $pos.'º').'</td>';
            echo '<td align="left">&nbsp;<a href="?p=view&view='.htmlspecialchars($r['usuario']).'">'.htmlspecialchars($r['usuario']).'</a></td>';
            echo '<td><img src="'.htmlspecialchars($vila_img).'" /></td>';
            echo '<td><b>['.((int)$r['nivel']).']</b></td>';
            echo '<td align="center" style="color:#FFD700;font-weight:bold;">'.number_format((int)$r['valor'],0,'.',',').'</td>';
            echo '</tr><tr><td colspan="5"><div class="sep"></div></td></tr>';
        }
        echo '</table>';
    }
?>
<div class="box_top">Ranking de Atributos</div>
<div class="box_middle">
    Top 10 jogadores por atributo de combate.<div class="sep"></div>
    <div align="center">
        <a href="?p=rank&filter=<?php echo $db['renegado']==='sim' ? 7 : $db['vila']; ?>">Ranking Geral</a> |
        <b>Atributos</b> |
        <a href="?p=rank&filter=pvp">Ranking PVP</a>
    </div>
    <div class="sep"></div>

    <table width="100%" cellpadding="4" cellspacing="0">
    <tr valign="top">
        <td width="33%">
            <div style="background:#0a0e1a;border:1px solid #4ea8e8;padding:6px 4px 4px;border-radius:4px;">
                <div style="text-align:center;margin-bottom:6px;">
                    <img src="_img/Icones/tai.png" style="width:20px;height:20px;vertical-align:middle;">
                    <b style="color:#4ea8e8;"> Taijutsu</b>
                </div>
                <?php _rank_atr_tabela($top_tai, $db, 'Taijutsu', '_img/Icones/tai.png', $medalhas_img); ?>
            </div>
        </td>
        <td width="33%">
            <div style="background:#0e0a1a;border:1px solid #c97fd4;padding:6px 4px 4px;border-radius:4px;">
                <div style="text-align:center;margin-bottom:6px;">
                    <img src="_img/Icones/nin.png" style="width:20px;height:20px;vertical-align:middle;">
                    <b style="color:#c97fd4;"> Ninjutsu</b>
                </div>
                <?php _rank_atr_tabela($top_nin, $db, 'Ninjutsu', '_img/Icones/nin.png', $medalhas_img); ?>
            </div>
        </td>
        <td width="33%">
            <div style="background:#0a1a0a;border:1px solid #5ecf6e;padding:6px 4px 4px;border-radius:4px;">
                <div style="text-align:center;margin-bottom:6px;">
                    <img src="_img/Icones/gen.png" style="width:20px;height:20px;vertical-align:middle;">
                    <b style="color:#5ecf6e;"> Genjutsu</b>
                </div>
                <?php _rank_atr_tabela($top_gen, $db, 'Genjutsu', '_img/Icones/gen.png', $medalhas_img); ?>
            </div>
        </td>
    </tr>
    </table>
</div>
<div class="box_bottom"></div>
<?php
    return; // não executa o código do rank normal abaixo
}

if(!isset($_GET['filter'])){ echo "<script>self.location='?p=home'</script>"; exit; }
$vilaantiga=$db['vila'];
if($db['renegado']=='sim') $db['vila']=7;
if(!isset($_GET['pg'])) $pg=0; else $pg=$_GET['pg'];
if(($_GET['filter']>7)||($_GET['filter']<0)){ echo "<script>self.location='?p=home'</script>"; exit; }

if (isset($_SESSION['servidor_id'])) {
    $srv_rank = (int)$_SESSION['servidor_id'];
} else {
    $stmt_srv = $conexao->prepare("SELECT servidor_id FROM usuarios WHERE id = ?");
    $stmt_srv->execute([$_SESSION['logado']]);
    $srv_row = $stmt_srv->fetch(PDO::FETCH_ASSOC);
    $srv_rank = (int)($srv_row['servidor_id'] ?? 0);
    $_SESSION['servidor_id'] = $srv_rank;
}
$srv_cond = " AND servidor_id=$srv_rank";
if($_GET['filter']==0) $filtro=" WHERE status<>'banido'".$srv_cond;
if($_GET['filter']==7) $filtro=" WHERE status<>'banido' AND renegado='sim'".$srv_cond;
if(($_GET['filter']==8)or($_GET['filter']>0)&&($_GET['filter']<7)) $filtro=" WHERE status<>'banido' AND renegado='nao' AND vila=".$_GET['filter'].$srv_cond;

if(date('Y-m-d H:i:s')<$db['vip']){
        $posicao=0; $stop=0;
        if(($_GET['filter']==0)or($_GET['filter']==$db['vila'])){
                try {
                        $stmt = $conexao->prepare("SELECT id FROM usuarios".$filtro." ORDER BY nivel DESC, yens_fat DESC, vitorias DESC, derrotas ASC");
                        $stmt->execute();
                        while($dbc = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                if($dbc['id']==$db['id']) {
                                        $stop=1;
                                        break;
                                }
                                $posicao++;
                        }
                } catch (PDOException $e) {
                        // Handle error silently for now
                }
        }
}
$timeout=time()-900;
try {
        $stmt = $conexao->prepare("SELECT usuario, vila, renegado, nivel, vitorias, derrotas, yens_fat, timestamp, personagem, vip FROM usuarios".$filtro." ORDER BY nivel DESC, yens_fat DESC, vitorias DESC, derrotas ASC LIMIT ?,50");
        $stmt->execute([($pg*50)]);
        $dbr = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
        $dbr = false;
}

try {
        $stmt2 = $conexao->prepare("SELECT count(id) as conta FROM usuarios".$filtro);
        $stmt2->execute();
        $dbv = $stmt2->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
        $dbv = ['conta' => 0];
}
?>
<div class="box_top">Ranking</div>
<div class="box_middle">Abaixo está o ranking atual do jogo. Você pode filtrar os ninjas por vila, utilizando o formulário abaixo. Para visualizar sua posição no ranking, utilize o link informado.<div class="sep"></div>
    <div align="center">
        <b>Ranking Geral</b> |
        <a href="?p=rank&filter=atributos">
            <img src="_img/Icones/tai.png" style="width:12px;height:12px;vertical-align:middle;">
            <img src="_img/Icones/nin.png" style="width:12px;height:12px;vertical-align:middle;">
            <img src="_img/Icones/gen.png" style="width:12px;height:12px;vertical-align:middle;">
            Atributos
        </a> |
        <a href="?p=rank&filter=pvp">Ranking PVP</a>
    </div>
    <div class="sep"></div>
        <div align="center">
    <form method="get" action="?p=rank" onsubmit="subm.value='Carregando...';subm.disabled=true;">
    <input type="hidden" id="p" name="p" value="rank" />
    <select id="filter" name="filter">
        <option value="0">Geral</option>
        <option value="1"<?php if($_GET['filter']==1) echo ' selected="selected"'; ?>>Vila da Folha</option>
        <option value="2"<?php if($_GET['filter']==2) echo ' selected="selected"'; ?>>Vila da Areia</option>
        <option value="3"<?php if($_GET['filter']==3) echo ' selected="selected"'; ?>>Vila do Som</option>
        <option value="4"<?php if($_GET['filter']==4) echo ' selected="selected"'; ?>>Vila da Chuva</option>
        <option value="5"<?php if($_GET['filter']==5) echo ' selected="selected"'; ?>>Vila da Nuvem</option>
        <option value="6"<?php if($_GET['filter']==6) echo ' selected="selected"'; ?>>Vila da Névoa</option>
        <option value="8"<?php if($_GET['filter']==8) echo ' selected="selected"'; ?>>Vila da Pedra</option>
        <option value="7"<?php if($_GET['filter']==7) echo ' selected="selected"'; ?>>Akatsuki</option>
    </select>&nbsp;
    <select id="pg" name="pg">
        <?php $i=1; $pag=0; do{ ?>
        <option value="<?php echo $pag; ?>"<?php if((isset($_GET['pg']))&&($_GET['pg']==$pag)) echo ' selected="selected"'; ?>>De <?php echo $i; ?> à <?php echo $i+49; ?></option>
        <?php $i=$i+50; $pag++; } while($i<=$dbv['conta']); ?>
    </select>&nbsp;
    <input type="submit" id="subm" name="subm" class="botao" value="Filtrar" />
    </form>
    </div>
    <div class="sep"></div>
    <?php if(date('Y-m-d H:i:s')<$db['vip']){ ?>
    <?php if(($_GET['filter']==0)or($_GET['filter']==$db['vila'])){ ?>
    <div class="aviso">Sua Posição: <b><?php echo $posicao; ?>&ordm; lugar</b> [<a href="?p=rank&amp;filter=<?php echo $_GET['filter']; ?>&amp;pg=<?php echo floor($posicao/50); ?>">Ver</a>]</div>
    <div class="sep"></div>
    <?php } ?>
    <?php } ?>
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr class="table_titulo">
                <td width="25">#</td>
            <td align="left" width="120">Ninja</td>
            <td>Vila</td>
            <td>Nível</td>
            <td>Vit.</td>
            <td>Der.</td>
            <td align="left">Yens Fat.</td>
            <td>&nbsp;</td>
            <td width="80">&nbsp;</td>
        </tr>
        <tr>
                <td colspan="9"><div class="sep"></div></td>
        </tr>
        <?php $i=1+(($pg)*50); if(!$dbr) echo '<tr><td colspan="8"><div class="aviso">Nenhum ninja encontrado.</div></div></tr>'; else do{ ?>
        <tr class="table_dados" height="20"<?php if($dbr['usuario']==$db['usuario']) echo ' style="background:url(_img/gradient.jpg)"'; ?>>
                <td><?php echo $i; ?>&ordm;</td>
            <td align="left">&nbsp;<a href="?p=view&amp;view=<?php echo $dbr['usuario']; ?>"><?php echo $dbr['usuario']; ?></a></td>
            <td><img src="_img/rank/<?php if($dbr['vila']==10) echo '1'; else { if($dbr['renegado']=='sim') echo '7'; else echo $dbr['vila']; } ?>.png" /></td>
            <td><b>[<?php echo $dbr['nivel']; ?>]</b></td>
            <td><?php echo $dbr['vitorias']; ?></td>
            <td><?php echo $dbr['derrotas']; ?></td>
            <td align="left"><?php echo number_format($dbr['yens_fat'],2,',','.'); ?></td>
            <td width="18"><img src="_img/<?php if($dbr['timestamp']>=$timeout) echo 'online'; else echo 'offline'; ?>.png" /></td>
            <td align="right"><?php 
                $isVipUser = isset($dbr['vip']) && date('Y-m-d H:i:s') < $dbr['vip'];
            ?><span style="position:relative;display:inline-block;"><img src="_img/rank/<?php echo $dbr['personagem']; ?>.jpg" <?php if($isVipUser) echo 'title="VIP"'; ?> /><?php if($isVipUser): ?><img src="_img/vip.png" alt="VIP" style="position:absolute;top:2px;left:2px;width:18px;height:18px;filter:drop-shadow(0 1px 2px rgba(0,0,0,0.8));pointer-events:none;" /><?php endif; ?></span></td>
        </tr>
        <tr>
                <td colspan="9"><div class="sep"></div></td>
        </tr>
        <?php $i++; } while($dbr=$stmt->fetch(PDO::FETCH_ASSOC)); ?>
    </table>
    <div align="center">
    <form method="get" action="?p=rank" onsubmit="subm.value='Carregando...';subm.disabled=true;">
    <input type="hidden" id="p" name="p" value="rank" />
    <select id="filter" name="filter">
        <option value="0">Geral</option>
        <option value="1"<?php if($_GET['filter']==1) echo ' selected="selected"'; ?>>Vila da Folha</option>
        <option value="2"<?php if($_GET['filter']==2) echo ' selected="selected"'; ?>>Vila da Areia</option>
        <option value="3"<?php if($_GET['filter']==3) echo ' selected="selected"'; ?>>Vila do Som</option>
        <option value="4"<?php if($_GET['filter']==4) echo ' selected="selected"'; ?>>Vila da Chuva</option>
        <option value="5"<?php if($_GET['filter']==5) echo ' selected="selected"'; ?>>Vila da Nuvem</option>
        <option value="6"<?php if($_GET['filter']==6) echo ' selected="selected"'; ?>>Vila da Névoa</option>
        <option value="8"<?php if($_GET['filter']==8) echo ' selected="selected"'; ?>>Vila da Pedra</option>
        <option value="7"<?php if($_GET['filter']==7) echo ' selected="selected"'; ?>>Akatsuki</option>
    </select>&nbsp;
    <select id="pg" name="pg">
        <?php $i=1; $pag=0; do{ ?>
        <option value="<?php echo $pag; ?>"<?php if((isset($_GET['pg']))&&($_GET['pg']==$pag)) echo ' selected="selected"'; ?>>De <?php echo $i; ?> à <?php echo $i+49; ?></option>
        <?php $i=$i+50; $pag++; } while($i<=$dbv['conta']); ?>
    </select>&nbsp;
    <input type="submit" id="subm" name="subm" class="botao" value="Filtrar" />
    </form>
    </div>
</div>
<div class="box_bottom"></div>
