<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Usa a conexão padrão do projeto (respeita config/database.php — MySQL)
if (!file_exists(__DIR__ . '/../_inc/conexao.php')) {
    die("Erro: Arquivo de conexão não encontrado em '../_inc/conexao.php'");
}
require_once(__DIR__ . '/../_inc/conexao.php');
if (!isset($conexao)) {
    die("Erro: Conexão com banco de dados não foi estabelecida");
}

if (session_status() == PHP_SESSION_NONE) session_start();
if(!isset($_SESSION['logado']) && !isset($_SESSION['userid']) && !isset($_SESSION['uid'])) {
    echo "<script>window.location.href='../index.php';</script>"; exit;
}
$user_id = $_SESSION['logado'] ?? $_SESSION['userid'] ?? $_SESSION['uid'];
try {
    $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $usuario_logado = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { die("Erro: " . $e->getMessage()); }

$modulo_necessario = 'cristais';
require_once('_gm_auth.php');

// ── Cadastro de novos tipos de cristal (table_usaveis) ───────────────────────
$mensagem_cad = '';
$tipo_mensagem_cad = '';

// Auto-migração: garante colunas tipo_efeito/valor_efeito (usadas para dar
// FUNÇÕES distintas aos cristais — cura, boost de stat, etc.). Veja
// _inc/usar_cristal_buff.php para a leitura desses campos.
try {
    $cols = $conexao->query("SHOW COLUMNS FROM table_usaveis")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('tipo_efeito', $cols, true)) {
        $conexao->exec("ALTER TABLE table_usaveis ADD COLUMN tipo_efeito VARCHAR(32) NULL DEFAULT NULL");
    }
    if (!in_array('valor_efeito', $cols, true)) {
        $conexao->exec("ALTER TABLE table_usaveis ADD COLUMN valor_efeito VARCHAR(64) NULL DEFAULT NULL");
    }
    // Quantos fragmentos formam 1 cristal completo (usado por Cristais de
    // Craft no Ferreiro). NULL/0 → fallback de 5 fragmentos.
    if (!in_array('fragmentos_necessarios', $cols, true)) {
        $conexao->exec("ALTER TABLE table_usaveis ADD COLUMN fragmentos_necessarios INTEGER NULL DEFAULT NULL");
    }
    // Imagem própria do FRAGMENTO (diferente da imagem do cristal completo).
    // Salva em _img/Craft/fragmentos/. NULL/'' → cai no fallback antigo (mesma
    // imagem do cristal com filtro CSS roxo).
    if (!in_array('imagem_fragmento', $cols, true)) {
        $conexao->exec("ALTER TABLE table_usaveis ADD COLUMN imagem_fragmento VARCHAR(255) NULL DEFAULT NULL");
    }
    // Quando uma row representa um FRAGMENTO (categoria='fragmento_craft'),
    // este campo aponta para o id do cristal (categoria='cristal_craft') que
    // ele se torna ao ser combinado no Ferreiro.
    if (!in_array('cristal_alvo_id', $cols, true)) {
        $conexao->exec("ALTER TABLE table_usaveis ADD COLUMN cristal_alvo_id INTEGER NULL DEFAULT NULL");
    }
} catch (Throwable $e) {}

// Constantes de validação para a "receita" de fragmentos de cristal.
const CRISTAL_FRAG_MIN = 2;
const CRISTAL_FRAG_MAX = 20;
const CRISTAL_FRAG_DEFAULT = 5;

/**
 * Catálogo de efeitos suportados pelos cristais de BUFF.
 * Cada chave é o `tipo_efeito` salvo em table_usaveis. O `valor_efeito` é
 * o conteúdo do campo dinâmico definido por `campos`.
 *
 * IMPORTANTE: para acrescentar um novo efeito, basta:
 *   1) registrar a chave aqui;
 *   2) tratar a chave em _inc/usar_cristal_buff.php.
 */
$cristal_efeitos_buff = [
    'taijutsu'   => ['label'=>'+% Taijutsu (temporário)',  'campos'=>['pct'=>'%','horas'=>'h'], 'desc'=>'Aumenta Taijutsu por X horas. Aplicado em batalha e na home.'],
    'ninjutsu'   => ['label'=>'+% Ninjutsu (temporário)',  'campos'=>['pct'=>'%','horas'=>'h'], 'desc'=>'Aumenta Ninjutsu por X horas. Aplicado em batalha e na home.'],
    'genjutsu'   => ['label'=>'+% Genjutsu (temporário)',  'campos'=>['pct'=>'%','horas'=>'h'], 'desc'=>'Aumenta Genjutsu por X horas. Aplicado em batalha e na home.'],
    'cura_total' => ['label'=>'Cura total (instantâneo)',  'campos'=>[],                         'desc'=>'Restaura HP e Chakra do jogador para o máximo imediatamente. Não cria buff temporário.'],
];

function _cristal_salvar_imagem($field, $subdir) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return '';
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) throw new Exception('Erro no upload da imagem.');
    $tmp = $_FILES[$field]['tmp_name'];
    $info = @getimagesize($tmp);
    if (!$info) throw new Exception('Arquivo enviado não é uma imagem válida.');
    $ext_map = [IMAGETYPE_PNG=>'png', IMAGETYPE_JPEG=>'jpg', IMAGETYPE_GIF=>'gif', IMAGETYPE_WEBP=>'webp'];
    if (!isset($ext_map[$info[2]])) throw new Exception('Formato não suportado (use PNG/JPG/GIF/WEBP).');
    $ext = $ext_map[$info[2]];
    $base = preg_replace('/[^a-z0-9_-]/i', '_', pathinfo($_FILES[$field]['name'], PATHINFO_FILENAME));
    if ($base === '') $base = 'cristal_' . time();
    $destino_dir = __DIR__ . '/../_img/' . $subdir;
    if (!is_dir($destino_dir)) @mkdir($destino_dir, 0755, true);
    $nome_final = $base . '.' . $ext;
    $i = 1;
    while (file_exists($destino_dir . '/' . $nome_final)) { $nome_final = $base . '_' . $i . '.' . $ext; $i++; }
    if (!move_uploaded_file($tmp, $destino_dir . '/' . $nome_final)) throw new Exception('Falha ao salvar imagem.');
    return $nome_final;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])
    && in_array($_POST['action'], ['cadastrar_cristal','cadastrar_cristal_buff','cadastrar_cristal_craft'], true)) {
    $cat_map = [
        'cadastrar_cristal'       => ['cat'=>'cristal',       'subdir'=>'ferreiro', 'rotulo'=>'refinamento'],
        'cadastrar_cristal_buff'  => ['cat'=>'cristal_buff',  'subdir'=>'Buff',     'rotulo'=>'buff'],
        'cadastrar_cristal_craft' => ['cat'=>'cristal_craft', 'subdir'=>'Craft',    'rotulo'=>'craft'],
    ];
    $cfg  = $cat_map[$_POST['action']];
    $nome = trim($_POST['novo_nome'] ?? '');
    $desc = trim($_POST['novo_desc'] ?? '');

    // Para cristais de CRAFT: aceitar quantos fragmentos formam 1 cristal completo.
    $fragmentos_necessarios = null;
    if ($cfg['cat'] === 'cristal_craft') {
        $raw_frag = (int)($_POST['fragmentos_necessarios'] ?? CRISTAL_FRAG_DEFAULT);
        if ($raw_frag < CRISTAL_FRAG_MIN || $raw_frag > CRISTAL_FRAG_MAX) {
            $mensagem_cad = 'Quantidade de fragmentos inválida. Use entre '.CRISTAL_FRAG_MIN.' e '.CRISTAL_FRAG_MAX.'.';
            $tipo_mensagem_cad = 'error';
            goto _fim_cadastro_cristal;
        }
        $fragmentos_necessarios = $raw_frag;
    }

    // Para cristais de buff: validar/normalizar tipo_efeito + valor_efeito
    $tipo_efeito = null; $valor_efeito = null;
    if ($cfg['cat'] === 'cristal_buff') {
        $tipo_efeito = $_POST['tipo_efeito'] ?? '';
        if (!isset($cristal_efeitos_buff[$tipo_efeito])) {
            $mensagem_cad = 'Selecione um tipo de efeito válido para o cristal de buff.';
            $tipo_mensagem_cad = 'error';
            $tipo_efeito = null;
            goto _fim_cadastro_cristal;
        }
        $efeito_def = $cristal_efeitos_buff[$tipo_efeito];
        if (!empty($efeito_def['campos'])) {
            $partes = [];
            foreach ($efeito_def['campos'] as $campo => $sufixo) {
                $v = (int)($_POST['ef_'.$campo] ?? 0);
                if ($v < 1 || $v > 10000) {
                    $mensagem_cad = "Valor inválido para \"$campo\" no efeito (1-10000).";
                    $tipo_mensagem_cad = 'error';
                    goto _fim_cadastro_cristal;
                }
                $partes[$campo] = $v;
            }
            $valor_efeito = json_encode($partes, JSON_UNESCAPED_UNICODE);
        } else {
            $valor_efeito = '';
        }
    }

    if ($nome === '' || mb_strlen($nome) > 100) {
        $mensagem_cad = 'Informe um nome válido (1-100 caracteres) para o cristal.'; $tipo_mensagem_cad = 'error';
    } else {
        try {
            $imagem = _cristal_salvar_imagem('novo_imagem', $cfg['subdir']);
            // Imagem do fragmento (apenas para cristais de craft) — opcional;
            // sem upload, fica NULL e o jogador vê a mesma do cristal com filtro.
            $imagem_fragmento = null;
            if ($cfg['cat'] === 'cristal_craft') {
                $img_frag = _cristal_salvar_imagem('novo_imagem_fragmento', 'Craft/fragmentos');
                if ($img_frag !== '') $imagem_fragmento = $img_frag;
            }
            $stmt = $conexao->prepare("INSERT INTO table_usaveis (nome, descricao, imagem, categoria, tipo_efeito, valor_efeito, fragmentos_necessarios, imagem_fragmento) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $desc, $imagem, $cfg['cat'], $tipo_efeito, $valor_efeito, $fragmentos_necessarios, $imagem_fragmento]);
            $mensagem_cad = "✅ Cristal de {$cfg['rotulo']} \"{$nome}\" cadastrado com sucesso!";
            $tipo_mensagem_cad = 'success';
        } catch (Exception $e) {
            $mensagem_cad = "Erro ao cadastrar: " . $e->getMessage(); $tipo_mensagem_cad = 'error';
        }
    }
    _fim_cadastro_cristal:;
}

// ── Remoção de tipos de cristal (DELETE em cascata) ────────────────────────
$mensagem_del = ''; $tipo_mensagem_del = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remover_cristal') {
    $crid = (int)($_POST['cristal_id'] ?? 0);
    $cat_esperada = $_POST['categoria'] ?? '';
    if ($crid > 0 && in_array($cat_esperada, ['cristal','cristal_buff','cristal_craft'], true)) {
        try {
            // Garantir que o tipo realmente pertence à categoria, evitando deletar item errado.
            $chk = $conexao->prepare("SELECT id, nome, imagem FROM table_usaveis WHERE id=? AND categoria=? LIMIT 1");
            $chk->execute([$crid, $cat_esperada]);
            $alvo = $chk->fetch(PDO::FETCH_ASSOC);
            if (!$alvo) {
                $mensagem_del = "Tipo de cristal não encontrado para esta categoria."; $tipo_mensagem_del = 'error';
            } else {
                $conexao->beginTransaction();
                $del_uso = $conexao->prepare("DELETE FROM usaveis WHERE itemid=?");
                $del_uso->execute([$crid]);
                $del_t = $conexao->prepare("DELETE FROM table_usaveis WHERE id=? AND categoria=?");
                $del_t->execute([$crid, $cat_esperada]);
                $conexao->commit();
                $mensagem_del = '✅ Cristal "'.$alvo['nome'].'" removido (e estoques dos jogadores zerados).';
                $tipo_mensagem_del = 'success';
            }
        } catch (Throwable $e) {
            if ($conexao->inTransaction()) $conexao->rollBack();
            $mensagem_del = 'Erro ao remover: '.$e->getMessage(); $tipo_mensagem_del = 'error';
        }
    }
}

// ── Edição da "receita" (fragmentos_necessarios + imagem_fragmento) ────────
$mensagem_receita = ''; $tipo_mensagem_receita = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'editar_receita_craft') {
    $crid = (int)($_POST['cristal_id'] ?? 0);
    $qtd  = (int)($_POST['fragmentos_necessarios'] ?? 0);
    if ($crid <= 0) {
        $mensagem_receita = 'Cristal inválido.'; $tipo_mensagem_receita = 'error';
    } elseif ($qtd < CRISTAL_FRAG_MIN || $qtd > CRISTAL_FRAG_MAX) {
        $mensagem_receita = 'Quantidade de fragmentos inválida. Use entre '.CRISTAL_FRAG_MIN.' e '.CRISTAL_FRAG_MAX.'.';
        $tipo_mensagem_receita = 'error';
    } else {
        try {
            // Upload opcional de uma nova imagem para o fragmento.
            $nova_img_frag = _cristal_salvar_imagem('imagem_fragmento_edit', 'Craft/fragmentos');
            if ($nova_img_frag !== '') {
                $upd = $conexao->prepare("UPDATE table_usaveis SET fragmentos_necessarios=?, imagem_fragmento=? WHERE id=? AND categoria='cristal_craft'");
                $upd->execute([$qtd, $nova_img_frag, $crid]);
                $mensagem_receita = '✅ Receita atualizada: '.$qtd.' fragmentos + nova imagem do fragmento.';
            } else {
                $upd = $conexao->prepare("UPDATE table_usaveis SET fragmentos_necessarios=? WHERE id=? AND categoria='cristal_craft'");
                $upd->execute([$qtd, $crid]);
                $mensagem_receita = '✅ Receita atualizada: '.$qtd.' fragmentos formam 1 cristal.';
            }
            $tipo_mensagem_receita = 'success';
        } catch (Throwable $e) {
            $mensagem_receita = 'Erro ao salvar receita: '.$e->getMessage();
            $tipo_mensagem_receita = 'error';
        }
    }
}

// ── Remover SÓ a imagem do fragmento (volta ao fallback do filtro CSS) ────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remover_imagem_fragmento') {
    $crid = (int)($_POST['cristal_id'] ?? 0);
    if ($crid > 0) {
        try {
            $upd = $conexao->prepare("UPDATE table_usaveis SET imagem_fragmento=NULL WHERE id=? AND categoria='cristal_craft'");
            $upd->execute([$crid]);
            $mensagem_receita = '✅ Imagem do fragmento removida (volta a usar a do cristal com filtro).';
            $tipo_mensagem_receita = 'success';
        } catch (Throwable $e) {
            $mensagem_receita = 'Erro: '.$e->getMessage();
            $tipo_mensagem_receita = 'error';
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// FRAGMENTOS DE CRAFT (entidades próprias)
// table_usaveis.categoria='fragmento_craft' com cristal_alvo_id apontando para
// um cristal completo (cat='cristal_craft'). Vários fragmentos diferentes
// podem virar o mesmo cristal alvo.
// ─────────────────────────────────────────────────────────────────────────────
$mensagem_frag = ''; $tipo_mensagem_frag = '';

// Salvar imagem: aceita upload OU caminho relativo dentro de _img/Fragmento de Cristal/.
function _frag_resolver_imagem($field_upload, $field_galeria) {
    // Prioridade 1: upload novo (vai para _img/Fragmento de Cristal/)
    if (isset($_FILES[$field_upload]) && $_FILES[$field_upload]['error'] === UPLOAD_ERR_OK) {
        return _cristal_salvar_imagem($field_upload, 'Fragmento de Cristal');
    }
    // Prioridade 2: seleção de imagem já existente na galeria
    $sel = trim($_POST[$field_galeria] ?? '');
    if ($sel !== '') {
        // Sanitiza: só nome de arquivo, sem barras
        $sel = basename($sel);
        $caminho = __DIR__ . '/../_img/Fragmento de Cristal/' . $sel;
        if (is_file($caminho)) return $sel;
    }
    return '';
}

// Cadastrar novo fragmento de craft
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cadastrar_fragmento_craft') {
    $nome   = trim($_POST['frag_nome'] ?? '');
    $desc   = trim($_POST['frag_desc'] ?? '');
    $qtd    = (int)($_POST['frag_qtd'] ?? CRISTAL_FRAG_DEFAULT);
    $alvoId = (int)($_POST['cristal_alvo_id'] ?? 0);

    if ($nome === '' || mb_strlen($nome) > 100) {
        $mensagem_frag = 'Nome inválido (1-100 caracteres).'; $tipo_mensagem_frag = 'error';
    } elseif ($qtd < CRISTAL_FRAG_MIN || $qtd > CRISTAL_FRAG_MAX) {
        $mensagem_frag = 'Quantidade inválida (entre '.CRISTAL_FRAG_MIN.' e '.CRISTAL_FRAG_MAX.').'; $tipo_mensagem_frag = 'error';
    } elseif ($alvoId <= 0) {
        $mensagem_frag = 'Selecione qual cristal este fragmento irá se tornar.'; $tipo_mensagem_frag = 'error';
    } else {
        // Confirma que o alvo existe e é um cristal_craft
        $chk = $conexao->prepare("SELECT id, nome FROM table_usaveis WHERE id=? AND categoria='cristal_craft'");
        $chk->execute([$alvoId]);
        $alvo = $chk->fetch(PDO::FETCH_ASSOC);
        if (!$alvo) {
            $mensagem_frag = 'Cristal alvo inválido (não encontrado em Cristais de Craft).'; $tipo_mensagem_frag = 'error';
        } else {
            try {
                $img = _frag_resolver_imagem('frag_imagem_upload', 'frag_imagem_galeria');
                if ($img === '') {
                    $mensagem_frag = 'Envie uma imagem ou selecione uma da galeria.'; $tipo_mensagem_frag = 'error';
                } else {
                    $stmt = $conexao->prepare("INSERT INTO table_usaveis (nome, descricao, imagem, categoria, fragmentos_necessarios, cristal_alvo_id) VALUES (?, ?, ?, 'fragmento_craft', ?, ?)");
                    $stmt->execute([$nome, $desc, $img, $qtd, $alvoId]);
                    $mensagem_frag = '✅ Fragmento "'.$nome.'" cadastrado: '.$qtd.' unidades → 1× '.$alvo['nome'].'.';
                    $tipo_mensagem_frag = 'success';
                }
            } catch (Exception $e) {
                $mensagem_frag = 'Erro ao cadastrar fragmento: '.$e->getMessage(); $tipo_mensagem_frag = 'error';
            }
        }
    }
}

// Editar fragmento existente (qtd, cristal alvo, imagem opcional)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'editar_fragmento_craft') {
    $fid    = (int)($_POST['frag_id'] ?? 0);
    $qtd    = (int)($_POST['frag_qtd'] ?? 0);
    $alvoId = (int)($_POST['cristal_alvo_id'] ?? 0);

    if ($fid <= 0) {
        $mensagem_frag = 'Fragmento inválido.'; $tipo_mensagem_frag = 'error';
    } elseif ($qtd < CRISTAL_FRAG_MIN || $qtd > CRISTAL_FRAG_MAX) {
        $mensagem_frag = 'Quantidade inválida (entre '.CRISTAL_FRAG_MIN.' e '.CRISTAL_FRAG_MAX.').'; $tipo_mensagem_frag = 'error';
    } elseif ($alvoId <= 0) {
        $mensagem_frag = 'Selecione um cristal alvo.'; $tipo_mensagem_frag = 'error';
    } else {
        $chk = $conexao->prepare("SELECT id, nome FROM table_usaveis WHERE id=? AND categoria='cristal_craft'");
        $chk->execute([$alvoId]);
        $alvo = $chk->fetch(PDO::FETCH_ASSOC);
        if (!$alvo) {
            $mensagem_frag = 'Cristal alvo inválido.'; $tipo_mensagem_frag = 'error';
        } else {
            try {
                $nova_img = _frag_resolver_imagem('frag_imagem_upload_edit', 'frag_imagem_galeria_edit');
                if ($nova_img !== '') {
                    $upd = $conexao->prepare("UPDATE table_usaveis SET fragmentos_necessarios=?, cristal_alvo_id=?, imagem=? WHERE id=? AND categoria='fragmento_craft'");
                    $upd->execute([$qtd, $alvoId, $nova_img, $fid]);
                } else {
                    $upd = $conexao->prepare("UPDATE table_usaveis SET fragmentos_necessarios=?, cristal_alvo_id=? WHERE id=? AND categoria='fragmento_craft'");
                    $upd->execute([$qtd, $alvoId, $fid]);
                }
                $mensagem_frag = '✅ Fragmento atualizado: '.$qtd.' unid. → 1× '.$alvo['nome'].'.';
                $tipo_mensagem_frag = 'success';
            } catch (Exception $e) {
                $mensagem_frag = 'Erro: '.$e->getMessage(); $tipo_mensagem_frag = 'error';
            }
        }
    }
}

// Dar fragmentos ao próprio admin (botão de TESTE) — facilita testar o Ferreiro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'dar_fragmento_teste') {
    $fid = (int)($_POST['frag_id'] ?? 0);
    $qtd = max(1, min(99, (int)($_POST['qtd'] ?? 5)));
    $meuId = (int)($db['id'] ?? 0);
    if ($fid > 0 && $meuId > 0) {
        try {
            $pkCF = Database::autoIncPK('id');
            $conexao->exec("CREATE TABLE IF NOT EXISTS craft_fragmentos (
                $pkCF,
                usuarioid INTEGER NOT NULL,
                itemid INTEGER NOT NULL,
                quantidade INTEGER NOT NULL DEFAULT 0,
                UNIQUE(usuarioid, itemid)
            )");
            $sql = Database::isMysql()
                ? "INSERT INTO craft_fragmentos (usuarioid, itemid, quantidade) VALUES (?,?,?)
                   ON DUPLICATE KEY UPDATE quantidade = quantidade + VALUES(quantidade)"
                : "INSERT INTO craft_fragmentos (usuarioid, itemid, quantidade) VALUES (?,?,?)
                   ON CONFLICT(usuarioid,itemid) DO UPDATE SET quantidade = craft_fragmentos.quantidade + EXCLUDED.quantidade";
            $conexao->prepare($sql)->execute([$meuId, $fid, $qtd]);
            $mensagem_frag = "✅ +$qtd fragmento(s) adicionado(s) ao seu inventário (TESTE).";
            $tipo_mensagem_frag = 'success';
        } catch (Exception $e) {
            $mensagem_frag = 'Erro ao dar fragmento: '.$e->getMessage();
            $tipo_mensagem_frag = 'error';
        }
    }
}

// Remover fragmento (zera estoques dos jogadores)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remover_fragmento_craft') {
    $fid = (int)($_POST['frag_id'] ?? 0);
    if ($fid > 0) {
        try {
            $conexao->prepare("DELETE FROM craft_fragmentos WHERE itemid=?")->execute([$fid]);
            $conexao->prepare("DELETE FROM table_usaveis WHERE id=? AND categoria='fragmento_craft'")->execute([$fid]);
            $mensagem_frag = '✅ Fragmento removido (estoques dos jogadores zerados).';
            $tipo_mensagem_frag = 'success';
        } catch (Exception $e) {
            $mensagem_frag = 'Erro: '.$e->getMessage(); $tipo_mensagem_frag = 'error';
        }
    }
}

// Listar imagens existentes em _img/Fragmento de Cristal/ para a galeria
$pasta_frag_imgs = __DIR__ . '/../_img/Fragmento de Cristal';
$galeria_fragmentos = [];
if (is_dir($pasta_frag_imgs)) {
    foreach (scandir($pasta_frag_imgs) as $f) {
        if ($f === '.' || $f === '..') continue;
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (in_array($ext, ['png','jpg','jpeg','gif','webp','jfif','bmp'], true)) {
            $galeria_fragmentos[] = $f;
        }
    }
    sort($galeria_fragmentos);
}

$cristais = $conexao->query("SELECT * FROM table_usaveis WHERE categoria = 'cristal' ORDER BY id")->fetchAll();

$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'adicionar_cristal') {
        $usuario_busca = trim($_POST['usuario'] ?? '');
        $cristal_id = (int)($_POST['cristal_id'] ?? 0);
        $quantidade = (int)($_POST['quantidade'] ?? 1);

        if (empty($usuario_busca)) {
            $mensagem = 'Por favor, informe o nome do usuário.'; $tipo_mensagem = 'error';
        } elseif ($cristal_id <= 0) {
            $mensagem = 'Por favor, selecione um tipo de cristal.'; $tipo_mensagem = 'error';
        } elseif ($quantidade <= 0 || $quantidade > 999) {
            $mensagem = 'Quantidade inválida. Use um valor entre 1 e 999.'; $tipo_mensagem = 'error';
        } else {
            $stmt = $conexao->prepare("SELECT id, usuario FROM usuarios WHERE usuario LIKE ?");
            $stmt->execute([$usuario_busca]);
            $usuario_destino = $stmt->fetch();
            if (!$usuario_destino) {
                $mensagem = "Usuário '$usuario_busca' não encontrado."; $tipo_mensagem = 'error';
            } else {
                try {
                    $stmt = $conexao->prepare("INSERT INTO usaveis (usuarioid, itemid, status) VALUES (?, ?, 'off')");
                    for ($i = 0; $i < $quantidade; $i++) $stmt->execute([$usuario_destino['id'], $cristal_id]);
                    $cristal_nome = '';
                    foreach ($cristais as $c) { if ($c['id'] == $cristal_id) { $cristal_nome = $c['nome']; break; } }
                    $mensagem = "✅ Adicionado $quantidade x '$cristal_nome' para '{$usuario_destino['usuario']}'!";
                    $tipo_mensagem = 'success';
                } catch (Exception $e) {
                    $mensagem = "Erro ao adicionar cristais: " . $e->getMessage(); $tipo_mensagem = 'error';
                }
            }
        }
    }
}

$stmt = $conexao->query("
    SELECT u.usuario, u.id as userid, tu.nome as cristal_nome, COUNT(*) as quantidade
    FROM usaveis us
    JOIN usuarios u ON us.usuarioid = u.id
    JOIN table_usaveis tu ON us.itemid = tu.id
    WHERE tu.categoria = 'cristal'
    GROUP BY u.id, us.itemid ORDER BY u.usuario, tu.nome
");
$jogadores_cristais = $stmt->fetchAll();

// ── Buff Crystals ─────────────────────────────────────────────────────────────
$cristais_buff = $conexao->query("SELECT * FROM table_usaveis WHERE categoria = 'cristal_buff' ORDER BY id")->fetchAll();
$mensagem_buff = '';
$tipo_mensagem_buff = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adicionar_cristal_buff') {
    $usuario_busca = trim($_POST['usuario_buff'] ?? '');
    $cristal_id    = (int)($_POST['cristal_buff_id'] ?? 0);
    $quantidade    = (int)($_POST['quantidade_buff'] ?? 1);

    if (empty($usuario_busca)) {
        $mensagem_buff = 'Informe o nome do jogador.'; $tipo_mensagem_buff = 'error';
    } elseif ($cristal_id <= 0) {
        $mensagem_buff = 'Selecione um tipo de cristal de buff.'; $tipo_mensagem_buff = 'error';
    } elseif ($quantidade <= 0 || $quantidade > 99) {
        $mensagem_buff = 'Quantidade inválida. Use entre 1 e 99.'; $tipo_mensagem_buff = 'error';
    } else {
        $stmt = $conexao->prepare("SELECT id, usuario FROM usuarios WHERE LOWER(usuario) = LOWER(?)");
        $stmt->execute([$usuario_busca]);
        $usuario_destino = $stmt->fetch();
        if (!$usuario_destino) {
            $mensagem_buff = "Usuário '$usuario_busca' não encontrado."; $tipo_mensagem_buff = 'error';
        } else {
            try {
                $stmt = $conexao->prepare("INSERT INTO usaveis (usuarioid, itemid, status) VALUES (?, ?, 'off')");
                for ($i = 0; $i < $quantidade; $i++) $stmt->execute([$usuario_destino['id'], $cristal_id]);
                $nome_cb = '';
                foreach ($cristais_buff as $c) { if ($c['id'] == $cristal_id) { $nome_cb = $c['nome']; break; } }
                $mensagem_buff = "✅ Adicionado $quantidade x '$nome_cb' para '{$usuario_destino['usuario']}'!";
                $tipo_mensagem_buff = 'success';
            } catch (Exception $e) {
                $mensagem_buff = "Erro: " . $e->getMessage(); $tipo_mensagem_buff = 'error';
            }
        }
    }
}

$stmt = $conexao->query("
    SELECT u.usuario, tu.nome as cristal_nome, COUNT(*) as quantidade
    FROM usaveis us
    JOIN usuarios u ON us.usuarioid = u.id
    JOIN table_usaveis tu ON us.itemid = tu.id
    WHERE tu.categoria = 'cristal_buff'
    GROUP BY u.id, us.itemid ORDER BY u.usuario, tu.nome
");
$jogadores_buff = $stmt->fetchAll();

// ── Craft Crystals ────────────────────────────────────────────────────────────
$cristais_craft = $conexao->query("SELECT * FROM table_usaveis WHERE categoria = 'cristal_craft' ORDER BY id")->fetchAll();
$mensagem_craft = '';
$tipo_mensagem_craft = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adicionar_cristal_craft') {
    $usuario_busca = trim($_POST['usuario_craft'] ?? '');
    $cristal_id    = (int)($_POST['cristal_craft_id'] ?? 0);
    $quantidade    = (int)($_POST['quantidade_craft'] ?? 1);

    if (empty($usuario_busca)) {
        $mensagem_craft = 'Informe o nome do jogador.'; $tipo_mensagem_craft = 'error';
    } elseif ($cristal_id <= 0) {
        $mensagem_craft = 'Selecione um tipo de cristal de craft.'; $tipo_mensagem_craft = 'error';
    } elseif ($quantidade <= 0 || $quantidade > 99) {
        $mensagem_craft = 'Quantidade inválida. Use entre 1 e 99.'; $tipo_mensagem_craft = 'error';
    } else {
        $stmt = $conexao->prepare("SELECT id, usuario FROM usuarios WHERE LOWER(usuario) = LOWER(?)");
        $stmt->execute([$usuario_busca]);
        $usuario_destino = $stmt->fetch();
        if (!$usuario_destino) {
            $mensagem_craft = "Usuário '$usuario_busca' não encontrado."; $tipo_mensagem_craft = 'error';
        } else {
            try {
                $stmt = $conexao->prepare("INSERT INTO usaveis (usuarioid, itemid, status) VALUES (?, ?, 'off')");
                for ($i = 0; $i < $quantidade; $i++) $stmt->execute([$usuario_destino['id'], $cristal_id]);
                $nome_cc = '';
                foreach ($cristais_craft as $c) { if ($c['id'] == $cristal_id) { $nome_cc = $c['nome']; break; } }
                $mensagem_craft = "✅ Adicionado $quantidade x '$nome_cc' para '{$usuario_destino['usuario']}'!";
                $tipo_mensagem_craft = 'success';
            } catch (Exception $e) {
                $mensagem_craft = "Erro: " . $e->getMessage(); $tipo_mensagem_craft = 'error';
            }
        }
    }
}

$stmt = $conexao->query("
    SELECT u.usuario, tu.nome as cristal_nome, COUNT(*) as quantidade
    FROM usaveis us
    JOIN usuarios u ON us.usuarioid = u.id
    JOIN table_usaveis tu ON us.itemid = tu.id
    WHERE tu.categoria = 'cristal_craft'
    GROUP BY u.id, us.itemid ORDER BY u.usuario, tu.nome
");
$jogadores_craft = $stmt->fetchAll();

$page_title = 'Gerenciar Cristais';
include 'adm_header.php';
?>

<div class="box_top">💎 Gerenciar Cristais</div>
<div class="box_middle">

<?php if ($mensagem_cad && in_array($_POST['action'] ?? '', ['cadastrar_cristal'], true)): ?>
    <div class="alert-<?php echo $tipo_mensagem_cad; ?>"><?php echo htmlspecialchars($mensagem_cad); ?></div>
    <div class="sep"></div>
<?php endif; ?>
<?php if ($mensagem_del && ($_POST['categoria'] ?? '') === 'cristal'): ?>
    <div class="alert-<?php echo $tipo_mensagem_del; ?>"><?php echo htmlspecialchars($mensagem_del); ?></div>
    <div class="sep"></div>
<?php endif; ?>

<div style="background:#1a1200;border-left:3px solid #FFD700;padding:8px 12px;margin-bottom:8px;">
    <b style="color:#FFD700;">📘 Para que serve?</b><br>
    <span class="sub2">Os <b>Cristais de Refinamento</b> são consumíveis usados pelo jogador no <b>Ferreiro</b> para
    aumentar o nível de refinamento de um equipamento (+1 → +20). Quanto maior o refinamento, mais bônus de
    atributo o item concede. Cada cristal cadastrado aqui aparece como uma opção de refino para os jogadores.</span>
</div>

<h3>🆕 Cadastrar novo Cristal de Refinamento</h3>
<div class="sep"></div>
<form method="POST" enctype="multipart/form-data" style="background:#1a1200;border:1px solid #555;padding:8px;">
    <input type="hidden" name="action" value="cadastrar_cristal">
    <table width="100%">
        <tr>
            <td width="30%"><label>Nome:</label><br><input type="text" name="novo_nome" maxlength="100" required placeholder="Ex.: Cristal Maior do Ferreiro" style="width:100%;"></td>
            <td width="40%"><label>Descrição (uso/efeito):</label><br><input type="text" name="novo_desc" maxlength="255" placeholder="Ex.: Refina equipamentos +5 a +10 com 80% de chance" style="width:100%;"></td>
            <td width="20%"><label>Imagem (opcional):</label><br><input type="file" name="novo_imagem" accept="image/*" style="width:100%;"></td>
            <td width="10%" valign="bottom"><button type="submit" class="botao btn-success">➕ Cadastrar</button></td>
        </tr>
    </table>
    <div class="sub2" style="margin-top:4px;">Imagem salva em <code>_img/ferreiro/</code>. Categoria: <code>cristal</code>.</div>
</form>

<div class="sep"></div>
<h3>🗑️ Remover Tipo de Cristal de Refinamento</h3>
<div class="sub2" style="margin-bottom:6px;">Apaga o tipo de cristal e <b>zera os estoques</b> de todos os jogadores para esse tipo. Não dá para desfazer.</div>
<?php if (empty($cristais)): ?>
    <p class="sub2">Nenhum cristal de refinamento cadastrado.</p>
<?php else: ?>
<table class="adm-table">
    <tr><th width="50">Img</th><th>Nome</th><th>Descrição</th><th width="100">Ação</th></tr>
    <?php foreach ($cristais as $cr): ?>
        <tr>
            <td align="center">
                <?php if (!empty($cr['imagem'])): ?>
                    <img src="../_img/ferreiro/<?php echo htmlspecialchars($cr['imagem']); ?>" onerror="this.style.display='none'" style="width:32px;height:32px;object-fit:contain;">
                <?php endif; ?>
            </td>
            <td><b style="color:#FFD700;"><?php echo htmlspecialchars($cr['nome']); ?></b></td>
            <td class="sub2"><?php echo htmlspecialchars($cr['descricao']); ?></td>
            <td>
                <form method="POST" onsubmit="return confirm('Remover \'<?php echo htmlspecialchars(addslashes($cr['nome'])); ?>\'?\nIsso vai apagar o tipo e zerar os estoques de todos os jogadores.');" style="margin:0;">
                    <input type="hidden" name="action" value="remover_cristal">
                    <input type="hidden" name="categoria" value="cristal">
                    <input type="hidden" name="cristal_id" value="<?php echo (int)$cr['id']; ?>">
                    <button type="submit" class="botao btn-danger" style="font-size:11px;">❌ Remover</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
<div class="sep"></div>

<?php if ($mensagem): ?>
    <div class="alert-<?php echo $tipo_mensagem; ?>"><?php echo htmlspecialchars($mensagem); ?></div>
    <div class="sep"></div>
<?php endif; ?>

<h3>➕ Adicionar Cristais a um Jogador</h3>
<div class="sep"></div>

<form method="POST" onsubmit="return validarFormulario()">
    <input type="hidden" name="action" value="adicionar_cristal">
    <table width="100%">
        <tr>
            <td width="50%" valign="top" style="padding-right:10px;">
                <label>Nome do Jogador:</label><br>
                <input type="text" name="usuario" id="usuario" placeholder="Nome exato do jogador" style="width:100%;">
            </td>
            <td width="20%" valign="top" style="padding-right:10px;">
                <label>Quantidade:</label><br>
                <input type="number" name="quantidade" id="quantidade" value="1" min="1" max="999" style="width:100%;">
            </td>
            <td width="30%" valign="bottom">
                <button type="submit" class="botao btn-success">💎 Adicionar Cristais</button>
            </td>
        </tr>
    </table>

    <div class="sep"></div>
    <label>Selecione o tipo de cristal:</label>
    <div class="sep"></div>

    <table width="100%">
        <tr>
        <?php foreach ($cristais as $i => $cristal): ?>
        <td valign="top" style="padding:5px;">
            <label style="display:block; cursor:pointer;" id="card_<?php echo $cristal['id']; ?>" onclick="selecionarCristal(<?php echo $cristal['id']; ?>)">
                <div style="border:1px solid #555; background:#1a1200; padding:10px; text-align:center;">
                    <?php if (!empty($cristal['imagem'])): ?>
                    <img src="../_img/ferreiro/<?php echo htmlspecialchars($cristal['imagem']); ?>"
                         onerror="this.style.display='none'"
                         style="width:48px;height:48px;object-fit:contain;display:block;margin:0 auto 6px auto;" />
                    <?php endif; ?>
                    <div style="font-weight:bold; color:#FFD700; margin-bottom:5px;"><?php echo htmlspecialchars($cristal['nome']); ?></div>
                    <div class="sub2"><?php echo htmlspecialchars($cristal['descricao']); ?></div>
                    <input type="radio" name="cristal_id" value="<?php echo $cristal['id']; ?>" style="display:none;">
                </div>
            </label>
        </td>
        <?php endforeach; ?>
        </tr>
    </table>

    <div id="erro-validacao" class="alert-error" style="display:none;"></div>
</form>

<div class="sep"></div>

<h3>📊 Cristais dos Jogadores</h3>
<div class="sep"></div>

<?php if (empty($jogadores_cristais)): ?>
    <p class="sub2">Nenhum jogador possui cristais de refinamento ainda.</p>
<?php else: ?>
<table class="adm-table">
    <tr>
        <th>Jogador</th>
        <th>Tipo de Cristal</th>
        <th>Quantidade</th>
    </tr>
    <?php foreach ($jogadores_cristais as $item): ?>
    <tr>
        <td><?php echo htmlspecialchars($item['usuario']); ?></td>
        <td><?php echo htmlspecialchars($item['cristal_nome']); ?></td>
        <td><span style="background:#ff6600; color:#fff; padding:2px 8px; font-weight:bold; font-size:11px;"><?php echo (int)$item['quantidade']; ?>x</span></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

</div>
<div class="box_bottom"></div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- ══ CRISTAIS DE BUFF ══════════════════════════════════════ -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div class="box_top" style="background:linear-gradient(90deg,#0a1a0a,#0d2a0d,#0a1a0a); margin-top:10px;">💎 Cristais de Buff</div>
<div class="box_middle">

<?php if ($mensagem_cad && in_array($_POST['action'] ?? '', ['cadastrar_cristal_buff'], true)): ?>
    <div class="alert-<?php echo $tipo_mensagem_cad; ?>"><?php echo htmlspecialchars($mensagem_cad); ?></div>
    <div class="sep"></div>
<?php endif; ?>
<?php if ($mensagem_del && ($_POST['categoria'] ?? '') === 'cristal_buff'): ?>
    <div class="alert-<?php echo $tipo_mensagem_del; ?>"><?php echo htmlspecialchars($mensagem_del); ?></div>
    <div class="sep"></div>
<?php endif; ?>

<div style="background:#0a1a0a;border-left:3px solid #5ecf6e;padding:8px 12px;margin-bottom:8px;">
    <b style="color:#5ecf6e;">📘 Para que serve?</b><br>
    <span class="sub2">Os <b>Cristais de Buff</b> são consumíveis usados pelo jogador para receber
    <b>bônus temporários de atributos</b> (Taijutsu, Genjutsu, Ninjutsu, Vida, etc.) durante missões e batalhas.
    A descrição deve deixar claro qual stat e percentual o cristal aplica (ex.: <code>+5% Taijutsu por 30 min</code>).</span>
</div>

<h3>🆕 Cadastrar novo Cristal de Buff</h3>
<div class="sep"></div>
<form method="POST" enctype="multipart/form-data" style="background:#0a1a0a;border:1px solid #2a6a2a;padding:8px;">
    <input type="hidden" name="action" value="cadastrar_cristal_buff">
    <table width="100%">
        <tr>
            <td width="30%"><label>Nome:</label><br><input type="text" name="novo_nome" maxlength="100" required placeholder="Ex.: Cristal do Tigre" style="width:100%;"></td>
            <td width="40%"><label>Descrição (livre):</label><br><input type="text" name="novo_desc" maxlength="255" placeholder="Ex.: Concentração ancestral dos felinos" style="width:100%;"></td>
            <td width="20%"><label>Imagem (PNG/JPG):</label><br><input type="file" name="novo_imagem" accept="image/*" style="width:100%;"></td>
            <td width="10%" valign="bottom"><button type="submit" class="botao btn-success">➕ Cadastrar</button></td>
        </tr>
    </table>
    <div class="sep"></div>
    <table width="100%">
        <tr>
            <td width="40%" valign="top">
                <label><b>Efeito do cristal:</b></label><br>
                <select name="tipo_efeito" id="tipo_efeito" onchange="atualizarCamposEfeito()" style="width:100%;" required>
                    <option value="">— Selecione um efeito —</option>
                    <?php foreach ($cristal_efeitos_buff as $tk => $td): ?>
                        <option value="<?php echo htmlspecialchars($tk); ?>"
                                data-desc="<?php echo htmlspecialchars($td['desc']); ?>"
                                data-campos='<?php echo htmlspecialchars(json_encode($td['campos'])); ?>'>
                            <?php echo htmlspecialchars($td['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="ef_descricao" class="sub2" style="margin-top:4px;color:#5ecf6e;"></div>
            </td>
            <td width="60%" valign="top">
                <div id="ef_campos_box" style="display:none;">
                    <label><b>Parâmetros do efeito:</b></label>
                    <div id="ef_campos" style="display:flex;gap:10px;flex-wrap:wrap;"></div>
                </div>
            </td>
        </tr>
    </table>
    <div class="sub2" style="margin-top:4px;">Imagem salva em <code>_img/Buff/</code>. Categoria: <code>cristal_buff</code>.
        Cada cristal precisa de um <b>tipo de efeito</b> — é isso que o diferencia dos demais.</div>
</form>

<script>
// Atualiza dinamicamente os inputs de parâmetros conforme o efeito escolhido.
function atualizarCamposEfeito() {
    var sel = document.getElementById('tipo_efeito');
    var opt = sel.options[sel.selectedIndex];
    var box = document.getElementById('ef_campos_box');
    var descEl = document.getElementById('ef_descricao');
    var div = document.getElementById('ef_campos');
    div.innerHTML = '';
    descEl.textContent = opt.getAttribute('data-desc') || '';
    if (!sel.value) { box.style.display = 'none'; return; }
    var campos = {};
    try { campos = JSON.parse(opt.getAttribute('data-campos') || '{}'); } catch (e) {}
    var keys = Object.keys(campos);
    if (keys.length === 0) { box.style.display = 'none'; return; }
    box.style.display = 'block';
    keys.forEach(function(k) {
        var lbl = document.createElement('label');
        lbl.style.display = 'flex';
        lbl.style.flexDirection = 'column';
        lbl.style.fontSize = '11px';
        lbl.innerHTML = '<span>'+k+' ('+campos[k]+')</span>' +
                        '<input type="number" name="ef_'+k+'" min="1" max="10000" required '+
                        'style="width:90px;" />';
        div.appendChild(lbl);
    });
}
</script>

<div class="sep"></div>
<h3>🗑️ Remover Tipo de Cristal de Buff</h3>
<?php if (empty($cristais_buff)): ?>
    <p class="sub2">Nenhum cristal de buff cadastrado.</p>
<?php else: ?>
<table class="adm-table">
    <tr><th width="50">Img</th><th>Nome</th><th>Efeito</th><th>Descrição</th><th width="100">Ação</th></tr>
    <?php foreach ($cristais_buff as $cr):
        $te = $cr['tipo_efeito'] ?? null;
        $ve = $cr['valor_efeito'] ?? null;
        $efeito_str = '— (legado)';
        if ($te && isset($cristal_efeitos_buff[$te])) {
            $efeito_str = $cristal_efeitos_buff[$te]['label'];
            if ($ve) {
                $vv = json_decode($ve, true);
                if (is_array($vv) && !empty($vv)) {
                    $partes = [];
                    foreach ($vv as $kk => $val) $partes[] = $kk.'='.$val;
                    $efeito_str .= ' ['.implode(', ',$partes).']';
                }
            }
        }
    ?>
        <tr>
            <td align="center">
                <?php if (!empty($cr['imagem'])): ?>
                    <img src="../_img/Buff/<?php echo htmlspecialchars($cr['imagem']); ?>" onerror="this.style.display='none'" style="width:32px;height:32px;object-fit:contain;">
                <?php endif; ?>
            </td>
            <td><b style="color:#5ecf6e;"><?php echo htmlspecialchars($cr['nome']); ?></b></td>
            <td class="sub2" style="color:#FFD700;"><?php echo htmlspecialchars($efeito_str); ?></td>
            <td class="sub2"><?php echo htmlspecialchars($cr['descricao']); ?></td>
            <td>
                <form method="POST" onsubmit="return confirm('Remover \'<?php echo htmlspecialchars(addslashes($cr['nome'])); ?>\'? Estoques serão zerados.');" style="margin:0;">
                    <input type="hidden" name="action" value="remover_cristal">
                    <input type="hidden" name="categoria" value="cristal_buff">
                    <input type="hidden" name="cristal_id" value="<?php echo (int)$cr['id']; ?>">
                    <button type="submit" class="botao btn-danger" style="font-size:11px;">❌ Remover</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
<div class="sep"></div>

<?php if ($mensagem_buff): ?>
    <div class="alert-<?php echo $tipo_mensagem_buff; ?>"><?php echo htmlspecialchars($mensagem_buff); ?></div>
    <div class="sep"></div>
<?php endif; ?>

<h3>➕ Adicionar Cristal de Buff a um Jogador</h3>
<div class="sep"></div>

<form method="POST" onsubmit="return validarFormularioBuff()">
    <input type="hidden" name="action" value="adicionar_cristal_buff">
    <table width="100%">
        <tr>
            <td width="50%" valign="top" style="padding-right:10px;">
                <label>Nome do Jogador:</label><br>
                <input type="text" name="usuario_buff" id="usuario_buff" placeholder="Nome do jogador" style="width:100%;">
            </td>
            <td width="20%" valign="top" style="padding-right:10px;">
                <label>Quantidade:</label><br>
                <input type="number" name="quantidade_buff" id="quantidade_buff" value="1" min="1" max="99" style="width:100%;">
            </td>
            <td width="30%" valign="bottom">
                <button type="submit" class="botao btn-success">💎 Adicionar Cristais de Buff</button>
            </td>
        </tr>
    </table>
    <div class="sep"></div>
    <label>Selecione o tipo de cristal de buff:</label>
    <div class="sep"></div>
    <table width="100%">
        <tr>
        <?php foreach ($cristais_buff as $cristal): ?>
        <td valign="top" style="padding:5px;">
            <label style="display:block; cursor:pointer;" id="buff_card_<?php echo $cristal['id']; ?>" onclick="selecionarBuff(<?php echo $cristal['id']; ?>)">
                <div style="border:1px solid #2a6a2a; background:#0a1a0a; padding:10px; text-align:center;">
                    <img src="../_img/Buff/<?php echo htmlspecialchars($cristal['imagem']); ?>"
                         onerror="this.style.display='none'"
                         style="width:48px;height:48px;object-fit:contain;display:block;margin:0 auto 6px auto;" />
                    <div style="font-weight:bold; color:#5ecf6e; margin-bottom:5px;"><?php echo htmlspecialchars($cristal['nome']); ?></div>
                    <div class="sub2"><?php echo htmlspecialchars($cristal['descricao']); ?></div>
                    <input type="radio" name="cristal_buff_id" value="<?php echo $cristal['id']; ?>" style="display:none;">
                </div>
            </label>
        </td>
        <?php endforeach; ?>
        </tr>
    </table>
    <div id="erro-buff" class="alert-error" style="display:none;"></div>
</form>

<div class="sep"></div>
<h3>📊 Cristais de Buff dos Jogadores</h3>
<div class="sep"></div>

<?php if (empty($jogadores_buff)): ?>
    <p class="sub2">Nenhum jogador possui cristais de buff ainda.</p>
<?php else: ?>
<table class="adm-table">
    <tr>
        <th>Jogador</th>
        <th>Tipo de Cristal</th>
        <th>Quantidade</th>
    </tr>
    <?php foreach ($jogadores_buff as $item): ?>
    <tr>
        <td><?php echo htmlspecialchars($item['usuario']); ?></td>
        <td><?php echo htmlspecialchars($item['cristal_nome']); ?></td>
        <td><span style="background:#1a5a1a; color:#5ecf6e; padding:2px 8px; font-weight:bold; font-size:11px; border:1px solid #5ecf6e;"><?php echo (int)$item['quantidade']; ?>x</span></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

</div>
<div class="box_bottom"></div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- ══ CRISTAIS DE CRAFT ═════════════════════════════════════ -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div class="box_top" style="background:linear-gradient(90deg,#1a0a1a,#2a0d2a,#1a0a1a); margin-top:10px;">⚙️ Cristais de Craft</div>
<div class="box_middle">

<?php if ($mensagem_cad && in_array($_POST['action'] ?? '', ['cadastrar_cristal_craft'], true)): ?>
    <div class="alert-<?php echo $tipo_mensagem_cad; ?>"><?php echo htmlspecialchars($mensagem_cad); ?></div>
    <div class="sep"></div>
<?php endif; ?>
<?php if ($mensagem_del && ($_POST['categoria'] ?? '') === 'cristal_craft'): ?>
    <div class="alert-<?php echo $tipo_mensagem_del; ?>"><?php echo htmlspecialchars($mensagem_del); ?></div>
    <div class="sep"></div>
<?php endif; ?>

<div style="background:#1a0a1a;border-left:3px solid #c084fc;padding:8px 12px;margin-bottom:8px;">
    <b style="color:#c084fc;">📘 Para que serve?</b><br>
    <span class="sub2">Os <b>Cristais de Craft</b> são insumos usados na <b>fabricação (forja) de equipamentos</b>
    e itens raros. Servem como matéria-prima nas receitas dos jogadores. A descrição deve indicar o tipo do
    insumo (ex.: <code>Núcleo elemental — necessário para forjar armas raras</code>).</span>
</div>

<h3>🆕 Cadastrar novo Cristal de Craft</h3>
<div class="sep"></div>
<form method="POST" enctype="multipart/form-data" style="background:#1a0a1a;border:1px solid #6a2a6a;padding:8px;">
    <input type="hidden" name="action" value="cadastrar_cristal_craft">
    <table width="100%">
        <tr>
            <td width="20%"><label>Nome:</label><br><input type="text" name="novo_nome" maxlength="100" required placeholder="Ex.: Cristal de Chakra Refinado" style="width:100%;"></td>
            <td width="25%"><label>Descrição (uso/efeito):</label><br><input type="text" name="novo_desc" maxlength="255" placeholder="Ex.: Insumo para forjar equipamentos raros" style="width:100%;"></td>
            <td width="11%"><label title="Quantos fragmentos formam 1 cristal completo.">🧩 Fragmentos:</label><br>
                <input type="number" name="fragmentos_necessarios" value="<?php echo CRISTAL_FRAG_DEFAULT; ?>" min="<?php echo CRISTAL_FRAG_MIN; ?>" max="<?php echo CRISTAL_FRAG_MAX; ?>" required style="width:100%;text-align:center;font-weight:bold;color:#cf6ecf;"></td>
            <td width="17%"><label title="Imagem do CRISTAL completo (a forma final).">💎 Imagem do Cristal:</label><br><input type="file" name="novo_imagem" accept="image/*" style="width:100%;"></td>
            <td width="17%"><label title="Imagem do FRAGMENTO (a versão quebrada que o jogador junta). Opcional — sem upload usa a do cristal com filtro roxo.">🧩 Imagem do Fragmento:</label><br><input type="file" name="novo_imagem_fragmento" accept="image/*" style="width:100%;"></td>
            <td width="10%" valign="bottom"><button type="submit" class="botao btn-success">➕ Cadastrar</button></td>
        </tr>
    </table>
    <div class="sub2" style="margin-top:4px;">
        💎 cristal salvo em <code>_img/Craft/</code> · 🧩 fragmento salvo em <code>_img/Craft/fragmentos/</code>.
        <b style="color:#cf6ecf;">Receita:</b> N fragmentos → 1× cristal (N entre <?php echo CRISTAL_FRAG_MIN; ?> e <?php echo CRISTAL_FRAG_MAX; ?>; padrão <?php echo CRISTAL_FRAG_DEFAULT; ?>).
        Sem upload da imagem do fragmento, o jogo usa a imagem do cristal com filtro roxo.
    </div>
</form>

<div class="sep"></div>
<h3>🧩 Receitas e Remoção de Cristais de Craft</h3>
<div class="sub2" style="margin-bottom:6px;">A coluna <b style="color:#cf6ecf;">Receita</b> mostra quantos fragmentos o jogador precisa juntar no Ferreiro para formar 1 unidade do cristal completo. Você pode editar a quantidade direto na linha e clicar em 💾 Salvar.</div>

<?php if ($mensagem_receita): ?>
    <div class="alert-<?php echo $tipo_mensagem_receita; ?>"><?php echo htmlspecialchars($mensagem_receita); ?></div>
    <div class="sep"></div>
<?php endif; ?>

<?php if (empty($cristais_craft)): ?>
    <p class="sub2">Nenhum cristal de craft cadastrado.</p>
<?php else: ?>
<table class="adm-table">
    <tr>
        <th width="60">💎 Cristal</th>
        <th>Nome / Descrição</th>
        <th width="420">🧩 Receita visual (fragmento → cristal)</th>
        <th width="100">Ação</th>
    </tr>
    <?php foreach ($cristais_craft as $cr): ?>
        <?php
            $frag_atual = (int)($cr['fragmentos_necessarios'] ?? 0);
            if ($frag_atual <= 0) $frag_atual = CRISTAL_FRAG_DEFAULT;
            $img_frag = trim((string)($cr['imagem_fragmento'] ?? ''));
            $tem_img_frag = ($img_frag !== '');
        ?>
        <tr>
            <td align="center">
                <?php if (!empty($cr['imagem'])): ?>
                    <img src="../_img/Craft/<?php echo htmlspecialchars($cr['imagem']); ?>" onerror="this.style.display='none'" style="width:42px;height:42px;object-fit:contain;">
                <?php endif; ?>
            </td>
            <td>
                <b style="color:#c084fc;"><?php echo htmlspecialchars($cr['nome']); ?></b><br>
                <span class="sub2"><?php echo htmlspecialchars($cr['descricao']); ?></span>
            </td>
            <td>
                <form method="POST" enctype="multipart/form-data" style="margin:0;">
                    <input type="hidden" name="action" value="editar_receita_craft">
                    <input type="hidden" name="cristal_id" value="<?php echo (int)$cr['id']; ?>">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <!-- Imagem do FRAGMENTO -->
                        <div style="text-align:center;">
                            <?php if ($tem_img_frag): ?>
                                <img src="../_img/Craft/fragmentos/<?php echo htmlspecialchars($img_frag); ?>?v=<?php echo (int)$cr['id']; ?>" onerror="this.style.display='none'" style="width:38px;height:38px;object-fit:contain;border:1px solid #cf6ecf;background:#0a000a;padding:1px;">
                            <?php elseif (!empty($cr['imagem'])): ?>
                                <img src="../_img/Craft/<?php echo htmlspecialchars($cr['imagem']); ?>" onerror="this.style.display='none'" style="width:38px;height:38px;object-fit:contain;border:1px dashed #555;padding:1px;filter:grayscale(80%) brightness(0.7) sepia(0.4) hue-rotate(260deg);">
                            <?php else: ?>
                                <div style="width:38px;height:38px;border:1px dashed #555;color:#555;font-size:9px;display:flex;align-items:center;justify-content:center;">sem img</div>
                            <?php endif; ?>
                            <div style="font-size:9px;color:#aaa;margin-top:1px;"><?php echo $tem_img_frag ? 'fragmento' : 'fallback'; ?></div>
                        </div>

                        <!-- Quantidade x -->
                        <div style="text-align:center;">
                            <input type="number" name="fragmentos_necessarios" value="<?php echo $frag_atual; ?>" min="<?php echo CRISTAL_FRAG_MIN; ?>" max="<?php echo CRISTAL_FRAG_MAX; ?>" required title="Quantos fragmentos formam 1 cristal" style="width:50px;text-align:center;font-weight:bold;color:#cf6ecf;background:#1a0a1a;border:1px solid #6a2a6a;">
                            <div style="font-size:9px;color:#aaa;">qtd</div>
                        </div>

                        <span style="color:#cf6ecf;font-weight:bold;font-size:18px;">→</span>

                        <!-- Imagem do CRISTAL -->
                        <div style="text-align:center;">
                            <?php if (!empty($cr['imagem'])): ?>
                                <img src="../_img/Craft/<?php echo htmlspecialchars($cr['imagem']); ?>" onerror="this.style.display='none'" style="width:38px;height:38px;object-fit:contain;border:1px solid #c084fc;background:#0a000a;padding:1px;">
                            <?php endif; ?>
                            <div style="font-size:9px;color:#c084fc;margin-top:1px;">1× cristal</div>
                        </div>

                        <div style="flex:1 1 100%;border-top:1px dotted #444;margin-top:4px;padding-top:4px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                            <span class="sub2" style="font-size:10px;">📷 trocar img frag.:</span>
                            <input type="file" name="imagem_fragmento_edit" accept="image/*" style="font-size:10px;max-width:160px;">
                            <button type="submit" class="botao btn-success" style="font-size:10px;padding:2px 6px;">💾 Salvar</button>
                            <?php if ($tem_img_frag): ?>
                                <button type="submit" formnovalidate name="action" value="remover_imagem_fragmento" class="botao btn-danger" style="font-size:10px;padding:2px 6px;" onclick="return confirm('Remover a imagem do fragmento? (Vai voltar ao filtro roxo na imagem do cristal.)');">🧹 Limpar img</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </td>
            <td>
                <form method="POST" onsubmit="return confirm('Remover \'<?php echo htmlspecialchars(addslashes($cr['nome'])); ?>\'? Estoques serão zerados.');" style="margin:0;">
                    <input type="hidden" name="action" value="remover_cristal">
                    <input type="hidden" name="categoria" value="cristal_craft">
                    <input type="hidden" name="cristal_id" value="<?php echo (int)$cr['id']; ?>">
                    <button type="submit" class="botao btn-danger" style="font-size:11px;">❌ Remover Cristal</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
<div class="sep"></div>

<?php if ($mensagem_craft): ?>
    <div class="alert-<?php echo $tipo_mensagem_craft; ?>"><?php echo htmlspecialchars($mensagem_craft); ?></div>
    <div class="sep"></div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- 🧩 FRAGMENTOS DE CRAFT (entidades próprias com cristal alvo)            -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<h3>🧩 Fragmentos de Craft</h3>
<div class="sub2" style="margin-bottom:6px;">
    Fragmentos são itens próprios (com nome e imagem). Cada fragmento aponta para 1 cristal alvo (já cadastrado acima).
    Quando o jogador junta a quantidade configurada no Ferreiro, vira automaticamente o cristal escolhido.
    Vários fragmentos diferentes podem virar o mesmo cristal.
</div>
<div class="sep"></div>

<?php if ($mensagem_frag): ?>
    <div class="alert-<?php echo $tipo_mensagem_frag; ?>"><?php echo htmlspecialchars($mensagem_frag); ?></div>
    <div class="sep"></div>
<?php endif; ?>

<?php if (empty($cristais_craft)): ?>
    <div class="alert-warning">
        ⚠️ Cadastre pelo menos 1 Cristal de Craft acima antes de criar fragmentos (você precisa escolher qual cristal cada fragmento vira).
    </div>
<?php else: ?>
<form method="POST" enctype="multipart/form-data" style="background:#1a0a1a;border:1px solid #6a2a6a;padding:8px;">
    <input type="hidden" name="action" value="cadastrar_fragmento_craft">
    <table width="100%">
        <tr>
            <td width="20%"><label>Nome do fragmento:</label><br>
                <input type="text" name="frag_nome" maxlength="100" required placeholder="Ex.: Fragmento Chakra Bruto" style="width:100%;"></td>
            <td width="22%"><label>Descrição (opcional):</label><br>
                <input type="text" name="frag_desc" maxlength="255" placeholder="Ex.: Pedaço bruto de cristal de chakra" style="width:100%;"></td>
            <td width="9%"><label title="Quantos fragmentos formam 1 cristal alvo.">🧩 Qtd:</label><br>
                <input type="number" name="frag_qtd" value="<?php echo CRISTAL_FRAG_DEFAULT; ?>" min="<?php echo CRISTAL_FRAG_MIN; ?>" max="<?php echo CRISTAL_FRAG_MAX; ?>" required style="width:100%;text-align:center;font-weight:bold;color:#cf6ecf;"></td>
            <td width="22%"><label title="Quando o jogador combinar no Ferreiro, vira este cristal.">💎 Vira o cristal:</label><br>
                <select name="cristal_alvo_id" required style="width:100%;background:#1a0a1a;color:#c084fc;border:1px solid #6a2a6a;padding:2px;">
                    <option value="">— escolha o cristal alvo —</option>
                    <?php foreach ($cristais_craft as $cc): ?>
                        <option value="<?php echo (int)$cc['id']; ?>"><?php echo htmlspecialchars($cc['nome']); ?></option>
                    <?php endforeach; ?>
                </select></td>
            <td width="17%"><label>📷 Imagem:</label><br>
                <select name="frag_imagem_galeria" style="width:100%;font-size:11px;background:#1a0a1a;color:#cf6ecf;border:1px solid #6a2a6a;">
                    <option value="">— da galeria —</option>
                    <?php foreach ($galeria_fragmentos as $gf): ?>
                        <option value="<?php echo htmlspecialchars($gf); ?>"><?php echo htmlspecialchars($gf); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="file" name="frag_imagem_upload" accept="image/*" style="width:100%;font-size:11px;margin-top:2px;" title="OU envie uma imagem nova"></td>
            <td width="10%" valign="bottom"><button type="submit" class="botao btn-success">➕ Cadastrar</button></td>
        </tr>
    </table>
    <div class="sub2" style="margin-top:4px;">
        Imagens salvas em <code>_img/Fragmento de Cristal/</code>. Use a galeria pra reaproveitar uma imagem existente, ou envie uma nova (PNG/JPG/WEBP/GIF).
    </div>
</form>

<div class="sep"></div>

<?php
$fragmentos_craft = $conexao->query("
    SELECT f.*, c.nome AS alvo_nome, c.imagem AS alvo_imagem
    FROM table_usaveis f
    LEFT JOIN table_usaveis c ON f.cristal_alvo_id = c.id
    WHERE f.categoria = 'fragmento_craft'
    ORDER BY f.id
")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (empty($fragmentos_craft)): ?>
    <p class="sub2">Nenhum fragmento cadastrado ainda. Use o formulário acima.</p>
<?php else: ?>
<table class="adm-table">
    <tr>
        <th width="60">🧩 Frag.</th>
        <th>Nome / Descrição</th>
        <th width="450">🔁 Receita visual (fragmento → cristal alvo)</th>
        <th width="100">Ação</th>
    </tr>
    <?php foreach ($fragmentos_craft as $f):
        $f_qtd = (int)($f['fragmentos_necessarios'] ?? 0);
        if ($f_qtd <= 0) $f_qtd = CRISTAL_FRAG_DEFAULT;
        $img_url = '../_img/Fragmento%20de%20Cristal/' . rawurlencode($f['imagem'] ?? '');
    ?>
        <tr>
            <td align="center">
                <?php if (!empty($f['imagem'])): ?>
                    <img src="<?php echo $img_url; ?>?v=<?php echo (int)$f['id']; ?>" onerror="this.style.display='none'" style="width:42px;height:42px;object-fit:contain;border:1px solid #cf6ecf;background:#0a000a;padding:1px;">
                <?php endif; ?>
            </td>
            <td>
                <b style="color:#cf6ecf;"><?php echo htmlspecialchars($f['nome']); ?></b><br>
                <span class="sub2"><?php echo htmlspecialchars($f['descricao'] ?? ''); ?></span>
                <?php if (empty($f['cristal_alvo_id']) || empty($f['alvo_nome'])): ?>
                    <br><span style="color:#ff6b6b;font-size:10px;">⚠️ Cristal alvo ausente! Edite e selecione um cristal.</span>
                <?php endif; ?>
            </td>
            <td>
                <form method="POST" enctype="multipart/form-data" style="margin:0;">
                    <input type="hidden" name="action" value="editar_fragmento_craft">
                    <input type="hidden" name="frag_id" value="<?php echo (int)$f['id']; ?>">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <!-- Imagem do fragmento -->
                        <div style="text-align:center;">
                            <?php if (!empty($f['imagem'])): ?>
                                <img src="<?php echo $img_url; ?>?v=<?php echo (int)$f['id']; ?>" onerror="this.style.display='none'" style="width:38px;height:38px;object-fit:contain;border:1px solid #cf6ecf;background:#0a000a;padding:1px;">
                            <?php else: ?>
                                <div style="width:38px;height:38px;border:1px dashed #555;color:#555;font-size:9px;display:flex;align-items:center;justify-content:center;">sem img</div>
                            <?php endif; ?>
                            <div style="font-size:9px;color:#cf6ecf;margin-top:1px;">fragmento</div>
                        </div>

                        <!-- Quantidade -->
                        <div style="text-align:center;">
                            <input type="number" name="frag_qtd" value="<?php echo $f_qtd; ?>" min="<?php echo CRISTAL_FRAG_MIN; ?>" max="<?php echo CRISTAL_FRAG_MAX; ?>" required title="Quantos fragmentos formam 1 cristal alvo" style="width:50px;text-align:center;font-weight:bold;color:#cf6ecf;background:#1a0a1a;border:1px solid #6a2a6a;">
                            <div style="font-size:9px;color:#aaa;">qtd</div>
                        </div>

                        <span style="color:#cf6ecf;font-weight:bold;font-size:18px;">→</span>

                        <!-- Dropdown cristal alvo -->
                        <div style="text-align:center;flex:0 0 auto;">
                            <select name="cristal_alvo_id" required style="background:#1a0a1a;color:#c084fc;border:1px solid #6a2a6a;padding:2px;font-size:11px;max-width:160px;">
                                <?php foreach ($cristais_craft as $cc): ?>
                                    <option value="<?php echo (int)$cc['id']; ?>" <?php if ($cc['id'] == $f['cristal_alvo_id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($cc['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div style="font-size:9px;color:#c084fc;margin-top:1px;">cristal alvo</div>
                        </div>

                        <!-- Preview alvo -->
                        <?php if (!empty($f['alvo_imagem'])): ?>
                            <div style="text-align:center;">
                                <img src="../_img/Craft/<?php echo htmlspecialchars($f['alvo_imagem']); ?>" onerror="this.style.display='none'" style="width:38px;height:38px;object-fit:contain;border:1px solid #c084fc;background:#0a000a;padding:1px;">
                                <div style="font-size:9px;color:#c084fc;">1× cristal</div>
                            </div>
                        <?php endif; ?>

                        <div style="flex:1 1 100%;border-top:1px dotted #444;margin-top:4px;padding-top:4px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                            <span class="sub2" style="font-size:10px;">📷 trocar img:</span>
                            <select name="frag_imagem_galeria_edit" style="font-size:10px;background:#1a0a1a;color:#cf6ecf;border:1px solid #6a2a6a;max-width:160px;">
                                <option value="">— manter atual —</option>
                                <?php foreach ($galeria_fragmentos as $gf): ?>
                                    <option value="<?php echo htmlspecialchars($gf); ?>"><?php echo htmlspecialchars($gf); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="file" name="frag_imagem_upload_edit" accept="image/*" style="font-size:10px;max-width:140px;">
                            <button type="submit" class="botao btn-success" style="font-size:10px;padding:2px 6px;">💾 Salvar</button>
                        </div>
                    </div>
                </form>
            </td>
            <td>
                <div style="display:flex;flex-direction:column;gap:4px;">
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="action" value="dar_fragmento_teste">
                        <input type="hidden" name="frag_id" value="<?php echo (int)$f['id']; ?>">
                        <input type="hidden" name="qtd" value="5">
                        <button type="submit" class="botao" style="font-size:10px;background:#1a3a5a;color:#9cf;border:1px solid #4a7;width:100%;" title="Adiciona 5 deste fragmento ao SEU inventário para testar o Ferreiro.">🧪 TESTE +5</button>
                    </form>
                    <form method="POST" onsubmit="return confirm('Remover o fragmento \'<?php echo htmlspecialchars(addslashes($f['nome'])); ?>\'? Estoques dos jogadores serão zerados.');" style="margin:0;">
                        <input type="hidden" name="action" value="remover_fragmento_craft">
                        <input type="hidden" name="frag_id" value="<?php echo (int)$f['id']; ?>">
                        <button type="submit" class="botao btn-danger" style="font-size:11px;width:100%;">❌ Remover</button>
                    </form>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
<?php endif; /* fim cristais_craft não vazio */ ?>

<div class="sep"></div>

<h3>➕ Adicionar Cristal de Craft a um Jogador</h3>
<div class="sep"></div>

<?php if (empty($cristais_craft)): ?>
    <div class="alert-warning">
        ⚠️ Nenhum cristal de craft cadastrado em <code>table_usaveis</code> com categoria <code>cristal_craft</code>.
        Cadastre cristais de craft primeiro pelo editor de banco para poder distribuí-los.
    </div>
<?php else: ?>
<form method="POST" onsubmit="return validarFormularioCraft()">
    <input type="hidden" name="action" value="adicionar_cristal_craft">
    <table width="100%">
        <tr>
            <td width="50%" valign="top" style="padding-right:10px;">
                <label>Nome do Jogador:</label><br>
                <input type="text" name="usuario_craft" id="usuario_craft" placeholder="Nome do jogador" style="width:100%;">
            </td>
            <td width="20%" valign="top" style="padding-right:10px;">
                <label>Quantidade:</label><br>
                <input type="number" name="quantidade_craft" id="quantidade_craft" value="1" min="1" max="99" style="width:100%;">
            </td>
            <td width="30%" valign="bottom">
                <button type="submit" class="botao btn-success">⚙️ Adicionar Cristais de Craft</button>
            </td>
        </tr>
    </table>
    <div class="sep"></div>
    <label>Selecione o tipo de cristal de craft:</label>
    <div class="sep"></div>
    <table width="100%">
        <tr>
        <?php foreach ($cristais_craft as $cristal): ?>
        <?php $frag_card = (int)($cristal['fragmentos_necessarios'] ?? 0); if ($frag_card <= 0) $frag_card = CRISTAL_FRAG_DEFAULT; ?>
        <td valign="top" style="padding:5px;">
            <label style="display:block; cursor:pointer;" id="craft_card_<?php echo $cristal['id']; ?>" onclick="selecionarCraft(<?php echo $cristal['id']; ?>)">
                <div style="border:1px solid #6a2a6a; background:#1a0a1a; padding:10px; text-align:center;">
                    <img src="../_img/Craft/<?php echo htmlspecialchars($cristal['imagem']); ?>"
                         onerror="this.style.display='none'"
                         style="width:48px;height:48px;object-fit:contain;display:block;margin:0 auto 6px auto;" />
                    <div style="font-weight:bold; color:#cf6ecf; margin-bottom:5px;"><?php echo htmlspecialchars($cristal['nome']); ?></div>
                    <div class="sub2"><?php echo htmlspecialchars($cristal['descricao']); ?></div>
                    <div style="margin-top:6px;background:#2a0d2a;border:1px dashed #cf6ecf;padding:3px 6px;font-size:11px;color:#e0a0e0;">
                        🧩 <b><?php echo $frag_card; ?> frags</b> → 1× cristal
                    </div>
                    <input type="radio" name="cristal_craft_id" value="<?php echo $cristal['id']; ?>" style="display:none;">
                </div>
            </label>
        </td>
        <?php endforeach; ?>
        </tr>
    </table>
    <div id="erro-craft" class="alert-error" style="display:none;"></div>
</form>
<?php endif; ?>

<div class="sep"></div>
<h3>📊 Cristais de Craft dos Jogadores</h3>
<div class="sep"></div>

<?php if (empty($jogadores_craft)): ?>
    <p class="sub2">Nenhum jogador possui cristais de craft ainda.</p>
<?php else: ?>
<table class="adm-table">
    <tr>
        <th>Jogador</th>
        <th>Tipo de Cristal</th>
        <th>Quantidade</th>
    </tr>
    <?php foreach ($jogadores_craft as $item): ?>
    <tr>
        <td><?php echo htmlspecialchars($item['usuario']); ?></td>
        <td><?php echo htmlspecialchars($item['cristal_nome']); ?></td>
        <td><span style="background:#5a1a5a; color:#cf6ecf; padding:2px 8px; font-weight:bold; font-size:11px; border:1px solid #cf6ecf;"><?php echo (int)$item['quantidade']; ?>x</span></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

</div>
<div class="box_bottom"></div>

<script>
function selecionarCristal(id) {
    document.querySelectorAll('[id^="card_"]').forEach(function(c) {
        c.querySelector('div').style.borderColor = '#555';
        c.querySelector('div').style.background = '#1a1200';
    });
    var card = document.getElementById('card_' + id);
    card.querySelector('div').style.borderColor = '#ff6600';
    card.querySelector('div').style.background = '#2a1800';
    card.querySelector('input[type="radio"]').checked = true;
    document.getElementById('erro-validacao').style.display = 'none';
}
function validarFormulario() {
    var usuario = document.getElementById('usuario').value.trim();
    var quantidade = document.getElementById('quantidade').value;
    var cristalSelecionado = document.querySelector('input[name="cristal_id"]:checked');
    var erroDiv = document.getElementById('erro-validacao');
    if (!usuario) { erroDiv.textContent = '❌ Por favor, informe o nome do jogador.'; erroDiv.style.display = 'block'; return false; }
    if (!quantidade || quantidade < 1 || quantidade > 999) { erroDiv.textContent = '❌ Quantidade inválida. Use um valor entre 1 e 999.'; erroDiv.style.display = 'block'; return false; }
    if (!cristalSelecionado) { erroDiv.textContent = '❌ Por favor, selecione um tipo de cristal.'; erroDiv.style.display = 'block'; return false; }
    return true;
}
function selecionarBuff(id) {
    document.querySelectorAll('[id^="buff_card_"]').forEach(function(c) {
        c.querySelector('div').style.borderColor = '#2a6a2a';
        c.querySelector('div').style.background = '#0a1a0a';
    });
    var card = document.getElementById('buff_card_' + id);
    card.querySelector('div').style.borderColor = '#5ecf6e';
    card.querySelector('div').style.background = '#0d2a0d';
    card.querySelector('input[type="radio"]').checked = true;
    document.getElementById('erro-buff').style.display = 'none';
}
function validarFormularioBuff() {
    var usuario = document.getElementById('usuario_buff').value.trim();
    var quantidade = document.getElementById('quantidade_buff').value;
    var cristalSelecionado = document.querySelector('input[name="cristal_buff_id"]:checked');
    var erroDiv = document.getElementById('erro-buff');
    if (!usuario) { erroDiv.textContent = '❌ Informe o nome do jogador.'; erroDiv.style.display = 'block'; return false; }
    if (!quantidade || quantidade < 1 || quantidade > 99) { erroDiv.textContent = '❌ Quantidade inválida. Use entre 1 e 99.'; erroDiv.style.display = 'block'; return false; }
    if (!cristalSelecionado) { erroDiv.textContent = '❌ Selecione um tipo de cristal de buff.'; erroDiv.style.display = 'block'; return false; }
    return true;
}
function selecionarCraft(id) {
    document.querySelectorAll('[id^="craft_card_"]').forEach(function(c) {
        c.querySelector('div').style.borderColor = '#6a2a6a';
        c.querySelector('div').style.background = '#1a0a1a';
    });
    var card = document.getElementById('craft_card_' + id);
    card.querySelector('div').style.borderColor = '#cf6ecf';
    card.querySelector('div').style.background = '#2a0d2a';
    card.querySelector('input[type="radio"]').checked = true;
    document.getElementById('erro-craft').style.display = 'none';
}
function validarFormularioCraft() {
    var usuario = document.getElementById('usuario_craft').value.trim();
    var quantidade = document.getElementById('quantidade_craft').value;
    var cristalSelecionado = document.querySelector('input[name="cristal_craft_id"]:checked');
    var erroDiv = document.getElementById('erro-craft');
    if (!usuario) { erroDiv.textContent = '❌ Informe o nome do jogador.'; erroDiv.style.display = 'block'; return false; }
    if (!quantidade || quantidade < 1 || quantidade > 99) { erroDiv.textContent = '❌ Quantidade inválida. Use entre 1 e 99.'; erroDiv.style.display = 'block'; return false; }
    if (!cristalSelecionado) { erroDiv.textContent = '❌ Selecione um tipo de cristal de craft.'; erroDiv.style.display = 'block'; return false; }
    return true;
}
</script>

<?php include 'adm_footer.php'; ?>
