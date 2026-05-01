<?php 
require_once('trava.php'); 

// Verificar se $db['admin'] existe e definir valor padrão
$isAdmin = (isset($db['admin']) && $db['admin'] == 1) ? 1 : 0;

/**
 * Cria as tabelas do chat se não existirem (MySQL).
 */
function chat_garantir_tabelas(PDO $conexao): void {
    static $criadas = false;
    if ($criadas) return;
    $criadas = true;
    $sqls = [
        "CREATE TABLE IF NOT EXISTS chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            username VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS chat_bans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            username VARCHAR(50) NOT NULL,
            ban_type VARCHAR(10) NOT NULL,
            banned_until DATETIME NULL,
            banned_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS chat_online (
            user_id INT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            vila VARCHAR(50) NOT NULL,
            last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];
    foreach ($sqls as $sql) {
        try { $conexao->exec($sql); } catch (PDOException $e) {}
    }
}

// Processar ações AJAX primeiro, antes de qualquer HTML
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    chat_garantir_tabelas($conexao);

    // Formatação MySQL: HH:MM via DATE_FORMAT.
    $timeExpr    = "DATE_FORMAT(created_at, '%H:%i')";
    $replaceVerb = "REPLACE INTO";

    if ($_GET['action'] == 'load') {
        $lastId = intval($_GET['last']);
        $stmt = $conexao->prepare("SELECT id, username, message, $timeExpr as time 
                                   FROM chat_messages 
                                   WHERE id > ? 
                                   ORDER BY id ASC 
                                   LIMIT 50");
        $stmt->execute([$lastId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(array('messages' => $messages));
        exit;
    }

    if ($_GET['action'] == 'send' && $_POST) {
        // SEMPRE atualizar status online primeiro, independente de mensagem
        $stmtOn = $conexao->prepare("$replaceVerb chat_online (user_id, username, vila, last_seen) 
                                     VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
        $stmtOn->execute([$db['id'], $db['usuario'], $db['vila']]);

        $message = trim($_POST['message'] ?? '');

        // Se mensagem vazia, só atualiza status e retorna sucesso
        if ($message === '') {
            echo json_encode(array('success' => true));
            exit;
        }

        // Verificar se está banido do chat
        $stmtBan = $conexao->prepare("SELECT COUNT(*) FROM chat_bans 
                                      WHERE user_id = ? 
                                      AND ban_type IN ('chat', 'account') 
                                      AND (banned_until IS NULL OR banned_until > CURRENT_TIMESTAMP)");
        $stmtBan->execute([$db['id']]);
        if ((int)$stmtBan->fetchColumn() > 0) {
            echo json_encode(array('success' => false, 'error' => 'Você está banido do chat.'));
            exit;
        }

        // Verificar comandos de admin
        if ($isAdmin === 1 && substr($message, 0, 1) == '/') {
            $parts = explode(' ', $message, 3);
            $command = $parts[0];

            if ($command == '/banchat' || $command == '/ban') {
                if (count($parts) >= 3) {
                    $targetUser = $parts[1];
                    $timeStr = $parts[2];

                    $stmtT = $conexao->prepare("SELECT id FROM usuarios WHERE usuario = ?");
                    $stmtT->execute([$targetUser]);
                    $targetData = $stmtT->fetch(PDO::FETCH_ASSOC);

                    if ($targetData) {
                        $banType = ($command == '/banchat') ? 'chat' : 'account';

                        // Remover ban anterior se existir
                        $stmtDel = $conexao->prepare("DELETE FROM chat_bans WHERE user_id = ?");
                        $stmtDel->execute([$targetData['id']]);

                        // Calcular banned_until: NULL para -1, senão NOW()+N dias
                        if ($timeStr == '-1' || !is_numeric($timeStr)) {
                            $stmtIns = $conexao->prepare("INSERT INTO chat_bans (user_id, username, ban_type, banned_until, banned_by) 
                                                          VALUES (?, ?, ?, NULL, ?)");
                            $stmtIns->execute([$targetData['id'], $targetUser, $banType, $db['id']]);
                        } else {
                            $days = intval($timeStr);
                            $expr = Database::dateOffsetParam('+', 'days');
                            $stmtIns = $conexao->prepare("INSERT INTO chat_bans (user_id, username, ban_type, banned_until, banned_by) 
                                                          VALUES (?, ?, ?, $expr, ?)");
                            $stmtIns->execute([$targetData['id'], $targetUser, $banType, $days, $db['id']]);
                        }

                        $banMsg = ($timeStr == '-1') ? 'permanentemente' : "por $timeStr dias";
                        echo json_encode(array('success' => true, 'message' => "Usuário $targetUser banido $banMsg"));
                    } else {
                        echo json_encode(array('success' => false, 'error' => 'Usuário não encontrado'));
                    }
                } else {
                    $helpText = "Comandos de Ban:\n/banchat [usuário] [tempo] - Bane usuário apenas do chat\n/ban [usuário] [tempo] - Bane usuário do chat e conta\nTempo: número de dias ou -1 para ban eterno\nExemplo: /banchat jogador123 7";
                    echo json_encode(array('success' => false, 'error' => $helpText));
                }
                exit;
            }

            if ($command == '/unban') {
                if (count($parts) >= 2) {
                    $targetUser = $parts[1];
                    $stmtT = $conexao->prepare("SELECT id FROM usuarios WHERE usuario = ?");
                    $stmtT->execute([$targetUser]);
                    $targetData = $stmtT->fetch(PDO::FETCH_ASSOC);
                    if ($targetData) {
                        $stmtDel = $conexao->prepare("DELETE FROM chat_bans WHERE user_id = ?");
                        $stmtDel->execute([$targetData['id']]);
                        echo json_encode(array('success' => true, 'message' => "Usuário $targetUser desbanido"));
                    } else {
                        echo json_encode(array('success' => false, 'error' => 'Usuário não encontrado'));
                    }
                } else {
                    echo json_encode(array('success' => false, 'error' => 'Use: /unban [usuário]'));
                }
                exit;
            }
        }

        if (strlen($message) > 200) {
            echo json_encode(array('success' => false, 'error' => 'Mensagem muito longa!'));
            exit;
        }

        // Inserir mensagem
        try {
            $stmtMsg = $conexao->prepare("INSERT INTO chat_messages (user_id, username, message) VALUES (?, ?, ?)");
            $stmtMsg->execute([$db['id'], $db['usuario'], $message]);
            echo json_encode(array('success' => true));
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => 'Erro ao enviar mensagem'));
        }
        exit;
    }

    if ($_GET['action'] == 'delete' && $isAdmin == 1) {
        $days = max(0, intval($_GET['days']));
        try {
            $expr = Database::dateOffsetLiteral('-', $days, 'days');
            $conexao->exec("DELETE FROM chat_messages WHERE created_at < $expr");
            echo json_encode(array('success' => true));
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => 'Erro ao apagar mensagens'));
        }
        exit;
    }

    if ($_GET['action'] == 'online') {
        // Limpar usuários offline (mais de 2 minutos)
        try {
            $expr = Database::dateOffsetLiteral('-', 2, 'minutes');
            $conexao->exec("DELETE FROM chat_online WHERE last_seen < $expr");
        } catch (PDOException $e) {}

        $stmt = $conexao->query("SELECT username, vila FROM chat_online ORDER BY username ASC");
        $users = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        echo json_encode(array('users' => $users));
        exit;
    }
}
?>

<script>
let lastMessageId = 0;
let isAdmin = <?php echo ($isAdmin == 1) ? 'true' : 'false'; ?>;

function loadMessages() {
    fetch('?p=chat&action=load&last=' + lastMessageId)
        .then(response => response.json())
        .then(data => {
            if (data.messages && data.messages.length > 0) {
                let chatContent = document.getElementById('chat-content');
                data.messages.forEach(msg => {
                    let messageDiv = document.createElement('div');
                    messageDiv.className = 'chat-message';
                    messageDiv.innerHTML = `
                        <span class="chat-time">[${msg.time}]</span>
                        <span class="chat-username">${msg.username}:</span>
                        <span class="chat-text">${msg.message}</span>
                    `;
                    chatContent.appendChild(messageDiv);
                    lastMessageId = Math.max(lastMessageId, msg.id);
                });
                chatContent.scrollTop = chatContent.scrollHeight;
            }
        });
}

function loadOnlineUsers() {
    fetch('?p=chat&action=online')
        .then(response => response.json())
        .then(data => {
            if (data.users) {
                let onlineList = document.getElementById('online-users-list');
                onlineList.innerHTML = '';
                data.users.forEach(user => {
                    let userDiv = document.createElement('div');
                    userDiv.className = 'online-user';
                    userDiv.innerHTML = `
                        <img src="_img/vilas/${user.vila.toLowerCase()}.jpg" class="vila-icon" onerror="this.src='_img/vilas/folha.jpg'">
                        <span class="username">${user.username}</span>
                    `;
                    onlineList.appendChild(userDiv);
                });
                document.getElementById('online-count').textContent = data.users.length;
            }
        });
}

function sendMessage() {
    let messageInput = document.getElementById('message-input');
    let message = messageInput.value.trim();
    
    if (message.length === 0) {
        alert('Digite uma mensagem!');
        return;
    }
    if (message.length > 200) {
        alert('Mensagem muito longa! Máximo de 200 caracteres.');
        return;
    }
    
    fetch('?p=chat&action=send', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'message=' + encodeURIComponent(message)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageInput.value = '';
            loadMessages();
        } else {
            alert(data.error || 'Erro ao enviar mensagem');
        }
    });
}

function deleteMessages(days) {
    if (confirm(`Tem certeza que deseja apagar todas as mensagens dos últimos ${days} dias?`)) {
        fetch('?p=chat&action=delete&days=' + days, {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Mensagens apagadas com sucesso!');
                location.reload();
            } else {
                alert(data.error || 'Erro ao apagar mensagens');
            }
        });
    }
}

function showAdminPanel() {
    if (!isAdmin) {
        alert('Acesso negado! Somente administradores podem usar esta função.');
        return;
    }
    document.getElementById('admin-panel').style.display = 
        document.getElementById('admin-panel').style.display === 'none' ? 'block' : 'none';
}

// Carregar mensagens a cada 2 segundos
setInterval(loadMessages, 2000);
// Carregar usuários online a cada 5 segundos
setInterval(loadOnlineUsers, 5000);
// Carregar dados iniciais
window.onload = function() {
    // Registrar usuário online sem enviar mensagem
    fetch('?p=chat&action=send', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'message='
    });
    
    loadMessages();
    loadOnlineUsers();
}

// Enter para enviar mensagem
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('message-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
});
</script>

<style>
.chat-main-container {
    display: flex;
    width: 100%;
    height: 500px;
    border: 1px solid #333;
}

.chat-container {
    flex: 1;
    height: 100%;
    display: flex;
    flex-direction: column;
    background: #000;
}

.chat-sidebar {
    width: 200px;
    background: #1a1a1a;
    border-left: 1px solid #333;
    display: flex;
    flex-direction: column;
}

.sidebar-header {
    background: #333;
    color: #fff;
    padding: 10px;
    text-align: center;
    font-weight: bold;
    border-bottom: 1px solid #555;
}

.online-users-list {
    flex: 1;
    overflow-y: auto;
    padding: 10px;
}

.online-user {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    color: #fff;
}

.vila-icon {
    width: 20px;
    height: 20px;
    margin-right: 8px;
    border-radius: 3px;
}

.online-user .username {
    font-size: 12px;
}

.chat-header {
    background: #333;
    color: #fff;
    padding: 10px;
    border-bottom: 1px solid #555;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.admin-gear {
    cursor: pointer;
    font-size: 20px;
    color: #ffcc00;
    margin-left: 10px;
    transition: all 0.3s ease;
}

.admin-gear:hover {
    color: #fff;
    transform: rotate(90deg);
}

.chat-content {
    flex: 1;
    overflow-y: auto;
    padding: 10px;
    background: #000;
    color: #fff;
}

.chat-message {
    margin-bottom: 5px;
    word-wrap: break-word;
}

.chat-time {
    color: #888;
    font-size: 11px;
}

.chat-username {
    font-weight: bold;
    color: #66ccff;
}

.chat-text {
    color: #fff;
}

.chat-input-area {
    border-top: 1px solid #555;
    padding: 10px;
    background: #1a1a1a;
}

.chat-input {
    width: 80%;
    padding: 5px;
    border: 1px solid #555;
    background: #333;
    color: #fff;
}

.chat-send-btn {
    width: 18%;
    padding: 5px;
    background: #0066cc;
    color: white;
    border: none;
    cursor: pointer;
}

.admin-panel {
    display: none;
    background: #2a1a1a;
    color: #fff;
    padding: 10px;
    border: 1px solid #555;
    margin-top: 10px;
}

.admin-controls {
    margin-bottom: 10px;
}

.admin-controls button {
    margin-right: 5px;
    padding: 3px 8px;
    background: #cc4444;
    color: white;
    border: none;
    cursor: pointer;
}

.rules-box {
    background: #1a1a2e;
    color: #fff;
    padding: 10px;
    margin-bottom: 10px;
    border: 1px solid #555;
}

.command-help {
    background: #333322;
    color: #fff;
    padding: 8px;
    margin-top: 5px;
    border: 1px solid #666633;
    font-size: 11px;
}
</style>

<?php
// Garantir que as tabelas do chat existam (idempotente, cross-DB)
if (!isset($_GET['action'])) {
    chat_garantir_tabelas($conexao);
}
?>

<div class="box_top">Chat <?php echo nome_servidor(); ?></div>
<div class="box_middle">
    <div class="rules-box">
        <div class="sub2"><b>REGRAS</b><br />
        - Está proibido mencionar informações sobre outros jogos;<br />
        - Está proibido spam no chat;<br />
        - Os moderadores estão obrigados a banir qualquer jogador que não respeitar as regras.<br />
        - Limite de 200 caracteres por mensagem.
        </div>
    </div>
    
    <div class="chat-main-container">
        <div class="chat-container">
            <div class="chat-header">
                <span><strong>💬 Chat em Tempo Real</strong></span>
                <?php if ($isAdmin == 1): ?>
                <span class="admin-gear" onclick="showAdminPanel()" title="Opções de Administrador" style="cursor: pointer; font-size: 20px; color: #ffcc00; margin-left: 10px;">⚙️</span>
                <?php endif; ?>
            </div>
            
            <div class="chat-content" id="chat-content">
                <!-- Mensagens serão carregadas aqui -->
            </div>
            
            <div class="chat-input-area">
                <input type="text" id="message-input" class="chat-input" placeholder="Digite sua mensagem... (máx. 200 caracteres)" maxlength="200">
                <button onclick="sendMessage()" class="chat-send-btn">Enviar</button>
            </div>
        </div>
        
        <div class="chat-sidebar">
            <div class="sidebar-header">
                Online (<span id="online-count">0</span>)
            </div>
            <div class="online-users-list" id="online-users-list">
                <!-- Usuários online serão carregados aqui -->
            </div>
        </div>
    </div>
    
    <?php if ($isAdmin == 1): ?>
    <div id="admin-panel" class="admin-panel">
        <h4>⚙️ Painel do Administrador</h4>
        <div class="admin-controls">
            <strong>🗑️ Apagar Mensagens Automaticamente:</strong><br>
            <button onclick="deleteMessages(3)" style="background: #cc4444; color: white; padding: 5px 10px; margin: 2px; border: none; cursor: pointer;">🗑️ Últimos 3 dias</button>
            <button onclick="deleteMessages(5)" style="background: #cc6644; color: white; padding: 5px 10px; margin: 2px; border: none; cursor: pointer;">🗑️ Últimos 5 dias</button>
            <button onclick="deleteMessages(7)" style="background: #cc8844; color: white; padding: 5px 10px; margin: 2px; border: none; cursor: pointer;">🗑️ Últimos 7 dias</button>
        </div>
        <div class="command-help">
            <strong>📝 Comandos disponíveis no chat:</strong><br>
            <strong>/banchat [usuário] [dias]</strong> - Bane usuário apenas do chat (use -1 para ban eterno)<br>
            <strong>/ban [usuário] [dias]</strong> - Bane usuário do chat e da conta (use -1 para ban eterno)<br>
            <strong>/unban [usuário]</strong> - Remove qualquer ban do usuário<br>
            <em>Exemplo: /banchat spammer 7 (bane por 7 dias)</em>
        </div>
    </div>
    <?php endif; ?>
</div>
<div class="box_bottom"></div>