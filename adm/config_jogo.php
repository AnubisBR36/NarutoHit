<?php
require_once('../_inc/conexao.php');
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['logado']) && !isset($_SESSION['userid']) && !isset($_SESSION['uid'])) {
    header('Location: ../index.php'); exit;
}
$user_id = $_SESSION['logado'] ?? $_SESSION['userid'] ?? $_SESSION['uid'];
try {
    $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $usuario_logado = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { header('Location: adm.php'); exit; }
require_once('_gm_auth.php');
if (!$is_admin_ext) { header('Location: adm.php'); exit; }

$page_title = 'Configurações do Jogo';
$msg = '';
$msg_tipo = '';

// Helper de upsert para configuracoes
function cfg_save(PDO $pdo, string $nome, string $valor, string $descricao): void {
    try {
        if (Database::isMysql()) {
            // INSERT … ON DUPLICATE KEY UPDATE requer UNIQUE em `nome`
            // Fazemos DELETE + INSERT para suportar tabelas sem o índice
            $pdo->prepare("DELETE FROM configuracoes WHERE nome = ?")->execute([$nome]);
            $pdo->prepare("INSERT INTO configuracoes (nome, valor, descricao) VALUES (?, ?, ?)")
                ->execute([$nome, $valor, $descricao]);
        } else {
            $pdo->prepare("INSERT OR REPLACE INTO configuracoes (nome, valor, descricao) VALUES (?, ?, ?)")
                ->execute([$nome, $valor, $descricao]);
        }
    } catch (Exception $e) {}
}

// ── SALVAR ────────────────────────────────────────────────────────────────────
if (!function_exists('adm_log')) {
    function adm_log($pdo, $autor_id, $autor_nome, $acao, $alvo_id = null, $alvo_nome = null, $detalhes = null) {
        try { $pdo->prepare("INSERT INTO admin_logs (autor_id,autor_nome,acao,alvo_id,alvo_nome,detalhes) VALUES (?,?,?,?,?,?)")->execute([$autor_id,$autor_nome,$acao,$alvo_id,$alvo_nome,$detalhes]); } catch(Exception $e) {}
    }
}
$_adm_nome = $usuario_logado['usuario'] ?? '?';

if (isset($_POST['salvar_geral'])) {
    $nome_srv = trim($_POST['site_nome'] ?? '');
    $url_site  = trim($_POST['site_url']  ?? '');
    if ($nome_srv !== '') {
        $brand_content = "<?php\nif (!defined('BRAND_NAME')) {\n    define('BRAND_NAME', '" . addslashes($nome_srv) . "');\n}\nif (!function_exists('nome_servidor')) {\n    function nome_servidor(): string { return BRAND_NAME; }\n}\nif (!function_exists('nome_servidor_safe')) {\n    function nome_servidor_safe(): string { return htmlspecialchars(nome_servidor(), ENT_QUOTES, 'UTF-8'); }\n}\n";
        file_put_contents('../config/brand.php', $brand_content);
    }
    cfg_save($conexao, 'site_url', $url_site, 'URL base do site para geração de nLinks');
    adm_log($conexao, $user_id, $_adm_nome, 'Config Geral', null, null, "Nome=$nome_srv | URL=$url_site");
    $msg = 'Configurações gerais salvas.'; $msg_tipo = 'success';
}

if (isset($_POST['salvar_caca'])) {
    cfg_save($conexao, 'caca_vip_bonus_exp',  (string)max(0,(int)$_POST['caca_vip_bonus_exp']),  'Bônus de EXP VIP na caça (pontos fixos)');
    cfg_save($conexao, 'caca_item_chance',     (string)max(0,min(50,(int)$_POST['caca_item_chance'])), 'Chance (%) de encontrar item ao finalizar caça');
    cfg_save($conexao, 'caca_item_chance2',    (string)max(0,min(50,(int)$_POST['caca_item_chance2'])),'Chance (%) de encontrar item usável ao finalizar caça');
    adm_log($conexao, $user_id, $_adm_nome, 'Config Caça', null, null, "VIP EXP={$_POST['caca_vip_bonus_exp']} | Chance1={$_POST['caca_item_chance']}% | Chance2={$_POST['caca_item_chance2']}%");
    $msg = 'Configurações de caça salvas.'; $msg_tipo = 'success';
}

if (isset($_POST['salvar_missoes'])) {
    foreach (['d'=>901,'c'=>902,'b'=>903,'a'=>904,'s'=>905] as $rank => $code) {
        $y = max(0,(int)($_POST["missao_yens_{$rank}"] ?? 0));
        cfg_save($conexao, "missao_yens_{$rank}", (string)$y, "Yens/hora missão Rank ".strtoupper($rank));
    }
    cfg_save($conexao, 'missao_exp_hora', (string)max(0,(int)($_POST['missao_exp_hora']??1)), 'EXP por hora de missão');
    adm_log($conexao, $user_id, $_adm_nome, 'Config Missões', null, null, "EXP/hora={$_POST['missao_exp_hora']}");
    $msg = 'Configurações de missões salvas.'; $msg_tipo = 'success';
}

if (isset($_POST['salvar_treino'])) {
    $div = max(1, (int)($_POST['treino_exp_divisor'] ?? 5));
    cfg_save($conexao, 'treino_exp_divisor', (string)$div, 'Divisor do tempo de treino para calcular EXP de jutsu');
    adm_log($conexao, $user_id, $_adm_nome, 'Config Treino', null, null, "Divisor=$div");
    $msg = 'Configurações de treino salvas.'; $msg_tipo = 'success';
}

if (isset($_POST['salvar_multiplicadores'])) {
    cfg_save($conexao, 'global_exp_mult',  number_format(max(0.1,min(10.0,(float)str_replace(',','.',($_POST['global_exp_mult'] ??'1')))),2,'.','.'), 'Multiplicador global de EXP (base para eventos)');
    cfg_save($conexao, 'global_yens_mult', number_format(max(0.1,min(10.0,(float)str_replace(',','.',($_POST['global_yens_mult']??'1')))),2,'.','.'), 'Multiplicador global de Yens (base para eventos)');
    cfg_save($conexao, 'global_drop_mult', number_format(max(0.1,min(10.0,(float)str_replace(',','.',($_POST['global_drop_mult']??'1')))),2,'.','.'), 'Multiplicador global de Drop (base para eventos)');
    adm_log($conexao, $user_id, $_adm_nome, 'Config Multiplicadores', null, null, "EXP={$_POST['global_exp_mult']}x | Yens={$_POST['global_yens_mult']}x | Drop={$_POST['global_drop_mult']}x");
    $msg = 'Multiplicadores globais salvos.'; $msg_tipo = 'success';
}

if (isset($_POST['salvar_registro'])) {
    cfg_save($conexao, 'cadastro_aberto',   $_POST['cadastro_aberto']   === '1' ? '1' : '0', 'Permite novos cadastros no site (1=sim, 0=não)');
    cfg_save($conexao, 'pvp_ativo',          $_POST['pvp_ativo']          === '1' ? '1' : '0', 'PVP entre jogadores habilitado (1=sim, 0=não)');
    cfg_save($conexao, 'vip_exp_bonus_pct',  (string)max(0,(int)$_POST['vip_exp_bonus_pct']),  'Bônus extra de EXP para VIP em %');
    cfg_save($conexao, 'vip_yens_bonus_pct', (string)max(0,(int)$_POST['vip_yens_bonus_pct']), 'Bônus extra de Yens para VIP em %');
    adm_log($conexao, $user_id, $_adm_nome, 'Config Servidor', null, null, "Cadastro={$_POST['cadastro_aberto']} | PVP={$_POST['pvp_ativo']} | VIP EXP={$_POST['vip_exp_bonus_pct']}% | VIP Yens={$_POST['vip_yens_bonus_pct']}%");
    $msg = 'Configurações de servidor salvas.'; $msg_tipo = 'success';
}

// ── CARREGAR VALORES ATUAIS ───────────────────────────────────────────────────
$cfg = [];
try {
    $rows = $conexao->query("SELECT nome, valor FROM configuracoes")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) $cfg[$r['nome']] = $r['valor'];
} catch (Exception $e) {}

// Geral
if (file_exists('../config/brand.php') && !defined('BRAND_NAME')) require_once('../config/brand.php');
$nome_srv   = defined('BRAND_NAME') ? BRAND_NAME : ($cfg['site_nome'] ?? 'NarutoTheGame');
$url_site   = $cfg['site_url'] ?? '';

// Caça
$caca_vip_bonus  = (int)($cfg['caca_vip_bonus_exp']  ?? 3);
$caca_item_ch1   = (int)($cfg['caca_item_chance']     ?? 10);
$caca_item_ch2   = (int)($cfg['caca_item_chance2']    ?? 10);

// Missões
$missao_defaults = ['d'=>250,'c'=>550,'b'=>1000,'a'=>1800,'s'=>3000];
$missao_yens     = [];
foreach ($missao_defaults as $rank => $def) {
    $missao_yens[$rank] = (int)($cfg["missao_yens_{$rank}"] ?? $def);
}
$missao_exp_hora = (int)($cfg['missao_exp_hora'] ?? 1);

// Treino
$treino_divisor  = (int)($cfg['treino_exp_divisor'] ?? 5);

// Multiplicadores globais
$global_exp_mult  = number_format((float)($cfg['global_exp_mult']  ?? '1.0'), 2, '.', '');
$global_yens_mult = number_format((float)($cfg['global_yens_mult'] ?? '1.0'), 2, '.', '');
$global_drop_mult = number_format((float)($cfg['global_drop_mult'] ?? '1.0'), 2, '.', '');

// Servidor
$cadastro_aberto    = ($cfg['cadastro_aberto']    ?? '1') === '1';
$pvp_ativo           = ($cfg['pvp_ativo']          ?? '1') === '1';
$vip_exp_bonus_pct   = (int)($cfg['vip_exp_bonus_pct']  ?? 0);
$vip_yens_bonus_pct  = (int)($cfg['vip_yens_bonus_pct'] ?? 0);

require_once('adm_header.php');
?>

<?php if ($msg): ?>
<div class="alert-<?php echo $msg_tipo; ?>"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<div class="adm-page-title">Configurações Globais do Jogo</div>

<style>
.cfg-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.cfg-row { display:flex; align-items:center; gap:8px; margin:6px 0; flex-wrap:wrap; }
.cfg-row label { color:#aaa; font-size:11px; min-width:180px; }
.cfg-row input[type=number],
.cfg-row input[type=text],
.cfg-row input[type=url],
.cfg-row select { width:120px; }
.cfg-row input[type=text].wide,
.cfg-row input[type=url].wide { width:260px; }
.cfg-hint { color:#555; font-size:10px; margin-left:4px; }
.rank-badge { display:inline-block; font-weight:bold; font-size:10px; padding:1px 6px; border-radius:3px; }
.rank-d { background:#555; color:#ddd; }
.rank-c { background:#1a4a1a; color:#90ee90; }
.rank-b { background:#1a3060; color:#87cefa; }
.rank-a { background:#4a2200; color:#ffa040; }
.rank-s { background:#3a0a0a; color:#ff6060; }
</style>

<!-- GERAL -->
<fieldset>
    <legend>Geral do Servidor</legend>
    <form method="post">
        <div class="cfg-row">
            <label>Nome do servidor:</label>
            <input type="text" class="wide" name="site_nome" value="<?php echo htmlspecialchars($nome_srv); ?>" maxlength="60">
            <span class="cfg-hint">Aparece em e-mails, rodapé e mensagens automáticas</span>
        </div>
        <div class="cfg-row">
            <label>URL do site:</label>
            <input type="url" class="wide" name="site_url" value="<?php echo htmlspecialchars($url_site); ?>" placeholder="https://seusite.com.br">
            <span class="cfg-hint">Usada nos links de convite (nLink)</span>
        </div>
        <div style="margin-top:8px;">
            <button type="submit" name="salvar_geral" class="btn-success">Salvar Geral</button>
        </div>
    </form>
</fieldset>

<!-- SERVIDOR / TOGGLES -->
<fieldset>
    <legend>Servidor — Toggles</legend>
    <form method="post">
        <div class="cfg-row">
            <label>Cadastro de novos jogadores:</label>
            <select name="cadastro_aberto">
                <option value="1" <?php echo $cadastro_aberto?'selected':''; ?>>Aberto</option>
                <option value="0" <?php echo !$cadastro_aberto?'selected':''; ?>>Fechado</option>
            </select>
        </div>
        <div class="cfg-row">
            <label>PVP entre jogadores:</label>
            <select name="pvp_ativo">
                <option value="1" <?php echo $pvp_ativo?'selected':''; ?>>Habilitado</option>
                <option value="0" <?php echo !$pvp_ativo?'selected':''; ?>>Desabilitado</option>
            </select>
        </div>
        <div class="cfg-row">
            <label>Bônus EXP VIP (%):</label>
            <input type="number" name="vip_exp_bonus_pct" value="<?php echo $vip_exp_bonus_pct; ?>" min="0" max="500">
            <img src="../_img/Icones/experiencia.png" style="width:12px;height:12px;vertical-align:middle;">
            <span class="cfg-hint">Aplica % adicional no EXP ganho por VIPs</span>
        </div>
        <div class="cfg-row">
            <label>Bônus Yens VIP (%):</label>
            <input type="number" name="vip_yens_bonus_pct" value="<?php echo $vip_yens_bonus_pct; ?>" min="0" max="500">
            <img src="../_img/yens.png" style="width:12px;height:12px;vertical-align:middle;">
            <span class="cfg-hint">Aplica % adicional nos Yens ganhos por VIPs</span>
        </div>
        <div style="margin-top:8px;">
            <button type="submit" name="salvar_registro" class="btn-success">Salvar Servidor</button>
        </div>
    </form>
</fieldset>

<!-- CAÇA -->
<fieldset>
    <legend>Caça</legend>
    <form method="post">
        <div class="cfg-row">
            <label>Bônus EXP VIP na caça (pts fixos):</label>
            <input type="number" name="caca_vip_bonus_exp" value="<?php echo $caca_vip_bonus; ?>" min="0" max="999">
            <img src="../_img/Icones/experiencia.png" style="width:12px;height:12px;vertical-align:middle;">
            <span class="cfg-hint">Pontos de EXP extras para VIPs ao finalizar caça (padrão: 3)</span>
        </div>
        <div class="cfg-row">
            <label>Chance de item (tipo 1) %:</label>
            <input type="number" name="caca_item_chance" value="<?php echo $caca_item_ch1; ?>" min="0" max="50">
            <span class="cfg-hint">Chance de encontrar item de equipamento ao finalizar caça (padrão: 10%)</span>
        </div>
        <div class="cfg-row">
            <label>Chance de item (tipo 2) %:</label>
            <input type="number" name="caca_item_chance2" value="<?php echo $caca_item_ch2; ?>" min="0" max="50">
            <span class="cfg-hint">Chance de encontrar item usável ao finalizar caça (padrão: 10%)</span>
        </div>
        <div style="margin-top:8px;">
            <button type="submit" name="salvar_caca" class="btn-success">Salvar Caça</button>
        </div>
    </form>
</fieldset>

<!-- MISSÕES -->
<fieldset>
    <legend>Missões — Recompensas por Hora</legend>
    <form method="post">
        <table class="adm-table" style="max-width:500px;">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Yens / hora</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php
            $ranks = [
                'd' => ['label'=>'D','class'=>'rank-d','def'=>250],
                'c' => ['label'=>'C','class'=>'rank-c','def'=>550],
                'b' => ['label'=>'B','class'=>'rank-b','def'=>1000],
                'a' => ['label'=>'A','class'=>'rank-a','def'=>1800],
                's' => ['label'=>'S','class'=>'rank-s','def'=>3000],
            ];
            foreach ($ranks as $r => $info):
            ?>
            <tr>
                <td><span class="rank-badge <?php echo $info['class']; ?>">Rank <?php echo $info['label']; ?></span></td>
                <td>
                    <input type="number" name="missao_yens_<?php echo $r; ?>" value="<?php echo $missao_yens[$r]; ?>" min="0" max="999999" style="width:100px;">
                    <img src="../_img/yens.png" style="width:12px;height:12px;vertical-align:middle;">
                </td>
                <td style="color:#555;font-size:10px;">padrão: <?php echo number_format($info['def'],0,'.',','); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="1" style="color:#aaa;font-size:11px;">EXP / hora:</td>
                <td>
                    <input type="number" name="missao_exp_hora" value="<?php echo $missao_exp_hora; ?>" min="0" max="999" style="width:100px;">
                    <img src="../_img/Icones/experiencia.png" style="width:12px;height:12px;vertical-align:middle;">
                </td>
                <td style="color:#555;font-size:10px;">padrão: 1</td>
            </tr>
            </tbody>
        </table>
        <div style="margin-top:8px;">
            <button type="submit" name="salvar_missoes" class="btn-success">Salvar Missões</button>
        </div>
    </form>
</fieldset>

<!-- TREINO -->
<fieldset>
    <legend>Treino de Jutsus</legend>
    <form method="post">
        <div class="cfg-row">
            <label>Divisor de EXP de treino:</label>
            <input type="number" name="treino_exp_divisor" value="<?php echo $treino_divisor; ?>" min="1" max="100">
            <span class="cfg-hint">EXP do jutsu = tempo_treino_min / divisor &nbsp; (padrão: 5)</span>
        </div>
        <div style="margin-top:8px;">
            <button type="submit" name="salvar_treino" class="btn-success">Salvar Treino</button>
        </div>
    </form>
</fieldset>

<!-- MULTIPLICADORES GLOBAIS -->
<fieldset>
    <legend>Multiplicadores Globais de Recompensa</legend>
    <p style="color:#888;font-size:11px;margin:0 0 8px 0;">
        Multiplicadores permanentes aplicados a <b style="color:#FFD700;">todas</b> as recompensas do jogo.
        Eventos temporários (na página Eventos) empilham em cima desses valores.
        Padrão: <b>1.00</b> = sem alteração. Use <b>2.00</b> para dobrar, <b>0.50</b> para metade, etc.
    </p>
    <form method="post">
        <div class="cfg-row">
            <label>Mult. EXP global:</label>
            <input type="number" name="global_exp_mult"  value="<?php echo $global_exp_mult; ?>"  min="0.1" max="10" step="0.1" style="width:80px;">
            <span class="cfg-hint">Multiplica TODO o EXP ganho em caças e treinos</span>
        </div>
        <div class="cfg-row">
            <label>Mult. Yens global:</label>
            <input type="number" name="global_yens_mult" value="<?php echo $global_yens_mult; ?>" min="0.1" max="10" step="0.1" style="width:80px;">
            <span class="cfg-hint">Multiplica todos os Yens ganhos em caças</span>
        </div>
        <div class="cfg-row">
            <label>Mult. Drop global:</label>
            <input type="number" name="global_drop_mult" value="<?php echo $global_drop_mult; ?>" min="0.1" max="10" step="0.1" style="width:80px;">
            <span class="cfg-hint">Multiplica a chance de encontrar item em caças</span>
        </div>
        <div style="margin-top:8px; display:flex; align-items:center; gap:12px;">
            <button type="submit" name="salvar_multiplicadores" class="btn-success">Salvar Multiplicadores</button>
            <a href="adm.php?modulo=eventos_bonus" style="color:#87CEFA;font-size:11px;">Gerenciar eventos temporários</a>
        </div>
    </form>
</fieldset>

<!-- RESUMO DOS VALORES ATIVOS -->
<fieldset>
    <legend>Resumo dos Valores Ativos</legend>
    <table class="adm-table" style="max-width:560px;">
        <thead><tr><th>Chave</th><th>Valor atual</th><th>Descrição</th></tr></thead>
        <tbody>
        <?php
        $resumo = [
            'cadastro_aberto'    => ['Cadastro aberto',        $cadastro_aberto ? 'Sim' : 'Não'],
            'pvp_ativo'          => ['PVP ativo',              $pvp_ativo ? 'Sim' : 'Não'],
            'vip_exp_bonus_pct'  => ['Bônus EXP VIP',         $vip_exp_bonus_pct.'%'],
            'vip_yens_bonus_pct' => ['Bônus Yens VIP',        $vip_yens_bonus_pct.'%'],
            'caca_vip_bonus_exp' => ['VIP bonus EXP caça',    $caca_vip_bonus.' pts'],
            'caca_item_chance'   => ['Chance item tipo 1',     $caca_item_ch1.'%'],
            'caca_item_chance2'  => ['Chance item tipo 2',     $caca_item_ch2.'%'],
            'missao_yens_d'      => ['Missão yens/h Rank D',   number_format($missao_yens['d'],0,'.',',')],
            'missao_yens_c'      => ['Missão yens/h Rank C',   number_format($missao_yens['c'],0,'.',',')],
            'missao_yens_b'      => ['Missão yens/h Rank B',   number_format($missao_yens['b'],0,'.',',')],
            'missao_yens_a'      => ['Missão yens/h Rank A',   number_format($missao_yens['a'],0,'.',',')],
            'missao_yens_s'      => ['Missão yens/h Rank S',   number_format($missao_yens['s'],0,'.',',')],
            'missao_exp_hora'    => ['Missão EXP/hora',        $missao_exp_hora],
            'treino_exp_divisor' => ['Divisor EXP treino',     $treino_divisor],
        ];
        foreach ($resumo as $k => [$desc, $val]):
        ?>
        <tr>
            <td style="color:#666;font-size:10px;"><?php echo htmlspecialchars($k); ?></td>
            <td style="color:#FFD700;font-weight:bold;"><?php echo htmlspecialchars($val); ?></td>
            <td style="color:#666;font-size:10px;"><?php echo htmlspecialchars($desc); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</fieldset>

<?php require_once('adm_footer.php'); ?>
