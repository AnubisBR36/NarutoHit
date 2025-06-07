<div class="box_top">VIP</div>
<div class="box_middle">Abaixo temos uma tabela com os valores para doação. Escolha uma das opções de valores abaixo, e siga os procedimentos do PagSeguro ou PayPal. Lembre-se que, sendo uma doação, o jogador não terá o direito de pedir o valor de volta, pois o mesmo será utilizado em investimentos para o narutoHIT. Como agradecimento pela doação, lhe será concedido uma conta VIP.<div class="sep"></div>
	<div class="aviso">Não esqueça de confirmar sua doação, utilizando o botão no fim desta página.<br />As doações que não forem confirmadas pelo link abaixo não serão entregues.<br />Se você já confirmou uma doação, clique <a href="?p=donations">aqui</a> e veja a situação da confirmação.</div><div class="sep"></div>
	<table width="100%" cellpading="0" cellspacing="1">
        <tr class="table_dados" style="background:#323232;text-align:left;">
        	<td>Conta VIP narutoHIT -  45 dias</td>
            <td>R$ 6,00</td>
        </tr>
    </table>
  <table width="100%" cellpadding="0" cellspacing="1">
  	 <tr class="table_dados" style="background:#323232;">
  		<td>Desejo utilizar o PagSeguro para doar<br />
        	<!-- INICIO FORMULARIO BOTAO PAGSEGURO -->
             <form target="pagseguro" action="https://pagseguro.uol.com.br/checkout/checkout.jhtml" method="post">
             <input type="hidden" name="email_cobranca" value="vip@narutohit.net">
             <input type="hidden" name="tipo" value="CP">
             <input type="hidden" name="moeda" value="BRL">
             <input type="hidden" name="item_id_1" value="NH30">
             <input type="hidden" name="item_descr_1" value="Conta VIP narutoHIT - 45 dias (ID <?php echo 50000+$db['id']; ?>)">
             <input type="hidden" name="item_quant_1" value="1">
             <input type="hidden" name="item_valor_1" value="600">
             <input type="hidden" name="item_frete_1" value="0">
             <input type="image" src="https://p.simg.uol.com.br/out/pagseguro/i/botoes/pagamento/btnPagarBR.jpg" name="submit" alt="Pague com PagSeguro - é rápido, grátis e seguro!">
             </form>
           <!-- FINAL FORMULARIO BOTAO PAGSEGURO -->
        </td>
      	<td width="50%">Desejo utilizar o PayPal para doar<br />
          	<?php /*<form target="paypal" action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_s-xclick">
            <input type="hidden" name="item_name" value="Conta VIP narutoHIT - 30 dias (ID <?php echo 50000+$db['id']; ?>)">
			<input type="hidden" name="hosted_button_id" value="G3CWPQR7HLHNS">
			<input type="image" src="https://www.paypal.com/pt_BR/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - A maneira mais fácil e segura de efetuar pagamentos on-line!">
			</form>*/ ?>
            <form target="paypal" action="https://www.paypal.com/cgi-bin/webscr" method="post">
            <input type="hidden" name="cmd" value="_s-xclick">
            <input type="hidden" name="item_name" value="Conta VIP narutoHIT - 45 dias (ID <?php echo 50000+$db['id']; ?>)">
            <input type="hidden" name="hosted_button_id" value="MJ77CAQV4DSE2">
            <input type="image" src="https://www.paypal.com/pt_BR/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - A maneira mais fácil e segura de efetuar pagamentos on-line!">
            </form>
        </td>
      </tr>
    </table>
    <div class="sep"></div>
    <div align="center"><input type="button" class="botao" value="Confirmar Doação" onclick="location.href='?p=vipform'" /></div>
</div>
<div class="box_bottom"></div>