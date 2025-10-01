<?php
/**
 * Classe Creance - Gestion des créances
 * Gestion des Créances - Version Web
 */

require_once 'Database.php';
require_once __DIR__ . '/../config/constants.php';

class Creance {
    private $db;
    private $table = 'creances';
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Obtenir toutes les créances avec filtres et pagination
     */
    public function getAll($filters = [], $archived = 0, $page = 1, $limit = ITEMS_PER_PAGE, $search = '') {
        $offset = ($page - 1) * $limit;
        $params = [];
        $where = ["archived = ?"];
        $params[] = $archived;
        
        // Recherche textuelle et numérique (combinée)
        if (!empty($search)) {
            $searchFields = [
                'region', 'secteur', 'client', 'intitule_marche', 
                'num_facture_situation', 'date_str', 'nature', 'observation'
            ];
            
            $searchConditions = [];
            // Recherche texte (LIKE)
            foreach ($searchFields as $field) {
                $searchConditions[] = "{$field} LIKE ?";
                $params[] = "%{$search}%";
            }

            // Normaliser la recherche pour détection numérique (supprimer espaces, convertir virgule->point)
            $searchNormalized = str_replace([' ', ','], ['', '.'], $search);
            if ($searchNormalized !== '' && is_numeric($searchNormalized)) {
                $numLike = "%{$searchNormalized}%";
                $numExact = (float)$searchNormalized;

                // Colonnes numériques à considérer
                $numFields = ['montant_total', 'montant_creance', 'encaissement', 'age_annees'];

                // CAST(... AS CHAR) LIKE '%num%' pour recherche partielle
                foreach ($numFields as $nf) {
                    $searchConditions[] = "CAST({$nf} AS CHAR) LIKE ?";
                    $params[] = $numLike;
                }

                // Comparaison numérique (égalité approx) pour correspondance exacte
                foreach ($numFields as $nf) {
                    $searchConditions[] = "ABS({$nf} - ?) < 0.0001";
                    $params[] = $numExact;
                }
            }

            if (!empty($searchConditions)) {
                $where[] = "(" . implode(" OR ", $searchConditions) . ")";
            }
        }
        
        // Filtres spécifiques
        foreach ($filters as $column => $values) {
            if (!empty($values) && in_array($column, COLONNES_FILTRABLES)) {
                if ($column === 'DATE') {
                    // Pour la date, filtrer par année
                    $yearConditions = [];
                    foreach ($values as $year) {
                        $yearConditions[] = "date_str LIKE ?";
                        $params[] = "%/{$year}";
                    }
                    if (!empty($yearConditions)) {
                        $where[] = "(" . implode(" OR ", $yearConditions) . ")";
                    }
                } else {
                    $placeholders = str_repeat('?,', count($values) - 1) . '?';
                    $where[] = "{$column} IN ({$placeholders})";
                    $params = array_merge($params, $values);
                }
            }
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        // ORDER BY date_str (si existe) puis id (évite erreur si updated_at absent)
        $limit = (int)$limit;
        $offset = (int)$offset;
        $sql = "SELECT * FROM {$this->table} {$whereClause} 
                ORDER BY date_str DESC, id DESC 
                LIMIT {$limit} OFFSET {$offset}";
        
        $results = $this->db->select($sql, $params);
        
        // Calculer les données dérivées
        foreach ($results as &$row) {
            $row = $this->calculateDerivedValues($row);
        }
        
        return $results;
    }
    
    /**
     * Obtenir le nombre total de créances (pour la pagination)
     */
    public function getCount($filters = [], $archived = 0, $search = '') {
        $params = [];
        $where = ["archived = ?"];
        $params[] = $archived;
        
        // Recherche textuelle et numérique (combinée)
        if (!empty($search)) {
            $searchFields = [
                'region', 'secteur', 'client', 'intitule_marche', 
                'num_facture_situation', 'date_str', 'nature', 'observation'
            ];
            
            $searchConditions = [];
            // Recherche texte (LIKE)
            foreach ($searchFields as $field) {
                $searchConditions[] = "{$field} LIKE ?";
                $params[] = "%{$search}%";
            }

            // Normaliser la recherche pour détection numérique (supprimer espaces, convertir virgule->point)
            $searchNormalized = str_replace([' ', ','], ['', '.'], $search);
            if ($searchNormalized !== '' && is_numeric($searchNormalized)) {
                $numLike = "%{$searchNormalized}%";
                $numExact = (float)$searchNormalized;

                // Colonnes numériques à considérer
                $numFields = ['montant_total', 'montant_creance', 'encaissement', 'age_annees'];

                // CAST(... AS CHAR) LIKE '%num%' pour recherche partielle
                foreach ($numFields as $nf) {
                    $searchConditions[] = "CAST({$nf} AS CHAR) LIKE ?";
                    $params[] = $numLike;
                }

                // Comparaison numérique (égalité approx) pour correspondance exacte
                foreach ($numFields as $nf) {
                    $searchConditions[] = "ABS({$nf} - ?) < 0.0001";
                    $params[] = $numExact;
                }
            }

            if (!empty($searchConditions)) {
                $where[] = "(" . implode(" OR ", $searchConditions) . ")";
            }
        }
        
        // Filtres spécifiques
        foreach ($filters as $column => $values) {
            if (!empty($values) && in_array($column, COLONNES_FILTRABLES)) {
                if ($column === 'DATE') {
                    $yearConditions = [];
                    foreach ($values as $year) {
                        $yearConditions[] = "date_str LIKE ?";
                        $params[] = "%/{$year}";
                    }
                    if (!empty($yearConditions)) {
                        $where[] = "(" . implode(" OR ", $yearConditions) . ")";
                    }
                } else {
                    $placeholders = str_repeat('?,', count($values) - 1) . '?';
                    $where[] = "{$column} IN ({$placeholders})";
                    $params = array_merge($params, $values);
                }
            }
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        $sql = "SELECT COUNT(*) as count FROM {$this->table} {$whereClause}";
        
        $result = $this->db->selectOne($sql, $params);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Obtenir une créance par ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $result = $this->db->selectOne($sql, [$id]);
        
        if ($result) {
            $result = $this->calculateDerivedValues($result);
        }
        
        return $result;
    }
    
    // ... le reste du fichier inchangé (add, update, delete, archive, restore, getUniqueValues, getStats, getAnalyticsData, calculateDerivedValues, processData, calculateAgeYears, calculateAgeMonths, calculateProvisionPercentage, validateData)
    /**
     * Ajouter une nouvelle créance
     */
    public function add($data) {
        // Validation des données
        $validationErrors = $this->validateData($data);
        if (!empty($validationErrors)) {
            throw new Exception(implode(', ', $validationErrors));
        }
        
        // Calculer les valeurs dérivées
        $processedData = $this->processData($data);
        
        try {
            $this->db->beginTransaction();
            
            $sql = "INSERT INTO {$this->table} (
                region, secteur, client, intitule_marche, num_facture_situation,
                date_str, nature, montant_total, encaissement, montant_creance,
                age_annees, pct_provision, provision_2024, observation, archived,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $params = [
                $processedData['region'],
                $processedData['secteur'],
                $processedData['client'],
                $processedData['intitule_marche'],
                $processedData['num_facture_situation'],
                $processedData['date_str'],
                $processedData['nature'],
                $processedData['montant_total'],
                $processedData['encaissement'],
                $processedData['montant_creance'],
                $processedData['age_annees'],
                $processedData['pct_provision'],
                $processedData['provision_2024'],
                $processedData['observation'],
                0 // archived = false par défaut
            ];
            
            $id = $this->db->insert($sql, $params);
            
            // Log de l'activité
            $this->db->logActivity($this->table, $id, 'INSERT', null, $processedData);
            
            $this->db->commit();
            
            return $id;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Modifier une créance existante
     */
    public function update($id, $data, $version = null) {
        // Validation des données
        $validationErrors = $this->validateData($data);
        if (!empty($validationErrors)) {
            throw new Exception(implode(', ', $validationErrors));
        }
        
        try {
            $this->db->beginTransaction();
            
            // Vérifier la version pour la concurrence (verrouillage optimiste)
            if ($version !== null) {
                $this->db->checkVersion($this->table, $id, $version);
            }
            
            // Obtenir les anciennes valeurs pour le log
            $oldData = $this->getById($id);
            if (!$oldData) {
                throw new Exception('Créance introuvable.');
            }
            
            // Calculer les valeurs dérivées
            $processedData = $this->processData($data);
            
            $sql = "UPDATE {$this->table} SET 
                region = ?, secteur = ?, client = ?, intitule_marche = ?, 
                num_facture_situation = ?, date_str = ?, nature = ?, 
                montant_total = ?, encaissement = ?, montant_creance = ?,
                age_annees = ?, pct_provision = ?, provision_2024 = ?, 
                observation = ?, version = version + 1, updated_at = NOW()
                WHERE id = ?";
            
            $params = [
                $processedData['region'],
                $processedData['secteur'],
                $processedData['client'],
                $processedData['intitule_marche'],
                $processedData['num_facture_situation'],
                $processedData['date_str'],
                $processedData['nature'],
                $processedData['montant_total'],
                $processedData['encaissement'],
                $processedData['montant_creance'],
                $processedData['age_annees'],
                $processedData['pct_provision'],
                $processedData['provision_2024'],
                $processedData['observation'],
                $id
            ];
            
            $rowCount = $this->db->update($sql, $params);
            
            if ($rowCount === 0) {
                throw new Exception('Aucune modification effectuée ou créance introuvable.');
            }
            
            // Log de l'activité
            $this->db->logActivity($this->table, $id, 'UPDATE', $oldData, $processedData);
            
            $this->db->commit();
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Supprimer définitivement une créance
     */
    public function delete($id) {
        try {
            $this->db->beginTransaction();
            
            // Obtenir les données pour le log
            $oldData = $this->getById($id);
            if (!$oldData) {
                throw new Exception('Créance introuvable.');
            }
            
            $sql = "DELETE FROM {$this->table} WHERE id = ?";
            $rowCount = $this->db->delete($sql, [$id]);
            
            if ($rowCount === 0) {
                throw new Exception('Créance introuvable.');
            }
            
            // Log de l'activité
            $this->db->logActivity($this->table, $id, 'DELETE', $oldData, null);
            
            $this->db->commit();
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Archiver une créance
     */
    public function archive($id) {
        try {
            $this->db->beginTransaction();
            
            $oldData = $this->getById($id);
            if (!$oldData) {
                throw new Exception('Créance introuvable.');
            }
            
            if ($oldData['archived'] == 1) {
                throw new Exception('La créance est déjà archivée.');
            }
            
            $sql = "UPDATE {$this->table} SET archived = 1, version = version + 1, updated_at = NOW() WHERE id = ?";
            $rowCount = $this->db->update($sql, [$id]);
            
            if ($rowCount === 0) {
                throw new Exception('Impossible d\'archiver la créance.');
            }
            
            // Log de l'activité
            $newData = $oldData;
            $newData['archived'] = 1;
            $this->db->logActivity($this->table, $id, 'ARCHIVE', $oldData, $newData);
            
            $this->db->commit();
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Restaurer une créance depuis les archives
     */
    public function restore($id) {
        try {
            $this->db->beginTransaction();
            
            $oldData = $this->getById($id);
            if (!$oldData) {
                throw new Exception('Créance introuvable.');
            }
            
            if ($oldData['archived'] == 0) {
                throw new Exception('La créance n\'est pas archivée.');
            }
            
            $sql = "UPDATE {$this->table} SET archived = 0, version = version + 1, updated_at = NOW() WHERE id = ?";
            $rowCount = $this->db->update($sql, [$id]);
            
            if ($rowCount === 0) {
                throw new Exception('Impossible de restaurer la créance.');
            }
            
            // Log de l'activité
            $newData = $oldData;
            $newData['archived'] = 0;
            $this->db->logActivity($this->table, $id, 'RESTORE', $oldData, $newData);
            
            $this->db->commit();
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Obtenir les valeurs uniques pour les filtres
     */
    public function getUniqueValues($column) {
        if (!in_array($column, COLONNES_FILTRABLES)) {
            return [];
        }
        
        if ($column === 'DATE') {
            // Pour les dates, extraire les années uniques
            $sql = "SELECT DISTINCT SUBSTRING(date_str, -4) as year FROM {$this->table} ORDER BY year DESC";
            $results = $this->db->select($sql);
            return array_column($results, 'year');
        } else {
            $sql = "SELECT DISTINCT {$column} as value FROM {$this->table} WHERE {$column} IS NOT NULL AND {$column} != '' ORDER BY value";
            $results = $this->db->select($sql);
            return array_column($results, 'value');
        }
    }
    
    public function getStats($filters = []) {
        $params = [];
        $where = ["archived = 0"];
        
        // Appliquer les filtres pour les provisions (données filtrées)
        foreach ($filters as $column => $values) {
            if (!empty($values) && in_array($column, COLONNES_FILTRABLES)) {
                if ($column === 'DATE') {
                    $yearConditions = [];
                    foreach ($values as $year) {
                        $yearConditions[] = "date_str LIKE ?";
                        $params[] = "%/{$year}";
                    }
                    if (!empty($yearConditions)) {
                        $where[] = "(" . implode(" OR ", $yearConditions) . ")";
                    }
                } else {
                    $placeholders = str_repeat('?,', count($values) - 1) . '?';
                    $where[] = "{$column} IN ({$placeholders})";
                    $params = array_merge($params, $values);
                }
            }
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        // Créances TTC et HT (toujours toutes les données actives)
        $sqlCreances = "SELECT SUM(montant_creance) as total FROM {$this->table} WHERE archived = 0";
        $resultCreances = $this->db->selectOne($sqlCreances);
        $creanceTTC = $resultCreances['total'] ?? 0;
        $creanceHT = $creanceTTC / 1.19;
        
        // Provisions TTC et HT (données filtrées)
        $sqlProvisions = "SELECT SUM(provision_2024) as total FROM {$this->table} {$whereClause}";
        $resultProvisions = $this->db->selectOne($sqlProvisions, $params);
        $provisionTTC = $resultProvisions['total'] ?? 0;
        $provisionHT = $provisionTTC / 1.19;
        
        return [
            'creance_ttc' => $creanceTTC,
            'creance_ht' => $creanceHT,
            'provision_ttc' => $provisionTTC,
            'provision_ht' => $provisionHT
        ];
    }
    
    /**
     * Obtenir les données pour les graphiques analytiques
     */
    public function getAnalyticsData($groupBy) {
        $validGroupBy = ['region', 'secteur', 'nature', 'age_annees'];
        if (!in_array($groupBy, $validGroupBy)) {
            throw new Exception('Groupement invalide.');
        }
        
        if ($groupBy === 'age_annees') {
            $sql = "SELECT 
                        CONCAT(age_annees, ' ans') as label,
                        SUM(montant_creance) as montant,
                        COUNT(*) as count
                    FROM {$this->table} 
                    WHERE archived = 0 
                    GROUP BY age_annees 
                    ORDER BY age_annees";
        } else {
            $sql = "SELECT 
                        {$groupBy} as label,
                        SUM(montant_creance) as montant,
                        COUNT(*) as count
                    FROM {$this->table} 
                    WHERE archived = 0 
                    GROUP BY {$groupBy} 
                    ORDER BY montant DESC";
        }
        
        return $this->db->select($sql);
    }    
    /**
     * Calculer les valeurs dérivées (âge, provisions, etc.)
     */
    private function calculateDerivedValues($data) {
        // Calculer l'âge en années
        $data['age_annees'] = $this->calculateAgeYears($data['date_str']);
        
        // Calculer l'âge en mois pour les provisions
        $ageMonths = $this->calculateAgeMonths($data['date_str']);
        
        // Calculer le pourcentage de provision
        $data['pct_provision'] = $this->calculateProvisionPercentage($ageMonths);
        
        // Calculer la provision 2024
        $data['provision_2024'] = ($data['montant_creance'] * $data['pct_provision']) / 100;
        
        return $data;
    }
    
private function processData($data) {
    $processed = [];
    
    $textFields = ['region', 'secteur', 'client', 'intitule_marche', 'num_facture_situation', 'date_str', 'nature'];
    foreach ($textFields as $field) {
        $processed[$field] = trim($data[$field] ?? '');
    }
    
    $processed['observation'] = !empty($data['observation']) ? trim($data['observation']) : null;
    
    $processed['montant_total'] = (float)($data['montant_total'] ?? 0);
    $processed['encaissement'] = (float)($data['encaissement'] ?? 0);
    
    if ($processed['encaissement'] == 0) {
        $processed['montant_creance'] = 0.0;
    } else {
        $processed['montant_creance'] = $processed['montant_total'] - $processed['encaissement'];
    }
    
    $processed['age_annees'] = $this->calculateAgeYears($processed['date_str']);
    $ageMonths = $this->calculateAgeMonths($processed['date_str']);
    $processed['pct_provision'] = $this->calculateProvisionPercentage($ageMonths);
    $processed['provision_2024'] = ($processed['montant_creance'] * $processed['pct_provision']) / 100;
    
    return $processed;
}
    
    private function calculateAgeYears($dateStr) {
        try {
            $date = DateTime::createFromFormat('d/m/Y', $dateStr);
            if (!$date) {
                return 0;
            }
            
            $now = new DateTime();
            $age = $now->diff($date)->y;
            return max($age, 0);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function calculateAgeMonths($dateStr) {
        try {
            $date = DateTime::createFromFormat('d/m/Y', $dateStr);
            if (!$date) {
                return 0;
            }
            
            $now = new DateTime();
            $diff = $now->diff($date);
            $months = ($diff->y * 12) + $diff->m;
            return max($months, 0);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function calculateProvisionPercentage($ageMonths) {
        if ($ageMonths >= 60) {      // 5 ans ou plus
            return 100;
        } elseif ($ageMonths >= 36) { // 3 à 5 ans
            return 50;
        } elseif ($ageMonths >= 24) { // 2 à 3 ans
            return 20;
        } else {                      // Moins de 2 ans
            return 0;
        }
    }
    
    private function validateData($data) {
        $errors = [];
        
        // Champs obligatoires
        $requiredFields = [
            'region', 'secteur', 'client', 'intitule_marche', 
            'num_facture_situation', 'date_str', 'nature', 'montant_total'
        ];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field]) || trim($data[$field]) === '') {
                $errors[] = "Le champ {$field} est obligatoire.";
            }
        }
        
        // Validation de la date
        if (!empty($data['date_str'])) {
            $date = DateTime::createFromFormat('d/m/Y', $data['date_str']);
            if (!$date || $date->format('d/m/Y') !== $data['date_str']) {
                $errors[] = "Format de date invalide. Utilisez DD/MM/YYYY.";
            }
        }
        
        // Validation des montants
        if (isset($data['montant_total'])) {
            if (!is_numeric($data['montant_total']) || $data['montant_total'] < 0) {
                $errors[] = "Le montant total doit être un nombre positif.";
            }
        }
        
        if (isset($data['encaissement'])) {
            if (!is_numeric($data['encaissement']) || $data['encaissement'] < 0) {
                $errors[] = "L'encaissement doit être un nombre positif.";
            }
            
            if (isset($data['montant_total']) && $data['encaissement'] > $data['montant_total']) {
                $errors[] = "L'encaissement ne peut pas être supérieur au montant total.";
            }
        }
        
        // Validation des listes déroulantes
        if (!empty($data['region']) && !in_array($data['region'], CHOIX_REGION)) {
            // Permettre les nouvelles régions
        }
        
        if (!empty($data['secteur']) && !in_array($data['secteur'], CHOIX_SECTEUR)) {
            $errors[] = "Secteur invalide.";
        }
        
        if (!empty($data['nature']) && !in_array($data['nature'], CHOIX_NATURE)) {
            $errors[] = "Nature invalide.";
        }
        
        if (!empty($data['observation']) && !in_array($data['observation'], CHOIX_OBSERVATION)) {
            $errors[] = "Observation invalide.";
        }
        
        return $errors;
    }
}
?>
