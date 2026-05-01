<?php
// Obter informações da vila do usuário
$vila_info = ForumSecurityHelper::getVilaInfo($usuario_vila, $user_data['renegado'] ?? 'nao');
$avatar_url = ForumSecurityHelper::getUserAvatar($user_data);

// Função para obter a cor da categoria baseada no nome
function getCorCategoria($nome_categoria) {
    $cores = [
        'Vila Neutra' => '#bbbbbb',
        'Fórum da Vila da Folha' => '#2ecc71',
        'Fórum da Vila da Areia' => '#daa520',
        'Fórum da Vila do Som' => '#9b59b6',
        'Fórum da Vila da Chuva' => '#1e3a8a',
        'Fórum da Vila da Nuvem' => '#87ceeb',
        'Fórum da Vila da Névoa' => '#5f9ea0',
        'Fórum da Vila da Pedra' => '#8b7355',
        'Fórum da Akatsuki' => '#8b0000'
    ];
    
    return $cores[$nome_categoria] ?? '#ff6600';
}

// Função para obter a cor da vila do usuário
function getCorVila($nome_vila) {
    $cores_vila = [
        'Vila Neutra' => '#bbbbbb',
        'Vila da Folha' => '#2ecc71',
        'Vila da Areia' => '#daa520',
        'Vila do Som' => '#9b59b6',
        'Vila da Chuva' => '#1e3a8a',
        'Vila da Nuvem' => '#87ceeb',
        'Vila da Névoa' => '#5f9ea0',
        'Vila da Pedra' => '#8b7355',
        'Akatsuki' => '#8b0000'
    ];
    
    return $cores_vila[$nome_vila] ?? '#aaa';
}

// Obter cor da vila do player
$cor_vila_player = getCorVila($vila_info['nome']);
?>

<!-- Box Principal com Info do Player e Busca -->
<div class="box_top">Fórum <?php echo nome_servidor(); ?></div>
<div class="box_middle">
    <div class="player-info-header">
        <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="Avatar" class="player-avatar">
        <div class="player-details">
            <div class="player-name">
                <?php if ($is_admin): ?>
                    <span class="admin-badge">ADMIN</span>
                <?php endif; ?>
                <?php echo htmlspecialchars($user_data['usuario']); ?>
            </div>
            <div class="player-vila">
                <img src="<?php echo htmlspecialchars($vila_info['imagem']); ?>" alt="<?php echo htmlspecialchars($vila_info['nome']); ?>">
                <span style="color: <?php echo $cor_vila_player; ?>; font-weight: bold; text-shadow: 1px 1px 2px #000;">
                    <?php echo htmlspecialchars($vila_info['nome']); ?>
                </span>
            </div>
        </div>
        <div>
            <a href="?p=home" class="forum-btn forum-btn-secondary">← Voltar ao Jogo</a>
        </div>
    </div>
    
    <div class="sep"></div>
    
    <!-- Busca -->
    <div class="forum-search">
        <form action="?p=forum_busca" method="GET" style="display:flex; width:100%; gap:10px;">
            <input type="hidden" name="p" value="forum_busca">
            <input type="text" name="q" placeholder="Buscar tópicos..." required style="flex:1;">
            <button type="submit">🔍 Buscar</button>
        </form>
    </div>
</div>
<div class="box_bottom"></div>

<!-- Categorias/Vilas -->
<?php foreach ($categorias as $cat): ?>
    <?php
    $pode_acessar = $is_admin || $cat['vila_id'] == 0 || $cat['vila_id'] == $usuario_vila;
    $cor_categoria = getCorCategoria($cat['nome']);
    ?>
    
    <div style="margin-top: 15px; <?php echo $pode_acessar ? '' : 'opacity: 0.5;'; ?>">
        <div class="box2_top" style="color: <?php echo $cor_categoria; ?>;">
            <?php echo htmlspecialchars($cat['nome']); ?>
            <?php if (!$pode_acessar): ?>
                <span style="color: #888; font-size: 11px;">🔒 Bloqueado</span>
            <?php endif; ?>
        </div>
        <div class="box2_middle">
            <div class="forum-categoria-header" style="<?php echo $pode_acessar ? 'cursor: pointer;' : ''; ?>" 
                 onclick="<?php echo $pode_acessar ? "location.href='?p=forum_topicos&categoria={$cat['id']}'" : ''; ?>">
                <img src="_img/forum/<?php echo htmlspecialchars($cat['imagem']); ?>" 
                     alt="<?php echo htmlspecialchars($cat['nome']); ?>" 
                     class="forum-categoria-icon">
                <div class="forum-categoria-info">
                    <div class="forum-categoria-desc">
                        <?php echo htmlspecialchars($cat['descricao']); ?>
                    </div>
                </div>
                <div class="forum-categoria-stats">
                    <div><strong><?php echo $cat['total_topicos'] ?? 0; ?></strong> Tópicos</div>
                    <div><strong><?php echo $cat['total_postagens'] ?? 0; ?></strong> Postagens</div>
                    <?php if ($cat['ultima_atividade']): ?>
                        <div style="font-size: 10px; color: #666;">
                            Última: <?php echo date('d/m/Y H:i', strtotime($cat['ultima_atividade'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="box2_bottom"></div>
    </div>
<?php endforeach; ?>

<?php if (empty($categorias)): ?>
    <div class="box_top">Aviso</div>
    <div class="box_middle" style="text-align: center; padding: 40px; color: #888;">
        Nenhuma categoria encontrada.
    </div>
    <div class="box_bottom"></div>
<?php endif; ?>

<!-- Informações -->
<div style="margin-top: 15px;">
    <div class="box_top">ℹ️ Informações</div>
    <div class="box_middle">
        <ul style="color: #aaa; line-height: 1.8; margin-left: 20px;">
            <li><strong>Vila Neutra</strong> - Área pública acessível para todos os jogadores</li>
            <li>Você só pode acessar o fórum da sua vila</li>
            <li>Ninjas renegados (Akatsuki) têm acesso ao fórum exclusivo da Akatsuki</li>
            <li>Administradores podem acessar todos os fóruns</li>
            <li>Seja respeitoso e siga as regras do jogo</li>
        </ul>
    </div>
    <div class="box_bottom"></div>
</div>
