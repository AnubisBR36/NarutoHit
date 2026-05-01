<?php
require_once('trava.php');
require_once('conexao.php');

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>alert('Método inválido!'); self.location='?p=reports';</script>";
    exit;
}

if(!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo "<script>alert('Token de segurança inválido!'); self.location='?p=reports';</script>";
    exit;
}

if(!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo "<script>alert('Relatório inválido!'); self.location='?p=reports';</script>";
    exit;
}

$report_id = (int)$_POST['id'];

try {
    $stmt = $conexao->prepare("SELECT id, usuarioid, inimigoid FROM relatorios WHERE id=?");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$report) {
        echo "<script>alert('Relatório não encontrado!'); self.location='?p=reports';</script>";
        exit;
    }
    
    if($report['usuarioid'] != $db['id'] && $report['inimigoid'] != $db['id']) {
        echo "<script>alert('Você não tem permissão para apagar este relatório!'); self.location='?p=reports';</script>";
        exit;
    }
    
    $stmt_del = $conexao->prepare("DELETE FROM relatorios WHERE id=?");
    $stmt_del->execute([$report_id]);
    
    if(file_exists('reports/r'.$report_id.'.txt')) {
        unlink('reports/r'.$report_id.'.txt');
    }
    
    echo "<script>alert('Relatório apagado com sucesso!'); self.location='?p=reports';</script>";
    
} catch(PDOException $e) {
    echo "<script>alert('Erro ao apagar relatório: " . addslashes($e->getMessage()) . "'); self.location='?p=reports';</script>";
}
?>
