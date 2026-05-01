<?php
// Pré-carregar mapa nome → id de todos os jutsus
$jutsu_mapa = [];
try {
    $stmt_jutsus = $conexao->query("SELECT id, nome FROM table_jutsus");
    while ($row = $stmt_jutsus->fetch(PDO::FETCH_ASSOC)) {
        $jutsu_mapa[$row['nome']] = $row['id'];
    }
} catch (PDOException $e) {}

// Função que determina o ícone com base no texto da atualização
function icone_atualizacao($texto, $jutsu_mapa) {
    // Jutsu aprendido: "aprendeu <b>Nome</b>"
    if (preg_match('/aprendeu <b>(.+?)<\/b>/i', $texto, $m)) {
        $nome_jutsu = $m[1];
        if (isset($jutsu_mapa[$nome_jutsu])) {
            return '_img/jutsus/' . $jutsu_mapa[$nome_jutsu] . '.jpg';
        }
        return '_img/jutsus/1.jpg';
    }
    // Selos Amaldiçoados
    if (preg_match('/Selo.*?C[eé]u/ui', $texto))   return '_img/Selos/Ce' . "\xc3\xba" . '/1.gif';
    if (preg_match('/Selo.*?Terra/i', $texto))       return '_img/Selos/terra/1.gif';
    if (preg_match('/Selo.*?Lua/i', $texto))         return '_img/Selos/Lua/1.gif';
    if (preg_match('/Selo.*?Sol/i', $texto))         return '_img/Selos/sol/1.gif';
    if (preg_match('/Selo/i', $texto))               return '_img/Selos/terra/1.gif';
    // Subiu de nível
    if (preg_match('/N[ií]vel/i', $texto))           return '_img/rank/1.png';
    // Akatsuki
    if (preg_match('/Akatsuki/i', $texto))           return '_img/an.png';
    // Padrão
    return '_img/refresh.png';
}

$sqltexto = "SELECT texto,hora FROM atualizacoes WHERE usuarioid=?";
$sqltexto .= $sqlupdate;
$sqltexto .= " ORDER BY id DESC LIMIT 10";
try {
    $stmt = $conexao->prepare($sqltexto);
    $stmt->execute([$db['id']]);
    $dbu = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dbu = false;
}
require_once('formatar_tempo.php');
?>
<div class="box_top">Atualizações</div>
<div class="box_middle">Últimos 10 comandos realizados por você e seus amigos. Na página de configuração, você pode definir as permissões de suas atualizações (os ninjas que conseguirão visualizar estas informações).<div class="sep"></div>
    <table width="100%" cellpadding="0" cellspacing="0">
    <?php if(!$dbu): ?>
        <tr><td colspan="3"><div class="aviso">Nenhuma atualização encontrada!</div><div class="sep"></div></td></tr>
    <?php else: do { $icone = icone_atualizacao($dbu['texto'], $jutsu_mapa); ?>
    <tr class="tabela_dados" style="background:url(_img/gradient.jpg) repeat-y;">
        <td style="background:#282828;text-align:center;width:38px;" valign="middle">
            <img src="<?php echo htmlspecialchars($icone); ?>" style="width:32px;height:32px;object-fit:cover;display:block;margin:auto;border-radius:4px;" />
        </td>
        <td style="padding-left:4px;padding-right:5px;"><?php echo $dbu['texto']; ?></td>
        <td style="text-align:center;" width="25%" valign="top">
            <?php echo formatar_tempo($dbu['hora']); ?>
        </td>
    </tr>
    <tr><td colspan="3"><div class="sep"></div></td></tr>
    <?php } while($dbu = $stmt->fetch(PDO::FETCH_ASSOC)); endif; ?>
    <tr>
        <td colspan="3" align="center"><input type="button" class="botao" value="Mais Atualizações" onclick="location.href='?p=updates'" /></td>
    </tr>
    </table>
</div>
<div class="box_bottom"></div>
