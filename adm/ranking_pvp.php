<?php
require_once('../_inc/conexao.php');
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['logado']) && !isset($_SESSION['userid']) && !isset($_SESSION['uid'])) {
    header('Location: ../index.php'); exit;
}
$user_id = $_SESSION['logado'] ?? $_SESSION['userid'] ?? $_SESSION['uid'];
try {
    $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $usuario_logado = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { header('Location: adm.php'); exit; }
require_once('_gm_auth.php');

$page_title = 'Ranking PVP';

// ── Parâmetros de filtro ──────────────────────────────────────────────────────
$periodo   = isset($_GET['periodo'])   ? (int)$_GET['periodo']          : 0;   // 0=todos, 7=7 dias, 30=30 dias
$ordenar   = isset($_GET['ordenar'])   ? (string)$_GET['ordenar']       : 'vitorias';
$busca     = isset($_GET['busca'])     ? trim((string)$_GET['busca'])   : '';
$pagina    = max(1, (int)($_GET['pg'] ?? 1));
$por_pagina = 30;
$offset    = ($pagina - 1) * $por_pagina;

$ordens_validas = ['vitorias', 'derrotas', 'total', 'kd', 'nivel'];
if (!in_array($ordenar, $ordens_validas)) $ordenar = 'vitorias';

// ── Filtro de data ────────────────────────────────────────────────────────────
$filtro_data = '';
$filtro_params = [];
if ($periodo > 0) {
    $filtro_data = Database::isMysql()
        ? "AND r.data >= DATE_SUB(NOW(), INTERVAL {$periodo} DAY)"
        : "AND r.data >= DATETIME('now', '-{$periodo} days')";
}

// ── Filtro de nome ────────────────────────────────────────────────────────────
$filtro_nome = '';
if ($busca !== '') {
    $filtro_nome = Database::isMysql()
        ? "AND u.usuario LIKE ?"
        : "AND LOWER(u.usuario) LIKE LOWER(?)";
    $filtro_params[] = '%' . $busca . '%';
}

// ── Ordenação SQL ─────────────────────────────────────────────────────────────
$order_sql = match($ordenar) {
    'derrotas' => 'derrotas DESC, vitorias DESC',
    'total'    => 'total DESC, vitorias DESC',
    'kd'       => 'kd_num DESC, vitorias DESC',
    'nivel'    => 'u.nivel DESC, vitorias DESC',
    default    => 'vitorias DESC, derrotas ASC',
};

// ── Query principal ───────────────────────────────────────────────────────────
$sql_rank = "
    SELECT
        u.id,
        u.usuario,
        u.nivel,
        u.personagem,
        u.vila,
        u.renegado,
        u.avatar,
        SUM(CASE WHEN r.vencedor = u.id THEN 1 ELSE 0 END) AS vitorias,
        SUM(CASE WHEN r.vencedor != u.id THEN 1 ELSE 0 END) AS derrotas,
        COUNT(r.id) AS total,
        CASE
            WHEN SUM(CASE WHEN r.vencedor != u.id THEN 1 ELSE 0 END) = 0
            THEN SUM(CASE WHEN r.vencedor = u.id THEN 1 ELSE 0 END)
            ELSE ROUND(
                SUM(CASE WHEN r.vencedor = u.id THEN 1 ELSE 0 END) * 1.0
                / SUM(CASE WHEN r.vencedor != u.id THEN 1 ELSE 0 END),
            2)
        END AS kd_num
    FROM usuarios u
    INNER JOIN relatorios r ON (r.usuarioid = u.id OR r.inimigoid = u.id)
    WHERE u.avatar > 0
    {$filtro_data}
    {$filtro_nome}
    GROUP BY u.id, u.usuario, u.nivel, u.personagem, u.vila, u.renegado, u.avatar
    HAVING total > 0
    ORDER BY {$order_sql}
";

// Contar total para paginação
$rows_ranking = [];
$total_jogadores = 0;
try {
    $stmt_count = $conexao->prepare("SELECT COUNT(*) FROM ({$sql_rank}) AS sub");
    $stmt_count->execute($filtro_params);
    $total_jogadores = (int)$stmt_count->fetchColumn();

    $stmt_rank = $conexao->prepare("{$sql_rank} LIMIT {$por_pagina} OFFSET {$offset}");
    $stmt_rank->execute($filtro_params);
    $rows_ranking = $stmt_rank->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $rows_ranking = [];
}

$total_paginas = max(1, (int)ceil($total_jogadores / $por_pagina));

// ── Estatísticas globais ──────────────────────────────────────────────────────
$stats = ['total_batalhas' => 0, 'hoje' => 0, 'semana' => 0];
try {
    $r = $conexao->query("SELECT COUNT(*) FROM relatorios")->fetchColumn();
    $stats['total_batalhas'] = (int)$r;

    $hoje_filter = Database::isMysql()
        ? "data >= CURDATE()"
        : "data >= DATE('now')";
    $stats['hoje'] = (int)$conexao->query("SELECT COUNT(*) FROM relatorios WHERE {$hoje_filter}")->fetchColumn();

    $semana_filter = Database::isMysql()
        ? "data >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        : "data >= DATETIME('now', '-7 days')";
    $stats['semana'] = (int)$conexao->query("SELECT COUNT(*) FROM relatorios WHERE {$semana_filter}")->fetchColumn();
} catch (Exception $e) {}

// ── Helpers visuais ───────────────────────────────────────────────────────────
function nome_vila_pvp(int $vila, string $renegado): string {
    $vilas = [1=>'Folha',2=>'Areia',3=>'Som',4=>'Chuva',5=>'Nuvem',6=>'Névoa',8=>'Pedra'];
    $n = $vilas[$vila] ?? '?';
    return $renegado === 'sim' ? "Akatsuki ({$n})" : $n;
}
function kd_color(float $kd): string {
    if ($kd >= 3) return '#FFD700';
    if ($kd >= 1.5) return '#90EE90';
    if ($kd >= 1) return '#87CEFA';
    return '#ff9999';
}
function url_pvp(array $params): string {
    $base = array_merge(['modulo'=>'ranking_pvp'], $params);
    return 'adm.php?' . http_build_query($base);
}
function th_link(string $campo, string $label, string $ordenar_atual, array $base_params): string {
    $ativo = $ordenar_atual === $campo;
    $p = array_merge($base_params, ['ordenar' => $campo, 'pg' => 1]);
    $cor = $ativo ? 'color:#ff6600;' : '';
    $seta = $ativo ? ' ▼' : '';
    return '<a href="' . url_pvp($p) . '" style="color:#FFD700;text-decoration:none;' . $cor . '">' . $label . $seta . '</a>';
}

$base_params = ['periodo' => $periodo, 'ordenar' => $ordenar, 'busca' => $busca];

require_once('adm_header.php');
?>

<div class="adm-page-title">Ranking PVP de Jogadores</div>

<!-- STATS GLOBAIS -->
<div class="stats-row" style="margin-bottom:10px;">
    <div class="stat-box">
        <div class="stat-number"><?php echo number_format($stats['total_batalhas'],0,'.',','); ?></div>
        <div>Batalhas totais</div>
    </div>
    <div class="stat-box">
        <div class="stat-number"><?php echo number_format($stats['hoje'],0,'.',','); ?></div>
        <div>Batalhas hoje</div>
    </div>
    <div class="stat-box">
        <div class="stat-number"><?php echo number_format($stats['semana'],0,'.',','); ?></div>
        <div>Nos últimos 7 dias</div>
    </div>
    <div class="stat-box">
        <div class="stat-number"><?php echo number_format($total_jogadores,0,'.',','); ?></div>
        <div>Jogadores ranqueados</div>
    </div>
</div>

<!-- FILTROS -->
<form method="get" action="adm.php" style="margin-bottom:10px; display:flex; gap:8px; flex-wrap:wrap; align-items:flex-end;">
    <input type="hidden" name="modulo" value="ranking_pvp">

    <div>
        <div style="color:#888;font-size:10px;margin-bottom:2px;">Período</div>
        <select name="periodo">
            <option value="0"  <?php echo $periodo===0  ? 'selected':'' ?>>Todo o tempo</option>
            <option value="7"  <?php echo $periodo===7  ? 'selected':'' ?>>Últimos 7 dias</option>
            <option value="30" <?php echo $periodo===30 ? 'selected':'' ?>>Últimos 30 dias</option>
        </select>
    </div>

    <div>
        <div style="color:#888;font-size:10px;margin-bottom:2px;">Ordenar por</div>
        <select name="ordenar">
            <option value="vitorias" <?php echo $ordenar==='vitorias' ? 'selected':'' ?>>Vitórias</option>
            <option value="derrotas" <?php echo $ordenar==='derrotas' ? 'selected':'' ?>>Derrotas</option>
            <option value="total"    <?php echo $ordenar==='total'    ? 'selected':'' ?>>Total de Batalhas</option>
            <option value="kd"       <?php echo $ordenar==='kd'       ? 'selected':'' ?>>K/D Ratio</option>
            <option value="nivel"    <?php echo $ordenar==='nivel'    ? 'selected':'' ?>>Nível</option>
        </select>
    </div>

    <div>
        <div style="color:#888;font-size:10px;margin-bottom:2px;">Buscar ninja</div>
        <input type="text" name="busca" value="<?php echo htmlspecialchars($busca); ?>" placeholder="Nome do jogador..." style="width:160px;">
    </div>

    <input type="hidden" name="pg" value="1">
    <button type="submit" class="btn-success">Filtrar</button>
    <?php if ($busca !== '' || $periodo > 0 || $ordenar !== 'vitorias'): ?>
    <a href="adm.php?modulo=ranking_pvp" class="btn-danger" style="text-decoration:none;padding:4px 10px;font-size:12px;display:inline-block;">Limpar</a>
    <?php endif; ?>
</form>

<!-- TABELA DE RANKING -->
<?php if (empty($rows_ranking)): ?>
<div class="alert-warning">Nenhum jogador encontrado com os filtros aplicados.</div>
<?php else: ?>

<table class="adm-table">
    <thead>
        <tr>
            <th style="width:36px;">#</th>
            <th><?php echo th_link('nivel', 'Nível', $ordenar, $base_params); ?></th>
            <th>Ninja</th>
            <th>Vila</th>
            <th style="text-align:center;"><?php echo th_link('vitorias', 'Vitórias', $ordenar, $base_params); ?></th>
            <th style="text-align:center;"><?php echo th_link('derrotas', 'Derrotas', $ordenar, $base_params); ?></th>
            <th style="text-align:center;"><?php echo th_link('total', 'Total', $ordenar, $base_params); ?></th>
            <th style="text-align:center;"><?php echo th_link('kd', 'K/D', $ordenar, $base_params); ?></th>
            <th style="text-align:center;">Taxa de Vitória</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows_ranking as $i => $row):
        $pos      = $offset + $i + 1;
        $vitorias = (int)$row['vitorias'];
        $derrotas = (int)$row['derrotas'];
        $total    = (int)$row['total'];
        $kd       = $derrotas > 0 ? round($vitorias / $derrotas, 2) : (float)$vitorias;
        $taxa     = $total > 0 ? round($vitorias / $total * 100, 1) : 0;
        $cor_pos  = match(true) {
            $pos === 1 => '#FFD700',
            $pos === 2 => '#C0C0C0',
            $pos === 3 => '#CD7F32',
            default    => '#666',
        };
        $medalha = match($pos) {
            1 => '1',
            2 => '2',
            3 => '3',
            default => $pos,
        };
    ?>
    <tr>
        <td style="text-align:center;font-weight:bold;color:<?php echo $cor_pos; ?>;font-size:<?php echo $pos<=3?'13px':'11px'; ?>">
            <?php echo $medalha; ?>
        </td>
        <td style="color:#87CEFA;font-weight:bold;"><?php echo (int)$row['nivel']; ?></td>
        <td>
            <?php if ((int)$row['avatar'] > 0): ?>
            <img src="../_img/personagens/<?php echo htmlspecialchars($row['personagem']); ?>/<?php echo (int)$row['avatar']; ?>.jpg"
                 style="width:20px;height:20px;vertical-align:middle;border:1px solid #444;margin-right:5px;">
            <?php endif; ?>
            <b style="color:#fff;"><?php echo htmlspecialchars($row['usuario']); ?></b>
        </td>
        <td style="color:#888;font-size:10px;"><?php echo nome_vila_pvp((int)$row['vila'], (string)$row['renegado']); ?></td>
        <td style="text-align:center;color:#90EE90;font-weight:bold;"><?php echo number_format($vitorias,0,'.',','); ?></td>
        <td style="text-align:center;color:#ff9999;"><?php echo number_format($derrotas,0,'.',','); ?></td>
        <td style="text-align:center;color:#aaa;"><?php echo number_format($total,0,'.',','); ?></td>
        <td style="text-align:center;font-weight:bold;color:<?php echo kd_color($kd); ?>">
            <?php echo number_format($kd,2,'.','.'); ?>
        </td>
        <td style="text-align:center;">
            <div style="background:#222;border-radius:3px;height:12px;width:80px;display:inline-block;vertical-align:middle;overflow:hidden;">
                <div style="background:<?php echo $taxa>=50?'#4CAF50':'#cc3333'; ?>;height:100%;width:<?php echo min(100,$taxa); ?>%;"></div>
            </div>
            <span style="color:#aaa;font-size:10px;margin-left:4px;"><?php echo $taxa; ?>%</span>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<!-- PAGINAÇÃO -->
<?php if ($total_paginas > 1): ?>
<div style="margin-top:8px; display:flex; gap:4px; flex-wrap:wrap; align-items:center;">
    <span style="color:#666;font-size:10px;">Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?> &mdash; <?php echo $total_jogadores; ?> jogadores</span>
    <?php
    $range_start = max(1, $pagina - 3);
    $range_end   = min($total_paginas, $pagina + 3);
    if ($pagina > 1): ?>
        <a href="<?php echo url_pvp(array_merge($base_params, ['pg' => $pagina-1])); ?>" style="color:#FFD700;text-decoration:none;padding:2px 6px;border:1px solid #555;">&laquo;</a>
    <?php endif;
    for ($p = $range_start; $p <= $range_end; $p++):
        $ativo_p = $p === $pagina;
    ?>
        <a href="<?php echo url_pvp(array_merge($base_params, ['pg' => $p])); ?>"
           style="color:<?php echo $ativo_p?'#ff6600':'#FFD700'; ?>;text-decoration:none;padding:2px 6px;border:1px solid <?php echo $ativo_p?'#ff6600':'#555'; ?>;">
            <?php echo $p; ?>
        </a>
    <?php endfor;
    if ($pagina < $total_paginas): ?>
        <a href="<?php echo url_pvp(array_merge($base_params, ['pg' => $pagina+1])); ?>" style="color:#FFD700;text-decoration:none;padding:2px 6px;border:1px solid #555;">&raquo;</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<?php require_once('adm_footer.php'); ?>
