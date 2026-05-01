<?php
// Obter informações do player
$user_data = ForumSecurityHelper::getUserData();
$usuario_vila = ForumSecurityHelper::getUserVila();
$is_admin = ForumSecurityHelper::isAdmin();
$vila_info = ForumSecurityHelper::getVilaInfo($usuario_vila, $user_data['renegado'] ?? 'nao');
$avatar_url = ForumSecurityHelper::getUserAvatar($user_data);

function getCorVilaCriar($nome_vila) {
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

$cor_vila_player = getCorVilaCriar($vila_info['nome']);
?>

<!-- Box Principal com Info do Player -->
<div class="box_top">Novo Tópico - <?php echo htmlspecialchars($categoria['nome']); ?></div>
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
            <a href="?p=forum_topicos&categoria=<?php echo $categoria['id']; ?>" class="forum-btn forum-btn-secondary">← Voltar</a>
        </div>
    </div>
</div>
<div class="box_bottom"></div>

<!-- Formulário -->
<div style="margin-top: 15px;">
    <div class="box2_top">Criar Tópico</div>
    <div class="box2_middle">
        <form method="POST" action="?p=forum_criar_topico" class="forum-form">
            <input type="hidden" name="categoria_id" value="<?php echo $categoria['id']; ?>">
            
            <div style="margin-bottom: 15px;">
                <label style="color: #ff6600; font-weight: bold; display: block; margin-bottom: 5px;">Título do Tópico:</label>
                <input type="text" name="titulo" required maxlength="255" placeholder="Digite um título claro e descritivo">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="color: #ff6600; font-weight: bold; display: block; margin-bottom: 5px;">Conteúdo:</label>
                <textarea name="conteudo" required placeholder="Escreva o conteúdo do seu tópico..."></textarea>
            </div>
            
            <button type="submit" class="forum-btn">✓ Criar Tópico</button>
            <a href="?p=forum_topicos&categoria=<?php echo $categoria['id']; ?>" class="forum-btn forum-btn-secondary">Cancelar</a>
        </form>
    </div>
    <div class="box2_bottom"></div>
</div>

<!-- Dicas -->
<div style="margin-top: 15px;">
    <div class="box_top">Dicas</div>
    <div class="box_middle">
        <ul style="color: #aaa; font-size: 12px; margin-left: 20px; line-height: 1.8;">
            <li>Seja claro e objetivo no título</li>
            <li>Descreva bem o assunto no conteúdo</li>
            <li>Respeite as regras do fórum e do jogo</li>
        </ul>
    </div>
    <div class="box_bottom"></div>
</div>
