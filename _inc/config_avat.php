
<?php
require_once('Encrypt.php');
$c=new C_Encrypt();

// Garante que sempre haja um personagem válido para renderizar as imagens.
// Prioridade: variável $char_atual já definida → coluna $db['personagem'] → fallback 'naruto'.
if(!isset($char_atual) || $char_atual === ''){
    $char_atual = isset($db['personagem']) ? trim((string)$db['personagem']) : '';
} else {
    $char_atual = trim((string)$char_atual);
}
if($char_atual === '' || !preg_match('/^[a-z0-9_\-]+$/i', $char_atual) || !is_dir(__DIR__ . '/../_img/personagens/' . $char_atual)){
    $char_atual = 'naruto';
}
// Se o personagem atual existe mas não tem avatares reais (ex.: install legado
// criou ADM com 'itachi'), cai no fallback do Naruto para o editor renderizar
// algo utilizável e o ADM conseguir trocar o avatar/personagem.
if(!is_file(__DIR__ . '/../_img/personagens/' . $char_atual . '/1.jpg')){
    $char_atual = 'naruto';
}

if(isset($_POST['fir_avatar'])){
        $avatar=$c->decode($_POST['fir_avatar'],$chaveuniversal);
        vn($avatar);
        
        // Verificar se o avatar é válido (1-9)
        if($avatar < 1 || $avatar > 9) {
                echo "<script>self.location='?p=config&type=avat&msg=3'</script>";
                exit;
        }
        
        try {
                // Atualizar avatar do usuário
                $stmt = $conexao->prepare("UPDATE usuarios SET avatar=? WHERE id=?");
                $result = $stmt->execute([$avatar, $db['id']]);
                
                if($result) {
                        // Atualizar a variável global para refletir a mudança imediatamente
                        $db['avatar'] = $avatar;
                        echo "<script>self.location='?p=config&type=avat&msg=1'</script>";
                } else {
                        echo "<script>self.location='?p=config&type=avat&msg=4'</script>";
                }
        } catch (PDOException $e) {
                error_log("Erro ao alterar avatar: " . $e->getMessage());
                echo "<script>self.location='?p=config&type=avat&msg=4'</script>";
        }
        exit;
}

// Verificar mensagens
if(isset($_GET['msg'])){
        switch($_GET['msg']){
                case 1: $msg='Avatar alterado com sucesso!'; break;
                case 2: $msg='O avatar só pode ser trocado uma vez por dia.'; break;
                case 3: $msg='Avatar inválido selecionado.'; break;
                case 4: $msg='Erro ao alterar avatar. Tente novamente.'; break;
                default: $msg=''; break;
        }
        if($msg) {
                echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
        }
}
?>
<form method="post" action="?p=config&amp;type=avat" onsubmit="fir_submit.value='Carregando...';fir_submit.disabled=true;">
<fieldset><legend>Alterar Avatar</legend>
<div align="center">
<?php
// Renderiza o grid de avatares 3x3, mas pula células cujo .jpg não existe.
// Assim personagens incompletos (ex.: Zabuza tem só 0-7.jpg) não exibem
// imagens quebradas e o ADM consegue ir adicionando avatares aos poucos.
$avatares_existentes = [];
for ($i = 1; $i <= 9; $i++) {
    if (file_exists('_img/personagens/'.$char_atual.'/'.$i.'.jpg')) {
        $avatares_existentes[] = $i;
    }
}
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<?php
$cols = 3;
$total = count($avatares_existentes);
for ($linha_idx = 0; $linha_idx < $total; $linha_idx += $cols):
?>
  <tr>
    <?php for ($k = 0; $k < $cols; $k++):
        $pos = $linha_idx + $k;
        if ($pos >= $total) {
            echo '<td width="150" bgcolor="#222222">&nbsp;</td>';
            continue;
        }
        $ai = $avatares_existentes[$pos];
    ?>
        <td width="150" align="center" bgcolor="#444444">
            <img src="_img/personagens/<?php echo $char_atual; ?>/<?php echo $ai; ?>.jpg" width="130" height="120" onclick="document.getElementById('fir_avatar<?php echo $ai; ?>').checked=true" />
        </td>
    <?php endfor; ?>
  </tr>
  <tr>
    <?php for ($k = 0; $k < $cols; $k++):
        $pos = $linha_idx + $k;
        if ($pos >= $total) { echo '<td>&nbsp;</td>'; continue; }
        $ai = $avatares_existentes[$pos];
    ?>
        <td align="center"><input type="radio" id="fir_avatar<?php echo $ai; ?>" name="fir_avatar" value="<?php echo $c->encode((string)$ai,$chaveuniversal); ?>" <?php if((int)$db['avatar']===$ai) echo ' checked="checked"'; ?>/></td>
    <?php endfor; ?>
  </tr>
  <tr><td colspan="<?php echo $cols; ?>" align="center"><div class="sep"></div></td></tr>
<?php endfor; ?>
<?php if ($total === 0): ?>
  <tr><td align="center"><div class="aviso">Nenhum avatar disponível para este personagem.<br/><span class="sub2">Peça para o ADM adicionar imagens em <code>_img/personagens/<?php echo htmlspecialchars($char_atual); ?>/</code>.</span></div></td></tr>
<?php endif; ?>
</table>
<div class="sep"></div>
<input type="submit" id="fir_submit" name="fir_submit" class="botao" value="Alterar Avatar" />
</div>
</fieldset>
</form>
