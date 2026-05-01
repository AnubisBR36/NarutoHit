<?php
require_once(__DIR__ . '/personagens_catalogo.php');

$iniciais = personagens_iniciais();
$catalogo = personagens_catalogo();

// Auto-desbloqueio por nível (caso o jogador tenha subido recentemente)
personagens_unlock_por_nivel($conexao, $db);

// ───────────────── POST: trocar personagem (e opcionalmente avatar) ─────────
if (isset($_POST['char'])) {
    $eh_adm_local = !empty($db['adm']) && (int)$db['adm'] === 1;
    $char         = isset($_POST['char_personagem']) ? trim((string)$_POST['char_personagem']) : '';
    $avatar_post  = (int)($_POST['char_avatar'] ?? 0);
    $somente_avatar = ($char !== '' && $char === ($db['personagem'] ?? ''));

    // Limite "uma vez por dia" — só se MUDOU de personagem (e não é admin)
    if (!$somente_avatar && !$eh_adm_local && !empty($db['config_personagem']) && $db['config_personagem'] === 'sim') {
        $resetou = false;
        try {
            $stmt_chk = $conexao->prepare("SELECT DATE(ultimo_acesso) AS d_ult, CURDATE() AS d_hoje FROM usuarios WHERE id = ?");
            $stmt_chk->execute([$db['id']]);
            $rr = $stmt_chk->fetch(PDO::FETCH_ASSOC);
            if ($rr && !empty($rr['d_ult']) && $rr['d_ult'] !== $rr['d_hoje']) {
                $conexao->prepare("UPDATE usuarios SET config_personagem='nao' WHERE id=?")->execute([$db['id']]);
                $db['config_personagem'] = 'nao';
                $resetou = true;
            }
        } catch (PDOException $e) { /* silencioso */ }
        if (!$resetou) {
            echo "<script>self.location='?p=config&type=char&msg=2'</script>"; exit;
        }
    }

    if ($char === '' || !preg_match('/^[a-z0-9_\-]+$/i', $char)) {
        echo "<script>self.location='?p=config&type=char&msg=3'</script>"; exit;
    }
    // Inicial sempre ok; demais devem estar desbloqueados no catálogo
    if (!in_array($char, $iniciais, true)) {
        if (!isset($catalogo[$char])) { echo "<script>self.location='?p=config&type=char&msg=3'</script>"; exit; }
        if (!personagem_esta_desbloqueado($conexao, $db, $char)) {
            echo "<script>self.location='?p=config&type=char&msg=4'</script>"; exit;
        }
    }
    if (!is_dir(__DIR__ . '/../_img/personagens/' . $char)) {
        echo "<script>self.location='?p=config&type=char&msg=4'</script>"; exit;
    }

    // Valida avatar enviado (se 0, mantém atual quando não trocou de personagem; senão usa 1)
    if ($avatar_post < 1 || $avatar_post > 9 || !is_file(__DIR__ . '/../_img/personagens/' . $char . '/' . $avatar_post . '.jpg')) {
        $avatar_post = $somente_avatar ? (int)($db['avatar'] ?? 1) : 1;
        // tenta achar o primeiro avatar válido se nem o 1 existir
        if (!is_file(__DIR__ . '/../_img/personagens/' . $char . '/' . $avatar_post . '.jpg')) {
            for ($i = 1; $i <= 9; $i++) {
                if (is_file(__DIR__ . '/../_img/personagens/' . $char . '/' . $i . '.jpg')) { $avatar_post = $i; break; }
            }
        }
    }

    try {
        if ($somente_avatar) {
            // só mudou o avatar — não consome "troca diária"
            $stmt = $conexao->prepare("UPDATE usuarios SET avatar=? WHERE id=?");
            $stmt->execute([$avatar_post, $db['id']]);
            $db['avatar'] = $avatar_post;
        } else {
            $config_personagem = (date('Y-m-d H:i:s') < ($db['vip'] ?? '2000-01-01')) ? 'nao' : 'sim';
            $stmt = $conexao->prepare("UPDATE usuarios SET personagem=?, avatar=?, config_personagem=? WHERE id=?");
            $stmt->execute([$char, $avatar_post, $config_personagem, $db['id']]);
            $db['personagem'] = $char;
            $db['avatar'] = $avatar_post;
            $db['config_personagem'] = $config_personagem;
        }
    } catch (PDOException $e) {
        error_log('config_char update falhou: ' . $e->getMessage());
        echo "<script>self.location='?p=config&type=char&msg=4'</script>"; exit;
    }
    echo "<script>self.location='?p=config&type=char&msg=1'</script>";
    exit;
}

if (isset($_GET['msg'])) {
    $msg = '';
    switch ($_GET['msg']) {
        case 1: $msg = 'Personagem/avatar alterado com sucesso!'; break;
        case 2: $msg = 'O personagem só pode ser trocado uma vez por dia.'; break;
        case 3: $msg = 'Selecione um personagem válido.'; break;
        case 4: $msg = 'Personagem indisponível ou ainda não desbloqueado.'; break;
    }
    if ($msg) echo '<div class="aviso">' . $msg . '</div><div class="sep"></div>';
}

// Carrega a linha do usuário em `personagens` para saber quais estão desbloqueados
$linha_unlock = personagens_carregar($conexao, (int)$db['id']);
$eh_vip       = personagem_jogador_eh_vip($db);
$char_atual   = $db['personagem'] ?? 'naruto';
$avatar_atual = (int)($db['avatar'] ?? 1);

require_once('funcoes.php');

/**
 * Monta a lista de personagens disponíveis, organizada em grupos:
 *   - Iniciais (sempre disponíveis)
 *   - Desbloqueados (do catálogo, com arte)
 *   - Bloqueados (do catálogo, com arte) — informativo, não selecionável
 *
 * Para cada personagem disponível, calcula a lista de avatares (1..9) que
 * existem no disco — usado pelo JS para renderizar o preview.
 */
$personagens_disp = [];   // chave => [nome, nivel, vip, avatares[]]
$personagens_lock = [];   // chave => [nome, nivel, vip, descricao]

foreach ($iniciais as $chave) {
    $avs = [];
    for ($i = 1; $i <= 9; $i++) {
        if (is_file(__DIR__ . '/../_img/personagens/' . $chave . '/' . $i . '.jpg')) $avs[] = $i;
    }
    if (empty($avs)) continue;
    $personagens_disp[$chave] = [
        'nome'  => ucfirst($chave),
        'nivel' => 0,
        'vip'   => false,
        'grupo' => 'Iniciais',
        'avatares' => $avs,
    ];
}

foreach ($catalogo as $chave => $info) {
    if (!personagem_tem_avatares($chave)) continue;
    $desbloqueado = !empty($linha_unlock) && isset($linha_unlock[$chave]) && (int)$linha_unlock[$chave] === 1;
    if ($desbloqueado) {
        $avs = [];
        for ($i = 1; $i <= 9; $i++) {
            if (is_file(__DIR__ . '/../_img/personagens/' . $chave . '/' . $i . '.jpg')) $avs[] = $i;
        }
        $personagens_disp[$chave] = [
            'nome'     => $info['nome'],
            'nivel'    => (int)$info['nivel'],
            'vip'      => !empty($info['vip']),
            'grupo'    => 'Desbloqueados',
            'avatares' => $avs,
        ];
    } else {
        $personagens_lock[$chave] = [
            'nome'      => $info['nome'],
            'nivel'     => (int)$info['nivel'],
            'vip'       => !empty($info['vip']),
            'descricao' => $info['descricao'] ?? '',
        ];
    }
}

// Mapa para JS: chave => lista de avatares disponíveis
$mapa_avatares_js = [];
foreach ($personagens_disp as $k => $v) $mapa_avatares_js[$k] = $v['avatares'];
?>
<form method="post" action="?p=config&amp;type=char" onsubmit="subm.value='Carregando...';subm.disabled=true;">
<input type="hidden" id="char" name="char" value="1" />
<input type="hidden" id="char_avatar" name="char_avatar" value="<?php echo $avatar_atual; ?>" />

<fieldset><legend>Alterar Personagem &amp; Avatar</legend>

<div align="center" style="margin-bottom:8px;">
    <span class="sub2">Escolha um personagem pelo nome — abaixo aparecem as imagens disponíveis.
    Clique numa imagem para selecionar o avatar.</span>
</div>

<table cellpadding="4" align="center"><tr>
    <td align="right"><label for="char_personagem"><b>Personagem:</b></label></td>
    <td>
        <select name="char_personagem" id="char_personagem" style="font-size:13px;padding:4px;min-width:240px;">
            <optgroup label="— Iniciais —">
            <?php foreach ($personagens_disp as $k => $v): if ($v['grupo'] !== 'Iniciais') continue; ?>
                <option value="<?php echo htmlspecialchars($k); ?>" <?php echo ($char_atual === $k ? 'selected' : ''); ?>>
                    <?php echo htmlspecialchars($v['nome']); ?>
                </option>
            <?php endforeach; ?>
            </optgroup>

            <?php $tem_desb = false; foreach ($personagens_disp as $v) if ($v['grupo'] === 'Desbloqueados') { $tem_desb = true; break; } ?>
            <?php if ($tem_desb): ?>
            <optgroup label="— Desbloqueados —">
                <?php foreach ($personagens_disp as $k => $v): if ($v['grupo'] !== 'Desbloqueados') continue; ?>
                    <option value="<?php echo htmlspecialchars($k); ?>" <?php echo ($char_atual === $k ? 'selected' : ''); ?>>
                        <?php echo htmlspecialchars($v['nome']); ?>
                        (Nv. <?php echo (int)$v['nivel']; ?><?php echo $v['vip'] ? ', VIP' : ''; ?>)
                    </option>
                <?php endforeach; ?>
            </optgroup>
            <?php endif; ?>

            <?php if (!empty($personagens_lock)): ?>
            <optgroup label="— 🔒 Bloqueados (informativo) —">
                <?php foreach ($personagens_lock as $k => $v): ?>
                    <option value="<?php echo htmlspecialchars($k); ?>" disabled>
                        <?php echo htmlspecialchars($v['nome']); ?>
                        — Nv. <?php echo (int)$v['nivel']; ?><?php echo $v['vip'] ? ' · VIP' : ''; ?>
                    </option>
                <?php endforeach; ?>
            </optgroup>
            <?php endif; ?>
        </select>
    </td>
</tr></table>

<div class="sep"></div>

<div id="char_preview" align="center" style="min-height:160px;"></div>

<div class="sep"></div>
<div align="center"><input type="submit" id="subm" name="subm" class="botao" value="Salvar alterações" /></div>

</fieldset>
</form>

<script>
(function(){
    var MAPA = <?php echo json_encode($mapa_avatares_js, JSON_UNESCAPED_UNICODE); ?>;
    var charSelect = document.getElementById('char_personagem');
    var avatarHid  = document.getElementById('char_avatar');
    var preview    = document.getElementById('char_preview');
    var atualChave = <?php echo json_encode($char_atual); ?>;
    var atualAvatar = <?php echo (int)$avatar_atual; ?>;

    function render() {
        var chave = charSelect.value;
        var avs = MAPA[chave] || [];
        // Avatar selecionado: se mudou de personagem, escolhe o primeiro disponível;
        // se voltou para o personagem atual, mantém o avatar atual.
        var sel = parseInt(avatarHid.value, 10) || 0;
        if (chave !== atualChave) {
            sel = avs[0] || 1;
        } else if (avs.indexOf(sel) === -1) {
            sel = atualAvatar || avs[0] || 1;
        }
        avatarHid.value = sel;

        if (avs.length === 0) {
            preview.innerHTML = '<div class="aviso" style="display:inline-block;">Este personagem ainda não tem imagens. Peça para o ADM enviar pelo painel.</div>';
            return;
        }

        var html = '<div style="display:inline-block;">' +
                   '<div class="sub2" style="margin-bottom:4px;">Imagens disponíveis (' + avs.length + ' de 9) — clique para escolher:</div>' +
                   '<div style="display:flex;flex-wrap:wrap;gap:6px;justify-content:center;max-width:600px;">';
        avs.forEach(function(i){
            var ativo = (i === sel);
            html += '<div data-idx="' + i + '" class="avatar-pick" style="cursor:pointer;border:3px solid ' + (ativo ? '#FFD700' : '#444') + ';padding:2px;background:#222;">' +
                    '<img src="_img/personagens/' + chave + '/' + i + '.jpg" width="110" height="100" style="display:block;" />' +
                    '<div style="text-align:center;font-size:10px;color:' + (ativo ? '#FFD700' : '#aaa') + ';">#' + i + (ativo ? ' ✓' : '') + '</div>' +
                    '</div>';
        });
        html += '</div></div>';
        preview.innerHTML = html;

        preview.querySelectorAll('.avatar-pick').forEach(function(el){
            el.addEventListener('click', function(){
                avatarHid.value = parseInt(el.getAttribute('data-idx'), 10);
                render();
            });
        });
    }

    charSelect.addEventListener('change', function(){
        avatarHid.value = 0; // força recálculo
        render();
    });
    render();
})();
</script>
