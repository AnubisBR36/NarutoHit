<?php
if (!defined('INDEX')) die('Acesso negado');

$mensagem_manutencao = obter_mensagem_manutencao();
$recaptcha_configured = recaptcha_configurado();
?>

<div class="box_top">⚙️ Manutenção</div>
<div class="box_middle">
    <div style="text-align: center; padding: 50px 20px;">
        <?php
        $imagens_manutencao = ['_img/Manutenção/manutenção.png', '_img/Manutenção/manutenção2.png', '_img/Manutenção/manutenção3.png'];
        $imagem_atual = $imagens_manutencao[array_rand($imagens_manutencao)];
        ?>
        <img src="<?php echo $imagem_atual; ?>" alt="Sistema em Manutenção" style="max-width: 100%; height: auto; display: block; margin: 0 auto 20px auto;">
        
        <div class="sep"></div>
        
        <div style="font-size: 18px; line-height: 1.6; max-width: 600px; margin: 0 auto; padding: 20px; background: rgba(0,0,0,0.3); border-radius: 10px;">
            <?php echo nl2br(htmlspecialchars($mensagem_manutencao)); ?>
        </div>
        
        <div class="sep"></div>
        <div class="sep"></div>
        
        <a href="?p=login" style="display: inline-block; background: url('_img/fundo_botao.jpg') center/cover no-repeat; color: white; padding: 12px 30px; border-radius: 5px; font-weight: bold; text-decoration: none; box-shadow: 0 4px 6px rgba(0,0,0,0.3); text-shadow: 1px 1px 2px rgba(0,0,0,0.8);">← Voltar para Login</a>
    </div>
</div>
<div class="box_bottom"></div>
