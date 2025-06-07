<form target="pagseguro" action="https://pagseguro.uol.com.br/checkout/checkout.jhtml" method="post">
<input type="hidden" name="email_cobranca" value="vip@narutohit.net">
<input type="hidden" name="tipo" value="CP">
<input type="hidden" name="moeda" value="BRL">
<input type="hidden" name="item_id_1" value="NH30">
<input type="hidden" name="item_descr_1" value="Conta VIP narutoHIT - 45 dias (ID <?php echo 50000+$db['id']; ?>)">
<input type="hidden" name="item_quant_1" value="1">
<input type="hidden" name="item_valor_1" value="020">
<input type="hidden" name="item_frete_1" value="0">
<input type="image" src="https://p.simg.uol.com.br/out/pagseguro/i/botoes/pagamento/btnPagarBR.jpg" name="submit" alt="Pague com PagSeguro - é rápido, grátis e seguro!">
</form>