<?php
require_once('../_inc/conexao.php');
if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['logado']) && !isset($_SESSION['userid']) && !isset($_SESSION['uid'])) {
    header('Location: ../index.php'); exit;
}
$user_id = $_SESSION['logado'] ?? $_SESSION['userid'] ?? $_SESSION['uid'];
$stmt = $conexao->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$usuario_logado = $stmt->fetch(PDO::FETCH_ASSOC);
$modulo_necessario = 'equipamentos';
require_once('_gm_auth.php');

// ------------------------------------------------------------
// Categorias dinâmicas: tabela + seed + helpers
// ------------------------------------------------------------
$conexao->exec("CREATE TABLE IF NOT EXISTS categorias_equipamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(40) NOT NULL UNIQUE,
    nome VARCHAR(60) NOT NULL,
    emoji VARCHAR(16) DEFAULT '',
    pasta VARCHAR(100) NOT NULL,
    placeholder VARCHAR(80) DEFAULT '',
    ordem INT DEFAULT 100,
    ativo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$cnt_cat = (int)$conexao->query("SELECT COUNT(*) FROM categorias_equipamento")->fetchColumn();
if ($cnt_cat === 0) {
    $defaults = [
        ['arma',       'Arma',       '⚔️', 'Armas',       'Armas/1001',       10],
        ['vestimenta', 'Vestimenta', '👕', 'Roupa',       'Roupa/c-1',        20],
        ['calcado',    'Calçado',    '👟', 'Sapatos',     'Sapatos/c-1',      30],
        ['mascara',    'Máscara',    '🎭', 'Mascara',     'Mascara/n-1',      40],
        ['pergaminho', 'Pergaminho', '📜', 'Pergaminhos', 'Pergaminhos/p-1',  50],
        ['calca',      'Calça',      '👖', 'Calça',       'Calça/l-1',        60],
        ['luva',       'Luva',       '🧤', 'Luva',        'Luva/l-1',         70],
    ];
    $ins = $conexao->prepare("INSERT INTO categorias_equipamento (slug,nome,emoji,pasta,placeholder,ordem) VALUES (?,?,?,?,?,?)");
    foreach ($defaults as $d) $ins->execute($d);
}

function eq_load_categories(PDO $conexao, bool $only_active = true): array {
    $sql = "SELECT * FROM categorias_equipamento" . ($only_active ? " WHERE ativo=1" : "") . " ORDER BY ordem, slug";
    return $conexao->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function eq_normalize_slug(string $s): string {
    // Remove acentos, espaços e caracteres especiais; só [a-z0-9_]
    $s = strtolower(trim($s));
    $tr = ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c'];
    $s = strtr($s, $tr);
    $s = preg_replace('/[^a-z0-9_]+/', '_', $s);
    return trim($s, '_');
}

// Endpoint AJAX: lista as imagens da pasta de uma categoria (para o seletor de miniaturas)
if (isset($_GET['list_images'])) {
    header('Content-Type: application/json; charset=utf-8');
    $slug = preg_replace('/[^a-z0-9_]/i', '', (string)$_GET['list_images']);
    $row = $conexao->prepare("SELECT pasta FROM categorias_equipamento WHERE slug=? LIMIT 1");
    $row->execute([$slug]);
    $folder = (string)($row->fetchColumn() ?: '');
    if ($folder === '') { echo json_encode(['folder' => '', 'images' => []]); exit; }
    $base = __DIR__ . '/../_img/equipamentos/' . $folder;
    $images = [];
    if (is_dir($base)) {
        $files = scandir($base) ?: [];
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            if (preg_match('/\.(png|jpg|jpeg|gif|webp)$/i', $f, $m)) {
                $name = preg_replace('/\.[a-z]+$/i', '', $f);
                $images[] = [
                    'name' => $name,
                    'path' => $folder . '/' . $name,
                    'url'  => '../_img/equipamentos/' . rawurlencode($folder) . '/' . rawurlencode($f),
                ];
            }
        }
        usort($images, function($a, $b){ return strnatcasecmp($a['name'], $b['name']); });
    }
    echo json_encode(['folder' => $folder, 'images' => $images]);
    exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add':
            try {
                $disponivel_shop = $_POST['disponivel_shop'] ?? 'sim';
                $stmt = $conexao->prepare("INSERT INTO table_itens (categoria, nome, descricao, imagem, valor, taijutsu, ninjutsu, genjutsu, reqtai, reqnin, reqgen, vip, disponivel_shop) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['categoria'], $_POST['nome'], $_POST['descricao'], $_POST['imagem'], $_POST['valor'], $_POST['taijutsu'], $_POST['ninjutsu'], $_POST['genjutsu'], $_POST['reqtai'], $_POST['reqnin'], $_POST['reqgen'], $_POST['vip'], $disponivel_shop]);
                $extra = ($disponivel_shop === 'nao') ? ' 🎯 Marcado como EXCLUSIVO — não aparece na loja.' : '';
                $msg = ['type' => 'success', 'text' => '✅ Equipamento adicionado com sucesso!' . $extra];
            } catch (Exception $e) {
                $msg = ['type' => 'error', 'text' => '❌ Erro ao adicionar: ' . htmlspecialchars($e->getMessage())];
            }
            break;
        case 'edit':
            try {
                $disponivel_shop = $_POST['disponivel_shop'] ?? 'sim';
                $stmt = $conexao->prepare("UPDATE table_itens SET categoria=?, nome=?, descricao=?, imagem=?, valor=?, taijutsu=?, ninjutsu=?, genjutsu=?, reqtai=?, reqnin=?, reqgen=?, vip=?, disponivel_shop=? WHERE id=?");
                $stmt->execute([$_POST['categoria'], $_POST['nome'], $_POST['descricao'], $_POST['imagem'], $_POST['valor'], $_POST['taijutsu'], $_POST['ninjutsu'], $_POST['genjutsu'], $_POST['reqtai'], $_POST['reqnin'], $_POST['reqgen'], $_POST['vip'], $disponivel_shop, $_POST['item_id']]);
                $msg = ['type' => 'success', 'text' => '✅ Equipamento atualizado com sucesso!'];
            } catch (Exception $e) {
                $msg = ['type' => 'error', 'text' => '❌ Erro ao atualizar: ' . htmlspecialchars($e->getMessage())];
            }
            break;
        case 'delete':
            try {
                $stmt = $conexao->prepare("DELETE FROM table_itens WHERE id=?");
                $stmt->execute([$_POST['item_id']]);
                $msg = ['type' => 'success', 'text' => '✅ Equipamento removido com sucesso!'];
            } catch (Exception $e) {
                $msg = ['type' => 'error', 'text' => '❌ Erro ao remover: ' . htmlspecialchars($e->getMessage())];
            }
            break;

        // ----- Categorias -----
        case 'cat_add':
            try {
                $slug  = eq_normalize_slug((string)($_POST['cat_slug'] ?? ''));
                $nome  = trim((string)($_POST['cat_nome'] ?? ''));
                $emoji = trim((string)($_POST['cat_emoji'] ?? ''));
                $pasta = trim((string)($_POST['cat_pasta'] ?? ''));
                $ordem = (int)($_POST['cat_ordem'] ?? 100);
                if ($slug === '' || $nome === '' || $pasta === '') {
                    throw new Exception('Slug, nome e pasta são obrigatórios.');
                }
                // Garante que a pasta seja segura (sem ../)
                $pasta = trim($pasta, "/\\");
                if (strpos($pasta, '..') !== false) throw new Exception('Pasta inválida.');
                $placeholder = $pasta . '/exemplo';

                $ins = $conexao->prepare("INSERT INTO categorias_equipamento (slug,nome,emoji,pasta,placeholder,ordem) VALUES (?,?,?,?,?,?)");
                $ins->execute([$slug, $nome, $emoji, $pasta, $placeholder, $ordem]);

                // Cria a pasta no disco se ainda não existir
                $dir = __DIR__ . '/../_img/equipamentos/' . $pasta;
                if (!is_dir($dir)) @mkdir($dir, 0775, true);

                $msg = ['type' => 'success', 'text' => '✅ Categoria "' . htmlspecialchars($nome) . '" criada!' . (is_dir($dir) ? ' Pasta pronta em _img/equipamentos/' . htmlspecialchars($pasta) : '')];
            } catch (Exception $e) {
                $msg = ['type' => 'error', 'text' => '❌ Erro ao criar categoria: ' . htmlspecialchars($e->getMessage())];
            }
            break;
        case 'cat_edit':
            try {
                $cid   = (int)($_POST['cat_id'] ?? 0);
                $nome  = trim((string)($_POST['cat_nome'] ?? ''));
                $emoji = trim((string)($_POST['cat_emoji'] ?? ''));
                $pasta = trim((string)($_POST['cat_pasta'] ?? ''));
                $ordem = (int)($_POST['cat_ordem'] ?? 100);
                $ativo = isset($_POST['cat_ativo']) ? 1 : 0;
                if ($cid <= 0 || $nome === '' || $pasta === '') throw new Exception('Dados incompletos.');
                $pasta = trim($pasta, "/\\");
                if (strpos($pasta, '..') !== false) throw new Exception('Pasta inválida.');

                $upd = $conexao->prepare("UPDATE categorias_equipamento SET nome=?, emoji=?, pasta=?, ordem=?, ativo=? WHERE id=?");
                $upd->execute([$nome, $emoji, $pasta, $ordem, $ativo, $cid]);

                $dir = __DIR__ . '/../_img/equipamentos/' . $pasta;
                if (!is_dir($dir)) @mkdir($dir, 0775, true);

                $msg = ['type' => 'success', 'text' => '✅ Categoria atualizada.'];
            } catch (Exception $e) {
                $msg = ['type' => 'error', 'text' => '❌ Erro ao atualizar categoria: ' . htmlspecialchars($e->getMessage())];
            }
            break;
        case 'cat_delete':
            try {
                $cid = (int)($_POST['cat_id'] ?? 0);
                $row = $conexao->prepare("SELECT slug FROM categorias_equipamento WHERE id=?");
                $row->execute([$cid]);
                $slug = (string)$row->fetchColumn();
                if ($slug === '') throw new Exception('Categoria não encontrada.');
                // Não permite excluir se houver itens com essa categoria
                $u = $conexao->prepare("SELECT COUNT(*) FROM table_itens WHERE categoria=?");
                $u->execute([$slug]);
                $usos = (int)$u->fetchColumn();
                if ($usos > 0) throw new Exception('Existem ' . $usos . ' equipamento(s) usando esta categoria. Mova-os ou desative a categoria em vez de excluir.');
                $conexao->prepare("DELETE FROM categorias_equipamento WHERE id=?")->execute([$cid]);
                $msg = ['type' => 'success', 'text' => '✅ Categoria removida.'];
            } catch (Exception $e) {
                $msg = ['type' => 'error', 'text' => '❌ Erro ao remover categoria: ' . htmlspecialchars($e->getMessage())];
            }
            break;
    }
}

// Carrega categorias (somente ativas para o formulário; todas para o painel de gestão)
$categorias_rows     = eq_load_categories($conexao, true);
$categorias_all_rows = eq_load_categories($conexao, false);
$categories          = array_column($categorias_rows, 'slug');
$cat_meta            = [];           // slug => row (ativas)
$cat_placeholder_map = [];           // slug => placeholder (para o JS)
foreach ($categorias_rows as $cr) {
    $cat_meta[$cr['slug']] = $cr;
    $cat_placeholder_map[$cr['slug']] = $cr['placeholder'] ?: ($cr['pasta'] . '/exemplo');
}

$stats = [];
foreach ($categories as $cat) {
    $s = $conexao->prepare("SELECT COUNT(*) as total FROM table_itens WHERE categoria=?");
    $s->execute([$cat]);
    $stats[$cat] = $s->fetch(PDO::FETCH_ASSOC)['total'];
}

$stmt = $conexao->prepare("SELECT * FROM table_itens ORDER BY categoria, valor ASC");
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Equipamentos';
include 'adm_header.php';
?>
<style>
.eq-stats { display:flex; gap:6px; flex-wrap:wrap; margin:8px 0; }
.eq-stat { background:#1a0a00; border:1px solid #444; padding:8px 12px; text-align:center; flex:1; min-width:70px; }
.eq-stat .num { font-size:20px; font-weight:bold; color:#ff6600; }
.eq-stat .lbl { font-size:10px; color:#888; margin-top:2px; }
.cat-tabs { display:flex; gap:4px; flex-wrap:wrap; margin:8px 0; }
.cat-tab { padding:5px 10px; border:1px solid #444; background:#1a1200; color:#BBBBBB; cursor:pointer; font-size:11px; }
.cat-tab:hover, .cat-tab.active { border-color:#ff6600; color:#FFD700; background:#2a1a00; }
.items-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:8px; margin:8px 0; }
.item-card { background:#1a1200; border:1px solid #333; overflow:hidden; }
.item-card:hover { border-color:#ff6600; }
.item-img { width:100%; height:130px; object-fit:contain; background:#0a0800; padding:6px; }
.item-info { padding:8px 10px; }
.item-name { font-size:13px; font-weight:bold; color:#ff6600; margin-bottom:4px; }
.item-desc { font-size:11px; color:#888; margin-bottom:6px; max-height:30px; overflow:hidden; }
.stat-badge { display:inline-block; padding:2px 7px; background:#0a0800; border:1px solid #444; font-size:10px; margin:1px; color:#BBBBBB; }
.vip-badge { background:#ffd700; color:#000; padding:2px 8px; font-size:10px; font-weight:bold; margin:1px; display:inline-block; }
.excl-badge { background:#7a1a00; color:#ff9966; padding:2px 8px; font-size:10px; font-weight:bold; border:1px solid #ff4400; margin:1px; display:inline-block; }
.loja-badge { background:#1a3a1a; color:#66cc66; padding:2px 8px; font-size:10px; font-weight:bold; border:1px solid #4CAF50; margin:1px; display:inline-block; }
.item-price { color:#90EE90; font-weight:bold; font-size:12px; margin:5px 0; }
.item-actions { display:flex; gap:4px; margin-top:6px; }
.modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.75); }
.modal-content { background:#1a1200; margin:2% auto; padding:20px; border:2px solid #ff6600; width:90%; max-width:620px; max-height:90vh; overflow-y:auto; }
/* Bloco inline (substitui o modal de "Adicionar"). Funciona sem JavaScript. */
.eq-add-block { background:#1a1200; border:2px solid #ff6600; margin:8px 0; }
.eq-add-block > summary { background:#2a1500; color:#ff6600; font-weight:bold; font-size:13px; padding:10px 14px; cursor:pointer; list-style:none; user-select:none; }
.eq-add-block > summary::-webkit-details-marker { display:none; }
.eq-add-block > summary:before { content:'➕ '; }
.eq-add-block[open] > summary:before { content:'➖ '; }
.eq-add-block > summary:hover { background:#3a1c00; }
.eq-add-block .eq-add-body { padding:14px; }
.modal-content h2, .modal-content h3 { color:#ff6600; margin:8px 0 5px 0; font-size:14px; }
.form-group { margin-bottom:12px; }
.form-group label { display:block; margin-bottom:4px; font-size:11px; color:#BBBBBB; font-weight:bold; }
.form-group input, .form-group select, .form-group textarea { width:100%; padding:6px 8px; border:1px solid #555; background:#0a0800; color:#FFFFFF; font-size:12px; box-sizing:border-box; }
.form-group textarea { resize:vertical; min-height:60px; }
.grid-2col { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.modal-close { color:#ff6600; float:right; font-size:24px; font-weight:bold; cursor:pointer; line-height:1; }
.modal-close:hover { color:#FFD700; }
.disp-box { background:#0a0800; border:2px solid #ff4400; padding:10px; margin-bottom:12px; }
.disp-box label { color:#ff9966 !important; }
.img-picker { background:#0a0800; border:1px solid #555; padding:8px; max-height:240px; overflow-y:auto; display:flex; flex-wrap:wrap; gap:6px; }
.img-picker-empty { color:#666; font-size:11px; padding:8px; width:100%; text-align:center; font-style:italic; }
.img-picker-loading { color:#ff9900; font-size:12px; padding:8px; width:100%; text-align:center; }
.img-thumb { width:64px; height:74px; border:2px solid #444; background:#1a1200; padding:2px; cursor:pointer; display:flex; flex-direction:column; align-items:center; justify-content:center; transition:border-color .15s; }
.img-thumb:hover { border-color:#ff9900; }
.img-thumb.selected { border-color:#ff6600; box-shadow:0 0 6px #ff6600; }
.img-thumb img { max-width:54px; max-height:48px; object-fit:contain; }
.img-thumb .nm { color:#aaa; font-size:9px; margin-top:2px; max-width:60px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
/* Tabela de categorias */
.cat-table { width:100%; border-collapse:collapse; font-size:12px; }
.cat-table th { background:#2a1500; color:#ff9900; padding:6px 8px; text-align:left; border-bottom:1px solid #ff6600; font-size:11px; }
.cat-table td { padding:6px 8px; border-bottom:1px solid #333; color:#ccc; }
.cat-table tr:hover td { background:#1a0a00; }
</style>

<?php if ($msg): ?>
<div class="alert-<?php echo $msg['type']; ?>"><?php echo $msg['text']; ?></div>
<div class="sep"></div>
<?php endif; ?>

<div class="eq-stats">
    <?php foreach ($categorias_rows as $cr): ?>
    <div class="eq-stat">
        <div class="num"><?php echo (int)($stats[$cr['slug']] ?? 0); ?></div>
        <div class="lbl"><?php echo htmlspecialchars($cr['nome']); ?></div>
    </div>
    <?php endforeach; ?>
    <div class="eq-stat"><div class="num"><?php echo array_sum($stats); ?></div><div class="lbl">Total</div></div>
</div>

<details class="eq-add-block" id="catBlock">
    <summary>Gerenciar Categorias <span style="color:#888; font-weight:normal; font-size:11px;">(<?php echo count($categorias_all_rows); ?> cadastradas)</span></summary>
    <div class="eq-add-body">
        <div class="cat-list">
            <table class="cat-table">
                <thead>
                    <tr>
                        <th style="width:50px;">Ord.</th>
                        <th>Nome</th>
                        <th>Slug</th>
                        <th>Pasta</th>
                        <th style="width:90px;">Itens</th>
                        <th style="width:60px;">Ativo</th>
                        <th style="width:140px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($categorias_all_rows as $cr):
                    $usos = (int)($stats[$cr['slug']] ?? 0);
                ?>
                    <tr>
                        <td><?php echo (int)$cr['ordem']; ?></td>
                        <td><?php echo htmlspecialchars(($cr['emoji'] ? $cr['emoji'].' ' : '').$cr['nome']); ?></td>
                        <td><code style="font-size:11px; color:#888;"><?php echo htmlspecialchars($cr['slug']); ?></code></td>
                        <td><code style="font-size:11px; color:#aaa;"><?php echo htmlspecialchars($cr['pasta']); ?></code></td>
                        <td><?php echo $usos; ?></td>
                        <td><?php echo $cr['ativo'] ? '<span style="color:#66cc66;">✓</span>' : '<span style="color:#cc6666;">✗</span>'; ?></td>
                        <td>
                            <button type="button" class="botao btn-success js-cat-edit" style="font-size:10px;"
                                data-cat='<?php echo htmlspecialchars(json_encode($cr), ENT_QUOTES, "UTF-8"); ?>'>✏️</button>
                            <?php if ($usos === 0): ?>
                                <button type="button" class="botao btn-danger js-cat-del" style="font-size:10px;"
                                    data-id="<?php echo (int)$cr['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($cr['nome'], ENT_QUOTES, "UTF-8"); ?>">🗑️</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="sep" style="margin:14px 0;"></div>

        <h3 style="color:#ff6600;">➕ Nova Categoria</h3>
        <form method="POST" action="?modulo=equipamentos" id="catAddForm">
            <input type="hidden" name="action" value="cat_add">
            <div class="grid-2col">
                <div class="form-group">
                    <label>Nome <span style="color:#ff4400;">*</span></label>
                    <input type="text" name="cat_nome" required placeholder="Ex: Anel, Colar, Brinco…" id="catNomeInput">
                </div>
                <div class="form-group">
                    <label>Slug <span style="color:#888; font-weight:normal;">(identificador interno, sem acentos/espaços)</span></label>
                    <input type="text" name="cat_slug" required placeholder="auto-gerado a partir do nome" id="catSlugInput" pattern="[a-z0-9_]+">
                </div>
            </div>
            <div class="grid-2col">
                <div class="form-group">
                    <label>Emoji <span style="color:#888; font-weight:normal;">(opcional)</span></label>
                    <input type="text" name="cat_emoji" placeholder="💍" maxlength="8">
                </div>
                <div class="form-group">
                    <label>Pasta de imagens <span style="color:#ff4400;">*</span></label>
                    <input type="text" name="cat_pasta" required placeholder="Ex: Aneis (em _img/equipamentos/)" id="catPastaInput">
                </div>
            </div>
            <div class="grid-2col">
                <div class="form-group">
                    <label>Ordem de exibição</label>
                    <input type="number" name="cat_ordem" value="100" min="0">
                </div>
                <div style="font-size:11px; color:#888; align-self:end; padding-bottom:10px;">
                    A pasta será criada automaticamente em <code>_img/equipamentos/</code> se não existir.
                </div>
            </div>
            <button type="submit" class="botao btn-success" style="width:100%; margin-top:8px; font-size:13px; padding:8px;">➕ Criar Categoria</button>
        </form>
    </div>
</details>

<details class="eq-add-block" id="addBlock"<?php echo (isset($msg) && $msg && ($msg['type'] ?? '') !== 'success') ? ' open' : ''; ?>>
    <summary>Adicionar Novo Equipamento</summary>
    <div class="eq-add-body">
        <form method="POST" action="?modulo=equipamentos" id="addForm">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Categoria</label>
                <select name="categoria" required data-placeholder="add">
                    <?php foreach ($categorias_rows as $cr): ?>
                        <option value="<?php echo htmlspecialchars($cr['slug']); ?>"><?php echo htmlspecialchars(($cr['emoji'] ? $cr['emoji'].' ' : '').$cr['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid-2col">
                <div class="form-group"><label>Nome</label><input type="text" name="nome" required></div>
                <div class="form-group"><label>Imagem (caminho)</label><input type="text" name="imagem" id="addImagePath" required placeholder="Ex: Armas/1001"></div>
            </div>
            <div class="form-group">
                <label>Escolha pelas miniaturas <span style="color:#888; font-weight:normal;">(opcional — clique para preencher o caminho automaticamente)</span></label>
                <div class="img-picker" id="addImgPicker" data-target="addImagePath" data-mode="add">
                    <div class="img-picker-empty">Selecione uma categoria acima para ver as imagens disponíveis.</div>
                </div>
            </div>
            <div class="form-group"><label>Descrição</label><textarea name="descricao" required></textarea></div>
            <div class="grid-2col">
                <div class="form-group"><label>Valor (yens)</label><input type="number" name="valor" step="0.01" required value="0"></div>
                <div class="form-group"><label>Item VIP?</label><select name="vip"><option value="nao">Não</option><option value="sim">Sim</option></select></div>
            </div>
            <div class="disp-box">
                <div class="form-group" style="margin-bottom:0;">
                    <label>🎯 Disponibilidade</label>
                    <select name="disponivel_shop" style="margin-top:6px;">
                        <option value="sim">🛒 Disponível na Loja</option>
                        <option value="nao">🔒 Exclusivo — via Missões de Clã (fragmentos)</option>
                    </select>
                    <div style="color:#888; font-size:10px; margin-top:5px;">Itens Exclusivos não aparecem na loja mas caem como fragmentos nas Missões de Clã.</div>
                </div>
            </div>
            <h3>Atributos Bônus</h3>
            <div class="grid-2col">
                <div class="form-group"><label>Taijutsu</label><input type="number" name="taijutsu" value="0"></div>
                <div class="form-group"><label>Ninjutsu</label><input type="number" name="ninjutsu" value="0"></div>
                <div class="form-group"><label>Genjutsu</label><input type="number" name="genjutsu" value="0"></div>
            </div>
            <h3>Requisitos Mínimos</h3>
            <div class="grid-2col">
                <div class="form-group"><label>Taijutsu Mín.</label><input type="number" name="reqtai" value="0"></div>
                <div class="form-group"><label>Ninjutsu Mín.</label><input type="number" name="reqnin" value="0"></div>
                <div class="form-group"><label>Genjutsu Mín.</label><input type="number" name="reqgen" value="0"></div>
            </div>
            <button type="submit" class="botao btn-success" style="width:100%; margin-top:8px; font-size:13px; padding:8px;">✅ Adicionar Equipamento</button>
        </form>
    </div>
</details>

<div class="sep"></div>

<div class="cat-tabs">
    <button type="button" class="cat-tab active" data-cat="all">Todos</button>
    <?php foreach ($categorias_rows as $cr): ?>
        <button type="button" class="cat-tab" data-cat="<?php echo htmlspecialchars($cr['slug']); ?>"><?php echo htmlspecialchars(($cr['emoji'] ? $cr['emoji'].' ' : '').$cr['nome']); ?></button>
    <?php endforeach; ?>
</div>

<div class="items-grid" id="itemsGrid">
    <?php foreach ($items as $item): ?>
    <div class="item-card" data-cat="<?php echo $item['categoria']; ?>">
        <img src="../_img/equipamentos/<?php echo htmlspecialchars($item['imagem']); ?>.png"
             class="item-img"
             onerror="this.src='../_img/sem_foto.jpg'">
        <div class="item-info">
            <div class="item-name"><?php echo htmlspecialchars($item['nome']); ?></div>
            <div class="item-desc"><?php echo htmlspecialchars($item['descricao']); ?></div>
            <div>
                <?php if ($item['taijutsu'] > 0): ?><span class="stat-badge">💪 Tai +<?php echo $item['taijutsu']; ?></span><?php endif; ?>
                <?php if ($item['ninjutsu'] > 0): ?><span class="stat-badge">🔮 Nin +<?php echo $item['ninjutsu']; ?></span><?php endif; ?>
                <?php if ($item['genjutsu'] > 0): ?><span class="stat-badge">👁️ Gen +<?php echo $item['genjutsu']; ?></span><?php endif; ?>
            </div>
            <div class="item-price">💰 <?php echo number_format($item['valor'], 2, ',', '.'); ?> yens</div>
            <div>
                <?php if ($item['vip'] == 'sim'): ?><span class="vip-badge">⭐ VIP</span><?php endif; ?>
                <?php if (isset($item['disponivel_shop']) && $item['disponivel_shop'] == 'nao'): ?>
                    <span class="excl-badge">🎯 EXCLUSIVO</span>
                <?php else: ?>
                    <span class="loja-badge">🛒 Loja</span>
                <?php endif; ?>
            </div>
            <div class="item-actions">
                <button type="button" class="botao btn-success js-edit" style="font-size:10px; flex:1;" data-item='<?php echo htmlspecialchars(json_encode($item), ENT_QUOTES, "UTF-8"); ?>'>✏️ Editar</button>
                <button type="button" class="botao btn-danger js-del" style="font-size:10px; flex:1;" data-id="<?php echo (int)$item['id']; ?>" data-name="<?php echo htmlspecialchars($item['nome'], ENT_QUOTES, "UTF-8"); ?>">🗑️</button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>


<!-- Modal Editar -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" data-close="editModal">&times;</span>
        <h2>✏️ Editar Equipamento</h2>
        <form method="POST" action="?modulo=equipamentos" id="editForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="item_id" id="editItemId">
            <div class="form-group">
                <label>Categoria</label>
                <select name="categoria" id="editCategoria" required>
                    <?php foreach ($categorias_rows as $cr): ?>
                        <option value="<?php echo htmlspecialchars($cr['slug']); ?>"><?php echo htmlspecialchars(($cr['emoji'] ? $cr['emoji'].' ' : '').$cr['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid-2col">
                <div class="form-group"><label>Nome</label><input type="text" name="nome" id="editNome" required></div>
                <div class="form-group"><label>Imagem (caminho)</label><input type="text" name="imagem" id="editImagem" required></div>
            </div>
            <div class="form-group">
                <label>Escolha pelas miniaturas <span style="color:#888; font-weight:normal;">(opcional — clique para preencher o caminho automaticamente)</span></label>
                <div class="img-picker" id="editImgPicker" data-target="editImagem" data-mode="edit">
                    <div class="img-picker-empty">Selecione uma categoria acima para ver as imagens disponíveis.</div>
                </div>
            </div>
            <div class="form-group"><label>Descrição</label><textarea name="descricao" id="editDescricao" required></textarea></div>
            <div class="grid-2col">
                <div class="form-group"><label>Valor (yens)</label><input type="number" name="valor" id="editValor" step="0.01" required></div>
                <div class="form-group"><label>Item VIP?</label><select name="vip" id="editVip"><option value="nao">Não</option><option value="sim">Sim</option></select></div>
            </div>
            <div class="disp-box">
                <div class="form-group" style="margin-bottom:0;">
                    <label>🎯 Disponibilidade</label>
                    <select name="disponivel_shop" id="editDisponivelShop" style="margin-top:6px;">
                        <option value="sim">🛒 Disponível na Loja</option>
                        <option value="nao">🔒 Exclusivo — via Missões de Clã (fragmentos)</option>
                    </select>
                </div>
            </div>
            <h3>Atributos Bônus</h3>
            <div class="grid-2col">
                <div class="form-group"><label>Taijutsu</label><input type="number" name="taijutsu" id="editTaijutsu"></div>
                <div class="form-group"><label>Ninjutsu</label><input type="number" name="ninjutsu" id="editNinjutsu"></div>
                <div class="form-group"><label>Genjutsu</label><input type="number" name="genjutsu" id="editGenjutsu"></div>
            </div>
            <h3>Requisitos Mínimos</h3>
            <div class="grid-2col">
                <div class="form-group"><label>Taijutsu Mín.</label><input type="number" name="reqtai" id="editReqtai"></div>
                <div class="form-group"><label>Ninjutsu Mín.</label><input type="number" name="reqnin" id="editReqnin"></div>
                <div class="form-group"><label>Genjutsu Mín.</label><input type="number" name="reqgen" id="editReqgen"></div>
            </div>
            <button type="submit" class="botao btn-success" style="width:100%; margin-top:8px; font-size:13px; padding:8px;">💾 Salvar Alterações</button>
        </form>
    </div>
</div>

<!-- Modal Editar Categoria -->
<div id="catEditModal" class="modal">
    <div class="modal-content" style="max-width:520px;">
        <span class="modal-close" data-close="catEditModal">&times;</span>
        <h2>✏️ Editar Categoria</h2>
        <form method="POST" action="?modulo=equipamentos">
            <input type="hidden" name="action" value="cat_edit">
            <input type="hidden" name="cat_id" id="catEditId">
            <div class="grid-2col">
                <div class="form-group"><label>Nome</label><input type="text" name="cat_nome" id="catEditNome" required></div>
                <div class="form-group"><label>Slug <span style="color:#888; font-weight:normal;">(não editável)</span></label><input type="text" id="catEditSlug" disabled style="opacity:.6;"></div>
            </div>
            <div class="grid-2col">
                <div class="form-group"><label>Emoji</label><input type="text" name="cat_emoji" id="catEditEmoji" maxlength="8"></div>
                <div class="form-group"><label>Pasta de imagens</label><input type="text" name="cat_pasta" id="catEditPasta" required></div>
            </div>
            <div class="grid-2col">
                <div class="form-group"><label>Ordem</label><input type="number" name="cat_ordem" id="catEditOrdem" min="0"></div>
                <div class="form-group" style="display:flex; align-items:center; padding-top:18px;">
                    <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="checkbox" name="cat_ativo" id="catEditAtivo" value="1"> Categoria ativa
                    </label>
                </div>
            </div>
            <div style="font-size:11px; color:#888; margin-bottom:8px;">Desativar uma categoria a esconde do formulário e dos filtros, mas mantém os itens existentes.</div>
            <button type="submit" class="botao btn-success" style="width:100%; margin-top:8px; font-size:13px; padding:8px;">💾 Salvar Categoria</button>
        </form>
    </div>
</div>

<script>
window.EQ_PLACEHOLDER_MAP = <?php echo json_encode($cat_placeholder_map, JSON_UNESCAPED_UNICODE); ?>;
(function(){
    function openEditModal(item) {
        document.getElementById('editItemId').value = item.id;
        document.getElementById('editCategoria').value = item.categoria;
        document.getElementById('editNome').value = item.nome;
        document.getElementById('editImagem').value = item.imagem;
        // popula o seletor de imagens com a categoria atual
        if (typeof window._loadImagePicker === 'function') window._loadImagePicker('edit');
        updatePlaceholder('edit');
        document.getElementById('editDescricao').value = item.descricao;
        document.getElementById('editValor').value = item.valor;
        document.getElementById('editVip').value = item.vip;
        document.getElementById('editDisponivelShop').value = item.disponivel_shop || 'sim';
        document.getElementById('editTaijutsu').value = item.taijutsu;
        document.getElementById('editNinjutsu').value = item.ninjutsu;
        document.getElementById('editGenjutsu').value = item.genjutsu;
        document.getElementById('editReqtai').value = item.reqtai;
        document.getElementById('editReqnin').value = item.reqnin;
        document.getElementById('editReqgen').value = item.reqgen;
        document.getElementById('editModal').style.display = 'block';
    }
    function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }
    function deleteItem(id, name) {
        if (confirm('Remover "' + name + '"?')) {
            var f = document.createElement('form');
            f.method = 'POST';
            f.action = '?modulo=equipamentos';
            f.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="item_id" value="' + id + '">';
            document.body.appendChild(f);
            f.submit();
        }
    }
    function filterCat(cat, btn) {
        document.querySelectorAll('.item-card').forEach(function(c){ c.style.display = (cat === 'all' || c.dataset.cat === cat) ? '' : 'none'; });
        document.querySelectorAll('.cat-tab').forEach(function(t){ t.classList.toggle('active', t === btn); });
    }
    function updatePlaceholder(mode) {
        var map = window.EQ_PLACEHOLDER_MAP || {};
        var sel = mode === 'add' ? document.querySelector('#addForm select[name="categoria"]') : document.querySelector('#editForm select[name="categoria"]');
        var inp = mode === 'add' ? document.getElementById('addImagePath') : document.getElementById('editImagem');
        if (sel && inp) inp.placeholder = 'Ex: ' + (map[sel.value] || '');
    }

    // ----- Categorias: auto-slug, edit, delete -----
    function slugify(s) {
        return (s || '').toLowerCase().trim()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')   // remove acentos
            .replace(/[^a-z0-9_]+/g, '_').replace(/^_+|_+$/g, '');
    }
    function openCatEdit(cat) {
        document.getElementById('catEditId').value     = cat.id;
        document.getElementById('catEditNome').value   = cat.nome;
        document.getElementById('catEditSlug').value   = cat.slug;
        document.getElementById('catEditEmoji').value  = cat.emoji || '';
        document.getElementById('catEditPasta').value  = cat.pasta;
        document.getElementById('catEditOrdem').value  = cat.ordem;
        document.getElementById('catEditAtivo').checked = (parseInt(cat.ativo, 10) === 1);
        document.getElementById('catEditModal').style.display = 'block';
    }
    function deleteCat(id, name) {
        if (!confirm('Remover a categoria "' + name + '"?\n\nIsso só funciona se nenhum equipamento estiver usando.')) return;
        var f = document.createElement('form');
        f.method = 'POST';
        f.action = '?modulo=equipamentos';
        f.innerHTML = '<input type="hidden" name="action" value="cat_delete"><input type="hidden" name="cat_id" value="' + id + '">';
        document.body.appendChild(f);
        f.submit();
    }

    // Cache de imagens por categoria para evitar requisições repetidas
    var imageCache = {};
    function loadImagePicker(mode) {
        var sel = mode === 'add' ? document.querySelector('#addForm select[name="categoria"]') : document.querySelector('#editForm select[name="categoria"]');
        var picker = mode === 'add' ? document.getElementById('addImgPicker') : document.getElementById('editImgPicker');
        var input  = document.getElementById(picker.dataset.target);
        if (!sel || !picker) return;
        var cat = sel.value;
        picker.innerHTML = '<div class="img-picker-loading">Carregando imagens…</div>';

        function render(data) {
            picker.innerHTML = '';
            if (!data.images || data.images.length === 0) {
                picker.innerHTML = '<div class="img-picker-empty">Nenhuma imagem encontrada na pasta "' + (data.folder || '?') + '".</div>';
                return;
            }
            var current = (input.value || '').trim();
            data.images.forEach(function(img){
                var div = document.createElement('div');
                div.className = 'img-thumb';
                if (current === img.path) div.classList.add('selected');
                div.title = img.path;
                div.dataset.path = img.path;
                var imEl = document.createElement('img');
                imEl.src = img.url;
                imEl.alt = img.name;
                imEl.loading = 'lazy';
                imEl.onerror = function(){ this.style.opacity='0.25'; };
                var nm = document.createElement('div');
                nm.className = 'nm';
                nm.textContent = img.name;
                div.appendChild(imEl);
                div.appendChild(nm);
                div.addEventListener('click', function(){
                    input.value = img.path;
                    picker.querySelectorAll('.img-thumb.selected').forEach(function(t){ t.classList.remove('selected'); });
                    div.classList.add('selected');
                });
                picker.appendChild(div);
            });
        }

        if (imageCache[cat]) { render(imageCache[cat]); return; }
        fetch('?modulo=equipamentos&list_images=' + encodeURIComponent(cat), {credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(data){ imageCache[cat] = data; render(data); })
            .catch(function(){ picker.innerHTML = '<div class="img-picker-empty">Erro ao carregar imagens.</div>'; });
    }
    // expor para o openEditModal
    window._loadImagePicker = loadImagePicker;

    document.addEventListener('DOMContentLoaded', function(){
        document.querySelectorAll('[data-close]').forEach(function(el){
            el.addEventListener('click', function(){
                var id = el.getAttribute('data-close');
                if (id === 'editModal') closeEditModal();
                else { var m = document.getElementById(id); if (m) m.style.display = 'none'; }
            });
        });

        // Auto-slug enquanto digita o nome (mas só se o usuário ainda não tocou no slug)
        var nomeIn = document.getElementById('catNomeInput');
        var slugIn = document.getElementById('catSlugInput');
        var pastaIn = document.getElementById('catPastaInput');
        var slugTouched = false;
        if (slugIn) slugIn.addEventListener('input', function(){ slugTouched = true; });
        if (nomeIn && slugIn) {
            nomeIn.addEventListener('input', function(){
                if (!slugTouched) slugIn.value = slugify(nomeIn.value);
                if (pastaIn && !pastaIn.value) {
                    var n = (nomeIn.value || '').trim();
                    if (n) pastaIn.value = n.charAt(0).toUpperCase() + n.slice(1) + 's';
                }
            });
        }

        // Editar / excluir categoria
        document.querySelectorAll('.js-cat-edit').forEach(function(btn){
            btn.addEventListener('click', function(){
                try { openCatEdit(JSON.parse(btn.getAttribute('data-cat'))); }
                catch(e){ alert('Erro ao abrir categoria.'); }
            });
        });
        document.querySelectorAll('.js-cat-del').forEach(function(btn){
            btn.addEventListener('click', function(){
                deleteCat(parseInt(btn.getAttribute('data-id'), 10), btn.getAttribute('data-name') || '');
            });
        });

        document.querySelectorAll('.cat-tab').forEach(function(btn){
            btn.addEventListener('click', function(){ filterCat(btn.dataset.cat, btn); });
        });

        document.querySelectorAll('.js-edit').forEach(function(btn){
            btn.addEventListener('click', function(){
                try { openEditModal(JSON.parse(btn.getAttribute('data-item'))); }
                catch(e){ alert('Erro ao abrir item para edição.'); }
            });
        });

        document.querySelectorAll('.js-del').forEach(function(btn){
            btn.addEventListener('click', function(){
                deleteItem(parseInt(btn.getAttribute('data-id'), 10), btn.getAttribute('data-name') || '');
            });
        });

        document.querySelectorAll('select[data-placeholder]').forEach(function(sel){
            sel.addEventListener('change', function(){
                var mode = sel.getAttribute('data-placeholder');
                updatePlaceholder(mode);
                loadImagePicker(mode);
            });
        });

        // Categoria do modal de edição também deve disparar o picker
        var editCat = document.getElementById('editCategoria');
        if (editCat) {
            editCat.addEventListener('change', function(){ loadImagePicker('edit'); });
        }

        // Popula o picker da seção "Adicionar" no carregamento
        updatePlaceholder('add');
        loadImagePicker('add');
    });

    document.addEventListener('click', function(e){
        if (e.target && e.target.classList && e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });
})();
</script>

<?php include 'adm_footer.php'; ?>
