<?php require_once('trava.php'); ?>
<?php
$dj_nomes = [1 => 'Sharingan', 2 => 'Byakugan', 3 => 'Rinnegan'];
$dj_cores  = [1 => '#CC2200',  2 => '#5599FF',  3 => '#9933CC'];
$dj_borda  = [1 => '#881100',  2 => '#2255AA',  3 => '#660099'];
$dj_imgs   = [1 => '_img/doujutsus/sharingan.jpg', 2 => '_img/doujutsus/byakugan.jpg', 3 => '_img/doujutsus/rinnegan.jpg'];

// Filtro por tipo de doujutsu
$filtro_dj = isset($_GET['dj']) ? (int)$_GET['dj'] : 0;
$ordem     = in_array($_GET['ordem'] ?? '', ['nivel','data']) ? $_GET['ordem'] : 'nivel';

$where_dj = "doujutsu > 0 AND status <> 'banido'";
if($filtro_dj > 0 && isset($dj_nomes[$filtro_dj])) $where_dj .= " AND doujutsu = {$filtro_dj}";
$order_sql = $ordem === 'data'
    ? "ORDER BY h.data ASC"
    : "ORDER BY u.doujutsu_nivel DESC, u.nivel DESC";

// Busca com data do despertar via histórico (LEFT JOIN — pode não ter se desbloqueado antes do feature)
try {
    $top_dj = $conexao->query("
        SELECT u.id, u.usuario, u.nivel, u.vila, u.renegado, u.personagem, u.vip, u.timestamp,
               u.doujutsu, u.doujutsu_nivel, u.doujutsu_exp, u.doujutsu_expmax,
               MIN(h.data) AS data_despertar
        FROM usuarios u
        LEFT JOIN despertar_historico h ON h.usuario_id = u.id AND h.resultado = u.doujutsu
        WHERE u.{$where_dj}
        GROUP BY u.id, u.usuario, u.nivel, u.vila, u.renegado, u.personagem, u.vip, u.timestamp,
                 u.doujutsu, u.doujutsu_nivel, u.doujutsu_exp, u.doujutsu_expmax
        {$order_sql}
        LIMIT 100
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e){ $top_dj = []; }

// Contagens por tipo
try {
    $contagens = $conexao->query("
        SELECT doujutsu, COUNT(*) as total FROM usuarios
        WHERE doujutsu > 0 AND status <> 'banido'
        GROUP BY doujutsu
    ")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch(PDOException $e){ $contagens = []; }

$total_despertos = array_sum($contagens);

// Minha posição
$minha_pos = null;
foreach($top_dj as $idx => $r){
    if((int)$r['id'] === (int)$db['id']){ $minha_pos = $idx + 1; break; }
}

$vilas_img = [1=>'_img/vilas/folha.gif',2=>'_img/vilas/areia.gif',3=>'_img/vilas/som.gif',
              4=>'_img/vilas/chuva.gif',5=>'_img/vilas/nuvem.gif',6=>'_img/vilas/nevoa.gif',8=>'_img/vilas/pedra.gif',7=>'_img/vilas/akatsuki.gif'];
$timeout_online = time() - 900;

$lnk_geral = "?p=rank&filter=" . ($db['renegado']==='sim' ? 7 : (int)$db['vila']);
$lnk_dj    = "?p=rank_doujutsu&ordem={$ordem}";
?>
<div class="box_top">Ranking — Linhagens Despertas</div>
<div class="box_middle">
    Os ninjas que despertaram o poder ancestral de sua linhagem.
    <div class="sep"></div>
    <div align="center">
        <a href="<?php echo $lnk_geral; ?>">Ranking Geral</a> |
        <a href="?p=rank&filter=atributos">
            <img src="_img/Icones/tai.png" style="width:12px;height:12px;vertical-align:middle;">
            <img src="_img/Icones/nin.png" style="width:12px;height:12px;vertical-align:middle;">
            <img src="_img/Icones/gen.png" style="width:12px;height:12px;vertical-align:middle;">
            Atributos
        </a> |
        <a href="?p=rank&filter=pvp">Ranking PVP</a> |
        <b style="color:#CC99FF;">Linhagens</b>
    </div>
    <div class="sep"></div>

    <!-- Filtros por tipo e ordem -->
    <div align="center" style="margin-bottom:8px;">
        <?php
        $estilos_filt = function($ativo){ return $ativo
            ? "display:inline-block;padding:3px 12px;border-radius:10px;font-size:11px;text-decoration:none;border:1px solid #9966CC;color:#CC99FF;background:#1a0a2a;"
            : "display:inline-block;padding:3px 12px;border-radius:10px;font-size:11px;text-decoration:none;border:1px solid #333;color:#666;"; };
        ?>
        <a href="<?php echo $lnk_dj; ?>&dj=0" style="<?php echo $estilos_filt($filtro_dj===0); ?>">
            Todos (<?php echo $total_despertos; ?>)
        </a>
        <?php foreach([1,2,3] as $dj_f): ?>
        <a href="<?php echo $lnk_dj; ?>&dj=<?php echo $dj_f; ?>" style="<?php echo $estilos_filt($filtro_dj===$dj_f); ?>color:<?php echo $dj_cores[$dj_f]; ?>;">
            <?php echo $dj_nomes[$dj_f]; ?> (<?php echo (int)($contagens[$dj_f] ?? 0); ?>)
        </a>
        <?php endforeach; ?>
        <span style="color:#333;margin:0 6px;">|</span>
        Ordenar:
        <a href="<?php echo $lnk_dj; ?>&dj=<?php echo $filtro_dj; ?>&ordem=nivel"
           style="<?php echo $estilos_filt($ordem==='nivel'); ?>">Por Nível</a>
        <a href="<?php echo $lnk_dj; ?>&dj=<?php echo $filtro_dj; ?>&ordem=data"
           style="<?php echo $estilos_filt($ordem==='data'); ?>">Por Data</a>
    </div>
    <div class="sep"></div>

    <?php if($minha_pos && ($filtro_dj === 0 || $filtro_dj === (int)$db['doujutsu'])): ?>
    <div class="aviso">Sua posição: <b><?php echo $minha_pos; ?>º lugar</b> entre os ninjas com Linhagem Desperta</div>
    <div class="sep"></div>
    <?php elseif((int)$db['doujutsu'] === 0): ?>
    <div class="aviso" style="color:#665577;">Você ainda não despertou sua linhagem. <a href="?p=despertar" style="color:#9966CC;">Iniciar Ritual</a></div>
    <div class="sep"></div>
    <?php endif; ?>

    <?php if(empty($top_dj)): ?>
    <div align="center" style="color:#555;padding:20px 0;font-size:13px;">Nenhum ninja despertou esta linhagem ainda.</div>
    <?php else: ?>
    <table width="100%" cellpadding="3" cellspacing="0">
    <tr style="background:#0d0015;">
        <td width="28" align="center" style="font-size:11px;color:#555;">#</td>
        <td style="font-size:11px;color:#999;">Ninja</td>
        <td width="30" align="center"></td>
        <td width="60" align="center" style="font-size:11px;color:#999;">Linhagem</td>
        <td width="40" align="center" style="font-size:11px;color:#999;">Nv. DJ</td>
        <td width="50" align="center" style="font-size:11px;color:#999;">Nível</td>
        <td width="80" align="center" style="font-size:11px;color:#999;">Despertou em</td>
    </tr>
    <tr><td colspan="7"><div class="sep"></div></td></tr>
    <?php foreach($top_dj as $pos => $r):
        $dj  = (int)$r['doujutsu'];
        $vila_img = $r['renegado']==='sim' ? $vilas_img[7] : ($vilas_img[(int)$r['vila']] ?? '');
        $online   = strtotime($r['timestamp'] ?? '0') > $timeout_online;
        $is_vip   = !empty($r['vip']) && date('Y-m-d H:i:s') < $r['vip'];
        $nome_cor = $is_vip ? 'color:#FFD700;' : ($online ? 'color:#EEEEEE;' : 'color:#888888;');
        $exp_pct  = $r['doujutsu_expmax'] > 0 ? min(100, round($r['doujutsu_exp'] / $r['doujutsu_expmax'] * 100)) : 0;
    ?>
    <tr>
        <td align="center">
            <?php if($pos===0) echo '<img src="_img/star.png" style="width:14px;height:14px;vertical-align:middle;" title="1º">';
            elseif($pos===1) echo '<b style="color:#aaa;">2</b>';
            elseif($pos===2) echo '<b style="color:#cd7f32;">3</b>';
            else echo '<span style="color:#444;font-size:11px;">'.($pos+1).'</span>'; ?>
        </td>
        <td>
            <a href="?p=view&id=<?php echo $r['id']; ?>" style="text-decoration:none;<?php echo $nome_cor; ?>font-size:12px;">
                <?php if($is_vip) echo '<b>'; ?>
                <?php echo htmlspecialchars($r['usuario']); ?>
                <?php if($is_vip) echo '</b>'; ?>
            </a>
            <?php if($online) echo '<span style="color:#00CC44;font-size:9px;margin-left:3px;">●</span>'; ?>
        </td>
        <td align="center">
            <?php if($vila_img): ?>
            <img src="<?php echo $vila_img; ?>" style="width:16px;height:16px;vertical-align:middle;" />
            <?php endif; ?>
        </td>
        <td align="center">
            <?php if(isset($dj_imgs[$dj])): ?>
            <span style="display:inline-flex;align-items:center;gap:4px;">
                <img src="<?php echo $dj_imgs[$dj]; ?>" style="width:18px;height:18px;border-radius:50%;border:1px solid <?php echo $dj_borda[$dj]; ?>;vertical-align:middle;">
                <span style="color:<?php echo $dj_cores[$dj]; ?>;font-size:10px;font-weight:bold;"><?php echo $dj_nomes[$dj]; ?></span>
            </span>
            <?php endif; ?>
        </td>
        <td align="center">
            <span style="color:<?php echo $dj_cores[$dj] ?? '#888'; ?>;font-weight:bold;font-size:12px;">
                <?php echo (int)$r['doujutsu_nivel']; ?>
            </span>
            <div style="background:#1a1a1a;border-radius:3px;height:3px;width:40px;margin:2px auto 0;overflow:hidden;">
                <div style="background:<?php echo $dj_cores[$dj] ?? '#888'; ?>;height:3px;width:<?php echo $exp_pct; ?>%;border-radius:3px;"></div>
            </div>
        </td>
        <td align="center" style="color:#888;font-size:11px;"><?php echo (int)$r['nivel']; ?></td>
        <td align="center" style="color:#555;font-size:10px;">
            <?php echo $r['data_despertar'] ? date('d/m/Y', strtotime($r['data_despertar'])) : '—'; ?>
        </td>
    </tr>
    <tr><td colspan="7"><div class="sep"></div></td></tr>
    <?php endforeach; ?>
    </table>
    <?php if(count($top_dj) >= 100): ?>
    <div align="center" style="color:#444;font-size:11px;margin-top:6px;">Exibindo os 100 primeiros resultados.</div>
    <?php endif; ?>
    <?php endif; ?>
</div>
