<?php
/**
 * Page principale de l'application
 * Gestion des Créances - Version Web
 */

session_start();

// Inclure les fichiers de configuration et classes
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'classes/Database.php';
require_once 'classes/Creance.php';

// Vérifier si composer est installé
if (!file_exists('vendor/autoload.php')) {
    die('Veuillez installer les dépendances avec "composer install"');
}

require_once 'vendor/autoload.php';

// Initialiser la vue courante
$currentView = $_GET['view'] ?? 'principal';
$validViews = ['principal', 'archive', 'analytics'];

if (!in_array($currentView, $validViews)) {
    $currentView = 'principal';
}

// Traitement des actions POST
$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $creance = new Creance();
        
        switch ($action) {
            case 'add':
                $id = $creance->add($_POST);
                $message = MESSAGES['SUCCESS_ADD'];
                $messageType = 'success';
                break;
                
            case 'edit':
                $id = $_POST['id'] ?? 0;
                $version = $_POST['version'] ?? null;
                $creance->update($id, $_POST, $version);
                $message = MESSAGES['SUCCESS_UPDATE'];
                $messageType = 'success';
                break;
                
            case 'delete':
                $id = $_POST['id'] ?? 0;
                $creance->delete($id);
                $message = MESSAGES['SUCCESS_DELETE'];
                $messageType = 'success';
                break;
                
            case 'archive':
                $id = $_POST['id'] ?? 0;
                $creance->archive($id);
                $message = MESSAGES['SUCCESS_ARCHIVE'];
                $messageType = 'success';
                break;
                
            case 'restore':
                $id = $_POST['id'] ?? 0;
                $creance->restore($id);
                $message = MESSAGES['SUCCESS_RESTORE'];
                $messageType = 'success';
                break;
        }
        
        // Rediriger pour éviter la resoumission du formulaire
        header("Location: index.php?view={$currentView}&message=" . urlencode($message) . "&type={$messageType}");
        exit;
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Récupérer le message de redirection
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'] ?? 'info';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo VIEWS[$currentView]; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/logo.ico">
    <link rel="shortcut icon" type="image/x-icon" href="assets/images/logo.ico">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Chart.js pour les graphiques -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body>
    <!-- En-tête de l'application -->
    <header class="app-header">
        <div class="header-content">
            <div class="header-left">
                <a href="index.php?view=principal" class="logo-link" title="Retour à l'accueil">
                    <img src="assets/images/logo.png" alt="Logo Gestion des Créances" class="app-logo">
                </a>
                <div class="title-group">
                    <h1 class="app-title"><?php echo APP_NAME; ?></h1>
                    <span class="version">v<?php echo APP_VERSION; ?></span>
                </div>
            </div>
            
            <div class="header-right">
                <div class="database-status" id="dbStatus">
                    <i class="fas fa-database"></i>
                    <span>Connexion DB</span>
                </div>
                <div class="current-time" id="currentTime">
                    <?php echo date('d/m/Y H:i:s'); ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Barre de navigation -->
    <nav class="app-nav">
        <div class="nav-content">
            <div class="nav-left">
                <div class="view-indicator">
                    Vue: <span class="current-view" id="currentView"><?php echo VIEWS[$currentView]; ?></span>
                </div>
            </div>
            
            <div class="nav-center">
                <?php if ($currentView === 'principal'): ?>
                    <!-- Barre de recherche -->
                    <div class="search-container">
                        <input type="text" id="searchInput" placeholder="Rechercher..." class="search-input">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="nav-right">
                <div class="nav-buttons">
                    <a href="?view=principal" class="nav-btn <?php echo $currentView === 'principal' ? 'active' : ''; ?>">
                        <i class="fas fa-table"></i> Principal
                    </a>
                    <a href="?view=archive" class="nav-btn <?php echo $currentView === 'archive' ? 'active' : ''; ?>">
                        <i class="fas fa-archive"></i> Archive
                    </a>
                    <a href="?view=analytics" class="nav-btn <?php echo $currentView === 'analytics' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i> Analytics
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Messages d'information -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?>" id="alertMessage">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
            <button type="button" class="alert-close" onclick="closeAlert()">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Contenu principal -->
    <main class="app-main">
        <?php
        // Charger le contenu selon la vue
        switch ($currentView) {
            case 'archive':
                include 'pages/archive.php';
                break;
            case 'analytics':
                include 'pages/analytics.php';
                break;
            default:
                include 'pages/main.php';
                break;
        }
        ?>
    </main>

    <!-- Modal de formulaire -->
    <div id="formModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Ajouter/Modifier une créance</h3>
                <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="creanceForm" method="POST">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="formId">
                    <input type="hidden" name="version" id="formVersion">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="region">Région *</label>
                            <div class="input-group">
                                <input type="checkbox" id="regionNouveau" class="checkbox-nouveau">
                                <label for="regionNouveau">Nouveau</label>
                                <select name="region" id="region" class="form-control" required>
                                    <option value="">Sélectionner...</option>
                                    <?php foreach (CHOIX_REGION as $region): ?>
                                        <option value="<?php echo htmlspecialchars($region); ?>"><?php echo htmlspecialchars($region); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" id="regionNew" name="region_new" class="form-control" style="display:none;" placeholder="Nouvelle région">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="secteur">Secteur *</label>
                            <select name="secteur" id="secteur" class="form-control" required>
                                <option value="">Sélectionner...</option>
                                <?php foreach (CHOIX_SECTEUR as $secteur): ?>
                                    <option value="<?php echo htmlspecialchars($secteur); ?>"><?php echo htmlspecialchars($secteur); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="client">Client *</label>
                            <div class="input-group">
                                <input type="checkbox" id="clientNouveau" class="checkbox-nouveau">
                                <label for="clientNouveau">Nouveau</label>
                                <select name="client" id="client" class="form-control" required>
                                    <option value="">Sélectionner...</option>
                                </select>
                                <input type="text" id="clientNew" name="client_new" class="form-control" style="display:none;" placeholder="Nouveau client">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="nature">Nature *</label>
                            <select name="nature" id="nature" class="form-control" required>
                                <option value="">Sélectionner...</option>
                                <?php foreach (CHOIX_NATURE as $nature): ?>
                                    <option value="<?php echo htmlspecialchars($nature); ?>"><?php echo htmlspecialchars($nature); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="intitule_marche">Intitulé Marché *</label>
                        <textarea name="intitule_marche" id="intitule_marche" class="form-control" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="num_facture_situation">N° Facture / Situation *</label>
                            <input type="text" name="num_facture_situation" id="num_facture_situation" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="date_str">Date *</label>
                            <input type="text" name="date_str" id="date_str" class="form-control" placeholder="DD/MM/YYYY" required>
                            <small class="form-help">Format: DD/MM/YYYY</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="montant_total">Montant Total *</label>
                            <input type="number" name="montant_total" id="montant_total" class="form-control" step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="encaissement">Encaissement</label>
                            <div class="input-group">
                                <input type="checkbox" id="encaissementZero" class="checkbox-zero">
                                <label for="encaissementZero">Zéro</label>
                                <input type="number" name="encaissement" id="encaissement" class="form-control" step="0.01" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="observation">Observation</label>
                        <div class="input-group">
                            <input type="checkbox" id="observationInclude" class="checkbox-include">
                            <label for="observationInclude">Inclure</label>
                            <select name="observation" id="observation" class="form-control" disabled>
                                <option value="">Sélectionner...</option>
                                <?php foreach (CHOIX_OBSERVATION as $obs): ?>
                                    <option value="<?php echo htmlspecialchars($obs); ?>"><?php echo htmlspecialchars($obs); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation -->
    <div id="confirmModal" class="modal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h3 id="confirmTitle">Confirmation</h3>
            </div>
            <div class="modal-body">
                <p id="confirmMessage">Êtes-vous sûr de vouloir effectuer cette action ?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="confirmYes">Oui</button>
                <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">Non</button>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="assets/js/main.js"></script>
    
    <script>
        // Configuration globale
        window.APP_CONFIG = {
            currentView: '<?php echo $currentView; ?>',
            ajaxUrl: 'pages/ajax/',
            constants: {
                choixRegion: <?php echo json_encode(CHOIX_REGION); ?>,
                choixSecteur: <?php echo json_encode(CHOIX_SECTEUR); ?>,
                choixNature: <?php echo json_encode(CHOIX_NATURE); ?>,
                choixObservation: <?php echo json_encode(CHOIX_OBSERVATION); ?>
            }
        };

        // Initialiser l'application
        document.addEventListener('DOMContentLoaded', function() {
            initializeApp();
            
            // Mettre à jour l'heure toutes les secondes
            updateTime();
            setInterval(updateTime, 1000);
            
            // Vérifier le statut de la base de données
            checkDatabaseStatus();
            setInterval(checkDatabaseStatus, 30000); // Toutes les 30 secondes
        });

        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('currentTime').textContent = timeString;
        }

        function checkDatabaseStatus() {
            fetch('pages/ajax/check_db.php')
                .then(response => response.json())
                .then(data => {
                    const statusElement = document.getElementById('dbStatus');
                    if (data.status === 'ok') {
                        statusElement.className = 'database-status status-ok';
                        statusElement.innerHTML = '<i class="fas fa-database"></i><span>DB OK</span>';
                    } else {
                        statusElement.className = 'database-status status-error';
                        statusElement.innerHTML = '<i class="fas fa-exclamation-triangle"></i><span>DB Error</span>';
                    }
                })
                .catch(() => {
                    const statusElement = document.getElementById('dbStatus');
                    statusElement.className = 'database-status status-error';
                    statusElement.innerHTML = '<i class="fas fa-exclamation-triangle"></i><span>DB Error</span>';
                });
        }

        function closeAlert() {
            const alert = document.getElementById('alertMessage');
            if (alert) {
                alert.style.display = 'none';
            }
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alert = document.getElementById('alertMessage');
            if (alert && alert.classList.contains('alert-success')) {
                alert.style.opacity = '0';
                setTimeout(() => alert.style.display = 'none', 300);
            }
        }, 5000);
    </script>
</body>
</html>
