<?php
if (!defined('INDEX')) die('Acesso negado');

define('ADMIN_LEVELS', [1, 2]);

function recaptcha_configurado() {
    $config = @include(__DIR__ . '/../config/recaptcha.php');
    
    if (!$config) return false;
    
    $site_key = $config['site_key'] ?? '';
    $secret_key = $config['secret_key'] ?? '';
    
    return !empty($site_key) && 
           !empty($secret_key) && 
           $site_key !== 'SUA_CHAVE_DO_SITE_AQUI' && 
           $secret_key !== 'SUA_CHAVE_SECRETA_AQUI';
}

function obter_config_recaptcha() {
    $config = @include(__DIR__ . '/../config/recaptcha.php');
    if (!$config) {
        return ['site_key' => '', 'secret_key' => '', 'version' => 'v2', 'min_score' => 0.5];
    }
    // Defaults retroativos: arquivos antigos não têm 'version' nem 'min_score'.
    if (empty($config['version'])) $config['version'] = 'v2';
    if (!isset($config['min_score'])) $config['min_score'] = 0.5;
    return $config;
}

function esta_em_manutencao() {
    $config = @include(__DIR__ . '/../config/manutencao.php');
    
    if (!$config) return false;
    
    return isset($config['ativo']) && $config['ativo'] === true;
}

function obter_mensagem_manutencao() {
    $config = @include(__DIR__ . '/../config/manutencao.php');
    
    if (!$config || empty($config['mensagem_personalizada'])) {
        return 'Estamos em Manutenção';
    }
    
    return $config['mensagem_personalizada'];
}

function salvar_config_manutencao($ativo, $mensagem) {
    $config = [
        'ativo' => (bool)$ativo,
        'mensagem_personalizada' => trim($mensagem)
    ];
    
    $conteudo = "<?php\n";
    $conteudo .= "/**\n";
    $conteudo .= " * Configuração do Sistema de Manutenção\n";
    $conteudo .= " * \n";
    $conteudo .= " * Este arquivo armazena as configurações do modo de manutenção.\n";
    $conteudo .= " * Administradores podem editar através do painel admin.\n";
    $conteudo .= " */\n\n";
    $conteudo .= "return [\n";
    $conteudo .= "    'ativo' => " . ($config['ativo'] ? 'true' : 'false') . ",\n";
    $conteudo .= "    'mensagem_personalizada' => '" . addslashes($config['mensagem_personalizada']) . "'\n";
    $conteudo .= "];\n";
    
    return file_put_contents(__DIR__ . '/../config/manutencao.php', $conteudo) !== false;
}

function usuario_e_admin($conexao, $usuario_id) {
    if (!$usuario_id) return false;
    
    $stmt = $conexao->prepare("SELECT adm FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $user && isset($user['adm']) && in_array($user['adm'], ADMIN_LEVELS);
}

function verificar_admin_por_dados($conexao, $usuario, $senha_hash) {
    $stmt = $conexao->prepare("SELECT id, adm FROM usuarios WHERE usuario = ? AND senha = ?");
    $stmt->execute([$usuario, $senha_hash]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $user && in_array($user['adm'], ADMIN_LEVELS) ? $user : false;
}

function salvar_config_recaptcha($version, $site_key, $secret_key, $min_score) {
    $config = [
        'version'    => in_array($version, ['v2','v3']) ? $version : 'v2',
        'site_key'   => trim($site_key),
        'secret_key' => trim($secret_key),
        'min_score'  => max(0.1, min(1.0, (float)$min_score)),
    ];

    $conteudo  = "<?php\n";
    $conteudo .= "/**\n * Configuração do reCAPTCHA\n */\n\n";
    $conteudo .= "return [\n";
    $conteudo .= "    'version'    => '" . addslashes($config['version'])    . "',\n";
    $conteudo .= "    'site_key'   => '" . addslashes($config['site_key'])   . "',\n";
    $conteudo .= "    'secret_key' => '" . addslashes($config['secret_key']) . "',\n";
    $conteudo .= "    'min_score'  => "  . $config['min_score']              . ",\n";
    $conteudo .= "];\n";

    return file_put_contents(__DIR__ . '/../config/recaptcha.php', $conteudo) !== false;
}

function verificar_recaptcha($response, $action_esperada = null) {
    if (empty($response)) return false;

    $config = obter_config_recaptcha();
    $secret_key = $config['secret_key'] ?? '';
    $version    = $config['version']    ?? 'v2';
    $min_score  = (float)($config['min_score'] ?? 0.5);

    if (empty($secret_key) || $secret_key === 'SUA_CHAVE_SECRETA_AQUI') {
        return false;
    }

    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret'   => $secret_key,
        'response' => $response,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ];

    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    if ($result === false) return false;

    $json = json_decode($result, true);
    if (!isset($json['success']) || $json['success'] !== true) return false;

    // No v3, validar pontuação e (opcionalmente) ação.
    if ($version === 'v3') {
        $score = isset($json['score']) ? (float)$json['score'] : 0.0;
        if ($score < $min_score) return false;
        if ($action_esperada !== null && isset($json['action'])
            && $json['action'] !== $action_esperada) {
            return false;
        }
    }
    return true;
}
