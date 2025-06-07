
<script>
function confirmarMissao(rank, form) {
    var select = form.querySelector('select[name="mis_tempo"]');
    var tempo = select.options[select.selectedIndex].text;
    var imagemRank = rank.toLowerCase();
    
    // Calcular recompensas baseadas no rank e tempo
    var yensPorHora = 0;
    var horasNumero = parseInt(select.options[select.selectedIndex].text);
    
    switch(rank) {
        case 'S': yensPorHora = 3000; break;
        case 'A': yensPorHora = 1800; break;
        case 'B': yensPorHora = 1000; break;
        case 'C': yensPorHora = 550; break;
        case 'D': yensPorHora = 250; break;
    }
    
    var yensTotal = yensPorHora * horasNumero;
    var expTotal = horasNumero; // 1 exp por hora
    
    // Remove modal existente se houver
    var modalExistente = document.getElementById('modalConfirmacao');
    if (modalExistente) {
        modalExistente.remove();
    }
    
    // Criar o modal
    var modal = document.createElement('div');
    modal.id = 'modalConfirmacao';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
    `;
    
    modal.innerHTML = `
        <div style="background: url('_img/box/box_middle.jpg'); 
                    width: 450px; 
                    border: 3px solid #8B4513;
                    border-radius: 10px;
                    text-align: center;
                    position: relative;">
            <div style="background: url('_img/box/box_top.jpg'); 
                        height: 30px; 
                        color: white; 
                        font-weight: bold; 
                        padding-top: 8px;
                        border-radius: 7px 7px 0 0;">
                Confirmação de Missão
            </div>
            <div style="padding: 20px; color: #8B0000;">
                <img src="_img/missoes/rank${imagemRank}.jpg" alt="Rank ${rank}" style="width: 80px; height: 80px; margin-bottom: 15px;" />
                <br />
                <b style="font-size: 16px; color: #8B0000; text-shadow: 1px 1px 0px #000, -1px -1px 0px #000, 1px -1px 0px #000, -1px 1px 0px #000;">Você está prestes a fazer uma<br />Missão Rank ${rank}</b>
                <br /><br />
                <span style="font-size: 14px; color: #8B0000; text-shadow: 1px 1px 0px #000, -1px -1px 0px #000, 1px -1px 0px #000, -1px 1px 0px #000;">Durante: <b>${tempo}</b></span>
                <br /><br />
                <div style="background: rgba(0,0,0,0.3); padding: 10px; border-radius: 5px; margin: 10px 0;">
                    <span style="font-size: 14px; color: #FFD700; text-shadow: 1px 1px 0px #000, -1px -1px 0px #000, 1px -1px 0px #000, -1px 1px 0px #000;"><b>Recompensas ao concluir:</b></span><br />
                    <span style="font-size: 13px; color: #90EE90; text-shadow: 1px 1px 0px #000, -1px -1px 0px #000, 1px -1px 0px #000, -1px 1px 0px #000;">💰 ${yensTotal.toLocaleString()} Yens</span><br />
                    <span style="font-size: 13px; color: #87CEEB; text-shadow: 1px 1px 0px #000, -1px -1px 0px #000, 1px -1px 0px #000, -1px 1px 0px #000;">⭐ ${expTotal} Experiência</span>
                </div>
                <div style="margin-top: 15px;">
                    <input type="button" 
                           onclick="confirmarEExecutar()" 
                           value="OK" 
                           style="background: url('_img/fundo_botao.jpg'); 
                                  border: 2px solid #654321; 
                                  color: white; 
                                  font-weight: bold; 
                                  padding: 8px 20px; 
                                  margin: 0 10px; 
                                  cursor: pointer;
                                  border-radius: 5px;" />
                    <input type="button" 
                           onclick="fecharModal()" 
                           value="Cancelar" 
                           style="background: url('_img/fundo_botao.jpg'); 
                                  border: 2px solid #654321; 
                                  color: white; 
                                  font-weight: bold; 
                                  padding: 8px 20px; 
                                  margin: 0 10px; 
                                  cursor: pointer;
                                  border-radius: 5px;" />
                </div>
            </div>
            <div style="background: url('_img/box/box_bottom.jpg'); 
                        height: 15px; 
                        border-radius: 0 0 7px 7px;">
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Função para confirmar e executar
    window.confirmarEExecutar = function() {
        var submitBtn = form.querySelector('input[type="submit"]');
        submitBtn.value = 'Carregando...';
        submitBtn.disabled = true;
        fecharModal();
        form.submit();
    };
    
    // Função para fechar modal
    window.fecharModal = function() {
        var modal = document.getElementById('modalConfirmacao');
        if (modal) {
            modal.remove();
        }
    };
    
    // Fechar modal ao clicar fora
    modal.onclick = function(e) {
        if (e.target === modal) {
            fecharModal();
        }
    };
    
    return false; // Impede o submit padrão
}
</script>

<div align="center">
<table width="100%" cellpadding="0" cellspacing="1">
<?php if($db['nivel']>=60){ ?>
<tr>
	<td colspan="3"><div class="sep"></div></td>
</tr>
<tr class="table_dados" style="background:#323232">
    <td><img src="_img/missoes/ranks.jpg" /></td>
    <td><b>Rank S</b><br /><span class="sub2">3.000,00 yens<br />por hora</span></td>
    <td>
    <form method="post" id="missao_s" name="missao_s" action="?p=missions" onsubmit="return confirmarMissao('S', this);">
    <input type="hidden" id="mis_rank" name="mis_rank" value="<?php echo $c->encode('S',$chaveuniversal); ?>">
    <select id="mis_tempo_s" name="mis_tempo">
    	<?php $i=1; do{ ?>
    	<option value="<?php echo $c->encode($i,$chaveuniversal); ?>"><?php echo $i; ?> hora<?php if($i>1) echo 's'; ?></option>
        <?php $i++; } while($i<25); ?>
    </select>
    <br /><span class="sub2">Selecione a quantidade de horas</span>
    <input type="submit" id="subm_s" name="subm" class="botao" value="Escolher">
    </form>    </td>
</tr>
<?php } ?>
<?php if($db['nivel']>=40){ ?>
<tr>
	<td colspan="3"><div class="sep"></div></td>
</tr>
<tr class="table_dados" style="background:#323232">
    <td><img src="_img/missoes/ranka.jpg" /></td>
    <td><b>Rank A</b><br /><span class="sub2">1.800,00 yens<br />por hora</span></td>
    <td>
    <form method="post" id="missao_a" name="missao_a" action="?p=missions" onsubmit="return confirmarMissao('A', this);">
    <input type="hidden" id="mis_rank" name="mis_rank" value="<?php echo $c->encode('A',$chaveuniversal); ?>">
    <select id="mis_tempo_a" name="mis_tempo">
    	<?php $i=1; do{ ?>
    	<option value="<?php echo $c->encode($i,$chaveuniversal); ?>"><?php echo $i; ?> hora<?php if($i>1) echo 's'; ?></option>
        <?php $i++; } while($i<25); ?>
    </select>
    <br /><span class="sub2">Selecione a quantidade de horas</span>
    <input type="submit" id="subm_a" name="subm" class="botao" value="Escolher">
    </form>    </td>
</tr>
<?php } ?>
<?php if($db['nivel']>=20){ ?>
<tr>
	<td colspan="3"><div class="sep"></div></td>
</tr>
<tr class="table_dados" style="background:#323232">
    <td><img src="_img/missoes/rankb.jpg" /></td>
    <td><b>Rank B</b><br /><span class="sub2">1.000,00 yens<br />por hora</span></td>
    <td>
    <form method="post" id="missao_b" name="missao_b" action="?p=missions" onsubmit="return confirmarMissao('B', this);">
    <input type="hidden" id="mis_rank" name="mis_rank" value="<?php echo $c->encode('B',$chaveuniversal); ?>">
    <select id="mis_tempo_b" name="mis_tempo">
    	<?php $i=1; do{ ?>
    	<option value="<?php echo $c->encode($i,$chaveuniversal); ?>"><?php echo $i; ?> hora<?php if($i>1) echo 's'; ?></option>
        <?php $i++; } while($i<25); ?>
    </select>
    <br /><span class="sub2">Selecione a quantidade de horas</span>
    <input type="submit" id="subm_b" name="subm" class="botao" value="Escolher">
    </form>    </td>
</tr>
<?php } ?>
<?php if($db['nivel']>=5){ ?>
<tr>
	<td colspan="3"><div class="sep"></div></td>
</tr>
<tr class="table_dados" style="background:#323232">
    <td><img src="_img/missoes/rankc.jpg" /></td>
    <td><b>Rank C</b><br /><span class="sub2">550,00 yens<br />por hora</span></td>
    <td>
    <form method="post" id="missao_c" name="missao_c" action="?p=missions" onsubmit="return confirmarMissao('C', this);">
    <input type="hidden" id="mis_rank" name="mis_rank" value="<?php echo $c->encode('C',$chaveuniversal); ?>">
    <select id="mis_tempo_c" name="mis_tempo">
    	<?php $i=1; do{ ?>
    	<option value="<?php echo $c->encode($i,$chaveuniversal); ?>"><?php echo $i; ?> hora<?php if($i>1) echo 's'; ?></option>
        <?php $i++; } while($i<25); ?>
    </select>
    <br /><span class="sub2">Selecione a quantidade de horas</span>
    <input type="submit" id="subm_c" name="subm" class="botao" value="Escolher">
    </form>    </td>
</tr>
<?php } ?>
<tr>
	<td colspan="3"><div class="sep"></div></td>
</tr>
<tr class="table_dados" style="background:#323232">
    <td><img src="_img/missoes/rankd.jpg" /></td>
    <td><b>Rank D</b><br /><span class="sub2">250,00 yens<br />por hora</span></td>
    <td>
    <form method="post" id="missao_d" name="missao_d" action="?p=missions" onsubmit="return confirmarMissao('D', this);">
    <input type="hidden" id="mis_rank" name="mis_rank" value="<?php echo $c->encode('D',$chaveuniversal); ?>">
    <select id="mis_tempo_d" name="mis_tempo">
    	<?php $i=1; do{ ?>
    	<option value="<?php echo $c->encode($i,$chaveuniversal); ?>"><?php echo $i; ?> hora<?php if($i>1) echo 's'; ?></option>
        <?php $i++; } while($i<25); ?>
    </select>
    <br /><span class="sub2">Selecione a quantidade de horas</span>
    <input type="submit" id="subm_d" name="subm" class="botao" value="Escolher">
    </form>    </td>
</tr>
</table>
</div>