<?php
require_once('Encrypt.php');
$c=new C_Encrypt();

// (Trava de energia removida da página do jogador — agora é exclusiva do painel ADM.)

if(isset($_POST['batt'])){
        try {
                $stmt = $conexao->prepare("SELECT id,jutsu,status FROM jutsus WHERE usuarioid=?");
                $stmt->execute([$db['id']]);
                
                while($dbj = $stmt->fetch(PDO::FETCH_ASSOC)){
                        if(isset($_POST['jutsu'.$dbj['jutsu']])){
                                if($dbj['status']=='inativo') {
                                        $updateStmt = $conexao->prepare("UPDATE jutsus SET status='ativo' WHERE id=?");
                                        $updateStmt->execute([$dbj['id']]);
                                }
                        } else {
                                if($dbj['status']=='ativo') {
                                        $updateStmt = $conexao->prepare("UPDATE jutsus SET status='inativo' WHERE id=?");
                                        $updateStmt->execute([$dbj['id']]);
                                }
                        }
                }
        } catch (PDOException $e) {
                // Handle error silently
        }
        echo "<script>self.location='?p=config&type=batt&msg=1'</script>";
}
?>
<?php
if(isset($_GET['msg'])){
    $msg_aviso = '';
    switch($_GET['msg']){
        case 1: $msg_aviso='Configurações alteradas com sucesso!'; break;
    }
    if(!empty($msg_aviso)) echo '<div class="aviso">'.$msg_aviso.'</div><div class="sep"></div>';
}
?>

<?php
try {
        $stmt = $conexao->prepare("SELECT j.jutsu,j.nivel,j.status,t.nome FROM jutsus j LEFT OUTER JOIN table_jutsus t ON j.jutsu=t.id WHERE j.usuarioid=? ORDER BY j.status, j.nivel");
        $stmt->execute([$db['id']]);
        $jutsus = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
        $jutsus = [];
}
?>
<fieldset><legend>Jutsus</legend>
        Selecione os jutsus que serão usados na batalha. Os jutsus serão usados em ordem aleatória. Assim que todos os jutsus marcados forem usados, será iniciado o combate corpo-a-corpo.<div class="sep"></div>
    <?php if(empty($jutsus)) echo '<div class="aviso">Você não aprendeu nenhum jutsu para utilizar em batalha.</div>'; else { ?>
    <form method="post" action="?p=config&amp;type=batt" onsubmit="subm.value='Carregando...';subm.disabled=true;">
    <input type="hidden" id="batt" name="batt" value="1" />
        <?php foreach($jutsus as $dbj){ ?>
        <div><input type="checkbox" id="jutsu<?php echo $dbj['jutsu']; ?>" name="jutsu<?php echo $dbj['jutsu']; ?>"<?php if($dbj['status']=='ativo') echo ' checked="checked"'; ?> /> <?php echo $dbj['nome']; ?> - <span class="sub2">Nível <?php echo $dbj['nivel']; ?></span></div>
    <?php } ?>
    <div class="sep"></div>
    <div align="center"><input type="submit" id="subm" name="subm" class="botao" value="Salvar Alterações" /></div>
    </form>
    <?php } ?>
</fieldset>
