<?php
require_once('Encrypt.php');
require_once('conexao.php');

// Função para verificar se o jogador está na floresta
function isPlayerInForest($player_id) {
    global $conexao;
    try {
        $stmt = $conexao->prepare("SELECT floresta_mapa, floresta_principal_x, floresta_principal_y FROM usuarios WHERE id = ?");
        $stmt->execute([$player_id]);
        $location = $stmt->fetch(PDO::FETCH_ASSOC);

        return $location && ($location['floresta_mapa'] !== null && !empty($location['floresta_mapa']));
    } catch (PDOException $e) {
        return false;
    }
}
function fpersonagem($personagem){
	switch($personagem){
		case 'naruto': return 'Uzumaki Naruto'; break;
		case 'sakura': return 'Haruno Sakura'; break;
		case 'sasuke': return 'Uchiha Sasuke'; break;
		case 'kakashi': return 'Hatake Kakashi'; break;
		case 'konohamaru': return 'Sarutobi Konohamaru'; break;
		case 'iruka': return 'Umino Iruka'; break;
		case 'asuma': return 'Sarutobi Asuma'; break;
		case 'kurenai': return 'Yuhi Kurenai'; break;
		case 'gai': return 'Maito Gai'; break;
		case 'shikamaru': return 'Nara Shikamaru'; break;
		case 'ino': return 'Yamanaka Ino'; break;
		case 'chouji': return 'Akimichi Chouji'; break;
		case 'neji': return 'Hyuuga Neji'; break;
		case 'tenten': return 'TenTen'; break;
		case 'lee': return 'Rock Lee'; break;
		case 'hinata': return 'Hyuuga Hinata'; break;
		case 'shino': return 'Aburame Shino'; break;
		case 'kiba': return 'Inuzuka Kiba'; break;
		case 'gaara': return 'Sabaku no Gaara'; break;
		case 'temari': return 'Sabaku no Temari'; break;
		case 'kankurou': return 'Sabaku no Kankurou'; break;
		case 'kabuto': return 'Yakushi Kabuto'; break;
		case 'haku': return 'Haku'; break;
		case 'zabuza': return 'Momochi Zabuza'; break;
		case 'jiroubo': return 'Jiroubo'; break;
		case 'kidoumaru': return 'Kidoumaru'; break;
		case 'sakon': return 'Sakon'; break;
		case 'tayuya': return 'Tayuya'; break;
		case 'kimimaro': return 'Kaguya Kimimaro'; break;
		case 'sai': return 'Sai'; break;
		case 'hagane': return 'Hagane Kotetsu'; break;
		case 'hayate': return 'Gekkou Hayate'; break;
		default: return 'Desconhecido'; break;
	}
}

function getBandanaPath($bandana_estilo, $renegado, $vila) {
	$pasta_bandana = ($bandana_estilo == 'classico') ? '_img/vilas' : '_img/NewsVilas';

	if($renegado == 'sim') {
		if($bandana_estilo == 'classico') {
			// Para estilo clássico e renegado, usar akatsuki_[vila]
			$nomes_vila = array(1 => 'folha', 2 => 'areia', 3 => 'som', 4 => 'chuva', 5 => 'nuvem', 6 => 'nevoa', 8 => 'pedra');
			$nome_vila = isset($nomes_vila[$vila]) ? $nomes_vila[$vila] : 'folha';
			$imagem_bandana = 'akatsuki_' . $nome_vila;
		} else {
			// Para estilo moderno e renegado, usar apenas akatsuki
			$imagem_bandana = 'akatsuki';
		}
	} else {
		// Para não renegados, usar o nome da vila
		$nomes_vila = array(1 => 'folha', 2 => 'areia', 3 => 'som', 4 => 'chuva', 5 => 'nuvem', 6 => 'nevoa', 8 => 'pedra');
		$imagem_bandana = isset($nomes_vila[$vila]) ? $nomes_vila[$vila] : 'folha';
	}

	return $pasta_bandana . '/' . $imagem_bandana . '.jpg';
}
function rankNinja($nivel){
	if($nivel>60) $rk='ANBU';
	if($nivel<60) $rk='Sannin';
	if($nivel<40) $rk='Jounnin';
	if($nivel<20) $rk='Chuunin';
	if($nivel<5) $rk='Gennin';
	return $rk;
}
function dano($taijutsu1,$taijutsu2){
	if(($taijutsu2+10)<=$taijutsu1) $bonus=rand(1,5); else $bonus=rand(1,10);
	if($taijutsu1<($taijutsu2-25)) return 0; else
	return round(($taijutsu1*((($taijutsu1*5)/$taijutsu2)/4))/4)+$bonus;
}
function danojutsu($ninjutsu1,$ninjutsu2,$forca){
	if($ninjutsu1<($ninjutsu2-25)) return 0; else
	return round(($ninjutsu1*((($ninjutsu1*5)/$ninjutsu2)/4))/4)+$forca+rand(1,10);
}
function verificar_ip_duplicado($ip, $usuario_atual = '') {
    // Função desabilitada - múltiplas contas por IP são permitidas
    return false;
}

function is_top1_vila($usuario_id, $vila_id) {
    // Verifica se o usuário é o top 1 da sua vila
    global $conexao;

    try {
        $stmt = $conexao->prepare("SELECT id FROM usuarios WHERE vila = ? AND status != 'banido' ORDER BY nivel DESC, yens_fat DESC, vitorias DESC, derrotas ASC LIMIT 1");
        $stmt->execute([$vila_id]);
        $top1 = $stmt->fetch(PDO::FETCH_ASSOC);

        if($top1) {
            return $top1['id'] == $usuario_id;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Erro na função is_top1_vila: " . $e->getMessage());
        return false;
    }
}
?>