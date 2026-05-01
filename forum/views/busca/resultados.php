<div style="margin-bottom: 20px;">
    <a href="?p=forum" class="forum-btn forum-btn-secondary">← Voltar ao Fórum</a>
    <h2 style="color: #ff6600; display: inline-block; margin-left: 20px;">
        Resultados da Busca
    </h2>
</div>

<!-- Busca -->
<div class="forum-search">
    <form action="?p=forum_busca" method="GET">
        <input type="hidden" name="p" value="forum_busca">
        <input type="text" name="q" value="<?php echo htmlspecialchars($termo); ?>" placeholder="Buscar tópicos..." required>
        <button type="submit">🔍 Buscar</button>
    </form>
</div>

<?php if (!empty($termo)): ?>
    <?php if (!empty($topicos)): ?>
        <div style="margin-bottom: 15px; color: #aaa;">
            Resultados para: <strong style="color: #ff6600;"><?php echo htmlspecialchars($termo); ?></strong>
        </div>
        
        <?php foreach ($topicos as $topico): ?>
            <div class="forum-topico">
                <div class="forum-topico-titulo">
                    <a href="?p=forum_topico&id=<?php echo $topico['id']; ?>" style="color: #fff; text-decoration: none;">
                        <?php echo htmlspecialchars($topico['titulo']); ?>
                    </a>
                </div>
                <div class="forum-topico-info">
                    Categoria: <strong><?php echo htmlspecialchars($topico['categoria_nome']); ?></strong> • 
                    Por <strong><?php echo htmlspecialchars($topico['autor_nome']); ?></strong> • 
                    <?php echo ($topico['total_respostas'] - 1); ?> respostas
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 40px; color: #888;">
            Nenhum resultado encontrado para "<?php echo htmlspecialchars($termo); ?>"
        </div>
    <?php endif; ?>
<?php else: ?>
    <div style="text-align: center; padding: 40px; color: #888;">
        Digite um termo para buscar tópicos
    </div>
<?php endif; ?>
