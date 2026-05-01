<?php
// Processar envio de mensagem
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['msg_destino'])) {
    $destinos_str = trim($_POST['msg_destino']);
    $assunto = trim($_POST['msg_assunto']);
    $mensagem = trim($_POST['msg_msg']);
    $origem = $db['id'];
    
    if(empty($destinos_str) || empty($assunto) || empty($mensagem)) {
        echo "<script>self.location='?p=messages&type=form&msg=2'</script>";
        exit;
    }
    
    // Separar múltiplos destinatários por vírgula
    $destinos_array = array_map('trim', explode(',', $destinos_str));
    $destinos_array = array_slice($destinos_array, 0, 10); // Máximo 10 usuários
    
    // Limitar mensagem a 2048 caracteres
    $mensagem = substr($mensagem, 0, 2048);
    
    $enviado = 0;
    
    try {
        foreach($destinos_array as $destino_nome) {
            if(empty($destino_nome)) continue;
            
            // Buscar usuário destinatário (COLLATE NOCASE ignora maiúsculas/minúsculas)
            $stmt_user = $conexao->prepare("SELECT id FROM usuarios WHERE usuario = ? COLLATE NOCASE");
            $stmt_user->execute([$destino_nome]);
            $destino_user = $stmt_user->fetch(PDO::FETCH_ASSOC);
            
            error_log("DEBUG: Buscando usuário '$destino_nome' - Encontrado: " . ($destino_user ? "ID " . $destino_user['id'] : "NÃO"));
            
            if($destino_user) {
                // Inserir mensagem
                $stmt_send = $conexao->prepare("INSERT INTO mensagens (origem, destino, assunto, msg, data, status) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, 'naolido')");
                $result = $stmt_send->execute([$origem, $destino_user['id'], $assunto, $mensagem]);
                error_log("DEBUG: Inserindo mensagem de ID " . $origem . " para ID " . $destino_user['id'] . " - Resultado: " . ($result ? "SUCESSO" : "FALHOU"));
                if($result) $enviado++;
            }
        }
        
        if($enviado > 0) {
            echo "<script>self.location='?p=messages&type=form&msg=0'</script>";
        } else {
            echo "<script>self.location='?p=messages&type=form&msg=1'</script>";
        }
        exit;
        
    } catch (PDOException $e) {
        error_log("Erro ao enviar mensagem: " . $e->getMessage());
        echo "<script>self.location='?p=messages&type=form&msg=2'</script>";
        exit;
    }
}
?>
<div class="box_top">Enviar Mensagem</div>
<div class="box_middle" style="display:<?php if((isset($_GET['destiny']))or(isset($_GET['subject']))) echo 'none'; else echo 'block'; ?>;">
        <div style="border:1px dotted #999999;padding:5px;text-align:center;" id="div1">
    <a href="#" onclick="document.getElementById('div1').style.display='none';document.getElementById('div2').style.display='block';">Clique aqui para enviar uma mensagem.</a>
    <?php if(isset($_GET['msg'])){
                switch($_GET['msg']){
                        case 0: $msg='Mensagem enviada com sucesso!'; break;
                        case 1: $msg='Usuário não encontrado.'; break;
                        case 2: $msg='Erro ao enviar mensagem. Preencha todos os campos.'; break;
                }
        echo '<div class="sep"></div><div class="aviso">'.$msg.'</div>'; } ?>
    </div>
</div>
<div id="div2" class="box_middle" style="display:<?php if((isset($_GET['destiny']))or(isset($_GET['subject']))) echo 'block'; else echo 'none'; ?>;">Utilize este formulário para enviar mensagens à outros jogadores. Para formatar o texto, utilize os ícones da barra de formatação. Você pode inserir imagens e links, clicando nos últimos botões da barra, e preenchendo os campos solicitados.<div class="sep"></div>
        <?php if(isset($_GET['msg'])){
                switch($_GET['msg']){
                        case 0: $msg='Mensagem enviada com sucesso!'; break;
                        case 1: $msg='Usuário não encontrado.'; break;
                        case 2: $msg='Erro ao enviar mensagem. Preencha todos os campos.'; break;
                }
        echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>'; } ?>
    <fieldset><legend>Enviar Mensagem</legend>
        <form method="post" action="?p=messages&type=form" onsubmit="subm.value='Carregando...';subm.disabled=true;">
          <input type="hidden" id="msg_origem" name="msg_origem" value="<?php echo htmlspecialchars($db['usuario']); ?>" />
            <span class="destaque">Destino(s) da Mensagem:</span><br />
            <input type="text" id="msg_destino" name="msg_destino" maxlength="159" onfocus="className='input'" onblur="className=''" <?php if(isset($_GET['destiny'])) echo 'value="'.htmlspecialchars($_GET['destiny']).'"'; ?>/><br />
            <span class="sub2">Digite o nome dos usuários que receberão sua mensagem (para mais de um usuário, separe por vírgula - máximo de 10 usuários por mensagem).</span><br /><div class="sep"></div>
            <span class="destaque">Assunto da Mensagem:</span><br />
            <input type="text" id="msg_assunto" name="msg_assunto" maxlength="60" onfocus="className='input'" onblur="className=''" <?php if(isset($_GET['subject'])) echo 'value="'.htmlspecialchars($_GET['subject']).'"'; ?>/><br />
            <span class="sub2">Digite o assunto da mensagem.</span><br /><div class="sep"></div>
            <span class="destaque">Mensagem:</span>
            <textarea id="msg_msg" name="msg_msg" style="width:100%;height:200px;"><?php if(isset($_GET['original_msg'])) echo htmlspecialchars($_GET['original_msg']); ?></textarea>
            <span class="sub2">Mensagem a ser enviada. Apenas os primeiros 2048 caracteres serão válidos.</span>
            <?php if(isset($_GET['original_msg'])) { ?>
            <div class="sep"></div>
            <div style="background:#2a2a2a;padding:8px;border-left:3px solid #ff6600;font-size:11px;">
                <b>💬 Mensagem Original Incluída</b><br />
                <span style="color:#999;">A mensagem original foi automaticamente incluída acima. Você pode editá-la ou escrever sua resposta antes dela.</span>
            </div>
            <?php } ?>
            <div class="sep"></div>
            <div align="center"><input type="submit" id="subm" name="sub2" class="botao" value="Enviar Mensagem"></div>
        </form>
    </fieldset>
</div>
<div class="box_bottom"></div>
