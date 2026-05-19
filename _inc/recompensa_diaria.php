<?php
/**
 * Sistema de Recompensa de Login Diário
 * Streak de 7 dias com recompensas editáveis pelo admin.
 * Imagens: _img/yens.png, _img/Icones/experiencia.png, _img/star.png, _img/Icones/Chakra.png
 */

define('_RD_YENS',  '_img/yens.png');
define('_RD_EXP',   '_img/Icones/experiencia.png');
define('_RD_STAR',  '_img/star.png');
define('_RD_CHAKRA','_img/Icones/Chakra.png');


$_rd_recompensas = [];
$_rd_dados       = null;
$_rd_recompensa  = null;
$_rd_streak_novo = 0;

try {
    // Tabela de streak
    $conexao->exec("CREATE TABLE IF NOT EXISTS login_streak (
        id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
        usuarioid INTEGER NOT NULL UNIQUE,
        streak INTEGER NOT NULL DEFAULT 1,
        ultimo_login DATE NOT NULL,
        total_logins INTEGER NOT NULL DEFAULT 1
    )");

    // Tabela de configuração de recompensas
    $conexao->exec("CREATE TABLE IF NOT EXISTS login_streak_config (
        dia TINYINT NOT NULL PRIMARY KEY,
        yens INTEGER NOT NULL DEFAULT 0,
        exp INTEGER NOT NULL DEFAULT 0
    )");
    // Adicionar coluna icone se ainda não existir
    try { $conexao->exec("ALTER TABLE login_streak_config ADD COLUMN icone VARCHAR(100) NOT NULL DEFAULT ''"); } catch(PDOException $e2) {}

    // Inserir defaults se vazio
    $cnt = (int)$conexao->query("SELECT COUNT(*) FROM login_streak_config")->fetchColumn();
    if ($cnt === 0) {
        $defaults = [
            [1, 100,  0],
            [2, 150,  0],
            [3, 200, 50],
            [4, 300,  0],
            [5, 250,100],
            [6, 400,  0],
            [7, 600,200],
        ];
        $ins = $conexao->prepare("INSERT INTO login_streak_config (dia, yens, exp) VALUES (?,?,?)");
        foreach ($defaults as $d) $ins->execute($d);
    }

    // Carregar recompensas do banco
    $rows_cfg = $conexao->query("SELECT dia, yens, exp, icone FROM login_streak_config ORDER BY dia")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows_cfg as $rc) {
        $y = (int)$rc['yens']; $e = (int)$rc['exp'];
        $lbl = $y > 0 ? number_format($y,0,'.',',').' yens' : '';
        if ($e > 0) $lbl .= ($lbl ? ' + ' : '') . $e . ' exp';
        $ico = (string)($rc['icone'] ?? '');
        // Resolver imagem: ícone customizado > fallback por tipo
        if ($ico && file_exists("_img/Login/$ico")) {
            $img = "_img/Login/$ico";
        } elseif ($e > 0) {
            $img = _RD_EXP;
        } elseif ((int)$rc['dia'] === 7) {
            $img = _RD_STAR;
        } else {
            $img = _RD_YENS;
        }
        $_rd_recompensas[(int)$rc['dia']] = ['yens'=>$y,'exp'=>$e,'label'=>$lbl,'img'=>$img];
    }

    $hoje = date('Y-m-d');

    $stmtSel = $conexao->prepare("SELECT streak, ultimo_login, total_logins FROM login_streak WHERE usuarioid = ?");
    $stmtSel->execute([$db['id']]);
    $row = $stmtSel->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        // Primeiro login — inserir e ENTREGAR recompensa do dia 1
        $conexao->prepare("INSERT INTO login_streak (usuarioid, streak, ultimo_login, total_logins) VALUES (?,1,?,1)")
                ->execute([$db['id'], $hoje]);
        $_rd_streak_novo = 1;
        $_rd_recompensa  = $_rd_recompensas[1] ?? ['yens'=>100,'exp'=>0,'label'=>'100 yens'];

        // Entregar recompensa
        $conexao->prepare("UPDATE usuarios SET yens = yens + ?, yens_fat = yens_fat + ? WHERE id = ?")
                ->execute([$_rd_recompensa['yens'], $_rd_recompensa['yens'], $db['id']]);
        if ($_rd_recompensa['exp'] > 0) {
            $conexao->prepare("UPDATE usuarios SET exp = exp + ?, exptotal = exptotal + ? WHERE id = ?")
                    ->execute([$_rd_recompensa['exp'], $_rd_recompensa['exp'], $db['id']]);
        }

    } else {
        $ultimo = $row['ultimo_login'];
        if ($ultimo !== $hoje) {
            $diff = (int)((strtotime($hoje) - strtotime($ultimo)) / 86400);
            $novoStreak = ($diff === 1) ? min((int)$row['streak'] + 1, 999) : 1;
            $_rd_streak_novo = $novoStreak;
            $diaRecomp = (($novoStreak - 1) % 7) + 1;
            $_rd_recompensa = $_rd_recompensas[$diaRecomp] ?? ['yens'=>100,'exp'=>0,'label'=>'100 yens'];

            // Entregar recompensa
            $conexao->prepare("UPDATE usuarios SET yens = yens + ?, yens_fat = yens_fat + ? WHERE id = ?")
                    ->execute([$_rd_recompensa['yens'], $_rd_recompensa['yens'], $db['id']]);
            if ($_rd_recompensa['exp'] > 0) {
                $conexao->prepare("UPDATE usuarios SET exp = exp + ?, exptotal = exptotal + ? WHERE id = ?")
                        ->execute([$_rd_recompensa['exp'], $_rd_recompensa['exp'], $db['id']]);
            }

            $conexao->prepare("UPDATE login_streak SET streak = ?, ultimo_login = ?, total_logins = total_logins + 1 WHERE usuarioid = ?")
                    ->execute([$novoStreak, $hoje, $db['id']]);
        }
    }

    // Recarregar dados para exibição
    $stmtSel2 = $conexao->prepare("SELECT streak, ultimo_login, total_logins FROM login_streak WHERE usuarioid = ?");
    $stmtSel2->execute([$db['id']]);
    $_rd_dados = $stmtSel2->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {}

if ($_rd_dados && !empty($_rd_recompensas)) {
    $streakAtual = (int)$_rd_dados['streak'];
    $proximoDia  = (($streakAtual) % 7) + 1;
    $_rd_proxima = $_rd_recompensas[$proximoDia] ?? $_rd_recompensas[1];
}
?>

<?php if ($_rd_recompensa):
    $diaAtualPopup  = (($_rd_streak_novo - 1) % 7) + 1;
    $imgRecompPopup = $_rd_recompensa['img'] ?? _RD_YENS;
?>
<style>
#rd-overlay {
    position:fixed;inset:0;background:rgba(0,0,0,0.88);
    z-index:10000;display:flex;align-items:center;justify-content:center;
    animation:rd-fadein 0.35s ease;
}
@keyframes rd-fadein{from{opacity:0}to{opacity:1}}
#rd-modal {
    background:linear-gradient(160deg,#0d0800 0%,#1e1200 60%,#0d0800 100%);
    border:2px solid #c8830a;border-radius:10px;
    padding:28px 36px;text-align:center;max-width:300px;width:90%;
    box-shadow:0 0 40px rgba(255,215,0,0.3),0 0 80px rgba(200,131,10,0.12);
    position:relative;
    animation:rd-popin 0.45s cubic-bezier(.17,.89,.32,1.28);
}
@keyframes rd-popin{
    from{transform:scale(0.55);opacity:0}
    to{transform:scale(1);opacity:1}
}
#rd-modal .rd-icone-img {
    width:56px;height:56px;object-fit:contain;
    animation:rd-bounce 0.6s 0.35s ease both;
    filter:drop-shadow(0 0 10px rgba(255,215,0,0.7));
}
@keyframes rd-bounce{
    0%{transform:scale(0.4) rotate(-8deg);opacity:0}
    70%{transform:scale(1.2) rotate(4deg);opacity:1}
    100%{transform:scale(1) rotate(0deg);opacity:1}
}
#rd-modal h2{color:#FFD700;margin:10px 0 4px;font-size:16px;letter-spacing:1px;font-family:Arial,sans-serif;}
#rd-modal .rd-streak-txt{
    color:#ff9900;font-size:12px;margin-bottom:10px;
    display:flex;align-items:center;justify-content:center;gap:5px;
}
#rd-modal .rd-streak-txt img{width:16px;height:16px;object-fit:contain;}
#rd-modal .rd-valor{
    font-size:20px;font-weight:bold;color:#fff;
    text-shadow:0 0 12px rgba(255,215,0,0.6);
    margin:8px 0;display:flex;align-items:center;justify-content:center;gap:6px;
}
#rd-modal .rd-valor img{width:20px;height:20px;object-fit:contain;}
#rd-modal .rd-dias{
    display:flex;justify-content:center;gap:5px;margin:14px 0 18px;
}
#rd-modal .rd-dia-box{
    width:34px;height:40px;border-radius:4px;border:1px solid #3a2800;
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    gap:2px;background:#1a1200;
}
#rd-modal .rd-dia-box img{width:16px;height:16px;object-fit:contain;opacity:0.5;}
#rd-modal .rd-dia-box .rd-num{font-size:11px;font-weight:bold;color:#555;line-height:1;}
#rd-modal .rd-dia-box.rd-passado{border-color:#5a4000;}
#rd-modal .rd-dia-box.rd-passado img{opacity:0.9;filter:sepia(0.4);}
#rd-modal .rd-dia-box.rd-passado .rd-num{color:#aa8800;}
#rd-modal .rd-dia-box.rd-hoje{
    border-color:#FFD700;background:#2a1c00;
    box-shadow:0 0 10px rgba(255,215,0,0.5);
    animation:rd-pulse-box 1.5s infinite;
}
@keyframes rd-pulse-box{
    0%,100%{box-shadow:0 0 8px rgba(255,215,0,0.45);}
    50%{box-shadow:0 0 20px rgba(255,215,0,0.85);}
}
#rd-modal .rd-dia-box.rd-hoje img{opacity:1;filter:drop-shadow(0 0 4px #FFD700);}
#rd-modal .rd-dia-box.rd-hoje .rd-num{color:#FFD700;}
.rd-btn{
    display:inline-block;padding:8px 28px;
    background:url('_img/fundo_botao.jpg') repeat-x center;
    border:1px solid #FFD700;color:#FFD700;
    font-size:13px;font-weight:bold;cursor:pointer;
    text-shadow:1px 1px 2px #000;letter-spacing:1px;font-family:Arial,sans-serif;
}
.rd-btn:hover{border-color:#fff;color:#fff;}
#rd-modal .rd-particles{position:absolute;inset:0;pointer-events:none;overflow:hidden;border-radius:10px;}
.rd-particle{position:absolute;border-radius:50%;animation:rd-float linear both infinite;}
@keyframes rd-float{
    from{transform:translateY(0) scale(1);opacity:0.9;}
    to{transform:translateY(-180px) scale(0.1);opacity:0;}
}
</style>
<div id="rd-overlay">
    <div id="rd-modal">
        <div class="rd-particles" id="rd-ptcls"></div>
        <img class="rd-icone-img" src="<?php echo $imgRecompPopup; ?>" alt="recompensa">
        <h2>Recompensa Diária!</h2>
        <div class="rd-streak-txt">
            <img src="<?php echo _RD_CHAKRA; ?>" alt="streak">
            Streak: <b><?php echo $_rd_streak_novo; ?> dia<?php echo $_rd_streak_novo > 1 ? 's' : ''; ?> seguido<?php echo $_rd_streak_novo > 1 ? 's' : ''; ?></b>
            <?php if ($_rd_streak_novo >= 7): ?><img src="<?php echo _RD_STAR; ?>" alt="star" style="width:14px;height:14px;"><?php endif; ?>
        </div>
        <div class="rd-valor">
            <img src="<?php echo $imgRecompPopup; ?>" alt="">
            +<?php echo htmlspecialchars($_rd_recompensa['label']); ?>
        </div>

        <div class="rd-dias">
        <?php for ($d = 1; $d <= 7; $d++):
            if ($d < $diaAtualPopup) $cls = 'rd-passado';
            elseif ($d === $diaAtualPopup) $cls = 'rd-hoje';
            else $cls = '';
            $dimg = $_rd_recompensas[$d]['img'] ?? _RD_YENS;
        ?>
            <div class="rd-dia-box <?php echo $cls; ?>">
                <div class="rd-num"><?php echo $d; ?></div>
                <img src="<?php echo htmlspecialchars($dimg); ?>" alt="dia <?php echo $d; ?>">
            </div>
        <?php endfor; ?>
        </div>

        <button class="rd-btn" onclick="document.getElementById('rd-overlay').remove()">Coletar</button>
    </div>
</div>
<script>
(function(){
    var ptcls=document.getElementById('rd-ptcls');
    var colors=['#FFD700','#ff9900','#fff8cc','#ffcc00','#ffe066'];
    for(var i=0;i<18;i++){
        var p=document.createElement('div');
        p.className='rd-particle';
        var delay=(Math.random()*1.6).toFixed(2);
        var dur=(1.4+Math.random()*1.8).toFixed(2);
        var left=(8+Math.random()*84).toFixed(1);
        var size=(3+Math.random()*5).toFixed(1);
        p.style.cssText='left:'+left+'%;bottom:8%;width:'+size+'px;height:'+size+'px;'
            +'background:'+colors[i%colors.length]+';'
            +'animation-duration:'+dur+'s;animation-delay:'+delay+'s;';
        ptcls.appendChild(p);
    }
})();
</script>
<?php endif; ?>

<?php if ($_rd_dados && !empty($_rd_recompensas)):
    $streakCard   = (int)$_rd_dados['streak'];
    $totalLogins  = (int)$_rd_dados['total_logins'];
    $diaAtualCard = (($streakCard - 1) % 7) + 1;
    $proxDiaCard  = ($diaAtualCard % 7) + 1;
    $proximaRecomp = $_rd_recompensas[$proxDiaCard] ?? $_rd_recompensas[1];
    $progressoPct  = round($diaAtualCard / 7 * 100);
?>
<style>
.rd-card{font-size:12px;}
.rd-streak-bar{
    width:100%;background:#1a1200;border:1px solid #333;
    height:8px;border-radius:4px;overflow:hidden;margin:5px 0 8px;
}
.rd-streak-fill{
    height:100%;background:linear-gradient(90deg,#8B4500,#c8830a,#FFD700);
    border-radius:4px;transition:width 1.2s ease;
}
.rd-semana{display:flex;gap:3px;justify-content:space-between;margin:6px 0;}
.rd-d{
    flex:1;text-align:center;border:1px solid #2a2000;border-radius:3px;
    padding:4px 1px;background:#111;min-width:26px;
}
.rd-d img{width:14px;height:14px;object-fit:contain;opacity:0.45;display:block;margin:2px auto 0;}
.rd-d.passado{border-color:#4a3800;background:#1c1400;}
.rd-d.passado img{opacity:0.85;}
.rd-d.hoje{border-color:#FFD700;background:#2a1c00;}
.rd-d.hoje img{opacity:1;filter:drop-shadow(0 0 3px #FFD700);}
.rd-d-num{font-size:11px;font-weight:bold;color:#555;line-height:1;}
.rd-d.passado .rd-d-num{color:#888;}
.rd-d.hoje .rd-d-num{color:#FFD700;}
</style>
<div class="box_top">
    <img src="<?php echo _RD_CHAKRA; ?>" style="width:14px;height:14px;vertical-align:middle;margin-right:4px;" alt="">
    Login Diário
</div>
<div class="box_middle rd-card">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                Streak: <b style="color:#FFD700;"><?php echo $streakCard; ?> dia<?php echo $streakCard > 1 ? 's' : ''; ?></b>
                <?php if ($streakCard >= 7): ?><img src="<?php echo _RD_STAR; ?>" style="width:12px;height:12px;vertical-align:middle;" alt="star"><?php endif; ?>
                <span style="color:#555;font-size:10px;"> (<?php echo $totalLogins; ?> logins)</span>
            </td>
            <td align="right" style="color:#666;font-size:10px;">
                Dia <?php echo $diaAtualCard; ?>/7
            </td>
        </tr>
    </table>
    <div class="rd-streak-bar">
        <div class="rd-streak-fill" style="width:<?php echo $progressoPct; ?>%;"></div>
    </div>

    <div class="rd-semana">
    <?php for ($d = 1; $d <= 7; $d++):
        if ($d < $diaAtualCard) $cls = 'passado';
        elseif ($d === $diaAtualCard) $cls = 'hoje';
        else $cls = '';
        $cdimg = $_rd_recompensas[$d]['img'] ?? _RD_YENS;
    ?>
        <div class="rd-d <?php echo $cls; ?>">
            <div class="rd-d-num"><?php echo $d; ?></div>
            <img src="<?php echo htmlspecialchars($cdimg); ?>" alt="dia <?php echo $d; ?>">
        </div>
    <?php endfor; ?>
    </div>

    <span class="sub2">
        Próxima (dia <?php echo $proxDiaCard; ?>):
        <img src="<?php echo $proximaRecomp['exp'] > 0 ? _RD_EXP : _RD_YENS; ?>" style="width:12px;height:12px;vertical-align:middle;" alt="">
        <b style="color:#FFD700;"><?php echo htmlspecialchars($proximaRecomp['label']); ?></b>
        — volte amanhã!
    </span>
</div>
<div class="box_bottom"></div>
<?php endif; ?>
