<?php
/**
 * Constantes de l'application
 * Gestion des Créances - Version Web
 */

// Colonnes du tableau principal
define('COLONNES', [
    'REGION', 'SECTEUR', 'CLIENT', 'INTITULE MARCHE', 'N° FACTURE / SITUATION',
    'DATE', 'NATURE', 'MONTANT TOTAL', 'ENCAISSEMENT', 'MONTANT CREANCE', 
    'AGE DE LA CREANCE', '% PROVISION', 'PROVISION 2024', 'OBSERVATION'
]);

// Colonnes filtrables
define('COLONNES_FILTRABLES', ['REGION', 'SECTEUR', 'CLIENT', 'NATURE', 'DATE']);

// Colonnes du formulaire
define('COLONNES_FORMULAIRE', [
    'REGION', 'SECTEUR', 'CLIENT', 'INTITULE MARCHE', 
    'N° FACTURE / SITUATION', 'DATE', 'NATURE', 'MONTANT TOTAL', 'ENCAISSEMENT', 'OBSERVATION'
]);

// Index des colonnes dans les données
define('INDEX_DONNEES', [
    'REGION' => 0,
    'SECTEUR' => 1,
    'CLIENT' => 2,
    'INTITULE MARCHE' => 3,
    'N° FACTURE / SITUATION' => 4,
    'DATE' => 5,
    'NATURE' => 6,
    'MONTANT TOTAL' => 7,
    'ENCAISSEMENT' => 8,
    'MONTANT CREANCE' => 9,
    'AGE DE LA CREANCE' => 10,
    '% PROVISION' => 11,
    'PROVISION 2024' => 12,
    'OBSERVATION' => 13
]);

// Choix pour les listes déroulantes
define('CHOIX_REGION', [
    'ARZEW', 'BECHAR', 'BEJAIA', 'BOUINAN', 'DJANET', 'DOUERA', 'EL OUED', 
    'EX-TISSEMSILT', 'LABO', 'GHARDAIA', 'MASCARA', 'MOSTAGANEM', 'OUARGLA', 
    'OUED SEMMAR', 'REGANNE', 'SIEGE', 'TAMANRASSET', 'TINDOUF', 'TISSEMSILT'
]);

define('CHOIX_SECTEUR', [
    'DEMEMBREMENT', 'INTRA-GROUPE', 'INTER-GROUPE', 'HORS MTPIB', 
    'PRIVE', 'FACT A ETABLIR', 'PX STOCKEE'
]);

define('CHOIX_NATURE', [
    'FACT', 'PX STOCKEE', 'RG SIT', 'SIT', 'FACT A ETABLIR'
]);

define('CHOIX_OBSERVATION', [
    'CONTENTIEUX', 'PRE-CONTENTIEUX', 'ACTION JURIDIQUE'
]);

// Statuts des créances
define('STATUS_ACTIVE', 0);
define('STATUS_ARCHIVED', 1);

// Messages de l'application
define('MESSAGES', [
    'SUCCESS_ADD' => 'Créance ajoutée avec succès.',
    'SUCCESS_UPDATE' => 'Créance modifiée avec succès.',
    'SUCCESS_DELETE' => 'Créance supprimée avec succès.',
    'SUCCESS_ARCHIVE' => 'Créance archivée avec succès.',
    'SUCCESS_RESTORE' => 'Créance restaurée avec succès.',
    'ERROR_NOT_FOUND' => 'Créance introuvable.',
    'ERROR_DATABASE' => 'Erreur de base de données.',
    'ERROR_VALIDATION' => 'Erreur de validation des données.',
    'ERROR_PERMISSION' => 'Permission insuffisante.',
    'ERROR_CONCURRENT' => 'Les données ont été modifiées par un autre utilisateur. Veuillez actualiser et réessayer.',
    'ERROR_INVALID_DATE' => 'Format de date invalide. Utilisez DD/MM/YYYY.',
    'ERROR_INVALID_AMOUNT' => 'Montant invalide.',
    'ERROR_REQUIRED_FIELD' => 'Champ obligatoire manquant.'
]);

// Configuration des provisions
define('PROVISION_CONFIG', [
    'moins_24_mois' => 0,      // 0%
    '24_36_mois' => 20,        // 20%
    '36_60_mois' => 50,        // 50%
    'plus_60_mois' => 100      // 100%
]);

// Configuration de pagination
define('ITEMS_PER_PAGE', 50);
define('MAX_ITEMS_PER_PAGE', 200);

// Configuration des exports
define('EXPORT_FORMATS', ['pdf', 'excel', 'csv']);
define('MAX_EXPORT_ROWS', 10000);

// Configuration des graphiques
define('CHART_COLORS', [
    '#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8', 
    '#82CA9D', '#FFC658', '#FF6B6B', '#4ECDC4', '#45B7D1'
]);

// Configuration de sécurité
define('ALLOWED_FILE_TYPES', ['pdf', 'xls', 'xlsx', 'csv']);
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// Configuration de validation
define('VALIDATION_RULES', [
    'region' => ['required', 'max:100'],
    'secteur' => ['required', 'max:100'],
    'client' => ['required', 'max:255'],
    'intitule_marche' => ['required', 'max:1000'],
    'num_facture_situation' => ['required', 'max:255'],
    'date_str' => ['required', 'date_format:d/m/Y'],
    'nature' => ['required', 'max:50'],
    'montant_total' => ['required', 'numeric', 'min:0'],
    'encaissement' => ['required', 'numeric', 'min:0'],
    'observation' => ['nullable', 'max:255']
]);

// Configuration des logs
define('LOG_LEVELS', [
    'DEBUG' => 1,
    'INFO' => 2,
    'WARNING' => 3,
    'ERROR' => 4,
    'CRITICAL' => 5
]);

// Configuration de cache
define('CACHE_ENABLED', true);
define('CACHE_TTL', 3600); // 1 heure
define('CACHE_PREFIX', 'gc_'); // gestion_creances_

// Configuration des vues
define('VIEWS', [
    'principal' => 'Tableau Principal',
    'archive' => 'Tableau Archive',
    'analytics' => 'Analytic Montant Créance'
]);

// Configuration des actions AJAX
define('AJAX_ACTIONS', [
    'add', 'edit', 'delete', 'archive', 'restore', 'search', 'filter', 'export'
]);
?>