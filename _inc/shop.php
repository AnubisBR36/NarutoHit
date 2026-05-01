<?php
require_once('trava.php');
require_once('Encrypt.php');
require_once(__DIR__ . '/personagens_catalogo.php');
$c=new C_Encrypt();

// Personagens são desbloqueados automaticamente por nível (catálogo central),
// portanto a "loja de personagens" virou uma vitrine. Se um POST antigo de
// compra de personagem chegar aqui, redirecionamos para a vitrine.
if(isset($_POST['char_id']) || (isset($_POST['buy_cat']) && $_POST['buy_cat'] === 'personagem')){
        // Tenta forçar o auto-unlock e devolve para a vitrine.
        personagens_unlock_por_nivel($conexao, $db);
        echo "<script>self.location='?p=shop&category=characters'</script>";
        exit;
}

if(isset($_POST['buy_id']) && isset($_POST['buy_page']) && isset($_POST['buy_cat'])) {
                $buy_id = intval($_POST['buy_id']);
                $page = $_POST['buy_page'];
                $category = $_POST['buy_cat'];

                // Personagens são liberados automaticamente por nível — nunca chega aqui
                // (interceptado no topo do arquivo). Para itens, usa o id do form.
                $buy = $buy_id;

        try {
                $stmt = $conexao->prepare("SELECT valor, reqtai, reqnin, reqgen, vip FROM table_itens WHERE id=?");
                $stmt->execute([$buy]);
                $dbb = $stmt->fetch(PDO::FETCH_ASSOC);

                if(!$dbb){
                        echo "<script>self.location='?p=home'</script>";
                        exit;
                }

                // Check VIP requirement
                if($dbb['vip'] == 'sim' && (!isset($db['vip']) || date('Y-m-d H:i:s') >= $db['vip'])){ 
                        echo "<script>self.location='?p=shop&category=".$page."&msg=1'</script>"; 
                        exit; 
                }

                $valor = $dbb['valor'];
                if(isset($db['vip']) && date('Y-m-d H:i:s') < $db['vip']) {
                        $valor = floor($valor * 0.8);
                }

                $bloq = 0;

                // Check requirements
                if($category == 'arma' && isset($dbb['reqtai']) && $db['taijutsu'] < $dbb['reqtai']) $bloq = 3;
                if($category == 'vestimenta' && isset($dbb['reqgen']) && $db['genjutsu'] < $dbb['reqgen']) $bloq = 6;
                if($category == 'calcado' && isset($dbb['reqtai'])) {
                        if($db['taijutsu'] < $dbb['reqtai'] || $db['ninjutsu'] < $dbb['reqtai'] || $db['genjutsu'] < $dbb['reqtai']) $bloq = 7;
                }
                if($category == 'mascara' && isset($dbb['reqtai'], $dbb['reqnin'], $dbb['reqgen'])) {
                        if($db['taijutsu'] < $dbb['reqtai'] || $db['ninjutsu'] < $dbb['reqnin'] || $db['genjutsu'] < $dbb['reqgen']) $bloq = 7;
                }
                if($category == 'pergaminho' && isset($dbb['reqtai'], $dbb['reqnin'], $dbb['reqgen'])) {
                        if($db['taijutsu'] < $dbb['reqtai'] || $db['ninjutsu'] < $dbb['reqnin'] || $db['genjutsu'] < $dbb['reqgen']) $bloq = 7;
                }
                if($category == 'calca' && isset($dbb['reqtai'], $dbb['reqnin'], $dbb['reqgen'])) {
                        if($db['taijutsu'] < $dbb['reqtai'] || $db['ninjutsu'] < $dbb['reqnin'] || $db['genjutsu'] < $dbb['reqgen']) $bloq = 7;
                }
                if($category == 'luva' && isset($dbb['reqtai'], $dbb['reqnin'], $dbb['reqgen'])) {
                        if($db['taijutsu'] < $dbb['reqtai'] || $db['ninjutsu'] < $dbb['reqnin'] || $db['genjutsu'] < $dbb['reqgen']) $bloq = 7;
                }
                if($bloq > 0){ 
                        echo "<script>self.location='?p=shop&category=".$page."&msg=".$bloq."'</script>"; 
                        exit; 
                }

                // Check if user has enough money
                // Force reload user data to ensure we have latest yens value
                $stmt_reload = $conexao->prepare("SELECT yens FROM usuarios WHERE id=?");
                $stmt_reload->execute([$db['id']]);
                $current_yens = $stmt_reload->fetch(PDO::FETCH_ASSOC);
                
                if($current_yens['yens'] < $valor) {
                        echo "<script>self.location='?p=shop&category=".$page."&msg=1'</script>"; 
                        exit;
                }

                // Check if user already has this item
                $stmt_check = $conexao->prepare("SELECT id FROM inventario WHERE usuarioid=? AND itemid=?");
                $stmt_check->execute([$db['id'], $buy]);
                $has_item = $stmt_check->fetch(PDO::FETCH_ASSOC);
                if($has_item) {
                        echo "<script>self.location='?p=shop&category=".$page."&msg=8'</script>";
                        exit;
                }

                // Process purchase
                $conexao->beginTransaction();

                // Deduct money
                $stmt_money = $conexao->prepare("UPDATE usuarios SET yens = yens - ? WHERE id=?");
                $stmt_money->execute([$valor, $db['id']]);

                // Add item to inventory (personagens são liberados automaticamente — não chegam aqui)
                $stmt_item = $conexao->prepare("INSERT INTO inventario (usuarioid, itemid, status, upgrade, venda) VALUES (?, ?, 'off', 0, 'nao')");
                $stmt_item->execute([$db['id'], $buy]);
                $msg = 2; // Item purchased

                $conexao->commit();
                echo "<script>self.location='?p=shop&category=".$page."&msg=".$msg."'</script>";
                exit;

        } catch (PDOException $e) {
                if($conexao->inTransaction()) {
                        $conexao->rollback();
                }
                echo "<script>self.location='?p=shop&category=".$page."&msg=1'</script>";
                exit;
        }
}
?>
<div class="box_top">Comércio</div>
<div class="box_middle">Bem-vindo ao centro comercial da vila. Temos tudo que você precisa para sua jornada no mundo ninja! Selecione uma das categorias abaixo e boas compras!<?php if(date('Y-m-d H:i:s')<$db['vip']) echo '<br /><b>OBS: Os itens estão com 20% de desconto pela sua VIP.</b>'; ?><div class="sep"></div>
        <div style="background:url(_img/gradient.jpg) repeat-y;color:#FFFFAA;"><img src="_img/yens.png" width="14" height="14" /> <b>Meus Yens: <?php echo number_format($db['yens'],2,',','.'); ?> yens</b></div><div class="sep"></div>
        <?php
        if(isset($_GET['msg'])){
                switch($_GET['msg']){
                        case 1: $msg='Yens insuficientes para comprar este item.'; break;
                        case 2: $msg='Item comprado com sucesso! Visite seu <a href="?p=inventory">inventário</a> agora mesmo!'; break;
                        case 3: $msg='Taijutsu insuficiente para comprar este item.'; break;
                        case 4: $msg='Nível insuficiente para desbloquear este personagem.'; break;
                        case 5: $msg='Personagem desbloqueado!'; break;
                        case 6: $msg='Genjutsu insuficiente para comprar este item.'; break;
                        case 7: $msg='Taijutsu, Ninjutsu ou Genjutsu insuficiente para comprar este item.'; break;
                        case 8: $msg='Você já possui este item em seu inventário.'; break;
                }
        echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
        }
        ?>
    <div align="center"><a href="?p=shop&amp;category=weapons">Armas</a> | <a href="?p=shop&amp;category=armors">Vestimentas</a> | <a href="?p=shop&amp;category=boots">Calçados</a> | <a href="?p=shop&amp;category=masks">Máscaras</a> | <a href="?p=shop&amp;category=scrolls">Pergaminhos</a> | <a href="?p=shop&amp;category=gloves">Luvas</a> | <a href="?p=shop&amp;category=pants">Calças</a> | <a href="?p=shop&amp;category=characters">Personagens</a> | <a href="?p=shops">Comércio Interno</a></div>
    <div class="sep"></div>
    <?php
        if(!isset($_GET['category'])) require_once('shop_weapons.php'); else
    if(isset($_GET['category'])){
                switch($_GET['category']){
                        case 'weapons': require_once('shop_weapons.php'); break;
                        case 'armors': require_once('shop_armors.php'); break;
                        case 'characters': require_once('shop_characters.php'); break;
                        case 'boots': require_once('shop_boots.php'); break;
                        case 'masks': require_once('shop_masks.php'); break;
                        case 'scrolls': require_once('shop_scrolls.php'); break;
                        case 'gloves': require_once('shop_gloves.php'); break;
                        case 'pants': require_once('shop_pants.php'); break;
                }
        }
        ?>
</div>
<div class="box_bottom"></div>
</replit_final_file>