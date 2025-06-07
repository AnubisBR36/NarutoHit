<?php require_once('_inc/conexao.php'); ?>
<?php
function vn($numero){
	if(!is_numeric($numero)){
			echo "<script>self.location='?p=home'</script>"; break;
	}
}
$chaveuniversal='hgfdhgfd';
require_once('_inc/Encrypt.php');
$c=new C_Encrypt();
if((!isset($_GET['id']))or(!isset($_GET['key']))){ echo "<script>self.location='index.php?p=home'</script>"; break; }
$q=$_GET['id'];
$key=$c->decode($_GET['key'],$chaveuniversal);
vn($q); vn($key);

try {
    $stmt_update = $conexao->prepare("UPDATE mensagens SET status='lido' WHERE id=?");
    $stmt_update->execute([$q]);
    
    $sqlmm = $conexao->prepare("SELECT m.*,u.usuario user_origem,u2.usuario user_destino FROM mensagens m LEFT JOIN usuarios u ON m.origem=u.id LEFT JOIN usuarios u2 ON m.destino=u2.id WHERE m.id=?");
    $sqlmm->execute([$q]);
    $dbmm = $sqlmm->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar mensagem: " . $e->getMessage());
    echo "<script>self.location='index.php?p=home'</script>";
    exit;
}
if(($dbmm['origem']<>$key)&&($dbmm['destino']<>$key)){ echo "<script>self.location='index.php?p=home'</script>"; break; }
if(strpos($dbmm['msg'],'senha')==true) $aviso='<tr><td colspan="2" style="text-align:justify;"><br /><b>FOI DETECTADO QUE A MENSAGEM ACIMA POSSUI A PALAVRA <u>SENHA</u> EM SEU CONTEXTO. LEMBRE-SE QUE <u>JAMAIS</u> REQUISITAREMOS SUA SENHA DE ACESSO. PARA SUA SEGURANÇA, E PARA A SEGURANÇA DOS DEMAIS JOGADORES, ESTA MENSAGEM TAMBÉM FOI ENVIADA PARA NOSSA EQUIPE. CASO SEJA UMA TENTATIVA DE ROUBO DE SUA SENHA, O USUÁRIO QUE A ENVIOU SERÁ BANIDO DO JOGO.</b></td></tr>'; else $aviso='';
$ex=explode(' ',$dbmm['data']);
$data=explode('-',$ex[0]);
$msg=str_replace(array('<p>','</p>'),array('','<br />'),$dbmm['msg']);
if($dbmm['origem']==0) $or='narutoHIT'; else $or=$dbmm['user_origem'];
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
    <td width="385" align="left">'.$or.'</td>
  </tr>
  <tr>
    <td align="left"><b>Para:</b></td>
    <td align="left">'.$dbmm['user_destino'].'</td>
  </tr>
  <tr>
    <td align="left"><b>Assunto:</b></td>
    <td align="left">'.$dbmm['assunto'].'</td>
  </tr>
  <tr>
    <td colsan="2">&nbsp;</td>
  </tr>
  <tr>
    <td align="left" valign="top"><b>Mensagem:</b></td>
    <td align="left">'.nl2br($msg).'</td>
  </tr>';
  if(strpos($dbmm['msg'],'senha')==true) echo '
  <tr>
  	<td colspan="2" style="text-align:justify;font-size:10px;color:#CC0000;"><br /><b>FOI DETECTADO QUE A MENSAGEM ACIMA POSSUI A PALAVRA <u>SENHA</u> EM SEU CONTEXTO. LEMBRE-SE QUE <u>JAMAIS</u> REQUISITAREMOS SUA SENHA DE ACESSO. PARA SUA SEGURAN&Ccedil;A, E PARA A SEGURAN&Ccedil;A DOS DEMAIS JOGADORES, ESTA MENSAGEM TAMB&Eacute;M FOI ENVIADA PARA NOSSA EQUIPE. CASO SEJA UMA TENTATIVA DE ROUBO DE SUA SENHA, O USU&Aacute;RIO QUE A ENVIOU SER&Aacute; BANIDO DO JOGO.</b>
	</td>
  </tr>';
  echo '
</table>
</div>
<div align="center" style="margin-top:7px;"><a href="?p=messages&destiny='.$dbmm['user_origem'].'&subject=R:'.$dbmm['assunto'].'" style="color:#666666;"><img src="_img/refresh.png" border="0" align="absmiddle" width="12" height="12" /> Responder</a> | <a href="#" rel="modalclose" style="color:#666666;"><img src="_img/close.jpg" border="0" align="absmiddle" width="12" height="12" /> Fechar</a></div>
</div>';
@mysql_free_result($sqlmm);
?>