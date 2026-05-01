<?php
/**
 * ?p=contact — Página de contato pública.
 *
 * - Carrega `config/contato.php` (gerenciada pelo ADM em
 *   `adm/adm.php?modulo=contatos`) e renderiza ícones para cada canal
 *   habilitado (e-mail, Discord, WhatsApp, Twitter/X, Instagram, Facebook,
 *   Telegram, Site).
 * - Mantém o formulário de mensagens; agora grava em `contato` via PDO,
 *   bloqueando flood (1 mensagem por minuto / IP).
 *
 * Substitui o arquivo legado que usava `mysql_query()` e tinha um
 * `break;` fora de loop/switch (Fatal error em PHP 8.2).
 */

if (!isset($conexao)) require_once(__DIR__ . '/conexao.php');

// ── garantir tabela ────────────────────────────────────────────────────────
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
        lido TINYINT(1) NOT NULL DEFAULT 0,
        KEY idx_contato_lido (lido),
        KEY idx_contato_data (criado_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Throwable $e) {}

// ── carregar contatos públicos ─────────────────────────────────────────────
$contatos_cfg_file = __DIR__ . '/../config/contato.php';
$contatos = [];
if (is_file($contatos_cfg_file)) {
    $loaded = @include $contatos_cfg_file;
    if (is_array($loaded)) $contatos = $loaded;
}

// Definição dos canais suportados (ordem, rótulos, ícones, cor, gerador de URL)
$canais_def = [
    'email'     => ['label'=>'E-mail',       'icon'=>'✉️',  'cor'=>'#FFD700', 'mk'=>fn($v)=>'mailto:'.$v],
    'discord'   => ['label'=>'Discord',      'icon'=>'🎮', 'cor'=>'#5865F2', 'mk'=>fn($v)=>$v],
    'whatsapp'  => ['label'=>'WhatsApp',     'icon'=>'💬', 'cor'=>'#25D366', 'mk'=>fn($v)=>'https://wa.me/'.preg_replace('/[^\d]/','',$v)],
    'twitter'   => ['label'=>'Twitter / X',  'icon'=>'𝕏',   'cor'=>'#1DA1F2', 'mk'=>fn($v)=>(str_starts_with($v,'http')?$v:'https://twitter.com/'.ltrim($v,'@'))],
    'instagram' => ['label'=>'Instagram',    'icon'=>'📷', 'cor'=>'#E4405F', 'mk'=>fn($v)=>(str_starts_with($v,'http')?$v:'https://instagram.com/'.ltrim($v,'@'))],
    'facebook'  => ['label'=>'Facebook',     'icon'=>'📘', 'cor'=>'#1877F2', 'mk'=>fn($v)=>(str_starts_with($v,'http')?$v:'https://facebook.com/'.ltrim($v,'@'))],
    'telegram'  => ['label'=>'Telegram',     'icon'=>'✈️',  'cor'=>'#0088CC', 'mk'=>fn($v)=>(str_starts_with($v,'http')?$v:'https://t.me/'.ltrim($v,'@'))],
    'youtube'   => ['label'=>'YouTube',      'icon'=>'▶️', 'cor'=>'#FF0000', 'mk'=>fn($v)=>(str_starts_with($v,'http')?$v:'https://youtube.com/'.ltrim($v,'@'))],
    'website'   => ['label'=>'Site',         'icon'=>'🌐', 'cor'=>'#9b87f5', 'mk'=>fn($v)=>(str_starts_with($v,'http')?$v:'https://'.$v)],
];

// ── processar formulário ──────────────────────────────────────────────────
$msg_form = ''; $msg_tipo_form = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['con_nome'])) {
    $nome     = trim((string)($_POST['con_nome'] ?? ''));
    $email    = trim((string)($_POST['con_email'] ?? ''));
    $assunto  = trim((string)($_POST['con_assunto'] ?? ''));
    $mensagem = trim((string)($_POST['con_msg'] ?? ''));
    $user     = isset($db['usuario']) ? $db['usuario'] : '-';
    $ip       = $_SERVER['REMOTE_ADDR'] ?? '';

    if ($nome === '' || mb_strlen($nome) > 80) {
        $msg_form = 'Informe seu nome (até 80 caracteres).'; $msg_tipo_form = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg_form = 'E-mail inválido.'; $msg_tipo_form = 'error';
    } elseif ($assunto === '' || mb_strlen($assunto) > 100) {
        $msg_form = 'Informe um assunto.'; $msg_tipo_form = 'error';
    } elseif ($mensagem === '' || mb_strlen($mensagem) < 10) {
        $msg_form = 'Mensagem muito curta (mínimo 10 caracteres).'; $msg_tipo_form = 'error';
    } else {
        // Rate-limit: 1 mensagem por minuto por IP
        $pode_enviar = true;
        try {
            $rl = $conexao->prepare("SELECT COUNT(*) FROM contato WHERE ip = ? AND criado_em >= (NOW() - INTERVAL 60 SECOND)");
            $rl->execute([$ip]);
            $pode_enviar = ((int)$rl->fetchColumn() === 0);
        } catch (Throwable $e) { /* segue */ }

        if (!$pode_enviar) {
            $msg_form = 'Aguarde um pouco antes de enviar outra mensagem.'; $msg_tipo_form = 'error';
        } else {
            try {
                $stmt = $conexao->prepare("INSERT INTO contato (nome, email, assunto, usuario, mensagem, ip) VALUES (?,?,?,?,?,?)");
                $stmt->execute([$nome, $email, $assunto, $user, $mensagem, $ip]);
                $msg_form = '✅ Mensagem enviada com sucesso! Retornaremos em breve.'; $msg_tipo_form = 'success';
                // limpa POST para o form ficar vazio
                $_POST = [];
            } catch (Throwable $e) {
                $msg_form = 'Erro ao enviar mensagem. Tente novamente em instantes.'; $msg_tipo_form = 'error';
            }
        }
    }
}
?>
<div class="box_top">📬 Contato</div>
<div class="box_middle">

<?php if ($msg_form): ?>
    <div class="aviso" style="background:<?php echo $msg_tipo_form==='success'?'#0a3a0a':'#3a0a0a'; ?>;border:1px solid <?php echo $msg_tipo_form==='success'?'#5ecf6e':'#e74c3c'; ?>;padding:8px;margin-bottom:8px;">
        <?php echo htmlspecialchars($msg_form); ?>
    </div>
<?php endif; ?>

<?php
// Conta quantos canais ativos para decidir se mostra ou não a seção
$canais_ativos = [];
foreach ($canais_def as $key => $def) {
    if (!empty($contatos[$key])) {
        $canais_ativos[$key] = $def;
    }
}
?>
<?php if (!empty($canais_ativos)): ?>
    <div style="background:#1a1200;border-left:3px solid #FFD700;padding:10px 12px;margin-bottom:10px;">
        <b style="color:#FFD700;font-size:13px;">📡 Nossos canais oficiais</b>
        <div class="sep"></div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            <?php foreach ($canais_ativos as $key => $def):
                $valor = (string)$contatos[$key];
                $url = ($def['mk'])($valor);
            ?>
                <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener noreferrer"
                   style="display:inline-flex;align-items:center;gap:6px;padding:6px 10px;background:#1a1a1a;border:1px solid <?php echo $def['cor']; ?>;border-radius:4px;text-decoration:none;color:#fff;font-size:12px;">
                    <span style="font-size:18px;line-height:1;"><?php echo $def['icon']; ?></span>
                    <span style="display:flex;flex-direction:column;line-height:1.1;">
                        <b style="color:<?php echo $def['cor']; ?>;"><?php echo htmlspecialchars($def['label']); ?></b>
                        <span class="sub2"><?php echo htmlspecialchars($valor); ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <div class="sub2" style="margin-bottom:10px;">
        <i>O administrador ainda não cadastrou canais oficiais de contato.
        Use o formulário abaixo para falar com a equipe.</i>
    </div>
<?php endif; ?>

<form method="post" action="?p=contact" autocomplete="off">
    <fieldset><legend>Enviar mensagem</legend>
    <table width="100%" cellpadding="3" cellspacing="0">
        <tr>
            <td width="120"><b>Seu nome:</b></td>
            <td><input type="text" name="con_nome" maxlength="80" value="<?php echo htmlspecialchars($_POST['con_nome'] ?? ($db['usuario'] ?? '')); ?>" required style="width:100%;" /></td>
        </tr>
        <tr>
            <td><b>Seu e-mail:</b></td>
            <td><input type="email" name="con_email" maxlength="120" value="<?php echo htmlspecialchars($_POST['con_email'] ?? ($db['email'] ?? '')); ?>" required style="width:100%;" /></td>
        </tr>
        <tr>
            <td><b>Assunto:</b></td>
            <td>
                <select name="con_assunto" style="width:100%;">
                    <?php $assuntos = ['Reportar Bug','Reportar Jogador','Sugestão','Dúvida','Doação','Outro'];
                    $sel = $_POST['con_assunto'] ?? '';
                    foreach ($assuntos as $a): ?>
                        <option value="<?php echo htmlspecialchars($a); ?>" <?php if ($sel === $a) echo 'selected'; ?>><?php echo htmlspecialchars($a); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td valign="top"><b>Mensagem:</b><br><span class="sub2" id="cnt">0</span>/2000</td>
            <td><textarea name="con_msg" id="con_msg" rows="6" maxlength="2000" oninput="document.getElementById('cnt').innerText=this.value.length;" required style="width:100%;resize:vertical;"><?php echo htmlspecialchars($_POST['con_msg'] ?? ''); ?></textarea></td>
        </tr>
        <tr>
            <td colspan="2" align="right"><button type="submit" class="botao btn-success">📨 Enviar Mensagem</button></td>
        </tr>
    </table>
    </fieldset>
</form>

</div>
<div class="box_bottom"></div>
