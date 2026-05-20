<?php
/**
 * funcoes_despertar.php
 * Helper para verificar e enviar notificação automática quando o jogador
 * atingir todos os requisitos do Ritual de Despertar da Linhagem.
 */

if(!function_exists('despertar_verificar_notificacao')){
    function despertar_verificar_notificacao($conexao, $usuario_id){
        try {
            // Buscar dados atualizados do jogador
            $stmt = $conexao->prepare("
                SELECT batalhas, vitorias, missoes_longas, doujutsu,
                       doujutsu_proxima_tentativa, despertar_notificado, nivel
                FROM usuarios WHERE id = ?
            ");
            $stmt->execute([$usuario_id]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);
            if(!$u) return;

            // Só elegíveis (nível 20, sem doujutsu, sem cooldown, notificação ainda não enviada)
            if((int)$u['nivel'] < 20) return;
            if((int)$u['doujutsu'] > 0) return;
            if((int)$u['despertar_notificado'] === 1) return;
            if(!empty($u['doujutsu_proxima_tentativa']) && strtotime($u['doujutsu_proxima_tentativa']) > time()) return;

            // Ler requisitos da tabela configuracoes
            $cfg = $conexao->query("
                SELECT nome, valor FROM configuracoes
                WHERE nome IN ('despertar_req_batalhas','despertar_req_vitorias','despertar_req_missoes')
            ")->fetchAll(PDO::FETCH_KEY_PAIR);

            $req_bat = max(1, (int)($cfg['despertar_req_batalhas'] ?? 100));
            $req_vit = max(1, (int)($cfg['despertar_req_vitorias'] ?? 10));
            $req_mis = max(1, (int)($cfg['despertar_req_missoes']  ?? 150));

            // Verificar se todos os requisitos foram atingidos
            if((int)$u['batalhas']       < $req_bat) return;
            if((int)$u['vitorias']       < $req_vit) return;
            if((int)$u['missoes_longas'] < $req_mis) return;

            // Enviar mensagem do sistema (origem=0)
            $assunto = 'O Ritual da Linhagem Aguarda por Você';
            $corpo   = 'Você provou seu valor em batalha e nas jornadas das missões.<br><br>'
                     . '<b style="color:#CC99FF;">O Ritual de Despertar da Linhagem está disponível.</b><br><br>'
                     . 'Todos os requisitos foram cumpridos:<br>'
                     . '✓ ' . $u['batalhas'] . ' batalhas realizadas<br>'
                     . '✓ ' . $u['vitorias'] . ' vitórias conquistadas<br>'
                     . '✓ ' . $u['missoes_longas'] . ' missões longas completadas<br><br>'
                     . 'Acesse <a href="?p=despertar" style="color:#9966CC;font-weight:bold;">Despertar da Linhagem</a> '
                     . 'e descubra se uma herança ancestral dorme em seu sangue.';

            $conexao->prepare("
                INSERT INTO mensagens (origem, destino, assunto, msg, data, status)
                VALUES (0, ?, ?, ?, CURRENT_TIMESTAMP, 'naolido')
            ")->execute([$usuario_id, $assunto, $corpo]);

            // Marcar como notificado para não enviar novamente
            $conexao->prepare("UPDATE usuarios SET despertar_notificado = 1 WHERE id = ?")
                ->execute([$usuario_id]);

        } catch(Exception $e){ /* silently fail — nunca interromper o fluxo do jogo */ }
    }
}

if(!function_exists('despertar_resetar_notificacao')){
    function despertar_resetar_notificacao($conexao, $usuario_id){
        try {
            $conexao->prepare("UPDATE usuarios SET despertar_notificado = 0 WHERE id = ?")
                ->execute([$usuario_id]);
        } catch(Exception $e){}
    }
}
