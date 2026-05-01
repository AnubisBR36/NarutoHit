<?php
require_once('_inc/conexao.php');
require_once('_inc/verificar.php');

// Verificar se o usuário está logado
if(!$db || !isset($db['id'])) {
    die('Acesso negado - usuário não logado');
}

function vn($numero){
    if(!is_numeric($numero)){
        echo "<script>self.location='?p=home'</script>";
        exit;
    }
}

// Função para sanitizar HTML permitindo apenas tags seguras de formatação
function sanitizeMessage($html) {
    // Permitir tags de formatação básica e links
    $allowed_tags = '<b><strong><i><em><u><br><p><a>';
    
    // Remove todas as tags exceto as permitidas
    $sanitized = strip_tags($html, $allowed_tags);
    
    // Remover atributos perigosos (on*, style com javascript, class, id) mas manter href e style básico
    $sanitized = preg_replace('/<(\w+)[^>]*?(on\w+)\s*=\s*["\'][^"\']*["\'][^>]*>/i', '<$1>', $sanitized);
    
    // Remover javascript: de href
    $sanitized = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/i', 'href="#"', $sanitized);
    
    // Substituir <p> por <br /> para melhor formatação
    $sanitized = str_replace(array('<p>','</p>'),array('','<br />'),$sanitized);
    
    return $sanitized;
}

$chaveuniversal='hgfdhgfd';
require_once('_inc/Encrypt.php');
$c=new C_Encrypt();

if((!isset($_GET['id']))or(!isset($_GET['key']))){ 
    echo "<script>self.location='index.php?p=home'</script>"; 
    exit; 
}

$q=$_GET['id'];
$key=$c->decode($_GET['key'],$chaveuniversal);
vn($q); 
vn($key);

try {
    // Buscar a mensagem
    $sqlmm = $conexao->prepare("SELECT m.*,u.usuario user_origem,u2.usuario user_destino FROM mensagens m LEFT JOIN usuarios u ON m.origem=u.id LEFT JOIN usuarios u2 ON m.destino=u2.id WHERE m.id=?");
    $sqlmm->execute([$q]);
    $dbmm = $sqlmm->fetch(PDO::FETCH_ASSOC);

    if(!$dbmm) {
        echo "<script>alert('Mensagem não encontrada!'); self.location='index.php?p=messages'</script>";
        exit;
    }

    // Verificar se o usuário tem permissão para ver a mensagem
    if(($dbmm['origem'] != $db['id']) && ($dbmm['destino'] != $db['id'])){ 
        echo "<script>self.location='index.php?p=home'</script>"; 
        exit; 
    }

    // Marcar como lida se for o destinatário
    if($dbmm['destino'] == $db['id']) {
        $stmt_update = $conexao->prepare("UPDATE mensagens SET status='lido' WHERE id=?");
        $stmt_update->execute([$q]);
    }

} catch (PDOException $e) {
    error_log("Erro ao buscar mensagem: " . $e->getMessage());
    echo "<script>self.location='index.php?p=home'</script>";
    exit;
}

// Verificar se a mensagem contém a palavra "senha"
$aviso_senha = (strpos(strtolower($dbmm['msg']),'senha') !== false);

$ex=explode(' ',$dbmm['data']);
$data=explode('-',$ex[0]);

// Processar mensagem - sanitizar mas permitir formatação básica segura
$msg = sanitizeMessage($dbmm['msg']);

if($dbmm['origem']==0) $or=nome_servidor(); else $or=$dbmm['user_origem'];

echo '
<div class="modalExemplo" style="width:450px;">
<div class="city_div">
<table width="100%" cellpadding="0" cellspacing="1">
  <tr>
    <td align="left"><b>Data:</b></td>
    <td align="left">'.$data[2].'/'.$data[1].'/'.$data[0].', às '.$ex[1].'</td>
  </tr>
  <tr>
    <td width="100" align="left"><b>De:</b></td>
    <td width="385" align="left">'.htmlspecialchars($or).'</td>
  </tr>
  <tr>
    <td align="left"><b>Para:</b></td>
    <td align="left">'.htmlspecialchars($dbmm['user_destino']).'</td>
  </tr>
  <tr>
    <td align="left"><b>Assunto:</b></td>
    <td align="left">'.htmlspecialchars($dbmm['assunto']).'</td>
  </tr>
  <tr>
    <td colspan="2">&nbsp;</td>
  </tr>
  <tr>
    <td align="left" valign="top"><b>Mensagem:</b></td>
    <td align="left">'.$msg.'</td>
  </tr>';

if($aviso_senha) {
    echo '
  <tr>
    <td colspan="2" style="text-align:justify;font-size:10px;color:#CC0000;"><br /><b>FOI DETECTADO QUE A MENSAGEM ACIMA POSSUI A PALAVRA <u>SENHA</u> EM SEU CONTEXTO. LEMBRE-SE QUE <u>JAMAIS</u> REQUISITAREMOS SUA SENHA DE ACESSO. PARA SUA SEGURANÇA, E PARA A SEGURANÇA DOS DEMAIS JOGADORES, ESTA MENSAGEM TAMBÉM FOI ENVIADA PARA NOSSA EQUIPE. CASO SEJA UMA TENTATIVA DE ROUBO DE SUA SENHA, O USUÁRIO QUE A ENVIOU SERÁ BANIDO DO JOGO.</b>
    </td>
  </tr>';
}

// Preparar mensagem original para citação
// Converter <br> e <p> em quebras de linha antes de remover tags HTML
$msg_para_citacao = str_replace(['<br>', '<br />', '<br/>', '</p>'], ["\n", "\n", "\n", "\n"], $dbmm['msg']);
$msg_para_citacao = strip_tags($msg_para_citacao); // Remove HTML mantendo o texto

$mensagem_citada = "\n\n--- Mensagem Original ---\n";
$mensagem_citada .= "De: " . $or . "\n"; // Sem htmlspecialchars - será aplicado no messages_form.php
$mensagem_citada .= "Data: " . $data[2].'/'.$data[1].'/'.$data[0].', às '.$ex[1] . "\n";
$mensagem_citada .= "Assunto: " . $dbmm['assunto'] . "\n\n"; // Sem htmlspecialchars
$mensagem_citada .= $msg_para_citacao;

echo '
</table>
</div>
<div align="center" style="margin-top:7px;">';

// Só mostrar botão responder se não for mensagem do sistema
$user_origem_safe = $dbmm['user_origem'] ?? '';
if($dbmm['origem'] != 0 && !empty($user_origem_safe)) {
    echo '<a href="index.php?p=messages&type=form&destiny='.urlencode($user_origem_safe).'&subject='.urlencode('RE:'.$dbmm['assunto']).'&original_msg='.urlencode($mensagem_citada).'" style="color:#666666;">
        <img src="_img/refresh.png" border="0" align="absmiddle" width="12" height="12" /> Responder
    </a> | ';
}

echo '<a href="#" rel="modalclose" style="color:#666666;">
        <img src="_img/close.jpg" border="0" align="absmiddle" width="12" height="12" /> Fechar
    </a>
</div>
</div>';
?>
