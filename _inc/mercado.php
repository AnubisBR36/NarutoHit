<?php
/**
 * ?p=mercado — Mercado dos jogadores
 *
 * - Logado:    lista todos os itens à venda no comércio interno do jogo,
 *              com filtros por moeda. (Mercado ao vivo.)
 * - Deslogado: estatísticas históricas de tudo que já foi vendido —
 *              total por moeda (Yens + cada cristal), top vendedores,
 *              top itens e últimas vendas. (Vitrine pública.)
 *
 * A tabela `mercado_historico` é criada automaticamente se ainda não existir
 * (registra cada compra concluída em shops.php / viewshop.php).
 */

require_once(__DIR__ . '/conexao.php');

// --- garantir tabela de histórico (auto-cria em instalações antigas) ---
try {
    $conexao->exec("CREATE TABLE IF NOT EXISTS `mercado_historico` (
        `id` INT AUTO_INCREMENT,
        `vendedor_id` INT NOT NULL,
        `comprador_id` INT NOT NULL,
        `item_id` INT NOT NULL,
        `item_nome` VARCHAR(150) NOT NULL,
        `item_imagem` VARCHAR(150) DEFAULT '',
        `valor` DOUBLE NOT NULL DEFAULT 0,
        `moeda_tipo` VARCHAR(10) NOT NULL DEFAULT 'yens',
        `data` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_mh_data` (`data`),
        KEY `idx_mh_moeda` (`moeda_tipo`),
        KEY `idx_mh_vendedor` (`vendedor_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Throwable $e) { /* segue mesmo se já existir / sem privilégio */ }

$moeda_labels = [
    'yens'     => 'Yens',
    'cristal1' => 'Cristal de Chakra Refinado',
    'cristal2' => 'Cristal de Chakra Bruto',
    'cristal3' => 'Chakra Forjado',
];
$moeda_icons = [
    'yens'     => '_img/yens.png',
    'cristal1' => '_img/ferreiro/Cristal de Chakra Refinado.png',
    'cristal2' => '_img/ferreiro/Cristal de Chakra Bruto.png',
    'cristal3' => '_img/ferreiro/Chakra Forjado.png',
];

$logado = isset($_SESSION['logado']);

if (!$logado) {
    /* ===========================================================
     * MODO DESLOGADO — Estatísticas Históricas
     * ===========================================================
     */
    $totais_por_moeda = array_fill_keys(array_keys($moeda_labels), ['qtd' => 0, 'soma' => 0.0]);
    try {
        $stmt = $conexao->query("SELECT moeda_tipo, COUNT(*) AS qtd, COALESCE(SUM(valor),0) AS soma FROM mercado_historico GROUP BY moeda_tipo");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $m = $r['moeda_tipo'];
            if (!isset($totais_por_moeda[$m])) continue;
            $totais_por_moeda[$m]['qtd']  = (int)$r['qtd'];
            $totais_por_moeda[$m]['soma'] = (float)$r['soma'];
        }
    } catch (Throwable $e) {}

    $top_vendedores = [];
    try {
        $stmt = $conexao->query(
            "SELECT u.usuario, COUNT(*) AS vendas
             FROM mercado_historico mh
             LEFT JOIN usuarios u ON mh.vendedor_id = u.id
             WHERE u.usuario IS NOT NULL
             GROUP BY mh.vendedor_id, u.usuario
             ORDER BY vendas DESC LIMIT 5"
        );
        $top_vendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}

    $top_itens = [];
    try {
        $stmt = $conexao->query(
            "SELECT item_nome, item_imagem, COUNT(*) AS qtd
             FROM mercado_historico
             GROUP BY item_nome, item_imagem
             ORDER BY qtd DESC LIMIT 10"
        );
        $top_itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}

    $ultimas = [];
    try {
        $stmt = $conexao->query(
            "SELECT mh.item_nome, mh.item_imagem, mh.valor, mh.moeda_tipo, mh.data,
                    uv.usuario AS vendedor, uc.usuario AS comprador
             FROM mercado_historico mh
             LEFT JOIN usuarios uv ON mh.vendedor_id  = uv.id
             LEFT JOIN usuarios uc ON mh.comprador_id = uc.id
             ORDER BY mh.data DESC LIMIT 15"
        );
        $ultimas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}

    $total_geral_transacoes = array_sum(array_column($totais_por_moeda, 'qtd'));
    ?>
    <div class="box_top">📊 Mercado dos Jogadores — Estatísticas</div>
    <div class="box_middle">
        Estas são as estatísticas <b>históricas</b> de tudo o que já foi negociado
        no mercado interno do <?php echo nome_servidor(); ?>. Total de
        <b><?php echo number_format($total_geral_transacoes, 0, ',', '.'); ?></b>
        transações concluídas até agora.
        <div class="sep"></div>

        <?php
        // Gráfico: prepara dados (apenas moedas com transações para o gráfico ficar legível)
        $chart_labels = []; $chart_qtd = []; $chart_soma = []; $chart_colors = [];
        $palette = ['#FFD700', '#7ec8e3', '#c084fc', '#34d399'];
        $idx_pal = 0;
        foreach ($moeda_labels as $tipo => $label) {
            $info = $totais_por_moeda[$tipo];
            if ((int)$info['qtd'] <= 0) continue;
            $chart_labels[] = $label;
            $chart_qtd[]    = (int)$info['qtd'];
            $chart_soma[]   = (float)$info['soma'];
            $chart_colors[] = $palette[$idx_pal % count($palette)];
            $idx_pal++;
        }
        ?>
        <?php if (!empty($chart_labels)): ?>
        <div style="background:#1f1f1f;padding:12px;border:1px solid #3a3a3a;border-radius:6px;">
            <b style="color:#FFD700;">📈 Moedas mais comercializadas</b>
            <div class="sep"></div>
            <div style="display:flex;flex-wrap:wrap;gap:16px;align-items:center;justify-content:center;">
                <div style="width:260px;height:220px;"><canvas id="mc_pizza"></canvas></div>
                <div style="flex:1;min-width:280px;height:220px;"><canvas id="mc_barras"></canvas></div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
        (function(){
            function init(){
                if (typeof Chart === 'undefined') { setTimeout(init, 200); return; }
                var labels = <?php echo json_encode($chart_labels); ?>;
                var qtd    = <?php echo json_encode($chart_qtd); ?>;
                var soma   = <?php echo json_encode($chart_soma); ?>;
                var cores  = <?php echo json_encode($chart_colors); ?>;
                var elP = document.getElementById('mc_pizza');
                var elB = document.getElementById('mc_barras');
                if (elP) {
                    new Chart(elP, {
                        type: 'doughnut',
                        data: { labels: labels, datasets: [{ data: qtd, backgroundColor: cores, borderColor:'#111', borderWidth:2 }] },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom', labels: { color: '#ddd', font: { size: 11 } } },
                                title:  { display: true, text: 'Nº de transações por moeda', color: '#FFD700' }
                            }
                        }
                    });
                }
                if (elB) {
                    new Chart(elB, {
                        type: 'bar',
                        data: { labels: labels, datasets: [{ label: 'Volume movimentado', data: soma, backgroundColor: cores, borderColor:'#111', borderWidth:1 }] },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                title:  { display: true, text: 'Volume total movimentado', color: '#FFD700' }
                            },
                            scales: {
                                x: { ticks: { color: '#ddd' }, grid: { color: '#333' } },
                                y: { ticks: { color: '#ddd' }, grid: { color: '#333' }, beginAtZero: true }
                            }
                        }
                    });
                }
            }
            init();
        })();
        </script>
        <div class="sep"></div>
        <?php endif; ?>

        <table width="100%" cellpadding="6" cellspacing="2">
            <tr><td colspan="2"><b style="color:#FFD700;font-size:13px;">💰 Total movimentado por moeda</b></td></tr>
            <?php foreach ($moeda_labels as $tipo => $label): $info = $totais_por_moeda[$tipo]; ?>
                <tr class="table_dados" style="background:#2a2a2a;">
                    <td width="40%" style="padding:6px 10px;">
                        <img src="<?php echo $moeda_icons[$tipo]; ?>" width="22" height="22" align="absmiddle" />
                        <?php echo htmlspecialchars($label); ?>
                    </td>
                    <td style="padding:6px 10px;">
                        <b><?php echo number_format($info['soma'], 0, ',', '.'); ?></b>
                        em <b><?php echo number_format($info['qtd'], 0, ',', '.'); ?></b>
                        transação<?php echo $info['qtd'] === 1 ? '' : 'ões'; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <div class="sep"></div>

        <table width="100%" cellpadding="0" cellspacing="0">
            <tr valign="top">
                <td width="50%" style="padding-right:8px;">
                    <b style="color:#FFD700;">🏆 Top 5 Vendedores</b>
                    <div class="sep"></div>
                    <table width="100%" cellpadding="4" cellspacing="1">
                        <?php if (empty($top_vendedores)): ?>
                            <tr><td><i>Nenhuma venda registrada ainda.</i></td></tr>
                        <?php else: foreach ($top_vendedores as $i => $v): ?>
                            <tr class="table_dados" style="background:#2a2a2a;">
                                <td width="32" align="center">#<?php echo $i + 1; ?></td>
                                <td><?php echo htmlspecialchars($v['usuario']); ?></td>
                                <td align="right"><b><?php echo number_format($v['vendas'], 0, ',', '.'); ?></b> vendas</td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </table>
                </td>
                <td width="50%" style="padding-left:8px;">
                    <b style="color:#FFD700;">🥇 Itens mais vendidos</b>
                    <div class="sep"></div>
                    <table width="100%" cellpadding="4" cellspacing="1">
                        <?php if (empty($top_itens)): ?>
                            <tr><td><i>Sem dados ainda.</i></td></tr>
                        <?php else: foreach ($top_itens as $it): ?>
                            <tr class="table_dados" style="background:#2a2a2a;">
                                <td width="40" align="center">
                                    <?php if (!empty($it['item_imagem'])): ?>
                                        <img src="_img/equipamentos/<?php echo htmlspecialchars($it['item_imagem']); ?>.png" width="28" height="28" />
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($it['item_nome']); ?></td>
                                <td align="right"><b><?php echo $it['qtd']; ?></b></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </table>
                </td>
            </tr>
        </table>

        <div class="sep"></div>

        <b style="color:#FFD700;">📜 Últimas Vendas</b>
        <div class="sep"></div>
        <table width="100%" cellpadding="4" cellspacing="1">
            <tr class="table_dados" style="background:#1a1a1a;color:#FFD700;">
                <td>Item</td>
                <td>Vendedor</td>
                <td>Comprador</td>
                <td align="right">Preço</td>
                <td align="right">Quando</td>
            </tr>
            <?php if (empty($ultimas)): ?>
                <tr><td colspan="5" align="center"><i>Nenhuma venda histórica registrada ainda.</i></td></tr>
            <?php else: foreach ($ultimas as $u):
                $mt   = $u['moeda_tipo'];
                $micn = $moeda_icons[$mt] ?? '_img/yens.png';
            ?>
                <tr class="table_dados" style="background:#2a2a2a;">
                    <td><?php echo htmlspecialchars($u['item_nome']); ?></td>
                    <td><?php echo htmlspecialchars($u['vendedor'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($u['comprador'] ?? '—'); ?></td>
                    <td align="right">
                        <b><?php echo number_format((float)$u['valor'], 0, ',', '.'); ?></b>
                        <img src="<?php echo $micn; ?>" width="14" height="14" align="absmiddle" />
                    </td>
                    <td align="right"><span class="sub2"><?php echo htmlspecialchars($u['data']); ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
        </table>

        <div class="sep"></div>
        <div align="center" class="sub2">
            Faça <a href="?p=login">login</a> para ver e comprar os itens disponíveis no mercado.
        </div>
    </div>
    <div class="box_bottom"></div>
    <?php
    return;
}

/* ===============================================================
 * MODO LOGADO — Mercado ao vivo de todos os jogadores
 * ===============================================================
 */
$filtro_moeda = isset($_GET['moeda']) && isset($moeda_labels[$_GET['moeda']]) ? $_GET['moeda'] : '';
$ordem        = isset($_GET['ord']) && $_GET['ord'] === 'desc' ? 'DESC' : 'ASC';

$pagina   = max(1, (int)($_GET['pag'] ?? 1));
$por_pag  = 30;
$offset   = ($pagina - 1) * $por_pag;

try {
    $where  = "i.venda='sim'";
    $params = [];
    if ($filtro_moeda !== '') {
        $where   .= " AND i.moeda_tipo = ?";
        $params[] = $filtro_moeda;
    }

    $stmt_total = $conexao->prepare("SELECT COUNT(*) FROM inventario i WHERE $where");
    $stmt_total->execute($params);
    $total = (int)$stmt_total->fetchColumn();

    $sql = "SELECT i.id, i.valor, i.upgrade, i.moeda_tipo, i.usuarioid,
                   t.nome, t.imagem, t.descricao, t.taijutsu, t.ninjutsu, t.genjutsu, t.categoria,
                   u.usuario, u.vila
            FROM inventario i
            LEFT JOIN table_itens t ON i.itemid = t.id
            LEFT JOIN usuarios u    ON i.usuarioid = u.id
            WHERE $where
            ORDER BY i.valor $ordem
            LIMIT $por_pag OFFSET $offset";
    $stmt = $conexao->prepare($sql);
    $stmt->execute($params);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $itens = [];
    $total = 0;
}
$paginas = max(1, (int)ceil($total / $por_pag));
?>
<div class="box_top">🛒 Mercado dos Jogadores</div>
<div class="box_middle">
    Aqui estão todos os itens que os ninjas estão vendendo agora no <?php echo nome_servidor(); ?>.
    Use os filtros para encontrar o que você procura.
    <div class="sep"></div>

    <form method="get" action="?p=mercado" style="margin:6px 0;">
        <input type="hidden" name="p" value="mercado" />
        <b>Moeda:</b>
        <select name="moeda" onchange="this.form.submit();">
            <option value=""<?php echo $filtro_moeda === '' ? ' selected' : ''; ?>>Todas</option>
            <?php foreach ($moeda_labels as $k => $lbl): ?>
                <option value="<?php echo $k; ?>"<?php echo $filtro_moeda === $k ? ' selected' : ''; ?>><?php echo $lbl; ?></option>
            <?php endforeach; ?>
        </select>
        &nbsp;<b>Preço:</b>
        <select name="ord" onchange="this.form.submit();">
            <option value="asc"<?php echo $ordem === 'ASC' ? ' selected' : ''; ?>>Menor → Maior</option>
            <option value="desc"<?php echo $ordem === 'DESC' ? ' selected' : ''; ?>>Maior → Menor</option>
        </select>
        &nbsp;<span class="sub2"><b><?php echo $total; ?></b> item(ns) à venda</span>
    </form>

    <table width="100%" cellpadding="0" cellspacing="1">
        <?php if (empty($itens)): ?>
            <tr><td><div class="aviso">Nenhum item à venda no momento com este filtro.</div></td></tr>
        <?php else: foreach ($itens as $it):
            $mt    = $it['moeda_tipo'] ?? 'yens';
            $micon = $moeda_icons[$mt] ?? '_img/yens.png';
            $mlbl  = $moeda_labels[$mt] ?? 'Yens';
            $eh_meu = ((int)$it['usuarioid'] === (int)$db['id']);
        ?>
            <tr><td><div class="sep"></div></td></tr>
            <tr class="table_dados" style="background:#323232;">
                <td>
                    <table width="100%" cellpadding="0" cellspacing="0"><tr>
                        <td width="80" align="center" valign="top" style="padding:6px;">
                            <img src="_img/equipamentos/<?php echo htmlspecialchars($it['imagem']); ?>.png" />
                        </td>
                        <td valign="top" style="padding:6px;">
                            <b><?php echo htmlspecialchars($it['nome']); ?><?php if ($it['upgrade'] > 0) echo ' +' . (int)$it['upgrade']; ?></b><br />
                            <span class="sub2"><?php echo htmlspecialchars($it['descricao']); ?></span><br />
                            <?php if ($it['taijutsu'] > 0) echo '<b>[+' . ($it['taijutsu'] + $it['upgrade']) . '] Taijutsu</b> '; ?>
                            <?php if ($it['ninjutsu'] > 0) echo '<b>[+' . ($it['ninjutsu'] + $it['upgrade']) . '] Ninjutsu</b> '; ?>
                            <?php if ($it['genjutsu'] > 0) echo '<b>[+' . ($it['genjutsu'] + $it['upgrade']) . '] Genjutsu</b>'; ?>
                            <br /><br />
                            <span style="font-size:13px;">
                                Preço:
                                <b><?php echo number_format((float)$it['valor'], 0, ',', '.'); ?>
                                    <img src="<?php echo $micon; ?>" width="16" height="16" align="absmiddle" />
                                    <?php echo $mlbl; ?></b>
                            </span><br />
                            Vendedor:
                            <a href="?p=view&view=<?php echo strtolower($it['usuario']); ?>"><?php echo htmlspecialchars($it['usuario']); ?></a>
                            &nbsp;·&nbsp;
                            <a href="?p=viewshop&shop=<?php echo urlencode(strtolower($it['usuario'])); ?>">Visitar Loja</a>
                            <?php if (!$eh_meu): ?>
                                &nbsp;·&nbsp;
                                <a href="?p=viewshop&shop=<?php echo urlencode(strtolower($it['usuario'])); ?>&buy=<?php echo (int)$it['id']; ?>" class="botao" style="font-size:11px;padding:2px 8px;">Comprar</a>
                            <?php else: ?>
                                <span class="sub2">&nbsp;(seu item)</span>
                            <?php endif; ?>
                        </td>
                    </tr></table>
                </td>
            </tr>
        <?php endforeach; endif; ?>
    </table>

    <?php if ($paginas > 1): ?>
        <div class="sep"></div>
        <div align="center">
            <?php for ($p = 1; $p <= $paginas; $p++):
                $url = '?p=mercado&pag=' . $p
                     . ($filtro_moeda !== '' ? '&moeda=' . $filtro_moeda : '')
                     . '&ord=' . strtolower($ordem);
                if ($p === $pagina) {
                    echo '<b style="margin:0 4px;color:#FFD700;">' . $p . '</b>';
                } else {
                    echo '<a href="' . $url . '" style="margin:0 4px;">' . $p . '</a>';
                }
            endfor; ?>
        </div>
    <?php endif; ?>
</div>
<div class="box_bottom"></div>
