<?php
require_once(__DIR__ . '/personagens_catalogo.php');

$catalogo = personagens_catalogo();
$linha    = personagens_carregar($conexao, (int)$db['id']);
$eh_vip   = personagem_jogador_eh_vip($db);
?>

<table width="100%" cellpadding="0" cellspacing="1">
<?php foreach($catalogo as $chave => $info):
        $valor = (int)($info['valor'] ?? 0);
        $nivel_req = (int)$info['nivel'];
        $vip_only  = !empty($info['vip']);

        $is_current = ($db['personagem'] === $chave);
        $unlocked   = !empty($linha) && isset($linha[$chave]) && (int)$linha[$chave] === 1;

        // Personagens não-VIP são liberados automaticamente por nível;
        // os VIP exigem assinatura ativa. Aqui mostramos apenas o status.
?>
        <tr><td colspan="3"><div class="sep"></div></td></tr>
        <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
                <td align="center" width="140">
                        <?php if($unlocked): ?>
                                <img src="_img/personagens/reg_<?php echo htmlspecialchars($chave); ?>.jpg" />
                        <?php else: ?>
                                <img src="_img/personagens/unlock_<?php echo htmlspecialchars($chave); ?>.jpg" />
                        <?php endif; ?>
                </td>
                <td style="padding:5px;">
                        <b><?php echo htmlspecialchars($info['nome']); ?></b>
                        <?php if($vip_only): ?> <span style="color:#ffc14d;">[VIP]</span><?php endif; ?>
                        <br />
                        <span class="sub2"><?php echo htmlspecialchars($info['descricao']); ?></span>
                </td>
                <td align="center" width="30%">
                        <b>Nível Necessário</b><br />
                        <span class="sub2"><?php echo $nivel_req; ?></span><br /><br />
                        <?php if($is_current): ?>
                                <span class="sub2" style="color:#9f9;">Personagem atual</span>
                        <?php elseif($unlocked): ?>
                                <span class="sub2" style="color:#9f9;">Desbloqueado — selecione em Configurações</span>
                        <?php elseif($vip_only && !$eh_vip): ?>
                                <span class="sub2" style="color:#ffc14d;">Exclusivo para VIP ativo</span>
                        <?php elseif((int)$db['nivel'] < $nivel_req): ?>
                                <span class="sub2">Nível insuficiente — faltam <?php echo ($nivel_req - (int)$db['nivel']); ?> nível(is)</span>
                        <?php else: ?>
                                <span class="sub2" style="color:#9f9;">Será desbloqueado em breve.</span>
                        <?php endif; ?>
                </td>
        </tr>
<?php endforeach; ?>
</table>

<div class="sep"></div>
<div class="aviso" style="font-size:11px;">
        <b>Como funciona:</b> personagens são <b>desbloqueados automaticamente</b> ao subir de nível.
        Os marcados com <span style="color:#ffc14d;">[VIP]</span> exigem assinatura VIP ativa no momento do desbloqueio.
        Após desbloquear, escolha o personagem em <b>Configurações &raquo; Personagem</b>.
</div>
