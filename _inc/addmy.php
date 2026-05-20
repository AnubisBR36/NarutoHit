<?php
if(date('Y-m-d H:i:s')>=$db['vip']){ echo "<script>self.location='?p=msgvip'</script>"; return; }

$moeda_labels = ['yens'=>'Yens','cristal1'=>'Cristal Refinado','cristal2'=>'Cristal Bruto','cristal3'=>'Chakra Forjado'];
$moeda_icons  = ['yens'=>'_img/yens.png','cristal1'=>'_img/ferreiro/Cristal de Chakra Refinado.png','cristal2'=>'_img/ferreiro/Cristal de Chakra Bruto.png','cristal3'=>'_img/ferreiro/Chakra Forjado.png'];

if(isset($_POST['id'])){
        vn($_POST['valor']);
        $moeda = in_array($_POST['moeda_tipo'] ?? 'yens', array_keys($moeda_labels)) ? $_POST['moeda_tipo'] : 'yens';
        // Sanitiza o valor: remove tudo exceto dígitos (a máscara do front pode enviar "1.234" ou "1.234,00" para Yens)
        // Cristais são SEMPRE inteiros; Yens também são tratados como inteiros pelo sistema (já era assim antes).
        $raw = (string)$_POST['valor'];
        $raw = preg_replace('/[^0-9]/', '', $raw); // remove pontos, vírgulas e qualquer outro caractere
        $valor = (int)$raw;
        if($valor <= 0) $valor = 1;
        $sqli=$conexao->prepare("SELECT * FROM inventario WHERE id=?");
        $sqli->execute([$_POST['id']]);
        $dbi=$sqli->fetch(PDO::FETCH_ASSOC);
        $sqlv=$conexao->prepare("SELECT valor FROM table_itens WHERE id=?");
        $sqlv->execute([$dbi['itemid']]);
        $dbv=$sqlv->fetch(PDO::FETCH_ASSOC);
        if($dbi['usuarioid']<>$db['id']){ echo "<script>self.location='?p=home'</script>"; return; }
        if($moeda==='yens'){
                $maximo=(($dbi['upgrade']+1)*$dbv['valor']);
                if($valor>$maximo){ echo "<script>self.location='?p=addmy&id=".$_POST['id']."&msg=1'</script>"; return; }
        }
        $upd=$conexao->prepare("UPDATE inventario SET venda='sim', status='off', valor=?, moeda_tipo=? WHERE id=?");
        $upd->execute([$valor, $moeda, $_POST['id']]);
        echo "<script>self.location='?p=myshop&msg=1'</script>";
        return;
}
if(!isset($_GET['id'])){ echo "<script>self.location='?p=home'</script>"; return; }
$sqli=$conexao->prepare("SELECT i.id,i.status,i.upgrade,i.usuarioid,t.categoria,t.descricao,t.taijutsu,t.ninjutsu,t.genjutsu,t.nome,t.imagem,t.valor FROM inventario i LEFT OUTER JOIN table_itens t ON i.itemid=t.id WHERE i.id=? ORDER BY i.status ASC");
$sqli->execute([$_GET['id']]);
$dbi=$sqli->fetch(PDO::FETCH_ASSOC);
if(!$dbi){ echo "<script>self.location='?p=home'</script>"; return; }
if($dbi['usuarioid']<>$db['id']){ echo "<script>self.location='?p=home'</script>"; return; }
?>
<div class="box_top">Anunciar Item</div>
<div class="box_middle">Digite as informações de venda do item. Escolha a moeda desejada e o valor. Caso anuncie um item equipado, ele será retirado automaticamente.<div class="sep"></div>
        <?php if(isset($_GET['msg'])) echo '<div class="aviso">Valor excede o máximo permitido para Yens.</div><div class="sep"></div>'; ?>
        <table width="100%" cellpadding="0" cellspacing="1">
<tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
        <td align="center" width="140" valign="top"><img src="_img/equipamentos/<?php echo $dbi['imagem']; ?>.png" /></td>
        <td style="padding:5px;">
                <b><?php echo $dbi['nome']; ?><?php if($dbi['upgrade']>0) echo ' +'.$dbi['upgrade']; ?></b><br />
                <span class="sub2"><?php echo $dbi['descricao']; ?></span><br />
                <b><?php if($dbi['taijutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['taijutsu']+$dbi['upgrade']).'] em Taijutsu<br />'; ?>
                <?php if($dbi['ninjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['ninjutsu']+$dbi['upgrade']).'] em Ninjutsu<br />'; ?>
                <?php if($dbi['genjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['genjutsu']+$dbi['upgrade']).'] em Genjutsu<br />'; ?></b>
                <br />
                <form method="post" action="?p=addmy" onsubmit="subm.value='Carregando...';subm.disabled=true;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo (int)$_GET['id']; ?>" />
                <b>Escolha a Moeda de Venda:</b><br />
                <div style="margin:8px 0;line-height:2.2;">
                        <label style="margin-right:16px;cursor:pointer;">
                                <input type="radio" name="moeda_tipo" value="yens" checked />
                                <img src="_img/yens.png" width="16" height="16" align="absmiddle" style="margin:0 4px;" /> Yens
                        </label><br />
                        <label style="margin-right:16px;cursor:pointer;">
                                <input type="radio" name="moeda_tipo" value="cristal1" />
                                <img src="_img/ferreiro/Cristal de Chakra Refinado.png" width="22" height="22" align="absmiddle" style="margin:0 4px;" /> Cristal de Chakra Refinado
                        </label><br />
                        <label style="margin-right:16px;cursor:pointer;">
                                <input type="radio" name="moeda_tipo" value="cristal2" />
                                <img src="_img/ferreiro/Cristal de Chakra Bruto.png" width="22" height="22" align="absmiddle" style="margin:0 4px;" /> Cristal de Chakra Bruto
                        </label><br />
                        <label style="cursor:pointer;">
                                <input type="radio" name="moeda_tipo" value="cristal3" />
                                <img src="_img/ferreiro/Chakra Forjado.png" width="22" height="22" align="absmiddle" style="margin:0 4px;" /> Chakra Forjado
                        </label>
                </div>
                <b>Quantidade / Preço:</b><br />
                <input type="text" name="valor" id="valor_input" value="" size="14" autocomplete="off" inputmode="numeric" />&nbsp;
                <input type="submit" id="subm" name="subm" class="botao" value="Anunciar" /><br />
                <span class="sub2" id="valor_dica">Para Yens, máximo: <?php echo number_format((($dbi['upgrade']+1)*$dbi['valor']),0,',','.'); ?> yens. Para cristais, informe a quantidade (apenas inteiros).</span>
                </form>

<script>
(function(){
    var maxYens = <?php echo (int)(($dbi['upgrade']+1)*$dbi['valor']); ?>;
    var inp     = document.getElementById('valor_input');
    var dica    = document.getElementById('valor_dica');
    var radios  = document.querySelectorAll('input[name="moeda_tipo"]');

    function moedaSelecionada(){
        for(var i=0;i<radios.length;i++) if(radios[i].checked) return radios[i].value;
        return 'yens';
    }

    // Formata número inteiro com separador de milhar BR ("1234" -> "1.234")
    function formatarBR(n){
        if(!n) return '';
        return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function aplicarMascara(){
        // Remove tudo que não for dígito
        var bruto = (inp.value || '').replace(/\D/g, '');
        if(bruto === ''){ inp.value=''; return; }
        var moeda = moedaSelecionada();
        // Aplica limite de Yens (cristais não têm limite de máximo)
        if(moeda === 'yens' && maxYens > 0){
            var n = parseInt(bruto, 10);
            if(n > maxYens){ bruto = String(maxYens); }
        }
        // Yens: formata com pontos de milhar; Cristais: também formata para facilitar leitura
        inp.value = formatarBR(bruto);
    }

    function atualizarDica(){
        var moeda = moedaSelecionada();
        if(moeda === 'yens'){
            dica.innerHTML = 'Yens podem ter qualquer valor inteiro. Máximo permitido para este item: <b>' + formatarBR(maxYens) + '</b> yens.';
        } else {
            var nomes = {cristal1:'Cristal Refinado', cristal2:'Cristal Bruto', cristal3:'Chakra Forjado'};
            dica.innerHTML = 'Cristais não permitem valores quebrados — informe apenas a quantidade inteira de <b>' + (nomes[moeda] || 'cristais') + '</b>.';
        }
        aplicarMascara(); // re-aplica limite/formatação ao trocar moeda
    }

    inp.addEventListener('input', aplicarMascara);
    inp.addEventListener('blur',  aplicarMascara);
    radios.forEach(function(r){ r.addEventListener('change', atualizarDica); });

    // Antes do submit, garante que o valor enviado não tenha pontos/vírgulas (servidor já sanitiza, mas também limpa aqui)
    var form = inp.form;
    if(form){
        form.addEventListener('submit', function(){
            inp.value = (inp.value || '').replace(/\D/g, '');
        });
    }

    atualizarDica();
})();
</script>
        </td>
</tr>
</table>
</div>
<div class="box_bottom"></div>
