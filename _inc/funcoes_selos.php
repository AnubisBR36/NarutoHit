<?php

function aplicarBonusSelo($usuario) {
    if(!isset($usuario['selo_tipo']) || $usuario['selo_tipo'] == 0 || $usuario['selo_nivel'] == 0) {
        return [
            'taijutsu_mult' => 1.0,
            'ninjutsu_mult' => 1.0,
            'genjutsu_mult' => 1.0,
            'hp_mult' => 1.0,
            'energia_mult' => 1.0,
            'critico_bonus' => 0,
            'esquiva_bonus' => 0,
            'defesa_bonus' => 0,
            'hp_por_turno' => 0,
            'confusao_chance' => 0
        ];
    }
    
    $selo_tipo = $usuario['selo_tipo'];
    $selo_nivel = $usuario['selo_nivel'];
    
    $bonus = [
        'taijutsu_mult' => 1.0,
        'ninjutsu_mult' => 1.0,
        'genjutsu_mult' => 1.0,
        'hp_mult' => 1.0,
        'energia_mult' => 1.0,
        'critico_bonus' => 0,
        'esquiva_bonus' => 0,
        'defesa_bonus' => 0,
        'hp_por_turno' => 0,
        'confusao_chance' => 0
    ];
    
    switch($selo_tipo) {
        case 1:
            switch($selo_nivel) {
                case 1:
                    $bonus['ninjutsu_mult'] = 1.15;
                    $bonus['taijutsu_mult'] = 0.95;
                    break;
                case 2:
                    $bonus['ninjutsu_mult'] = 1.30;
                    $bonus['taijutsu_mult'] = 0.90;
                    $bonus['critico_bonus'] = 5;
                    break;
                case 3:
                    $bonus['ninjutsu_mult'] = 1.50;
                    $bonus['taijutsu_mult'] = 0.85;
                    $bonus['critico_bonus'] = 10;
                    $bonus['hp_por_turno'] = -2;
                    break;
            }
            break;
            
        case 2:
            switch($selo_nivel) {
                case 1:
                    $bonus['taijutsu_mult'] = 1.15;
                    $bonus['ninjutsu_mult'] = 0.95;
                    break;
                case 2:
                    $bonus['taijutsu_mult'] = 1.30;
                    $bonus['ninjutsu_mult'] = 0.90;
                    $bonus['defesa_bonus'] = 5;
                    break;
                case 3:
                    $bonus['taijutsu_mult'] = 1.50;
                    $bonus['ninjutsu_mult'] = 0.85;
                    $bonus['defesa_bonus'] = 10;
                    $bonus['energia_mult'] = 1.15;
                    break;
            }
            break;
            
        case 3:
            switch($selo_nivel) {
                case 1:
                    $bonus['genjutsu_mult'] = 1.20;
                    $bonus['esquiva_bonus'] = 3;
                    break;
                case 2:
                    $bonus['genjutsu_mult'] = 1.40;
                    $bonus['esquiva_bonus'] = 6;
                    $bonus['confusao_chance'] = 10;
                    break;
                case 3:
                    $bonus['genjutsu_mult'] = 1.60;
                    $bonus['esquiva_bonus'] = 10;
                    $bonus['confusao_chance'] = 15;
                    $bonus['genjutsu_mult'] = 1.10;
                    break;
            }
            break;
            
        case 4:
            switch($selo_nivel) {
                case 1:
                    $bonus['taijutsu_mult'] = 1.10;
                    $bonus['ninjutsu_mult'] = 1.10;
                    $bonus['genjutsu_mult'] = 1.10;
                    break;
                case 2:
                    $bonus['taijutsu_mult'] = 1.20;
                    $bonus['ninjutsu_mult'] = 1.20;
                    $bonus['genjutsu_mult'] = 1.20;
                    $bonus['hp_mult'] = 1.05;
                    break;
                case 3:
                    $bonus['taijutsu_mult'] = 1.35;
                    $bonus['ninjutsu_mult'] = 1.35;
                    $bonus['genjutsu_mult'] = 1.35;
                    $bonus['hp_mult'] = 1.10;
                    break;
            }
            break;
    }
    
    return $bonus;
}

function aplicarAtributosSelo(&$atributos, $usuario) {
    $bonus = aplicarBonusSelo($usuario);
    
    if(isset($atributos['taijutsu'])) {
        $atributos['taijutsu'] = round($atributos['taijutsu'] * $bonus['taijutsu_mult']);
    }
    
    if(isset($atributos['ninjutsu'])) {
        $atributos['ninjutsu'] = round($atributos['ninjutsu'] * $bonus['ninjutsu_mult']);
    }
    
    if(isset($atributos['genjutsu'])) {
        $atributos['genjutsu'] = round($atributos['genjutsu'] * $bonus['genjutsu_mult']);
    }
    
    if(isset($atributos['energiamax'])) {
        $energia_original = $atributos['energiamax'];
        $atributos['energiamax'] = round($atributos['energiamax'] * $bonus['hp_mult']);
        if(isset($atributos['energia'])) {
            $proporcao = $atributos['energia'] / max(1, $energia_original);
            $atributos['energia'] = round($atributos['energiamax'] * $proporcao);
        }
    }
    
    $atributos['selo_bonus'] = $bonus;
    
    return $atributos;
}

function aplicarEfeitosPorTurno(&$hp_atual, $hp_max, $usuario) {
    if(!isset($usuario['selo_tipo']) || $usuario['selo_tipo'] == 0 || $usuario['selo_nivel'] == 0) {
        return $hp_atual;
    }
    
    $bonus = aplicarBonusSelo($usuario);
    
    if($bonus['hp_por_turno'] < 0) {
        $dano = abs($hp_max * ($bonus['hp_por_turno'] / 100));
        $hp_atual = max(1, $hp_atual - $dano);
    }
    
    return $hp_atual;
}

function calcularCritico($base_chance, $usuario) {
    $bonus = aplicarBonusSelo($usuario);
    return $base_chance + $bonus['critico_bonus'];
}

function calcularEsquiva($base_chance, $usuario) {
    $bonus = aplicarBonusSelo($usuario);
    return $base_chance + $bonus['esquiva_bonus'];
}

function calcularDefesaAdicional($base_defesa, $usuario) {
    $bonus = aplicarBonusSelo($usuario);
    return $base_defesa * (1 + ($bonus['defesa_bonus'] / 100));
}

function calcularCustoEnergia($custo_base, $usuario) {
    $bonus = aplicarBonusSelo($usuario);
    return round($custo_base * $bonus['energia_mult']);
}

function verificarConfusao($usuario) {
    $bonus = aplicarBonusSelo($usuario);
    if($bonus['confusao_chance'] > 0) {
        return (rand(1, 100) <= $bonus['confusao_chance']);
    }
    return false;
}

function getNomeSelo($tipo) {
    $nomes = [
        0 => 'Nenhum',
        1 => 'Selo do Céu',
        2 => 'Selo da Terra',
        3 => 'Selo da Lua',
        4 => 'Selo do Sol'
    ];
    return isset($nomes[$tipo]) ? $nomes[$tipo] : 'Desconhecido';
}

function getImagemSelo($tipo, $nivel) {
    if($tipo == 0 || $nivel == 0) return null;
    
    $pastas = [
        1 => 'Ceú',
        2 => 'terra',
        3 => 'Lua',
        4 => 'sol'
    ];
    
    if(isset($pastas[$tipo])) {
        return '_img/Selos/'.$pastas[$tipo].'/'.$nivel.'.gif';
    }
    
    return null;
}

?>
