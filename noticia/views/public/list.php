<!-- Exibição Pública de Notícias -->
<?php 
require_once __DIR__ . '/../../helpers/SecurityHelper.php';
require_once __DIR__ . '/../../helpers/ColorHelper.php';
?>
<div class="box_top">Notícias do Jogo</div>
<div class="box_middle">
    <div style="padding: 10px;">
        
        <?php if (empty($noticias)): ?>
            <p style="text-align: center; color: #999; padding: 20px;">
                Nenhuma notícia disponível no momento.
            </p>
        <?php else: ?>
            <?php foreach ($noticias as $noticia): ?>
                <div id="noticia-<?php echo $noticia['id']; ?>" style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #555; scroll-margin-top: 20px;">
                    <h3 style="margin: 0 0 5px 0; color: #ffcc00;">
                        <?php 
                        if ($noticia['usar_cores']) {
                            echo ColorHelper::renderFormatting($noticia['titulo']);
                        } else {
                            echo htmlspecialchars($noticia['titulo']);
                        }
                        ?>
                    </h3>
                    <div style="font-size: 11px; color: #999; margin-bottom: 10px;">
                        Por <strong><?php echo htmlspecialchars($noticia['autor']); ?></strong> 
                        em <?php echo date('d/m/Y às H:i', strtotime($noticia['data_criacao'])); ?>
                    </div>
                    <div style="line-height: 1.6; color: #ddd;">
                        <?php 
                        if ($noticia['usar_cores']) {
                            echo ColorHelper::renderFormatting($noticia['conteudo']);
                        } else {
                            echo nl2br(SecurityHelper::sanitizeHtml($noticia['conteudo']));
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if ($page < $totalPages): ?>
                <div style="margin-top: 20px; text-align: center; padding: 15px; background: #222; border-radius: 5px;">
                    <a href="?p=news&page=<?php echo $page + 1; ?>" 
                       style="background: #ffcc00; color: #000; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;">
                        📰 Ver Notícias Antigas
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if ($page > 1): ?>
                <div style="margin-top: 10px; text-align: center;">
                    <a href="?p=news&page=<?php echo $page - 1; ?>" style="color: #ffcc00; text-decoration: none;">
                        ← Voltar para Notícias Recentes
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<div class="box_bottom"></div>
