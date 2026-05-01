const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const cors = require('cors');
const fs = require('fs');
const path = require('path');

const app = express();
app.use(cors());
app.use(express.json());
app.use(express.static('.'));

const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"]
    }
});

const CONFIG_FILE = 'map_config.json';
const PLAYERS_FILE = 'map_players.json';

const VILAS = [
    "MapaBase",
    "Vila Akatsuki",
    "Vila Oculta da Areia",
    "Vila Oculta da Chuva",
    "Vila Oculta da Folha",
    "Vila Oculta da Névoa",
    "Vila Oculta da Nuvem",
    "Vila Oculta da Pedra"
];

const VILA_FILES = {
    "MapaBase": "_img/mapas_vilas/MapaBase.jpg",
    "Vila Akatsuki": "_img/mapas_vilas/Vila Akatsuki.jpg",
    "Vila Oculta da Areia": "_img/mapas_vilas/Vila Oculta da Areia.jpg",
    "Vila Oculta da Chuva": "_img/mapas_vilas/Vila Oculta da Chuva.jpg",
    "Vila Oculta da Folha": "_img/mapas_vilas/Vila Oculta da Folha.jpg",
    "Vila Oculta da Névoa": "_img/mapas_vilas/Vila Oculta da Névoa.jpg",
    "Vila Oculta da Nuvem": "_img/mapas_vilas/Vila Oculta da Nuvem.jpg",
    "Vila Oculta da Pedra": "_img/mapas_vilas/Vila Oculta da Pedra.jpg"
};

let players = {};
let mapConfig = loadMapConfig();

function loadMapConfig() {
    try {
        if (fs.existsSync(CONFIG_FILE)) {
            return JSON.parse(fs.readFileSync(CONFIG_FILE, 'utf8'));
        }
    } catch (e) {
        console.error('Error loading map config:', e);
    }
    return {
        "MapaBase": {
            "tileSize": 20,
            "entradas": [],
            "bloqueados": []
        },
        "Vila Akatsuki": { "tileSize": 40, "saidas": [], "bloqueados": [] },
        "Vila Oculta da Areia": { "tileSize": 40, "saidas": [], "bloqueados": [] },
        "Vila Oculta da Chuva": { "tileSize": 40, "saidas": [], "bloqueados": [] },
        "Vila Oculta da Folha": { "tileSize": 40, "saidas": [], "bloqueados": [] },
        "Vila Oculta da Névoa": { "tileSize": 40, "saidas": [], "bloqueados": [] },
        "Vila Oculta da Nuvem": { "tileSize": 40, "saidas": [], "bloqueados": [] },
        "Vila Oculta da Pedra": { "tileSize": 40, "saidas": [], "bloqueados": [] }
    };
}

function saveMapConfig() {
    try {
        fs.writeFileSync(CONFIG_FILE, JSON.stringify(mapConfig, null, 2));
        return true;
    } catch (e) {
        console.error('Error saving map config:', e);
        return false;
    }
}

function savePlayers() {
    try {
        const playerData = {};
        for (const id in players) {
            playerData[players[id].odbuId] = {
                odbuId: players[id].odbuId,
                x: players[id].x,
                y: players[id].y,
                mapaAtual: players[id].mapaAtual
            };
        }
        fs.writeFileSync(PLAYERS_FILE, JSON.stringify(playerData, null, 2));
    } catch (e) {
        console.error('Error saving players:', e);
    }
}

function loadPlayerPosition(odbuId) {
    try {
        if (fs.existsSync(PLAYERS_FILE)) {
            const data = JSON.parse(fs.readFileSync(PLAYERS_FILE, 'utf8'));
            if (data[odbuId]) {
                return data[odbuId];
            }
        }
    } catch (e) {
        console.error('Error loading player position:', e);
    }
    return { x: 50, y: 50, mapaAtual: "MapaBase" };
}

app.get('/map/config', (req, res) => {
    res.json({
        config: mapConfig,
        vilas: VILAS,
        vilaFiles: VILA_FILES
    });
});

app.get('/map/icons', (req, res) => {
    const iconsDir = '_img/Icones_map';
    try {
        const files = fs.readdirSync(iconsDir);
        const icons = files.filter(f => f.endsWith('.png') || f.endsWith('.jpg'));
        res.json({ icons: icons.map(f => `${iconsDir}/${f}`) });
    } catch (e) {
        res.json({ icons: [] });
    }
});

app.post('/map/save', (req, res) => {
    const { config, isAdmin } = req.body;
    if (!isAdmin) {
        return res.status(403).json({ success: false, message: 'Acesso negado' });
    }
    mapConfig = { ...mapConfig, ...config };
    const success = saveMapConfig();
    io.emit('configUpdated', mapConfig);
    res.json({ success, config: mapConfig });
});

app.post('/map/entrada/add', (req, res) => {
    const { mapa, x, y, destino, icone, isAdmin } = req.body;
    if (!isAdmin) {
        return res.status(403).json({ success: false, message: 'Acesso negado' });
    }
    if (!mapConfig[mapa]) {
        mapConfig[mapa] = { entradas: [], saidas: [], bloqueados: [] };
    }
    if (!mapConfig[mapa].entradas) {
        mapConfig[mapa].entradas = [];
    }
    const existing = mapConfig[mapa].entradas.findIndex(e => e.x === x && e.y === y);
    if (existing >= 0) {
        mapConfig[mapa].entradas[existing] = { x, y, destino, icone };
    } else {
        mapConfig[mapa].entradas.push({ x, y, destino, icone });
    }
    saveMapConfig();
    io.emit('configUpdated', mapConfig);
    res.json({ success: true, config: mapConfig });
});

app.post('/map/saida/add', (req, res) => {
    const { mapa, x, y, destino, destinoX, destinoY, icone, isAdmin } = req.body;
    if (!isAdmin) {
        return res.status(403).json({ success: false, message: 'Acesso negado' });
    }
    if (!mapConfig[mapa]) {
        mapConfig[mapa] = { entradas: [], saidas: [], bloqueados: [] };
    }
    if (!mapConfig[mapa].saidas) {
        mapConfig[mapa].saidas = [];
    }
    const existing = mapConfig[mapa].saidas.findIndex(e => e.x === x && e.y === y);
    if (existing >= 0) {
        mapConfig[mapa].saidas[existing] = { x, y, destino, destinoX, destinoY, icone };
    } else {
        mapConfig[mapa].saidas.push({ x, y, destino, destinoX, destinoY, icone });
    }
    saveMapConfig();
    io.emit('configUpdated', mapConfig);
    res.json({ success: true, config: mapConfig });
});

app.post('/map/entrada/remove', (req, res) => {
    const { mapa, x, y, isAdmin } = req.body;
    if (!isAdmin) {
        return res.status(403).json({ success: false, message: 'Acesso negado' });
    }
    if (mapConfig[mapa] && mapConfig[mapa].entradas) {
        mapConfig[mapa].entradas = mapConfig[mapa].entradas.filter(e => !(e.x === x && e.y === y));
        saveMapConfig();
        io.emit('configUpdated', mapConfig);
    }
    res.json({ success: true, config: mapConfig });
});

app.post('/map/saida/remove', (req, res) => {
    const { mapa, x, y, isAdmin } = req.body;
    if (!isAdmin) {
        return res.status(403).json({ success: false, message: 'Acesso negado' });
    }
    if (mapConfig[mapa] && mapConfig[mapa].saidas) {
        mapConfig[mapa].saidas = mapConfig[mapa].saidas.filter(e => !(e.x === x && e.y === y));
        saveMapConfig();
        io.emit('configUpdated', mapConfig);
    }
    res.json({ success: true, config: mapConfig });
});

app.get('/map/players', (req, res) => {
    res.json({ players: Object.values(players) });
});

io.on('connection', (socket) => {
    console.log('Player connected:', socket.id);

    socket.on('join', (data) => {
        const savedPos = loadPlayerPosition(data.odbuId);
        players[socket.id] = {
            id: socket.id,
            odbuId: data.odbuId,
            nome: data.nome || 'Jogador',
            avatar: data.avatar || '_img/personagens/no_avatar.jpg',
            vila: data.vila || 'Folha',
            isVIP: data.isVIP || false,
            isAdmin: data.isAdmin || false,
            x: savedPos.x,
            y: savedPos.y,
            mapaAtual: savedPos.mapaAtual
        };
        
        socket.emit('init', {
            player: players[socket.id],
            config: mapConfig,
            vilas: VILAS,
            vilaFiles: VILA_FILES,
            allPlayers: Object.values(players)
        });
        
        socket.broadcast.emit('playerJoined', players[socket.id]);
    });

    socket.on('move', (data) => {
        if (players[socket.id]) {
            const player = players[socket.id];
            const config = mapConfig[player.mapaAtual] || {};
            const tileSize = config.tileSize || (player.mapaAtual === 'MapaBase' ? 20 : 40);
            const moveDistance = player.isVIP ? 2 : 1;
            
            let newX = player.x;
            let newY = player.y;
            
            switch(data.direction) {
                case 'up': newY -= moveDistance; break;
                case 'down': newY += moveDistance; break;
                case 'left': newX -= moveDistance; break;
                case 'right': newX += moveDistance; break;
            }
            
            if (newX < 0) newX = 0;
            if (newY < 0) newY = 0;
            
            const bloqueados = config.bloqueados || [];
            const isBlocked = bloqueados.some(b => b.x === newX && b.y === newY);
            
            if (!isBlocked) {
                player.x = newX;
                player.y = newY;
                savePlayers();
                
                io.emit('playerMoved', {
                    id: socket.id,
                    x: player.x,
                    y: player.y,
                    mapaAtual: player.mapaAtual
                });
                
                const entradas = config.entradas || [];
                const entrada = entradas.find(e => e.x === player.x && e.y === player.y);
                if (entrada) {
                    player.mapaAtual = entrada.destino;
                    player.x = entrada.destinoX || 5;
                    player.y = entrada.destinoY || 5;
                    savePlayers();
                    
                    socket.emit('mapChanged', {
                        player: player,
                        config: mapConfig[player.mapaAtual]
                    });
                    
                    io.emit('playerChangedMap', {
                        id: socket.id,
                        mapaAtual: player.mapaAtual,
                        x: player.x,
                        y: player.y
                    });
                }
                
                const saidas = config.saidas || [];
                const saida = saidas.find(s => s.x === player.x && s.y === player.y);
                if (saida) {
                    player.mapaAtual = saida.destino;
                    player.x = saida.destinoX || 50;
                    player.y = saida.destinoY || 50;
                    savePlayers();
                    
                    socket.emit('mapChanged', {
                        player: player,
                        config: mapConfig[player.mapaAtual]
                    });
                    
                    io.emit('playerChangedMap', {
                        id: socket.id,
                        mapaAtual: player.mapaAtual,
                        x: player.x,
                        y: player.y
                    });
                }
            }
        }
    });

    socket.on('teleport', (data) => {
        if (players[socket.id] && players[socket.id].isAdmin) {
            players[socket.id].x = data.x;
            players[socket.id].y = data.y;
            if (data.mapa) {
                players[socket.id].mapaAtual = data.mapa;
            }
            savePlayers();
            
            socket.emit('mapChanged', {
                player: players[socket.id],
                config: mapConfig[players[socket.id].mapaAtual]
            });
            
            io.emit('playerMoved', {
                id: socket.id,
                x: players[socket.id].x,
                y: players[socket.id].y,
                mapaAtual: players[socket.id].mapaAtual
            });
        }
    });

    socket.on('disconnect', () => {
        console.log('Player disconnected:', socket.id);
        if (players[socket.id]) {
            savePlayers();
            delete players[socket.id];
            io.emit('playerLeft', socket.id);
        }
    });
});

const PORT = 3001;
server.listen(PORT, '0.0.0.0', () => {
    console.log(`Map server running on port ${PORT}`);
});
