<?php
if(!isset($_GET['id'])){ echo "<script>self.location='?p=home'</script>"; break; }
$id=$c->decode($_GET['id'],$chaveuniversal);
vn($id);
if($id==0){ echo "<script>self.location='?p=donations&msg=1'</script>"; break; }
$sqlv=mysql_query("SELECT * FROM vip WHERE id=$id");
if(mysql_num_rows($sqlv)==0){ echo "<script>self.location='?p=donations&msg=1'</script>"; break; }
$dbv=mysql_fetch_assoc($sqlv);
?>
<div class="box_top">Confirmação de Doação</div>
<div class="box_middle">Abaixo estão os dados de sua confirmação de doação.<div class="sep"></div>
	<table width="100%" cellpadding="0" cellspacing="7">
    	<tr>
        	<td width="30%" style="text-align:right;padding-right:5px;">Data da Confirmação:</td>
            <td><b><?php $data=explode(' ',$dbv['data']); if($data[0]==date('Y-m-d')) echo '<b>Hoje</b>'; else echo date('d/m/Y',strtotime($data[0])); ?>, às <?php echo date('H:i:s',strtotime($data[1])); ?></b></td>
        </tr>
        <tr>
        	<td style="text-align:right;padding-right:5px;">Status da Doação:</td>
            <td bgcolor=""><b>
			<?php
            switch($dbv['status']){
				case 'analise': echo '<span style="color:#FFCC00;">Em Análise</span>'; break;
				case 'cancelado': echo '<span style="color:#FF0000;">Cancelado</span>'; break;
				case 'entregue': echo '<span style="color:#00EE00;">Entregue</span>'; break;
			} ?></b></td>
        </tr>
        <tr>
        	<td style="text-align:right;padding-right:5px;">Descrição:</td>
            <td><b><?php echo $dbv['descricao']; ?></b></td>
        </tr>
        <tr>
        	<td style="text-align:right;padding-right:5px;">Código de Autenticação:</td>
            <td><b><?php echo $dbv['autenticacao']; ?></b></td>
        </tr>
        <tr>
        	<td style="text-align:right;padding-right:5px;">Valor da Doação:</td>
            <td><b>R$ <?php echo number_format($dbv['valor'],2,',','.'); ?></b></td>
        </tr>
        <tr>
        	<td style="text-align:right;padding-right:5px;">Meio de Pagamento:</td>
            <td><b>
			<?php switch($dbv['meio']){
				case 'ps': echo 'PagSeguro'; break;
				case 'pp': echo 'PayPal'; break;
			} ?></b></td>
        </tr>
        <tr>
        	<td style="text-align:right;padding-right:5px;">Observações:</td>
        	<td valign="top"><b><?php echo nl2br($dbv['obs']); ?></b></td>
        </tr>
    </table>
    <div class="sep"></div>
    <div align="center"><input type="button" class="botao" value="Voltar" onclick="location.href='?p=donations'" /></div>
</div>
<div class="box_bottom"></div>