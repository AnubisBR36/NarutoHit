<?php
// Iniciar sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// SEGURANÇA: se já existe uma sessão logada, redireciona para a home.
// Evita que a página de login mostre/processe credenciais quando o usuário
// já está autenticado (botão "voltar" do navegador, abas duplicadas, etc.).
if (!empty($_SESSION['logado'])) {
    // Headers anti-cache para que o navegador não sirva uma versão da
    // página de login do cache após o usuário já ter logado.
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Location: index.php?p=home');
    exit;
}

// Carregar configuração do reCAPTCHA
$recaptcha_config_file = __DIR__ . '/../config/recaptcha.php';
if (!file_exists($recaptcha_config_file)) {
    die('ERRO: Arquivo de configuração do reCAPTCHA não encontrado!<br><br>
         Por favor, copie o arquivo <b>config/recaptcha.php.example</b> para <b>config/recaptcha.php</b> 
         e configure suas chaves do Google reCAPTCHA.<br><br>
         Instruções: <a href="https://www.google.com/recaptcha/admin" target="_blank">https://www.google.com/recaptcha/admin</a>');
}
$recaptcha_config = require $recaptcha_config_file;
// Defaults retroativos para configs antigas sem 'version' / 'min_score'.
if (empty($recaptcha_config['version']))   $recaptcha_config['version']   = 'v2';
if (!isset($recaptcha_config['min_score'])) $recaptcha_config['min_score'] = 0.5;
$recaptcha_version = $recaptcha_config['version'];

// Carregar configuração de manutenção
$manutencao_config_file = __DIR__ . '/../config/manutencao.php';
if (file_exists($manutencao_config_file)) {
    $manutencao_config = require $manutencao_config_file;
} else {
    $manutencao_config = ['ativo' => false, 'mensagem_personalizada' => ''];
}

// Verificar se o reCAPTCHA está configurado
$recaptcha_enabled = !empty($recaptcha_config['secret_key']) && !empty($recaptcha_config['site_key']);

// Verificar se está em modo de manutenção (keys não configuradas OU manutenção ativada)
$em_manutencao = !$recaptcha_enabled || ($manutencao_config['ativo'] === true);

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Verificar se está tentando logar durante manutenção
    if ($em_manutencao) {
        // Buscar usuário para verificar se é admin
        $login_tentativa = $_POST['login_login'] ?? '';
        $senha_tentativa = $_POST['login_senha'] ?? '';
        
        if (!empty($login_tentativa) && !empty($senha_tentativa)) {
            try {
                require_once('conexao.php');
                $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE usuario = ?");
                $stmt->execute([$login_tentativa]);
                $usuario = $stmt->fetch();

                // Verifica senha (suporta hash bcrypt moderno e MD5 legado)
                if ($usuario && !senha_verificar((string)$senha_tentativa, (string)($usuario['senha'] ?? ''))) {
                    $usuario = false;
                }

                // Se não for admin (adm = 1 ou 2), redirecionar para manutenção
                if (!$usuario || !in_array($usuario['adm'], [1, 2])) {
                    require_once('manutencao.php');
                    exit;
                }
                
                // Se for admin, permitir login e entrar no servidor selecionado
                if ($usuario && in_array($usuario['adm'], [1, 2])) {
                    $srv_maint = isset($_POST['servidor_id']) ? (int)$_POST['servidor_id'] : (int)($usuario['servidor_id'] ?? 0);
                    $_SESSION['logado'] = $usuario['id'];
                    $_SESSION['user_id'] = $usuario['id'];
                    $_SESSION['username'] = $usuario['usuario'];
                    $_SESSION['adm'] = $usuario['adm'];
                    $_SESSION['servidor_id'] = $srv_maint;
                    echo '<script>window.location.href = "index.php?p=home";</script>';
                    exit;
                }
            } catch (Exception $e) {
                require_once('manutencao.php');
                exit;
            }
        } else {
            require_once('manutencao.php');
            exit;
        }
    }
    
    // STEP 1: VALIDAÇÃO DO GOOGLE RECAPTCHA (v2 caixa OU v3 invisível)
    $recaptcha_response = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : '';

    if (empty($recaptcha_response)) {
        echo '<script>window.location.href = "index.php?p=login&erro=5";</script>';
        exit;
    }

    // verificar_recaptcha() já entende v2 e v3 (incluindo pontuação/ação no v3).
    // No v3 esperamos a ação "login" emitida pelo grecaptcha.execute().
    $action_esperada = ($recaptcha_version === 'v3') ? 'login' : null;
    if (!verificar_recaptcha($recaptcha_response, $action_esperada)) {
        error_log('reCAPTCHA: validação falhou (versão=' . $recaptcha_version . ')');
        echo '<script>window.location.href = "index.php?p=login&erro=5";</script>';
        exit;
    }

    // STEP 2: Validação de login e senha
    if (empty($_POST['login_login'])) {
        echo '<script>window.location.href = "index.php?p=login&erro=2";</script>';
        exit;
    }

    if (empty($_POST['login_senha'])) {
        echo '<script>window.location.href = "index.php?p=login&erro=1";</script>';
        exit;
    }

    // STEP 3: Validar login no banco de dados
    $login = $_POST['login_login'];
    $senha = $_POST['login_senha'];
    $servidor_selecionado = isset($_POST['servidor_id']) ? (int)$_POST['servidor_id'] : 1;

    try {
        require_once('conexao.php');
        $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE usuario = ?");
        $stmt->execute([$login]);
        $usuario = $stmt->fetch();

        // Verifica senha (suporta hash bcrypt moderno e MD5 legado)
        if ($usuario && !senha_verificar((string)$senha, (string)($usuario['senha'] ?? ''))) {
            $usuario = false;
        }

        if ($usuario) {
            // Verificar servidor do jogador
            $usuario_servidor = isset($usuario['servidor_id']) && $usuario['servidor_id'] !== null ? (int)$usuario['servidor_id'] : null;
            if ($usuario_servidor === null) {
                // Usuário sem servidor atribuído: bloquear login até que o admin defina
                $_SESSION['erro_servidor'] = 'sem_servidor';
                echo '<script>window.location.href = "index.php?p=login&erro=7";</script>';
                exit;
            }
            $is_adm_gm = isset($usuario['adm']) && ($usuario['adm'] == 1 || $usuario['adm'] == 2);
            if (!$is_adm_gm && $usuario_servidor != $servidor_selecionado) {
                echo '<script>window.location.href = "index.php?p=login&erro=7";</script>';
                exit;
            }
            $_SESSION['logado'] = $usuario['id'];
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['username'] = $usuario['usuario'];
            $_SESSION['adm'] = $usuario['adm'] ?? 0;
            // Admin/GM entra no servidor que selecionou; jogador normal no servidor da conta
            $_SESSION['servidor_id'] = $is_adm_gm ? $servidor_selecionado : $usuario_servidor;
            echo '<script>window.location.href = "index.php?p=home";</script>';
            exit;
        } else {
            echo '<script>window.location.href = "index.php?p=login&erro=3";</script>';
            exit;
        }
    } catch (Exception $e) {
        echo '<script>window.location.href = "index.php?p=login&erro=3";</script>';
        exit;
    }
}

if (!defined('__INCLUDED__')) {
    define('__INCLUDED__', true);
}
?>

<div class="box_top">Login</div>
<div class="box_middle">Digite seu login e senha nos campos abaixo para acessar o jogo.<div class="sep"></div>

<?php
$show_ban_popup = false;
$ban_info_popup = null;
$show_servidor_errado_popup = false;

if (isset($_GET['erro'])) {
    $msg = '';
    switch ($_GET['erro']) {
        case 'ban':
            $show_ban_popup = true;
            if (isset($_SESSION['ban_info'])) {
                $ban_info_popup = $_SESSION['ban_info'];
                unset($_SESSION['ban_info']);
            }
            break;
        case 1:
            $msg = 'Digite uma <b>SENHA</b> válida.';
            break;
        case 2:
            $msg = 'Nenhum usuário encontrado com o login informado.';
            break;
        case 3:
            $msg = 'Senha digitada incorreta.';
            break;
        case 4:
            if (isset($_GET['date'])) {
                $data = $c->decode($_GET['date'], $chaveuniversal);
                $ex = explode('_', $data);
                $data = explode('-', $ex[0]);
            } else {
                $data = '0000-00-00_00:00:00';
            }
            $msg = 'Sua conta está em período de férias.<br />Não é possível realizar o login até o dia <b>' . $data[2] . '/' . $data[1] . '/' . $data[0] . ', às ' . $ex[1] . ' horas</b>.';
            break;
        case 5:
            $msg = '<b>Verificação anti-bot falhou!</b><br />Por favor, complete a verificação do Google reCAPTCHA.';
            break;
        case 6:
            $msg = '<b>Verificação reCAPTCHA falhou!</b><br />Por favor, complete a verificação corretamente.';
            break;
        case 7:
            $show_servidor_errado_popup = true;
            break;
        case 'recaptcha_admin':
            $msg = '<b style="color: #ffaa00;">⚠️ ADMIN: reCAPTCHA não configurado</b><br />Configure as chaves do reCAPTCHA no arquivo config/recaptcha.php';
            break;
    }
    if (!empty($msg)) {
        echo '<div class="aviso">' . $msg . '</div><div class="sep"></div>';
    }
}

if (isset($_GET['reason'])) echo '<div class="aviso">Você foi deslogado pois outro usuário acessou sua conta.</div><div class="sep"></div>';

// Verificar se o reCAPTCHA está habilitado
$recaptcha_enabled = !empty($recaptcha_config['secret_key']) && !empty($recaptcha_config['site_key']);
?>

<?php if ($recaptcha_enabled): ?>
  <?php if ($recaptcha_version === 'v3'): ?>
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars($recaptcha_config['site_key']); ?>"></script>
  <?php else: ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <?php endif; ?>
<?php endif; ?>

<fieldset>
    <legend>Dados de Acesso</legend>
    <form method="post" action="index.php?p=login" id="login" name="login" style="background:url(_img/login<?php echo rand(1, 2); ?>.jpg) no-repeat right;">
        <span class="destaque">Servidor:</span><br />
        <?php
        $srv_list_login = [];
        try {
            require_once('conexao.php');
            $srv_list_login = $conexao->query("SELECT s.id, s.nome, s.capacidade,
                (SELECT COUNT(*) FROM usuarios u WHERE u.servidor_id = s.id) AS total_players
                FROM servidores s WHERE s.ativo = 1 ORDER BY s.id ASC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $srv_list_login = [['id'=>1,'nome'=>'Servidor 01','capacidade'=>500,'total_players'=>0]];
        }
        $srv_selecionado_login = isset($_GET['srv']) ? (int)$_GET['srv'] : (int)($srv_list_login[0]['id'] ?? 1);
        // Build JS data
        $srv_js_data = [];
        foreach ($srv_list_login as $s) {
            $pct = $s['capacidade'] > 0 ? min(100, round(($s['total_players'] / $s['capacidade']) * 100)) : 100;
            $srv_js_data[] = ['id'=>(int)$s['id'],'pct'=>$pct,'cheio'=>($s['capacidade'] - $s['total_players']) <= 0];
        }
        ?>
        <select name="servidor_id" id="srv_select_login" onchange="atualizarBarraServidor()">
            <?php foreach ($srv_list_login as $srv): ?>
                <?php $cheio = ($srv['capacidade'] - $srv['total_players']) <= 0; ?>
                <option value="<?php echo $srv['id']; ?>"
                    <?php echo ($srv['id'] == $srv_selecionado_login) ? 'selected' : ''; ?>
                    <?php echo $cheio ? 'disabled' : ''; ?>>
                    <?php echo htmlspecialchars($srv['nome']); ?><?php echo $cheio ? ' (Cheio)' : ''; ?>
                </option>
            <?php endforeach; ?>
            <?php if (empty($srv_list_login)): ?>
                <option value="0" disabled>Nenhum servidor disponível</option>
            <?php endif; ?>
        </select>
        <div style="display:flex;align-items:center;gap:7px;margin:5px 0 2px 0;">
            <span style="font-size:10px;color:#aaa;">Lotação:</span>
            <div id="srv_bar_wrap" style="width:120px;height:8px;background:#444;border-radius:4px;overflow:hidden;">
                <div id="srv_bar_fill" style="height:8px;border-radius:4px;transition:width 0.3s,background 0.3s;"></div>
            </div>
            <span id="srv_bar_label" style="font-size:10px;color:#aaa;"></span>
        </div>
        <script>
        var _srvData = <?php echo json_encode($srv_js_data); ?>;
        function atualizarBarraServidor() {
            var sel = document.getElementById('srv_select_login');
            var id = parseInt(sel.value);
            var bar = document.getElementById('srv_bar_fill');
            var lbl = document.getElementById('srv_bar_label');
            for (var i = 0; i < _srvData.length; i++) {
                if (_srvData[i].id === id) {
                    var pct = _srvData[i].pct;
                    bar.style.width = pct + '%';
                    bar.style.background = pct >= 90 ? '#e53935' : pct >= 60 ? '#f9a825' : '#43a047';
                    lbl.textContent = pct + '%';
                    lbl.style.color = pct >= 90 ? '#e53935' : pct >= 60 ? '#f9a825' : '#43a047';
                    return;
                }
            }
        }
        window.addEventListener('load', atualizarBarraServidor);
        </script><br />
        <span class="sub2">Escolha o servidor em que deseja jogar.</span><br /><br />
        <span class="destaque">Login:</span><br />
        <input type="text" id="login_login" name="login_login" maxlength="15" onfocus="className='input'" onblur="className=''" /><br />
        <span class="sub2">Digite seu login de acesso (nome do usuário).</span><br /><br />
        <span class="destaque">Senha:</span><br />
        <input type="password" id="login_senha" name="login_senha" maxlength="15" onfocus="className='input'" onblur="className=''" /><br />
        <span class="sub2">Digite sua senha de acesso.</span><br /><br />
        
        <?php if ($recaptcha_enabled): ?>
          <?php if ($recaptcha_version === 'v3'): ?>
            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response-login" />
            <script>
            (function(){
              var f = document.getElementById('login');
              if(!f) return;
              f.addEventListener('submit', function(ev){
                if (f.dataset.captchaOk === '1') return; // já temos token, deixa enviar
                ev.preventDefault();
                if (typeof grecaptcha === 'undefined' || !grecaptcha.ready) {
                  alert('reCAPTCHA não carregou. Recarregue a página.'); return;
                }
                grecaptcha.ready(function(){
                  grecaptcha.execute('<?php echo htmlspecialchars($recaptcha_config['site_key']); ?>', {action:'login'})
                    .then(function(token){
                      document.getElementById('g-recaptcha-response-login').value = token;
                      f.dataset.captchaOk = '1';
                      f.submit();
                    });
                });
              });
            })();
            </script>
          <?php else: ?>
            <span class="destaque">Verificação Anti-Bot:</span><br />
            <div class="g-recaptcha" data-sitekey="<?php echo $recaptcha_config['site_key']; ?>" style="margin: 10px 0;"></div>
            <span class="sub2">Marque a caixa "Não sou um robô" para continuar.</span><br /><br />
          <?php endif; ?>
        <?php endif; ?>

        <button type="submit" id="subm" name="subm" class="botao">Acessar</button><br /><br />

        <a href="?p=terms">Registrar no <?php echo nome_servidor(); ?></a> | <a href="?p=recover">Nova Senha</a>
    </form>
</fieldset>
</div>
<div class="box_bottom"></div>

<?php if ($show_ban_popup): ?>
<?php
$ban_motivo = 'Não especificado';
$ban_tempo  = 'Indeterminado';
$ban_data_fmt = '';
if ($ban_info_popup) {
    $ban_motivo = htmlspecialchars($ban_info_popup['motivo']);
    $duracao = (int)$ban_info_popup['duracao'];
    $ban_data_raw = $ban_info_popup['data'];
    if ($ban_data_raw) {
        $ban_data_fmt = date('d/m/Y \à\s H:i', strtotime($ban_data_raw));
    }
    if ($duracao >= 3650) {
        $ban_tempo = 'Permanente';
    } elseif ($duracao > 0 && $ban_data_raw) {
        try {
            $data_fim = new DateTime($ban_data_raw);
            $data_fim->add(new DateInterval('P' . $duracao . 'D'));
            $agora = new DateTime();
            $diff = $data_fim->diff($agora);
            if ($diff->days >= 365) {
                $anos = floor($diff->days / 365);
                $ban_tempo = $anos . ' ano' . ($anos > 1 ? 's' : '');
            } elseif ($diff->days >= 30) {
                $meses = floor($diff->days / 30);
                $ban_tempo = $meses . ' mês' . ($meses > 1 ? 'es' : '');
            } elseif ($diff->days > 0) {
                $ban_tempo = $diff->days . ' dia' . ($diff->days > 1 ? 's' : '');
            } elseif ($diff->h > 0) {
                $ban_tempo = $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
            } else {
                $ban_tempo = 'Menos de 1 hora';
            }
        } catch (Exception $e) {
            $ban_tempo = $duracao . ' dia(s)';
        }
    }
}
?>

<div id="ban-overlay">
    <div id="ban-popup">
        <img src="_img/Ban/banned.png" id="ban-icon" alt="Banido" />
        <div id="ban-scroll">
            <h2 id="ban-title">CONTA BANIDA</h2>
            <div id="ban-divider"></div>
            <div class="ban-row">
                <span class="ban-label">Motivo:</span>
                <span class="ban-value"><?php echo $ban_motivo; ?></span>
            </div>
            <?php if ($ban_data_fmt): ?>
            <div class="ban-row">
                <span class="ban-label">Data do ban:</span>
                <span class="ban-value"><?php echo $ban_data_fmt; ?></span>
            </div>
            <?php endif; ?>
            <div class="ban-row">
                <span class="ban-label">Tempo restante:</span>
                <span class="ban-value ban-tempo"><?php echo $ban_tempo; ?></span>
            </div>
            <div id="ban-footer-text">Entre em contato com a administração caso acredite que isso é um engano.</div>
            <button id="ban-close-btn" onclick="document.getElementById('ban-overlay').style.display='none'">Fechar</button>
        </div>
    </div>
</div>

<style>
#ban-overlay {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.82);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}
#ban-popup {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    max-width: 420px;
    width: 95%;
    animation: ban-appear 0.4s ease;
}
@keyframes ban-appear {
    from { transform: scale(0.7); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
}
#ban-icon {
    width: 90px;
    height: 90px;
    position: relative;
    z-index: 2;
    margin-bottom: -20px;
    filter: drop-shadow(0 0 12px #ff0000cc);
}
#ban-scroll {
    background: url('../_img/Ban/menu-repete.png') repeat;
    border: 3px solid #8b3a00;
    border-radius: 8px;
    padding: 35px 30px 25px 30px;
    width: 100%;
    box-sizing: border-box;
    text-align: center;
    box-shadow: 0 0 30px rgba(0,0,0,0.8), inset 0 0 20px rgba(0,0,0,0.4);
}
#ban-title {
    color: #cc0000;
    font-size: 22px;
    font-weight: bold;
    margin: 0 0 10px 0;
    text-shadow:
        -1px -1px 0 #000, 1px -1px 0 #000,
        -1px  1px 0 #000, 1px  1px 0 #000,
        -2px  0   0 #000, 2px  0   0 #000,
         0   -2px 0 #000, 0    2px 0 #000,
         0 0 12px #ff0000;
    letter-spacing: 2px;
}
#ban-divider {
    height: 2px;
    background: linear-gradient(to right, transparent, #8b3a00, transparent);
    margin: 10px 0 15px 0;
}
.ban-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin: 8px 0;
    gap: 10px;
}
.ban-label {
    color: #ffffff;
    font-weight: bold;
    font-size: 13px;
    white-space: nowrap;
    text-shadow: -2px -2px 0 #000, 2px -2px 0 #000, -2px 2px 0 #000, 2px 2px 0 #000,
                 -2px 0 0 #000, 2px 0 0 #000, 0 -2px 0 #000, 0 2px 0 #000;
    min-width: 110px;
    text-align: left;
}
.ban-value {
    color: #ffffff;
    font-size: 13px;
    font-weight: bold;
    text-shadow: -2px -2px 0 #000, 2px -2px 0 #000, -2px 2px 0 #000, 2px 2px 0 #000,
                 -2px 0 0 #000, 2px 0 0 #000, 0 -2px 0 #000, 0 2px 0 #000;
    text-align: right;
    word-break: break-word;
}
.ban-tempo {
    color: #ff6666;
    font-weight: bold;
    text-shadow: -2px -2px 0 #000, 2px -2px 0 #000, -2px 2px 0 #000, 2px 2px 0 #000,
                 -2px 0 0 #000, 2px 0 0 #000, 0 -2px 0 #000, 0 2px 0 #000;
}
#ban-footer-text {
    color: #ffffff;
    font-size: 13px;
    font-weight: bold;
    margin-top: 14px;
    font-style: italic;
    text-shadow: -2px -2px 0 #000, 2px -2px 0 #000, -2px 2px 0 #000, 2px 2px 0 #000,
                 -2px 0 0 #000, 2px 0 0 #000, 0 -2px 0 #000, 0 2px 0 #000;
}
#ban-close-btn {
    margin-top: 18px;
    background: linear-gradient(to bottom, #8b0000, #5a0000);
    color: #fff;
    border: 2px solid #cc0000;
    padding: 8px 30px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    border-radius: 4px;
    text-shadow: 1px 1px 2px #000;
    box-shadow: 0 2px 6px rgba(0,0,0,0.5);
    transition: background 0.2s;
}
#ban-close-btn:hover {
    background: linear-gradient(to bottom, #b00000, #780000);
}
</style>
<?php endif; ?>

<?php
// Popup de termos de desbanimento
$show_unban_terms = isset($_GET['unban']) && $_GET['unban'] == '1' && isset($_SESSION['unban_info']);
// Popup de penalidade com contagem regressiva
$show_penalty = isset($_GET['penalidade']) && $_GET['penalidade'] == '1' && isset($_SESSION['ban_penalty_info']);
$penalty_ate_ts = 0;
if ($show_penalty) {
    try { $penalty_ate_ts = (new DateTime($_SESSION['ban_penalty_info']['ate']))->getTimestamp(); } catch (Exception $e) { $show_penalty = false; }
}
?>

<?php if ($show_unban_terms): ?>
<div id="unban-overlay">
    <div id="unban-popup">
        <div id="unban-scroll">
            <h2 id="unban-title">⚔ DESBANIMENTO ⚔</h2>
            <div id="unban-divider"></div>
            <div id="unban-body">
                <p class="unban-intro">Shinobi, sua punição chegou ao fim.<br>Antes de retornar às batalhas, relembre seu compromisso com as regras desta aldeia:</p>
                <ul class="unban-rules">
                    <li>Não utilize múltiplas contas, bots ou qualquer meio de trapaça</li>
                    <li>Respeite todos os jogadores e a equipe de administração</li>
                    <li>Siga as regras e regulamentos do <?php echo nome_servidor(); ?> em todos os momentos</li>
                    <li>Compreenda que uma nova infração pode resultar em banimento permanente</li>
                </ul>
                <p class="unban-chance"><em>Esta é sua segunda chance — use-a com sabedoria.</em></p>
            </div>
            <div id="unban-divider"></div>
            <form method="POST" action="index.php?p=login" id="unban-form">
                <label id="unban-checkbox-label">
                    <input type="checkbox" id="unban-check" onchange="document.getElementById('unban-accept-btn').disabled = !this.checked;" />
                    Li e aceito os termos, me comprometendo a seguir as regras do <?php echo nome_servidor(); ?>.
                </label>
                <div id="unban-buttons">
                    <button type="submit" name="ban_terms_action" value="accept" id="unban-accept-btn" disabled>✅ Confirmar e Entrar</button>
                    <button type="button" id="unban-deny-btn" onclick="document.getElementById('unban-deny-confirm').style.display='block'; this.style.display='none';">❌ Negar</button>
                </div>
                <div id="unban-deny-confirm" style="display:none;">
                    <p id="unban-deny-warn">⚠ Ao negar, você ficará bloqueado de logar por alguns minutos. Tem certeza?</p>
                    <button type="submit" name="ban_terms_action" value="deny" id="unban-deny-final">Sim, negar os termos</button>
                    <button type="button" onclick="document.getElementById('unban-deny-confirm').style.display='none'; document.getElementById('unban-deny-btn').style.display='inline-block';">Voltar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
#unban-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.85); z-index: 9999;
    display: flex; align-items: center; justify-content: center;
}
#unban-popup {
    max-width: 460px; width: 95%;
    animation: ban-appear 0.4s ease;
}
#unban-scroll {
    background: url('../_img/Ban/menu-repete.png') repeat;
    border: 3px solid #c8860a;
    border-radius: 8px;
    padding: 28px 30px 22px 30px;
    box-shadow: 0 0 30px rgba(0,0,0,0.8), inset 0 0 20px rgba(0,0,0,0.4);
}
#unban-title {
    color: #cc0000;
    font-size: 20px; font-weight: bold;
    margin: 0 0 10px 0; text-align: center;
    text-shadow: -2px -2px 0 #000, 2px -2px 0 #000, -2px 2px 0 #000, 2px 2px 0 #000,
                 -2px 0 0 #000, 2px 0 0 #000, 0 -2px 0 #000, 0 2px 0 #000;
    letter-spacing: 2px;
}
#unban-divider {
    height: 2px;
    background: linear-gradient(to right, transparent, #c8860a, transparent);
    margin: 10px 0 14px 0;
}
.unban-intro {
    color: #ffffff; font-size: 13px; font-weight: bold; text-align: center; margin: 0 0 12px 0;
    text-shadow: -2px -2px 0 #000, 2px -2px 0 #000, -2px 2px 0 #000, 2px 2px 0 #000,
                 -2px 0 0 #000, 2px 0 0 #000, 0 -2px 0 #000, 0 2px 0 #000;
}
.unban-rules {
    color: #ffffff; font-size: 12px; font-weight: bold; margin: 0 0 10px 10px; padding-left: 16px; line-height: 1.7;
    text-shadow: -2px -2px 0 #000, 2px -2px 0 #000, -2px 2px 0 #000, 2px 2px 0 #000,
                 -2px 0 0 #000, 2px 0 0 #000, 0 -2px 0 #000, 0 2px 0 #000;
}
.unban-chance {
    color: #ffffff; font-size: 12px; font-weight: bold; text-align: center; margin: 0 0 12px 0;
    text-shadow: -2px -2px 0 #000, 2px -2px 0 #000, -2px 2px 0 #000, 2px 2px 0 #000,
                 -2px 0 0 #000, 2px 0 0 #000, 0 -2px 0 #000, 0 2px 0 #000;
}
#unban-checkbox-label {
    display: flex; align-items: flex-start; gap: 8px;
    color: #ffffff; font-size: 12px; font-weight: bold; cursor: pointer; margin-bottom: 14px;
    text-shadow: -2px -2px 0 #000, 2px -2px 0 #000, -2px 2px 0 #000, 2px 2px 0 #000,
                 -2px 0 0 #000, 2px 0 0 #000, 0 -2px 0 #000, 0 2px 0 #000;
}
#unban-check { margin-top: 2px; cursor: pointer; }
#unban-buttons { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
#unban-accept-btn {
    background: linear-gradient(to bottom, #2e7d32, #1b5e20);
    color: #fff; border: 2px solid #4caf50;
    padding: 8px 22px; font-size: 13px; font-weight: bold;
    cursor: pointer; border-radius: 4px;
    text-shadow: 1px 1px 2px #000;
    box-shadow: 0 2px 6px rgba(0,0,0,0.5);
}
#unban-accept-btn:disabled { opacity: 0.4; cursor: not-allowed; }
#unban-accept-btn:not(:disabled):hover { background: linear-gradient(to bottom, #388e3c, #256029); }
#unban-deny-btn {
    background: linear-gradient(to bottom, #8b0000, #5a0000);
    color: #fff; border: 2px solid #cc0000;
    padding: 8px 22px; font-size: 13px; font-weight: bold;
    cursor: pointer; border-radius: 4px;
    text-shadow: 1px 1px 2px #000;
    box-shadow: 0 2px 6px rgba(0,0,0,0.5);
}
#unban-deny-btn:hover { background: linear-gradient(to bottom, #b00000, #780000); }
#unban-deny-confirm { margin-top: 12px; text-align: center; }
#unban-deny-warn {
    color: #ffffff; font-size: 12px; font-weight: bold; margin-bottom: 10px;
    text-shadow: -2px -2px 0 #000, 2px -2px 0 #000, -2px 2px 0 #000, 2px 2px 0 #000,
                 -2px 0 0 #000, 2px 0 0 #000, 0 -2px 0 #000, 0 2px 0 #000;
}
#unban-deny-final {
    background: #8b0000; color: #fff; border: 1px solid #cc0000;
    padding: 6px 14px; font-size: 12px; cursor: pointer; border-radius: 4px; margin-right: 8px;
}
#unban-deny-confirm button:last-child {
    background: #444; color: #fff; border: 1px solid #666;
    padding: 6px 14px; font-size: 12px; cursor: pointer; border-radius: 4px;
}
</style>
<?php endif; ?>

<?php if ($show_penalty): ?>
<div id="penalty-overlay">
    <div id="penalty-popup">
        <img src="_img/Ban/banned.png" id="penalty-icon" alt="Bloqueado" />
        <div id="penalty-scroll">
            <h2 id="penalty-title">ACESSO BLOQUEADO</h2>
            <div id="penalty-divider"></div>
            <p id="penalty-msg">Você negou os termos de desbanimento.<br>Aguarde para tentar novamente:</p>
            <div id="penalty-countdown">--:--</div>
            <p id="penalty-sub">Após o tempo, você poderá logar normalmente.</p>
        </div>
    </div>
</div>

<style>
#penalty-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.85); z-index: 9999;
    display: flex; align-items: center; justify-content: center;
}
#penalty-popup {
    display: flex; flex-direction: column; align-items: center;
    max-width: 380px; width: 95%;
    animation: ban-appear 0.4s ease;
}
#penalty-icon {
    width: 80px; height: 80px; margin-bottom: -18px; z-index: 2;
    filter: drop-shadow(0 0 10px #ff0000cc);
}
#penalty-scroll {
    background: url('../_img/Ban/menu-repete.png') repeat;
    border: 3px solid #8b3a00; border-radius: 8px;
    padding: 32px 28px 22px 28px; text-align: center;
    box-shadow: 0 0 30px rgba(0,0,0,0.8), inset 0 0 20px rgba(0,0,0,0.4);
    width: 100%; box-sizing: border-box;
}
#penalty-title {
    color: #cc0000; font-size: 20px; font-weight: bold;
    margin: 0 0 10px 0; letter-spacing: 2px;
    text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000, 0 0 10px #ff0000;
}
#penalty-divider {
    height: 2px;
    background: linear-gradient(to right, transparent, #8b3a00, transparent);
    margin: 8px 0 14px 0;
}
#penalty-msg {
    color: #ffffff; font-size: 13px; font-weight: bold; margin: 0 0 14px 0;
    text-shadow: -2px -2px 0 #000, 2px -2px 0 #000, -2px 2px 0 #000, 2px 2px 0 #000,
                 -2px 0 0 #000, 2px 0 0 #000, 0 -2px 0 #000, 0 2px 0 #000;
}
#penalty-countdown {
    font-size: 42px; font-weight: bold; color: #ff4444;
    text-shadow: -2px -2px 0 #000, 2px -2px 0 #000, -2px 2px 0 #000, 2px 2px 0 #000,
                 -2px 0 0 #000, 2px 0 0 #000, 0 -2px 0 #000, 0 2px 0 #000, 0 0 14px #ff0000;
    margin: 4px 0 12px 0; letter-spacing: 4px;
    font-family: monospace;
}
#penalty-sub {
    color: #ffffff; font-size: 12px; font-weight: bold; font-style: italic;
    text-shadow: -2px -2px 0 #000, 2px -2px 0 #000, -2px 2px 0 #000, 2px 2px 0 #000,
                 -2px 0 0 #000, 2px 0 0 #000, 0 -2px 0 #000, 0 2px 0 #000;
}
</style>

<script>
(function() {
    var targetTs = <?php echo $penalty_ate_ts; ?> * 1000;
    var display = document.getElementById('penalty-countdown');
    var overlay = document.getElementById('penalty-overlay');

    function update() {
        var now = Date.now();
        var diff = targetTs - now;
        if (diff <= 0) {
            overlay.style.display = 'none';
            window.location.href = 'index.php?p=login';
            return;
        }
        var m = Math.floor(diff / 60000);
        var s = Math.floor((diff % 60000) / 1000);
        display.textContent = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
        setTimeout(update, 500);
    }
    update();
})();
</script>
<?php endif; ?>

<?php if ($show_servidor_errado_popup): ?>
<div id="srv-erro-overlay" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);z-index:9999;display:flex;align-items:center;justify-content:center;">
    <div style="position:relative;width:420px;max-width:95vw;text-align:center;">
        <img src="_img/Ban/menu-repete.png" alt="" style="width:100%;display:block;" onerror="this.style.display='none'">
        <div style="position:absolute;top:0;left:0;width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:30px 40px;box-sizing:border-box;">
            <img src="_img/Ban/banned.png" alt="Erro" style="width:60px;margin-bottom:12px;" onerror="this.style.display='none'">
            <p style="color:#FF4444;font-size:20px;font-weight:bold;margin:0 0 10px 0;
                text-shadow:-1px -1px 0 #000,1px -1px 0 #000,-1px 1px 0 #000,1px 1px 0 #000;">
                Acesso Negado
            </p>
            <p style="color:#ffffff;font-size:14px;font-weight:bold;margin:0 0 20px 0;
                text-shadow:-1px -1px 0 #000,1px -1px 0 #000,-1px 1px 0 #000,1px 1px 0 #000;">
                Essa conta não pertence a este servidor.<br>
                Selecione o servidor correto para entrar.
            </p>
            <button onclick="document.getElementById('srv-erro-overlay').style.display='none';"
                style="background:#cc0000;color:#fff;font-weight:bold;border:2px solid #ff4444;
                    padding:8px 28px;border-radius:5px;cursor:pointer;font-size:14px;">
                Fechar
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
<?php if (!$show_ban_popup && !$show_unban_terms && !$show_penalty && !$show_servidor_errado_popup): ?>
document.forms[0].login_login.focus();
<?php endif; ?>
</script>
