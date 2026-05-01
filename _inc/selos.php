<?php require_once('trava.php'); ?>
<?php require_once('verificar.php'); ?>
<?php require_once('funcoes_selos.php'); ?>
<?php
// Verificar se usuário está logado
if(!$db || !is_array($db)){
    echo "<script>self.location='?p=login'</script>";
    exit;
}

// Verificar nível mínimo
if($db['nivel'] < 30){
    echo "<script>self.location='?p=home&msg=nivel_minimo'</script>";
    exit;
}

// Processar ativação de selo
if(isset($_POST['ativar_selo'])){
    $selo_escolhido = (int)$_POST['selo_tipo'];
    
    // Validar selo
    if($selo_escolhido < 1 || $selo_escolhido > 4){
        echo "<script>self.location='?p=selos&erro=invalido'</script>";
        exit;
    }
    
    // Verificar se já tem selo
    if($db['selo_tipo'] > 0){
        echo "<script>self.location='?p=selos&erro=ja_tem'</script>";
        exit;
    }
    
    // Verificar requisitos específicos de cada selo
    $erro = false;
    switch($selo_escolhido){
        case 1: // Céu (Ninjutsu)
            if($db['ninjutsu'] < 50){
                echo "<script>self.location='?p=selos&erro=req_ceu'</script>";
                exit;
            }
            break;
        case 2: // Terra (Taijutsu)
            if($db['taijutsu'] < 50){
                echo "<script>self.location='?p=selos&erro=req_terra'</script>";
                exit;
            }
            break;
        case 3: // Lua (Genjutsu)
            if($db['genjutsu'] < 50){
                echo "<script>self.location='?p=selos&erro=req_lua'</script>";
                exit;
            }
            break;
        case 4: // Sol (Balanceado)
            $diff = max($db['taijutsu'], $db['ninjutsu'], $db['genjutsu']) - min($db['taijutsu'], $db['ninjutsu'], $db['genjutsu']);
            if($diff > 20){
                echo "<script>self.location='?p=selos&erro=req_sol'</script>";
                exit;
            }
            break;
    }
    
    // Verificar yens
    $custo = 50000;
    if($db['yens'] < $custo){
        echo "<script>self.location='?p=selos&erro=yens'</script>";
        exit;
    }
    
    // Ativar selo
    try {
        $conexao->beginTransaction();
        
        $stmt = $conexao->prepare("UPDATE usuarios SET selo_tipo=?, selo_nivel=1, selo_vitorias=0, yens=yens-? WHERE id=?");
        $stmt->execute([$selo_escolhido, $custo, $db['id']]);
        
        // Adicionar atualização
        $selos_nomes = ['', 'do Céu', 'da Terra', 'da Lua', 'do Sol'];
        $update_text = '<a href="?p=view&view='.strtolower($db['usuario']).'">'.$db['usuario'].'</a> ativou o <b>Selo Amaldiçoado '.$selos_nomes[$selo_escolhido].'</b>!';
        $stmt_update = $conexao->prepare("INSERT INTO atualizacoes (usuarioid, texto, hora) VALUES (?, ?, ?)");
        $stmt_update->execute([$db['id'], $update_text, time()]);
        
        $conexao->commit();
        
        echo "<script>self.location='?p=selos&sucesso=ativado'</script>";
        exit;
    } catch (PDOException $e) {
        $conexao->rollBack();
        echo "<script>self.location='?p=selos&erro=db'</script>";
        exit;
    }
}

// Processar evolução de selo
if(isset($_POST['evoluir_selo'])){
    if($db['selo_tipo'] == 0 || $db['selo_nivel'] == 0){
        echo "<script>self.location='?p=selos&erro=sem_selo'</script>";
        exit;
    }
    
    if($db['selo_nivel'] >= 3){
        echo "<script>self.location='?p=selos&erro=max_nivel'</script>";
        exit;
    }
    
    // Requisitos por nível
    $requisitos = [
        2 => ['vitorias' => 100, 'yens' => 100000],
        3 => ['vitorias' => 300, 'yens' => 500000]
    ];
    
    $prox_nivel = $db['selo_nivel'] + 1;
    $req = $requisitos[$prox_nivel];
    
    if($db['selo_vitorias'] < $req['vitorias']){
        echo "<script>self.location='?p=selos&erro=vitorias&req=".$req['vitorias']."'</script>";
        exit;
    }
    
    if($db['yens'] < $req['yens']){
        echo "<script>self.location='?p=selos&erro=yens_evo'</script>";
        exit;
    }
    
    // Evoluir selo
    try {
        $conexao->beginTransaction();
        
        $stmt = $conexao->prepare("UPDATE usuarios SET selo_nivel=?, yens=yens-? WHERE id=?");
        $stmt->execute([$prox_nivel, $req['yens'], $db['id']]);
        
        $selos_nomes = ['', 'do Céu', 'da Terra', 'da Lua', 'do Sol'];
        $update_text = '<a href="?p=view&view='.strtolower($db['usuario']).'">'.$db['usuario'].'</a> evoluiu o <b>Selo Amaldiçoado '.$selos_nomes[$db['selo_tipo']].'</b> para o nível '.$prox_nivel.'!';
        $stmt_update = $conexao->prepare("INSERT INTO atualizacoes (usuarioid, texto, hora) VALUES (?, ?, ?)");
        $stmt_update->execute([$db['id'], $update_text, time()]);
        
        $conexao->commit();
        
        echo "<script>self.location='?p=selos&sucesso=evoluido'</script>";
        exit;
    } catch (PDOException $e) {
        $conexao->rollBack();
        echo "<script>self.location='?p=selos&erro=db'</script>";
        exit;
    }
}

// Processar remoção de selo
if(isset($_POST['remover_selo'])){
    if($db['selo_tipo'] == 0){
        echo "<script>self.location='?p=selos&erro=sem_selo'</script>";
        exit;
    }
    
    $custo_remocao = 250000;
    if($db['yens'] < $custo_remocao){
        echo "<script>self.location='?p=selos&erro=yens_remover'</script>";
        exit;
    }
    
    try {
        $stmt = $conexao->prepare("UPDATE usuarios SET selo_tipo=0, selo_nivel=0, selo_vitorias=0, yens=yens-? WHERE id=?");
        $stmt->execute([$custo_remocao, $db['id']]);
        
        echo "<script>self.location='?p=selos&sucesso=removido'</script>";
        exit;
    } catch (PDOException $e) {
        echo "<script>self.location='?p=selos&erro=db'</script>";
        exit;
    }
}

// Definir informações dos selos
$selos_info = [
    1 => [
        'nome' => 'Selo do Céu',
        'pasta' => 'Ceú',
        'foco' => 'Ninjutsu',
        'cor' => '#4169E1',
        'requisito' => 'Ninjutsu mínimo: 50',
        'bonus' => [
            1 => '+15% Ninjutsu, -5% Taijutsu',
            2 => '+30% Ninjutsu, -10% Taijutsu, +5% Crítico',
            3 => '+50% Ninjutsu, -15% Taijutsu, +10% Crítico, -2% HP por turno'
        ]
    ],
    2 => [
        'nome' => 'Selo da Terra',
        'pasta' => 'terra',
        'foco' => 'Taijutsu',
        'cor' => '#8B4513',
        'requisito' => 'Taijutsu mínimo: 50',
        'bonus' => [
            1 => '+15% Taijutsu, -5% Ninjutsu',
            2 => '+30% Taijutsu, -10% Ninjutsu, +5% Defesa',
            3 => '+50% Taijutsu, -15% Ninjutsu, +10% Defesa, +15% custo energia'
        ]
    ],
    3 => [
        'nome' => 'Selo da Lua',
        'pasta' => 'Lua',
        'foco' => 'Genjutsu',
        'cor' => '#9370DB',
        'requisito' => 'Genjutsu mínimo: 50',
        'bonus' => [
            1 => '+20% Genjutsu, +3% Esquiva',
            2 => '+40% Genjutsu, +6% Esquiva, 10% Confusão',
            3 => '+60% Genjutsu, +10% Esquiva, 15% Confusão, -50% bônus base'
        ]
    ],
    4 => [
        'nome' => 'Selo do Sol',
        'pasta' => 'sol',
        'foco' => 'Balanceado',
        'cor' => '#FFD700',
        'requisito' => 'Atributos balanceados (diferença < 20)',
        'bonus' => [
            1 => '+10% em todos atributos',
            2 => '+20% em todos, +5% HP Max',
            3 => '+35% em todos, +10% HP Max, +20% tempo missões'
        ]
    ]
];
?>

<style>
.selo-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.selo-card {
    background: linear-gradient(135deg, #2a2a2a 0%, #1a1a1a 100%);
    border: 2px solid #444;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.selo-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}

.selo-card.ativo {
    border-color: #FFD700;
    box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
}

.selo-imagem {
    width: 120px;
    height: 120px;
    margin: 10px auto;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px;
}

.selo-imagem img {
    max-width: 100%;
    max-height: 100%;
    filter: drop-shadow(0 0 10px rgba(255,255,255,0.3));
}

.selo-nome {
    font-size: 20px;
    font-weight: bold;
    margin: 15px 0 10px;
    text-transform: uppercase;
}

.selo-foco {
    font-size: 14px;
    color: #999;
    margin-bottom: 15px;
}

.selo-requisito {
    background: rgba(255,255,255,0.05);
    padding: 8px;
    border-radius: 5px;
    font-size: 12px;
    margin: 10px 0;
    color: #FFD700;
}

.selo-bonus {
    text-align: left;
    background: rgba(0,0,0,0.3);
    padding: 15px;
    border-radius: 5px;
    margin: 15px 0;
}

.selo-bonus h4 {
    color: #FFD700;
    margin-bottom: 10px;
    font-size: 14px;
}

.selo-bonus ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.selo-bonus li {
    padding: 5px 0;
    font-size: 12px;
    color: #ccc;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.selo-bonus li:last-child {
    border-bottom: none;
}

.nivel-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
    color: #000;
    padding: 5px 15px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 12px;
}

.meu-selo-info {
    background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
    border: 2px solid #FFD700;
    border-radius: 10px;
    padding: 20px;
    margin: 20px 0;
}

.evolucao-barra {
    background: rgba(0,0,0,0.3);
    height: 30px;
    border-radius: 15px;
    overflow: hidden;
    margin: 15px 0;
    position: relative;
}

.evolucao-progresso {
    height: 100%;
    background: linear-gradient(90deg, #FFD700 0%, #FFA500 100%);
    transition: width 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #000;
    font-weight: bold;
    font-size: 12px;
}

.btn-selo {
    background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    margin: 5px;
    transition: all 0.3s ease;
}

.btn-selo:hover {
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
}

.btn-selo.danger {
    background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
}

.btn-selo.danger:hover {
    box-shadow: 0 5px 15px rgba(244, 67, 54, 0.4);
}
</style>

<div class="box_top">⚡ Selos Amaldiçoados</div>
<div class="box_middle">

<?php
// Mensagens
if(isset($_GET['sucesso'])){
    echo '<div class="aviso" style="background:#00CC00;color:#FFF;padding:15px;border-radius:5px;margin-bottom:20px;">';
    switch($_GET['sucesso']){
        case 'ativado': echo '✅ <b>Selo ativado com sucesso!</b> Seu poder aumentou significativamente.'; break;
        case 'evoluido': echo '⬆️ <b>Selo evoluído!</b> Você alcançou um novo nível de poder.'; break;
        case 'removido': echo '🗑️ <b>Selo removido.</b> Você perdeu todos os bônus, mas está livre novamente.'; break;
    }
    echo '</div>';
}

if(isset($_GET['erro'])){
    echo '<div class="aviso" style="background:#CC0000;color:#FFF;padding:15px;border-radius:5px;margin-bottom:20px;">';
    switch($_GET['erro']){
        case 'ja_tem': echo '❌ Você já possui um Selo Amaldiçoado ativo!'; break;
        case 'req_ceu': echo '❌ Requisito não atendido: Ninjutsu mínimo 50'; break;
        case 'req_terra': echo '❌ Requisito não atendido: Taijutsu mínimo 50'; break;
        case 'req_lua': echo '❌ Requisito não atendido: Genjutsu mínimo 50'; break;
        case 'req_sol': echo '❌ Requisito não atendido: Seus atributos devem estar balanceados (diferença máxima de 20 pontos)'; break;
        case 'yens': echo '❌ Você não tem yens suficientes! Custo: 50.000 yens'; break;
        case 'yens_evo': echo '❌ Você não tem yens suficientes para evoluir!'; break;
        case 'yens_remover': echo '❌ Você não tem yens suficientes! Custo de remoção: 250.000 yens'; break;
        case 'vitorias': echo '❌ Você precisa de '.$_GET['req'].' vitórias PVP para evoluir!'; break;
        case 'sem_selo': echo '❌ Você não possui nenhum selo ativo!'; break;
        case 'max_nivel': echo '❌ Seu selo já está no nível máximo!'; break;
        default: echo '❌ Erro ao processar sua solicitação.'; break;
    }
    echo '</div>';
}
?>

<div style="background:rgba(0,0,0,0.3);padding:15px;border-radius:5px;margin-bottom:20px;border-left:4px solid #FFD700;">
    <p style="margin:0;color:#FFD700;"><b>⚠️ ATENÇÃO:</b> Os Selos Amaldiçoados concedem grande poder, mas com um preço. Escolha sabiamente!</p>
    <p style="margin:10px 0 0;font-size:12px;color:#999;">• Você só pode ter 1 selo ativo por vez<br>
    • Remover um selo custa 250.000 yens e você perde toda progressão<br>
    • Os bônus aumentam conforme você evolui o selo</p>
</div>

<?php if(isset($db['selo_tipo']) && $db['selo_tipo'] > 0): ?>
    <div class="meu-selo-info">
        <h3 style="color:#FFD700;margin-top:0;">🔥 Seu Selo Ativo</h3>
        <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
            <div class="selo-imagem">
                <img src="_img/Selos/<?php echo $selos_info[$db['selo_tipo']]['pasta']; ?>/<?php echo $db['selo_nivel']; ?>.gif" alt="Selo">
            </div>
            <div style="flex:1;">
                <h2 style="color:<?php echo $selos_info[$db['selo_tipo']]['cor']; ?>;margin:10px 0;">
                    <?php echo $selos_info[$db['selo_tipo']]['nome']; ?> - Nível <?php echo $db['selo_nivel']; ?>
                </h2>
                <p style="color:#ccc;margin:5px 0;">
                    <b>Bônus Atual:</b> <?php echo $selos_info[$db['selo_tipo']]['bonus'][$db['selo_nivel']]; ?>
                </p>
                <p style="color:#999;margin:5px 0;font-size:12px;">
                    Vitórias PVP acumuladas: <b style="color:#FFD700;"><?php echo $db['selo_vitorias']; ?></b>
                </p>
                
                <?php if($db['selo_nivel'] < 3): ?>
                    <?php
                    $requisitos_evo = [
                        2 => ['vitorias' => 100, 'yens' => 100000],
                        3 => ['vitorias' => 300, 'yens' => 500000]
                    ];
                    $prox = $db['selo_nivel'] + 1;
                    $progresso = min(100, ($db['selo_vitorias'] / $requisitos_evo[$prox]['vitorias']) * 100);
                    ?>
                    <div style="margin-top:15px;">
                        <p style="color:#FFD700;margin-bottom:5px;"><b>Progresso para Nível <?php echo $prox; ?>:</b></p>
                        <div class="evolucao-barra">
                            <div class="evolucao-progresso" style="width:<?php echo $progresso; ?>%;">
                                <?php echo $db['selo_vitorias']; ?> / <?php echo $requisitos_evo[$prox]['vitorias']; ?>
                            </div>
                        </div>
                        <p style="color:#999;font-size:12px;margin:5px 0;">
                            Requisitos: <?php echo number_format($requisitos_evo[$prox]['vitorias']); ?> vitórias + <?php echo number_format($requisitos_evo[$prox]['yens']); ?> yens
                        </p>
                        
                        <form method="post" style="margin-top:10px;">
                            <button type="submit" name="evoluir_selo" class="btn-selo" 
                                    <?php if($db['selo_vitorias'] < $requisitos_evo[$prox]['vitorias'] || $db['yens'] < $requisitos_evo[$prox]['yens']) echo 'disabled style="opacity:0.5;cursor:not-allowed;"'; ?>>
                                ⬆️ Evoluir para Nível <?php echo $prox; ?>
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div style="background:rgba(255,215,0,0.2);padding:15px;border-radius:5px;margin-top:15px;border:2px solid #FFD700;">
                        <p style="color:#FFD700;margin:0;"><b>🏆 NÍVEL MÁXIMO ALCANÇADO!</b></p>
                        <p style="color:#ccc;margin:5px 0 0;font-size:12px;">Você dominou completamente o poder deste selo!</p>
                    </div>
                <?php endif; ?>
                
                <form method="post" style="margin-top:15px;" onsubmit="return confirm('Tem certeza? Você perderá todo o progresso e pagará 250.000 yens!');">
                    <button type="submit" name="remover_selo" class="btn-selo danger">
                        🗑️ Remover Selo (250.000 yens)
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="sep"></div>
    
    <h3 style="color:#FFD700;">📚 Outros Selos Disponíveis</h3>
    <p style="color:#999;font-size:12px;">Você pode trocar seu selo atual removendo-o e ativando outro.</p>
<?php else: ?>
    <h3 style="color:#FFD700;">Escolha seu Selo Amaldiçoado</h3>
    <p style="color:#999;margin-bottom:20px;">Selecione um selo que se adeque ao seu estilo de luta. Custo: <b style="color:#FFD700;">50.000 yens</b></p>
<?php endif; ?>

<div class="selo-container">
    <?php foreach($selos_info as $id => $selo): ?>
        <div class="selo-card <?php if(isset($db['selo_tipo']) && $db['selo_tipo'] == $id) echo 'ativo'; ?>">
            <?php if(isset($db['selo_tipo']) && $db['selo_tipo'] == $id): ?>
                <div class="nivel-badge">ATIVO</div>
            <?php endif; ?>
            
            <div class="selo-imagem">
                <img src="_img/Selos/<?php echo $selo['pasta']; ?>/1.gif" alt="<?php echo $selo['nome']; ?>">
            </div>
            
            <div class="selo-nome" style="color:<?php echo $selo['cor']; ?>;">
                <?php echo $selo['nome']; ?>
            </div>
            
            <div class="selo-foco">
                Foco: <b style="color:<?php echo $selo['cor']; ?>;"><?php echo $selo['foco']; ?></b>
            </div>
            
            <div class="selo-requisito">
                <?php echo $selo['requisito']; ?>
            </div>
            
            <div class="selo-bonus">
                <h4>Bônus por Nível:</h4>
                <ul>
                    <li><b>Nível 1:</b> <?php echo $selo['bonus'][1]; ?></li>
                    <li><b>Nível 2:</b> <?php echo $selo['bonus'][2]; ?></li>
                    <li><b>Nível 3:</b> <?php echo $selo['bonus'][3]; ?></li>
                </ul>
            </div>
            
            <?php if(!isset($db['selo_tipo']) || $db['selo_tipo'] == 0): ?>
                <form method="post">
                    <input type="hidden" name="selo_tipo" value="<?php echo $id; ?>">
                    <button type="submit" name="ativar_selo" class="btn-selo" style="background:url('_img/fundo_botao.jpg') center center; background-size:cover; color:#FFF; text-shadow:1px 1px 2px #000; border:2px solid <?php echo $selo['cor']; ?>;">
                        ⚡ Ativar Selo
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<div class="sep"></div>

<div style="background:rgba(0,0,0,0.3);padding:15px;border-radius:5px;border-left:4px solid #4169E1;">
    <h4 style="color:#4169E1;margin-top:0;">📖 Como Funciona?</h4>
    <ul style="color:#ccc;font-size:13px;">
        <li><b>Ativação:</b> Escolha um selo e pague 50.000 yens</li>
        <li><b>Evolução:</b> Ganhe vitórias PVP para acumular progresso</li>
        <li><b>Nível 2:</b> Requer 100 vitórias + 100.000 yens</li>
        <li><b>Nível 3:</b> Requer 300 vitórias totais + 500.000 yens</li>
        <li><b>Remoção:</b> Custa 250.000 yens e perde todo progresso</li>
    </ul>
</div>

</div>
<div class="box_bottom"></div>
