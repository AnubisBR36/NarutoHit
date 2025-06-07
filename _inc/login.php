<div class="box_top">Login</div>
<div class="box_middle">Digite seu login e senha nos campos abaixo para acessar o jogo.<div class="sep"></div>
	<?php if(isset($_GET['erro'])){
		switch($_GET['erro']){
			case 'ban': $msg='Conta banida.'; break;
			case 1: $msg='Digite uma <b>SENHA</b> v&aacute;lida.'; break;
			case 2: $msg='Nenhum usu&aacute;rio encontrado com o login informado.'; break;
			case 3: $msg='Senha digitada incorreta.'; break;
			case 4: if(isset($_GET['date'])){ $data=$c->decode($_GET['date'],$chaveuniversal); $ex=explode('_',$data); $data=explode('-',$ex[0]); } else $data='0000-00-00_00:00:00'; $msg='Sua conta está em período de férias.<br />Não é possível realizar o login até o dia <b>'.$data[2].'/'.$data[1].'/'.$data[0].', às '.$ex[1].' horas</b>.'; break;
			//case 5: $msg='Sua conta ainda está inativa.<br />Verifique o email de ativação enviado pelo sistema.'; break;
		}
		echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
	} ?>
    <?php if(isset($_GET['reason'])) echo '<div class="aviso">Você foi deslogado pois outro usuário acessou sua conta.</div><div class="sep"></div>'; ?>
    <?php if(isset($_GET['ban'])) echo '<div class="aviso">Conta banida.</div><div class="sep"></div>'; ?>
    <fieldset>
    	<legend>Dados de Acesso</legend>
        	<form method="post" action="?p=login" id="login" name="login" style="background:url(_img/login<?php echo rand(1,2); ?>.jpg) no-repeat right;" onsubmit="subm.value='Carregando...';subm.disabled=true;">
            	<span class="destaque">Servidor:</span><br />
                <select>
                	<option onclick="location.href='http://www.narutohit.net/?p=login'" selected="selected">Servidor 01</option>
                    <option onclick="location.href='http://servidor02.narutohit.net/?p=login'">Servidor 02</option>
                </select><br />
                <span class="sub2">Escolha o servidor em que deseja jogar.</span><br /><br />
            	<span class="destaque">Login:</span><br />
                <input type="text" id="login_login" name="login_login" maxlength="15" onfocus="className='input'" onblur="className=''" /><br />
                <span class="sub2">Digite seu login de acesso (nome do usu&aacute;rio).</span><br /><br />
                <span class="destaque">Senha:</span><br />
                <input type="password" id="login_senha" name="login_senha" maxlength="15" onfocus="className='input'" onblur="className=''" /><br />
                <span class="sub2">Digite sua senha de acesso.</span><br /><br />
                <input type="submit" id="subm" name="subm" class="botao" value="Acessar" /><br /><br />
                <a href="?p=terms">Registrar no narutoHIT</a> | <a href="?p=recover">Nova Senha</a>
            </form>
	</fieldset>
</div>
<div class="box_bottom"></div>
<script>document.forms[0].login_login.focus()</script>
<?php
// _inc/login.php
if(isset($_POST['subm'])){
	$erro=0;
	if($_POST['login_senha']=='') $erro=1;
	if($_POST['login_login']=='') $erro=1;
	if($erro>0){ header("Location: index.php?p=login&erro=".$erro); exit; }
	else {
		// Buscar usuário diretamente sem normalização
		$stmt = $conexao->prepare("SELECT * FROM usuarios WHERE usuario=? AND senha=?");
		$stmt->execute([$_POST['login_login'], md5($_POST['login_senha'])]);
		$db = $stmt->fetch(PDO::FETCH_ASSOC);

		if($db){
			// Verificar se o usuário está banido
			if($db['status'] == 'banido') {
				$ban_fim = strtotime($db['ban_fim']);
				if($ban_fim > time()) {
					// Ainda está banido
					$ban_data = date('d/m/Y H:i', $ban_fim);
					$msg = "Sua conta está banida até $ban_data.<br>Motivo: " . htmlspecialchars($db['ban_motivo']);
				} else {
					// Ban expirou, reativar conta
					$conexao->exec("UPDATE usuarios SET status='ativo', ban_fim='2000-01-01 00:00:00', ban_motivo='' WHERE id='".$db['id']."'");
					$db['status'] = 'ativo';
				}
			}
			if($db['ativo']==0){ header("Location: index.php?p=login&erro=5"); exit; }
			if($db['banido']>0){ header("Location: index.php?p=login&erro=ban"); exit; }
			if($db['ferias']>$timestamp){ header("Location: index.php?p=login&erro=4&date=".$c->encode($db['ferias'].'_'.$db['ferias_horario'],$chaveuniversal)); exit; }
			$_SESSION['uid']=$db['id'];
			$_SESSION['userid']=$db['id'];
			$_SESSION['logado']=$db['id'];
			$_SESSION['login']=$db['usuario'];
			$_SESSION['senha']=$db['senha'];
			$_SESSION['email']=$db['email'];
			$_SESSION['admin']=$db['admin'];
			$_SESSION['adm']=$db['adm'];
			$_SESSION['vip']=$db['vip'];
			$_SESSION['avatar']=$db['avatar'];
			$_SESSION['ultimo_login']=$db['ultimo_login'];
			$conexao->exec("UPDATE usuarios SET ultimo_login='".$timestamp."' WHERE id='".$db['id']."'");
			header("Location: index.php?p=inicio"); exit;
		} else {
			// Senha incorreta
			header("Location: index.php?p=login&erro=3"); exit;
		}
	}
}
?>