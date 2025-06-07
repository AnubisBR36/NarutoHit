<?php
$sqlv=mysql_query("SELECT * FROM vip WHERE usuarioid=".$db['id']);
$dbv=mysql_fetch_assoc($sqlv);
?>
<div class="box_top">Doações</div>
<div class="box_middle">Abaixo estão relacionadas as confirmações de doação cadastradas por você.<div class="sep"></div>
	<?php
	if(isset($_GET['msg'])){
	switch($_GET['msg']){
		case 1: $msg='Registro não foi encontrado.'; break;
	}
	echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
	}
	?>
	<table width="100%" cellpading="0" cellspacing="1">
    	<tr class="table_titulo">
        	<td width="70">Data</td>
            <td width="40%">Informações</td>
            <td width="13%">Valor</td>
            <td>Status</td>
            <td width="13%">&nbsp;</td>
        </tr>
        <?php if(mysql_num_rows($sqlv)==0) echo '<tr><td colspan="5"><div class="aviso">Nenhum registro encontrado.</div></td></tr>'; else do{ $data=explode(' ',$dbv['data']); ?>
        <tr class="table_dados" style="background:#323232;">
            <td align="center"><?php if($data[0]==date('Y-m-d')) echo '<b>Hoje</b>'; else echo date('d/m/Y',strtotime($data[0])); ?><br /><?php echo date('H:i:s',strtotime($data[1])); ?></td>
            <td style="padding-left:5px;text-align:left;"><b><?php
            switch($dbv['descricao']){
				case 'vip30': echo 'Conta VIP narutoHIT - 30 dias'; break;
			} ?></b><br /><span class="sub2">Código de Autenticação: <?php echo substr($dbv['autenticacao'],0,7); ?>...</span></td>
            <td align="center">R$ <?php echo number_format($dbv['valor'],2,',','.'); ?></td>
            <td><?php
            switch($dbv['status']){
				case 'analise': echo '<span style="color:#FFCC00;">Em Análise</span>'; break;
				case 'cancelado': echo '<span style="color:#FF0000;">Cancelado</span>'; break;
				case 'entregue': echo '<span style="color:#00EE00;">Entregue</span>'; break;
			} ?></td>
            <td><a href="?p=viewdonation&amp;id=<?php echo $c->encode($dbv['id'],$chaveuniversal); ?>">Visualizar</a></td>
	       	<?php /*<td><?php echo $dbv['data']; ?></td>
            <td>R$ <?php echo number_format($dbv['valor']); ?></td>
            <td><?php echo $dbv['autenticacao']; ?></td>
            <td><?php
            switch($dbr['status']){
				case 'analise': echo '<span style="color:#FFCC00;">Em Análise</span>'; break;
			} ?></td>
            <td><a href="?p=viewdonation&id=<?php echo $c->encode($dbv['id'],$chaveuniversal); ?>">Visualizar</a></td>*/ ?>
        </tr>
        <?php } while($dbv=mysql_fetch_assoc($sqlv)); ?>
    </table>
    <div class="sep"></div>
    <div align="center"><input type="button" class="botao" value="Fazer Doação" onclick="location.href='?p=vip2'" /> <input type="button" class="botao" value="Confirmar Doação" onclick="location.href='?p=vipform'" /></div>
</div>
<div class="box_bottom"></div>