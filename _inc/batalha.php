
<?php require_once('trava.php'); ?>
<?php require_once('verificar.php'); ?>
<?php require_once('funcoes.php'); ?>
<?php require_once('conexao.php'); ?>
<?php
// Verificar se o opponent_id foi passado
if(!isset($_GET['opponent_id'])) {
    echo "<script>self.location='?p=home'</script>";
    exit;
}

$opponent_id = intval($_GET['opponent_id']);

try {
    // Buscar dados do oponente
    $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$opponent_id]);
    $opponent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$opponent) {
        echo "<script>self.location='?p=hunt&msg=1'</script>";
        exit;
    }
} catch(PDOException $e) {
    echo "<script>self.location='?p=hunt&msg=1'</script>";
    exit;
}

// Verificar se o oponente está online e disponível para batalha
if($opponent['energia'] < 25) {
    echo "<script>self.location='?p=hunt&msg=2'</script>";
    exit;
}

if($db['energia'] < 25) {
    echo "<script>self.location='?p=hunt&msg=13'</script>";
    exit;
}

// Verificar se são da mesma vila (não podem lutar)
if(($opponent['vila'] == $db['vila']) && ($opponent['renegado'] == 'nao') && ($db['renegado'] == 'nao')) {
    echo "<script>self.location='?p=hunt&msg=11'</script>";
    exit;
}

// Verificar se ambos são renegados (não podem lutar entre si)
if(($opponent['renegado'] == 'sim') && ($db['renegado'] == 'sim')) {
    echo "<script>self.location='?p=hunt&msg=11'</script>";
    exit;
}

// Verificar cooldown de batalhas
try {
    $stmt_cooldown = $conexao->prepare("SELECT data FROM relatorios WHERE usuarioid=? AND inimigoid=? ORDER BY id DESC LIMIT 1");
    $stmt_cooldown->execute([$db['id'], $opponent['id']]);
    $dbv = $stmt_cooldown->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $dbv = false;
}

$soma = mktime(date('H')-12, date('i'), date('s'));
$penalidade = date('Y-m-d H:i:s',$soma);
if($dbv && $penalidade < $dbv['data']) {
    echo "<script>self.location='?p=hunt&msg=9'</script>";
    exit;
}

// Definir sessão para o prepare
$_SESSION['prepare'] = $opponent_id;
?>

<div class="box_top">Batalha contra <?php echo $opponent['usuario']; ?></div>
<div class="box_middle">
    <div style="text-align: center; padding: 20px;">
        <p>Você está prestes a atacar <b><?php echo $opponent['usuario']; ?></b>!</p>
        <p>Tem certeza que deseja continuar com a batalha?</p>
        
        <div style="margin: 20px 0;">
            <img src="_img/personagens/<?php echo $opponent['personagem']; ?>/<?php echo $opponent['avatar']; ?>.jpg" 
                 style="width: 100px; height: 100px; border: 2px solid #333;" />
        </div>
        
        <div style="margin: 20px 0;">
            <p><b>Vila:</b> 
            <?php 
            switch($opponent['vila']) {
                case 1: echo ($opponent['renegado'] == 'sim') ? 'Akatsuki (Vila da Folha)' : 'Vila da Folha'; break;
                case 2: echo ($opponent['renegado'] == 'sim') ? 'Akatsuki (Vila da Areia)' : 'Vila da Areia'; break;
                case 3: echo ($opponent['renegado'] == 'sim') ? 'Akatsuki (Vila do Som)' : 'Vila do Som'; break;
                case 4: echo ($opponent['renegado'] == 'sim') ? 'Akatsuki (Vila da Chuva)' : 'Vila da Chuva'; break;
                case 5: echo ($opponent['renegado'] == 'sim') ? 'Akatsuki (Vila da Nuvem)' : 'Vila da Nuvem'; break;
                case 6: echo ($opponent['renegado'] == 'sim') ? 'Akatsuki (Vila da Névoa)' : 'Vila da Névoa'; break;
                case 8: echo ($opponent['renegado'] == 'sim') ? 'Akatsuki (Vila da Pedra)' : 'Vila da Pedra'; break;
            }
            ?>
            </p>
            <p><b>Nível:</b> <?php echo $opponent['nivel']; ?></p>
            <p><b>Energia:</b> <?php echo $opponent['energia']; ?>/<?php echo $opponent['energiamax']; ?></p>
        </div>
        
        <div style="margin: 30px 0;">
            <?php $_SESSION['bot'] = rand(1,5); ?>
            <p style="background:#333333; padding: 10px; margin: 10px 0;">
                <b>Sistema Anti-Bot</b>: Para atacar, clique no <b><?php echo $_SESSION['bot']; ?>º</b> botão.<br />
                <span style="font-size: 12px;">Ao errar 2 vezes seguidas, sua conta é deslogada.</span>
            </p>
            
            <input type="button" class="botao" value="Atacar" onclick="location.href='?p=attack&bot=1'" />
            <input type="button" class="botao" value="Atacar" onclick="location.href='?p=attack&bot=2'" />
            <input type="button" class="botao" value="Atacar" onclick="location.href='?p=attack&bot=3'" />
            <input type="button" class="botao" value="Atacar" onclick="location.href='?p=attack&bot=4'" />
            <input type="button" class="botao" value="Atacar" onclick="location.href='?p=attack&bot=5'" />
        </div>
        
        <div style="margin: 20px 0;">
            <input type="button" class="botao" value="Cancelar" onclick="location.href='?p=mapa'" style="background: #666;" />
        </div>
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
</style>
