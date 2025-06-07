<?php
require_once('verificar.php');

// Processar envio de mensagem
if(isset($_POST['sub2'])) {
    $msg_origem = $db['id'];
    $msg_destino = trim($_POST['msg_destino']);
    $msg_assunto = trim($_POST['msg_assunto']);
    $msg_msg = trim($_POST['msg_msg']);

    if(empty($msg_destino) || empty($msg_assunto) || empty($msg_msg)) {
        $erro = "Preencha todos os campos obrigatórios.";
    } else {
        // Verificar se o destinatário existe
        $destinatarios = explode(',', $msg_destino);
        $destinatarios_validos = array();

        foreach($destinatarios as $dest) {
            $dest = trim($dest);
            if(!empty($dest)) {
                try {
                    $sql_check = $conexao->prepare("SELECT id FROM usuarios WHERE usuario = ?");
                    $sql_check->execute([$dest]);
                    $user_found = $sql_check->fetch(PDO::FETCH_ASSOC);
                    if($user_found) {
                        $destinatarios_validos[] = $user_found;
                    }
                } catch (PDOException $e) {
                    error_log("Erro ao verificar destinatário: " . $e->getMessage());
                }
            }
        }

        if(empty($destinatarios_validos)) {
            $erro = "Nenhum destinatário válido encontrado.";
        } else {
            // Enviar mensagem para cada destinatário válido
            $sucesso = true;
            foreach($destinatarios_validos as $dest_data) {
                try {
                    $sql_insert = $conexao->prepare("INSERT INTO mensagens (origem, destino, assunto, msg, data, status) VALUES (?, ?, ?, ?, datetime('now'), 'naolido')");
                    $result = $sql_insert->execute([
                        $msg_origem,
                        $dest_data['id'],
                        $msg_assunto,
                        substr($msg_msg, 0, 2048)
                    ]);

                    if(!$result) {
                        $sucesso = false;
                        break;
                    }
                } catch (PDOException $e) {
                    error_log("Erro ao inserir mensagem: " . $e->getMessage());
                    $sucesso = false;
                    break;
                }
            }

            if($sucesso) {
                $sucesso_msg = "✅ Mensagem enviada com sucesso para ".count($destinatarios_validos)." destinatário(s)!";
                // Redirecionar para limpar POST e mostrar mensagem de sucesso
                header("Location: ?p=messages&aba=escrever&sucesso=1");
                exit;
            } else {
                $erro = "❌ Erro ao enviar mensagem. Tente novamente.";
            }
        }
    }
}

// Deletar mensagem
if(isset($_GET['del']) && is_numeric($_GET['del'])) {
    $del_id = (int)$_GET['del'];
    try {
        $sql_del = $conexao->prepare("DELETE FROM mensagens WHERE id = ? AND destino = ?");
        $sql_del->execute([$del_id, $db['id']]);
    } catch (PDOException $e) {
        error_log("Erro ao deletar mensagem: " . $e->getMessage());
    }
}

// Estatísticas de mensagens
try {
    $sql_total = $conexao->prepare("SELECT COUNT(id) as total FROM mensagens WHERE destino = ?");
    $sql_total->execute([$db['id']]);
    $total_row = $sql_total->fetch(PDO::FETCH_ASSOC);
    $total_mensagens = $total_row ? $total_row['total'] : 0;

    $sql_nao_lidas = $conexao->prepare("SELECT COUNT(id) as total FROM mensagens WHERE destino = ? AND status = 'naolido'");
    $sql_nao_lidas->execute([$db['id']]);
    $nao_lidas_row = $sql_nao_lidas->fetch(PDO::FETCH_ASSOC);
    $mensagens_nao_lidas = $nao_lidas_row ? $nao_lidas_row['total'] : 0;
} catch (PDOException $e) {
    error_log("Erro ao obter estatísticas: " . $e->getMessage());
    $total_mensagens = 0;
    $mensagens_nao_lidas = 0;
}

// Determinar qual aba mostrar
$aba_ativa = isset($_GET['aba']) ? $_GET['aba'] : 'escrever';

// Verificar se deve incluir o arquivo antigo
if(!isset($_GET['aba']) && (isset($_GET['type']) || isset($_GET['destiny']) || isset($_GET['subject']))) {
    if($_GET['type'] == 'form' || isset($_GET['destiny']) || isset($_GET['subject'])) {
        include('messages_form.php');
        return;
    } elseif($_GET['type'] == 'r') {
        include('messages_r.php');
        return;
    } elseif($_GET['type'] == 'e') {
        include('messages_e.php');
        return;
    } else {
        include('messages_main.php');
        return;
    }
}
?>

<style>
.messages-container {
    background: #1a1a1a;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 6px 20px rgba(0,0,0,0.4);
    max-width: 1200px;
    margin: 0 auto;
}

.messages-header {
    background: linear-gradient(135deg, #2d2d2d, #1f1f1f);
    padding: 15px 20px;
    border-bottom: 2px solid #333;
}

.messages-title {
    color: #fff;
    font-size: 14px;
    font-weight: bold;
    margin: 0;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
    letter-spacing: 0.5px;
}

.messages-tabs {
    display: flex;
    background: #252525;
    border-bottom: 1px solid #333;
    flex-wrap: wrap;
}

.tab-button {
    flex: 1;
    min-width: 60px;
    padding: 6px 10px;
    background: #2a2a2a;
    color: #ccc;
    border: none;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    transition: all 0.3s ease;
    border-right: 1px solid #333;
    font-size: 10px;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.tab-button:hover {
    background: #333;
    color: #fff;
    transform: translateY(-1px);
}

.tab-button.active {
    background: #4CAF50;
    color: #fff;
    font-weight: bold;
}

@media (max-width: 768px) {
    .tab-button {
        flex: 1 1 50%;
        min-width: auto;
        font-size: 11px;
        padding: 10px 8px;
    }

    .messages-title {
        font-size: 16px;
    }

    .messages-header {
        padding: 12px 15px;
    }
}

@media (max-width: 480px) {
    .tab-button {
        flex: 1 1 100%;
        border-right: none;
        border-bottom: 1px solid #333;
        font-size: 10px;
        padding: 8px 6px;
    }

    .messages-title {
        font-size: 14px;
    }

    .messages-header {
        padding: 10px 12px;
    }
}

.messages-content {
    padding: 20px;
    min-height: 400px;
    background: #1a1a1a;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.stat-card {
    background: linear-gradient(135deg, #2d2d2d, #1f1f1f);
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    border: 1px solid #333;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-number {
    font-size: 28px;
    font-weight: bold;
    color: #4CAF50;
    margin-bottom: 10px;
}

.stat-label {
    color: #ccc;
    font-size: 14px;
    font-weight: 500;
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.action-card {
    background: linear-gradient(135deg, #2d2d2d, #1f1f1f);
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    border: 1px solid #333;
    transition: transform 0.3s ease;
}

.action-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}

.action-icon {
    font-size: 36px;
    margin-bottom: 12px;
}

.action-title {
    color: #fff;
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 10px;
}

.action-desc {
    color: #ccc;
    font-size: 14px;
    margin-bottom: 18px;
    line-height: 1.4;
}

.btn-action {
    background: linear-gradient(135deg, #4CAF50, #45a049);
    color: white;
    padding: 8px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
    font-weight: bold;
    font-size: 12px;
}

.btn-action:hover {
    background: linear-gradient(135deg, #45a049, #4CAF50);
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
}

.btn-secondary {
    background: linear-gradient(135deg, #2196F3, #1976D2);
}

.btn-secondary:hover {
    background: linear-gradient(135deg, #1976D2, #2196F3);
    box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
}

.form-container {
    background: #2a2a2a;
    border-radius: 8px;
    padding: 20px;
    border: 1px solid #333;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.form-group {
    margin-bottom: 25px;
}

.form-label {
    display: block;
    color: #fff;
    font-weight: bold;
    margin-bottom: 10px;
    font-size: 15px;
}

.form-input, .form-textarea {
    width: 100%;
    padding: 14px 16px;
    background: #1a1a1a;
    border: 2px solid #333;
    border-radius: 6px;
    color: #fff;
    font-size: 15px;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}

.form-input:focus, .form-textarea:focus {
    border-color: #4CAF50;
    outline: none;
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
}

.form-textarea {
    min-height: 140px;
    resize: vertical;
    font-family: inherit;
}

.form-help {
    color: #888;
    font-size: 13px;
    margin-top: 8px;
}

.alert {
    padding: 18px 20px;
    border-radius: 6px;
    margin-bottom: 25px;
    font-size: 15px;
    font-weight: 500;
}

.alert-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.alert-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.messages-table {
    width: 100%;
    background: #2a2a2a;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #333;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.messages-table th {
    background: #333;
    color: #fff;
    padding: 18px 20px;
    text-align: left;
    font-weight: bold;
    font-size: 15px;
}

.messages-table td {
    padding: 16px 20px;
    border-bottom: 1px solid #333;
    color: #ccc;
    font-size: 14px;
    vertical-align: middle;
}

.messages-table tr:hover {
    background: #333;
}

.msg-status {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: inline-block;
}

.msg-unread {
    background: #ff6b6b;
    box-shadow: 0 0 8px rgba(255, 107, 107, 0.4);
}

.msg-read {
    background: #51cf66;
}

.pagination {
    text-align: center;
    margin-top: 25px;
}

.pagination a {
    color: #4CAF50;
    text-decoration: none;
    margin: 0 15px;
    padding: 10px 15px;
    border-radius: 5px;
    transition: all 0.3s ease;
    font-weight: 500;
    font-size: 15px;
}

.pagination a:hover {
    background: #4CAF50;
    color: #fff;
    text-decoration: none;
    transform: translateY(-1px);
}

/* Melhorar a legibilidade geral */
h1, h2, h3 {
    color: #fff;
    line-height: 1.3;
}

h2 {
    font-size: 22px;
    margin-bottom: 20px;
}

/* Responsividade melhorada */
@media (max-width: 768px) {
    .messages-content {
        padding: 15px;
    }

    .form-container {
        padding: 15px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .action-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .messages-content {
        padding: 12px;
    }

    .form-container {
        padding: 12px;
    }

    .messages-table th,
    .messages-table td {
        padding: 8px 10px;
        font-size: 11px;
    }

    .btn-action {
        padding: 6px 12px;
        font-size: 11px;
    }
}
</style>

<div class="messages-container">
    <div class="messages-header">
        <h1 class="messages-title">📮 Correios</h1>
    </div>

    <div class="messages-tabs">
        <a href="?p=messages&aba=escrever" class="tab-button <?php echo ($aba_ativa == 'escrever') ? 'active' : ''; ?>">
            Enviar
        </a>
        <a href="?p=messages&aba=recebidas" class="tab-button <?php echo ($aba_ativa == 'recebidas') ? 'active' : ''; ?>">
            Recebidas (<?php echo $mensagens_nao_lidas; ?>)
        </a>
        <a href="?p=messages&aba=enviadas" class="tab-button <?php echo ($aba_ativa == 'enviadas') ? 'active' : ''; ?>">
            Enviadas
        </a>
    </div>

    <div class="messages-content">
        <?php if(isset($sucesso_msg)): ?>
            <div class="alert alert-success">✅ <?php echo $sucesso_msg; ?></div>
        <?php endif; ?>

        <?php if(isset($_GET['sucesso']) && $_GET['sucesso'] == '1'): ?>
            <div class="alert alert-success">✅ Mensagem enviada com sucesso!</div>
        <?php endif; ?>

        <?php if(isset($erro)): ?>
            <div class="alert alert-error">❌ <?php echo $erro; ?></div>
        <?php endif; ?>

        <?php if($aba_ativa == 'escrever'): ?>
            <div class="form-container">
                <h2 style="color: #fff; margin-top: 0; font-size: 12px;">Enviar Mensagem</h2>
                <form method="post" action="?p=messages&aba=escrever">
                    <div class="form-group">
                        <label class="form-label" for="msg_destino">Destinatário(s):</label>
                        <input type="text" id="msg_destino" name="msg_destino" class="form-input" placeholder="Digite o nome do usuário" maxlength="159" required>
                        <div class="form-help">Para múltiplos destinatários, separe os nomes por vírgula (máximo 10 usuários)</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="msg_assunto">Assunto:</label>
                        <input type="text" id="msg_assunto" name="msg_assunto" class="form-input" placeholder="Digite o assunto da mensagem" maxlength="60" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="msg_msg">Mensagem:</label>
                        <textarea id="msg_msg" name="msg_msg" class="form-textarea" placeholder="Digite sua mensagem aqui..." required></textarea>
                        <div class="form-help">Máximo de 2048 caracteres</div>
                    </div>

                    <button type="submit" name="sub2" class="btn-action" id="btn-enviar">📨 Enviar Mensagem</button>
                </form>
            </div>

        <?php elseif($aba_ativa == 'recebidas'): ?>
            <?php
            $pg = isset($_GET['pg']) ? (int)$_GET['pg'] : 0;
            $min = $pg * 10;

            try {
                $sql_messages = $conexao->prepare("SELECT m.*, u.usuario FROM mensagens m 
                    LEFT JOIN usuarios u ON m.origem = u.id 
                    WHERE m.destino = ? 
                    ORDER BY m.status DESC, m.data DESC 
                    LIMIT 10 OFFSET ?");
                $sql_messages->execute([$db['id'], $min]);
            } catch (PDOException $e) {
                error_log("Erro ao buscar mensagens: " . $e->getMessage());
                $sql_messages = false;
            }
            ?>

            <h2 style="color: #fff; margin-top: 0; font-size: 12px;">Caixa de Entrada</h2>

            <?php 
            $mensagens_encontradas = false;
            $mensagens_array = [];
            if($sql_messages) {
                while($msg = $sql_messages->fetch(PDO::FETCH_ASSOC)) {
                    $mensagens_array[] = $msg;
                    $mensagens_encontradas = true;
                }
            }

            if(!$mensagens_encontradas): ?>
                <div style="text-align: center; padding: 40px; color: #888;">
                    📭 Nenhuma mensagem encontrada.
                </div>
            <?php else: ?>
                <table class="messages-table">
                    <thead>
                        <tr>
                            <th width="30">Status</th>
                            <th width="100">Data</th>
                            <th>De / Assunto</th>
                            <th width="120">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($mensagens_array as $msg): ?>
                            <?php $data = explode(' ', $msg['data']); ?>
                            <tr>
                                <td>
                                    <span class="msg-status <?php echo ($msg['status'] == 'naolido') ? 'msg-unread' : 'msg-read'; ?>" title="<?php echo ($msg['status'] == 'naolido') ? 'Não lida' : 'Lida'; ?>"></span>
                                </td>
                                <td>
                                    <?php echo ($data[0] == date('Y-m-d')) ? '<b>Hoje</b>' : date('d/m/Y', strtotime($data[0])); ?><br>
                                    <small><?php echo date('H:i:s', strtotime($data[1])); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo ($msg['origem'] == 0) ? 'narutoHIT' : $msg['usuario']; ?></strong><br>
                                    <small style="color: #888;"><?php echo htmlspecialchars($msg['assunto']); ?></small>
                                </td>
                                <td>
                                    <a href="search_msg.php?id=<?php echo $msg['id']; ?>&key=<?php echo $c->encode($db['id'], $chaveuniversal); ?>" 
                                       class="modal" rel="modal" style="color: #4CAF50; text-decoration: none; margin-right: 10px;">Ver</a>
                                    <a href="?p=messages&aba=recebidas&pg=<?php echo $pg; ?>&del=<?php echo $msg['id']; ?>" 
                                       style="color: #ff6b6b; text-decoration: none;" 
                                       onclick="return confirm('Tem certeza que deseja excluir esta mensagem?')">Excluir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if($total_mensagens > 10): ?>
                    <div class="pagination">
                        <?php if($pg > 0): ?>
                            <a href="?p=messages&aba=recebidas&pg=<?php echo $pg-1; ?>">« Anterior</a>
                        <?php endif; ?>

                        <?php if(($pg+1)*10 < $total_mensagens): ?>
                            <a href="?p=messages&aba=recebidas&pg=<?php echo $pg+1; ?>">Próximo »</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        <?php elseif($aba_ativa == 'enviadas'): ?>
            <?php
            $pg = isset($_GET['pg']) ? (int)$_GET['pg'] : 0;
            $min = $pg * 10;

            try {
                $sql_sent = $conexao->prepare("SELECT COUNT(id) as total FROM mensagens WHERE origem = ?");
                $sql_sent->execute([$db['id']]);
                $sent_total = $sql_sent->fetch(PDO::FETCH_ASSOC);
                $total_enviadas = $sent_total ? $sent_total['total'] : 0;

                $sql_sent_messages = $conexao->prepare("SELECT m.*, u.usuario FROM mensagens m 
                    LEFT JOIN usuarios u ON m.destino = u.id 
                    WHERE m.origem = ? 
                    ORDER BY m.data DESC 
                    LIMIT 10 OFFSET ?");
                $sql_sent_messages->execute([$db['id'], $min]);
            } catch (PDOException $e) {
                error_log("Erro ao buscar mensagens enviadas: " . $e->getMessage());
                $total_enviadas = 0;
                $sql_sent_messages = false;
            }
            ?>

            <h2 style="color: #fff; margin-top: 0; font-size: 12px;">Mensagens Enviadas</h2>

            <?php 
            $mensagens_enviadas = false;
            $mensagens_env_array = [];
            if($sql_sent_messages) {
                while($msg = $sql_sent_messages->fetch(PDO::FETCH_ASSOC)) {
                    $mensagens_env_array[] = $msg;
                    $mensagens_enviadas = true;
                }
            }

            if(!$mensagens_enviadas): ?>
                <div style="text-align: center; padding: 40px; color: #888;">
                    📭 Nenhuma mensagem enviada encontrada.
                </div>
            <?php else: ?>
                <table class="messages-table">
                    <thead>
                        <tr>
                            <th width="100">Data</th>
                            <th>Para / Assunto</th>
                            <th width="80">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($mensagens_env_array as $msg): ?>
                            <?php $data = explode(' ', $msg['data']); ?>
                            <tr>
                                <td>
                                    <?php echo ($data[0] == date('Y-m-d')) ? '<b>Hoje</b>' : date('d/m/Y', strtotime($data[0])); ?><br>
                                    <small><?php echo date('H:i:s', strtotime($data[1])); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($msg['usuario']); ?></strong><br>
                                    <small style="color: #888;"><?php echo htmlspecialchars($msg['assunto']); ?></small>
                                </td>
                                <td>
                                    <a href="search_msg.php?id=<?php echo $msg['id']; ?>&key=<?php echo $c->encode($db['id'], $chaveuniversal); ?>" 
                                       class="modal" rel="modal" style="color: #4CAF50; text-decoration: none;">Ver</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if($total_enviadas > 10): ?>
                    <div class="pagination">
                        <?php if($pg > 0): ?>
                            <a href="?p=messages&aba=enviadas&pg=<?php echo $pg-1; ?>">« Anterior</a>
                        <?php endif; ?>

                        <?php if(($pg+1)*10 < $total_enviadas): ?>
                            <a href="?p=messages&aba=enviadas&pg=<?php echo $pg+1; ?>">Próximo »</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<div class="action-grid">
                <div class="action-card">
                    <div class="action-icon">📨</div>
                    <div class="action-title">Enviar Mensagem</div>
                    <div class="action-desc">Escreva uma nova mensagem para outro jogador</div>
                    <a href="?p=messages&aba=escrever" class="btn-action">✉️ Escrever Nova Mensagem</a>
                </div>
                <div class="action-card">
                    <div class="action-icon">📬</div>
                    <div class="action-title">Mensagens Recebidas</div>
                    <div class="action-desc">Clique aqui para ver uma mensagem.</div>
                    <a href="?p=messages&aba=recebidas" class="btn-action btn-secondary">📨 Ver Mensagens Recebidas</a>
                </div>
            </div>

<script>
// Adicionar funcionalidade de modal para visualização de mensagens
$(document).ready(function() {
    $('a.modal').modal();

    // Limpar formulário e mostrar feedback após envio bem-sucedido
    <?php if(isset($sucesso_msg)): ?>
    // Limpar todos os campos do formulário
    $('#msg_destino').val('');
    $('#msg_assunto').val('');
    $('#msg_msg').val('');

    // Scroll para o topo para mostrar a mensagem de sucesso
    $('html, body').animate({scrollTop: 0}, 500);
    <?php endif; ?>

    // Adicionar confirmação antes de enviar
    $('form').submit(function(e) {
        var destino = $('#msg_destino').val().trim();
        var assunto = $('#msg_assunto').val().trim();
        var mensagem = $('#msg_msg').val().trim();

        if(!destino || !assunto || !mensagem) {
            e.preventDefault();
            alert('Por favor, preencha todos os campos obrigatórios.');
            return false;
        }

        // Desabilitar botão para evitar duplo envio
        $('button[name="sub2"]').prop('disabled', true).text('Enviando...');
    });
});
</script>