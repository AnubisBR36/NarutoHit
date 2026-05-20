<?php if($db['orgid']>0){ echo "<script>self.location='?p=myorg'</script>"; return; } ?>
<?php if($db['nivel']<1){ ?>
<div class="box_top">Criar Organização</div>
<div class="box_middle"><div class="aviso">Seu nível é muito baixo para ser líder de uma organização.<br />Volte quando estiver no nível 5.</div></div>
<div class="box_bottom"></div>
<?php } else { ?>
<?php
        if(isset($_POST['org'])){
                if(!csrf_validar()) { echo "<script>self.location='?p=createorg'</script>"; exit; }
                $nome = trim($_POST['org_nome']);
                $sigla = substr(strtoupper(trim($_POST['org_sigla'])), 0, 4);
                
                // Validações básicas
                if(empty($nome) || empty($sigla)) {
                        echo "<div class='aviso'>Nome e sigla são obrigatórios!</div>";
                } else {
                        try {
                                // Verificar se o usuário já não tem clã
                                if($db['orgid'] > 0) {
                                        echo "<script>self.location='?p=myorg'</script>";
                                        exit;
                                }
                                
                                // Verificar se já existe clã com a mesma sigla no mesmo servidor
                                $stmt = $conexao->prepare("SELECT id FROM organizacoes WHERE sigla = ? AND servidor_id = ?");
                                $stmt->execute([$sigla, (int)($_SESSION['servidor_id'] ?? 1)]);
                                if($stmt->fetch()) {
                                        echo "<div class='aviso'>Já existe um clã com essa sigla neste servidor!</div>";
                                } else {
                                        // Inserir organização
                                        $stmt = $conexao->prepare("INSERT INTO organizacoes (vila, nome, sigla, data, liderid, nivel, exp, expmax, servidor_id) VALUES (?, ?, ?, ?, ?, 1, 0, 10, ?)");
                                        $stmt->execute([$db['vila'], $nome, $sigla, date('Y-m-d H:i:s'), $db['id'], (int)($_SESSION['servidor_id'] ?? 1)]);
                                        $orgid = $conexao->lastInsertId();
                                        
                                        // Inserir membro líder na tabela membros
                                        $stmt = $conexao->prepare("INSERT INTO membros (orgid, organizacao_id, usuario_id, usuarioid, posicao, rank, doado, status, missoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                        $stmt->execute([$orgid, $orgid, $db['id'], $db['id'], 1, 'Líder', 0, 'sim', 0]);
                                        
                                        // Atualizar usuário
                                        $stmt = $conexao->prepare("UPDATE usuarios SET orgid = ? WHERE id = ?");
                                        $stmt->execute([$orgid, $db['id']]);
                                        
                                        echo "<script>self.location='?p=myorg'</script>";
                                }
                        } catch (PDOException $e) {
                                echo "<div class='aviso'>Erro ao criar organização: " . htmlspecialchars($e->getMessage()) . "</div>";
                        }
                }
        }
?>
<div class="box_top">Criar Organização</div>
<div class="box_middle">Para criar uma organização, primeiramente é necessário ter espírito de liderança. Um bom líder conseguirá administrar uma boa organização, e assim, conquistar os melhores ninjas! Além disso, também é preciso ter uma boa reserva de yens! Os custos para a criação são de 3.000,00 yens.<div class="sep"></div><div style="padding-left:5px;background:url(_img/gradient.jpg) repeat-y;font-weight:bold;color:#FFFFAA;"><img src="_img/yens.png" width="14" height="14" align="absmiddle" /> Meus yens: <?php echo number_format($db['yens'],2,',','.'); ?> yens</div><div class="sep"></div>
        <form method="post" action="?p=createorg" onsubmit="subm.value='Carregando...';subm.disabled=true;">
    <?php echo csrf_field(); ?>
    <input type="hidden" id="org" name="org" value="1">
    <fieldset><legend>Criar Organização</legend>
        <span class="destaque">Nome da Organização:</span><br />
        <input type="text" id="org_nome" name="org_nome"><br />
        <span class="sub2">Digite o nome da nova organização.</span><br /><br />
        <span class="destaque">Sigla:</span><br />
        <input type="text" id="org_sigla" name="org_sigla" maxlength="4" size="6"><br />
        <span class="sub2">Digite uma sigla de até 4 caracteres para a organização.</span>
        <div class="sep"></div>
        <div align="center"><input type="submit" id="subm" name="subm" class="botao" value="Criar Organização" /></div>
    </fieldset>
    </form>
</div>
<div class="box_bottom"></div>
<?php } ?>