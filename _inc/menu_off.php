<div style="width:170px;">
<div class="box2_top">Navegação</div>
<div class="box2_middle">
<div style="padding: 5px;">
<?php
$current_page = isset($_GET['p']) ? $_GET['p'] : 'home';
$nav_links = array(
    'login' => 'Login',
    'terms' => 'Registrar', 
    'recover' => 'Nova Senha',
    'manual' => 'Manual',
    'contact' => 'Contato',
    'mercado' => 'Mercado',
    'banidos' => 'Banidos',
    'faq' => 'FAQ'
);

foreach($nav_links as $page => $title) {
    $is_current = ($current_page == $page);
    $icon = $is_current ? 'online.png' : 'offline.png';
    $style = $is_current ? 'color: #00ff00; font-weight: bold; text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;' : 'color: #ffff00; text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;';

    echo '<div style="margin: 2px 0; padding: 2px; border-bottom: 1px solid #333;">';
    echo '<img src="_img/'.$icon.'" style="vertical-align: middle; margin-right: 5px;" />';
    echo '<a href="?p='.$page.'" style="'.$style.' text-decoration: none; font-size: 11px;">'.$title.'</a>';
    echo '</div>';
}
?>
</div>
</div>
<div class="box2_bottom"></div>

</div>