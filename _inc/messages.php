<?php
// Verificar se o usuário está logado
if (!isset($db['id'])) {
    echo "<script>self.location='?p=home'</script>";
    exit;
}

// Processar ação de deletar mensagem
if(isset($_GET['del'])) {
    $msg_id = (int)$_GET['del'];
    try {
        // Verificar se a mensagem pertence ao usuário logado
        $stmt_check = $conexao->prepare("SELECT id FROM mensagens WHERE id = ? AND (origem = ? OR destino = ?)");
        $stmt_check->execute([$msg_id, $db['id'], $db['id']]);

        if($stmt_check->fetch(PDO::FETCH_ASSOC)) {
            $stmt_delete = $conexao->prepare("DELETE FROM mensagens WHERE id = ?");
            $stmt_delete->execute([$msg_id]);

            if(isset($_GET['type']) && $_GET['type'] == 'e') {
                echo "<script>self.location='?p=messages&type=e'</script>";
            } else {
                echo "<script>self.location='?p=messages&type=r'</script>";
            }
            exit;
        }
    } catch (PDOException $e) {
        error_log("Erro ao deletar mensagem: " . $e->getMessage());
    }
}

// Determinar qual aba mostrar
// Aceitar tanto 'type' quanto 'aba' como parâmetros (aba=escrever redireciona para type=form)
if(isset($_GET['aba']) && $_GET['aba'] == 'escrever') {
    $type = 'form';
} else {
    $type = isset($_GET['type']) ? $_GET['type'] : 'r';
}
?>

<table width="100%" border="0">
  <tr>
    <td width="33%" align="center" style="background:#323232;<?php if($type=='r') echo 'border-bottom:2px solid #444;'; ?>">
      <a href="?p=messages&type=r" style="color:#CCCCCC; text-decoration:none; display:block; padding:8px;">
        <b>Mensagens Recebidas</b>
      </a>
    </td>
    <td width="33%" align="center" style="background:#323232;<?php if($type=='e') echo 'border-bottom:2px solid #444;'; ?>">
      <a href="?p=messages&type=e" style="color:#CCCCCC; text-decoration:none; display:block; padding:8px;">
        <b>Mensagens Enviadas</b>
      </a>
    </td>
    <td width="34%" align="center" style="background:#323232;<?php if($type=='form') echo 'border-bottom:2px solid #444;'; ?>">
      <a href="?p=messages&type=form" style="color:#CCCCCC; text-decoration:none; display:block; padding:8px;">
        <b>Enviar Mensagem</b>
      </a>
    </td>
  </tr>
</table>

<?php
if($type == 'r') {
    // Incluir mensagens recebidas
    include('_inc/messages_r.php');
} else if($type == 'e') {
    // Incluir mensagens enviadas
    include('_inc/messages_e.php');
} else if($type == 'form') {
    // Incluir formulário de enviar mensagem
    include('_inc/messages_form.php');
}
?>
