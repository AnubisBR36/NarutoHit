
<?php
// Incluir arquivos de conexão
require_once('_inc/conexao.php');
require_once('_inc/include.php');

echo "<h3>Teste de Conexão com Banco de Dados</h3>";

// Testar conexão
if(function_exists('mysql_connect')) {
    echo "✓ Função mysql_connect está disponível<br>";
} else {
    echo "✗ Função mysql_connect NÃO está disponível<br>";
}

// Verificar se as tabelas existem
$tables = array('usuarios', 'salas');
foreach($tables as $table) {
    $result = mysql_query("SHOW TABLES LIKE '$table'");
    if($result && mysql_num_rows($result) > 0) {
        echo "✓ Tabela '$table' existe<br>";
        
        // Mostrar estrutura da tabela
        $structure = mysql_query("DESCRIBE $table");
        if($structure) {
            echo "&nbsp;&nbsp;Campos: ";
            $fields = array();
            while($row = mysql_fetch_assoc($structure)) {
                $fields[] = $row['Field'];
            }
            echo implode(", ", $fields) . "<br>";
        }
    } else {
        echo "✗ Tabela '$table' NÃO existe<br>";
    }
}

// Testar consulta específica da escola
echo "<br><h4>Teste da consulta das salas:</h4>";
$sqls=mysql_query("SELECT s.*,u.usuario FROM salas s LEFT OUTER JOIN usuarios u ON s.usuarioid=u.id ORDER BY s.id ASC");
if($sqls) {
    echo "✓ Consulta executada com sucesso<br>";
    echo "Número de salas encontradas: " . mysql_num_rows($sqls) . "<br>";
} else {
    echo "✗ Erro na consulta: " . mysql_error() . "<br>";
}
?>
