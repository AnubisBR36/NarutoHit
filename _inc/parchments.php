<?php
$sqlp=mysql_query("SELECT count(u.id) conta,u.id,t.descricao,t.nome,t.imagem FROM usaveis u LEFT OUTER JOIN table_usaveis t ON u.itemid=t.id WHERE u.usuarioid=".$db['id']." GROUP BY u.itemid ORDER BY u.itemid ASC");
$dbp=mysql_fetch_assoc($sqlp);
?>
<div class="box_top">Pergaminhos</div>
<div class="box_middle">Abaixo estão a quantidade de pergaminhos que você possui.<div class="sep"></div>
	<div align="center"><a href="?p=inventory">Itens</a> | <a href="?p=parchments">Pergaminhos</a></div>
	<table width="100%" cellpadding="0" cellspacing="1">
    <?php if(mysql_num_rows($sqlp)==0) echo '<tr><td><div class="sep"></div></td></tr><tr><td><div class="aviso">Nenhum pergaminho encontrado.</div></td></tr>'; else do{ ?>
    <tr>
    	<td colspan="2"><div class="sep"></div></td>
    </tr>
    <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
    	<td align="center" width="140"><img src="_img/equipamentos/<?php echo $dbp['imagem']; ?>.jpg" /></td>
        <td style="padding:5px;">
        	<b><?php echo $dbp['nome']; ?></b><br />
            <span class="sub2"><?php echo $dbp['descricao']; ?></span>
            <br /><br />Quantidade: <b><?php echo $dbp['conta']; ?> pergaminho<?php if($dbp['conta']>1) echo 's'; ?></b>
          </td>
  	</tr>
    <?php } while($dbp=mysql_fetch_assoc($sqlp)); ?>
    </table>
</div>
<div class="box_bottom"></div>