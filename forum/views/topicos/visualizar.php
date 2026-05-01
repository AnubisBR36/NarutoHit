<?php
// Obter informações da vila do usuário logado
$vila_info_player = ForumSecurityHelper::getVilaInfo($usuario_vila, $user_data['renegado'] ?? 'nao');
$avatar_url_player = ForumSecurityHelper::getUserAvatar($user_data);

// Função para obter a cor da vila
function getCorVilaVisualizar($nome_vila) {
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

$cor_vila_player = getCorVilaVisualizar($vila_info_player['nome']);
?>

<!-- Box Principal com Info do Player -->
<div class="box_top"><?php echo ForumSecurityHelper::sanitizeOutput($topico['titulo']); ?></div>
<div class="box_middle">
    <div class="player-info-header">
        <img src="<?php echo htmlspecialchars($avatar_url_player); ?>" alt="Avatar" class="player-avatar">
        <div class="player-details">
            <div class="player-name">
                <?php if ($is_admin): ?>
                    <span class="admin-badge">ADMIN</span>
                <?php endif; ?>
                <?php echo htmlspecialchars($user_data['usuario']); ?>
            </div>
            <div class="player-vila">
                <img src="<?php echo htmlspecialchars($vila_info_player['imagem']); ?>" alt="<?php echo htmlspecialchars($vila_info_player['nome']); ?>">
                <span style="color: <?php echo $cor_vila_player; ?>; font-weight: bold; text-shadow: 1px 1px 2px #000;">
                    <?php echo htmlspecialchars($vila_info_player['nome']); ?>
                </span>
            </div>
        </div>
        <div>
            <a href="?p=forum_topicos&categoria=<?php echo $topico['categoria_id']; ?>" class="forum-btn forum-btn-secondary">← Voltar</a>
        </div>
    </div>
    
    <div class="sep"></div>
    
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div style="color: #888; font-size: 12px;">
            <?php if ($topico['fixado']): ?>📌 Fixado <?php endif; ?>
            <?php if ($topico['fechado']): ?>🔒 Fechado <?php endif; ?>
            <?php echo $topico['visualizacoes']; ?> visualizações • 
            Criado em <?php echo date('d/m/Y H:i', strtotime($topico['criado_em'])); ?>
        </div>
        
        <?php if ($is_admin): ?>
            <div>
                <?php if ($topico['fixado']): ?>
                    <a href="?p=forum_fixar&id=<?php echo $topico['id']; ?>&fixar=0" class="forum-btn forum-btn-secondary" style="font-size:11px; padding:5px 10px;">📌 Desafixar</a>
                <?php else: ?>
                    <a href="?p=forum_fixar&id=<?php echo $topico['id']; ?>&fixar=1" class="forum-btn" style="font-size:11px; padding:5px 10px;">📌 Fixar</a>
                <?php endif; ?>
                
                <?php if ($topico['fechado']): ?>
                    <a href="?p=forum_fechar&id=<?php echo $topico['id']; ?>&fechar=0" class="forum-btn" style="font-size:11px; padding:5px 10px;">🔓 Abrir</a>
                <?php else: ?>
                    <a href="?p=forum_fechar&id=<?php echo $topico['id']; ?>&fechar=1" class="forum-btn forum-btn-secondary" style="font-size:11px; padding:5px 10px;">🔒 Fechar</a>
                <?php endif; ?>
                
                <a href="?p=forum_deletar_topico&id=<?php echo $topico['id']; ?>" 
                   onclick="return confirm('Tem certeza que deseja deletar este tópico?')" 
                   class="forum-btn" style="background: #c00; font-size:11px; padding:5px 10px;">🗑️ Deletar</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<div class="box_bottom"></div>

<!-- Postagens -->
<div style="margin-top: 15px;">
    <div class="box2_top">Postagens</div>
    <div class="box2_middle">
        <?php foreach ($postagens as $postagem): ?>
            <?php
            $post_user_data = [
                'personagem' => $postagem['personagem'] ?? '',
                'avatar' => $postagem['avatar'] ?? 0,
                'vila' => $postagem['vila'] ?? 0,
                'renegado' => $postagem['renegado'] ?? 'nao'
            ];
            
            $avatar_path = ForumSecurityHelper::getUserAvatar($post_user_data);
            $vila_info = ForumSecurityHelper::getVilaInfo($post_user_data['vila'], $post_user_data['renegado']);
            $reacoes = $reacaoModel->contarReacoes($postagem['id']);
            $reacao_usuario = $reacaoModel->getReacaoUsuario($postagem['id'], $usuario_id);
            ?>
            <div class="forum-postagem">
                <div class="forum-postagem-autor">
                    <img src="<?php echo $avatar_path; ?>" class="forum-postagem-avatar" alt="Avatar">
                    <div style="font-weight: bold; color: #ff6600; margin-bottom: 5px; font-size:12px;">
                        <?php echo htmlspecialchars($postagem['usuario'] ?? 'Usuário Desconhecido'); ?>
                    </div>
                    <?php
                    $adm_val = $postagem['adm'] ?? '';
                    if ($adm_val == 1 || $adm_val == 2): ?>
                        <div style="margin-bottom: 5px;">
                            <span style="display:inline-block; background:#8b0000; color:#ffd700; font-size:10px; font-weight:bold; padding:2px 7px; border-radius:3px; border:1px solid #ffd700; letter-spacing:1px; text-shadow:0 0 4px #ffd700;">ADM</span>
                        </div>
                    <?php elseif ($adm_val === 'sim'): ?>
                        <div style="margin-bottom: 5px;">
                            <span style="display:inline-block; background:#1a3a6e; color:#7ec8e3; font-size:10px; font-weight:bold; padding:2px 7px; border-radius:3px; border:1px solid #7ec8e3; letter-spacing:1px; text-shadow:0 0 4px #7ec8e3;">GM</span>
                        </div>
                    <?php endif; ?>
                    <?php if ($vila_info): ?>
                        <div style="margin-bottom: 5px;">
                            <img src="<?php echo $vila_info['imagem']; ?>" alt="<?php echo $vila_info['nome']; ?>" style="width: 22px; height: 22px; vertical-align: middle;">
                            <span style="font-size: 10px; color: #aaa; margin-left: 3px;"><?php echo $vila_info['nome']; ?></span>
                        </div>
                    <?php endif; ?>
                    <div style="font-size: 10px; color: #888;">
                        Membro desde<br>
                        <?php echo isset($postagem['data_registro']) ? date('d/m/Y', strtotime($postagem['data_registro'])) : 'N/A'; ?>
                    </div>
                </div>
                
                <div class="forum-postagem-conteudo">
                    <div style="color: #fff; line-height: 1.6; margin-bottom: 10px; word-wrap: break-word; font-size:13px;">
                        <?php echo nl2br(ForumSecurityHelper::sanitizeOutput($postagem['conteudo'])); ?>
                    </div>
                    
                    <?php if ($postagem['editado']): ?>
                        <div style="color: #666; font-size: 10px; font-style: italic; margin-bottom: 8px;">
                            Editado em <?php echo date('d/m/Y H:i', strtotime($postagem['editado_em'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div style="border-top: 1px solid #444; padding-top: 8px; margin-top: 8px;">
                        <div style="display: inline-block; margin-right: 10px;">
                            <?php 
                            $reacoes_emojis = [
                                'coracao' => '❤️',
                                'rindo' => '😂',
                                'triste' => '😢',
                                'bravo' => '😠',
                                'surpreso' => '😮'
                            ];
                            
                            foreach ($reacoes_emojis as $tipo => $emoji):
                                $count = $reacoes[$tipo] ?? 0;
                                $ativo = ($reacao_usuario === $tipo) ? 'ativo' : '';
                            ?>
                                <button class="forum-reacao-btn <?php echo $ativo; ?>" 
                                        data-postagem="<?php echo $postagem['id']; ?>"
                                        data-tipo="<?php echo $tipo; ?>"
                                        onclick="reagir(<?php echo $postagem['id']; ?>, '<?php echo $tipo; ?>')">
                                    <?php echo $emoji; ?> 
                                    <span class="reacao-count" id="reacao-<?php echo $tipo; ?>-<?php echo $postagem['id']; ?>"><?php echo $count > 0 ? $count : ''; ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if ($postagem['usuario_id'] == $usuario_id || $is_admin): ?>
                            <a href="?p=forum_deletar_postagem&id=<?php echo $postagem['id']; ?>" 
                               onclick="return confirm('Tem certeza que deseja deletar esta postagem?')" 
                               style="color: #c00; margin-left: 10px; font-size: 11px;">🗑️ Deletar</a>
                        <?php endif; ?>
                        
                        <div style="float: right; color: #888; font-size: 10px;">
                            #<?php echo $postagem['id']; ?> • <?php echo date('d/m/Y H:i', strtotime($postagem['criado_em'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if ($total_paginas > 1): ?>
            <div class="forum-paginacao">
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="?p=forum_topico&id=<?php echo $topico['id']; ?>&pagina=<?php echo $i; ?>" 
                       class="<?php echo $page == $i ? 'ativo' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="box2_bottom"></div>
</div>

<!-- Formulário de Resposta -->
<?php if (!$topico['fechado'] || $is_admin): ?>
    <div style="margin-top: 15px;">
        <div class="box_top">✍️ Responder</div>
        <div class="box_middle">
            <form method="POST" action="?p=forum_criar_postagem" class="forum-form">
                <input type="hidden" name="topico_id" value="<?php echo $topico['id']; ?>">
                <textarea name="conteudo" required placeholder="Escreva sua resposta..."></textarea>
                <button type="submit" class="forum-btn" style="margin-top: 10px;">✓ Enviar Resposta</button>
            </form>
        </div>
        <div class="box_bottom"></div>
    </div>
<?php else: ?>
    <div style="margin-top: 15px;">
        <div class="box_top">🔒 Tópico Fechado</div>
        <div class="box_middle" style="text-align: center; color: #888;">
            Este tópico está fechado. Apenas administradores podem responder.
        </div>
        <div class="box_bottom"></div>
    </div>
<?php endif; ?>

<script>
function reagir(postagem_id, tipo) {
    fetch('?p=forum_reacao', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'postagem_id=' + postagem_id + '&tipo=' + tipo
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const tipos = ['coracao', 'rindo', 'triste', 'bravo', 'surpreso'];
            tipos.forEach(t => {
                const countElem = document.getElementById('reacao-' + t + '-' + postagem_id);
                const btn = document.querySelector(`button[data-postagem="${postagem_id}"][data-tipo="${t}"]`);
                
                if (countElem && btn) {
                    const count = data.reacoes[t] || 0;
                    countElem.textContent = count > 0 ? count : '';
                    btn.classList.remove('ativo');
                }
            });
            
            if (data.acao === 'adicionada') {
                const activeBtn = document.querySelector(`button[data-postagem="${postagem_id}"][data-tipo="${tipo}"]`);
                if (activeBtn) {
                    activeBtn.classList.add('ativo');
                }
            }
        }
    })
    .catch(error => console.error('Erro:', error));
}
</script>
