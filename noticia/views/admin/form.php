<?php require_once __DIR__ . '/../../helpers/SecurityHelper.php'; ?>
<!-- Formulário de Notícia - Admin -->
<div style="background: #2a2a2a; border: 1px solid #444; border-radius: 5px; margin-bottom: 10px;">
    <div style="background: #333; padding: 10px; border-bottom: 1px solid #444; border-radius: 5px 5px 0 0;">
        <strong style="color: #ffcc00;"><?php echo $acao === 'editar' ? '✏️ Editar Notícia' : '➕ Nova Notícia'; ?></strong>
    </div>
    <div style="padding: 15px;">
        
        <?php if (isset($_SESSION['erro_noticia'])): ?>
            <div class="aviso_erro" style="margin-bottom: 10px; padding: 10px; background: #f44336; color: white; border-radius: 4px;">
                <?php 
                echo htmlspecialchars($_SESSION['erro_noticia']); 
                unset($_SESSION['erro_noticia']);
                ?>
            </div>
        <?php endif; ?>
        
        <!-- Guia de Uso de Cores -->
        <div style="background: #1a1a1a; border: 2px solid #ffcc00; padding: 12px; margin-bottom: 20px; border-radius: 5px;">
            <strong style="color: #ffcc00;">📝 Como usar cores nas notícias:</strong>
            <div style="margin-top: 8px; font-size: 13px; line-height: 1.6;">
                <p style="margin: 5px 0;">Para colorir texto, use a tag: <code style="background: #333; padding: 2px 6px; border-radius: 3px;">[cor=#CODIGO]texto[/cor]</code></p>
                <p style="margin: 5px 0;"><strong>Exemplos de cores:</strong></p>
                <ul style="margin: 5px 0; padding-left: 20px;">
                    <li><code style="background: #333; padding: 2px 6px; border-radius: 3px;">[cor=#FF0000]Vermelho[/cor]</code> → <span style="color: #FF0000;">Vermelho</span></li>
                    <li><code style="background: #333; padding: 2px 6px; border-radius: 3px;">[cor=#00FF00]Verde[/cor]</code> → <span style="color: #00FF00;">Verde</span></li>
                    <li><code style="background: #333; padding: 2px 6px; border-radius: 3px;">[cor=#0080FF]Azul[/cor]</code> → <span style="color: #0080FF;">Azul</span></li>
                    <li><code style="background: #333; padding: 2px 6px; border-radius: 3px;">[cor=#FFD700]Dourado[/cor]</code> → <span style="color: #FFD700;">Dourado</span></li>
                    <li><code style="background: #333; padding: 2px 6px; border-radius: 3px;">[cor=#FF00FF]Rosa[/cor]</code> → <span style="color: #FF00FF;">Rosa</span></li>
                </ul>
                <p style="margin: 5px 0;"><strong>Formatação adicional:</strong></p>
                <ul style="margin: 5px 0; padding-left: 20px;">
                    <li><code style="background: #333; padding: 2px 6px; border-radius: 3px;">[b]Negrito[/b]</code></li>
                    <li><code style="background: #333; padding: 2px 6px; border-radius: 3px;">[i]Itálico[/i]</code></li>
                    <li><code style="background: #333; padding: 2px 6px; border-radius: 3px;">[u]Sublinhado[/u]</code></li>
                </ul>
            </div>
        </div>
        
        <form method="POST" action="?p=admin_noticias&acao=salvar">
            <input type="hidden" name="csrf_token" value="<?php echo SecurityHelper::generateCsrfToken(); ?>">
            <?php if ($noticia): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($noticia['id']); ?>">
            <?php endif; ?>
            
            <div style="margin-bottom: 15px;">
                <label for="titulo" style="display: block; margin-bottom: 5px; font-weight: bold;">
                    Título da Notícia:
                </label>
                <input type="text" 
                       id="titulo" 
                       name="titulo" 
                       value="<?php echo $noticia ? htmlspecialchars($noticia['titulo']) : ''; ?>" 
                       required 
                       maxlength="250"
                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                <small style="color: #999;">Use tags de cor para destacar partes do título</small>
            </div>
            
            <!-- Botões de Cores Rápidas -->
            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Cores Rápidas:</label>
                <button type="button" onclick="insertColor('#FF0000', 'conteudo')" style="background: #FF0000; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; margin-right: 5px;">Vermelho</button>
                <button type="button" onclick="insertColor('#00FF00', 'conteudo')" style="background: #00FF00; color: black; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; margin-right: 5px;">Verde</button>
                <button type="button" onclick="insertColor('#0080FF', 'conteudo')" style="background: #0080FF; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; margin-right: 5px;">Azul</button>
                <button type="button" onclick="insertColor('#FFD700', 'conteudo')" style="background: #FFD700; color: black; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; margin-right: 5px;">Dourado</button>
                <button type="button" onclick="insertColor('#FF00FF', 'conteudo')" style="background: #FF00FF; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; margin-right: 5px;">Rosa</button>
                <button type="button" onclick="insertColor('#FFFFFF', 'conteudo')" style="background: #FFFFFF; color: black; padding: 5px 10px; border: 1px solid #ccc; border-radius: 3px; cursor: pointer; margin-right: 5px;">Branco</button>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="conteudo" style="display: block; margin-bottom: 5px; font-weight: bold;">
                    Conteúdo:
                </label>
                <textarea id="conteudo" 
                          name="conteudo" 
                          required 
                          rows="12"
                          style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-family: monospace; font-size: 13px;"><?php echo $noticia ? htmlspecialchars($noticia['conteudo']) : ''; ?></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label for="dias_expiracao" style="display: block; margin-bottom: 5px; font-weight: bold;">
                        ⏰ Expirar em quantos dias? (opcional)
                    </label>
                    <input type="number" 
                           id="dias_expiracao" 
                           name="dias_expiracao" 
                           min="1" 
                           max="365"
                           placeholder="Ex: 7 (deixe vazio para nunca expirar)"
                           value="<?php 
                           if ($noticia && $noticia['data_expiracao']) {
                               $diff = (strtotime($noticia['data_expiracao']) - time()) / 86400;
                               echo $diff > 0 ? round($diff) : '';
                           }
                           ?>"
                           style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                    <small style="color: #999;">A notícia será removida automaticamente após esse período</small>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">
                        🎨 Configurações
                    </label>
                    <div style="padding: 8px;">
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" 
                                   name="usar_cores" 
                                   checked="<?php echo (!$noticia || $noticia['usar_cores']) ? 'checked' : ''; ?>"
                                   style="margin-right: 8px; width: 18px; height: 18px;">
                            <span>Ativar sistema de cores</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 20px;">
                <button type="submit" style="background: #4CAF50; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold;">
                    💾 <?php echo $acao === 'editar' ? 'Atualizar' : 'Criar'; ?> Notícia
                </button>
                <a href="?p=admin_noticias" style="background: #999; color: white; padding: 12px 24px; border-radius: 4px; text-decoration: none; margin-left: 10px; display: inline-block;">
                    ❌ Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function insertColor(color, textareaId) {
    var textarea = document.getElementById(textareaId);
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    var selectedText = textarea.value.substring(start, end);
    
    if (selectedText.length === 0) {
        selectedText = 'texto';
    }
    
    var colorTag = '[cor=' + color + ']' + selectedText + '[/cor]';
    
    textarea.value = textarea.value.substring(0, start) + colorTag + textarea.value.substring(end);
    
    // Reposicionar cursor
    textarea.focus();
    textarea.setSelectionRange(start + 10 + color.length, start + 10 + color.length + selectedText.length);
}
</script>
