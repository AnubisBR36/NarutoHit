<?php require_once('trava.php'); ?>
<?php
require_once('game_config.php');
require_once('game_events.php');
$atual=date('Y-m-d H:i:s');
if($db['treino']==0){ echo "<script>self.location='?p=home'</script>"; return; } else {
        if($atual<$db['treino_fim']){ echo "<script>self.location='?p=busytrain'</script>"; return; } else {
                try {
                        $conexao->beginTransaction();
                        
                        $update_treino = $conexao->prepare("UPDATE usuarios SET treino=0 WHERE id=?");
                        $update_treino->execute([$db['id']]);
                        
                        $sqlj = $conexao->prepare("SELECT id, exp, expmax, nivel FROM jutsus WHERE usuarioid=? AND jutsu=?");
                        $sqlj->execute([$db['id'], $db['treino']]);
                        $dbj = $sqlj->fetch(PDO::FETCH_ASSOC);
                        
                        if($dbj) {
                                $_treino_div = max(1,(int)get_gc('treino_exp_divisor',5));
                                $exp=$db['treino_tempo']/$_treino_div;
                                // Aplicar multiplicador de evento/global
                                $_mults_treino = get_event_mults();
                                $exp = max(1, (int)round($exp * $_mults_treino['exp']));
                                $dbj['exp']=$dbj['exp']+$exp;
                                $msgadicional='';
                                
                                if($dbj['exp']>=$dbj['expmax']){
                                        $dbj['nivel']=$dbj['nivel']+1;
                                        $expatual=$dbj['exp']-$dbj['expmax'];
                                        if($dbj['nivel']<5) $dbj['expmax']=$dbj['expmax']+30; else $dbj['expmax']=99999;
                                        $msgadicional='Seu jutsu alcançou o <b>nível '.$dbj['nivel'].'</b>.';
                                        
                                        $update_jutsu = $conexao->prepare("UPDATE jutsus SET exp=?, expmax=?, nivel=nivel+1 WHERE id=?");
                                        $update_jutsu->execute([$expatual, $dbj['expmax'], $dbj['id']]);
                                } else {
                                        $update_exp = $conexao->prepare("UPDATE jutsus SET exp=exp+? WHERE id=?");
                                        $update_exp->execute([$exp, $dbj['id']]);
                                }
                        } else {
                                $exp = 0;
                                $msgadicional = 'Jutsu não encontrado.';
                        }
                        
                        $conexao->commit();
                } catch (PDOException $e) {
                        $conexao->rollBack();
                        echo "<script>self.location='?p=home'</script>";
                        return;
                }
        }
}
?>
<div id="newlvl">
</div>
<div class="box_top">Treino Finalizado</div>
<div class="box_middle"><div class="aviso">Parabéns por conseguir terminar seu treino.<br />Você adquiriu <b><?php echo $exp; ?> pontos de experiência</b> para o jutsu treinado.<br /><?php echo $msgadicional; ?>Clique <a href="?p=school">aqui</a> para voltar à escola.</div>
</div>
<div class="box_bottom"></div>
