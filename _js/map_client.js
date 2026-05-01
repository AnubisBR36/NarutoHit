const MapGame = (function() {
    let socket = null;
    let canvas = null;
    let ctx = null;
    let mapImage = null;
    let player = null;
    let players = {};
    let config = {};
    let vilas = [];
    let vilaFiles = {};
    let icons = [];
    let isAdmin = false;
    let adminMode = false;
    let selectedIcon = null;
    let selectedAction = null;
    let selectedDestino = null;
    
    const CAMERA_OFFSET_X = 400;
    const CAMERA_OFFSET_Y = 300;
    
    function init(options) {
        canvas = document.getElementById(options.canvasId || 'mapCanvas');
        ctx = canvas.getContext('2d');
        isAdmin = options.isAdmin || false;
        
        const serverUrl = options.serverUrl || `${window.location.protocol}//${window.location.hostname}:3001`;
        socket = io(serverUrl);
        
        socket.on('connect', () => {
            console.log('Connected to map server');
            socket.emit('join', {
                odbuId: options.odbuId,
                nome: options.nome,
                avatar: options.avatar,
                vila: options.vila,
                isVIP: options.isVIP,
                isAdmin: options.isAdmin
            });
        });
        
        socket.on('init', (data) => {
            player = data.player;
            config = data.config;
            vilas = data.vilas;
            vilaFiles = data.vilaFiles;
            
            data.allPlayers.forEach(p => {
                players[p.id] = p;
            });
            
            loadMapImage(player.mapaAtual);
            loadIcons();
            
            if (isAdmin) {
                createAdminPanel();
            }
        });
        
        socket.on('playerJoined', (p) => {
            players[p.id] = p;
            render();
        });
        
        socket.on('playerLeft', (id) => {
            delete players[id];
            render();
        });
        
        socket.on('playerMoved', (data) => {
            if (players[data.id]) {
                players[data.id].x = data.x;
                players[data.id].y = data.y;
                players[data.id].mapaAtual = data.mapaAtual;
            }
            render();
        });
        
        socket.on('playerChangedMap', (data) => {
            if (players[data.id]) {
                players[data.id].mapaAtual = data.mapaAtual;
                players[data.id].x = data.x;
                players[data.id].y = data.y;
            }
            render();
        });
        
        socket.on('mapChanged', (data) => {
            player = data.player;
            players[socket.id] = player;
            loadMapImage(player.mapaAtual);
            updateMapInfo();
        });
        
        socket.on('configUpdated', (newConfig) => {
            config = newConfig;
            render();
        });
        
        setupControls();
    }
    
    function loadMapImage(mapaAtual) {
        const imgPath = vilaFiles[mapaAtual] || `_img/mapas_vilas/${mapaAtual}.jpg`;
        mapImage = new Image();
        mapImage.onload = () => {
            render();
        };
        mapImage.onerror = () => {
            console.error('Failed to load map image:', imgPath);
        };
        mapImage.src = imgPath;
    }
    
    function loadIcons() {
        fetch('/map/icons')
            .then(res => res.json())
            .then(data => {
                icons = data.icons || [];
                if (isAdmin) {
                    updateIconPanel();
                }
            });
    }
    
    function getTileSize() {
        if (!player) return 40;
        const mapaConfig = config[player.mapaAtual];
        if (mapaConfig && mapaConfig.tileSize) {
            return mapaConfig.tileSize;
        }
        return player.mapaAtual === 'MapaBase' ? 20 : 40;
    }
    
    function render() {
        if (!ctx || !mapImage || !mapImage.complete) return;
        
        const tileSize = getTileSize();
        
        ctx.fillStyle = '#1a1a1a';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        const cameraX = player ? (player.x * tileSize) - CAMERA_OFFSET_X : 0;
        const cameraY = player ? (player.y * tileSize) - CAMERA_OFFSET_Y : 0;
        
        ctx.save();
        ctx.translate(-cameraX, -cameraY);
        
        ctx.drawImage(mapImage, 0, 0);
        
        if (adminMode) {
            drawGrid(tileSize);
        }
        
        drawPortals(tileSize);
        
        drawPlayers(tileSize);
        
        ctx.restore();
        
        drawUI();
    }
    
    function drawGrid(tileSize) {
        if (!mapImage) return;
        
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.2)';
        ctx.lineWidth = 1;
        
        const cols = Math.ceil(mapImage.width / tileSize);
        const rows = Math.ceil(mapImage.height / tileSize);
        
        for (let x = 0; x <= cols; x++) {
            ctx.beginPath();
            ctx.moveTo(x * tileSize, 0);
            ctx.lineTo(x * tileSize, mapImage.height);
            ctx.stroke();
        }
        
        for (let y = 0; y <= rows; y++) {
            ctx.beginPath();
            ctx.moveTo(0, y * tileSize);
            ctx.lineTo(mapImage.width, y * tileSize);
            ctx.stroke();
        }
    }
    
    function drawPortals(tileSize) {
        if (!player || !config[player.mapaAtual]) return;
        
        const mapaConfig = config[player.mapaAtual];
        
        if (mapaConfig.entradas) {
            mapaConfig.entradas.forEach(entrada => {
                const x = entrada.x * tileSize;
                const y = entrada.y * tileSize;
                
                if (entrada.icone) {
                    const img = new Image();
                    img.src = entrada.icone;
                    ctx.drawImage(img, x, y, tileSize, tileSize);
                } else {
                    ctx.fillStyle = 'rgba(0, 255, 0, 0.5)';
                    ctx.fillRect(x, y, tileSize, tileSize);
                    ctx.strokeStyle = '#00ff00';
                    ctx.strokeRect(x, y, tileSize, tileSize);
                }
                
                if (adminMode) {
                    ctx.fillStyle = '#fff';
                    ctx.font = '10px Arial';
                    ctx.fillText(entrada.destino.substring(0, 10), x + 2, y + 12);
                }
            });
        }
        
        if (mapaConfig.saidas) {
            mapaConfig.saidas.forEach(saida => {
                const x = saida.x * tileSize;
                const y = saida.y * tileSize;
                
                if (saida.icone) {
                    const img = new Image();
                    img.src = saida.icone;
                    ctx.drawImage(img, x, y, tileSize, tileSize);
                } else {
                    ctx.fillStyle = 'rgba(255, 165, 0, 0.5)';
                    ctx.fillRect(x, y, tileSize, tileSize);
                    ctx.strokeStyle = '#ffa500';
                    ctx.strokeRect(x, y, tileSize, tileSize);
                }
                
                if (adminMode) {
                    ctx.fillStyle = '#fff';
                    ctx.font = '10px Arial';
                    ctx.fillText('Saída', x + 2, y + 12);
                }
            });
        }
    }
    
    function drawPlayers(tileSize) {
        if (!player) return;
        
        for (const id in players) {
            const p = players[id];
            if (p.mapaAtual !== player.mapaAtual) continue;
            
            const x = p.x * tileSize;
            const y = p.y * tileSize;
            const isCurrentPlayer = (id === socket.id);
            
            ctx.fillStyle = isCurrentPlayer ? '#ff6600' : '#3498db';
            ctx.beginPath();
            ctx.arc(x + tileSize/2, y + tileSize/2, tileSize/2.5, 0, Math.PI * 2);
            ctx.fill();
            
            ctx.strokeStyle = isCurrentPlayer ? '#cc5200' : '#2980b9';
            ctx.lineWidth = 2;
            ctx.stroke();
            
            ctx.fillStyle = '#fff';
            ctx.font = 'bold 12px Arial';
            ctx.textAlign = 'center';
            const name = p.nome || 'Jogador';
            ctx.fillText(name, x + tileSize/2, y - 5);
            
            if (p.isVIP) {
                ctx.fillStyle = '#ffd700';
                ctx.font = 'bold 10px Arial';
                ctx.fillText('★ VIP', x + tileSize/2, y - 18);
            }
        }
        
        ctx.textAlign = 'left';
    }
    
    function drawUI() {
        ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
        ctx.fillRect(10, 10, 200, 80);
        
        ctx.fillStyle = '#fff';
        ctx.font = '14px Arial';
        ctx.fillText(`Mapa: ${player ? player.mapaAtual : 'Carregando...'}`, 20, 30);
        ctx.fillText(`Posição: (${player ? player.x : 0}, ${player ? player.y : 0})`, 20, 50);
        ctx.fillText(`Jogadores: ${Object.values(players).filter(p => p.mapaAtual === (player ? player.mapaAtual : '')).length}`, 20, 70);
        
        if (adminMode) {
            ctx.fillStyle = 'rgba(255, 102, 0, 0.8)';
            ctx.fillRect(10, 100, 200, 30);
            ctx.fillStyle = '#fff';
            ctx.font = 'bold 14px Arial';
            ctx.fillText('MODO ADMIN ATIVO', 20, 120);
        }
    }
    
    function setupControls() {
        document.addEventListener('keydown', (e) => {
            if (!player || !socket) return;
            
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
                socket.emit('move', { direction });
            }
        });
        
        canvas.addEventListener('click', (e) => {
            if (!adminMode) return;
            
            const rect = canvas.getBoundingClientRect();
            const tileSize = getTileSize();
            
            const cameraX = player ? (player.x * tileSize) - CAMERA_OFFSET_X : 0;
            const cameraY = player ? (player.y * tileSize) - CAMERA_OFFSET_Y : 0;
            
            const clickX = e.clientX - rect.left + cameraX;
            const clickY = e.clientY - rect.top + cameraY;
            
            const tileX = Math.floor(clickX / tileSize);
            const tileY = Math.floor(clickY / tileSize);
            
            handleAdminClick(tileX, tileY);
        });
        
        canvas.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            if (!adminMode) return;
            
            const rect = canvas.getBoundingClientRect();
            const tileSize = getTileSize();
            
            const cameraX = player ? (player.x * tileSize) - CAMERA_OFFSET_X : 0;
            const cameraY = player ? (player.y * tileSize) - CAMERA_OFFSET_Y : 0;
            
            const clickX = e.clientX - rect.left + cameraX;
            const clickY = e.clientY - rect.top + cameraY;
            
            const tileX = Math.floor(clickX / tileSize);
            const tileY = Math.floor(clickY / tileSize);
            
            removePortalAt(tileX, tileY);
        });
    }
    
    function handleAdminClick(x, y) {
        if (!selectedAction) {
            showMessage('Selecione uma ação no painel admin primeiro');
            return;
        }
        
        if (selectedAction === 'entrada') {
            if (!selectedDestino) {
                showMessage('Selecione um destino primeiro');
                return;
            }
            
            fetch('/map/entrada/add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    mapa: player.mapaAtual,
                    x: x,
                    y: y,
                    destino: selectedDestino,
                    icone: selectedIcon,
                    isAdmin: true
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    config = data.config;
                    render();
                    showMessage(`Entrada para ${selectedDestino} criada em (${x}, ${y})`);
                }
            });
            
        } else if (selectedAction === 'saida') {
            const destinoX = prompt('X de destino no MapaBase:', '50');
            const destinoY = prompt('Y de destino no MapaBase:', '50');
            
            fetch('/map/saida/add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    mapa: player.mapaAtual,
                    x: x,
                    y: y,
                    destino: 'MapaBase',
                    destinoX: parseInt(destinoX) || 50,
                    destinoY: parseInt(destinoY) || 50,
                    icone: selectedIcon,
                    isAdmin: true
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    config = data.config;
                    render();
                    showMessage(`Saída para MapaBase criada em (${x}, ${y})`);
                }
            });
            
        } else if (selectedAction === 'teleport') {
            socket.emit('teleport', { x: x, y: y });
            showMessage(`Teleportado para (${x}, ${y})`);
        }
    }
    
    function removePortalAt(x, y) {
        const mapaConfig = config[player.mapaAtual];
        if (!mapaConfig) return;
        
        const hasEntrada = mapaConfig.entradas && mapaConfig.entradas.some(e => e.x === x && e.y === y);
        const hasSaida = mapaConfig.saidas && mapaConfig.saidas.some(s => s.x === x && s.y === y);
        
        if (hasEntrada) {
            fetch('/map/entrada/remove', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    mapa: player.mapaAtual,
                    x: x,
                    y: y,
                    isAdmin: true
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    config = data.config;
                    render();
                    showMessage(`Entrada removida de (${x}, ${y})`);
                }
            });
        }
        
        if (hasSaida) {
            fetch('/map/saida/remove', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    mapa: player.mapaAtual,
                    x: x,
                    y: y,
                    isAdmin: true
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    config = data.config;
                    render();
                    showMessage(`Saída removida de (${x}, ${y})`);
                }
            });
        }
    }
    
    function createAdminPanel() {
        const panel = document.createElement('div');
        panel.id = 'adminMapPanel';
        panel.innerHTML = `
            <style>
                #adminMapPanel {
                    position: fixed;
                    right: 10px;
                    top: 100px;
                    width: 250px;
                    background: rgba(26, 26, 26, 0.95);
                    border: 2px solid #ff6600;
                    border-radius: 10px;
                    padding: 15px;
                    color: #fff;
                    font-family: Arial, sans-serif;
                    z-index: 1000;
                    max-height: 80vh;
                    overflow-y: auto;
                }
                #adminMapPanel h3 {
                    color: #ff6600;
                    margin: 0 0 10px 0;
                    text-align: center;
                }
                #adminMapPanel .section {
                    margin-bottom: 15px;
                    padding: 10px;
                    background: rgba(255,255,255,0.1);
                    border-radius: 5px;
                }
                #adminMapPanel .section-title {
                    color: #ff6600;
                    font-weight: bold;
                    margin-bottom: 8px;
                }
                #adminMapPanel button {
                    width: 100%;
                    padding: 8px;
                    margin: 3px 0;
                    background: url('_img/fundo_botao.jpg');
                    border: 2px solid #654321;
                    color: #fff;
                    font-weight: bold;
                    cursor: pointer;
                    border-radius: 5px;
                }
                #adminMapPanel button:hover {
                    background: #ff6600;
                }
                #adminMapPanel button.active {
                    background: #ff6600;
                    border-color: #fff;
                }
                #adminMapPanel select {
                    width: 100%;
                    padding: 8px;
                    background: #333;
                    border: 1px solid #ff6600;
                    color: #fff;
                    border-radius: 5px;
                }
                #adminMapPanel .icon-grid {
                    display: grid;
                    grid-template-columns: repeat(4, 1fr);
                    gap: 5px;
                    margin-top: 10px;
                }
                #adminMapPanel .icon-item {
                    width: 40px;
                    height: 40px;
                    cursor: pointer;
                    border: 2px solid transparent;
                    border-radius: 5px;
                }
                #adminMapPanel .icon-item:hover {
                    border-color: #ff6600;
                }
                #adminMapPanel .icon-item.selected {
                    border-color: #00ff00;
                }
                #adminMapPanel .help {
                    font-size: 11px;
                    color: #999;
                    margin-top: 10px;
                }
            </style>
            <h3>🗺️ Editor de Mapas</h3>
            
            <div class="section">
                <div class="section-title">Modo Admin</div>
                <button id="btnToggleAdmin">Ativar Editor</button>
            </div>
            
            <div class="section">
                <div class="section-title">Ação</div>
                <button id="btnEntrada" class="action-btn">Criar Entrada</button>
                <button id="btnSaida" class="action-btn">Criar Saída</button>
                <button id="btnTeleport" class="action-btn">Teleportar</button>
            </div>
            
            <div class="section">
                <div class="section-title">Destino (para entradas)</div>
                <select id="selectDestino">
                    <option value="">Selecione...</option>
                </select>
            </div>
            
            <div class="section">
                <div class="section-title">Trocar Mapa</div>
                <select id="selectMapa">
                    <option value="">Ir para...</option>
                </select>
            </div>
            
            <div class="section">
                <div class="section-title">Ícones</div>
                <div id="iconGrid" class="icon-grid"></div>
            </div>
            
            <div class="help">
                <b>Controles:</b><br>
                - Clique esquerdo: Aplicar ação<br>
                - Clique direito: Remover portal<br>
                - WASD/Setas: Mover
            </div>
        `;
        
        document.body.appendChild(panel);
        
        document.getElementById('btnToggleAdmin').addEventListener('click', () => {
            adminMode = !adminMode;
            document.getElementById('btnToggleAdmin').textContent = adminMode ? 'Desativar Editor' : 'Ativar Editor';
            document.getElementById('btnToggleAdmin').classList.toggle('active', adminMode);
            render();
        });
        
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.action-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                selectedAction = e.target.id.replace('btn', '').toLowerCase();
            });
        });
        
        document.getElementById('selectDestino').addEventListener('change', (e) => {
            selectedDestino = e.target.value;
        });
        
        document.getElementById('selectMapa').addEventListener('change', (e) => {
            if (e.target.value && socket) {
                socket.emit('teleport', { x: 5, y: 5, mapa: e.target.value });
                e.target.value = '';
            }
        });
        
        updateAdminSelects();
    }
    
    function updateAdminSelects() {
        const selectDestino = document.getElementById('selectDestino');
        const selectMapa = document.getElementById('selectMapa');
        
        if (selectDestino && vilas.length > 0) {
            selectDestino.innerHTML = '<option value="">Selecione...</option>';
            vilas.forEach(vila => {
                selectDestino.innerHTML += `<option value="${vila}">${vila}</option>`;
            });
        }
        
        if (selectMapa && vilas.length > 0) {
            selectMapa.innerHTML = '<option value="">Ir para...</option>';
            vilas.forEach(vila => {
                selectMapa.innerHTML += `<option value="${vila}">${vila}</option>`;
            });
        }
    }
    
    function updateIconPanel() {
        const grid = document.getElementById('iconGrid');
        if (!grid) return;
        
        grid.innerHTML = '';
        icons.forEach(iconPath => {
            const img = document.createElement('img');
            img.src = iconPath;
            img.className = 'icon-item';
            img.title = iconPath.split('/').pop();
            img.addEventListener('click', () => {
                document.querySelectorAll('.icon-item').forEach(i => i.classList.remove('selected'));
                img.classList.add('selected');
                selectedIcon = iconPath;
            });
            grid.appendChild(img);
        });
    }
    
    function updateMapInfo() {
        updateAdminSelects();
        render();
    }
    
    function showMessage(msg) {
        const existing = document.getElementById('mapMessage');
        if (existing) existing.remove();
        
        const div = document.createElement('div');
        div.id = 'mapMessage';
        div.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.8);
            color: #fff;
            padding: 15px 25px;
            border-radius: 10px;
            border: 2px solid #ff6600;
            z-index: 2000;
            font-family: Arial, sans-serif;
        `;
        div.textContent = msg;
        document.body.appendChild(div);
        
        setTimeout(() => div.remove(), 3000);
    }
    
    return {
        init: init,
        render: render
    };
})();
