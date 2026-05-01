
<?php
require_once('conexao.php');
try {
    $stmt = $conexao->prepare("UPDATE usuarios SET timestamp=0 WHERE id=?");
    $stmt->execute([$_SESSION['logado']]);
} catch (PDOException $e) {
    // Handle error silently for logout
}
// Destruir toda a sessão para evitar que dados de admin vazem para outras contas
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
setcookie('logado', '', time() - 3600, '/');
setcookie('session_id', '', time() - 3600, '/');
if(isset($_GET['reason'])) $reason='&reason='.$_GET['reason']; else $reason='';
echo "<script>self.location='?p=login".$reason."'</script>";
?>
