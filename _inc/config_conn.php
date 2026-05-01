<?php
if(!isset($db) || !is_array($db)){ $db = []; }
if(isset($_POST['conn'])){
        $atu = isset($_POST['conn_atu']) ? 'sim' : 'nao';

        // YouTube: aceita URL completa, @handle ou ID; armazenamos só o identificador
        $yt_raw = trim($_POST['conn_youtube'] ?? '');
        $youtube = '';
        if(isset($_POST['conn_yt_have']) && $yt_raw !== ''){
                if(preg_match('~youtube\.com/(?:channel/|user/|c/|@)?([A-Za-z0-9_\-]+)~i', $yt_raw, $m)){
                        $youtube = $m[1];
                } else {
                        $youtube = ltrim($yt_raw, '@');
                }
                $youtube = preg_replace('/[^A-Za-z0-9_\-]/', '', $youtube);
        }
        $okyt = (isset($_POST['conn_yt_ok']) && $youtube !== '') ? 'sim' : 'nao';

        try {
                $stmt = $conexao->prepare("UPDATE usuarios SET config_atualizacoes = ?, config_youtube = ?, config_okyoutube = ? WHERE id = ?");
                $stmt->execute([$atu, $youtube, $okyt, (int)$db['id']]);
                $db['config_atualizacoes'] = $atu;
                $db['config_youtube']      = $youtube;
                $db['config_okyoutube']    = $okyt;
        } catch (PDOException $e) {
                // Handle error silently
        }
        echo "<script>self.location='?p=config&type=conn&msg=1'</script>";
}
if(isset($_GET['msg'])){
        switch($_GET['msg']){
                case 1: $msg='Configurações atualizadas com sucesso!'; break;
        }
        echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
}
?>
<script>
function showDiv(box,id)
{
 var elm = document.getElementById(id);
 elm.style.display = box.checked? "block":"none"
}
</script>
<form method="post" action="?p=config&amp;type=conn" style="background:url(_img/config_conn.jpg) no-repeat right top;" onsubmit="subm.value='Carregando...';subm.disabled=true;">
<input type="hidden" id="conn" name="conn" value="1" />
<fieldset><legend>YouTube</legend>
    <input type="checkbox" id="conn_yt_have" name="conn_yt_have" <?php if(($db['config_youtube'] ?? '')<>'') echo 'checked="true"'; ?> onclick="showDiv(this,'youtube');" /> Desejo exibir meu canal do YouTube no perfil.<br /><span class="sub2">Marque esta opção para mostrar os últimos vídeos do seu canal do YouTube no seu perfil.</span>
    <div id="youtube" style="padding-left:25px;display:<?php if(($db['config_youtube'] ?? '')<>'') echo 'block'; else echo 'none'; ?>">
        <div class="sep"></div><span class="destaque">Meu Canal:</span><br />
        <input type="text" id="conn_youtube" name="conn_youtube" value="<?php echo htmlspecialchars($db['config_youtube'] ?? ''); ?>" placeholder="UCxxxxxxxx ou @SeuCanal" style="width:280px;" /><br />
        <span class="sub2">Cole o <b>ID do canal</b> (começa com <code>UC</code>, encontrado em youtube.com/account_advanced) ou o link do seu canal. O ID é o mais recomendado para garantir que os últimos vídeos apareçam.</span>
        <div class="sep"></div>
        <input type="checkbox" id="conn_yt_ok" name="conn_yt_ok" <?php if(($db['config_okyoutube'] ?? '')=='sim') echo 'checked="true"'; ?>/> Autorizo todos os jogadores a verem meu YouTube.<br /><span class="sub2">Marque esta opção para permitir que outros jogadores vejam seu canal.</span>
    </div>
</fieldset>
<fieldset><legend>Atualizações</legend>
    <input type="checkbox" id="conn_atu" name="conn_atu" <?php if(($db['config_atualizacoes'] ?? '')=='sim') echo 'checked="true"'; ?>/> Desejo enviar minhas atualizações aos meus amigos.<br /><span class="sub2">Marque esta opção para permitir o envio de atualizações à seus amigos.</span>
    <div class="sep"></div>
    <div align="center"><input type="submit" id="subm" name="subm" class="botao" value="Salvar Alterações" /></div>
</fieldset>
</form>
