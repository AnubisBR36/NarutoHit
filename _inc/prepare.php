
<?php require_once('trava.php'); ?>
<?php require_once('verificar.php'); ?>
<?php require_once('funcoes.php'); ?>
<?php

// Verificar se é preparação para invasão
$is_invasao = isset($_GET['invasao']) && $_GET['invasao'] == 1;

if($is_invasao) {
    // Para invasão, verificar se há invasão ativa
    require_once('conexao.php');
    
    try {
        $srv_prep = isset($_SESSION['servidor_id']) ? (int)$_SESSION['servidor_id'] : 0;
        $stmt = $conexao->prepare("SELECT * FROM invasoes WHERE status = 'ativa' AND servidor_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$srv_prep]);
        $invasao_ativa = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$invasao_ativa) {
            echo "<script>alert('Nenhuma invasão ativa encontrada!'); self.location='?p=invasao'</script>";
            exit;
        }
        
        if($invasao_ativa['hp_atual'] <= 0) {
            echo "<script>alert('Esta invasão já foi finalizada!'); self.location='?p=invasao'</script>";
            exit;
        }
        
        if($db['energia'] < 25) {
            echo "<script>alert('Você precisa de pelo menos 25 pontos de energia para atacar!'); self.location='?p=invasao'</script>";
            exit;
        }
        
        // Definir sessão para invasão
        $_SESSION['prepare_invasao'] = $invasao_ativa['id'];
        
    } catch(PDOException $e) {
        echo "<script>self.location='?p=invasao'</script>";
        exit;
    }
} else {
    // Sistema normal de preparação
    // Aceita ID da sessão (de hunt.php) ou da URL (de outros lugares)
    if(isset($_GET['id'])) {
        $target_id = $_GET['id'];
        $_SESSION['prepare'] = $target_id; // Salva na sessão para consistency
    } elseif(isset($_SESSION['prepare'])) {
        $target_id = $_SESSION['prepare'];
    } else {
        echo "<script>self.location='?p=home'</script>"; 
        exit;
    }
    
    require_once('conexao.php');

    // Verificar se PVP está habilitado antes de qualquer processamento
    try {
        $stmt_pvp_pre = $conexao->prepare("SELECT valor FROM configuracoes WHERE nome = 'pvp_ativo' LIMIT 1");
        $stmt_pvp_pre->execute();
        $pvp_pre_val = $stmt_pvp_pre->fetchColumn();
        if ($pvp_pre_val !== false && (string)$pvp_pre_val === '0') {
            echo "<script>alert('O PVP entre jogadores está desabilitado no momento.'); self.location='?p=hunt'</script>";
            exit;
        }
    } catch (Exception $e) {}
    
    try {
        $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE id=?");
        $stmt->execute([$target_id]);
        $dbi = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$dbi){ 
            echo "<script>self.location='?p=hunt&msg=1'</script>"; 
            exit; 
        }
    } catch(PDOException $e) {
        echo "<script>self.location='?p=home'</script>"; 
        exit;
    }
    
    // Verificações normais...
    if($dbi['energia']<25){ echo "<script>self.location='?p=hunt&msg=2'</script>"; exit; }
    if($db['energia']<25){ echo "<script>self.location='?p=hunt&msg=13'</script>"; exit; }
    if($dbi['avatar']==0){ echo "<script>self.location='?p=hunt&msg=12'</script>"; exit; }
    if(($dbi['renegado']=='sim')&&($db['renegado']=='sim')){ echo "<script>self.location='?p=hunt&msg=11'</script>"; exit; }
    if(($dbi['vila']==$db['vila'])&&($dbi['renegado']=='nao')&&($db['renegado']=='nao')){ echo "<script>self.location='?p=hunt&msg=11'</script>"; exit; }
    
    // A sessão já foi definida corretamente nas linhas 44-48 acima
    // Não precisamos sobrescrever aqui
}

if(!defined('INDEX')) die('Acesso direto a este arquivo não é permitido!');
?>

<div class="box_top">
    <?php if($is_invasao): ?>
        Batalha contra <?php echo htmlspecialchars($invasao_ativa['nome_invasor']); ?>
    <?php else: ?>
        Batalha contra <?php echo $dbi['usuario']; ?>
    <?php endif; ?>
</div>
<div class="box_middle">
    <div style="text-align: center; padding: 20px;">
        <?php if($is_invasao): ?>
            <p>Você está prestes a atacar o invasor <b><?php echo htmlspecialchars($invasao_ativa['nome_invasor']); ?></b>!</p>
            <p>Tem certeza que deseja continuar com a batalha?</p>
            
            <div style="margin: 20px 0;">
                <img src="_img/Invasao/<?php echo htmlspecialchars($invasao_ativa['imagem_arquivo']); ?>" 
                     style="width: 100px; height: 100px; border: 2px solid #ff0000; border-radius: 5px;" />
            </div>
            
            <div style="margin: 20px 0;">
                <p><b>HP:</b> <?php echo number_format($invasao_ativa['hp_atual']); ?> / <?php echo number_format($invasao_ativa['hp_total']); ?></p>
                <p><b>Prêmio:</b> <?php echo number_format($invasao_ativa['premio_yens']); ?> Yens</p>
                <p><b>Bônus da Vila:</b> +<?php echo $invasao_ativa['bonus_vila']; ?>% em todos os status</p>
            </div>
            
            <div style="margin: 30px 0;">
                <p style="background:#333333; padding: 10px; margin: 10px 0;">
                    <b>Energia Necessária:</b> 25 pontos<br />
                    <span style="font-size: 12px;">Seu chakra atual: <?php echo $db['energia']; ?>/<?php echo $db['energiamax']; ?></span>
                </p>
                
                <input type="button" class="botao" value="Atacar Invasor" onclick="location.href='?p=attack&invasao=1'" />
            </div>
            
            <div style="margin: 20px 0;">
                <input type="button" class="botao" value="Cancelar" onclick="location.href='?p=invasao'" style="background: #666;" />
            </div>
            
        <?php else: ?>
            <p>Você está prestes a atacar <b><?php echo $dbi['usuario']; ?></b>!</p>
            <p>Tem certeza que deseja continuar com a batalha?</p>
            
            <div style="margin: 20px 0;">
                <img src="_img/personagens/<?php echo $dbi['personagem']; ?>/<?php echo $dbi['avatar']; ?>.jpg" 
                     style="width: 100px; height: 100px; border: 2px solid #333;" />
            </div>
            
            <div style="margin: 20px 0;">
                <p><b>Vila:</b> 
                <?php 
                switch($dbi['vila']) {
                    case 1: echo ($dbi['renegado'] == 'sim') ? 'Akatsuki (Vila da Folha)' : 'Vila da Folha'; break;
                    case 2: echo ($dbi['renegado'] == 'sim') ? 'Akatsuki (Vila da Areia)' : 'Vila da Areia'; break;
                    case 3: echo ($dbi['renegado'] == 'sim') ? 'Akatsuki (Vila do Som)' : 'Vila do Som'; break;
                    case 4: echo ($dbi['renegado'] == 'sim') ? 'Akatsuki (Vila da Chuva)' : 'Vila da Chuva'; break;
                    case 5: echo ($dbi['renegado'] == 'sim') ? 'Akatsuki (Vila da Nuvem)' : 'Vila da Nuvem'; break;
                    case 6: echo ($dbi['renegado'] == 'sim') ? 'Akatsuki (Vila da Névoa)' : 'Vila da Névoa'; break;
                    case 8: echo ($dbi['renegado'] == 'sim') ? 'Akatsuki (Vila da Pedra)' : 'Vila da Pedra'; break;
                }
                ?>
                </p>
                <p><b>Nível:</b> <?php echo $dbi['nivel']; ?></p>
                <p><b>Energia:</b> <?php echo $dbi['energia']; ?>/<?php echo $dbi['energiamax']; ?></p>
            </div>
            
            <div style="margin: 30px 0;">
                <?php
                // Gerar sistema anti-bot com imagens de vilas
                $vilas_disponiveis = [
                    ['arquivo' => 'konoha', 'nome' => 'Konoha'],
                    ['arquivo' => 'areia', 'nome' => 'Areia'],
                    ['arquivo' => 'nevoa', 'nome' => 'Névoa'],
                    ['arquivo' => 'rocha', 'nome' => 'Rocha'],
                    ['arquivo' => 'nuvem', 'nome' => 'Nuvem'],
                    ['arquivo' => 'akatsuki', 'nome' => 'Akatsuki'],
                    ['arquivo' => 'som', 'nome' => 'Som'],
                    ['arquivo' => 'chuva', 'nome' => 'Chuva']
                ];
                shuffle($vilas_disponiveis);
                
                // Pegar 5 imagens aleatórias para os botões
                $_SESSION['bot_images'] = array_slice($vilas_disponiveis, 0, 5);
                
                // Gerar sequência de EXATAMENTE 3 vilas DIFERENTES que o jogador deve clicar
                $indices_disponiveis = [0, 1, 2, 3, 4]; // Índices dos 5 botões
                shuffle($indices_disponiveis); // Embaralhar
                $_SESSION['bot_sequence'] = array_slice($indices_disponiveis, 0, 3); // Pegar 3 índices diferentes
                
                // Gerar token de segurança
                $_SESSION['bot_token'] = bin2hex(random_bytes(16));
                
                // Resetar clicks e erros
                $_SESSION['bot_clicks'] = [];
                if(!isset($_SESSION['errobot'])) $_SESSION['errobot'] = 0;
                ?>
                
                <p style="background:#333333; padding: 10px; margin: 10px 0;">
                    <b>Sistema Anti-Bot</b>: Clique nas vilas na ordem indicada abaixo.<br />
                    <span style="font-size: 12px;">Ao errar 2 vezes seguidas, sua conta é deslogada.</span>
                </p>
                
                <div id="sequencia-pedida" style="background:#1a1a1a; padding: 15px; margin: 15px 0; border: 2px solid #ff6600; border-radius: 5px;">
                    <p style="margin: 0 0 10px 0; font-weight: bold; color: #ff6600;">Clique na ordem:</p>
                    <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                        <?php foreach($_SESSION['bot_sequence'] as $index => $img_index): ?>
                            <div style="text-align: center;">
                                <div style="font-size: 18px; font-weight: bold; color: #ff6600; margin-bottom: 5px;">
                                    <?php echo ($index + 1); ?>º
                                </div>
                                <img src="_img/Vilas Captchar/<?php echo $_SESSION['bot_images'][$img_index]['arquivo']; ?>.png" 
                                     style="width: 60px; height: 60px; border: 2px solid #ff6600; border-radius: 5px;" />
                                <div style="font-size: 12px; color: #fff; margin-top: 5px;">
                                    <?php echo $_SESSION['bot_images'][$img_index]['nome']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div id="status-clicks" style="background:#1a1a1a; padding: 10px; margin: 10px 0; border-radius: 5px; min-height: 30px; text-align: center; color: #fff;">
                    Aguardando cliques...
                </div>
                
                <div style="margin: 20px 0; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <?php foreach($_SESSION['bot_images'] as $index => $vila_data): ?>
                        <button type="button" class="botao-vila" data-index="<?php echo $index; ?>" onclick="clicarVila(<?php echo $index; ?>)">
                            <img src="_img/Vilas Captchar/<?php echo $vila_data['arquivo']; ?>.png" 
                                 style="width: 80px; height: 80px; display: block;" />
                            <div style="font-size: 12px; margin-top: 5px;"><?php echo $vila_data['nome']; ?></div>
                        </button>
                    <?php endforeach; ?>
                </div>
                
                <form id="form-attack" method="POST" action="?p=attack" style="display: none;">
                    <input type="hidden" name="bot_sequence" id="bot_sequence" value="" />
                    <input type="hidden" name="bot_token" value="<?php echo $_SESSION['bot_token']; ?>" />
                </form>
                
                <script>
                let clicksRegistrados = [];
                const sequenciaCorreta = <?php echo json_encode($_SESSION['bot_sequence']); ?>;
                
                function clicarVila(index) {
                    // Verificar se o clique atual está correto
                    const posicaoAtual = clicksRegistrados.length;
                    const statusDiv = document.getElementById('status-clicks');
                    
                    // Verificar se o índice clicado corresponde ao esperado nesta posição
                    if(index !== sequenciaCorreta[posicaoAtual]) {
                        // ERRO! Embaralhar imediatamente
                        statusDiv.innerHTML = '<span style="color: #f44336; font-weight: bold;">✗ Sequência incorreta! Embaralhando vilas...</span>';
                        
                        // Desabilitar todos os botões para evitar cliques múltiplos
                        const botoes = document.querySelectorAll('.botao-vila');
                        botoes.forEach(b => b.disabled = true);
                        
                        // Recarregar a página após 1.5 segundos para gerar novas imagens e nova sequência
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                        return; // Parar execução aqui
                    }
                    
                    // Se chegou aqui, o clique está correto
                    clicksRegistrados.push(index);
                    statusDiv.innerHTML = 'Cliques: ' + clicksRegistrados.length + ' de ' + sequenciaCorreta.length;
                    
                    // Verificar se completou a sequência corretamente
                    if(clicksRegistrados.length === sequenciaCorreta.length) {
                        statusDiv.innerHTML = '<span style="color: #4CAF50; font-weight: bold;">✓ Sequência correta! Processando ataque...</span>';
                        
                        // Enviar para o servidor
                        document.getElementById('bot_sequence').value = JSON.stringify(clicksRegistrados);
                        setTimeout(() => {
                            document.getElementById('form-attack').submit();
                        }, 500);
                    }
                }
                </script>
            </div>
            
            <div style="margin: 20px 0;">
                <input type="button" class="botao" value="Cancelar" onclick="location.href='?p=mapa'" style="background: #666;" />
            </div>
        <?php endif; ?>
    </div>
</div>
<div class="box_bottom"></div>

<style>
.botao {
    background: #4CAF50;
    color: white;
    border: none;
    padding: 10px 15px;
    margin: 5px;
    cursor: pointer;
    border-radius: 3px;
    font-weight: bold;
}

.botao:hover {
    background: #45a049;
}

.botao-vila {
    background: #2a2a2a;
    border: 3px solid #ff6600;
    border-radius: 10px;
    padding: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    color: white;
    font-weight: bold;
}

.botao-vila:hover {
    background: #3a3a3a;
    border-color: #ff8833;
    transform: scale(1.05);
    box-shadow: 0 0 15px rgba(255, 102, 0, 0.5);
}

.botao-vila:active {
    transform: scale(0.95);
}
</style>
