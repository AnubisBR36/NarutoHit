<?php
/*
Autor: Jocean Martins By Jefferson Maceira
E-mail: email@cyberdata.com.br
*/

# Pagseguro BR Versão Cyberdata- Payment Gateway Module

$GATEWAYMODULE["pagseguroname"]="pagseguro";
$GATEWAYMODULE["pagsegurovisiblename"]="PagSeguro 4.0 Com retorno";
$GATEWAYMODULE["pagsegurotype"]="Invoices";

function pagseguro_activate() {
defineGatewayField("pagseguro","text","conta","","Conta","50","Preencher o email de sua conta vendedor pagseguro");
defineGatewayField("pagseguro","text","taxa","","Taxa","6","Informar aqui a taxa de percentagem para adicionar à fatura, Ex: 5% digite 0.05, o resultado será somado com 0,40 a seguir, ex: valor fatura R$ 5,00x0.05+0,40=0.65, o valor a pagar será=5.65");
}

//function pagseguro_link($params) {
// taxa
$a = $params['taxa'];
$b = $params['amount'];
$c = 0.40;
$taxa = $a * $b;
// soma taxa
$valort= $b + $c + $taxa;
$ttaxa = 0;
$valort = number_format($valort, "2", ".", ",");
//uf
$sigla_uf = '';

if ($params['clientdetails']['state'] == 'Acre') {

   $sigla_uf = 'AC';

} else if ($params['clientdetails']['state'] == 'Alagoas') {

   $sigla_uf = 'AL';

} else if (($params['clientdetails']['state'] == 'Amapa') || ($params['clientdetails']['state'] == 'Amapá')) {

   $sigla_uf = 'AP';

} else if ($params['clientdetails']['state'] == 'Amazonas') {

   $sigla_uf = 'AM';

} else if ($params['clientdetails']['state'] == 'Bahia') {

   $sigla_uf = 'BA';

} else if (($params['clientdetails']['state'] == 'Ceara') || ($params['clientdetails']['state'] == 'Ceará')) {

   $sigla_uf = 'CE';

} else if ($params['clientdetails']['state'] == 'Distrito Federal') {

   $sigla_uf = 'DF';

} else if ($params['clientdetails']['state'] == 'Espirito Santo') {

   $sigla_uf = 'ES';

} else if (($params['clientdetails']['state'] == 'Goias') || ($params['clientdetails']['state'] == 'Goiás')) {

   $sigla_uf = 'GO';

} else if (($params['clientdetails']['state'] == 'Maranhao') || ($params['clientdetails']['state'] == 'Maranhão')) {

   $sigla_uf = 'MA';

} else if ($params['clientdetails']['state'] == 'Mato Grosso') {

   $sigla_uf = 'MT';

} else if ($params['clientdetails']['state'] == 'Mato Grosso do Sul') {

   $sigla_uf = 'MS';

} else if ($params['clientdetails']['state'] == 'Minas Gerais') {

   $sigla_uf = 'MG';

} else if (($params['clientdetails']['state'] == 'Para') || ($params['clientdetails']['state'] == 'Pará')) {

   $sigla_uf = 'PA';

} else if (($params['clientdetails']['state'] == 'Paraiba') || ($params['clientdetails']['state'] == 'Paraíba')) {

   $sigla_uf = 'PB';

} else if (($params['clientdetails']['state'] == 'Parana') || ($params['clientdetails']['state'] == 'Paraná')) {

   $sigla_uf = 'PR';

} else if ($params['clientdetails']['state'] == 'Pernambuco') {

   $sigla_uf = 'PE';

} else if (($params['clientdetails']['state'] == 'Piaui') || ($params['clientdetails']['state'] == 'Piauí')) {

   $sigla_uf = 'PI';

} else if ($params['clientdetails']['state'] == 'Rio de Janeiro') {

   $sigla_uf = 'RJ';

} else if ($params['clientdetails']['state'] == 'Rio Grande do Norte') {

   $sigla_uf = 'RN';

} else if ($params['clientdetails']['state'] == 'Rio Grande do Sul') {

   $sigla_uf = 'RS';

} else if (($params['clientdetails']['state'] == 'Rondonia') || ($params['clientdetails']['state'] == 'Rondônia')) {

   $sigla_uf = 'RO';

} else if ($params['clientdetails']['state'] == 'Roraima') {

   $sigla_uf = 'RR';

} else if ($params['clientdetails']['state'] == 'Santa Catarina') {

   $sigla_uf = 'SC';

} else if (($params['clientdetails']['state'] == 'Sao Paulo') || ($params['clientdetails']['state'] == 'São Paulo')) {

   $sigla_uf = 'SP';

} else if ($params['clientdetails']['state'] == 'Sergipe') {

   $sigla_uf = 'SE';

} else if ($params['clientdetails']['state'] == 'Tocantins') {

   $sigla_uf = 'TO';

} else {

   $sigla_uf = $params['clientdetails']['state'];

}

//cep
$cli_cep = sprintf('%08d',$params['clientdetails']['postcode']);
$code=' <br />
Valor da Fatura: <b>R$ 0,20 </b> <br>
Taxas de cobrança PagSeguro R$ '.$ttaxa.' <br>
Valor a Pagar: <b>R$ 0,20 </b><br><br />

<form target="pagseguro" action="https://pagseguro.uol.com.br/security/webpagamentos/webpagto.aspx" method="post">
<input type="hidden" name="email_cobranca" value="vip@narutohit.net">
<input type="hidden" name="tipo" value="CP">
<input type="hidden" name="moeda" value="BRL">
<input type="hidden" name="ref_transacao" value="1">
<input type="hidden" name="item_id_1" value="1">
<input type="hidden" name="item_descr_1" value="testes">
<input type="hidden" name="item_quant_1" value="1">
<input type="hidden" name="item_valor_1" value="020">
<input type="hidden" name="item_frete_1" value="0">
<input type="hidden" name="cliente_nome" value="Leander">
<input type="hidden" name="cliente_cep" value="15385-000">
<input type="hidden" name="cliente_end" value="Passeio Teresina">
<input type="hidden" name="cliente_bairro" value="Zona Norte">
<input type="hidden" name="cliente_cidade" value="Ilha Solteira">
<input type="hidden" name="cliente_uf" value="SP">
<input type="hidden" name="cliente_pais" value="Brasil">
<input type="hidden" name="cliente_ddd" value="">
<input type="hidden" name="cliente_tel" value="1888149026">
<input type="hidden" name="cliente_email" value="leander_90@hotmail.com">';

$code.='<input type="submit" value="'.$params['langpaynow'].'"></form>';

echo $code;
?>