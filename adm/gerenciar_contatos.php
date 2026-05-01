<?php
/**
 * adm/gerenciar_contatos.php
 *
 * Painel ADM para editar os canais de contato (e-mail, Discord, redes sociais,
 * WhatsApp, etc.) que aparecem na página `?p=contact`.
 *
 * Os valores são gravados em `config/contato.php`, um arquivo PHP que devolve
 * um `return [...]` com chave => valor. Esse arquivo é lido por
 * `_inc/contact.php` e renderizado como ícones clicáveis.
 *
 * Acesso: somente via roteador `adm/adm.php?modulo=contatos`.
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../_inc/conexao.php';

if (empty($_SESSION['logado']) || empty($_SESSION['adm']) || !in_array((int)$_SESSION['adm'], [1, 2], true)) {
    header('Location: ../index.php?p=login');
    exit;
}

$cfg_dir  = __DIR__ . '/../config';
$cfg_file = $cfg_dir . '/contato.php';

$canais = [
    'email'     => ['label'=>'E-mail',       'icon'=>'✉️',  'placeholder'=>'contato@seujogo.com'],
    'discord'   => ['label'=>'Discord',      'icon'=>'🎮', 'placeholder'=>'https://discord.gg/seuserver'],
    'whatsapp'  => ['label'=>'WhatsApp',     'icon'=>'💬', 'placeholder'=>'5511999999999  (apenas números, com DDI)'],
    'twitter'   => ['label'=>'Twitter / X',  'icon'=>'𝕏',   'placeholder'=>'@seujogo  ou URL completa'],
    'instagram' => ['label'=>'Instagram',    'icon'=>'📷', 'placeholder'=>'@seujogo'],
    'facebook'  => ['label'=>'Facebook',     'icon'=>'📘', 'placeholder'=>'seujogo'],
    'telegram'  => ['label'=>'Telegram',     'icon'=>'✈️',  'placeholder'=>'@seujogo'],
    'youtube'   => ['label'=>'YouTube',      'icon'=>'▶️', 'placeholder'=>'@seucanal'],
    'website'   => ['label'=>'Site oficial', 'icon'=>'🌐', 'placeholder'=>'https://seusite.com'],
];

// Carrega valores atuais
$atual = [];
if (is_file($cfg_file)) {
    $loaded = @include $cfg_file;
    if (is_array($loaded)) $atual = $loaded;
}

$msg = ''; $msg_tipo = '';

// ── salvar ─────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'salvar_contatos') {
    $novos = [];
    foreach ($canais as $k => $_) {
        $v = trim((string)($_POST[$k] ?? ''));
        // sanitização leve: corta tags, ctrl chars
        $v = preg_replace('/[\x00-\x1F\x7F]/u', '', $v);
        $v = strip_tags($v);
        if (mb_strlen($v) > 200) $v = mb_substr($v, 0, 200);
        $novos[$k] = $v;
    }

    if (!is_dir($cfg_dir)) @mkdir($cfg_dir, 0755, true);

    // Gera arquivo PHP com cabeçalho explicativo
    $linhas = [
        '<?php',
        '/**',
        ' * config/contato.php — Canais oficiais de contato exibidos em ?p=contact',
        ' *',
        ' * Gerado automaticamente pelo painel ADM em '.date('Y-m-d H:i:s').'.',
        ' * Para editar de novo, acesse: adm/adm.php?modulo=contatos',
        ' */',
        '',
        'return [',
    ];
    foreach ($novos as $k => $v) {
        $linhas[] = "    " . var_export($k, true) . " => " . var_export($v, true) . ",";
    }
    $linhas[] = "];";

    $conteudo = implode("\n", $linhas) . "\n";

    if (@file_put_contents($cfg_file, $conteudo, LOCK_EX) === false) {
        $msg = 'Erro ao gravar config/contato.php — verifique permissões da pasta config/.';
        $msg_tipo = 'error';
    } else {
        $atual = $novos;
        $msg = '✅ Canais de contato atualizados com sucesso! Veja em ?p=contact';
        $msg_tipo = 'success';
    }
}

$page_title = 'Canais de Contato';
include 'adm_header.php';
?>

<div class="box_top">📬 Canais de Contato</div>
<div class="box_middle">

<?php if ($msg): ?>
    <div class="alert-<?php echo htmlspecialchars($msg_tipo); ?>"><?php echo htmlspecialchars($msg); ?></div>
    <div class="sep"></div>
<?php endif; ?>

<div style="background:#1a1200;border-left:3px solid #FFD700;padding:8px 12px;margin-bottom:8px;">
    <b style="color:#FFD700;">📘 Como funciona</b><br>
    <span class="sub2">
        Os campos abaixo aparecem como ícones clicáveis na página <code>?p=contact</code>.<br>
        Deixe em branco qualquer campo que você não quer exibir. Para WhatsApp use somente
        números (ex.: <code>5511999999999</code>). Para redes sociais, basta o handle (<code>@seujogo</code>)
        ou a URL completa.
    </span>
</div>

<form method="POST" autocomplete="off">
    <input type="hidden" name="action" value="salvar_contatos">
    <table class="adm-table" style="width:100%;">
        <tr>
            <th width="50">Ícone</th>
            <th width="160">Canal</th>
            <th>Valor</th>
        </tr>
    <?php foreach ($canais as $key => $def): ?>
        <tr>
            <td align="center" style="font-size:22px;"><?php echo $def['icon']; ?></td>
            <td><b style="color:#FFD700;"><?php echo htmlspecialchars($def['label']); ?></b><br>
                <span class="sub2"><code><?php echo htmlspecialchars($key); ?></code></span></td>
            <td>
                <input type="text" name="<?php echo htmlspecialchars($key); ?>"
                       value="<?php echo htmlspecialchars((string)($atual[$key] ?? '')); ?>"
                       placeholder="<?php echo htmlspecialchars($def['placeholder']); ?>"
                       maxlength="200" style="width:100%;">
            </td>
        </tr>
    <?php endforeach; ?>
    </table>

    <div class="sep"></div>
    <div align="right">
        <button type="submit" class="botao btn-success">💾 Salvar Contatos</button>
        &nbsp;
        <a href="../index.php?p=contact" target="_blank" class="botao">🔍 Ver página pública</a>
    </div>
</form>

<div class="sep"></div>
<h3>📥 Mensagens recebidas (últimas 30)</h3>
<?php
$mensagens = [];
try {
    $conexao->exec("CREATE TABLE IF NOT EXISTS contato (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(80) NOT NULL,
        email VARCHAR(120) NOT NULL,
        assunto VARCHAR(100) NOT NULL,
        usuario VARCHAR(60) NOT NULL DEFAULT '-',
        mensagem TEXT NOT NULL,
        ip VARCHAR(45) DEFAULT '',
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        lido TINYINT(1) NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $mensagens = $conexao->query("SELECT id, nome, email, assunto, usuario, mensagem, ip, criado_em, lido FROM contato ORDER BY criado_em DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $mensagens = []; }
?>
<?php if (empty($mensagens)): ?>
    <p class="sub2">Nenhuma mensagem recebida ainda.</p>
<?php else: ?>
<table class="adm-table" style="width:100%;">
    <tr>
        <th width="120">Quando</th>
        <th>De</th>
        <th>Assunto</th>
        <th>Mensagem</th>
    </tr>
    <?php foreach ($mensagens as $m): ?>
        <tr style="<?php echo $m['lido']?'opacity:0.6;':''; ?>">
            <td class="sub2"><?php echo htmlspecialchars($m['criado_em']); ?></td>
            <td>
                <b><?php echo htmlspecialchars($m['nome']); ?></b><br>
                <span class="sub2"><?php echo htmlspecialchars($m['email']); ?></span><br>
                <span class="sub2">user: <code><?php echo htmlspecialchars($m['usuario']); ?></code></span>
            </td>
            <td><?php echo htmlspecialchars($m['assunto']); ?></td>
            <td style="max-width:380px;word-wrap:break-word;"><?php echo nl2br(htmlspecialchars($m['mensagem'])); ?></td>
        </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

</div>
<div class="box_bottom"></div>
</body></html>
