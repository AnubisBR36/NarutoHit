<?php
require_once('Encrypt.php');
$c=new C_Encrypt();

// Verificar se o usuário está logado
if(!isset($db['id'])) {
    echo "<script>location.href='?p=home';</script>";
    exit;
}

// Verificar se foi passado o ID da sala
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>location.href='?p=school';</script>";
    exit;
}

$sala_id = (int)$_GET['id'];
$atual = date('Y-m-d H:i:s');

try {
    // Verificar se a sala existe e se o usuário tem acesso
    $stmt = $conexao->prepare("SELECT * FROM salas WHERE id = ? AND usuarioid = ? AND fim > ?");
    $stmt->execute([$sala_id, $db['id'], $atual]);
    $dbr = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$dbr) {
        echo "<script>location.href='?p=school';</script>";
        exit;
    }
    
    // Calcular tempo restante
    $tempo_restante = strtotime($dbr['fim']) - strtotime($atual);
    if($tempo_restante > 0) {
        $horas = floor($tempo_restante / 3600);
        $minutos = floor(($tempo_restante % 3600) / 60);
        $segundos = $tempo_restante % 60;
        
        $tempo_formatado = sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos);
        $msgconc = '<b>Tempo Restante: <span id="sala_tempo" style="color:#FFFFFF">'.$tempo_formatado.'</span></b>';
        $msg = '<b>Tempo Restante: <span id="sala_tempo" style="color:#FFFFFF">'.$tempo_formatado.'</span></b>';
    } else {
        echo "<script>location.href='?p=school';</script>";
        exit;
    }
    
} catch (PDOException $e) {
    echo "<script>location.href='?p=school';</script>";
    exit;
}
?>
<script language="javascript" type="text/javascript">
var conc=0;
function calculafim(div,divtotal){
        if(conc==0){
        var navegador=navigator.appName;
        var tmp = document.getElementById(div).innerHTML.split(":");
        var s = tmp[2];
        var m = tmp[1];
        var h = tmp[0];
        s--;
        if (s < 00){ s = 59;    m--; }
        if (m < 00){ m = 59;    h--; };
        s = new String(s); if (s.length < 2) s = "0" + s;
        m = new String(m); if (m.length < 2) m = "0" + m;
        h = new String(h); if (h.length < 2) h = "0" + h;
        
        var temp = h + ":" + m + ":" + s;
        
        document.getElementById(div).innerHTML = temp;
        document.getElementById(div).value = temp;
        atualiza(div,divtotal);
        }
}
<?php if($atual<$dbr['fim']) echo "window.setInterval('calculafim(\"sala_tempo\",\"mensagem\")',1000);"; ?>
function atualiza(div,divtotal){
        if((document.getElementById(div).value) < "00:00:01"){
                self.location="?p=school";
                conc=1;
        }
}
</script>
<?php
if(isset($_POST['train'])){
        if(!csrf_validar()) { echo "<script>location.href='?p=home';</script>"; exit; }
        $train=$c->decode($_POST['train'],$chaveuniversal);
        $tempo=$c->decode($_POST['tempo'],$chaveuniversal);
        
        // Validar dados
        if(!is_numeric($train) || !is_numeric($tempo)) {
                echo "<script>location.href='?p=schooltrain&id=".$_GET['id']."';</script>";
                exit;
        }
        
        if($tempo < 10 || $tempo > 480) {
                echo "<script>location.href='?p=home';</script>";
                exit;
        }
        
        $treinofim = date('Y-m-d H:i:s', strtotime("+{$tempo} minutes"));
        
        try {
                $conexao->beginTransaction();
                
                // Liberar a sala
                $update_sala = $conexao->prepare("UPDATE salas SET fim='0000-00-00 00:00:00', usuarioid=0 WHERE id=?");
                $update_sala->execute([$_GET['id']]);
                
                // Iniciar treino do usuário
                $update_user = $conexao->prepare("UPDATE usuarios SET treino=?, treino_tempo=?, treino_fim=? WHERE id=?");
                $update_user->execute([$train, $tempo, $treinofim, $db['id']]);
                
                $conexao->commit();
                echo "<script>location.href='?p=busytrain';</script>";
                exit;
        } catch (PDOException $e) {
                $conexao->rollBack();
                echo "<script>location.href='?p=room&id=".$_GET['id']."';</script>";
                exit;
        }
}

// Buscar jutsus do usuário
$jutsus_usuario = [];
try {
    $sqlj = $conexao->prepare("SELECT j.*, t.nome, t.natureza, t.forca, t.id as jutsu_id FROM jutsus j LEFT OUTER JOIN table_jutsus t ON j.jutsu=t.id WHERE j.usuarioid=? ORDER BY t.natureza DESC, j.nivel DESC");
    $sqlj->execute([$db['id']]);
    $jutsus_usuario = $sqlj->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $jutsus_usuario = [];
}
?>
<div class="box_top">Treinar</div>
<div class="box_middle">Aperfeiçoe tudo que você aprendeu até o momento! Escolha o que deseja treinar, e por quanto tempo. Lembre-se que a cada 10 minutos treinados, você adquire 2 pontos de experiência para a habilidade escolhida. Seu treinamento não será feito na sala, e sim no pátio da escola, portanto esta sala ficará disponível para outros ninjas.
  <div class="sep"></div>
        <div class="aviso" id="mensagem">
    <b>
        <?php
        if($atual<$dbr['fim'])
                echo $msg; 
        else
                echo $msgconc;
        ?>
    </b></div><div class="sep"></div>
     <?php if(count($jutsus_usuario) == 0) { ?>
         <div class="aviso">Nenhum jutsu aprendido até o momento.</div><div class="sep"></div>
     <?php } else { ?>
    <table width="100%" cellpadding="0" cellspacing="1">
    <?php 
    // Usar os jutsus já buscados
    foreach($jutsus_usuario as $dbj) { ?>
    <tr class="table_dados" style="background:#323232">
        <td width="230"><img src="_img/jutsus/<?php echo $dbj['jutsu']; ?>.jpg" /></td>
        <td width="100"><b>Nível <?php echo $dbj['nivel']; ?></b><br /><span class="sub2">Experiência<br /><?php echo $dbj['exp'].' / '.$dbj['expmax']; ?></span></td>
        <td>
        <?php if($dbj['nivel']==5) { ?>
            Nível máximo alcançado!
        <?php } else { ?>
        <form method="post" action="?p=schooltrain&amp;id=<?php echo (int)$_GET['id']; ?>" onsubmit="subm.value='Carregando...';subm.disabled=true;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="train" value="<?php echo $c->encode($dbj['jutsu'],$chaveuniversal); ?>">
        <select name="tempo">
            <?php for($i=1; $i<49; $i++) { ?>
            <option value="<?php echo $c->encode(($i*10),$chaveuniversal); ?>"><?php echo $i*10; ?> minutos</option>
            <?php } ?>
        </select>
        <br /><span class="sub2">Selecione a quantidade de minutos</span><br />
        <input type="submit" name="subm" class="botao" value="Escolher">
        </form>
        <?php } ?>
        </td>
    </tr>
    <tr>
        <td colspan="3"><div class="sep"></div></td>
    </tr>
    <?php } ?>
    </table>
    <?php } ?>
    <div align="center"><input type="button" class="botao" value="Sair da Sala" onclick="location.href='?p=room&id=<?php echo $_GET['id']; ?>'" /></div>
</div>
<div class="box_bottom"></div>
<?php
// PDO automatically frees result sets
?>