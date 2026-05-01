
<?php
require_once('Encrypt.php');
$c=new C_Encrypt();

// Garante que sempre haja um personagem válido para renderizar as imagens
$char_atual = isset($db['personagem']) ? trim((string)$db['personagem']) : '';
if($char_atual === '' || !preg_match('/^[a-z0-9_\-]+$/i', $char_atual)){
    $char_atual = 'naruto';
}
// Mesmo guard do config_avat: se o personagem atual não tem avatares reais
// (só placeholder 0.jpg), cai para naruto pra renderizar algo escolhível.
if(!is_file(__DIR__ . '/../_img/personagens/' . $char_atual . '/1.jpg')){
    $char_atual = 'naruto';
}
// Verifica se a pasta do personagem existe; senão usa naruto como fallback
if(!is_dir(__DIR__ . '/../_img/personagens/' . $char_atual)){
    $char_atual = 'naruto';
}

if(isset($_POST['fir_avatar'])){
        $avatar=$c->decode($_POST['fir_avatar'],$chaveuniversal);
        vn($avatar);
        
        try {
                // Atualizar avatar do usuário
                $stmt = $conexao->prepare("UPDATE usuarios SET avatar = ? WHERE id = ?");
                $stmt->execute([$avatar, $db['id']]);
                
                // Corrigir nome de usuário removendo espaços
                $novo = str_replace(' ','_',$db['usuario']);
                if($db['usuario'] != $novo) {
                        $stmt_update = $conexao->prepare("UPDATE usuarios SET usuario = ? WHERE id = ?");
                        $stmt_update->execute([$novo, $db['id']]);
                }
                
                echo "<script>self.location='?p=home'</script>";
                exit;
                
        } catch (PDOException $e) {
                error_log("Erro no first.php: " . $e->getMessage());
                echo "<script>alert('Erro ao salvar avatar. Tente novamente.'); self.location='?p=first';</script>";
        }
}
?>
<div class="box_top">Primeiro Login</div>
<div class="box_middle">Seja bem-vindo ao <?php echo nome_servidor(); ?>! Como este é seu primeiro login no jogo, queremos que você escolha um avatar para representação. Marque uma das 9 opções abaixo, e clique no botão para confirmar. Lembramos que esta função não interfere na força da sua conta. A troca de avatares pode ser feita uma vez por dia (ilimitado para jogadores VIP).<div class="sep"></div>
        <form method="post" action="?p=first" onsubmit="subm.value='Carregando...';subm.disabled=true;">
        <fieldset><legend>Avatar</legend>
    <div align="center">
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="150" align="center" bgcolor="#444444"><img src="_img/personagens/<?php echo $char_atual; ?>/1.jpg" width="130" height="120" onclick="document.getElementById('fir_avatar1').checked=true" /></td>
        <td width="150" align="center" bgcolor="#444444"><img src="_img/personagens/<?php echo $char_atual; ?>/2.jpg" width="130" height="120" onclick="document.getElementById('fir_avatar2').checked=true" /></td>
        <td width="150" align="center" bgcolor="#444444"><img src="_img/personagens/<?php echo $char_atual; ?>/3.jpg" width="130" height="120" onclick="document.getElementById('fir_avatar3').checked=true" /></td>
      </tr>
      <tr>
        <td align="center"><input type="radio" id="fir_avatar1" name="fir_avatar" value="<?php echo $c->encode('1',$chaveuniversal); ?>" checked="checked" /></td>
        <td align="center"><input type="radio" id="fir_avatar2" name="fir_avatar" value="<?php echo $c->encode('2',$chaveuniversal); ?>" /></td>
        <td align="center"><input type="radio" id="fir_avatar3" name="fir_avatar" value="<?php echo $c->encode('3',$chaveuniversal); ?>" /></td>
      </tr>
      <tr>
        <td colspan="3" align="center"><div class="sep"></div></td>
        </tr>
      <tr>
        <td align="center" bgcolor="#444444"><img src="_img/personagens/<?php echo $char_atual; ?>/4.jpg" width="130" height="120" onclick="document.getElementById('fir_avatar4').checked=true" /></td>
        <td align="center" bgcolor="#444444"><img src="_img/personagens/<?php echo $char_atual; ?>/5.jpg" width="130" height="120" onclick="document.getElementById('fir_avatar5').checked=true" /></td>
        <td align="center" bgcolor="#444444"><img src="_img/personagens/<?php echo $char_atual; ?>/6.jpg" width="130" height="120" onclick="document.getElementById('fir_avatar6').checked=true" /></td>
      </tr>
      <tr>
        <td align="center"><input type="radio" id="fir_avatar4" name="fir_avatar" value="<?php echo $c->encode('4',$chaveuniversal); ?>" /></td>
        <td align="center"><input type="radio" id="fir_avatar5" name="fir_avatar" value="<?php echo $c->encode('5',$chaveuniversal); ?>" /></td>
        <td align="center"><input type="radio" id="fir_avatar6" name="fir_avatar" value="<?php echo $c->encode('6',$chaveuniversal); ?>" /></td>
      </tr>
      <tr>
        <td colspan="3" align="center"><div class="sep"></div></td>
        </tr>
      <tr>
        <td align="center" bgcolor="#444444"><img src="_img/personagens/<?php echo $char_atual; ?>/7.jpg" width="130" height="120" onclick="document.getElementById('fir_avatar7').checked=true" /></td>
        <td align="center" bgcolor="#444444"><img src="_img/personagens/<?php echo $char_atual; ?>/8.jpg" width="130" height="120" onclick="document.getElementById('fir_avatar8').checked=true" /></td>
        <td align="center" bgcolor="#444444"><img src="_img/personagens/<?php echo $char_atual; ?>/9.jpg" width="130" height="120" onclick="document.getElementById('fir_avatar9').checked=true" /></td>
      </tr>
      <tr>
        <td align="center"><input type="radio" id="fir_avatar7" name="fir_avatar" value="<?php echo $c->encode('7',$chaveuniversal); ?>" /></td>
        <td align="center"><input type="radio" id="fir_avatar8" name="fir_avatar" value="<?php echo $c->encode('8',$chaveuniversal); ?>" /></td>
        <td align="center"><input type="radio" id="fir_avatar9" name="fir_avatar" value="<?php echo $c->encode('9',$chaveuniversal); ?>" /></td>
      </tr>
    </table>
    <div class="sep"></div>
    <input type="submit" id="subm" name="subm" class="botao" value="Confirmar" />
    </div>
    </fieldset>
    </form>
</div>
<div class="box_bottom"></div>
