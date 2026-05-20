<?php
/**
 * adm/gerenciar_jutsus.php
 *
 * Painel ADM para gerenciar JUTSUS:
 *   • Listar, criar, editar e excluir jutsus (tabela `table_jutsus`)
 *   • Upload de imagem por jutsu (_img/jutsus/{id}.jpg)
 *
 * Acesso: adm/adm.php?modulo=jutsus
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../_inc/conexao.php';

if (empty($_SESSION['logado']) || empty($_SESSION['adm']) || !in_array((int)$_SESSION['adm'], [1, 2], true)) {
    header('Location: ../index.php?p=login'); exit;
}

$img_dir   = realpath(__DIR__ . '/../_img/jutsus');
$img_url   = '../_img/jutsus/';
$msg       = '';
$msg_ok    = false;

$naturezas_opcoes = ['nenhum','fogo','agua','terra','vento','raio'];
$doujutsus_opcoes = [0=>'Nenhum', 1=>'Sharingan', 2=>'Byakugan', 3=>'Rinnegan'];

// ─── PROCESSAR AÇÕES POST ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // ── CRIAR ────────────────────────────────────────────────────────────────
    if ($acao === 'criar') {
        $nome         = trim($_POST['nome'] ?? '');
        $nivel        = max(1, (int)($_POST['nivel'] ?? 1));
        $natureza     = in_array($_POST['natureza'] ?? '', $naturezas_opcoes) ? $_POST['natureza'] : 'nenhum';
        $forca        = max(0, (int)($_POST['forca'] ?? 0));
        $valor        = max(0.0, (float)($_POST['valor'] ?? 0));
        $doujutsu     = (int)($_POST['doujutsu'] ?? 0);
        $doujutsu_nivel = (int)($_POST['doujutsu_nivel'] ?? 0);
        $texto        = trim($_POST['texto'] ?? '');

        if ($nome === '') {
            $msg = 'O nome do jutsu é obrigatório.';
        } else {
            $stmt = $conexao->prepare("INSERT INTO table_jutsus (nome, nivel, natureza, forca, valor, doujutsu, doujutsu_nivel, texto) VALUES (?,?,?,?,?,?,?,?)");
            if ($stmt->execute([$nome, $nivel, $natureza, $forca, $valor, $doujutsu, $doujutsu_nivel, $texto])) {
                $novo_id = (int)$conexao->lastInsertId();
                // Upload de imagem
                if (!empty($_FILES['imagem']['tmp_name']) && $img_dir) {
                    $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                        $dest = $img_dir . '/' . $novo_id . '.jpg';
                        move_uploaded_file($_FILES['imagem']['tmp_name'], $dest);
                    }
                }
                $msg = "Jutsu <b>" . htmlspecialchars($nome) . "</b> criado com sucesso! (ID: $novo_id)";
                $msg_ok = true;
            } else {
                $msg = 'Erro ao criar jutsu.';
            }
        }
    }

    // ── EDITAR ────────────────────────────────────────────────────────────────
    if ($acao === 'editar') {
        $id           = (int)($_POST['id'] ?? 0);
        $nome         = trim($_POST['nome'] ?? '');
        $nivel        = max(1, (int)($_POST['nivel'] ?? 1));
        $natureza     = in_array($_POST['natureza'] ?? '', $naturezas_opcoes) ? $_POST['natureza'] : 'nenhum';
        $forca        = max(0, (int)($_POST['forca'] ?? 0));
        $valor        = max(0.0, (float)($_POST['valor'] ?? 0));
        $doujutsu     = (int)($_POST['doujutsu'] ?? 0);
        $doujutsu_nivel = (int)($_POST['doujutsu_nivel'] ?? 0);
        $texto        = trim($_POST['texto'] ?? '');

        if ($id <= 0 || $nome === '') {
            $msg = 'Dados inválidos.';
        } else {
            $stmt = $conexao->prepare("UPDATE table_jutsus SET nome=?, nivel=?, natureza=?, forca=?, valor=?, doujutsu=?, doujutsu_nivel=?, texto=? WHERE id=?");
            $stmt->execute([$nome, $nivel, $natureza, $forca, $valor, $doujutsu, $doujutsu_nivel, $texto, $id]);
            // Upload de imagem
            if (!empty($_FILES['imagem']['tmp_name']) && $img_dir) {
                $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                    $dest = $img_dir . '/' . $id . '.jpg';
                    move_uploaded_file($_FILES['imagem']['tmp_name'], $dest);
                }
            }
            $msg = "Jutsu <b>" . htmlspecialchars($nome) . "</b> atualizado com sucesso!";
            $msg_ok = true;
        }
    }

    // ── EXCLUIR ───────────────────────────────────────────────────────────────
    if ($acao === 'excluir') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $jrow = $conexao->prepare("SELECT nome FROM table_jutsus WHERE id=?");
            $jrow->execute([$id]);
            $jrow = $jrow->fetch(PDO::FETCH_ASSOC);
            $conexao->prepare("DELETE FROM table_jutsus WHERE id=?")->execute([$id]);
            $conexao->prepare("DELETE FROM jutsus WHERE jutsu=?")->execute([$id]);
            // Remove imagem
            $img_path = $img_dir . '/' . $id . '.jpg';
            if (file_exists($img_path)) @unlink($img_path);
            $msg = "Jutsu <b>" . htmlspecialchars($jrow['nome'] ?? "#$id") . "</b> excluído.";
            $msg_ok = true;
        }
    }
}

// ─── CARREGAR JUTSUS ─────────────────────────────────────────────────────────
$filtro_nat  = $_GET['nat'] ?? '';
$filtro_dou  = isset($_GET['dou']) ? (int)$_GET['dou'] : -1;
$busca       = trim($_GET['q'] ?? '');
$editar_id   = (int)($_GET['editar'] ?? 0);

$where = '1=1';
$params = [];
if ($filtro_nat !== '' && in_array($filtro_nat, $naturezas_opcoes)) {
    $where .= ' AND natureza=?'; $params[] = $filtro_nat;
}
if ($filtro_dou >= 0) {
    $where .= ' AND doujutsu=?'; $params[] = $filtro_dou;
}
if ($busca !== '') {
    $where .= ' AND nome LIKE ?'; $params[] = '%' . $busca . '%';
}

$stmt = $conexao->prepare("SELECT * FROM table_jutsus WHERE $where ORDER BY natureza, nivel ASC");
$stmt->execute($params);
$jutsus = $stmt->fetchAll(PDO::FETCH_ASSOC);

$editar_jutsu = null;
if ($editar_id > 0) {
    $s = $conexao->prepare("SELECT * FROM table_jutsus WHERE id=?");
    $s->execute([$editar_id]);
    $editar_jutsu = $s->fetch(PDO::FETCH_ASSOC);
}

// Contar quantos jogadores têm cada jutsu
$stmt_uso = $conexao->query("SELECT jutsu, COUNT(*) as total FROM jutsus GROUP BY jutsu");
$uso_map  = $stmt_uso->fetchAll(PDO::FETCH_KEY_PAIR);

// Cor por natureza
$nat_cor = ['nenhum'=>'#888','fogo'=>'#CC4400','agua'=>'#2255CC','terra'=>'#996600','vento'=>'#44AA44','raio'=>'#9922CC'];
$nat_label = ['nenhum'=>'Nenhuma','fogo'=>'Fogo','agua'=>'Água','terra'=>'Terra','vento'=>'Vento','raio'=>'Raio'];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Gerenciar Jutsus — Admin</title>
<link rel="stylesheet" href="../_css/naruto.css">
<style>
body { background:#1a1a1a; }
.adm-wrap { max-width:960px; margin:20px auto; }
.msg-ok  { background:#1a3a1a; border:1px solid #33aa33; color:#66ff66; padding:8px 12px; margin-bottom:12px; border-radius:4px; }
.msg-err { background:#3a1a1a; border:1px solid #aa3333; color:#ff6666; padding:8px 12px; margin-bottom:12px; border-radius:4px; }
.jutsu-img { width:50px; height:50px; object-fit:cover; border-radius:4px; border:1px solid #444; vertical-align:middle; }
.jutsu-img-missing { width:50px; height:50px; background:#333; border-radius:4px; border:1px dashed #555; display:inline-block; vertical-align:middle; }
.nat-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:bold; }
.filter-bar { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; align-items:center; }
.filter-bar a, .filter-bar span { padding:3px 10px; border:1px solid #555; border-radius:10px; font-size:12px; color:#aaa; text-decoration:none; cursor:pointer; }
.filter-bar a.ativo { border-color:#FF9900; color:#FF9900; font-weight:bold; }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.form-grid label { display:flex; flex-direction:column; gap:4px; font-size:12px; color:#ccc; }
.form-grid input, .form-grid select, .form-grid textarea { background:#2a2a2a; border:1px solid #555; color:#fff; padding:5px 8px; border-radius:4px; font-size:13px; }
.form-grid textarea { grid-column:1/-1; height:60px; resize:vertical; }
.form-full { grid-column:1/-1; }
.btn-acao { padding:5px 14px; border:1px solid; border-radius:4px; cursor:pointer; font-size:12px; font-weight:bold; text-decoration:none; display:inline-block; }
.btn-edit { border-color:#FF9900; color:#FF9900; background:transparent; }
.btn-del  { border-color:#CC3333; color:#CC3333; background:transparent; }
.btn-save { border-color:#33AA33; color:#33AA33; background:transparent; }
.btn-new  { border-color:#3399FF; color:#3399FF; background:transparent; }
table.jtbl { width:100%; border-collapse:collapse; font-size:13px; }
table.jtbl th { background:#2a2a2a; padding:8px 6px; text-align:left; color:#FFD700; border-bottom:2px solid #444; }
table.jtbl td { padding:6px; border-bottom:1px solid #2a2a2a; vertical-align:middle; }
table.jtbl tr:hover td { background:#1f1f1f; }
</style>
</head>
<body>
<div class="adm-wrap">

<div class="box_top">Gerenciar Jutsus</div>
<div class="box_middle">
<div style="margin-bottom:8px;">
    <a href="adm.php" style="font-size:12px;color:#aaa;">← Voltar ao painel</a>
    &nbsp;|&nbsp;
    <a href="?modulo=jutsus" class="btn-acao btn-new">+ Novo Jutsu</a>
    &nbsp;
    <span style="font-size:12px;color:#aaa;">Total: <b style="color:#fff;"><?php echo count($jutsus); ?></b> jutsus encontrados</span>
</div>

<?php if ($msg): ?>
<div class="<?php echo $msg_ok ? 'msg-ok' : 'msg-err'; ?>"><?php echo $msg; ?></div>
<?php endif; ?>

<!-- ── FORMULÁRIO CRIAR / EDITAR ─────────────────────────────────────────── -->
<div class="box_top" style="margin-top:14px;"><?php echo $editar_jutsu ? 'Editar Jutsu #' . $editar_jutsu['id'] : 'Novo Jutsu'; ?></div>
<div class="box_middle">
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="acao" value="<?php echo $editar_jutsu ? 'editar' : 'criar'; ?>">
    <?php if ($editar_jutsu): ?>
    <input type="hidden" name="id" value="<?php echo $editar_jutsu['id']; ?>">
    <?php endif; ?>

    <div class="form-grid">
        <label>Nome do Jutsu *
            <input type="text" name="nome" value="<?php echo htmlspecialchars($editar_jutsu['nome'] ?? ''); ?>" required>
        </label>
        <label>Nível mínimo
            <input type="number" name="nivel" value="<?php echo $editar_jutsu['nivel'] ?? 1; ?>" min="1" max="100">
        </label>
        <label>Natureza de Chakra
            <select name="natureza">
                <?php foreach ($naturezas_opcoes as $n): ?>
                <option value="<?php echo $n; ?>" <?php echo ($editar_jutsu['natureza'] ?? 'nenhum') === $n ? 'selected' : ''; ?>><?php echo $nat_label[$n]; ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Força do Jutsu
            <input type="number" name="forca" value="<?php echo $editar_jutsu['forca'] ?? 0; ?>" min="0">
        </label>
        <label>Custo (Yens)
            <input type="number" name="valor" value="<?php echo $editar_jutsu['valor'] ?? 0; ?>" min="0" step="0.01">
        </label>
        <label>Doujutsu exclusivo
            <select name="doujutsu">
                <?php foreach ($doujutsus_opcoes as $d => $dn): ?>
                <option value="<?php echo $d; ?>" <?php echo (int)($editar_jutsu['doujutsu'] ?? 0) === $d ? 'selected' : ''; ?>><?php echo $dn; ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Nível mínimo do Doujutsu
            <input type="number" name="doujutsu_nivel" value="<?php echo $editar_jutsu['doujutsu_nivel'] ?? 0; ?>" min="0">
        </label>
        <label class="form-full">Imagem (jpg/png — será salva como <code>{id}.jpg</code> em _img/jutsus/)
            <?php if ($editar_jutsu): ?>
            <?php $img_ex = $img_dir . '/' . $editar_jutsu['id'] . '.jpg'; ?>
            <?php if (file_exists($img_ex)): ?>
            <div style="margin-bottom:4px;"><img src="<?php echo $img_url . $editar_jutsu['id']; ?>.jpg?<?php echo time(); ?>" style="height:50px;border-radius:4px;"> <small style="color:#aaa;">(imagem atual)</small></div>
            <?php endif; ?>
            <?php endif; ?>
            <input type="file" name="imagem" accept="image/*">
        </label>
        <label>Descrição / Texto
            <textarea name="texto"><?php echo htmlspecialchars($editar_jutsu['texto'] ?? ''); ?></textarea>
        </label>
    </div>
    <div style="margin-top:10px; display:flex; gap:8px; align-items:center;">
        <button type="submit" class="btn-acao btn-save"><?php echo $editar_jutsu ? 'Salvar Alterações' : 'Criar Jutsu'; ?></button>
        <?php if ($editar_jutsu): ?>
        <a href="?modulo=jutsus" class="btn-acao" style="border-color:#888;color:#aaa;">Cancelar</a>
        <?php endif; ?>
    </div>
</form>
</div>
<div class="box_bottom"></div>

<!-- ── FILTROS ────────────────────────────────────────────────────────────── -->
<form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:14px 0 8px;">
    <input type="hidden" name="modulo" value="jutsus">
    <input type="text" name="q" value="<?php echo htmlspecialchars($busca); ?>" placeholder="Buscar nome..." style="background:#2a2a2a;border:1px solid #555;color:#fff;padding:5px 8px;border-radius:4px;font-size:12px;width:180px;">
    <select name="nat" style="background:#2a2a2a;border:1px solid #555;color:#fff;padding:5px;border-radius:4px;font-size:12px;">
        <option value="">Todas as naturezas</option>
        <?php foreach ($naturezas_opcoes as $n): ?>
        <option value="<?php echo $n; ?>" <?php echo $filtro_nat === $n ? 'selected' : ''; ?>><?php echo $nat_label[$n]; ?></option>
        <?php endforeach; ?>
    </select>
    <select name="dou" style="background:#2a2a2a;border:1px solid #555;color:#fff;padding:5px;border-radius:4px;font-size:12px;">
        <option value="-1">Todos os doujutsus</option>
        <?php foreach ($doujutsus_opcoes as $d => $dn): ?>
        <option value="<?php echo $d; ?>" <?php echo $filtro_dou === $d ? 'selected' : ''; ?>><?php echo $dn; ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-acao" style="border-color:#FF9900;color:#FF9900;">Filtrar</button>
    <a href="?modulo=jutsus" class="btn-acao" style="border-color:#888;color:#aaa;">Limpar</a>
</form>

<!-- ── LISTA DE JUTSUS ────────────────────────────────────────────────────── -->
<table class="jtbl">
    <thead>
        <tr>
            <th width="60">Img</th>
            <th width="30">ID</th>
            <th>Nome</th>
            <th width="70">Natureza</th>
            <th width="50">Nível</th>
            <th width="60">Força</th>
            <th width="90">Custo (Y)</th>
            <th width="80">Doujutsu</th>
            <th width="60">Uso</th>
            <th width="110">Ações</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($jutsus)): ?>
    <tr><td colspan="10" align="center" style="color:#888;padding:20px;">Nenhum jutsu encontrado.</td></tr>
    <?php endif; ?>
    <?php foreach ($jutsus as $j): ?>
    <?php
        $img_path = $img_dir . '/' . $j['id'] . '.jpg';
        $tem_img  = file_exists($img_path);
        $cor_nat  = $nat_cor[$j['natureza']] ?? '#888';
        $usos     = (int)($uso_map[$j['id']] ?? 0);
    ?>
    <tr>
        <td>
            <?php if ($tem_img): ?>
            <img src="<?php echo $img_url . $j['id']; ?>.jpg?t=<?php echo filemtime($img_path); ?>" class="jutsu-img" title="<?php echo htmlspecialchars($j['nome']); ?>">
            <?php else: ?>
            <span class="jutsu-img-missing" title="Sem imagem"></span>
            <?php endif; ?>
        </td>
        <td style="color:#888;"><?php echo $j['id']; ?></td>
        <td>
            <b style="color:#FFD700;"><?php echo htmlspecialchars($j['nome']); ?></b>
            <?php if ($j['texto']): ?><br><small style="color:#888;"><?php echo htmlspecialchars(mb_substr($j['texto'],0,60)); ?>...</small><?php endif; ?>
        </td>
        <td>
            <span class="nat-badge" style="color:<?php echo $cor_nat; ?>;border:1px solid <?php echo $cor_nat; ?>;">
                <?php echo $nat_label[$j['natureza']] ?? $j['natureza']; ?>
            </span>
        </td>
        <td align="center"><?php echo $j['nivel']; ?></td>
        <td align="center"><b><?php echo $j['forca']; ?></b></td>
        <td align="right"><?php echo number_format($j['valor'],2,',','.'); ?></td>
        <td align="center">
            <?php if ($j['doujutsu'] > 0): ?>
            <span style="color:<?php echo ['#CC2200','#2255CC','#7722AA'][$j['doujutsu']-1] ?? '#888'; ?>;font-size:11px;font-weight:bold;">
                <?php echo $doujutsus_opcoes[$j['doujutsu']] ?? '?'; ?>
                <?php if ($j['doujutsu_nivel'] > 0): ?><br><small>nv.<?php echo $j['doujutsu_nivel']; ?></small><?php endif; ?>
            </span>
            <?php else: ?>
            <span style="color:#555;">—</span>
            <?php endif; ?>
        </td>
        <td align="center">
            <?php if ($usos > 0): ?>
            <span title="<?php echo $usos; ?> jogador(es) com este jutsu" style="color:#66CC66;"><?php echo $usos; ?> jog.</span>
            <?php else: ?>
            <span style="color:#555;">0</span>
            <?php endif; ?>
        </td>
        <td>
            <a href="?modulo=jutsus&editar=<?php echo $j['id']; ?><?php echo $filtro_nat ? '&nat='.$filtro_nat : ''; ?><?php echo $busca ? '&q='.urlencode($busca) : ''; ?>" class="btn-acao btn-edit" style="font-size:11px;padding:3px 9px;">Editar</a>
            &nbsp;
            <form method="POST" style="display:inline;" onsubmit="return confirm('Excluir o jutsu <?php echo addslashes(htmlspecialchars($j['nome'])); ?>? Isso remove o jutsu de todos os jogadores que o possuem.');">
                <input type="hidden" name="acao" value="excluir">
                <input type="hidden" name="id" value="<?php echo $j['id']; ?>">
                <button type="submit" class="btn-acao btn-del" style="font-size:11px;padding:3px 9px;cursor:pointer;">Excluir</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

</div>
<div class="box_bottom"></div>
</div>
</body>
</html>
