
<?php
require_once('_inc/conexao.php');

// Verificar se o campo já existe
$check = mysql_query("SHOW COLUMNS FROM usuarios LIKE 'missao_inicio'");
if(mysql_num_rows($check) == 0) {
    // Adicionar o campo missao_inicio
    $query = "ALTER TABLE usuarios ADD COLUMN missao_inicio DATETIME DEFAULT NULL AFTER missao_tempo";
    $result = mysql_query($query);
    
    if($result) {
        echo "Campo 'missao_inicio' adicionado com sucesso!<br>";
        
        // Atualizar missões em andamento para ter um início calculado
        $update = "UPDATE usuarios SET missao_inicio = DATE_SUB(missao_fim, INTERVAL missao_tempo HOUR) WHERE missao > 0 AND missao_inicio IS NULL";
        $update_result = mysql_query($update);
        
        if($update_result) {
            echo "Missões em andamento atualizadas com sucesso!<br>";
        } else {
            echo "Erro ao atualizar missões em andamento: " . mysql_error() . "<br>";
        }
    } else {
        echo "Erro ao adicionar campo: " . mysql_error() . "<br>";
    }
} else {
    echo "Campo 'missao_inicio' já existe!<br>";
}

mysql_close();
?>
