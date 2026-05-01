<?php
class ForumSecurityHelper {
    public static function sanitize($string) {
        // Remove todas as tags HTML para prevenir XSS
        $string = strip_tags($string);
        // Converte caracteres especiais para entities HTML
        $string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
        // Remove múltiplos espaços em branco
        $string = preg_replace('/\s+/', ' ', $string);
        // Trim
        return trim($string);
    }
    
    public static function sanitizeOutput($string) {
        // Para output, apenas converte caracteres especiais
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['logado']) && !empty($_SESSION['logado']);
    }
    
    public static function getUserId() {
        return $_SESSION['logado'] ?? 0;
    }
    
    public static function isAdmin() {
        if (!self::isLoggedIn()) return false;
        
        require_once '_inc/conexao.php';
        global $conexao;
        if (!$conexao) return false;
        
        $stmt = $conexao->prepare("SELECT adm FROM usuarios WHERE id = ?");
        $stmt->execute([self::getUserId()]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($user && ($user['adm'] == 'sim' || $user['adm'] == 1 || $user['adm'] == 2));
    }
    
    public static function getUserVila() {
        if (!self::isLoggedIn()) return 0;
        
        require_once '_inc/conexao.php';
        global $conexao;
        if (!$conexao) return 0;
        
        $stmt = $conexao->prepare("SELECT vila, renegado FROM usuarios WHERE id = ?");
        $stmt->execute([self::getUserId()]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) return 0;
        
        // Se for renegado (Akatsuki), retorna 999
        if ($user['renegado'] == 'sim') return 999;
        
        return $user['vila'];
    }
    
    public static function getUserData() {
        if (!self::isLoggedIn()) return null;
        
        require_once '_inc/conexao.php';
        global $conexao;
        if (!$conexao) return null;
        
        $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([self::getUserId()]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public static function getUserAvatar($user_data) {
        if (!$user_data) return '_img/personagens/no_avatar.jpg';
        
        $personagem = $user_data['personagem'] ?? '';
        $avatar_numero = $user_data['avatar'] ?? 0;
        
        if (empty($personagem)) {
            return '_img/personagens/no_avatar.jpg';
        }
        
        $avatar_path = "_img/forum/Personagens/{$personagem}/{$avatar_numero}.jpg";
        if (file_exists($avatar_path)) {
            return $avatar_path;
        }
        
        return '_img/personagens/no_avatar.jpg';
    }
    
    public static function getVilaInfo($vila_id, $renegado = 'nao') {
        if ($renegado === 'sim') {
            return ['nome' => 'Akatsuki', 'imagem' => '_img/forum/akatsuki.png'];
        }
        
        $vilas = [
            0 => ['nome' => 'Vila Neutra', 'imagem' => '_img/forum/ferro.png'],
            1 => ['nome' => 'Vila da Folha', 'imagem' => '_img/forum/konoha.png'],
            2 => ['nome' => 'Vila da Areia', 'imagem' => '_img/forum/areia.png'],
            3 => ['nome' => 'Vila do Som', 'imagem' => '_img/forum/som.png'],
            4 => ['nome' => 'Vila da Chuva', 'imagem' => '_img/forum/chuva.png'],
            5 => ['nome' => 'Vila da Nuvem', 'imagem' => '_img/forum/nuvem.png'],
            6 => ['nome' => 'Vila da Névoa', 'imagem' => '_img/forum/nevoa.png'],
            7 => ['nome' => 'Vila Oculta', 'imagem' => '_img/forum/ferro.png'],
            8 => ['nome' => 'Vila da Pedra', 'imagem' => '_img/forum/rocha.png'],
        ];
        
        return $vilas[$vila_id] ?? ['nome' => 'Desconhecida', 'imagem' => '_img/forum/ferro.png'];
    }
}
