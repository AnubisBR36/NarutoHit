<?
$mysql_banco='narutohi_nh';
$mysql_usuario='narutohi_nh';
$mysql_senha='1004lean';
$mysql_host='localhost';
$conexao=mysql_pconnect($mysql_host,$mysql_usuario,$mysql_senha);
mysql_select_db($mysql_banco);
  $silent = 'true';

function tep_not_null($value) {

if (is_array($value)) {

if (sizeof($value) > 0) {

return true;

} else {

return false;

}

} else {

if (($value != '') && ($value != 'NULL') && (strlen(trim($value)) > 0)) {

return true;

} else {

return false;

}

}

}
//fim conexção
// RECEBE O POST ENVIADO PELA PagSeguro E ADICIONA OS VALORES PARA VALIDAÇÃO DOS DADOS
$PagSeguro = 'Comando=validar';
$PagSeguro .= '&Token=6ADDCECD18324C0D9E0CC55E7B02AEEB';
$Cabecalho = "";

foreach ($_POST as $key => $value)
{
 $value = urlencode(stripslashes($value));
 $PagSeguro .= "&$key=$value";
}

if (function_exists('curl_exec'))
{
 //Prefira utilizar a função CURL do PHP
 //Leia mais sobre CURL em: http://us3.php.net/curl
 $curl = true;
}
elseif ( (PHP_VERSION >= 4.3) && ($fp = @fsockopen ('ssl://pagseguro.uol.com.br', 443, $errno, $errstr, 30)) )
{
 $fsocket = true;
}
elseif ($fp = @fsockopen('pagseguro.uol.com.br', 80, $errno, $errstr, 30))
{
 $fsocket = true;
}

// ENVIA DE VOLTA PARA A PagSeguro OS DADOS PARA VALIDAÇÃO
if ($curl == true)
{
 $ch = curl_init();

 curl_setopt($ch, CURLOPT_URL, 'https://pagseguro.uol.com.br/Security/NPI/Default.aspx');
 curl_setopt($ch, CURLOPT_POST, true);
 curl_setopt($ch, CURLOPT_POSTFIELDS, $PagSeguro);
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 curl_setopt($ch, CURLOPT_HEADER, false);
 curl_setopt($ch, CURLOPT_TIMEOUT, 30);
 curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

 $resp = curl_exec($ch);
 if (!tep_not_null($resp))
 {
    curl_setopt($ch, CURLOPT_URL, 'https://pagseguro.uol.com.br/Security/NPI/Default.aspx');
    $resp = curl_exec($ch);
 }

 curl_close($ch);
 $confirma = (strcmp ($resp, "VERIFICADO") == 0);
}
elseif ($fsocket == true)
{
 $Cabecalho  = "POST /Security/NPI/Default.aspx HTTP/1.0\r\n";
 $Cabecalho .= "Content-Type: application/x-www-form-urlencoded\r\n";
 $Cabecalho .= "Content-Length: " . strlen($PagSeguro) . "\r\n\r\n";

 if ($fp || $errno>0)
 {
    fputs ($fp, $Cabecalho . $PagSeguro);
    $confirma = false;
    $resp = '';
    while (!feof($fp))
    {
       $res = @fgets ($fp, 1024);
       $resp .= $res;
       // Verifica se o status da transação está VERIFICADO
       if (strcmp ($res, "VERIFICADO") == 0)
       {
          $confirma=true;
          break;
       }
    }
    fclose ($fp);
 }
 else
 {
    echo "$errstr ($errno)<br />\n";
    // ERRO HTTP
 }
}


if ($confirma)
{
 // RECEBE OS DADOS ENVIADOS PELA PagSeguro E ARMAZENA EM VARIÁVEIS
 //Selecione aqui todos os parâmetros enviados pela PagSeguro
 $TransacaoID = $_POST['TransacaoID'];
 $Referencia = $_POST['Referencia'];
 $StatusTransacao = $_POST['StatusTransacao'];
 $CliNome = $_POST['CliNome'];
 $NumItens = $_POST['NumItens'];
//conecção
$sql = mysql_query("SELECT * FROM pagseguro WHERE id=$Referencia");
$dados= mysql_fetch_array($sql) ;
$status=$dados['status'];
$valor=$dados['total'];

//inico curl api
if ($status=="Unpaid" ) {

if ($StatusTransacao=="Aprovado" ) {
$ProdValor_MB = $valor;
mysql_query("UPDATE usuarios SET creditos=creditos+600 WHERE usuario='Blake'"); # URL to WHMCS API file
$username = "leandersantosm"; # Admin username goes here
$password = "leancaio"; # Admin password goes here

$postfields["username"] = $username;
$postfields["password"] = md5($password);
$postfields["action"] = "addinvoicepayment";
$postfields["invoiceid"] = $Referencia;
$postfields["amount"] = $ProdValor_MB;
$postfields["transid"] = $TransacaoID;
$postfields["gateway"] = "pagseguro";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 100);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
$data = curl_exec($ch);
curl_close($ch);

$data = explode(";",$data);
foreach ($data AS $temp) {
  $temp = explode("=",$temp);
  $results[$temp[0]] = $temp[1];
}

if ($results["result"]=="success") {
  # Result was OK!
} else {
  # An error occured
  echo "The following error occured: ".$results["message"];
}

//fim Api
//fecha Unpaid
}
//fecha status transação
}
//fim meio continua pagseguro fechando
}
else
{
 if (strcmp ($res, "FALSO") == 0)
 {
    // LOG para investigação manual
 }
}

// FECHA A CONEXÃO
mysql_close($conexao);

?>
<meta http-equiv="refresh" content="1;URL=http://www.narutohit.net" />
Obrigado por nos escolher.