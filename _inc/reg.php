<?php
require_once('Encrypt.php');
$c=new C_Encrypt();

if(isset($_SESSION['logado'])){ header("Location: index.php?p=home"); exit; }
$sqlc=$conexao->query("SELECT COUNT(id) as conta FROM usuarios");
$dbc=$sqlc->fetch(PDO::FETCH_ASSOC);
if(!$dbc) $dbc = array('conta' => 0);
$vagas=30000;
if($dbc['conta']>=$vagas){ header("Location: index.php?p=login"); exit; }
if(isset($_POST['reg_submit'])){
        $erro=0;
        if(@$_POST['reg_termos']=='') $erro=11;
        if(!isset($_POST['reg_termos'])) $erro=8;
        if($_POST['reg_senha']<>$_POST['reg_senha2']) $erro=7;
        if($_POST['reg_vila']=='') $erro=6;
        if($_POST['reg_personagem']=='') $erro=5;
        if($_POST['reg_email']=='') $erro=4;
        if($_POST['reg_senha2']=='') $erro=3;
        if($_POST['reg_senha']=='') $erro=2;
        if($_POST['reg_usuario']=='') $erro=1;
        $personagem=$c->decode($_POST['reg_personagem'],$chaveuniversal);
        $vila=$c->decode($_POST['reg_vila'],$chaveuniversal);
        // Whitelist do personagem inicial — protege contra valores corrompidos vindos do decode
        $iniciais_validos = ['naruto','sakura','sasuke','kakashi'];
        if(!in_array($personagem, $iniciais_validos, true)) $personagem = 'naruto';
        // Usar nome de usuário exatamente como digitado
        $usuario = $_POST['reg_usuario'];
        $sqlc=$conexao->prepare("SELECT COUNT(id) as conta FROM usuarios WHERE usuario=?");
        $sqlc->execute([$usuario]);
        $dbc=$sqlc->fetch(PDO::FETCH_ASSOC);
        if(!$dbc) $dbc = array('conta' => 0);
        if($dbc['conta']>0) $erro=12;
        $sqlc=$conexao->prepare("SELECT COUNT(id) as conta FROM usuarios WHERE email=?");
        $sqlc->execute([$_POST['reg_email']]);
        $dbc=$sqlc->fetch(PDO::FETCH_ASSOC);
        if(!$dbc) $dbc = array('conta' => 0);
        if($dbc['conta']>0) $erro=12;
        // Validar servidor selecionado (IDs válidos: 0-9)
        $reg_servidor_id = isset($_POST['reg_servidor_id']) ? (int)$_POST['reg_servidor_id'] : -1;
        if ($reg_servidor_id >= 0) {
            $stmtSrv = $conexao->prepare("SELECT capacidade, (SELECT COUNT(*) FROM usuarios WHERE servidor_id = servidores.id) AS total FROM servidores WHERE id = ? AND ativo = 1");
            $stmtSrv->execute([$reg_servidor_id]);
            $srvRow = $stmtSrv->fetch(PDO::FETCH_ASSOC);
            if (!$srvRow || ($srvRow['total'] >= $srvRow['capacidade'])) $erro=16;
        } else {
            $erro=16; // Nenhum servidor selecionado
        }
        $nlink_criador_id = 0;
        $nlink_criador_user = '';
        if($_POST['reg_nlink']<>''){
                $nlink=$_POST['reg_nlink'];
                $sqlv=$conexao->prepare("SELECT id, usuario, criador_conteudo FROM usuarios WHERE ref_link=? OR usuario=? LIMIT 1");
                $sqlv->execute([$nlink, $nlink]);
                $dbv=$sqlv->fetch(PDO::FETCH_ASSOC);
                if(!$dbv) $erro=13;
                else { $nlink_criador_id = (int)$dbv['id']; $nlink_criador_user = $dbv['usuario']; }
        }
        // Verificação de multi-contas removida temporariamente
        if(isset($_POST['reg_nlink'])) $link='&nlink='.$_POST['reg_nlink']; else $link='';
        if($erro>0){ 
                header("Location: index.php?p=reg&user=".$_POST['reg_usuario']."&mail=".$_POST['reg_email']."&char=".$personagem."&village=".$vila."&erro=".$erro.$link); 
                exit; 
        }
        else {
                if(isset($_POST['reg_akatsuki'])) $renegado='sim'; else $renegado='nao';
                $soma=mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'));
                $vipfim=date('Y-m-d H:i:s',$soma);
                // Usar o nome de usuário original, sem modificações
                $bandana_estilo = isset($_POST['bandana_estilo']) ? $_POST['bandana_estilo'] : 'moderno';
                $stmt = $conexao->prepare("INSERT INTO usuarios (usuario, senha, email, personagem, vila, renegado, hunt_restantes, reg, natureza1, natureza2, natureza3, ip, vip, vip_inicio, bandana_estilo, servidor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$usuario, senha_hash((string)$_POST['reg_senha']), strtolower($_POST['reg_email']), $personagem, $vila, $renegado, 14, date('Y-m-d H:i:s'), '', '', '', ip2long($_SERVER['REMOTE_ADDR']), $vipfim, date('Y-m-d H:i:s'), $bandana_estilo, $reg_servidor_id]);

                // Cria a linha do novo usuário na tabela de desbloqueio de personagens.
                try {
                        $novoId = (int)$conexao->lastInsertId();
                        if($novoId > 0){
                                require_once(__DIR__ . '/personagens_catalogo.php');
                                personagens_garantir_linha($conexao, $novoId);
                        }
                } catch (Exception $e) { /* ignora */ }

                if($nlink_criador_id > 0) {
                        $stmt2 = $conexao->prepare("UPDATE usuarios SET yens=yens+100, yens_fat=yens_fat+100 WHERE id=?");
                        $stmt2->execute([$nlink_criador_id]);
                        // Buscar id do novo usuário e logar o referral
                        try {
                                $stmtNew = $conexao->prepare("SELECT id FROM usuarios WHERE usuario=? LIMIT 1");
                                $stmtNew->execute([$usuario]);
                                $rowNew = $stmtNew->fetch(PDO::FETCH_ASSOC);
                                if($rowNew){
                                        $stmtLog = $conexao->prepare("INSERT INTO criador_refs (criador_id, novo_usuario_id, novo_usuario_nome, data) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
                                        $stmtLog->execute([$nlink_criador_id, (int)$rowNew['id'], $usuario]);
                                }
                        } catch(Exception $e) {}
                }
                $mensagem='<div align="center"><img src="http://www.anubisserve.net/_img/support/minilogo2.jpg" style="border-bottom:1px solid #DDDDDD" /><br /><br /><b>Ativação de Conta</b><br />Utilize o link abaixo para ativar sua conta:<br /><br /><b>http://www.anubisserve.net/ativation.php?token='.md5($_POST['reg_senha']).'</b><br /><br /><b><span style="color:#CC0000">Bom jogo!</span></b><br /><br />Atenciosamente, equipe '.nome_servidor().'.</div>';
                $assunto='Ativar Conta';
                $remetente=nome_servidor().' - CBT <contato@anubisserve.net>';
                $headers = implode ( "\n",array ( "From: $remetente","Subject: ","Return-Path: $remetente","MIME-Version: 1.0","X-Priority: 3","Content-Type: text/html;" ) );
                //mail(strtolower($_POST['reg_email']),$assunto,$mensagem,$headers);
                header("Location: index.php?p=reg2"); 
                exit;
        }
}
?>
<?php
$txtnaruto='<b>Nome:</b> Uzumaki Naruto<br /><b>Vila:</b> Vila da Folha<br /><b>Classificação:</b> Gennin';
$txtsakura='<b>Nome:</b> Haruno Sakura<br /><b>Vila:</b> Vila da Folha<br /><b>Classificação:</b> Chuunin';
$txtsasuke='<b>Nome:</b> Uchiha Sasuke<br /><b>Vila:</b> Vila da Folha<br /><b>Classificação:</b> Nukenin';
$txtkakashi='<b>Nome:</b> Hatake Kakashi<br /><b>Vila:</b> Vila da Folha<br /><b>Classificação:</b> Jounnin';
$sqlc=$conexao->query("SELECT COUNT(id) as conta FROM usuarios");
$dbc=$sqlc->fetch(PDO::FETCH_ASSOC);
if(!$dbc) $dbc = array('conta' => 0);
?>
<div class="box_top">Registrar</div><div class="box_middle">Seja bem-vindo ao <?php echo nome_servidor(); ?>! Para que você possa se aventurar no mundo de Naruto, é necessário criar uma conta de acesso. Para isso, basta preencher o formulário abaixo com os dados solicitados. Todos os campos solicitados são obrigatórios, sem exceção.<br /><div class="sep"></div>
<?php if(isset($_GET['erro'])){
        switch($_GET['erro']){
                case 1: $msg='Favor preencha o campo <b>NOME DE USUÁRIO</b>.'; break;
                case 2: $msg='Favor preencha o campo <b>SENHA</b>.'; break;
                case 3: $msg='Favor preencha o campo <b>CONFIRMAR SENHA</b>.'; break;
                case 4: $msg='Favor preencha o campo <b>EMAIL</b>.'; break;
                case 5: $msg='Favor selecione um <b>PERSONAGEM</b>.'; break;
                case 6: $msg='Favor selecione uma <b>VILA</b>.'; break;
                case 7: $msg='<b>SENHAS</b> digitadas não conferem.'; break;
                case 8: $msg='Você precisa aceitar os <b>TERMOS E CONDIÇÕES</b> do jogo antes de prosseguir.'; break;
                case 9: $msg='<b>CHAVE DE REGISTRO</b> não existe.'; break;
                case 10: $msg='<b>CHAVE DE REGISTRO</b> já foi utilizada.'; break;
                case 11: $msg='Favor preencha o campo <b>CHAVE DE REGISTRO</b>.'; break;
                case 12: $msg='<b>USUÁRIO</b> ou <b>EMAIL</b> já está em uso!'; break;
                case 13: $msg='Código <b>NLINK</b> utilizado é inválido.'; break;
                case 14: $msg='<b>IP</b> já está em uso por outra conta.'; break;
                case 15: $msg='Erro interno do sistema.'; break;
                case 16: $msg='O servidor selecionado está <b>cheio</b> ou inativo. Escolha outro servidor.'; break;
        }
        echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
} ?>
<form method="post" action="?p=reg" name="reg" id="reg" style="background:url(_img/reg.jpg) no-repeat right top;" onsubmit="subm.value='Carregando...';subm.disabled=true;">
<input type="hidden" id="reg_submit" name="reg_submit" value="1" />
<input type="hidden" id="reg_nlink" name="reg_nlink" value="<?php if(isset($_GET['nlink'])) echo $_GET['nlink']; ?>" />
<fieldset>
        <legend>Dados da Conta</legend>
    <span class="destaque">Nome de Usuário:</span><br />
    <input type="text" id="reg_usuario" name="reg_usuario" maxlength="15" onfocus="className='input'" onblur="className=''" <?php if(isset($_GET['user'])) echo 'value="'.$_GET['user'].'"'; ?>/><br />
    <span class="sub2">Digite um nome de usuário com até<br />15 caracteres. Este também será seu<br />login no jogo, e o nome do personagem.</span><br /><br />

    <span class="destaque">Senha:</span><br />
    <input type="password" id="reg_senha" name="reg_senha" maxlength="15" onfocus="className='input'" onblur="className=''" /><br />
    <span class="sub2">Digite uma senha de acesso<br />com até 15 caracteres.</span><br /><br />

    <span class="destaque">Confirmar Senha:</span><br />
    <input type="password" id="reg_senha2" name="reg_senha2" maxlength="15" onfocus="className='input'" onblur="className=''" /><br />
    <span class="sub2">Digite novamente a senha.</span><br /><br />

    <span class="destaque">Email:</span><br />
    <input type="text" id="reg_email" name="reg_email" maxlength="250" onfocus="className='input'" onblur="className=''" <?php if(isset($_GET['mail'])) echo 'value="'.$_GET['mail'].'"'; ?>/><br />
    <span class="sub2">Digite um email válido<br />(será necessário ativar a conta em alguns dias).</span>
</fieldset>
<fieldset>
        <legend>Personagem</legend>
    <span class="sub2">Escolha um personagem entre os 4 abaixo. Durante sua evolução no jogo, novos personagens são liberados para uso. A troca de personagem pode ser feita uma vez por dia.</span><div class="sep"></div>
    <div align="center">
    <table>
          <tr>
          <td width="116" height="65" align="center"><img src="_img/personagens/reg_naruto.jpg" onclick="document.getElementById('reg_personagem1').checked=true" /></td>
                <td width="116" align="center"><img src="_img/personagens/reg_sakura.jpg" onclick="document.getElementById('reg_personagem2').checked=true" /></td>
          <td width="116" align="center"><img src="_img/personagens/reg_sasuke.jpg" onclick="document.getElementById('reg_personagem3').checked=true" /></td>
            <td width="116" align="center"><img src="_img/personagens/reg_kakashi.jpg" onclick="document.getElementById('reg_personagem4').checked=true" /></td>
        </tr>
        <tr>
                <td align="center"><input type="radio" id="reg_personagem1" name="reg_personagem" value="<?php echo $c->encode('naruto',$chaveuniversal); ?>" <?php if((!isset($_GET['char']))or(isset($_GET['char']))&&($_GET['char']=='naruto')) echo 'checked="checked"'; ?>/></td>
            <td align="center"><input type="radio" id="reg_personagem2" name="reg_personagem" value="<?php echo $c->encode('sakura',$chaveuniversal); ?>" <?php if((isset($_GET['char']))&&($_GET['char']=='sakura')) echo 'checked="checked"'; ?>/></td>
            <td align="center"><input type="radio" id="reg_personagem3" name="reg_personagem" value="<?php echo $c->encode('sasuke',$chaveuniversal); ?>" <?php if((isset($_GET['char']))&&($_GET['char']=='sasuke')) echo 'checked="checked"'; ?>/></td>
          <td align="center"><input type="radio" id="reg_personagem4" name="reg_personagem" value="<?php echo $c->encode('kakashi',$chaveuniversal); ?>" <?php if((isset($_GET['char']))&&($_GET['char']=='kakashi')) echo 'checked="checked"'; ?>/></td>
        </tr>
    </table>
    </div>
</fieldset>
<fieldset>
        <legend>Vila</legend>
    <span class="sub2">Escolha uma vila para representar. Jogadores da mesma vila n&atilde;o podem se enfrentar. Qualquer jogador poder&aacute; se tornar um Akatsuki.</span><div class="sep"></div>
    <div align="center">
    <table>
          <tr>
                <td width="70" height="65" align="center"><img src="_img/vilas/reg_folha.jpg" onclick="document.getElementById('reg_vila1').checked=true" /></td>
                <td width="70" align="center"><img src="_img/vilas/reg_areia.jpg" onclick="document.getElementById('reg_vila2').checked=true" /></td>
            <td width="70" align="center"><img src="_img/vilas/reg_som.jpg" onclick="document.getElementById('reg_vila3').checked=true" /></td>
            <td width="70" align="center"><img src="_img/vilas/reg_chuva.jpg" onclick="document.getElementById('reg_vila4').checked=true" /></td>
            <td width="70" align="center"><img src="_img/vilas/reg_nuvem.jpg" onclick="document.getElementById('reg_vila5').checked=true" /></td>
            <td width="70" align="center"><img src="_img/vilas/reg_nevoa.jpg" onclick="document.getElementById('reg_vila6').checked=true" /></td>
            <td width="70" align="center"><img src="_img/vilas/reg_pedra.jpg" onclick="document.getElementById('reg_vila8').checked=true" /></td>
        </tr>
        <tr>
                <td align="center"><input type="radio" id="reg_vila1" name="reg_vila" value="<?php echo $c->encode('1',$chaveuniversal); ?>" <?php if((!isset($_GET['village']))or(isset($_GET['village']))&&($_GET['village']==1)) echo 'checked="checked"'; ?>/></td>
            <td align="center"><input type="radio" id="reg_vila2" name="reg_vila" value="<?php echo $c->encode('2',$chaveuniversal); ?>" <?php if((isset($_GET['village']))&&($_GET['village']==2)) echo 'checked="checked"'; ?>/></td>
            <td align="center"><input type="radio" id="reg_vila3" name="reg_vila" value="<?php echo $c->encode('3',$chaveuniversal); ?>" <?php if((isset($_GET['village']))&&($_GET['village']==3)) echo 'checked="checked"'; ?>/></td>
            <td align="center"><input type="radio" id="reg_vila4" name="reg_vila" value="<?php echo $c->encode('4',$chaveuniversal); ?>" <?php if((isset($_GET['village']))&&($_GET['village']==4)) echo 'checked="checked"'; ?>/></td>
            <td align="center"><input type="radio" id="reg_vila5" name="reg_vila" value="<?php echo $c->encode('5',$chaveuniversal); ?>" <?php if((isset($_GET['village']))&&($_GET['village']==5)) echo 'checked="checked"'; ?>/></td>
            <td align="center"><input type="radio" id="reg_vila6" name="reg_vila" value="<?php echo $c->encode('6',$chaveuniversal); ?>" <?php if((isset($_GET['village']))&&($_GET['village']==6)) echo 'checked="checked"'; ?>/></td>
            <td align="center"><input type="radio" id="reg_vila8" name="reg_vila" value="<?php echo $c->encode('8',$chaveuniversal); ?>" <?php if((isset($_GET['village']))&&($_GET['village']==8)) echo 'checked="checked"'; ?>/></td>
        </tr>
    </table>
    </div>

    <div class="sep"></div>
    <div>
        <span class="sub2">Escolha o estilo da sua bandana:</span><br>
        <input type="radio" id="bandana_moderno" name="bandana_estilo" value="moderno" checked="checked" />
        <label for="bandana_moderno"><img src="_img/NewsVilas/akatsuki.jpg" style="width: 117px; height: 55px; cursor: pointer;" onclick="document.getElementById('bandana_moderno').checked=true"></label>

        <input type="radio" id="bandana_classico" name="bandana_estilo" value="classico" />
        <label for="bandana_classico"><img src="_img/vilas/akatsuki_folha.jpg" style="width: 117px; height: 55px; cursor: pointer;" onclick="document.getElementById('bandana_classico').checked=true"></label>
    </div>

    <div class="sep"></div>
    <input type="checkbox" id="reg_akatsuki" name="reg_akatsuki" /> Desejo iniciar o jogo como sendo um ninja renegado (membro da Akatsuki).
</fieldset>
<fieldset>
    <legend>Servidor</legend>
    <?php
    $srv_reg_list = [];
    try {
        $srv_reg_list = $conexao->query("SELECT s.id, s.nome, s.capacidade,
            (SELECT COUNT(*) FROM usuarios u WHERE u.servidor_id = s.id) AS total_players
            FROM servidores s WHERE s.ativo = 1 ORDER BY s.id ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $srv_reg_list = [];
    }
    ?>
    <span class="sub2">Escolha em qual servidor deseja criar sua conta.</span>
    <div class="sep"></div>
    <?php if (!empty($srv_reg_list)): ?>
    <div style="display:flex;flex-wrap:wrap;gap:10px;margin:8px 0;">
        <?php foreach ($srv_reg_list as $idx => $srv_r): ?>
            <?php
            $vagas_r = $srv_r['capacidade'] - $srv_r['total_players'];
            $cheio_r = $vagas_r <= 0;
            $pct_r   = $srv_r['capacidade'] > 0 ? min(100, round(($srv_r['total_players'] / $srv_r['capacidade']) * 100)) : 100;
            $cor_r   = $pct_r >= 90 ? '#e53935' : ($pct_r >= 60 ? '#f9a825' : '#43a047');
            $borda_r = $cheio_r ? '#555' : '#cc4400';
            ?>
            <label style="cursor:<?php echo $cheio_r ? 'not-allowed' : 'pointer'; ?>;border:2px solid <?php echo $borda_r; ?>;border-radius:6px;padding:8px 12px;background:#1a1a1a;min-width:130px;display:block;opacity:<?php echo $cheio_r ? '0.5' : '1'; ?>;">
                <input type="radio" name="reg_servidor_id" value="<?php echo $srv_r['id']; ?>"
                    <?php echo ($idx === 0 && !$cheio_r) ? 'checked' : ''; ?>
                    <?php echo $cheio_r ? 'disabled' : ''; ?>
                    style="margin-right:5px;">
                <b style="color:#FF9800;"><?php echo htmlspecialchars($srv_r['nome']); ?></b><br>
                <div style="background:#333;border-radius:3px;height:7px;margin:5px 0 3px 0;overflow:hidden;">
                    <div style="width:<?php echo $pct_r; ?>%;height:7px;background:<?php echo $cor_r; ?>;border-radius:3px;"></div>
                </div>
                <small style="color:#aaa;font-size:11px;"><?php echo $cheio_r ? 'Cheio' : ($vagas_r . ' vagas'); ?></small>
            </label>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <input type="hidden" name="reg_servidor_id" value="1">
    <p style="color:#aaa;">Nenhum servidor disponível no momento.</p>
    <?php endif; ?>
</fieldset>
<fieldset>
        <legend>Termos e Condi&ccedil;&otilde;es</legend>
    <input type="checkbox" id="reg_termos" name="reg_termos" /> Declaro que <b>li</b> e <b>aceito</b> os termos propostos, e que estou ciente das regras do jogo.
    <div class="sep"></div>
    <div align="center"><input type="submit" class="botao" id="subm" name="subm" value="Registrar" /></div>
</fieldset>
</form>
</div>
<div class="box_bottom"></div>
<?php
// PDO automatically frees result sets
?>