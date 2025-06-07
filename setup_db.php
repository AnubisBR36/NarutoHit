
<?php
// Script temporário para configurar o banco de dados
$host = 'localhost';
$user = 'root';
$pass = '';

// Conectar sem especificar banco
$conn = mysqli_connect($host, $user, $pass);

if (!$conn) {
    die('Erro de conexão: ' . mysqli_connect_error());
}

// Criar banco se não existir
$sql = "CREATE DATABASE IF NOT EXISTS narutohi_nh2 CHARACTER SET utf8 COLLATE utf8_general_ci";
if (mysqli_query($conn, $sql)) {
    echo "Banco de dados criado/verificado com sucesso<br>";
} else {
    echo "Erro ao criar banco: " . mysqli_error($conn) . "<br>";
}

mysqli_close($conn);

echo "Configuração concluída. Você pode acessar o site agora.";
?>
