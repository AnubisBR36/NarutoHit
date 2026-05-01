<?php
class ProvablyFair {
    
    public static function generateServerSeed() {
        return bin2hex(random_bytes(32));
    }
    
    public static function hashServerSeed($serverSeed) {
        return hash('sha256', $serverSeed);
    }
    
    public static function generateCombinedHash($serverSeed, $clientSeed, $nonce) {
        $combined = $serverSeed . ':' . $clientSeed . ':' . $nonce;
        return hash('sha256', $combined);
    }
    
    public static function hashToNumber($hash, $min = 0, $max = 99) {
        $hexPart = substr($hash, 0, 8);
        $decimal = hexdec($hexPart);
        $range = $max - $min + 1;
        return $min + ($decimal % $range);
    }
    
    public static function roll($serverSeed, $clientSeed, $nonce) {
        $hash = self::generateCombinedHash($serverSeed, $clientSeed, $nonce);
        $number = self::hashToNumber($hash);
        
        return [
            'hash' => $hash,
            'number' => $number
        ];
    }
    
    public static function isSuccess($number, $chancePercent) {
        return $number < $chancePercent;
    }
    
    public static function verify($serverSeed, $clientSeed, $nonce, $expectedHash) {
        $hash = self::generateCombinedHash($serverSeed, $clientSeed, $nonce);
        return $hash === $expectedHash;
    }
    
    public static function getCommittedSeed($conexao, $userId) {
        $stmt = $conexao->prepare("SELECT id, server_seed, nonce_counter FROM provably_fair_seeds WHERE usuario_id = ? AND usado = 0 AND hash_revealed = 1 ORDER BY id DESC LIMIT 1");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result;
        }
        
        return null;
    }
    
    public static function getNextNonce($conexao, $seedId) {
        $stmt = $conexao->prepare("SELECT nonce_counter FROM provably_fair_seeds WHERE id = ?");
        $stmt->execute([$seedId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $nonce = $result ? intval($result['nonce_counter']) : 0;
        
        $stmt = $conexao->prepare("UPDATE provably_fair_seeds SET nonce_counter = nonce_counter + 1 WHERE id = ?");
        $stmt->execute([$seedId]);
        
        return $nonce;
    }
    
    public static function markSeedAsUsed($conexao, $seedId) {
        $stmt = $conexao->prepare("UPDATE provably_fair_seeds SET usado = 1, usado_em = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$seedId]);
    }
    
    public static function logRoll($conexao, $userId, $equipmentId, $serverSeed, $clientSeed, $nonce, $hash, $number, $chance, $success) {
        $stmt = $conexao->prepare("
            INSERT INTO provably_fair_logs 
            (usuario_id, equipment_id, server_seed, client_seed, nonce, hash, numero_gerado, chance_percent, sucesso, criado_em) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$userId, $equipmentId, $serverSeed, $clientSeed, $nonce, $hash, $number, $chance, $success ? 1 : 0]);
        
        return $conexao->lastInsertId();
    }
    
    public static function getHashedServerSeed($conexao, $userId) {
        $stmt = $conexao->prepare("SELECT id, server_seed_hash FROM provably_fair_seeds WHERE usuario_id = ? AND usado = 0 ORDER BY id DESC LIMIT 1");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $stmt = $conexao->prepare("UPDATE provably_fair_seeds SET hash_revealed = 1 WHERE id = ?");
            $stmt->execute([$result['id']]);
            return $result['server_seed_hash'];
        }
        
        $newSeed = self::generateServerSeed();
        $hashedSeed = self::hashServerSeed($newSeed);
        
        $stmt = $conexao->prepare("INSERT INTO provably_fair_seeds (usuario_id, server_seed, server_seed_hash, hash_revealed, nonce_counter, criado_em) VALUES (?, ?, ?, 1, 0, CURRENT_TIMESTAMP)");
        $stmt->execute([$userId, $newSeed, $hashedSeed]);
        
        return $hashedSeed;
    }
    
    public static function createNewSeedForUser($conexao, $userId) {
        $newSeed = self::generateServerSeed();
        $hashedSeed = self::hashServerSeed($newSeed);
        
        $stmt = $conexao->prepare("INSERT INTO provably_fair_seeds (usuario_id, server_seed, server_seed_hash, hash_revealed, nonce_counter, criado_em) VALUES (?, ?, ?, 0, 0, CURRENT_TIMESTAMP)");
        $stmt->execute([$userId, $newSeed, $hashedSeed]);
        
        return $hashedSeed;
    }
    
    public static function createTablesIfNeeded($conexao) {
        try {
            $pk = Database::autoIncPK('id');
            $conexao->exec("
                CREATE TABLE IF NOT EXISTS provably_fair_seeds (
                    $pk,
                    usuario_id INTEGER NOT NULL,
                    server_seed TEXT NOT NULL,
                    server_seed_hash TEXT NOT NULL,
                    hash_revealed INTEGER DEFAULT 0,
                    nonce_counter INTEGER DEFAULT 0,
                    usado INTEGER DEFAULT 0,
                    criado_em TEXT,
                    usado_em TEXT
                )
            ");

            $conexao->exec("
                CREATE TABLE IF NOT EXISTS provably_fair_logs (
                    $pk,
                    usuario_id INTEGER NOT NULL,
                    equipment_id INTEGER,
                    server_seed TEXT NOT NULL,
                    client_seed TEXT NOT NULL,
                    nonce TEXT NOT NULL,
                    hash TEXT NOT NULL,
                    numero_gerado INTEGER NOT NULL,
                    chance_percent INTEGER NOT NULL,
                    sucesso INTEGER NOT NULL,
                    criado_em TEXT
                )
            ");
        } catch (PDOException $e) {
            // Tabelas já existem ou foram criadas pelo instalador.
        }
    }
}
