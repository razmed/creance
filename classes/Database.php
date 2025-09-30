<?php
/**
 * Classe Database - Gestion de la base de données avec support de la concurrence
 * Gestion des Créances - Version Web
 */

class Database {
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $pdo;
    private $charset = 'utf8mb4';
    
    public function __construct($host = DB_HOST, $dbname = DB_NAME, $username = DB_USER, $password = DB_PASS) {
        $this->host = $host;
        $this->dbname = $dbname;
        $this->username = $username;
        $this->password = $password;
    }
    
    /**
     * Connexion à la base de données
     */
    public function connect() {
        if ($this->pdo === null) {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset} COLLATE {$this->charset}_unicode_ci",
                PDO::ATTR_PERSISTENT => true
            ];
            
            try {
                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                $this->logError('Database connection failed: ' . $e->getMessage());
                throw new Exception('Erreur de connexion à la base de données.');
            }
        }
        
        return $this->pdo;
    }
    
    /**
     * Obtenir l'instance PDO
     */
    public function getPdo() {
        return $this->connect();
    }
    
    /**
     * Démarrer une transaction
     */
    public function beginTransaction() {
        return $this->getPdo()->beginTransaction();
    }
    
    /**
     * Valider une transaction
     */
    public function commit() {
        return $this->getPdo()->commit();
    }
    
    /**
     * Annuler une transaction
     */
    public function rollback() {
        return $this->getPdo()->rollback();
    }
    
    /**
     * Exécuter une requête de sélection
     */
    public function select($sql, $params = []) {
        try {
            $stmt = $this->getPdo()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logError('Select query failed: ' . $e->getMessage() . ' SQL: ' . $sql);
            throw new Exception('Erreur lors de la sélection des données.');
        }
    }
    
    /**
     * Exécuter une requête de sélection (une seule ligne)
     */
    public function selectOne($sql, $params = []) {
        try {
            $stmt = $this->getPdo()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logError('SelectOne query failed: ' . $e->getMessage() . ' SQL: ' . $sql);
            throw new Exception('Erreur lors de la sélection des données.');
        }
    }
    
    /**
     * Exécuter une requête d'insertion
     */
    public function insert($sql, $params = []) {
        try {
            $stmt = $this->getPdo()->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                return $this->getPdo()->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            $this->logError('Insert query failed: ' . $e->getMessage() . ' SQL: ' . $sql);
            throw new Exception('Erreur lors de l\'insertion des données.');
        }
    }
    
    /**
     * Exécuter une requête de mise à jour
     */
    public function update($sql, $params = []) {
        try {
            $stmt = $this->getPdo()->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                return $stmt->rowCount();
            }
            
            return false;
        } catch (PDOException $e) {
            $this->logError('Update query failed: ' . $e->getMessage() . ' SQL: ' . $sql);
            throw new Exception('Erreur lors de la mise à jour des données.');
        }
    }
    
    /**
     * Exécuter une requête de suppression
     */
    public function delete($sql, $params = []) {
        try {
            $stmt = $this->getPdo()->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                return $stmt->rowCount();
            }
            
            return false;
        } catch (PDOException $e) {
            $this->logError('Delete query failed: ' . $e->getMessage() . ' SQL: ' . $sql);
            throw new Exception('Erreur lors de la suppression des données.');
        }
    }
    
    /**
     * Exécuter une requête personnalisée
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->getPdo()->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            $this->logError('Execute query failed: ' . $e->getMessage() . ' SQL: ' . $sql);
            throw new Exception('Erreur lors de l\'exécution de la requête.');
        }
    }
    
    /**
     * Obtenir le nombre de lignes d'une table
     */
    public function count($table, $where = '', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM {$table}";
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }
        
        $result = $this->selectOne($sql, $params);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Vérifier si une table existe
     */
    public function tableExists($tableName) {
        $sql = "SELECT COUNT(*) as count FROM information_schema.tables 
                WHERE table_schema = ? AND table_name = ?";
        $result = $this->selectOne($sql, [$this->dbname, $tableName]);
        return $result && $result['count'] > 0;
    }
    
    /**
     * Obtenir la structure d'une table
     */
    public function getTableStructure($tableName) {
        $sql = "DESCRIBE {$tableName}";
        return $this->select($sql);
    }
    
    /**
     * Verrouillage optimiste - Vérifier la version
     */
    public function checkVersion($table, $id, $version) {
        $sql = "SELECT version FROM {$table} WHERE id = ?";
        $result = $this->selectOne($sql, [$id]);
        
        if (!$result) {
            throw new Exception('Enregistrement introuvable.');
        }
        
        if ((int)$result['version'] !== (int)$version) {
            throw new Exception('Les données ont été modifiées par un autre utilisateur. Veuillez actualiser et réessayer.');
        }
        
        return true;
    }
    
    /**
     * Incrémenter la version (pour le verrouillage optimiste)
     */
    public function incrementVersion($table, $id) {
        $sql = "UPDATE {$table} SET version = version + 1, updated_at = NOW() WHERE id = ?";
        return $this->update($sql, [$id]);
    }
    
    /**
     * Obtenir des statistiques sur la base de données
     */
    public function getDatabaseStats() {
        $stats = [];
        
        // Taille de la base de données
        $sql = "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS db_size_mb 
                FROM information_schema.tables 
                WHERE table_schema = ?";
        $result = $this->selectOne($sql, [$this->dbname]);
        $stats['db_size_mb'] = $result['db_size_mb'] ?? 0;
        
        // Nombre total de créances
        $stats['total_creances'] = $this->count('creances');
        
        // Nombre de créances actives
        $stats['active_creances'] = $this->count('creances', 'archived = 0');
        
        // Nombre de créances archivées
        $stats['archived_creances'] = $this->count('creances', 'archived = 1');
        
        // Montant total des créances
        $sql = "SELECT 
                    SUM(montant_creance) as total_creances,
                    SUM(provision_2024) as total_provisions
                FROM creances 
                WHERE archived = 0";
        $result = $this->selectOne($sql);
        $stats['total_montant_creances'] = $result['total_creances'] ?? 0;
        $stats['total_provisions'] = $result['total_provisions'] ?? 0;
        
        return $stats;
    }
    
    /**
     * Nettoyer les anciennes données (maintenance)
     */
    public function cleanup($days = 30) {
        $sql = "DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        return $this->delete($sql, [$days]);
    }
    
    /**
     * Sauvegarder la base de données
     */
    public function backup($filePath = null) {
        if (!$filePath) {
            $filePath = ROOT_PATH . '/exports/backup_' . date('Y-m-d_H-i-s') . '.sql';
        }
        
        $command = "mysqldump --user={$this->username} --password={$this->password} --host={$this->host} {$this->dbname} > {$filePath}";
        
        $output = null;
        $return_var = null;
        exec($command, $output, $return_var);
        
        if ($return_var === 0) {
            return $filePath;
        } else {
            throw new Exception('Erreur lors de la sauvegarde de la base de données.');
        }
    }
    
    /**
     * Logger une erreur
     */
    private function logError($message) {
        $logFile = ROOT_PATH . '/logs/database_error.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
        
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Logger une activité
     */
    public function logActivity($table, $recordId, $action, $oldValues = null, $newValues = null) {
        try {
            $sql = "INSERT INTO activity_logs (table_name, record_id, action, old_values, new_values, user_ip, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $params = [
                $table,
                $recordId,
                $action,
                $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                $this->getUserIP()
            ];
            
            $this->insert($sql, $params);
        } catch (Exception $e) {
            $this->logError('Failed to log activity: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtenir l'adresse IP de l'utilisateur
     */
    private function getUserIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
    }
    
    /**
     * Fermer la connexion
     */
    public function close() {
        $this->pdo = null;
    }
    
    /**
     * Destructeur
     */
    public function __destruct() {
        $this->close();
    }
}