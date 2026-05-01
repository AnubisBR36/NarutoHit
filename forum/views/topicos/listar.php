<?php
// Obter informações da vila do usuário
$vila_info = ForumSecurityHelper::getVilaInfo($usuario_vila, $user_data['renegado'] ?? 'nao');
$avatar_url = ForumSecurityHelper::getUserAvatar($user_data);

// Função para obter a cor da vila
function getCorVilaListar($nome_vila) {
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

$cor_vila_player = getCorVilaListar($vila_info['nome']);
?>

<!-- Box Principal com Info do Player -->
<div class="box_top">Fórum - <?php echo htmlspecialchars($categoria['nome']); ?></div>
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
            <a href="?p=forum" class="forum-btn forum-btn-secondary">← Voltar às Categorias</a>
            <a href="?p=forum_criar_topico&categoria=<?php echo $categoria['id']; ?>" class="forum-btn">➕ Novo Tópico</a>
        </div>
    </div>
    
    <div class="sep"></div>
    
    <div style="color: #aaa; font-size: 12px;">
        <?php echo htmlspecialchars($categoria['descricao']); ?>
    </div>
</div>
<div class="box_bottom"></div>

<!-- Lista de Tópicos -->
<div style="margin-top: 15px;">
    <div class="box2_top">Tópicos</div>
    <div class="box2_middle">
        <?php if (!empty($topicos)): ?>
            <?php foreach ($topicos as $topico): ?>
                <?php 
                $foi_lido = in_array($topico['id'], $topicos_lidos);
                $classe_lido = $foi_lido ? 'lido' : '';
                ?>
                <div class="forum-topico <?php echo $topico['fixado'] ? 'forum-topico-fixado' : $classe_lido; ?>">
                    <div class="forum-topico-titulo">
                        <?php if ($topico['fixado']): ?>
                            📌
                        <?php endif; ?>
                        <?php if ($topico['fechado']): ?>
                            🔒
                        <?php endif; ?>
                        <a href="?p=forum_topico&id=<?php echo $topico['id']; ?>" style="color: #fff; text-decoration: none;">
                            <?php echo ForumSecurityHelper::sanitizeOutput($topico['titulo']); ?>
                        </a>
                        <?php if (!$foi_lido && !$topico['fixado']): ?>
                            <span style="background:#ff6600; color:#fff; padding:1px 5px; font-size:9px; margin-left:5px;">NOVO</span>
                        <?php endif; ?>
                    </div>
                    <div class="forum-topico-info">
                        Por <strong><?php echo htmlspecialchars($topico['autor_nome']); ?></strong> • 
                        <?php echo date('d/m/Y H:i', strtotime($topico['criado_em'])); ?> • 
                        <strong><?php echo ($topico['total_respostas'] - 1); ?></strong> respostas •
                        <strong><?php echo $topico['visualizacoes']; ?></strong> visualizações
                        <?php if ($topico['ultima_resposta']): ?>
                             • Última: <?php echo date('d/m/Y H:i', strtotime($topico['ultima_resposta'])); ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if ($total_paginas > 1): ?>
                <div class="forum-paginacao">
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <a href="?p=forum_topicos&categoria=<?php echo $categoria['id']; ?>&pagina=<?php echo $i; ?>" 
                           class="<?php echo $page == $i ? 'ativo' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #888;">
                Nenhum tópico nesta categoria ainda. Seja o primeiro a criar!
            </div>
        <?php endif; ?>
    </div>
    <div class="box2_bottom"></div>
</div>

<!-- Legenda -->
<div style="margin-top: 10px;">
    <div class="box_top">Legenda</div>
    <div class="box_middle">
        <div style="display:flex; gap:20px; font-size:12px;">
            <div><span style="display:inline-block; width:15px; height:15px; border:2px solid #ff6600; background:#1a1a1a; vertical-align:middle; margin-right:5px;"></span> Tópico Novo</div>
            <div><span style="display:inline-block; width:15px; height:15px; border:2px solid #cc0000; background:#1a1a1a; vertical-align:middle; margin-right:5px;"></span> Tópico Lido</div>
            <div><span style="display:inline-block; width:15px; height:15px; border:2px solid #ffd700; background:#2d2d1a; vertical-align:middle; margin-right:5px;"></span> Tópico Fixado</div>
        </div>
    </div>
    <div class="box_bottom"></div>
</div>
