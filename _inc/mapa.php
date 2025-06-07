Applying the provided changes to implement the player interaction popup and movement functionality.
```

```php
<?php
if(!isset($_SESSION['logado'])) {
    header("Location: index.php?p=login");
    exit;
}



// Verificar se o jogador tem posição no mapa
$stmt = $conexao->prepare("SELECT * FROM players_positions WHERE player_id = ?");
$stmt->execute([$_SESSION['logado']]);
$position = $stmt->fetch(PDO::FETCH_ASSOC);

// Se não tem posição, colocar em Konoha
if(!$position) {
    $stmt = $conexao->prepare("INSERT INTO players_positions (player_id, current_page_id, x, y) VALUES (?, 1, 10, 10)");
    $stmt->execute([$_SESSION['logado']]);
    $position = ['player_id' => $_SESSION['logado'], 'current_page_id' => 1, 'x' => 10, 'y' => 10];
}

// Buscar informações da página atual
$stmt = $conexao->prepare("SELECT * FROM maps_pages WHERE id = ?");
$stmt->execute([$position['current_page_id']]);
$current_page = $stmt->fetch(PDO::FETCH_ASSOC);

// Se a página não existe, colocar o jogador em Konoha (página 1)
if(!$current_page) {
    $stmt = $conexao->prepare("UPDATE players_positions SET current_page_id = 1 WHERE player_id = ?");
    $stmt->execute([$_SESSION['logado']]);

    // Buscar página de Konoha
    $stmt = $conexao->prepare("SELECT * FROM maps_pages WHERE id = 1");
    $stmt->execute();
    $current_page = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se ainda não existe, criar página básica
    if(!$current_page) {
        $stmt = $conexao->prepare("INSERT INTO maps_pages (id, name, grid_data) VALUES (1, 'Konoha', '{\"type\":\"vila\",\"obstacles\":[]}')");
        $stmt->execute();
        $current_page = ['id' => 1, 'name' => 'Konoha', 'grid_data' => '{"type":"vila","obstacles":[]}', 'north_page_id' => null, 'south_page_id' => null, 'east_page_id' => null, 'west_page_id' => null];
    }
}

// Buscar outros jogadores na mesma página
$stmt = $conexao->prepare("
    SELECT pp.*, u.usuario, u.avatar, u.personagem 
    FROM players_positions pp 
    JOIN usuarios u ON pp.player_id = u.id 
    WHERE pp.current_page_id = ? AND pp.player_id != ?
");
$stmt->execute([$position['current_page_id'], $_SESSION['logado']]);
$other_players = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar movimento se enviado
if(isset($_POST['move_x']) && isset($_POST['move_y'])) {
    $new_x = intval($_POST['move_x']);
    $new_y = intval($_POST['move_y']);

    // Verificar cooldown
    $last_move = strtotime($position['last_move_time']);
    $now = time();
    if($now - $last_move >= 1.5) {
        // Verificar se o movimento é válido (máximo 2 quadrados)
        $distance = abs($new_x - $position['x']) + abs($new_y - $position['y']);
        if($distance <= 2 && $new_x >= 0 && $new_x < 20 && $new_y >= 0 && $new_y < 20) {
            $stmt = $conexao->prepare("UPDATE players_positions SET x = ?, y = ?, last_move_time = CURRENT_TIMESTAMP WHERE player_id = ?");
            $stmt->execute([$new_x, $new_y, $_SESSION['logado']]);
            $position['x'] = $new_x;
            $position['y'] = $new_y;
        }
    }
}

// Processar mudança de página
if(isset($_POST['change_page'])) {
    $direction = $_POST['change_page'];
    $new_page_id = null;

    switch($direction) {
        case 'north': $new_page_id = $current_page['north_page_id']; break;
        case 'south': $new_page_id = $current_page['south_page_id']; break;
        case 'east': $new_page_id = $current_page['east_page_id']; break;
        case 'west': $new_page_id = $current_page['west_page_id']; break;
    }

    if($new_page_id) {
        $stmt = $conexao->prepare("UPDATE players_positions SET current_page_id = ? WHERE player_id = ?");
        $stmt->execute([$new_page_id, $_SESSION['logado']]);
        header("Location: index.php?p=mapa");
        exit;
    }
}
?>

<div class="box_top">Mapa - <?php echo $current_page['name']; ?></div>
<div class="box_middle">
    <div style="position: relative; width: 420px; margin: 0 auto;">
        <!-- Botões de navegação -->
        <?php if($current_page['north_page_id']): ?>
        <div style="text-align: center; margin-bottom: 5px;">
            <form method="post" style="display: inline;">
                <input type="hidden" name="change_page" value="north">
                <button type="submit" style="padding: 5px 10px; background: #333; color: white; border: none; cursor: pointer;">Norte ↑</button>
            </form>
        </div>
        <?php endif; ?>

        <div style="display: flex; align-items: center; justify-content: center;">
            <?php if($current_page['west_page_id']): ?>
            <form method="post" style="margin-right: 10px;">
                <input type="hidden" name="change_page" value="west">
                <button type="submit" style="padding: 5px 10px; background: #333; color: white; border: none; cursor: pointer;">← Oeste</button>
            </form>
            <?php endif; ?>

            <div id="mapa-container" style="position: relative; width: 400px; height: 400px; border: 2px solid #333; background: url('_img/mapa_konoha.png') no-repeat center center; background-size: cover;">
                <!-- Grade do mapa -->
                <div id="mapa-grid" style="width: 100%; height: 100%; position: absolute; top: 0; left: 0;">
                    <?php for($y = 0; $y < 20; $y++): ?>
                        <?php for($x = 0; $x < 20; $x++): ?>
                            <div class="grid-cell" 
                                 data-x="<?php echo $x; ?>" 
                                 data-y="<?php echo $y; ?>"
                                 style="position: absolute; 
                                        left: <?php echo ($x * 20); ?>px; 
                                        top: <?php echo ($y * 20); ?>px; 
                                        width: 20px; 
                                        height: 20px; 
                                        height: 20px; 
                                        border: 1px solid rgba(0,0,0,0.1);
                                        cursor: pointer;"
                                 ondblclick="moverPara(<?php echo $x; ?>, <?php echo $y; ?>)">
                            </div>
                        <?php endfor; ?>
                    <?php endfor; ?>

                    <!-- Jogador atual -->
                    <div id="player-self" 
                         style="position: absolute; 
                                left: <?php echo ($position['x'] * 20 + 7); ?>px; 
                                top: <?php echo ($position['y'] * 20 + 7); ?>px; 
                                width: 6px; 
                                height: 6px; 
                                background: blue; 
                                border-radius: 50%;
                                z-index: 10;"
                         title="Você"></div>

                    <!-- Outros jogadores -->
                    <?php foreach($other_players as $player): ?>
                    <div class="other-player" 
                         data-player-id="<?php echo $player['player_id']; ?>"
                         data-player-name="<?php echo htmlspecialchars($player['usuario']); ?>"
                         data-player-avatar="<?php echo $player['avatar'] ? "_img/personagens/" . $player['personagem'] . "/" . $player['avatar'] . ".jpg" : "_img/personagens/no_avatar.jpg"; ?>"
                         data-player-personagem="<?php echo htmlspecialchars($player['personagem']); ?>"
                         data-player-x="<?php echo $player['x']; ?>"
                         data-player-y="<?php echo $player['y']; ?>"
                         style="position: absolute; 
                                left: <?php echo ($player['x'] * 20 + 5); ?>px; 
                                top: <?php echo ($player['y'] * 20 + 5); ?>px; 
                                width: 10px; 
                                height: 10px; 
                                background: red; 
                                border-radius: 50%;
                                z-index: 9;
                                cursor: pointer;"
                         title="<?php echo $player['usuario']; ?>"
                         onclick="mostrarPopupJogador(this)"></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if($current_page['east_page_id']): ?>
            <form method="post" style="margin-left: 10px;">
                <input type="hidden" name="change_page" value="east">
                <button type="submit" style="padding: 5px 10px; background: #333; color: white; border: none; cursor: pointer;">Leste →</button>
            </form>
            <?php endif; ?>
        </div>

        <?php if($current_page['south_page_id']): ?>
        <div style="text-align: center; margin-top: 5px;">
            <form method="post" style="display: inline;">
                <input type="hidden" name="change_page" value="south">
                <button type="submit" style="padding: 5px 10px; background: #333; color: white; border: none; cursor: pointer;">Sul ↓</button>
            </form>
        </div>
        <?php endif; ?>
    </div>



    <div class="sep"></div>
    <div id="position-display" style="text-align: center;">
        <b>Posição:</b> X: <?php echo $position['x']; ?>, Y: <?php echo $position['y']; ?><br>
        <small>Setas: 2 quadrados | Duplo clique: até 2 quadrados</small>
    </div>
</div>
<div class="box_bottom"></div>

<form id="move-form" method="post" style="display: none;">
    <input type="hidden" id="move-x" name="move_x">
    <input type="hidden" id="move-y" name="move_y">
</form>

<div id="player-popup" style="display: none; position: absolute; background: white; border: 2px solid #333; padding: 15px; z-index: 1000; border-radius: 5px; box-shadow: 0 4px 8px rgba(0,0,0,0.3); min-width: 200px;">
    <h3 id="popup-player-name" style="margin: 0 0 10px 0; text-align: center; color: #333;"></h3>
    <div style="text-align: center; margin-bottom: 10px;">
        <img id="popup-player-avatar" src="" alt="Avatar" style="width: 60px; height: 60px; border: 1px solid #ccc;">
    </div>
    <p style="margin: 5px 0; text-align: center;"><strong>Personagem:</strong> <span id="popup-player-personagem"></span></p>
    <div style="text-align: center; margin-top: 15px;">
        <button onclick="atacarJogador()" style="background: #d32f2f; color: white; border: none; padding: 8px 15px; margin: 0 5px; cursor: pointer; border-radius: 3px;">Atacar</button>
        <button onclick="cancelarAtaque()" style="background: #757575; color: white; border: none; padding: 8px 15px; margin: 0 5px; cursor: pointer; border-radius: 3px;">Cancelar</button>
    </div>
    <div style="text-align: center; margin-top: 10px;">
        <button onclick="fecharPopup()" style="background: #388e3c; color: white; border: none; padding: 6px 12px; cursor: pointer; border-radius: 3px; font-size: 12px;">Fechar</button>
    </div>
</div>

<script>
let lastMoveTime = 0;
let currentX = <?php echo $position['x']; ?>;
let currentY = <?php echo $position['y']; ?>;

function moverPara(x, y) {
    const now = Date.now();
    const distance = Math.abs(x - currentX) + Math.abs(y - currentY);
    if (distance === 2) {
        if(now - lastMoveTime < 1500) {
            alert('Aguarde 1.5 segundos entre movimentos!');
            return;
        }
    }
    if(distance > 2) {
        alert('Você só pode mover até 2 quadrados por vez!');
        return;
    }

    // Fazer movimento via AJAX
    const formData = new FormData();
    formData.append('move_x', x);
    formData.append('move_y', y);

    fetch(window.location.pathname + '?p=mapa', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro no movimento: ' + response.status);
        }
        return response.text();
    })
    .then(data => {
        // Atualizar posição atual
        currentX = x;
        currentY = y;

        // Atualizar posição visual do jogador
        const playerElement = document.getElementById('player-self');
        if (playerElement) {
            playerElement.style.left = (x * 20 + 7) + 'px';
            playerElement.style.top = (y * 20 + 7) + 'px';
        }

        // Atualizar display de posição
        const positionDisplay = document.getElementById('position-display');
        if(positionDisplay) {
            positionDisplay.innerHTML = '<b>Posição:</b> X: ' + x + ', Y: ' + y + '<br><small>Setas: 2 quadrados | Duplo clique: até 2 quadrados</small>';
        }

        if (distance === 2) {
          lastMoveTime = now;
        }
    })
    .catch(error => {
        console.error('Erro ao mover:', error);
        alert('Erro ao realizar movimento!');
    });
}

function mostrarPopupJogador(element) {
    const playerId = element.getAttribute('data-player-id');
    const playerName = element.getAttribute('data-player-name');
    const playerAvatar = element.getAttribute('data-player-avatar');
    const playerPersonagem = element.getAttribute('data-player-personagem');
    const playerX = parseInt(element.getAttribute('data-player-x'));
    const playerY = parseInt(element.getAttribute('data-player-y'));

    // Verificar proximidade (máximo 2 quadrados)
    const distance = Math.abs(playerX - currentX) + Math.abs(playerY - currentY);
    if(distance > 2) {
        alert('Você precisa estar mais próximo para interagir com este jogador!');
        return;
    }

    document.getElementById('popup-player-name').innerText = playerName;
    document.getElementById('popup-player-avatar').src = playerAvatar || '_img/personagens/no_avatar.jpg';
    document.getElementById('popup-player-personagem').innerText = playerPersonagem;

    const popup = document.getElementById('player-popup');
    popup.style.display = 'block';
    popup.style.left = (playerX * 20 + 20) + 'px';
    popup.style.top = (playerY * 20 - 20) + 'px';

    // Tornar variáveis acessíveis globalmente
    window.selectedPlayerId = playerId;
    window.selectedPlayerX = playerX;
    window.selectedPlayerY = playerY;
}

function atacarJogador() {
    // Redirecionar para a página de batalha com o ID do jogador selecionado
    if (window.selectedPlayerId) {
        window.location.href = 'index.php?p=batalha&opponent_id=' + window.selectedPlayerId;
    } else {
        alert('Nenhum jogador selecionado para atacar.');
    }
}

function cancelarAtaque() {
    // Mover o jogador atual 5 quadrados para longe do jogador selecionado
    if (window.selectedPlayerX !== undefined && window.selectedPlayerY !== undefined) {
        let newX = currentX;
        let newY = currentY;

        // Calcular direção oposta
        const deltaX = currentX - window.selectedPlayerX;
        const deltaY = currentY - window.selectedPlayerY;

        // Mover 5 quadrados na direção oposta
        if (Math.abs(deltaX) > Math.abs(deltaY)) {
            // Mover horizontalmente
            newX = deltaX > 0 ? Math.min(19, currentX + 5) : Math.max(0, currentX - 5);
        } else {
            // Mover verticalmente
            newY = deltaY > 0 ? Math.min(19, currentY + 5) : Math.max(0, currentY - 5);
        }

        moverPara(newX, newY);
    }

    fecharPopup();
}

function fecharPopup() {
    document.getElementById('player-popup').style.display = 'none';
    window.selectedPlayerId = null;
    window.selectedPlayerX = null;
    window.selectedPlayerY = null;
}

// Atualizar outros jogadores a cada 10 segundos sem recarregar a página
function atualizarOutrosJogadores() {
    const url = 'ajax_outros_jogadores.php';
    
    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Cache-Control': 'no-cache'
        }
    })
    .then(response => {
        console.log('Status da resposta:', response.status);
        if (!response.ok) {
            throw new Error('HTTP ' + response.status + ': ' + response.statusText);
        }
        return response.text();
    })
    .then(text => {
        console.log('Resposta recebida (primeiros 100 chars):', text.substring(0, 100));
        
        // Verificar se a resposta começar com HTML
        if (text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<html')) {
            console.warn('Recebida resposta HTML em vez de JSON - ignorando atualização');
            return;
        }
        
        // Verificar se é uma string vazia
        if (!text.trim()) {
            console.warn('Resposta vazia recebida');
            return;
        }
        
        let data;
        try {
            data = JSON.parse(text);
            console.log('JSON parseado com sucesso:', data);
        } catch (e) {
            console.error('Erro ao parsear JSON:', e.message);
            console.error('Texto recebido:', text);
            return;
        }
        
        // Verificar se há erro na resposta
        if (data.error) {
            console.error('Erro retornado pelo servidor:', data.error);
            return;
        }
        
        // Remover jogadores existentes
        const existingPlayers = document.querySelectorAll('.other-player');
        existingPlayers.forEach(player => player.remove());

        // Adicionar jogadores atualizados
        const mapGrid = document.getElementById('mapa-grid');
        if (mapGrid && Array.isArray(data)) {
            console.log('Adicionando', data.length, 'jogadores ao mapa');
            data.forEach(player => {
                // Verificar se player tem as propriedades necessárias
                if (player.x === undefined || player.y === undefined) {
                    console.warn('Jogador sem coordenadas válidas:', player);
                    return;
                }
                if (!player.usuario) {
                    console.warn('Jogador sem nome de usuário:', player);
                    return;
                }
                
                const playerDiv = document.createElement('div');
                playerDiv.className = 'other-player';
                playerDiv.setAttribute('data-player-id', player.player_id || '');
                playerDiv.setAttribute('data-player-name', player.usuario || '');
                playerDiv.setAttribute('data-player-avatar', player.avatar || '_img/personagens/no_avatar.jpg');
                playerDiv.setAttribute('data-player-personagem', player.personagem || 'Não definido');
                playerDiv.setAttribute('data-player-x', player.x);
                playerDiv.setAttribute('data-player-y', player.y);
                playerDiv.style.position = 'absolute';
                playerDiv.style.left = (parseInt(player.x) * 20 + 5) + 'px';
                playerDiv.style.top = (parseInt(player.y) * 20 + 5) + 'px';
                playerDiv.style.width = '10px';
                playerDiv.style.height = '10px';
                playerDiv.style.background = 'red';
                playerDiv.style.borderRadius = '50%';
                playerDiv.style.zIndex = '9';
                playerDiv.style.cursor = 'pointer';
                playerDiv.title = player.usuario;
                playerDiv.onclick = function() { mostrarPopupJogador(this); };
                mapGrid.appendChild(playerDiv);
            });
        } else {
            console.log('Nenhum jogador encontrado ou dados inválidos');
        }
    })
    .catch(error => {
        console.error('Erro ao atualizar jogadores:', error.message || error);
    });
}

// Atualizar outros jogadores a cada 10 segundos
setInterval(atualizarOutrosJogadores, 10000);

// Adicionar controles de teclado para movimentação
document.addEventListener('keydown', function(e) {
    // Verificar se não estamos em um campo de input
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
        return;
    }

    let newX = currentX;
    let newY = currentY;
    let moved = false;
    let distance = 2; // Movement is now 2 squares

    switch(e.keyCode) {
        case 37: // Seta esquerda
            newX = Math.max(0, currentX - distance);
            moved = true;
            break;
        case 38: // Seta para cima
            newY = Math.max(0, currentY - distance);
            moved = true;
            break;
        case 39: // Seta direita
            newX = Math.min(19, currentX + distance);
            moved = true;
            break;
        case 40: // Seta para baixo
            newY = Math.min(19, currentY + distance);
            moved = true;
            break;
    }

    if (moved && (newX !== currentX || newY !== currentY)) {
        e.preventDefault(); // Prevenir scroll da página
        const actualDistance = Math.abs(newX - currentX) + Math.abs(newY - currentY);
         if (actualDistance !== 2) {
            return;
        }

        moverPara(newX, newY);
    }
});

// Garantir que o elemento tenha foco para receber eventos de teclado
document.addEventListener('DOMContentLoaded', function() {
    document.body.tabIndex = 0;
    document.body.focus();
});

// Fechar popup ao clicar fora dele
document.addEventListener('click', function(e) {
    const popup = document.getElementById('player-popup');
    if (popup && popup.style.display === 'block' && !popup.contains(e.target) && !e.target.classList.contains('other-player')) {
        fecharPopup();
    }
});
</script>
</replit_final_file>