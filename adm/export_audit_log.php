<?php
require_once('../_inc/conexao.php');
if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['logado']) && !isset($_SESSION['userid']) && !isset($_SESSION['uid'])) {
    http_response_code(403); exit('Acesso negado');
}
$uid = $_SESSION['logado'] ?? $_SESSION['userid'] ?? $_SESSION['uid'];
try {
    $st = $conexao->prepare("SELECT adm FROM usuarios WHERE id = ?");
    $st->execute([$uid]);
    $u = $st->fetch(PDO::FETCH_ASSOC);
} catch(Exception $e) { http_response_code(500); exit('Erro DB'); }
if (!$u || $u['adm'] != 1) { http_response_code(403); exit('Acesso negado'); }

// Filtros (mesmos do viewer)
$al_autor  = trim($_GET['al_autor'] ?? '');
$al_acao   = trim($_GET['al_acao']  ?? '');
$al_alvo   = trim($_GET['al_alvo']  ?? '');
$al_de     = trim($_GET['al_de']    ?? '');
$al_ate    = trim($_GET['al_ate']   ?? '');
$al_de_val  = ($al_de  !== '' && strtotime($al_de))  ? $al_de  : '';
$al_ate_val = ($al_ate !== '' && strtotime($al_ate)) ? $al_ate : '';

$where  = [];
$params = [];
if ($al_autor  !== '') { $where[] = 'autor_nome LIKE ?'; $params[] = "%$al_autor%"; }
if ($al_acao   !== '') { $where[] = 'acao LIKE ?';       $params[] = "%$al_acao%"; }
if ($al_alvo   !== '') { $where[] = 'alvo_nome LIKE ?';  $params[] = "%$al_alvo%"; }
if ($al_de_val  !== '') { $where[] = 'data_hora >= ?';   $params[] = $al_de_val . ' 00:00:00'; }
if ($al_ate_val !== '') { $where[] = 'data_hora <= ?';   $params[] = $al_ate_val . ' 23:59:59'; }
$sql_where = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

try {
    $st2 = $conexao->prepare("SELECT id, data_hora, autor_nome, acao, alvo_id, alvo_nome, detalhes FROM admin_logs $sql_where ORDER BY id DESC");
    $st2->execute($params);
    $rows = $st2->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) { http_response_code(500); exit('Erro ao buscar logs'); }

$filename = 'audit_log_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

// BOM para Excel reconhecer UTF-8
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
fputcsv($out, ['ID', 'Data/Hora', 'Admin/GM', 'Ação', 'Alvo ID', 'Alvo Nome', 'Detalhes'], ';');
foreach ($rows as $row) {
    fputcsv($out, [
        $row['id'],
        $row['data_hora'],
        $row['autor_nome'],
        $row['acao'],
        $row['alvo_id'] ?? '',
        $row['alvo_nome'] ?? '',
        $row['detalhes'] ?? '',
    ], ';');
}
fclose($out);
exit;
