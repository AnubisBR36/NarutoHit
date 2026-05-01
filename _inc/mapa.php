<?php
require_once('verificar.php');
require_once('conexao.php');

$isVIP = isset($db['vip']) && ($db['vip'] > 0 || (is_string($db['vip']) && strtotime($db['vip']) > time()));
$isAdmin = isset($db['adm']) && ($db['adm'] == 1 || $db['adm'] == 2);

$avatar = '_img/personagens/no_avatar.jpg';
if (isset($db['personagem']) && isset($db['avatar'])) {
    $avatar = "_img/personagens/{$db['personagem']}/{$db['avatar']}.jpg";
}

$vilaNome = 'Folha';
$vilaId = isset($db['vila']) ? (int)$db['vila'] : 1;
if (isset($db['vila'])) {
    switch($db['vila']) {
        case 1: $vilaNome = 'Folha'; break;
        case 2: $vilaNome = 'Areia'; break;
        case 3: $vilaNome = 'Névoa'; break;
        case 4: $vilaNome = 'Nuvem'; break;
        case 5: $vilaNome = 'Pedra'; break;
        case 6: $vilaNome = 'Som'; break;
        case 7: $vilaNome = 'Chuva'; break;
        case 8: $vilaNome = 'Akatsuki'; break;
    }
}

$aliancas = [
    1 => [2],
    2 => [1],
    3 => [],
    4 => [],
    5 => [],
    6 => [],
    7 => [],
    8 => []
];

$myAllies = isset($aliancas[$vilaId]) ? $aliancas[$vilaId] : [];
?>

        <style>
        #mapContainer {
            width: 100%;
            height: auto;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            background: #1a1a1a;
            border-radius: 5px;
            overflow: hidden;
        }
        
        #mapCanvas {
            border: 2px solid #ff6600;
            border-radius: 5px;
            cursor: crosshair;
            max-width: 100%;
        }
        
        #mapControls {
            background: rgba(0, 0, 0, 0.8);
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid #ff6600;
            color: #fff;
            text-align: center;
            margin-top: 10px;
        }
        
        #mapControls .key {
            display: inline-block;
            background: #333;
            border: 1px solid #666;
            padding: 3px 8px;
            margin: 2px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 11px;
        }
        
        
        .loading-map {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #ff6600;
            font-size: 18px;
            font-weight: bold;
        }
        
        #mapControlsLegend {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            padding: 10px 15px;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 5px;
            border: 1px solid #ff6600;
            gap: 15px;
        }
        
        #mapControls {
            color: #fff;
            font-size: 12px;
            flex: 1;
            font-weight: bold;
        }
        
        #mapLegend {
            background: rgba(255, 255, 255, 0.95);
            color: #000;
            font-size: 10px;
            display: flex;
            flex-direction: row;
            gap: 12px;
            align-items: center;
            flex-shrink: 0;
            padding: 10px 15px;
            border-radius: 5px;
            border: 2px solid #ff6600;
        }
        
        #mapLegend .legend-item {
            display: flex;
            align-items: center;
            white-space: nowrap;
            background: rgba(255, 102, 0, 0.15);
            padding: 5px 10px;
            border-radius: 3px;
            border: 1px solid #ff6600;
        }
        
        #mapLegend .legend-icon {
            width: 14px;
            height: 14px;
            margin-right: 3px;
            border-radius: 3px;
        }
        
        #mapLegend .legend-title {
            font-weight: bold;
            margin-right: 5px;
            color: #ff6600;
        }
        
        <?php if($isAdmin): ?>
        #adminPanel {
            background: rgba(26, 26, 26, 0.95);
            border: 2px solid #ff6600;
            border-radius: 10px;
            padding: 15px;
            color: #fff;
            margin-top: 15px;
        }
        #adminPanel h4 {
            color: #ff6600;
            margin: 0 0 10px 0;
        }
        #adminPanel .admin-section {
            margin-bottom: 10px;
            padding: 10px;
            background: rgba(255,255,255,0.1);
            border-radius: 5px;
        }
        #adminPanel select, #adminPanel button {
            padding: 5px 10px;
            margin: 3px;
            border-radius: 3px;
        }
        #adminPanel button {
            background: url('_img/fundo_botao.jpg');
            border: 1px solid #654321;
            color: #fff;
            cursor: pointer;
        }
        #adminPanel button:hover {
            background: #ff6600;
        }
        #adminPanel button.active {
            background: #ff6600;
            border-color: #fff;
        }
        #adminPanel select {
            background: #333;
            border: 1px solid #ff6600;
            color: #fff;
        }
        <?php endif; ?>
    </style>
    
    <div id="mapContainer">
        <div class="loading-map" id="loadingMap">Carregando mapa...</div>
        <canvas id="mapCanvas" width="900" height="600"></canvas>
    </div>
    
    <div id="mapControlsLegend">
        <div id="mapControls">
            <strong>Mapa:</strong> <span id="currentMap">MapaBase</span> | <strong>Posição:</strong> (<span id="posX">0</span>, <span id="posY">0</span>)
        </div>
        <div id="mapLegend"></div>
    </div>
    
    <?php if($isAdmin): ?>
    <div id="adminPanel">
        <h4>Editor de Mapas (Admin)</h4>
        
        <div class="admin-section">
            <strong>Modo:</strong>
            <button id="btnToggleEdit" onclick="toggleEditMode()">Ativar Editor</button>
        </div>
        
        <div class="admin-section">
            <strong>Destino (para entradas):</strong>
            <select id="selectDestino" onchange="setDestino(this.value)">
                <option value="">Selecione...</option>
                <option value="MapaBase">MapaBase (Mapa Mundial)</option>
                <option value="Vila Akatsuki">Vila Akatsuki</option>
                <option value="Vila Oculta da Areia">Vila Oculta da Areia</option>
                <option value="Vila Oculta da Chuva">Vila Oculta da Chuva</option>
                <option value="Vila Oculta da Folha">Vila Oculta da Folha</option>
                <option value="Vila Oculta da Névoa">Vila Oculta da Névoa</option>
                <option value="Vila Oculta da Nuvem">Vila Oculta da Nuvem</option>
                <option value="Vila Oculta da Pedra">Vila Oculta da Pedra</option>
            </select>
        </div>
        
        <div class="admin-section" id="coordenadasPortal">
            <strong>Coordenadas do Portal (onde aparece no mapa):</strong><br>
            <label>X: <input type="number" id="portalX" value="0" min="0" max="500" style="width:60px; background:#333; border:1px solid #ff6600; color:#fff;"></label>
            <label>Y: <input type="number" id="portalY" value="0" min="0" max="500" style="width:60px; background:#333; border:1px solid #ff6600; color:#fff;"></label>
            <br><small style="color:#999;">Posicao do icone icone_vila.png no mapa atual</small>
        </div>
        
        <div class="admin-section" id="coordenadasDestino">
            <strong>Coordenadas de Destino:</strong><br>
            <label>X: <input type="number" id="destinoX" value="10" min="0" max="500" style="width:60px; background:#333; border:1px solid #ff6600; color:#fff;"></label>
            <label>Y: <input type="number" id="destinoY" value="10" min="0" max="500" style="width:60px; background:#333; border:1px solid #ff6600; color:#fff;"></label>
            <br><small style="color:#999;">Posicao onde o jogador vai aparecer no mapa de destino</small>
        </div>
        
        <div class="admin-section">
            <strong>Criar Portal:</strong><br>
            <button onclick="addEntradaManual()" style="background:#228B22;">Criar Entrada</button>
            <button onclick="addSaidaManual()" style="background:#FF8C00;">Criar Saida</button>
            <br><small style="color:#999;">Use os campos acima para definir as coordenadas</small>
        </div>
        
        <div class="admin-section">
            <strong>Remover Portal:</strong><br>
            <label>X: <input type="number" id="removePortalX" value="0" min="0" max="500" style="width:60px; background:#333; border:1px solid #ff6600; color:#fff;"></label>
            <label>Y: <input type="number" id="removePortalY" value="0" min="0" max="500" style="width:60px; background:#333; border:1px solid #ff6600; color:#fff;"></label>
            <button onclick="removePortalManual()" style="background:#DC143C;">Remover</button>
            <br><small style="color:#999;">Insira as coordenadas do portal a remover</small>
        </div>
        
        <div class="admin-section">
            <strong>Ir para Mapa:</strong>
            <select id="selectMapa" onchange="goToMap(this.value)">
                <option value="">Selecione...</option>
                <option value="MapaBase">MapaBase (Mapa Mundial)</option>
                <option value="Vila Akatsuki">Vila Akatsuki</option>
                <option value="Vila Oculta da Areia">Vila Oculta da Areia</option>
                <option value="Vila Oculta da Chuva">Vila Oculta da Chuva</option>
                <option value="Vila Oculta da Folha">Vila Oculta da Folha</option>
                <option value="Vila Oculta da Névoa">Vila Oculta da Névoa</option>
                <option value="Vila Oculta da Nuvem">Vila Oculta da Nuvem</option>
                <option value="Vila Oculta da Pedra">Vila Oculta da Pedra</option>
            </select>
        </div>
        
        <div class="admin-section">
            <small>Clique esquerdo = aplicar acao selecionada</small><br>
            <small>Icone padrao: icone_vila.png (automatico)</small>
        </div>
        
        <div class="admin-section">
            <strong>Configuracoes de Movimento (Mapa Mundial):</strong><br>
            <label>Tempo entre passos (segundos): 
                <input type="number" id="moveDelay" value="3" min="0" max="30" step="0.5" style="width:60px; background:#333; border:1px solid #ff6600; color:#fff;">
            </label>
            <button onclick="updateMoveDelay()">Salvar</button><br>
            <label style="margin-top:5px; display:inline-block;">
                <input type="checkbox" id="adminBypassMove" onchange="toggleAdminBypass()"> Admin anda sem tempo de espera
            </label>
        </div>
    </div>
    <?php endif; ?>
</div>
<div class="box_bottom"></div>

<script>
(function() {
    const VILAS = {
        "MapaBase": "_img/mapas_vilas/MapaBase.jpg",
        "Vila Akatsuki": "_img/mapas_vilas/Vila Akatsuki.jpg",
        "Vila Oculta da Areia": "_img/mapas_vilas/Vila Oculta da Areia.jpg",
        "Vila Oculta da Chuva": "_img/mapas_vilas/Vila Oculta da Chuva.jpg",
        "Vila Oculta da Folha": "_img/mapas_vilas/Vila Oculta da Folha.jpg",
        "Vila Oculta da Névoa": "_img/mapas_vilas/Vila Oculta da Névoa.jpg",
        "Vila Oculta da Nevoa": "_img/mapas_vilas/Vila Oculta da Névoa.jpg",
        "Vila Oculta da Nuvem": "_img/mapas_vilas/Vila Oculta da Nuvem.jpg",
        "Vila Oculta da Pedra": "_img/mapas_vilas/Vila Oculta da Pedra.jpg"
    };
    
    const canvas = document.getElementById('mapCanvas');
    const ctx = canvas.getContext('2d');
    
    let mapImage = null;
    
    const VILA_MAPA = {
        1: 'Vila Oculta da Folha',
        2: 'Vila Oculta da Areia',
        3: 'Vila Oculta da Nevoa',
        4: 'Vila Oculta da Nuvem',
        5: 'Vila Oculta da Pedra',
        6: 'Vila Oculta do Som',
        7: 'Vila Oculta da Chuva',
        8: 'Vila Akatsuki'
    };
    
    const playerVila = <?php echo $vilaId; ?>;
    const playerAllies = <?php echo json_encode($myAllies); ?>;
    const mapaInicial = VILA_MAPA[playerVila] || 'Vila Oculta da Folha';
    
    let player = {
        id: <?php echo isset($db['id']) ? (int)$db['id'] : 0; ?>,
        nome: '<?php echo addslashes($db['usuario'] ?? ''); ?>',
        vila: playerVila,
        x: 50,
        y: 50,
        mapaAtual: mapaInicial,
        isVIP: <?php echo $isVIP ? 'true' : 'false'; ?>,
        isAdmin: <?php echo $isAdmin ? 'true' : 'false'; ?>
    };
    let players = {};
    let config = {};
    let editMode = false;
    let selectedAction = null;
    let selectedDestino = null;
    let showGrid = true;
    
    let cameraX = 0;
    let cameraY = 0;
    let hoverTile = { x: -1, y: -1 };
    
    // Zoom do MapaBase - sem zoom (padrão)
    const WORLD_MAP_ZOOM = 1.0;
    
    // Sistema de delay de movimento (em milissegundos)
    let worldMapMoveDelay = 3000; // 3 segundos padrao
    let lastMoveTime = 0;
    let isMoving = false;
    let adminBypassMove = false;
    
    const ICON_SELF = '_img/Icones_map/Ninja_personagem.png';
    const ICON_SAME_VILLAGE = '_img/Icones_map/Ninja_vila.jpg';
    const ICON_ALLY = '_img/Icones_map/Ninja_aliado.jpg';
    const ICON_ENEMY = '_img/Icones_map/Ninja_Inimigo.jpg';
    const ICON_BOT = '_img/Icones_map/Ninja_bot.jpg';
    const ICON_PORTAL = '_img/Icones_map/icone_vila.png';
    
    const playerIcons = {};
    const iconLoadStatus = {};
    
    [ICON_SELF, ICON_SAME_VILLAGE, ICON_ALLY, ICON_ENEMY, ICON_BOT, ICON_PORTAL].forEach(function(src) {
        iconLoadStatus[src] = 'loading';
        const img = new Image();
        img.onload = function() {
            iconLoadStatus[src] = 'loaded';
            render();
        };
        img.onerror = function() {
            iconLoadStatus[src] = 'failed';
            console.log('Icon failed to load:', src);
        };
        img.src = src;
        playerIcons[src] = img;
    });
    
    function loadConfig() {
        $.ajax({
            url: '_inc/map_api.php',
            type: 'GET',
            data: { action: 'getConfig' },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    config = data.config || {};
                    if (data.player && data.player.mapaAtual) {
                        player.x = data.player.x || 50;
                        player.y = data.player.y || 50;
                        player.mapaAtual = data.player.mapaAtual;
                    }
                    loadMapImage(player.mapaAtual);
                } else {
                    console.error('API error:', data.message);
                    loadMapImage(player.mapaAtual);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                loadMapImage(player.mapaAtual);
            }
        });
    }
    
    function loadMapImage(mapaAtual) {
        let imgPath = VILAS[mapaAtual] || VILAS['MapaBase'];
        imgPath = encodeURI(imgPath);
        
        console.log('Loading map:', mapaAtual, 'Path:', imgPath);
        
        mapImage = new Image();
        mapImage.onload = function() {
            console.log('Map loaded successfully:', imgPath, 'Size:', mapImage.width, 'x', mapImage.height);
            document.getElementById('loadingMap').style.display = 'none';
            
            // Ajustar tamanho do canvas
            if (!isWorldMap()) {
                // Pegar largura disponivel (borda laranja)
                var container = document.getElementById('mapContainer');
                var maxWidth = container.offsetWidth - 4; // 4px para bordas
                var maxHeight = 500;
                
                // Calcular escala para caber
                var scaleX = maxWidth / mapImage.width;
                var scaleY = maxHeight / mapImage.height;
                var scale = Math.min(scaleX, scaleY, 1);
                
                canvas.width = mapImage.width * scale;
                canvas.height = mapImage.height * scale;
            } else {
                // Para mapa mundi, usar tamanho original da imagem para melhor visualizacao
                canvas.width = mapImage.width;
                canvas.height = mapImage.height;
            }
            
            updateCamera();
            render();
        };
        mapImage.onerror = function() {
            console.error('Failed to load map:', imgPath);
            document.getElementById('loadingMap').textContent = 'Erro ao carregar mapa: ' + mapaAtual;
            if (mapaAtual !== 'MapaBase') {
                console.log('Trying fallback to MapaBase...');
                loadMapImage('MapaBase');
            }
        };
        mapImage.src = imgPath;
    }
    
    function getTileSize() {
        const mapaConfig = config[player.mapaAtual];
        if (mapaConfig && mapaConfig.tileSize) {
            return mapaConfig.tileSize;
        }
        // Default fallback
        return 32;
    }
    
    function isWorldMap() {
        return player.mapaAtual === 'MapaBase';
    }
    
    function updateCamera() {
        if (!mapImage || !mapImage.complete) return;
        
        if (isWorldMap()) {
            // MapaBase: sem zoom, viewport é o canvas inteiro
            const viewportWidth = canvas.width;
            const viewportHeight = canvas.height;
            const tileSize = getTileSize();
            const playerPixelX = player.x * tileSize + tileSize / 2;
            const playerPixelY = player.y * tileSize + tileSize / 2;
            
            cameraX = playerPixelX - viewportWidth / 2;
            cameraY = playerPixelY - viewportHeight / 2;
            
            // Parar nas bordas
            cameraX = Math.max(0, Math.min(cameraX, mapImage.width - viewportWidth));
            cameraY = Math.max(0, Math.min(cameraY, mapImage.height - viewportHeight));
        } else {
            cameraX = 0;
            cameraY = 0;
        }
    }
    
    function render() {
        if (!ctx || !mapImage || !mapImage.complete || mapImage.naturalWidth === 0) return;
        
        const tileSize = getTileSize();
        
        ctx.fillStyle = '#1a1a1a';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        ctx.save();
        
        if (isWorldMap()) {
            // MapaBase: sem zoom, desenhar com câmera
            ctx.translate(-cameraX, -cameraY);
            ctx.drawImage(mapImage, 0, 0);
        } else {
            // Mapas de vilas - fixos, escalados para caber no canvas
            ctx.drawImage(mapImage, 0, 0, canvas.width, canvas.height);
        }
        
        // Calcular escala para mapas de vilas
        var renderScale = isWorldMap() ? 1 : (canvas.width / mapImage.width);
        var scaledTileSize = tileSize * renderScale;
        
        if (showGrid) {
            drawGrid(scaledTileSize, renderScale);
        }
        
        drawPortals(scaledTileSize, renderScale);
        drawPlayers(scaledTileSize, renderScale);
        
        ctx.restore();
        
        updateUI();
    }
    
    function drawGrid(tileSize, renderScale) {
        if (!mapImage) return;
        
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.42)';
        ctx.lineWidth = 1;
        
        var drawWidth = isWorldMap() ? mapImage.width : canvas.width;
        var drawHeight = isWorldMap() ? mapImage.height : canvas.height;
        var baseTileSize = getTileSize();
        var cols = Math.ceil(mapImage.width / baseTileSize);
        var rows = Math.ceil(mapImage.height / baseTileSize);
        
        for (let x = 0; x <= cols; x++) {
            ctx.beginPath();
            ctx.moveTo(x * tileSize, 0);
            ctx.lineTo(x * tileSize, drawHeight);
            ctx.stroke();
        }
        
        for (let y = 0; y <= rows; y++) {
            ctx.beginPath();
            ctx.moveTo(0, y * tileSize);
            ctx.lineTo(drawWidth, y * tileSize);
            ctx.stroke();
        }
        
        // Hover tile
        if (hoverTile.x >= 0 && hoverTile.y >= 0) {
            var hx = hoverTile.x * tileSize;
            var hy = hoverTile.y * tileSize;
            ctx.fillStyle = 'rgba(255, 255, 255, 0.12)';
            ctx.fillRect(hx, hy, tileSize, tileSize);
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.55)';
            ctx.lineWidth = 1.5;
            ctx.strokeRect(hx + 1, hy + 1, tileSize - 2, tileSize - 2);
        }
        
        // Destacar tile atual do jogador
        var playerTileX = player.x * tileSize;
        var playerTileY = player.y * tileSize;
        ctx.strokeStyle = 'rgba(255, 102, 0, 0.9)';
        ctx.lineWidth = 2;
        ctx.strokeRect(playerTileX + 1, playerTileY + 1, tileSize - 2, tileSize - 2);
        ctx.fillStyle = 'rgba(255, 102, 0, 0.18)';
        ctx.fillRect(playerTileX, playerTileY, tileSize, tileSize);
    }
    
    function drawPortals(tileSize, renderScale) {
        const mapaConfig = config[player.mapaAtual];
        if (!mapaConfig) return;
        
        const portalIcon = playerIcons[ICON_PORTAL];
        const iconSize = tileSize * 1.5;
        var fontSize = Math.max(8, Math.floor(9 * (renderScale || 1)));
        var useIcon = true; // Sempre usar icone em todos os mapas
        
        if (mapaConfig.entradas) {
            mapaConfig.entradas.forEach(function(entrada) {
                const x = entrada.x * tileSize;
                const y = entrada.y * tileSize;
                
                if (useIcon && portalIcon && portalIcon.complete && iconLoadStatus[ICON_PORTAL] === 'loaded') {
                    ctx.drawImage(portalIcon, x - (iconSize - tileSize)/2, y - (iconSize - tileSize)/2, iconSize, iconSize);
                } else if (!useIcon) {
                    // No MapaBase, mostrar um marcador sutil para todos os jogadores (sem icone)
                    ctx.fillStyle = 'rgba(0, 200, 0, 0.25)';
                    ctx.fillRect(x, y, tileSize, tileSize);
                    ctx.strokeStyle = 'rgba(0, 255, 0, 0.5)';
                    ctx.lineWidth = 1;
                    ctx.strokeRect(x, y, tileSize, tileSize);
                } else {
                    ctx.fillStyle = 'rgba(0, 255, 0, 0.5)';
                    ctx.fillRect(x, y, tileSize, tileSize);
                    ctx.strokeStyle = '#00ff00';
                    ctx.lineWidth = 2;
                    ctx.strokeRect(x, y, tileSize, tileSize);
                }
                
                // Mostrar nome do destino apenas em vilas ou em modo admin no MapaBase
                if (!isWorldMap() || (editMode && player.isAdmin)) {
                    ctx.fillStyle = '#0f0';
                    ctx.strokeStyle = '#000';
                    ctx.lineWidth = 2;
                    ctx.font = 'bold ' + fontSize + 'px Arial';
                    ctx.textAlign = 'center';
                    ctx.strokeText(entrada.destino.substring(0, 15), x + tileSize/2, y + tileSize + 12 * (renderScale || 1));
                    ctx.fillText(entrada.destino.substring(0, 15), x + tileSize/2, y + tileSize + 12 * (renderScale || 1));
                }
            });
        }
        
        if (mapaConfig.saidas) {
            mapaConfig.saidas.forEach(function(saida) {
                const x = saida.x * tileSize;
                const y = saida.y * tileSize;
                
                if (useIcon && portalIcon && portalIcon.complete && iconLoadStatus[ICON_PORTAL] === 'loaded') {
                    ctx.drawImage(portalIcon, x - (iconSize - tileSize)/2, y - (iconSize - tileSize)/2, iconSize, iconSize);
                } else if (!useIcon) {
                    // No MapaBase, mostrar um marcador sutil para todos os jogadores (sem icone)
                    ctx.fillStyle = 'rgba(255, 165, 0, 0.25)';
                    ctx.fillRect(x, y, tileSize, tileSize);
                    ctx.strokeStyle = 'rgba(255, 165, 0, 0.5)';
                    ctx.lineWidth = 1;
                    ctx.strokeRect(x, y, tileSize, tileSize);
                } else {
                    ctx.fillStyle = 'rgba(255, 165, 0, 0.5)';
                    ctx.fillRect(x, y, tileSize, tileSize);
                    ctx.strokeStyle = '#ffa500';
                    ctx.lineWidth = 2;
                    ctx.strokeRect(x, y, tileSize, tileSize);
                }
                
                // Mostrar "Saida" apenas em vilas ou em modo admin no MapaBase
                if (!isWorldMap() || (editMode && player.isAdmin)) {
                    ctx.fillStyle = '#ffa500';
                    ctx.strokeStyle = '#000';
                    ctx.lineWidth = 2;
                    ctx.font = 'bold ' + fontSize + 'px Arial';
                    ctx.textAlign = 'center';
                    ctx.strokeText('Saida', x + tileSize/2, y + tileSize + 12 * (renderScale || 1));
                    ctx.fillText('Saida', x + tileSize/2, y + tileSize + 12 * (renderScale || 1));
                }
            });
        }
        
        ctx.textAlign = 'left';
    }
    
    function getPlayerIcon(otherPlayer) {
        if (!otherPlayer || !otherPlayer.id) return ICON_ENEMY;
        
        if (otherPlayer.id == player.id) {
            return ICON_SELF;
        }
        
        if (otherPlayer.isBot) {
            return ICON_BOT;
        }
        
        const otherVila = otherPlayer.vila || 0;
        
        if (otherVila === playerVila) {
            return ICON_SAME_VILLAGE;
        }
        
        if (playerAllies.includes(otherVila)) {
            return ICON_ALLY;
        }
        
        return ICON_ENEMY;
    }
    
    function drawPlayerCircle(px, py, tileSize, color) {
        ctx.fillStyle = color;
        ctx.beginPath();
        ctx.arc(px + tileSize/2, py + tileSize/2, tileSize/2.5, 0, Math.PI * 2);
        ctx.fill();
        ctx.strokeStyle = '#000';
        ctx.lineWidth = 2;
        ctx.stroke();
    }
    
    function drawPlayerWithIcon(iconSrc, px, py, tileSize, fallbackColor) {
        const iconSize = tileSize * 1.8;
        const icon = playerIcons[iconSrc];
        const status = iconLoadStatus[iconSrc];
        
        if (status === 'loaded' && icon && icon.complete && icon.naturalWidth > 0) {
            try {
                ctx.drawImage(icon, px - (iconSize - tileSize)/2, py - (iconSize - tileSize)/2, iconSize, iconSize);
                return;
            } catch(e) {}
        }
        
        drawPlayerCircle(px, py, tileSize, fallbackColor);
    }
    
    function drawPlayers(tileSize, renderScale) {
        var scale = renderScale || 1;
        var fontSize = Math.max(9, Math.floor(11 * scale));
        
        const x = player.x * tileSize;
        const y = player.y * tileSize;
        
        drawPlayerWithIcon(ICON_SELF, x, y, tileSize, '#ff6600');
        
        ctx.fillStyle = '#fff';
        ctx.strokeStyle = '#000';
        ctx.lineWidth = 3;
        ctx.font = 'bold ' + fontSize + 'px Arial';
        ctx.textAlign = 'center';
        ctx.strokeText(player.nome, x + tileSize/2, y - 8 * scale);
        ctx.fillText(player.nome, x + tileSize/2, y - 8 * scale);
        
        
        for (const id in players) {
            const p = players[id];
            if (!p || !p.id || !p.nome || p.mapaAtual !== player.mapaAtual || p.id == player.id) continue;
            
            const px = p.x * tileSize;
            const py = p.y * tileSize;
            
            const iconSrc = getPlayerIcon(p);
            const fallbackColors = {
                [ICON_SAME_VILLAGE]: '#00ff00',
                [ICON_ALLY]: '#3498db',
                [ICON_ENEMY]: '#ff0000',
                [ICON_BOT]: '#9b59b6'
            };
            
            drawPlayerWithIcon(iconSrc, px, py, tileSize, fallbackColors[iconSrc] || '#3498db');
            
            ctx.fillStyle = '#fff';
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 3;
            ctx.font = 'bold 11px Arial';
            ctx.textAlign = 'center';
            ctx.strokeText(p.nome, px + tileSize/2, py - 8);
            ctx.fillText(p.nome, px + tileSize/2, py - 8);
        }
        
        ctx.textAlign = 'left';
    }
    
    function updateUI() {
        document.getElementById('currentMap').textContent = player.mapaAtual;
        document.getElementById('posX').textContent = player.x;
        document.getElementById('posY').textContent = player.y;
    }
    
    function getMaxTiles() {
        const tileSize = getTileSize();
        if (mapImage && mapImage.complete) {
            return {
                maxX: Math.floor(mapImage.width / tileSize) - 1,
                maxY: Math.floor(mapImage.height / tileSize) - 1
            };
        }
        return { maxX: 50, maxY: 50 };
    }
    
    function canMove() {
        // Admin com bypass ativado pode andar sem espera
        if (player.isAdmin && adminBypassMove) {
            return true;
        }
        
        // Verificar delay apenas no mapa mundial
        if (isWorldMap()) {
            var now = Date.now();
            var elapsed = now - lastMoveTime;
            if (elapsed < worldMapMoveDelay) {
                var remaining = Math.ceil((worldMapMoveDelay - elapsed) / 1000);
                showMoveMessage('Aguarde ' + remaining + 's para mover');
                return false;
            }
        }
        
        return true;
    }
    
    function showMoveMessage(msg) {
        var existing = document.getElementById('moveMessage');
        if (existing) existing.remove();
        
        var div = document.createElement('div');
        div.id = 'moveMessage';
        div.style.cssText = 'position:absolute;top:10px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,0.8);color:#ff6600;padding:8px 16px;border-radius:5px;border:1px solid #ff6600;z-index:100;font-size:12px;';
        div.textContent = msg;
        document.getElementById('mapContainer').appendChild(div);
        
        setTimeout(function() {
            if (div.parentNode) div.remove();
        }, 1500);
    }
    
    function move(direction) {
        if (!canMove()) return;
        
        const tileSize = getTileSize();
        const moveDistance = 1; // Todos os jogadores andam sempre 1 quadrado
        let newX = player.x;
        let newY = player.y;
        
        switch(direction) {
            case 'up': newY -= moveDistance; break;
            case 'down': newY += moveDistance; break;
            case 'left': newX -= moveDistance; break;
            case 'right': newX += moveDistance; break;
        }
        
        // Limites rigorosos - garantir que nunca saia do mapa
        if (mapImage && mapImage.complete) {
            const maxPixelX = mapImage.width - tileSize;
            const maxPixelY = mapImage.height - tileSize;
            
            const newPixelX = newX * tileSize;
            const newPixelY = newY * tileSize;
            
            // Impedir que o personagem saia dos limites da imagem
            if (newPixelX < 0) newX = 0;
            if (newPixelY < 0) newY = 0;
            if (newPixelX > maxPixelX) newX = Math.floor(maxPixelX / tileSize);
            if (newPixelY > maxPixelY) newY = Math.floor(maxPixelY / tileSize);
        } else {
            // Fallback seguro
            if (newX < 0) newX = 0;
            if (newY < 0) newY = 0;
            const bounds = getMaxTiles();
            if (newX > bounds.maxX) newX = bounds.maxX;
            if (newY > bounds.maxY) newY = bounds.maxY;
        }
        
        player.x = newX;
        player.y = newY;
        lastMoveTime = Date.now();
        
        updateCamera();
        savePosition();
        render();
    }
    
    function savePosition() {
        $.ajax({
            url: '_inc/map_api.php',
            type: 'POST',
            data: {
                action: 'savePosition',
                x: player.x,
                y: player.y,
                mapaAtual: player.mapaAtual
            },
            dataType: 'json'
        });
    }
    
    
    function loadPlayers() {
        $.ajax({
            url: '_inc/map_api.php',
            type: 'GET',
            data: { action: 'getPlayers' },
            dataType: 'json',
            success: function(data) {
                if (data.players) {
                    players = {};
                    data.players.forEach(function(p) {
                        if (p.id != player.id) {
                            players[p.id] = p;
                        }
                    });
                    render();
                }
            }
        });
    }
    
    document.addEventListener('keydown', function(e) {
        if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') return;
        
        let direction = null;
        
        switch(e.key.toLowerCase()) {
            case 'w':
            case 'arrowup':
                direction = 'up';
                break;
            case 's':
            case 'arrowdown':
                direction = 'down';
                break;
            case 'a':
            case 'arrowleft':
                direction = 'left';
                break;
            case 'd':
            case 'arrowright':
                direction = 'right';
                break;
        }
        
        if (direction) {
            e.preventDefault();
            move(direction);
        }
    });
    
    function getClickTile(e, silent) {
        const rect = canvas.getBoundingClientRect();
        const tileSize = getTileSize();
        
        let clickX = e.clientX - rect.left;
        let clickY = e.clientY - rect.top;
        
        if (!silent) {
            console.log('Click bruto - clientX:', e.clientX, 'clientY:', e.clientY);
            console.log('Rect do canvas - left:', rect.left, 'top:', rect.top, 'width:', rect.width, 'height:', rect.height);
            console.log('Canvas lógico - width:', canvas.width, 'height:', canvas.height);
            console.log('Click relativo - clickX:', clickX, 'clickY:', clickY);
        }
        
        if (isWorldMap()) {
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            clickX *= scaleX;
            clickY *= scaleY;
            clickX += cameraX;
            clickY += cameraY;
            if (!silent) console.log('MapaBase - scaleX:', scaleX, 'scaleY:', scaleY, 'após escala - clickX:', clickX, 'clickY:', clickY);
        } else {
            const scaleX = mapImage.width / canvas.width;
            const scaleY = mapImage.height / canvas.height;
            clickX *= scaleX;
            clickY *= scaleY;
            if (!silent) console.log('Vila - scaleX:', scaleX, 'scaleY:', scaleY, 'após escala - clickX:', clickX, 'clickY:', clickY);
        }
        
        if (clickX < 0 || clickY < 0) {
            return { x: -1, y: -1 };
        }
        
        const tileX = Math.floor(clickX / tileSize);
        const tileY = Math.floor(clickY / tileSize);
        
        if (!silent) console.log('Tile final - tileX:', tileX, 'tileY:', tileY);
        
        return {
            x: tileX,
            y: tileY
        };
    }
    
    // Distancia maxima para CLICAR no portal (em tiles) - quanto maior, mais fácil clicar de longe
    const PORTAL_CLICK_DISTANCE = 10;
    // Distancia maxima para INTERAGIR com portal (player precisa estar perto) - o player precisa estar perto
    const PORTAL_INTERACTION_DISTANCE = 10;
    
    function getDistanceToTile(tx, ty) {
        return Math.abs(player.x - tx) + Math.abs(player.y - ty);
    }
    
    function findNearbyPortal(clickX, clickY) {
        const mapaConfig = config[player.mapaAtual];
        if (!mapaConfig) return null;
        
        let nearestPortal = null;
        let nearestDistance = PORTAL_CLICK_DISTANCE;
        
        // Verificar entradas
        if (mapaConfig.entradas) {
            for (var i = 0; i < mapaConfig.entradas.length; i++) {
                var e = mapaConfig.entradas[i];
                var dx = parseInt(e.x) - clickX;
                var dy = parseInt(e.y) - clickY;
                var dist = Math.abs(dx) + Math.abs(dy);
                
                if (dist <= nearestDistance) {
                    nearestDistance = dist;
                    nearestPortal = { type: 'entrada', portal: e };
                }
            }
        }
        
        // Verificar saidas
        if (mapaConfig.saidas) {
            for (var i = 0; i < mapaConfig.saidas.length; i++) {
                var s = mapaConfig.saidas[i];
                var dx = parseInt(s.x) - clickX;
                var dy = parseInt(s.y) - clickY;
                var dist = Math.abs(dx) + Math.abs(dy);
                
                if (dist <= nearestDistance) {
                    nearestDistance = dist;
                    nearestPortal = { type: 'saida', portal: s };
                }
            }
        }
        
        return nearestPortal;
    }
    
    function teleportPlayer(portalInfo) {
        var destino = portalInfo.portal.destino;
        var defaultCoord = (portalInfo.type === 'entrada' ? 5 : 50);
        
        // Usar Number.isNaN para permitir coordenadas 0
        var parsedX = parseInt(portalInfo.portal.destinoX);
        var parsedY = parseInt(portalInfo.portal.destinoY);
        var destinoX = Number.isNaN(parsedX) ? defaultCoord : parsedX;
        var destinoY = Number.isNaN(parsedY) ? defaultCoord : parsedY;
        
        console.log('Teleportando para:', destino, 'X:', destinoX, 'Y:', destinoY);
        
        player.mapaAtual = destino || 'MapaBase';
        player.x = destinoX;
        player.y = destinoY;
        
        loadMapImage(player.mapaAtual);
        savePosition();
    }
    
    canvas.addEventListener('click', function(e) {
        const tile = getClickTile(e);
        
        if (tile.x < 0 || tile.y < 0) return;
        
        // Verificar se clicou perto de um portal
        var portalInfo = findNearbyPortal(tile.x, tile.y);
        
        console.log('Clique no mapa - Tile:', tile.x, tile.y, 'Portal encontrado:', portalInfo !== null);
        
        if (portalInfo) {
            // Verificar se o PLAYER está perto do portal
            var portalX = parseInt(portalInfo.portal.x);
            var portalY = parseInt(portalInfo.portal.y);
            var playerDistance = Math.abs(player.x - portalX) + Math.abs(player.y - portalY);
            
            console.log('Portal: (' + portalX + ',' + portalY + ') - Player: (' + player.x + ',' + player.y + ') - Distância: ' + playerDistance);
            console.log('Distância máxima: ' + PORTAL_INTERACTION_DISTANCE);
            
            if (playerDistance <= PORTAL_INTERACTION_DISTANCE) {
                // Player está perto do portal - teleportar
                console.log('Teleportando...');
                teleportPlayer(portalInfo);
                return;
            } else {
                console.log('Player muito longe do portal');
            }
        } else {
            console.log('Nenhum portal encontrado perto do clique');
        }
    });
    
    canvas.addEventListener('mousemove', function(e) {
        if (!showGrid) return;
        var tile = getClickTile(e, true);
        if (tile.x !== hoverTile.x || tile.y !== hoverTile.y) {
            hoverTile = tile;
            render();
        }
    });
    
    canvas.addEventListener('mouseleave', function() {
        hoverTile = { x: -1, y: -1 };
        render();
    });
    
    canvas.addEventListener('contextmenu', function(e) {
        e.preventDefault();
    });
    
    window.toggleEditMode = function() {
        editMode = !editMode;
        document.getElementById('btnToggleEdit').textContent = editMode ? 'Desativar Editor' : 'Ativar Editor';
        document.getElementById('btnToggleEdit').classList.toggle('active', editMode);
        render();
    };
    
    window.setAction = function(action) {
        selectedAction = action;
        document.querySelectorAll('.action-btn').forEach(function(btn) {
            btn.classList.remove('active');
            if (btn.dataset.action === action) btn.classList.add('active');
        });
    };
    
    window.setDestino = function(destino) {
        selectedDestino = destino;
    };
    
    window.goToMap = function(mapa) {
        if (!mapa) return;
        player.mapaAtual = mapa;
        player.x = 5;
        player.y = 5;
        loadMapImage(mapa);
        savePosition();
        document.getElementById('selectMapa').value = '';
    };
    
    window.updateMoveDelay = function() {
        var input = document.getElementById('moveDelay');
        if (input) {
            var value = parseFloat(input.value) || 3;
            worldMapMoveDelay = value * 1000; // Converter para milissegundos
            alert('Tempo de movimento atualizado para ' + value + ' segundos');
        }
    };
    
    window.toggleAdminBypass = function() {
        var checkbox = document.getElementById('adminBypassMove');
        if (checkbox) {
            adminBypassMove = checkbox.checked;
            if (adminBypassMove) {
                alert('Admin bypass ativado - voce pode andar sem espera');
            } else {
                alert('Admin bypass desativado');
            }
        }
    };
    
    // Funcao para adicionar entrada usando campos de coordenadas
    window.addEntradaManual = function() {
        if (!editMode || !player.isAdmin) {
            alert('Ative o Modo Editor antes de criar portais');
            return;
        }
        
        if (!selectedDestino) {
            alert('Selecione um destino primeiro');
            return;
        }
        
        var portalXInput = document.getElementById('portalX');
        var portalYInput = document.getElementById('portalY');
        var destinoXInput = document.getElementById('destinoX');
        var destinoYInput = document.getElementById('destinoY');
        
        var portalX = parseInt(portalXInput.value);
        var portalY = parseInt(portalYInput.value);
        var destinoX = parseInt(destinoXInput.value);
        var destinoY = parseInt(destinoYInput.value);
        
        // Validar que os valores nao sao NaN
        if (isNaN(portalX) || isNaN(portalY)) {
            alert('Por favor, insira coordenadas validas para o portal');
            return;
        }
        if (isNaN(destinoX)) destinoX = 10;
        if (isNaN(destinoY)) destinoY = 10;
        
        $.ajax({
            url: '_inc/map_api.php',
            type: 'POST',
            data: {
                action: 'addEntrada',
                mapa: player.mapaAtual,
                x: portalX,
                y: portalY,
                destino: selectedDestino,
                destinoX: destinoX,
                destinoY: destinoY,
                icone: '_img/Icones_map/icone_vila.png'
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    config = data.config;
                    render();
                    alert('Entrada para ' + selectedDestino + ' criada em (' + portalX + ', ' + portalY + ') -> Destino: (' + destinoX + ', ' + destinoY + ')');
                } else {
                    alert('Erro: ' + (data.message || 'Falha ao criar entrada'));
                }
            },
            error: function(xhr, status, error) {
                alert('Erro de conexao: ' + error);
            }
        });
    };
    
    // Funcao para adicionar saida usando campos de coordenadas
    window.addSaidaManual = function() {
        if (!editMode || !player.isAdmin) {
            alert('Ative o Modo Editor antes de criar portais');
            return;
        }
        
        var portalXInput = document.getElementById('portalX');
        var portalYInput = document.getElementById('portalY');
        var destinoXInput = document.getElementById('destinoX');
        var destinoYInput = document.getElementById('destinoY');
        
        var portalX = parseInt(portalXInput.value);
        var portalY = parseInt(portalYInput.value);
        var destinoX = parseInt(destinoXInput.value);
        var destinoY = parseInt(destinoYInput.value);
        
        // Validar que os valores nao sao NaN
        if (isNaN(portalX) || isNaN(portalY)) {
            alert('Por favor, insira coordenadas validas para o portal');
            return;
        }
        if (isNaN(destinoX)) destinoX = 50;
        if (isNaN(destinoY)) destinoY = 50;
        
        $.ajax({
            url: '_inc/map_api.php',
            type: 'POST',
            data: {
                action: 'addSaida',
                mapa: player.mapaAtual,
                x: portalX,
                y: portalY,
                destino: 'MapaBase',
                destinoX: destinoX,
                destinoY: destinoY,
                icone: '_img/Icones_map/icone_vila.png'
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    config = data.config;
                    render();
                    alert('Saida criada em (' + portalX + ', ' + portalY + ') -> MapaBase: (' + destinoX + ', ' + destinoY + ')');
                } else {
                    alert('Erro: ' + (data.message || 'Falha ao criar saida'));
                }
            },
            error: function(xhr, status, error) {
                alert('Erro de conexao: ' + error);
            }
        });
    };
    
    window.removePortalManual = function() {
        if (!editMode || !player.isAdmin) {
            alert('Ative o Modo Editor antes de remover portais');
            return;
        }
        
        var removeXInput = document.getElementById('removePortalX');
        var removeYInput = document.getElementById('removePortalY');
        
        var removeX = parseInt(removeXInput.value);
        var removeY = parseInt(removeYInput.value);
        
        if (isNaN(removeX) || isNaN(removeY)) {
            alert('Por favor, insira coordenadas validas');
            return;
        }
        
        $.ajax({
            url: '_inc/map_api.php',
            type: 'POST',
            data: {
                action: 'removePortal',
                mapa: player.mapaAtual,
                x: removeX,
                y: removeY
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    config = data.config;
                    render();
                    alert('Portal removido de (' + removeX + ', ' + removeY + ')');
                } else {
                    alert('Nenhum portal encontrado nessa posicao');
                }
            },
            error: function(xhr, status, error) {
                alert('Erro de conexao: ' + error);
            }
        });
    };
    
    function handleAdminClick(x, y) {
        if (!selectedAction) {
            alert('Selecione uma acao primeiro');
            return;
        }
        
        if (selectedAction === 'entrada') {
            // Preencher campos com coordenadas do clique para facilitar
            document.getElementById('portalX').value = x;
            document.getElementById('portalY').value = y;
            alert('Coordenadas (' + x + ', ' + y + ') preenchidas. Configure o destino e clique em "Criar Entrada"');
            return;
            
        } else if (selectedAction === 'saida') {
            // Preencher campos com coordenadas do clique para facilitar
            document.getElementById('portalX').value = x;
            document.getElementById('portalY').value = y;
            alert('Coordenadas (' + x + ', ' + y + ') preenchidas. Configure e clique em "Criar Saida"');
            return;
            
        } else if (selectedAction === 'remover') {
            // Remover entrada ou saida no tile clicado
            $.ajax({
                url: '_inc/map_api.php',
                type: 'POST',
                data: {
                    action: 'removePortal',
                    mapa: player.mapaAtual,
                    x: x,
                    y: y
                },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        config = data.config;
                        render();
                        alert('Portal removido de (' + x + ', ' + y + ')');
                    } else {
                        alert('Nenhum portal encontrado nessa posicao');
                    }
                },
                error: function(xhr, status, error) {
                    alert('Erro de conexao: ' + error);
                }
            });
            
        } else if (selectedAction === 'teleport') {
            player.x = x;
            player.y = y;
            updateCamera();
            savePosition();
            render();
        }
    }
    
    function removePortalAt(x, y) {
        $.ajax({
            url: '_inc/map_api.php',
            type: 'POST',
            data: {
                action: 'removePortal',
                mapa: player.mapaAtual,
                x: x,
                y: y
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    config = data.config;
                    render();
                }
            }
        });
    }
    
    function renderMapLegend() {
        const legendContainer = document.getElementById('mapLegend');
        if (!legendContainer) return;
        
        const legendItems = [
            { icon: ICON_SELF, label: 'Você' },
            { icon: ICON_SAME_VILLAGE, label: 'Mesma Vila' },
            { icon: ICON_ALLY, label: 'Aliado' },
            { icon: ICON_ENEMY, label: 'Inimigo' },
            { icon: ICON_BOT, label: 'Bot' },
            { icon: ICON_PORTAL, label: 'Portal' }
        ];
        
        legendContainer.innerHTML = '';
        legendItems.forEach(function(item) {
            const div = document.createElement('div');
            div.className = 'legend-item';
            div.innerHTML = '<img src="' + item.icon + '" class="legend-icon" alt="' + item.label + '" style="width:20px; height:20px; margin-right:4px;"><span>' + item.label + '</span>';
            legendContainer.appendChild(div);
        });
    }
    
    // Renderizar legenda quando as imagens carregarem
    $(document).ready(function() {
        setTimeout(renderMapLegend, 500);
    });
    
    loadConfig();
    setInterval(loadPlayers, 3000);
    setInterval(renderMapLegend, 5000);
})();
</script>
