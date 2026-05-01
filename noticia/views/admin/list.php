<?php require_once __DIR__ . '/../../helpers/SecurityHelper.php'; ?>
<!-- Lista de Notícias - Admin -->
<div style="background: #2a2a2a; border: 1px solid #444; border-radius: 5px; margin-bottom: 10px;">
    <div style="background: #333; padding: 10px; border-bottom: 1px solid #444; border-radius: 5px 5px 0 0;">
        <strong style="color: #ffcc00;">📋 Gerenciar Notícias</strong>
    </div>
    <div style="padding: 15px;">
        
        <?php if (isset($_SESSION['sucesso_noticia'])): ?>
            <div class="aviso_ok" style="margin-bottom: 10px; padding: 10px; background: #4CAF50; color: white; border-radius: 4px;">
                <?php 
                echo htmlspecialchars($_SESSION['sucesso_noticia']); 
                unset($_SESSION['sucesso_noticia']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['erro_noticia'])): ?>
            <div class="aviso_erro" style="margin-bottom: 10px; padding: 10px; background: #f44336; color: white; border-radius: 4px;">
                <?php 
                echo htmlspecialchars($_SESSION['erro_noticia']); 
                unset($_SESSION['erro_noticia']);
                ?>
            </div>
        <?php endif; ?>
        
        <div style="margin-bottom: 15px;">
            <a href="?p=admin_noticias&acao=nova" class="button" style="background: #4CAF50; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; display: inline-block;">
                ➕ Nova Notícia
            </a>
        </div>
        
        <?php if (empty($noticias)): ?>
            <p style="text-align: center; color: #999;">Nenhuma notícia cadastrada ainda.</p>
        <?php else: ?>
            <table width="100%" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">
                <thead>
                    <tr style="background: #333; color: #fff;">
                        <th style="padding: 8px; text-align: left;">ID</th>
                        <th style="padding: 8px; text-align: left;">Título</th>
                        <th style="padding: 8px; text-align: left;">Autor</th>
                        <th style="padding: 8px; text-align: left;">Data</th>
                        <th style="padding: 8px; text-align: center;">Status</th>
                        <th style="padding: 8px; text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($noticias as $noticia): 
                        $expirada = false;
                        $expira_em = '';
                        if ($noticia['data_expiracao']) {
                            $expiracao = strtotime($noticia['data_expiracao']);
                            $agora = time();
                            if ($expiracao < $agora) {
                                $expirada = true;
                            } else {
                                $dias_restantes = ceil(($expiracao - $agora) / 86400);
                                $expira_em = $dias_restantes . ' dia' . ($dias_restantes > 1 ? 's' : '');
                            }
                        }
                    ?>
                        <tr style="border-bottom: 1px solid #ddd; <?php echo $expirada ? 'background: #ffebee;' : ''; ?>">
                            <td style="padding: 8px;"><?php echo htmlspecialchars($noticia['id']); ?></td>
                            <td style="padding: 8px;">
                                <strong><?php echo htmlspecialchars($noticia['titulo']); ?></strong>
                                <?php if ($noticia['usar_cores']): ?>
                                    <span style="color: #FFD700; font-size: 12px;" title="Usa cores personalizadas">🎨</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 8px;"><?php echo htmlspecialchars($noticia['autor']); ?></td>
                            <td style="padding: 8px;">
                                <?php echo date('d/m/Y H:i', strtotime($noticia['data_criacao'])); ?>
                            </td>
                            <td style="padding: 8px; text-align: center;">
                                <?php if ($expirada): ?>
                                    <span style="color: #f44336; font-weight: bold;">❌ Expirada</span>
                                <?php elseif ($noticia['data_expiracao']): ?>
                                    <span style="color: #ff9800;">⏰ <?php echo $expira_em; ?></span>
                                <?php else: ?>
                                    <span style="color: #4CAF50;">✔️ Permanente</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 8px; text-align: center;">
                                <a href="?p=admin_noticias&acao=editar&id=<?php echo $noticia['id']; ?>" 
                                   style="color: #2196F3; text-decoration: none; margin-right: 10px;">
                                    ✏️ Editar
                                </a>
                                <form method="POST" action="?p=admin_noticias&acao=deletar" 
                                      style="display: inline;" 
                                      onsubmit="return confirm('Tem certeza que deseja deletar esta notícia?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo SecurityHelper::generateCsrfToken(); ?>">
                                    <input type="hidden" name="id" value="<?php echo $noticia['id']; ?>">
                                    <button type="submit" style="background: none; border: none; color: #f44336; cursor: pointer;">
                                        🗑️ Deletar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($totalPages > 1): ?>
                <div style="margin-top: 15px; text-align: center;">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <strong style="margin: 0 5px;"><?php echo $i; ?></strong>
                        <?php else: ?>
                            <a href="?p=admin_noticias&page=<?php echo $i; ?>" style="margin: 0 5px;">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
