<?php
require_once('conexao.php');
require_once('verificar.php');
require_once('funcoes.php');

if(!$db){ echo "<script>self.location='?p=home'</script>"; exit; }

// ——— PROCESSAR ATAQUE POST ———
if(isset($_POST['action']) && $_POST['action'] == 'atacar'){

    if($db['nivel'] < 20){ echo "<script>self.location='?p=despertar';</script>"; exit; }
    if($db['doujutsu'] > 0){ echo "<script>self.location='?p=home';</script>"; exit; }
    if(!empty($db['doujutsu_proxima_tentativa']) && strtotime($db['doujutsu_proxima_tentativa']) > time()){
        echo "<script>self.location='?p=despertar';</script>"; exit;
    }
    // Verificar cooldown de ataque
    if(!empty($db['doujutsu_despertar_cooldown']) && strtotime($db['doujutsu_despertar_cooldown']) > time()){
        echo "<script>self.location='?p=despertar';</script>"; exit;
    }

    // Sorteio: 70% de acertar, 30% de errar
    $acertou = (rand(1, 100) <= 70);

    if($acertou){
        // Golpe certeiro — bot morre — sorteio da linhagem
        $roll = rand(1, 100);
        if($roll <= 5)       $novo_doujutsu = 3; // Rinnegan 5%
        elseif($roll <= 20)  $novo_doujutsu = 1; // Sharingan 15%
        elseif($roll <= 40)  $novo_doujutsu = 2; // Byakugan 20%
        else                 $novo_doujutsu = 0; // Nenhum 60%

        if($novo_doujutsu > 0){
            $stmt = $conexao->prepare("UPDATE usuarios SET doujutsu=?, doujutsu_nivel=1, doujutsu_exp=0, doujutsu_expmax=100, doujutsu_despertar_hp=-1, doujutsu_despertar_cooldown=NULL, doujutsu_proxima_tentativa=NULL WHERE id=?");
            $stmt->execute([$novo_doujutsu, $db['id']]);
        } else {
            $proxima = date('Y-m-d H:i:s', strtotime('+15 days'));
            $stmt = $conexao->prepare("UPDATE usuarios SET doujutsu=0, doujutsu_despertar_hp=NULL, doujutsu_despertar_cooldown=NULL, doujutsu_proxima_tentativa=? WHERE id=?");
            $stmt->execute([$proxima, $db['id']]);
        }
        $_SESSION['despertar_resultado'] = $novo_doujutsu;
        echo "<script>self.location='?p=despertar&resultado=1';</script>"; exit;

    } else {
        // Golpe errou — cooldown de 30 segundos
        $proximo_cooldown = date('Y-m-d H:i:s', time() + 30);
        $stmt = $conexao->prepare("UPDATE usuarios SET doujutsu_despertar_cooldown=? WHERE id=?");
        $stmt->execute([$proximo_cooldown, $db['id']]);
        $_SESSION['despertar_msg'] = 'lose';
    }

    echo "<script>self.location='?p=despertar';</script>"; exit;
}

// ——— RESULTADO FINAL DO SORTEIO ———
if(isset($_GET['resultado']) && isset($_SESSION['despertar_resultado'])){
    $resultado = (int)$_SESSION['despertar_resultado'];
    unset($_SESSION['despertar_resultado']);
    $nomes = [1=>'Sharingan', 2=>'Byakugan', 3=>'Rinnegan'];
    $cores = [1=>'#CC2200', 2=>'#88BBFF', 3=>'#9933CC'];
    ?>
<style>
@keyframes eyeReveal { 0%{opacity:0;transform:scale(0.3) rotate(-10deg);} 60%{transform:scale(1.15) rotate(3deg);} 100%{opacity:1;transform:scale(1) rotate(0);} }
@keyframes glow { 0%,100%{box-shadow:0 0 15px currentColor;} 50%{box-shadow:0 0 50px currentColor, 0 0 80px currentColor;} }
</style>
<div class="box_top" style="background:linear-gradient(90deg,#0a0010,#1a0033,#0a0010);">✨ Despertar da Linhagem — Revelação</div>
<div class="box_middle" style="background:radial-gradient(ellipse at center,#0a0015,#000);text-align:center;padding:25px;">
    <?php if($resultado > 0): ?>
        <div style="animation:eyeReveal 1.5s ease forwards;display:inline-block;border:3px solid <?php echo $cores[$resultado]; ?>;border-radius:12px;padding:25px 40px;background:linear-gradient(135deg,#0a0005,#15001f);box-shadow:0 0 40px <?php echo $cores[$resultado]; ?>,0 0 80px <?php echo $cores[$resultado]; ?>44;margin:10px auto;animation:glow 2s ease infinite;color:<?php echo $cores[$resultado]; ?>;">
            <p style="font-size:15px;color:#FFD700;text-shadow:0 0 12px #FF8800;margin:0 0 12px;">⚡ Seu golpe atravessou a Sombra!</p>
            <img src="_img/doujutsus/<?php echo strtolower($nomes[$resultado]); ?>.jpg" style="width:120px;height:120px;border-radius:50%;border:4px solid <?php echo $cores[$resultado]; ?>;display:block;margin:0 auto 12px;" />
            <p style="font-size:26px;font-weight:bold;color:<?php echo $cores[$resultado]; ?>;text-shadow:0 0 20px <?php echo $cores[$resultado]; ?>;margin:0 0 8px;"><?php echo $nomes[$resultado]; ?></p>
            <p style="color:#BBB;font-size:12px;margin:0;">"O sangue de seus ancestrais finalmente despertou.<br>Este olho é agora parte de você — para sempre."</p>
        </div>
    <?php else: ?>
        <div style="border:2px solid #444;border-radius:10px;padding:25px;max-width:380px;margin:0 auto;background:#0a0a0a;">
            <p style="font-size:40px;margin:0 0 10px;">💔</p>
            <p style="font-size:17px;color:#888;margin:0 0 8px;">Nenhuma herança foi encontrada...</p>
            <p style="color:#555;font-size:12px;margin:0 0 15px;">"A Sombra dissipou-se. Você olha para suas mãos —<br>nenhum traço de linhagem especial. Talvez em outra vida."</p>
            <p style="color:#FF6600;font-weight:bold;margin:0;">Próxima tentativa disponível em <b>15 dias</b>.</p>
        </div>
    <?php endif; ?>
    <br><input type="button" style="background:linear-gradient(180deg,#333,#111);color:#AAA;border:1px solid #555;padding:9px 22px;cursor:pointer;border-radius:4px;margin-top:12px;" value="← Voltar para a Home" onclick="location.href='?p=home'" />
</div>
<div class="box_bottom"></div>
    <?php return;
}

// ——— VERIFICAÇÕES DE ACESSO ———
if($db['doujutsu'] > 0){ echo "<script>self.location='?p=home';</script>"; exit; }

if($db['nivel'] < 20){ ?>
<div class="box_top">🔮 Despertar da Linhagem</div>
<div class="box_middle" style="text-align:center;padding:20px;">
    <p style="color:#888;">Nível insuficiente. Retorne quando atingir o <b style="color:#FFD700;">Nível 20</b>.</p>
    <p style="color:#555;font-size:12px;">Seu nível atual: <?php echo $db['nivel']; ?></p>
    <input type="button" style="background:#222;color:#888;border:1px solid #444;padding:8px 20px;cursor:pointer;" value="Voltar" onclick="location.href='?p=home'" />
</div>
<div class="box_bottom"></div>
<?php return; }

// Cooldown de 15 dias (tentativa de linhagem perdida)
if(!empty($db['doujutsu_proxima_tentativa']) && strtotime($db['doujutsu_proxima_tentativa']) > time()){
    $dias = ceil((strtotime($db['doujutsu_proxima_tentativa']) - time()) / 86400); ?>
<div class="box_top">🔮 Despertar da Linhagem — Em Espera</div>
<div class="box_middle" style="text-align:center;padding:20px;">
    <p style="font-size:32px;">⏳</p>
    <p style="color:#888;">Sua linhagem não respondeu ao ritual. O sangue precisa de tempo para se recuperar.</p>
    <p style="color:#FF6600;font-weight:bold;font-size:16px;">Próxima tentativa em <?php echo $dias; ?> dia(s).</p>
    <input type="button" style="background:#222;color:#888;border:1px solid #444;padding:8px 20px;cursor:pointer;" value="Voltar" onclick="location.href='?p=home'" />
</div>
<div class="box_bottom"></div>
<?php return; }

// ——— DADOS DA ARENA ———
$cooldown_ts = (!empty($db['doujutsu_despertar_cooldown'])) ? strtotime($db['doujutsu_despertar_cooldown']) : 0;
$em_cooldown = ($cooldown_ts > time());
$segundos_cd = $em_cooldown ? ($cooldown_ts - time()) : 0;

// Mensagem do último ataque
$errou = isset($_SESSION['despertar_msg']) && $_SESSION['despertar_msg'] === 'lose';
unset($_SESSION['despertar_msg']);

// Avatar do player
$avatar_src = '_img/personagens/' . ($db['personagem'] ?? 'naruto') . '/' . ($db['avatar'] ?? '1') . '.jpg';
?>

<style>
.btn-atacar{background:linear-gradient(180deg,#8B0000,#3D0000);color:#FFD700;border:2px solid #FF4400;padding:13px 35px;cursor:pointer;font-weight:bold;font-size:16px;border-radius:6px;text-shadow:1px 1px 3px #000;letter-spacing:1px;transition:all 0.2s;}
.btn-atacar:hover{background:linear-gradient(180deg,#AA0000,#600000);box-shadow:0 0 15px #FF440077;}
.btn-atacar:disabled{background:#2a2a2a;color:#555;border-color:#333;cursor:not-allowed;box-shadow:none;}
@keyframes pulse{0%,100%{box-shadow:0 0 10px #FF440044;}50%{box-shadow:0 0 30px #FF4400AA;}}
</style>

<div class="box_top" style="background:linear-gradient(90deg,#0a0010,#1a0033,#0a0010);font-size:14px;letter-spacing:1px;">⚔️ Ritual de Despertar da Linhagem</div>
<div class="box_middle" style="background:radial-gradient(ellipse at center,#0d0008,#000);padding:20px;">

    <?php if($errou): ?>
    <div style="text-align:center;padding:10px 15px;border-radius:6px;margin-bottom:15px;font-weight:bold;font-size:14px;background:#1a0000;border:1px solid #AA0000;color:#FF4444;">
        ❌ A Sombra desviou! Aguarde <?php echo $segundos_cd > 0 ? $segundos_cd : 30; ?>s para o próximo ataque.
    </div>
    <?php endif; ?>

    <!-- ARENA DE BATALHA -->
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <!-- PLAYER -->
            <td width="40%" style="text-align:center;vertical-align:middle;">
                <img src="<?php echo $avatar_src; ?>" style="width:110px;height:110px;object-fit:cover;border-radius:8px;border:3px solid #44FF88;box-shadow:0 0 20px #00AA0066;display:block;margin:0 auto;"
                     onerror="this.src='_img/personagens/no_avatar.jpg'" />
                <p style="color:#88FF88;font-weight:bold;margin:8px 0 0;font-size:13px;"><?php echo htmlspecialchars($db['usuario']); ?></p>
            </td>

            <!-- VS -->
            <td width="20%" style="text-align:center;vertical-align:middle;">
                <div style="font-size:28px;font-weight:bold;color:#FF4400;text-shadow:0 0 15px #FF4400;animation:pulse 2s infinite;">VS</div>
            </td>

            <!-- BOT -->
            <td width="40%" style="text-align:center;vertical-align:middle;">
                <img src="_img/Despertar/Despertar.png" style="width:110px;height:110px;object-fit:contain;border-radius:8px;border:3px solid #CC0000;box-shadow:0 0 20px #AA000066;display:block;margin:0 auto;"
                     onerror="this.src='_img/offline.png'" />
                <p style="color:#FF6666;font-weight:bold;margin:8px 0 0;font-size:13px;">Sombra da Linhagem</p>
            </td>
        </tr>
    </table>

    <!-- NARRATIVA -->
    <div style="text-align:center;margin:15px 0;padding:12px 20px;background:#0a0005;border-left:3px solid #660066;border-right:3px solid #660066;color:#AA88CC;font-size:12px;font-style:italic;line-height:1.6;">
        "A Sombra da Linhagem paira diante de você. Ela só pode ser vencida por um golpe certeiro — concentre toda a sua força e ataque no momento exato."
    </div>

    <!-- BOTÃO / COOLDOWN -->
    <div style="text-align:center;margin-top:12px;">
        <?php if($em_cooldown): ?>
            <p style="color:#FF9900;font-size:13px;margin:0 0 8px;">⏳ Recuperando... <b id="cd_timer"><?php echo $segundos_cd; ?>s</b></p>
            <form method="post">
                <input type="hidden" name="action" value="atacar">
                <input type="submit" class="btn-atacar" value="⚔️ Atacar" disabled id="btn_atacar" />
            </form>
            <script>
            var cd = <?php echo $segundos_cd; ?>;
            var btn = document.getElementById('btn_atacar');
            var lbl = document.getElementById('cd_timer');
            var itv = setInterval(function(){
                cd--;
                if(cd <= 0){
                    clearInterval(itv);
                    lbl.innerText = '';
                    btn.disabled = false;
                    btn.value = '⚔️ Atacar';
                    document.getElementById('cd_label').style.display='none';
                } else {
                    lbl.innerText = cd + 's';
                }
            }, 1000);
            </script>
            <span id="cd_label" style="display:block;color:#666;font-size:11px;margin-top:5px;">A Sombra desviou... aguarde para tentar novamente.</span>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="action" value="atacar">
                <input type="submit" class="btn-atacar" value="⚔️ Atacar" />
            </form>
            <p style="color:#555;font-size:11px;margin:8px 0 0;">Um único golpe certeiro é o suficiente. Se errar, aguarde 30 segundos.</p>
        <?php endif; ?>

        <br>
        <input type="button" style="background:#111;color:#666;border:1px solid #333;padding:6px 18px;cursor:pointer;border-radius:3px;" value="← Voltar" onclick="location.href='?p=home'" />
    </div>

</div>
<div class="box_bottom"></div>
