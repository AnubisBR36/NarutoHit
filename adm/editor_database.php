<?php
require_once('../_inc/conexao.php');
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

if(!$usuario_logado || $usuario_logado['adm'] != 1) {
    header('Location: adm.php'); exit;
}

$resultado = '';
$erro = '';

if(isset($_POST['sql_query']) && !empty(trim($_POST['sql_query']))) {
    $sql = trim($_POST['sql_query']);
    try {
        if(stripos($sql, 'SELECT') === 0) {
            $stmt = $conexao->prepare($sql);
            $stmt->execute();
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if($resultados) {
                $resultado = 'success';
                $resultado_data = $resultados;
            } else {
                $resultado = 'empty';
            }
        } else {
            $stmt = $conexao->prepare($sql);
            $stmt->execute();
            $linhas = $stmt->rowCount();
            $resultado = 'affected';
            $linhas_afetadas = $linhas;
        }
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

$tabelas = Database::listTables($conexao);

$page_title = 'Editor SQL';
include 'adm_header.php';
?>
<style>
.code-area { width:100%; height:180px; font-family:'Courier New',monospace; font-size:12px; padding:8px;
    background:#0a0800; color:#FFD700; border:1px solid #ff6600; resize:vertical; }
.table-chip { display:inline-block; background:#1a1200; border:1px solid #444; padding:2px 8px; margin:2px;
    cursor:pointer; font-size:11px; color:#BBBBBB; }
.table-chip:hover { border-color:#ff6600; color:#FFD700; }
.quick-query { background:#1a1200; border:1px solid #333; padding:6px 10px; margin:3px 0;
    cursor:pointer; font-size:11px; color:#BBBBBB; border-left:3px solid #ff6600; display:block; }
.quick-query:hover { border-left-color:#FFD700; color:#FFD700; }
.result-table { border-collapse:collapse; width:100%; font-size:11px; }
.result-table th { background:url('../_img/menu.jpg') repeat-x; color:#FFD700; padding:4px 6px; border:1px solid #333; }
.result-table td { padding:4px 6px; border:1px solid #222; color:#BBBBBB; }
.result-table tr:hover td { background:#2a2200; }
</style>

<div class="box_top">📝 Editor SQL Direto</div>
<div class="box_middle">

<div class="aviso">
    ⚠️ <strong>ÁREA PERIGOSA:</strong> Este editor executa SQL diretamente no banco de dados.
    Use com extrema cautela. Comandos UPDATE, DELETE e DROP podem causar perda de dados irreversível.
</div>
<div class="sep"></div>

<table width="100%" valign="top">
<tr>
<td width="200" valign="top" style="padding-right:10px; border-right:1px solid #333;">
    <h3 style="margin-top:0;">📋 Tabelas</h3>
    <?php foreach($tabelas as $tab): ?>
        <span class="table-chip" onclick="setSQL('SELECT * FROM <?php echo $tab; ?> LIMIT 10')"><?php echo htmlspecialchars($tab); ?></span>
    <?php endforeach; ?>
    <div class="sep"></div>
    <h3>🚀 Queries Rápidas</h3>
    <div class="quick-query" onclick="setSQL('SELECT * FROM usuarios ORDER BY id DESC LIMIT 10')">👥 Últimos 10 usuários</div>
    <div class="quick-query" onclick="setSQL('SELECT COUNT(*) as total FROM usuarios')">📊 Total de usuários</div>
    <div class="quick-query" onclick="setSQL('SELECT usuario, nivel, yens FROM usuarios ORDER BY nivel DESC LIMIT 10')">🏆 Top 10 por nível</div>
    <div class="quick-query" onclick="setSQL('SELECT * FROM usuarios WHERE adm > 0')">👑 Administradores</div>
    <div class="quick-query" onclick="setSQL('SELECT status, COUNT(*) as qtd FROM usuarios GROUP BY status')">📈 Status dos jogadores</div>
    <div class="quick-query" onclick="setSQL('SELECT * FROM invasoes ORDER BY id DESC LIMIT 5')">⚡ Últimas invasões</div>
    <div class="quick-query" onclick="setSQL('SELECT * FROM organizacoes ORDER BY id DESC LIMIT 10')">🏯 Clãs</div>
</td>
<td valign="top" style="padding-left:10px;">
    <h3 style="margin-top:0;">💻 Editor SQL</h3>
    <form method="POST">
        <textarea name="sql_query" id="sqlBox" class="code-area" placeholder="Digite sua query SQL aqui...

Exemplos:
SELECT * FROM usuarios WHERE nivel > 10
UPDATE usuarios SET energia = 1000 WHERE id = 1
INSERT INTO ... VALUES (...)
DELETE FROM ... WHERE id = 999  ← CUIDADO!"><?php echo isset($_POST['sql_query']) ? htmlspecialchars($_POST['sql_query']) : ''; ?></textarea>
        <div style="margin-top:6px;">
            <button type="submit" class="botao btn-success">▶️ Executar Query</button>
            &nbsp;
            <button type="button" class="botao" onclick="document.getElementById('sqlBox').value=''">🗑️ Limpar</button>
        </div>
    </form>

    <?php if($erro): ?>
    <div class="sep"></div>
    <div class="alert-error">❌ <strong>Erro na Query:</strong><br><code style="font-family:monospace; font-size:11px;"><?php echo htmlspecialchars($erro); ?></code></div>
    <?php endif; ?>

    <?php if($resultado === 'success'): ?>
    <div class="sep"></div>
    <div class="alert-success">✅ <?php echo count($resultado_data); ?> registro(s) encontrado(s)</div>
    <div style="overflow-x:auto; max-height:350px; border:1px solid #333; margin-top:6px;">
        <table class="result-table">
            <thead>
                <tr><?php foreach(array_keys($resultado_data[0]) as $col): ?><th><?php echo htmlspecialchars($col); ?></th><?php endforeach; ?></tr>
            </thead>
            <tbody>
                <?php foreach($resultado_data as $row): ?>
                <tr><?php foreach($row as $v): ?><td><?php echo htmlspecialchars($v ?? ''); ?></td><?php endforeach; ?></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php elseif($resultado === 'empty'): ?>
    <div class="sep"></div>
    <div class="alert-warning">⚠️ Query executada com sucesso, mas nenhum resultado encontrado.</div>
    <?php elseif($resultado === 'affected'): ?>
    <div class="sep"></div>
    <div class="alert-success">✅ Query executada com sucesso! Linhas afetadas: <strong><?php echo $linhas_afetadas; ?></strong></div>
    <?php endif; ?>

    <div class="sep"></div>
    <fieldset>
        <legend>💡 Dicas</legend>
        <ul style="margin:5px 0; padding-left:18px;" class="sub2">
            <li><strong style="color:#90EE90;">SELECT</strong> — Consultar dados (seguro)</li>
            <li><strong style="color:#FFD700;">INSERT</strong> — Inserir novos dados</li>
            <li><strong style="color:#FFAAAA;">UPDATE ... WHERE</strong> — Alterar dados (sempre use WHERE!)</li>
            <li><strong style="color:#FFAAAA;">DELETE ... WHERE</strong> — Remover dados (MUITO PERIGOSO sem WHERE!)</li>
            <li>Use <strong>LIMIT</strong> para evitar resultados muito grandes</li>
        </ul>
    </fieldset>
</td>
</tr>
</table>

</div>
<div class="box_bottom"></div>

<script>
function setSQL(sql) { document.getElementById('sqlBox').value = sql; document.getElementById('sqlBox').focus(); }
</script>

<?php include 'adm_footer.php'; ?>
