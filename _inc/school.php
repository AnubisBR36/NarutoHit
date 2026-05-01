<?php
if(isset($_GET['cancel'])){
        try {
                $stmt = $conexao->prepare("UPDATE usuarios SET treino=0, treino_fim='0000-00-00 00:00:00' WHERE id=?");
                $stmt->execute([$db['id']]);
        } catch (PDOException $e) {
                // Handle error silently
        }
        $db['treino']=0;
        $db['treino_fim']='0000-00-00 00:00:00';
}
?>
<?php require_once('verificar.php'); ?>
<?php
// Verificar se a tabela 'salas' existe, se não, criar 
try {
    // Verificar se a tabela 'salas' existe 
    $table_exists = Database::tableExists($conexao, 'salas');

    if(!$table_exists) {
        $pk = Database::autoIncPK('id');
        $create_table = "CREATE TABLE IF NOT EXISTS salas (
            $pk,
            usuarioid INTEGER NOT NULL DEFAULT 0,
            fim VARCHAR(20) NOT NULL DEFAULT '0000-00-00 00:00:00'
        )";
        $conexao->exec($create_table);
    }

    // Verificar quantas salas existem
    $count_salas = $conexao->prepare("SELECT COUNT(*) as total FROM salas");
    $count_salas->execute();
    $total_salas = $count_salas->fetch(PDO::FETCH_ASSOC)['total'];

    // Se não há salas suficientes, inserir as faltantes
    if($total_salas < 5) {
        for($i = 1; $i <= 5; $i++) {
            $check_existing = $conexao->prepare("SELECT id FROM salas WHERE id = ?");
            $check_existing->execute([$i]);
            if($check_existing->rowCount() == 0) {
                try {
                    $stmt = $conexao->prepare("INSERT INTO salas (usuarioid, fim) VALUES (0, '0000-00-00 00:00:00')");
                    $stmt->execute();
                } catch (PDOException $e) {
                    // Se falhar a inserção, ignorar (sala já existe)
                }
            }
        }
    }

    $sqls = $conexao->prepare("SELECT s.*,u.usuario FROM salas s LEFT OUTER JOIN usuarios u ON s.usuarioid=u.id ORDER BY s.id ASC");
    $sqls->execute();
    $dbs = $sqls->fetch(PDO::FETCH_ASSOC);

    // Verificar se há pelo menos uma sala
    if(!$dbs) {
        // Se não há salas, criar salas padrão
        for($i = 1; $i <= 5; $i++) {
            $stmt = $conexao->prepare("INSERT INTO salas (usuarioid, fim) VALUES (0, '0000-00-00 00:00:00')");
            $stmt->execute();
        }
        // Tentar novamente
        $sqls = $conexao->prepare("SELECT s.*,u.usuario FROM salas s LEFT OUTER JOIN usuarios u ON s.usuarioid=u.id ORDER BY s.id ASC");
        $sqls->execute();
        $dbs = $sqls->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    echo '<div class="box_top">Erro</div>';
    echo '<div class="box_middle">Erro ao carregar informações das salas. Erro na consulta: ' . $e->getMessage() . '</div>';
    echo '<div class="box_bottom"></div>';
    return;
}
?>
<?php
$atual=date('Y-m-d H:i:s');
try {
    $sqlv = $conexao->prepare("SELECT * FROM salas WHERE usuarioid=? AND fim>?");
    $sqlv->execute([$db['id'], $atual]);
    $dbv = $sqlv->fetch(PDO::FETCH_ASSOC);
    $num_rows = $sqlv->rowCount();
} catch (PDOException $e) {
    echo '<div class="box_top">Erro</div>';
    echo '<div class="box_middle">Erro ao verificar reservas de sala. Erro na consulta: ' . $e->getMessage() . '</div>';
    echo '<div class="box_bottom"></div>';
    return;
}

// Se o usuário já tem uma sala ativa, redirecionar automaticamente para ela
if($num_rows > 0 && $dbv){
    // Verificar se a reserva ainda é válida
    if($dbv['fim'] > $atual) {
        // Redirecionar automaticamente para a sala ativa
        echo "<script>location.href='?p=room&id=".$dbv['id']."';</script>";
        return;
    } else {
        // Se chegou aqui, a reserva expirou
        $msg = 'Sua reserva expirou.';
        // Clear expired reservation
        try {
            $clear_stmt = $conexao->prepare("UPDATE salas SET usuarioid=0, fim='0000-00-00 00:00:00' WHERE id=?");
            $clear_stmt->execute([$dbv['id']]);
            $dbv = false;
            $num_rows = 0;
        } catch (PDOException $e) {
            // Handle clear error
        }
    }
}

// Limpar reservas expiradas ANTES de mostrar a lista de salas
try {
    $cleanup = $conexao->prepare("UPDATE salas SET usuarioid=0, fim='0000-00-00 00:00:00' WHERE usuarioid<>0 AND fim<=?");
    $cleanup->execute([$atual]);
    
    // Fazer uma segunda limpeza forçada para casos persistentes
    $forceCleanup = $conexao->prepare("UPDATE salas SET usuarioid=0, fim='0000-00-00 00:00:00' WHERE usuarioid<>0 AND fim < DATE_ADD(NOW(), INTERVAL -1 MINUTE)");
    $forceCleanup->execute();
} catch (PDOException $e) {
    // Handle cleanup error silently
}

// Se o usuário não tem sala ativa, mostrar salas disponíveis
?>
<div class="box_top">Escola Ninja</div>
<div class="box_middle">Bem-vindo à Escola Ninja! Aqui você poderá aprender novos jutsus, descobrir a natureza do seu chakra, praticar jutsus, entre outros. Escolha uma das salas disponíveis abaixo para usar. Haverá um sensei a sua espera!<br /><b>OBS: Você só poderá ficar na sala em um tempo máximo de 5 minutos.</b><div class="sep"></div>
        <div align="center">
    <div class="aviso"><?php if(isset($_GET['cancel'])) echo 'Treino cancelado!<div class="sep"></div>'; ?><b>Salas disponíveis: <span id="disp"></span> de 5</b></div>
    <?php if(isset($_GET['cleanup'])) { 
        try {
            // Limpar apenas a sala da conta atual
            $user_cleanup = $conexao->prepare("UPDATE salas SET usuarioid=0, fim='0000-00-00 00:00:00' WHERE usuarioid=?");
            $user_cleanup->execute([$db['id']]);
            
            // Também limpar salas expiradas em geral
            $force_cleanup = $conexao->prepare("UPDATE salas SET usuarioid=0, fim='0000-00-00 00:00:00' WHERE fim<=?");
            $force_cleanup->execute([$atual]);
            
            echo '<div class="aviso" style="color:#33CC00">Sua reserva foi removida e salas atualizadas!</div>';
        } catch (PDOException $e) {
            echo '<div class="aviso" style="color:#FF0000">Erro ao atualizar salas.</div>';
        }
        echo '<script>setTimeout(function(){location.href="?p=school";}, 1500);</script>';
    } ?>
    <div style="margin:5px 0;">
        <a href="?p=school&cleanup=1" class="botao" style="padding:3px 8px; font-size:11px; text-decoration:none;">Atualizar Salas</a>
    </div>
    <div class="sep"></div>
    <table width="100%" border="0" cellpadding="3" cellspacing="1" style="border-collapse: collapse;">
      <tr class="table_titulo">
        <td width="20%" style="text-align:center;"><b>Sala</b></td>
        <td width="30%" style="text-align:center;"><b>Usuário</b></td>
        <td width="25%" style="text-align:center;"><b>Status</b></td>
        <td width="25%" style="text-align:center;"><b>Ação</b></td>
      </tr>
      <?php 
      // Buscar salas novamente após a limpeza
      $sqls = $conexao->prepare("SELECT s.*,u.usuario FROM salas s LEFT OUTER JOIN usuarios u ON s.usuarioid=u.id ORDER BY s.id ASC");
      $sqls->execute();
      $i=1; $d=0; 
      while($dbs = $sqls->fetch(PDO::FETCH_ASSOC)) { 
        // Verificar se a sala está disponível (sem usuário ou tempo expirado)
        $sala_disponivel = ($dbs['usuarioid']==0) || ($atual>$dbs['fim']);
        if($sala_disponivel) {
            $d++; 
            // Se a sala expirou mas ainda tem usuário, limpar agora
            if($dbs['usuarioid'] != 0 && $atual > $dbs['fim']) {
                try {
                    $clear_expired = $conexao->prepare("UPDATE salas SET usuarioid=0, fim='0000-00-00 00:00:00' WHERE id=?");
                    $clear_expired->execute([$dbs['id']]);
                    $dbs['usuarioid'] = 0;
                    $dbs['usuario'] = null;
                } catch (PDOException $e) {
                    // Handle error silently
                }
            }
        }
      ?>
      <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
        <td style="text-align:center; padding:5px;">Sala <?php echo $i; ?></td>
        <td style="text-align:center; padding:5px;"><?php 
          if($sala_disponivel) {
            echo 'Ninguém'; 
          } else {
            if($dbs['usuario']) {
              echo '<a href="?p=view&view='.$dbs['usuario'].'">'.$dbs['usuario'].'</a>';
            } else {
              echo 'Ocupada';
            }
          }
        ?></td>
        <td style="text-align:center; padding:5px;"><?php 
          if($sala_disponivel) {
            echo '<span style="color:#33CC00">Disponível</span>'; 
          } else {
            echo '<span style="color:#FF9900">Ocupada</span>';
          }
        ?></td>
        <td style="text-align:center; padding:5px;"><?php 
          if($sala_disponivel) {
            echo '<a href="?p=room&amp;id='.$dbs['id'].'" class="botao" style="padding:4px 12px; text-decoration:none; display:inline-block; margin:2px;">Entrar</a>';
          } else {
            echo '-';
          }
        ?></td>
      </tr>
      <?php $i++; } ?>
    </table>
    </div>
    <script>document.getElementById('disp').innerHTML='<?php echo $d; ?>';</script>
</div>
<div class="box_bottom"></div>
<script language="javascript" type="text/javascript">
var conc=0;
function calculafim(div,divtotal){
        var navegador=navigator.appName;
        var element = document.getElementById(div);
        if(!element || !element.innerHTML) return;

        var tmp = element.innerHTML.split(":");
        if(tmp.length != 3) return;

        var s = parseInt(tmp[2]);
        var m = parseInt(tmp[1]);
        var h = parseInt(tmp[0]);
        s--;
        if (s < 0){ s = 59; m--; }
        if (m < 0){ m = 59; h--; }
        if (h < 0){ h = 0; m = 0; s = 0; }

        s = new String(s); if (s.length < 2) s = "0" + s;
        m = new String(m); if (m.length < 2) m = "0" + m;
        h = new String(h); if (h.length < 2) h = "0" + h;

        var temp = h + ":" + m + ":" + s;

        element.innerHTML = temp;
        if(element.value !== undefined) element.value = temp;
        atualiza(div,divtotal);
}
function atualiza(div,divtotal){
        if((document.getElementById(div).value) < "00:00:01"){
                self.location="?p=school";
                conc=1;
        }
}
</script>
<?php
// PDO automatically frees result sets
?>