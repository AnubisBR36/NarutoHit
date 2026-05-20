<?php
require_once('conexao.php');
require_once('verificar.php');
require_once('funcoes.php');
require_once('funcoes_despertar.php');

if(!$db){ echo "<script>self.location='?p=home'</script>"; exit; }

// ─── VERIFICAÇÕES DE ACESSO ─────────────────────────────────────────────────
if($db['doujutsu'] > 0){ echo "<script>self.location='?p=home';</script>"; exit; }

if($db['nivel'] < 20){ ?>
<div class="box_top">Despertar da Linhagem</div>
<div class="box_middle">
    Seu nível ainda não é suficiente para iniciar o ritual.<div class="sep"></div>
    <p>Retorne quando atingir o <b>Nível 20</b>. (Nível atual: <b><?php echo (int)$db['nivel']; ?></b>)</p>
    <div align="center"><input type="button" class="botao" onclick="location.href='?p=home'" value="← Voltar"></div>
</div>
<div class="box_bottom"></div>
<?php return; }

// ─── REQUISITOS PARA O RITUAL ────────────────────────────────────────────────
try {
    $cfg_stmt = $conexao->query("SELECT nome, valor FROM configuracoes WHERE nome IN ('despertar_req_batalhas','despertar_req_vitorias','despertar_req_missoes')");
    $cfg_rows = $cfg_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch(Exception $e){ $cfg_rows = []; }
$req_batalhas = max(1, (int)($cfg_rows['despertar_req_batalhas'] ?? 100));
$req_vitorias = max(1, (int)($cfg_rows['despertar_req_vitorias'] ?? 10));
$req_missoes  = max(1, (int)($cfg_rows['despertar_req_missoes']  ?? 150));

// ─── MINI-RANKING: últimos despertes ────────────────────────────────────────
$_dj_nomes = [1=>'Sharingan', 2=>'Byakugan', 3=>'Rinnegan'];
$_dj_cores = [1=>'#CC2200',  2=>'#2255CC',  3=>'#7722AA'];
$_dj_imgs  = [1=>'_img/doujutsus/sharingan.jpg', 2=>'_img/doujutsus/byakugan.jpg', 3=>'_img/doujutsus/rinnegan.jpg'];
$_ultimos_dj = [];
try {
    $_ultimos_dj = $conexao->query("
        SELECT u.usuario, u.doujutsu, h.data
        FROM despertar_historico h
        JOIN usuarios u ON u.id = h.usuario_id
        WHERE h.resultado > 0
        ORDER BY h.data DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e){ $_ultimos_dj = []; }

function _desp_mini_ranking($ultimos, $nomes, $cores, $imgs){
    echo '<div class="sep"></div>';
    echo '<b>Últimas Linhagens Despertas</b><div class="sep"></div>';
    if(empty($ultimos)){
        echo '<p><i>Nenhum ninja despertou ainda. Seja o primeiro.</i></p>';
    } else {
        echo '<table width="100%" cellpadding="3" cellspacing="0">';
        foreach($ultimos as $u){
            $dj = (int)$u['doujutsu'];
            $cor = $cores[$dj] ?? '#888';
            $img = $imgs[$dj] ?? '';
            $nome_dj = $nomes[$dj] ?? '?';
            $data = date('d/m/Y', strtotime($u['data']));
            echo '<tr>';
            echo '<td width="24" align="center">';
            if($img) echo "<img src='{$img}' style='width:16px;height:16px;border-radius:50%;vertical-align:middle;'>";
            echo '</td>';
            echo '<td>' . htmlspecialchars($u['usuario']) . '</td>';
            echo "<td><b style='color:{$cor};'>{$nome_dj}</b></td>";
            echo "<td align='right' style='color:#888;font-size:11px;'>{$data}</td>";
            echo '</tr>';
        }
        echo '</table>';
    }
    echo '<div align="center" style="margin-top:8px;"><a href="?p=rank_doujutsu">Ver ranking completo de linhagens →</a></div>';
}

$bat = (int)($db['batalhas'] ?? 0);
$vit = (int)($db['vitorias'] ?? 0);
$mis = (int)($db['missoes_longas'] ?? 0);
$ok_bat = ($bat >= $req_batalhas);
$ok_vit = ($vit >= $req_vitorias);
$ok_mis = ($mis >= $req_missoes);

// ─── REQUISITOS NÃO CUMPRIDOS ────────────────────────────────────────────────
if(!$ok_bat || !$ok_vit || !$ok_mis){ ?>
<div class="box_top">Despertar da Linhagem — Requisitos</div>
<div class="box_middle">
    Você ainda não cumpriu todos os requisitos para iniciar o Ritual de Despertar da Linhagem.<div class="sep"></div>
    <table width="100%" cellpadding="4" cellspacing="0">
        <tr>
            <td width="24" align="center"><?php echo $ok_bat ? '<b style="color:green;">✓</b>' : '○'; ?></td>
            <td><b>Batalhas realizadas:</b></td>
            <td><b><?php echo $bat; ?>/<?php echo $req_batalhas; ?></b></td>
            <td><?php echo $ok_bat ? '<span style="color:green;">Completo</span>' : '<span style="color:#CC4400;">Pendente</span>'; ?></td>
        </tr>
        <tr>
            <td align="center"><?php echo $ok_vit ? '<b style="color:green;">✓</b>' : '○'; ?></td>
            <td><b>Vitórias em batalha:</b></td>
            <td><b><?php echo $vit; ?>/<?php echo $req_vitorias; ?></b></td>
            <td><?php echo $ok_vit ? '<span style="color:green;">Completo</span>' : '<span style="color:#CC4400;">Pendente</span>'; ?></td>
        </tr>
        <tr>
            <td align="center"><?php echo $ok_mis ? '<b style="color:green;">✓</b>' : '○'; ?></td>
            <td><b>Missões longas (≥10h):</b></td>
            <td><b><?php echo $mis; ?>/<?php echo $req_missoes; ?></b></td>
            <td><?php echo $ok_mis ? '<span style="color:green;">Completo</span>' : '<span style="color:#CC4400;">Pendente</span>'; ?></td>
        </tr>
    </table>
    <div align="center" style="margin-top:10px;"><input type="button" class="botao" onclick="location.href='?p=home'" value="← Voltar"></div>
    <?php _desp_mini_ranking($_ultimos_dj, $_dj_nomes, $_dj_cores, $_dj_imgs); ?>
</div>
<div class="box_bottom"></div>
<?php return; }

// ─── ABANDONAR RITUAL ────────────────────────────────────────────────────────
if(isset($_GET['abandonar'])){
    unset($_SESSION['despertar_fase'], $_SESSION['despertar_roll']);
    echo "<script>self.location='?p=home';</script>"; exit;
}

// ─── COOLDOWN DE 15 DIAS ─────────────────────────────────────────────────────
if(!empty($db['doujutsu_proxima_tentativa']) && strtotime($db['doujutsu_proxima_tentativa']) > time()){
    $ts_prox = strtotime($db['doujutsu_proxima_tentativa']);
    $diff    = $ts_prox - time();
    $dias    = floor($diff / 86400);
    $horas   = floor(($diff % 86400) / 3600); ?>
<div class="box_top">Despertar da Linhagem — Em Recuperação</div>
<div class="box_middle">
    Seu sangue precisa de tempo para se recompor após o ritual.<div class="sep"></div>
    <table width="100%" cellpadding="4" cellspacing="0">
        <tr>
            <td><b>Próxima tentativa disponível em:</b></td>
            <td><b style="color:#FF6600; font-size:16px;"><?php echo $dias; ?>d <?php echo $horas; ?>h</b></td>
        </tr>
    </table>
    <div class="sep"></div>
    <div align="center"><input type="button" class="botao" onclick="location.href='?p=home'" value="← Voltar"></div>
    <?php _desp_mini_ranking($_ultimos_dj, $_dj_nomes, $_dj_cores, $_dj_imgs); ?>
</div>
<div class="box_bottom"></div>
<?php return; }

// ─── ESTADO DA FASE ──────────────────────────────────────────────────────────
$fase_atual = (int)($_SESSION['despertar_fase'] ?? 0);

// ─── POST HANDLERS ───────────────────────────────────────────────────────────
if(isset($_POST['action'])){
    $action = $_POST['action'];

    if($action === 'iniciar_fase2'){
        $roll = rand(1, 100);
        if($roll <= 5)      $res = 3; // Rinnegan 5%
        elseif($roll <= 20) $res = 1; // Sharingan 15%
        elseif($roll <= 40) $res = 2; // Byakugan 20%
        else                $res = 0; // Nenhum 60%
        $_SESSION['despertar_fase'] = 2;
        $_SESSION['despertar_roll'] = $res;
        echo "<script>self.location='?p=despertar';</script>"; exit;
    }

    if($action === 'escolher_selo'){
        if(!isset($_SESSION['despertar_roll'])){
            unset($_SESSION['despertar_fase']);
            echo "<script>self.location='?p=despertar';</script>"; exit;
        }
        $_SESSION['despertar_fase'] = 3;
        echo "<script>self.location='?p=despertar';</script>"; exit;
    }

    if($action === 'revelar'){
        if(!isset($_SESSION['despertar_roll'])){
            unset($_SESSION['despertar_fase']);
            echo "<script>self.location='?p=despertar';</script>"; exit;
        }
        $res = (int)$_SESSION['despertar_roll'];
        unset($_SESSION['despertar_fase'], $_SESSION['despertar_roll']);
        if($res > 0){
            $stmt = $conexao->prepare("UPDATE usuarios SET doujutsu=?, doujutsu_nivel=1, doujutsu_exp=0, doujutsu_expmax=100, doujutsu_despertar_cooldown=NULL, doujutsu_proxima_tentativa=NULL WHERE id=?");
            $stmt->execute([$res, $db['id']]);
        } else {
            $proxima = date('Y-m-d H:i:s', strtotime('+15 days'));
            $stmt = $conexao->prepare("UPDATE usuarios SET doujutsu=0, doujutsu_despertar_cooldown=NULL, doujutsu_proxima_tentativa=? WHERE id=?");
            $stmt->execute([$proxima, $db['id']]);
        }
        try {
            $conexao->prepare("
                INSERT INTO despertar_historico (usuario_id, resultado, batalhas, vitorias, missoes_longas, data)
                SELECT ?, ?, batalhas, vitorias, missoes_longas, NOW()
                FROM usuarios WHERE id = ?
            ")->execute([$db['id'], $res, $db['id']]);
        } catch(Exception $e){}
        despertar_resetar_notificacao($conexao, $db['id']);
        $_SESSION['despertar_resultado_final'] = $res;
        echo "<script>self.location='?p=despertar&revelado=1';</script>"; exit;
    }
}

// ─── RESULTADO FINAL ─────────────────────────────────────────────────────────
if(isset($_GET['revelado']) && isset($_SESSION['despertar_resultado_final'])){
    $res = (int)$_SESSION['despertar_resultado_final'];
    unset($_SESSION['despertar_resultado_final']);
    $nomes = [1=>'Sharingan', 2=>'Byakugan', 3=>'Rinnegan'];
    $cores  = [1=>'#CC2200',  2=>'#2255CC',  3=>'#7722AA'];
    $frases = [
        1 => 'O fogo do Clã Uchiha arde em seus olhos.',
        2 => 'A visão dos 360 graus do Clã Hyuga desperta.',
        3 => 'O poder dos Seis Caminhos se manifesta em você.',
    ];
    ?>
<style>
@keyframes revealIn { 0%{opacity:0;transform:scale(0.5);} 100%{opacity:1;transform:scale(1);} }
@keyframes glowBeat { 0%,100%{box-shadow:0 0 12px var(--gc);} 50%{box-shadow:0 0 30px var(--gc), 0 0 60px var(--gc);} }
</style>
<div class="box_top">Despertar da Linhagem — Resultado</div>
<div class="box_middle" align="center">
<?php if($res > 0):
    $c = $cores[$res]; ?>
    <div class="sep"></div>
    <b style="font-size:14px;">O sangue ancestral respondeu ao chamado!</b>
    <div class="sep"></div>
    <div style="--gc:<?php echo $c; ?>;display:inline-block;border:3px solid <?php echo $c; ?>;border-radius:10px;padding:20px 36px;animation:revealIn 1s ease forwards, glowBeat 2s ease 1s infinite;">
        <img src="_img/doujutsus/<?php echo strtolower($nomes[$res]); ?>.jpg"
             style="width:100px;height:100px;border-radius:50%;border:3px solid <?php echo $c; ?>;display:block;margin:0 auto 10px;"
             onerror="this.style.display='none'" />
        <p style="font-size:22px;font-weight:bold;color:<?php echo $c; ?>;margin:0 0 6px;"><?php echo $nomes[$res]; ?></p>
        <p style="font-size:12px;color:#555;margin:0;font-style:italic;">"<?php echo $frases[$res]; ?>"</p>
    </div>
    <div class="sep"></div>
    <p>Parabéns! Você agora possui o <b style="color:<?php echo $c; ?>;"><?php echo $nomes[$res]; ?></b>.</p>
<?php else: ?>
    <div class="sep"></div>
    <b style="font-size:14px;">Os ancestrais permaneceram em silêncio...</b>
    <div class="sep"></div>
    <p>Nenhuma linhagem respondeu ao chamado desta vez.</p>
    <p>Sua próxima tentativa estará disponível em <b style="color:#FF6600;">15 dias</b>.</p>
<?php endif; ?>
    <input type="button" class="botao" onclick="location.href='?p=home'" value="← Voltar para Home">
</div>
<div class="box_bottom"></div>
<?php return; }

// ─── FASE 1: CONCENTRAÇÃO ────────────────────────────────────────────────────
if($fase_atual < 2){ ?>
<style>
@keyframes orbBreath { 0%,100%{transform:scale(1);box-shadow:0 0 18px #FF990044;} 50%{transform:scale(1.06);box-shadow:0 0 36px #FF990077;} }
.btn-canalizar { background:linear-gradient(180deg,#CC6600,#993300);color:#fff;border:2px solid #FF9900;padding:12px 36px;font-size:14px;font-weight:bold;cursor:pointer;border-radius:4px;letter-spacing:1px;transition:all 0.2s; }
.btn-canalizar:hover { background:linear-gradient(180deg,#FF8800,#CC5500);box-shadow:0 0 14px #FF990066; }
.btn-canalizar:disabled { background:#ccc;color:#888;border-color:#aaa;cursor:not-allowed;box-shadow:none; }
</style>
<div class="box_top">Ritual da Linhagem — Fase I: Concentração</div>
<div class="box_middle" align="center">
    <table width="100%" cellpadding="2" cellspacing="0" style="margin-bottom:8px;">
        <tr align="center">
            <td><b style="color:#FF6600;">● Concentração</b></td>
            <td style="color:#aaa;">○ Invocação</td>
            <td style="color:#aaa;">○ Revelação</td>
        </tr>
    </table>
    <div class="sep"></div>

    <div style="width:120px;height:120px;margin:0 auto 14px;border-radius:50%;border:3px solid #FF9900;background:#fff9f0;animation:orbBreath 3s ease-in-out infinite;position:relative;">
        <div id="orb_symbol" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:36px;line-height:1;">🩸</div>
        <svg width="126" height="126" style="position:absolute;top:-3px;left:-3px;" viewBox="0 0 126 126">
            <circle cx="63" cy="63" r="58" fill="none" stroke="#FF9900" stroke-width="4"
                    stroke-dasharray="365" stroke-dashoffset="365"
                    id="orb_ring" stroke-linecap="round" transform="rotate(-90 63 63)"
                    style="transition:stroke-dashoffset 0.08s linear;" />
        </svg>
    </div>
    <p id="orb_label" style="color:#888;font-size:12px;margin:0 0 12px;">Aguardando canalização...</p>

    <div class="sep"></div>
    <i style="font-size:12px;color:#666;">"Feche seus olhos. Sinta o chakra fluir por suas veias. O sangue ancestral aguarda — mas apenas o verdadeiro herdeiro pode invocá-lo."</i>
    <div class="sep"></div>

    <form method="POST" id="form_fase1">
        <input type="hidden" name="action" value="iniciar_fase2">
        <button type="button" class="btn-canalizar" id="btn_canalizar" onclick="iniciarCanalizacao()">Canalizar Chakra</button>
    </form>
    <p style="font-size:11px;color:#aaa;margin:6px 0 0;">Mantenha o foco por 4 segundos</p>
    <div class="sep"></div>
    <a href="?p=despertar&abandonar=1" style="font-size:11px;color:#aaa;">Abandonar ritual</a>
    <?php _desp_mini_ranking($_ultimos_dj, $_dj_nomes, $_dj_cores, $_dj_imgs); ?>
</div>
<div class="box_bottom"></div>

<script>
(function(){
    var canalizando = false;
    var prog = 0, total = 4000, step = 50;
    var circumference = 365;
    var ring   = document.getElementById('orb_ring');
    var label  = document.getElementById('orb_label');
    var symbol = document.getElementById('orb_symbol');
    var btn    = document.getElementById('btn_canalizar');

    window.iniciarCanalizacao = function(){
        if(canalizando) return;
        canalizando = true;
        btn.disabled = true;
        btn.textContent = 'Canalizando...';
        label.textContent = 'Concentrando chakra...';

        var iv = setInterval(function(){
            prog += step;
            var pct = prog / total;
            ring.style.strokeDashoffset = circumference * (1 - pct);

            if(prog >= total){
                clearInterval(iv);
                label.textContent = 'Chakra canalizado!';
                symbol.textContent = '⚡';
                ring.style.stroke = '#FFD700';
                ring.style.strokeDashoffset = '0';
                setTimeout(function(){
                    document.getElementById('form_fase1').submit();
                }, 700);
            }
        }, step);
    };
})();
</script>
<?php return; }

// ─── FASE 2: INVOCAÇÃO ───────────────────────────────────────────────────────
if($fase_atual == 2){
    if(!isset($_SESSION['despertar_roll'])){
        unset($_SESSION['despertar_fase']);
        echo "<script>self.location='?p=despertar';</script>"; exit;
    }

    $selos = [
        ['simbolo'=>'巳', 'nome'=>'Serpente', 'sub'=>'Selo da Transformação', 'cor'=>'#CC4400'],
        ['simbolo'=>'龍', 'nome'=>'Dragão',   'sub'=>'Selo da Invocação',     'cor'=>'#2255CC'],
        ['simbolo'=>'虎', 'nome'=>'Tigre',    'sub'=>'Selo da Força',          'cor'=>'#AA8800'],
    ];
    shuffle($selos);
    ?>
<style>
@keyframes sealIn { from{opacity:0;transform:translateY(20px);} to{opacity:1;transform:translateY(0);} }
.selo-card { display:inline-flex;flex-direction:column;align-items:center;justify-content:center;width:90px;padding:16px 10px;border-radius:6px;cursor:pointer;transition:transform 0.2s;background:#fffaf0;border:2px solid; }
.selo-card:hover { transform:translateY(-4px); }
.selo-card:nth-of-type(1){animation:sealIn 0.4s ease 0.1s both;}
.selo-card:nth-of-type(2){animation:sealIn 0.4s ease 0.25s both;}
.selo-card:nth-of-type(3){animation:sealIn 0.4s ease 0.4s both;}
</style>
<div class="box_top">Ritual da Linhagem — Fase II: Invocação</div>
<div class="box_middle" align="center">
    <table width="100%" cellpadding="2" cellspacing="0" style="margin-bottom:8px;">
        <tr align="center">
            <td style="color:#888;">✓ Concentração</td>
            <td><b style="color:#FF6600;">● Invocação</b></td>
            <td style="color:#aaa;">○ Revelação</td>
        </tr>
    </table>
    <div class="sep"></div>
    <b>Três selos surgem diante de você</b><br>
    <i style="font-size:12px;color:#666;">"Apenas um ressoa com o seu sangue. Siga seu instinto — o chakra guiará sua mão."</i>
    <div class="sep"></div>

    <div style="display:flex;justify-content:center;gap:12px;flex-wrap:wrap;margin:10px 0;">
    <?php foreach($selos as $s): ?>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="escolher_selo">
            <button type="submit" class="selo-card" style="border-color:<?php echo $s['cor']; ?>;">
                <div style="font-size:36px;color:<?php echo $s['cor']; ?>;font-family:serif;line-height:1.1;margin-bottom:8px;"><?php echo $s['simbolo']; ?></div>
                <div style="font-size:12px;font-weight:bold;color:<?php echo $s['cor']; ?>;margin-bottom:2px;"><?php echo $s['nome']; ?></div>
                <div style="font-size:10px;color:#888;"><?php echo $s['sub']; ?></div>
            </button>
        </form>
    <?php endforeach; ?>
    </div>

    <div class="sep"></div>
    <a href="?p=despertar&abandonar=1" style="font-size:11px;color:#aaa;">Abandonar ritual</a>
</div>
<div class="box_bottom"></div>
<?php return; }

// ─── FASE 3: REVELAÇÃO ───────────────────────────────────────────────────────
if($fase_atual == 3){
    if(!isset($_SESSION['despertar_roll'])){
        unset($_SESSION['despertar_fase']);
        echo "<script>self.location='?p=despertar';</script>"; exit;
    } ?>
<style>
@keyframes sealSpin { 0%{transform:rotate(0deg);} 100%{transform:rotate(360deg);} }
</style>
<div class="box_top">Ritual da Linhagem — Fase III: Revelação</div>
<div class="box_middle" align="center">
    <table width="100%" cellpadding="2" cellspacing="0" style="margin-bottom:8px;">
        <tr align="center">
            <td style="color:#888;">✓ Concentração</td>
            <td style="color:#888;">✓ Invocação</td>
            <td><b style="color:#FF6600;">● Revelação</b></td>
        </tr>
    </table>
    <div class="sep"></div>

    <div style="font-size:48px;animation:sealSpin 1.8s linear infinite;display:inline-block;margin:10px 0;color:#FF9900;">⊕</div>
    <div class="sep"></div>
    <b style="font-size:14px;">O destino está traçado</b><br>
    <i style="font-size:12px;color:#666;">"O véu entre você e seus ancestrais está se rasgando... O ritual atingiu seu ápice."</i>
    <div class="sep"></div>

    <form method="POST" id="form_revelar">
        <input type="hidden" name="action" value="revelar">
        <input type="submit" class="botao" value="Revelar Destino">
    </form>
    <script>setTimeout(function(){ document.getElementById('form_revelar').submit(); }, 2500);</script>
</div>
<div class="box_bottom"></div>
<?php return; }

// Fallback: fase inválida — reiniciar
unset($_SESSION['despertar_fase'], $_SESSION['despertar_roll']);
echo "<script>self.location='?p=despertar';</script>"; exit;
