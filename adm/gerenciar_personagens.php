<?php
/**
 * adm/gerenciar_personagens.php
 *
 * Painel ADM para gerenciar PERSONAGENS jogáveis:
 *   • CRUD do catálogo (tabela `personagens_catalogo`)
 *   • Upload/remoção de avatares (1.jpg .. 9.jpg) por personagem
 *   • Auto-cria a coluna em `personagens` (sistema de desbloqueio) e a
 *     pasta `_img/personagens/<chave>/` ao adicionar um novo personagem.
 *
 * Acesso: somente via roteador `adm/adm.php?modulo=personagens`.
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../_inc/conexao.php';
require_once __DIR__ . '/../_inc/personagens_catalogo.php';

// Reaproveita a checagem de admin do painel principal
if (empty($_SESSION['logado']) || empty($_SESSION['adm']) || !in_array((int)$_SESSION['adm'], [1, 2], true)) {
    header('Location: ../index.php?p=login');
    exit;
}

$msg = ''; $msg_tipo = '';
$base_personagens = realpath(__DIR__ . '/../_img/personagens');

/** Sanitiza chave de personagem para evitar path traversal. */
function _gp_chave_valida(string $c): bool {
    return $c !== '' && (bool)preg_match('/^[a-z0-9_\-]+$/i', $c);
}

/** Lista quais avatares (1..9) existem no disco para uma chave. */
function _gp_avatares_existentes(string $chave): array {
    $existentes = [];
    for ($i = 1; $i <= 9; $i++) {
        if (is_file(__DIR__ . '/../_img/personagens/' . $chave . '/' . $i . '.jpg')) {
            $existentes[] = $i;
        }
    }
    return $existentes;
}

// ── POST: CRUD do catálogo ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'cat_add') {
        $r = personagem_catalogo_add($conexao, [
            'chave'     => $_POST['chave']     ?? '',
            'nome'      => $_POST['nome']      ?? '',
            'nivel'     => $_POST['nivel']     ?? 1,
            'vip'       => isset($_POST['vip']),
            'descricao' => $_POST['descricao'] ?? '',
            'ordem'     => $_POST['ordem']     ?? 0,
        ]);
        $msg = ($r['ok'] ? '✅ ' : '❌ ') . htmlspecialchars($r['msg']);
        $msg_tipo = $r['ok'] ? 'success' : 'error';
    }
    elseif ($action === 'cat_edit') {
        $r = personagem_catalogo_edit($conexao, (int)($_POST['cat_id'] ?? 0), [
            'nome'      => $_POST['nome']      ?? '',
            'nivel'     => $_POST['nivel']     ?? 1,
            'vip'       => isset($_POST['vip']),
            'descricao' => $_POST['descricao'] ?? '',
            'ordem'     => $_POST['ordem']     ?? 0,
            'ativo'     => isset($_POST['ativo']),
        ]);
        $msg = ($r['ok'] ? '✅ ' : '❌ ') . htmlspecialchars($r['msg']);
        $msg_tipo = $r['ok'] ? 'success' : 'error';
    }
    elseif ($action === 'cat_delete') {
        $r = personagem_catalogo_delete($conexao, (int)($_POST['cat_id'] ?? 0));
        $msg = ($r['ok'] ? '✅ ' : '❌ ') . htmlspecialchars($r['msg']);
        $msg_tipo = $r['ok'] ? 'success' : 'error';
    }
    elseif ($action === 'upload_avatar') {
        $chave = (string)($_POST['chave'] ?? '');
        $slot  = (int)($_POST['slot'] ?? 0);
        if (!_gp_chave_valida($chave)) {
            $msg = 'Chave de personagem inválida.'; $msg_tipo = 'error';
        } elseif ($slot < 1 || $slot > 9) {
            $msg = 'Slot inválido (use 1 a 9).'; $msg_tipo = 'error';
        } elseif (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $msg = 'Nenhum arquivo enviado ou houve erro no upload.'; $msg_tipo = 'error';
        } else {
            $tmp  = $_FILES['avatar']['tmp_name'];
            $info = @getimagesize($tmp);
            $aceitos = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
            if (!$info || !in_array($info[2], $aceitos, true)) {
                $msg = 'Arquivo inválido. Envie uma imagem JPG, PNG, GIF ou WEBP.'; $msg_tipo = 'error';
            } else {
                $dir = __DIR__ . '/../_img/personagens/' . $chave;
                if (!is_dir($dir)) @mkdir($dir, 0755, true);
                $destino = $dir . '/' . $slot . '.jpg';
                try {
                    switch ($info[2]) {
                        case IMAGETYPE_JPEG: $im = @imagecreatefromjpeg($tmp); break;
                        case IMAGETYPE_PNG:  $im = @imagecreatefrompng($tmp);  break;
                        case IMAGETYPE_GIF:  $im = @imagecreatefromgif($tmp);  break;
                        case IMAGETYPE_WEBP: $im = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false; break;
                        default: $im = false;
                    }
                    if (!$im) throw new RuntimeException('Falha ao decodificar a imagem.');
                    $w = imagesx($im); $h = imagesy($im);
                    $canvas = imagecreatetruecolor($w, $h);
                    $bg = imagecolorallocate($canvas, 17, 17, 17);
                    imagefilledrectangle($canvas, 0, 0, $w, $h, $bg);
                    imagecopy($canvas, $im, 0, 0, 0, 0, $w, $h);
                    imagedestroy($im);
                    if (!imagejpeg($canvas, $destino, 88)) throw new RuntimeException('Falha ao salvar JPG.');
                    imagedestroy($canvas);
                    @clearstatcache();
                    $msg = "✅ Avatar enviado: <code>$chave/$slot.jpg</code>"; $msg_tipo = 'success';
                } catch (Throwable $e) {
                    if (@move_uploaded_file($tmp, $destino)) {
                        $msg = "✅ Avatar salvo (cópia direta): <code>$chave/$slot.jpg</code>"; $msg_tipo = 'success';
                    } else {
                        $msg = 'Erro ao salvar o avatar: ' . htmlspecialchars($e->getMessage()); $msg_tipo = 'error';
                    }
                }
            }
        }
    }
    elseif ($action === 'remover_avatar') {
        $chave = (string)($_POST['chave'] ?? '');
        $slot  = (int)($_POST['slot'] ?? 0);
        if (_gp_chave_valida($chave) && $slot >= 1 && $slot <= 9) {
            $alvo = realpath(__DIR__ . '/../_img/personagens/' . $chave . '/' . $slot . '.jpg');
            if ($alvo && $base_personagens && strpos($alvo, $base_personagens) === 0 && @unlink($alvo)) {
                @clearstatcache();
                $msg = "🗑️ Avatar removido: <code>$chave/$slot.jpg</code>"; $msg_tipo = 'success';
            } else {
                $msg = 'Não foi possível remover o avatar.'; $msg_tipo = 'error';
            }
        } else {
            $msg = 'Parâmetros inválidos para remoção.'; $msg_tipo = 'error';
        }
    }
}

// ── Carregar TUDO (inclusive inativos) para a tabela admin ───────────────────
_personagens_catalogo_garantir_tabela($conexao);
$rows_admin = $conexao->query("SELECT id, chave, nome, nivel, vip, descricao, ordem, ativo
                               FROM personagens_catalogo ORDER BY ordem ASC, nivel ASC, nome ASC")
                      ->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Gerenciar Personagens';
include 'adm_header.php';
?>

<div class="box_top">🥷 Gerenciar Personagens</div>
<div class="box_middle">

<?php if ($msg): ?>
    <div class="alert-<?php echo htmlspecialchars($msg_tipo); ?>"><?php echo $msg; ?></div>
    <div class="sep"></div>
<?php endif; ?>

<div style="background:#1a1200;border-left:3px solid #FFD700;padding:8px 12px;margin-bottom:8px;">
    <b style="color:#FFD700;">📘 Como funciona</b><br>
    <span class="sub2">
        Adicione novos personagens pelo formulário abaixo (define nome, nível mínimo
        e se requer VIP). Em seguida envie as <b>9 imagens</b>
        (<code>1.jpg</code> .. <code>9.jpg</code>) de cada personagem.
        Personagens sem nenhum avatar ficam ocultos no seletor dos jogadores;
        personagens parciais (ex.: 7 de 9) só mostram os slots existentes.
    </span>
</div>

<!-- ─────────── NOVO PERSONAGEM ─────────── -->
<details open style="background:#0a1a0a;border:1px solid #2a6a2a;padding:8px;margin-bottom:10px;">
    <summary style="cursor:pointer;color:#5ecf6e;font-weight:bold;">➕ Adicionar novo personagem</summary>
    <form method="POST" style="margin-top:8px;">
        <input type="hidden" name="action" value="cat_add">
        <table style="width:100%;font-size:12px;">
            <tr>
                <td style="width:120px;"><label>Nome*</label></td>
                <td><input type="text" name="nome" id="np_nome" required style="width:100%;" placeholder="Ex.: Itachi"></td>
                <td style="width:120px;"><label>Chave (slug)*</label></td>
                <td><input type="text" name="chave" id="np_chave" required pattern="[a-z0-9_]+" style="width:100%;" placeholder="auto-gerada do nome"></td>
            </tr>
            <tr>
                <td><label>Nível mínimo*</label></td>
                <td><input type="number" name="nivel" min="1" max="999" value="10" required style="width:100%;"></td>
                <td><label>Requer VIP?</label></td>
                <td><label><input type="checkbox" name="vip" value="1"> Sim, exige VIP ativo</label></td>
            </tr>
            <tr>
                <td><label>Ordem</label></td>
                <td><input type="number" name="ordem" min="0" value="100" style="width:100%;" title="Menor = aparece primeiro"></td>
                <td><label>Descrição</label></td>
                <td><input type="text" name="descricao" maxlength="255" style="width:100%;" placeholder="Ex.: Membro da Akatsuki"></td>
            </tr>
            <tr><td colspan="4" align="right">
                <button type="submit" class="botao btn-success">➕ Criar personagem</button>
            </td></tr>
        </table>
    </form>
</details>

<!-- ─────────── TABELA: TODOS OS PERSONAGENS DO CATÁLOGO ─────────── -->
<table class="adm-table" style="width:100%;">
    <tr>
        <th width="140">Personagem</th>
        <th width="50">Nível</th>
        <th width="40">VIP</th>
        <th width="80">Status (arte)</th>
        <th>Avatares (1 → 9)</th>
        <th width="240">Enviar avatar</th>
        <th width="60">Ações</th>
    </tr>
<?php
$completos = 0; $parciais = 0; $vazios = 0; $inativos = 0;
foreach ($rows_admin as $r):
    $chave = $r['chave'];
    $existentes = _gp_avatares_existentes($chave);
    $qtd = count($existentes);
    $faltam = array_values(array_diff(range(1,9), $existentes));
    if ((int)$r['ativo'] === 0) { $inativos++; $cor = '#888'; $status = '⏸️ Inativo'; }
    elseif ($qtd === 9)         { $completos++; $cor = '#5ecf6e'; $status = '✅ Completo'; }
    elseif ($qtd === 0)         { $vazios++;    $cor = '#e74c3c'; $status = '❌ Sem arte'; }
    else                        { $parciais++;  $cor = '#FFD700'; $status = '⚠️ '.$qtd.'/9'; }

    $cat_json = htmlspecialchars(json_encode([
        'id'        => (int)$r['id'],
        'chave'     => $chave,
        'nome'      => $r['nome'],
        'nivel'     => (int)$r['nivel'],
        'vip'       => (int)$r['vip'],
        'descricao' => $r['descricao'],
        'ordem'     => (int)$r['ordem'],
        'ativo'     => (int)$r['ativo'],
    ], JSON_UNESCAPED_UNICODE), ENT_QUOTES);
?>
    <tr>
        <td><b style="color:#FFD700;"><?php echo htmlspecialchars($r['nome']); ?></b><br>
            <span class="sub2"><code><?php echo htmlspecialchars($chave); ?></code></span>
        </td>
        <td align="center"><?php echo (int)$r['nivel']; ?></td>
        <td align="center"><?php echo !empty($r['vip']) ? '⭐' : '—'; ?></td>
        <td align="center" style="color:<?php echo $cor; ?>;font-weight:bold;"><?php echo $status; ?></td>
        <td>
            <div style="display:flex;flex-wrap:wrap;gap:4px;">
                <?php for ($i = 1; $i <= 9; $i++):
                    $tem = in_array($i, $existentes, true);
                ?>
                    <div style="text-align:center;width:54px;">
                        <div style="border:1px solid <?php echo $tem ? '#2a6a2a' : '#444'; ?>;background:<?php echo $tem ? '#0a1a0a' : '#222'; ?>;height:48px;display:flex;align-items:center;justify-content:center;">
                            <?php if ($tem): ?>
                                <img src="../_img/personagens/<?php echo htmlspecialchars($chave); ?>/<?php echo $i; ?>.jpg?v=<?php echo time(); ?>" style="max-width:48px;max-height:48px;" />
                            <?php else: ?>
                                <span style="color:#666;font-size:18px;">—</span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:10px;color:#aaa;">#<?php echo $i; ?></div>
                        <?php if ($tem): ?>
                            <form method="POST" onsubmit="return confirm('Remover avatar #<?php echo $i; ?> de <?php echo htmlspecialchars(addslashes($r['nome'])); ?>?');" style="margin:2px 0 0 0;">
                                <input type="hidden" name="action" value="remover_avatar">
                                <input type="hidden" name="chave" value="<?php echo htmlspecialchars($chave); ?>">
                                <input type="hidden" name="slot"  value="<?php echo $i; ?>">
                                <button type="submit" class="botao btn-danger" style="font-size:9px;padding:1px 4px;">❌</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </td>
        <td>
            <form method="POST" enctype="multipart/form-data" style="margin:0;">
                <input type="hidden" name="action" value="upload_avatar">
                <input type="hidden" name="chave"  value="<?php echo htmlspecialchars($chave); ?>">
                <select name="slot" style="width:60px;font-size:11px;">
                    <?php
                    $ordem = array_merge($faltam, array_values(array_diff(range(1,9), $faltam)));
                    foreach ($ordem as $s):
                        $livre = in_array($s, $faltam, true);
                    ?>
                        <option value="<?php echo $s; ?>"><?php echo $s; ?><?php echo $livre ? ' (livre)' : ' (subst.)'; ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="file" name="avatar" accept="image/*" required style="font-size:11px;width:130px;">
                <button type="submit" class="botao btn-success" style="font-size:11px;">⬆️ Enviar</button>
            </form>
        </td>
        <td align="center">
            <button type="button" class="botao js-cat-edit" data-cat="<?php echo $cat_json; ?>" style="font-size:10px;padding:2px 5px;" title="Editar">✏️</button>
            <form method="POST" onsubmit="return confirm('Remover o personagem &quot;<?php echo htmlspecialchars(addslashes($r['nome'])); ?>&quot; do catálogo? (Não funciona se houver jogadores usando.)');" style="display:inline;margin:0;">
                <input type="hidden" name="action" value="cat_delete">
                <input type="hidden" name="cat_id" value="<?php echo (int)$r['id']; ?>">
                <button type="submit" class="botao btn-danger" style="font-size:10px;padding:2px 5px;" title="Excluir">🗑️</button>
            </form>
        </td>
    </tr>
<?php endforeach; ?>
</table>

<div class="sep"></div>
<div class="sub2">
    <b>Total no catálogo:</b> <?php echo count($rows_admin); ?> &nbsp;|&nbsp;
    <span style="color:#5ecf6e;">Completos: <?php echo $completos; ?></span> &nbsp;|&nbsp;
    <span style="color:#FFD700;">Parciais: <?php echo $parciais; ?></span> &nbsp;|&nbsp;
    <span style="color:#e74c3c;">Sem arte: <?php echo $vazios; ?></span> &nbsp;|&nbsp;
    <span style="color:#888;">Inativos: <?php echo $inativos; ?></span>
</div>

</div>
<div class="box_bottom"></div>

<!-- ─────────── MODAL: EDITAR PERSONAGEM ─────────── -->
<div id="catEditOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#1a1200;border:2px solid #FFD700;padding:14px;width:520px;max-width:95%;color:#fff;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <b style="color:#FFD700;">✏️ Editar personagem</b>
            <span style="cursor:pointer;color:#888;font-size:18px;" onclick="document.getElementById('catEditOverlay').style.display='none';">×</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="cat_edit">
            <input type="hidden" name="cat_id" id="ed_id">
            <table style="width:100%;font-size:12px;">
                <tr>
                    <td style="width:90px;"><label>Chave</label></td>
                    <td><input type="text" id="ed_chave" disabled style="width:100%;opacity:.6;"></td>
                </tr>
                <tr>
                    <td><label>Nome*</label></td>
                    <td><input type="text" name="nome" id="ed_nome" required style="width:100%;"></td>
                </tr>
                <tr>
                    <td><label>Nível*</label></td>
                    <td><input type="number" name="nivel" id="ed_nivel" min="1" max="999" required style="width:100%;"></td>
                </tr>
                <tr>
                    <td><label>VIP?</label></td>
                    <td><label><input type="checkbox" name="vip" id="ed_vip" value="1"> Exige VIP ativo</label></td>
                </tr>
                <tr>
                    <td><label>Ordem</label></td>
                    <td><input type="number" name="ordem" id="ed_ordem" min="0" style="width:100%;"></td>
                </tr>
                <tr>
                    <td><label>Descrição</label></td>
                    <td><input type="text" name="descricao" id="ed_desc" maxlength="255" style="width:100%;"></td>
                </tr>
                <tr>
                    <td><label>Ativo?</label></td>
                    <td><label><input type="checkbox" name="ativo" id="ed_ativo" value="1"> Visível para jogadores</label></td>
                </tr>
                <tr><td colspan="2" align="right" style="padding-top:8px;">
                    <button type="submit" class="botao btn-success">💾 Salvar</button>
                </td></tr>
            </table>
        </form>
    </div>
</div>

<script>
(function(){
    // Auto-gerar chave (slug) a partir do nome
    function slugify(s){
        return (s||'').toLowerCase().trim()
            .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
            .replace(/[^a-z0-9_]+/g,'_').replace(/^_+|_+$/g,'');
    }
    var nomeIn  = document.getElementById('np_nome');
    var chaveIn = document.getElementById('np_chave');
    var chaveTouched = false;
    if (chaveIn) chaveIn.addEventListener('input', function(){ chaveTouched = true; });
    if (nomeIn && chaveIn) {
        nomeIn.addEventListener('input', function(){
            if (!chaveTouched) chaveIn.value = slugify(nomeIn.value);
        });
    }

    // Abrir modal de edição
    document.querySelectorAll('.js-cat-edit').forEach(function(btn){
        btn.addEventListener('click', function(){
            try {
                var c = JSON.parse(btn.getAttribute('data-cat'));
                document.getElementById('ed_id').value    = c.id;
                document.getElementById('ed_chave').value = c.chave;
                document.getElementById('ed_nome').value  = c.nome;
                document.getElementById('ed_nivel').value = c.nivel;
                document.getElementById('ed_vip').checked = (c.vip === 1);
                document.getElementById('ed_ordem').value = c.ordem;
                document.getElementById('ed_desc').value  = c.descricao || '';
                document.getElementById('ed_ativo').checked = (c.ativo === 1);
                var ov = document.getElementById('catEditOverlay');
                ov.style.display = 'flex';
            } catch(e){ alert('Erro ao abrir editor: ' + e.message); }
        });
    });
})();
</script>

</body></html>
