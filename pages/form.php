<?php
/**
 * Page de formulaire standalone pour ajouter/modifier une créance
 * Gestion des Créances - Version Web
 */

session_start();
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../classes/Database.php';
require_once '../classes/Creance.php';

$creance = new Creance();
$action = $_GET['action'] ?? 'add';
$id = $_GET['id'] ?? 0;
$editData = null;
$message = '';
$messageType = 'info';

// Si on est en mode édition, charger les données
if ($action === 'edit' && $id > 0) {
    try {
        $editData = $creance->getById($id);
        if (!$editData) {
            $message = 'Créance introuvable.';
            $messageType = 'error';
            $action = 'add';
        }
    } catch (Exception $e) {
        $message = 'Erreur lors du chargement : ' . $e->getMessage();
        $messageType = 'error';
        $action = 'add';
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($_POST['action'] === 'add') {
            $newId = $creance->add($_POST);
            $message = 'Créance ajoutée avec succès (ID: ' . $newId . ')';
            $messageType = 'success';
        } elseif ($_POST['action'] === 'edit') {
            $id = $_POST['id'] ?? 0;
            $version = $_POST['version'] ?? null;
            $creance->update($id, $_POST, $version);
            $message = 'Créance modifiée avec succès.';
            $messageType = 'success';
            // Recharger les données mises à jour
            $editData = $creance->getById($id);
        }
    } catch (Exception $e) {
        $message = 'Erreur : ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Charger les clients existants pour la liste déroulante
try {
    $db = new Database();
    $clientsResult = $db->select("SELECT DISTINCT client FROM creances WHERE client IS NOT NULL AND client != '' ORDER BY client");
    $clientsExistants = array_column($clientsResult, 'client');
} catch (Exception $e) {
    $clientsExistants = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $action === 'edit' ? 'Modifier' : 'Ajouter'; ?> une créance - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h1>
                <i class="fas fa-<?php echo $action === 'edit' ? 'edit' : 'plus'; ?>"></i>
                <?php echo $action === 'edit' ? 'Modifier' : 'Ajouter'; ?> une créance
            </h1>
            <a href="../index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form id="creanceForm" method="POST" novalidate>
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($action === 'edit' && $editData): ?>
                    <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">
                    <input type="hidden" name="version" value="<?php echo $editData['version']; ?>">
                <?php endif; ?>

                <div class="form-section">
                    <h3>Informations générales</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="region">Région *</label>
                            <div class="input-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" id="regionNouveau" class="checkbox-nouveau">
                                    <label for="regionNouveau">Nouvelle région</label>
                                </div>
                                <select name="region" id="region" class="form-control" required>
                                    <option value="">Sélectionner une région...</option>
                                    <?php foreach (CHOIX_REGION as $region): ?>
                                        <option value="<?php echo htmlspecialchars($region); ?>" 
                                                <?php echo ($editData && $editData['region'] === $region) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($region); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" id="regionNew" name="region_new" class="form-control" 
                                       style="display:none;" placeholder="Nouvelle région" maxlength="100">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="secteur">Secteur *</label>
                            <select name="secteur" id="secteur" class="form-control" required>
                                <option value="">Sélectionner un secteur...</option>
                                <?php foreach (CHOIX_SECTEUR as $secteur): ?>
                                    <option value="<?php echo htmlspecialchars($secteur); ?>"
                                            <?php echo ($editData && $editData['secteur'] === $secteur) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($secteur); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="client">Client *</label>
                            <div class="input-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" id="clientNouveau" class="checkbox-nouveau">
                                    <label for="clientNouveau">Nouveau client</label>
                                </div>
                                <select name="client" id="client" class="form-control" required>
                                    <option value="">Sélectionner un client...</option>
                                    <?php foreach ($clientsExistants as $client): ?>
                                        <option value="<?php echo htmlspecialchars($client); ?>"
                                                <?php echo ($editData && $editData['client'] === $client) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($client); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" id="clientNew" name="client_new" class="form-control" 
                                       style="display:none;" placeholder="Nouveau client" maxlength="255">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="nature">Nature *</label>
                            <select name="nature" id="nature" class="form-control" required>
                                <option value="">Sélectionner une nature...</option>
                                <?php foreach (CHOIX_NATURE as $nature): ?>
                                    <option value="<?php echo htmlspecialchars($nature); ?>"
                                            <?php echo ($editData && $editData['nature'] === $nature) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($nature); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Détails du marché</h3>
                    
                    <div class="form-group">
                        <label for="intitule_marche">Intitulé du marché *</label>
                        <textarea name="intitule_marche" id="intitule_marche" class="form-control" 
                                  rows="3" required maxlength="1000" 
                                  placeholder="Décrire l'intitulé du marché..."><?php echo $editData ? htmlspecialchars($editData['intitule_marche']) : ''; ?></textarea>
                        <small class="form-help">Maximum 1000 caractères</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="num_facture_situation">N° Facture / Situation *</label>
                            <input type="text" name="num_facture_situation" id="num_facture_situation" 
                                   class="form-control" required maxlength="255"
                                   value="<?php echo $editData ? htmlspecialchars($editData['num_facture_situation']) : ''; ?>"
                                   placeholder="Ex: FACT-2024-001">
                        </div>
                        
                        <div class="form-group">
                            <label for="date_str">Date de la créance *</label>
                            <input type="text" name="date_str" id="date_str" class="form-control" 
                                   required pattern="\d{2}/\d{2}/\d{4}"
                                   value="<?php echo $editData ? htmlspecialchars($editData['date_str']) : ''; ?>"
                                   placeholder="DD/MM/YYYY">
                            <small class="form-help">Format: DD/MM/YYYY (ex: 15/03/2024)</small>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Montants financiers</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="montant_total">Montant Total (DZD) *</label>
                            <input type="number" name="montant_total" id="montant_total" class="form-control" 
                                   step="0.01" min="0" required
                                   value="<?php echo $editData ? number_format($editData['montant_total'], 2, '.', '') : ''; ?>"
                                   placeholder="0.00">
                            <small class="form-help">Montant total de la créance en dinars algériens</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="encaissement">Encaissement (DZD)</label>
                            <div class="input-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" id="encaissementZero" class="checkbox-zero">
                                    <label for="encaissementZero">Montant total encaissé</label>
                                </div>
                                <input type="number" name="encaissement" id="encaissement" class="form-control" 
                                       step="0.01" min="0" 
                                       value="<?php echo $editData ? number_format($editData['encaissement'], 2, '.', '') : '0'; ?>"
                                       placeholder="0.00">
                            </div>
                            <small class="form-help">Montant déjà encaissé (0 par défaut)</small>
                        </div>
                    </div>
                    
                    <div class="calculated-info">
                        <div class="info-row">
                            <span>Montant de la créance:</span>
                            <span id="montantCreance">0.00 DZD</span>
                        </div>
                        <div class="info-row">
                            <span>Âge estimé:</span>
                            <span id="ageCreance">0 ans</span>
                        </div>
                        <div class="info-row">
                            <span>Provision estimée:</span>
                            <span id="provisionCreance">0.00 DZD (0%)</span>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Observation (optionnelle)</h3>
                    
                    <div class="form-group">
                        <div class="input-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="observationInclude" class="checkbox-include"
                                       <?php echo ($editData && !empty($editData['observation'])) ? 'checked' : ''; ?>>
                                <label for="observationInclude">Ajouter une observation</label>
                            </div>
                            <select name="observation" id="observation" class="form-control" 
                                    <?php echo ($editData && !empty($editData['observation'])) ? '' : 'disabled'; ?>>
                                <option value="">Aucune observation</option>
                                <?php foreach (CHOIX_OBSERVATION as $obs): ?>
                                    <option value="<?php echo htmlspecialchars($obs); ?>"
                                            <?php echo ($editData && $editData['observation'] === $obs) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($obs); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <small class="form-help">Sélectionner une observation si nécessaire</small>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i>
                        <?php echo $action === 'edit' ? 'Modifier' : 'Ajouter'; ?> la créance
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Réinitialiser
                    </button>
                    <a href="../index.php" class="btn btn-light">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
    $(document).ready(function() {
        // Initialisation
        initFormEvents();
        updateCalculatedValues();
        
        // Vérifier si on édite avec nouvelle région/client
        <?php if ($editData): ?>
            <?php if (!empty($editData['region']) && !in_array($editData['region'], CHOIX_REGION)): ?>
                $('#regionNouveau').prop('checked', true).trigger('change');
                $('#regionNew').val('<?php echo htmlspecialchars($editData['region']); ?>');
            <?php endif; ?>
            
            <?php if (!empty($editData['client']) && !in_array($editData['client'], $clientsExistants)): ?>
                $('#clientNouveau').prop('checked', true).trigger('change');
                $('#clientNew').val('<?php echo htmlspecialchars($editData['client']); ?>');
            <?php endif; ?>
        <?php endif; ?>
    });

    function initFormEvents() {
        // Région nouveau
        $('#regionNouveau').change(function() {
            if (this.checked) {
                $('#region').prop('disabled', true).removeAttr('required');
                $('#regionNew').show().prop('required', true).attr('name', 'region');
                $('#region').removeAttr('name');
            } else {
                $('#region').prop('disabled', false).prop('required', true).attr('name', 'region');
                $('#regionNew').hide().removeAttr('required').removeAttr('name').val('');
            }
        });

        // Client nouveau
        $('#clientNouveau').change(function() {
            if (this.checked) {
                $('#client').prop('disabled', true).removeAttr('required');
                $('#clientNew').show().prop('required', true).attr('name', 'client');
                $('#client').removeAttr('name');
            } else {
                $('#client').prop('disabled', false).prop('required', true).attr('name', 'client');
                $('#clientNew').hide().removeAttr('required').removeAttr('name').val('');
            }
        });

        // Observation include
        $('#observationInclude').change(function() {
            $('#observation').prop('disabled', !this.checked);
            if (!this.checked) {
                $('#observation').val('');
            }
        });

        // Encaissement zéro
        $('#encaissementZero').change(function() {
            if (this.checked) {
                var montantTotal = parseFloat($('#montant_total').val()) || 0;
                $('#encaissement').prop('disabled', true).val(montantTotal.toFixed(2));
            } else {
                $('#encaissement').prop('disabled', false).val('0.00');
            }
            updateCalculatedValues();
        });

        // Auto-formatage date
        $('#date_str').on('input', function() {
            var value = this.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2);
            }
            if (value.length >= 5) {
                value = value.slice(0, 5) + '/' + value.slice(5, 9);
            }
            this.value = value;
            updateCalculatedValues();
        });

        // Mise à jour calculs
        $('#montant_total, #encaissement').on('input', function() {
            if ($('#encaissementZero').is(':checked')) {
                var montantTotal = parseFloat($('#montant_total').val()) || 0;
                $('#encaissement').val(montantTotal.toFixed(2));
            }
            updateCalculatedValues();
        });

        // Validation formulaire
        $('#creanceForm').submit(function(e) {
            if (!validateForm()) {
                e.preventDefault();
            }
        });
    }

    function updateCalculatedValues() {
        var montantTotal = parseFloat($('#montant_total').val()) || 0;
        var encaissement = parseFloat($('#encaissement').val()) || 0;
        var montantCreance = montantTotal - encaissement;
        
        // Validation encaissement
        if (encaissement > montantTotal) {
            $('#encaissement').addClass('error');
            $('#encaissement')[0].setCustomValidity('L\'encaissement ne peut pas être supérieur au montant total');
        } else {
            $('#encaissement').removeClass('error');
            $('#encaissement')[0].setCustomValidity('');
        }

        $('#montantCreance').text(formatMoney(montantCreance) + ' DZD');

        // Calculer l'âge
        var dateStr = $('#date_str').val();
        var age = calculateAge(dateStr);
        $('#ageCreance').text(age + ' ans');

        // Calculer la provision
        var ageMonths = calculateAgeMonths(dateStr);
        var provisionPct = calculateProvisionPercentage(ageMonths);
        var provision = (montantCreance * provisionPct) / 100;
        $('#provisionCreance').text(formatMoney(provision) + ' DZD (' + provisionPct + '%)');
    }

    function calculateAge(dateStr) {
        if (!dateStr || !validateDate(dateStr)) return 0;
        
        var parts = dateStr.split('/');
        var creanceDate = new Date(parts[2], parts[1] - 1, parts[0]);
        var today = new Date();
        
        var age = today.getFullYear() - creanceDate.getFullYear();
        if (today < new Date(today.getFullYear(), creanceDate.getMonth(), creanceDate.getDate())) {
            age--;
        }
        return Math.max(age, 0);
    }

    function calculateAgeMonths(dateStr) {
        if (!dateStr || !validateDate(dateStr)) return 0;
        
        var parts = dateStr.split('/');
        var creanceDate = new Date(parts[2], parts[1] - 1, parts[0]);
        var today = new Date();
        
        var months = (today.getFullYear() - creanceDate.getFullYear()) * 12;
        months += today.getMonth() - creanceDate.getMonth();
        return Math.max(months, 0);
    }

    function calculateProvisionPercentage(ageMonths) {
        if (ageMonths >= 60) return 100;      // 5 ans ou plus
        if (ageMonths >= 36) return 50;       // 3-5 ans
        if (ageMonths >= 24) return 20;       // 2-3 ans
        return 0;                             // Moins de 2 ans
    }

    function validateDate(dateStr) {
        var regex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
        var match = dateStr.match(regex);
        
        if (!match) return false;
        
        var day = parseInt(match[1], 10);
        var month = parseInt(match[2], 10);
        var year = parseInt(match[3], 10);
        
        if (month < 1 || month > 12) return false;
        if (day < 1 || day > 31) return false;
        
        var date = new Date(year, month - 1, day);
        return date.getFullYear() === year && 
               date.getMonth() === month - 1 && 
               date.getDate() === day;
    }

    function validateForm() {
        var isValid = true;
        
        // Validation date
        var dateStr = $('#date_str').val();
        if (!validateDate(dateStr)) {
            alert('Format de date invalide. Utilisez DD/MM/YYYY');
            $('#date_str').focus();
            return false;
        }
        
        // Validation montants
        var montantTotal = parseFloat($('#montant_total').val()) || 0;
        var encaissement = parseFloat($('#encaissement').val()) || 0;
        
        if (montantTotal <= 0) {
            alert('Le montant total doit être supérieur à zéro');
            $('#montant_total').focus();
            return false;
        }
        
        if (encaissement > montantTotal) {
            alert('L\'encaissement ne peut pas être supérieur au montant total');
            $('#encaissement').focus();
            return false;
        }
        
        return isValid;
    }

    function formatMoney(amount) {
        return new Intl.NumberFormat('fr-DZ', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    }
    </script>

    <style>
    .form-container {
        max-width: 900px;
        margin: 2rem auto;
        padding: 0 1rem;
    }

    .form-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--primary-color);
    }

    .form-card {
        background: white;
        border-radius: 12px;
        box-shadow: var(--shadow-lg);
        padding: 2rem;
    }

    .form-section {
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #eee;
    }

    .form-section:last-of-type {
        border-bottom: none;
    }

    .form-section h3 {
        color: var(--primary-color);
        margin-bottom: 1rem;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-section h3::before {
        content: '';
        width: 4px;
        height: 20px;
        background: var(--primary-color);
        border-radius: 2px;
    }

    .checkbox-group {
        margin-bottom: 0.5rem;
    }

    .checkbox-group input[type="checkbox"] {
        margin-right: 0.5rem;
    }

    .calculated-info {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 1rem;
        margin-top: 1rem;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 0.25rem 0;
        font-weight: 500;
    }

    .info-row:last-child {
        border-top: 1px solid #dee2e6;
        margin-top: 0.5rem;
        padding-top: 0.75rem;
        color: var(--primary-color);
    }

    .form-control.error {
        border-color: var(--danger-color);
        box-shadow: 0 0 0 3px rgba(244, 67, 54, 0.1);
    }

    .btn-lg {
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
    }

    @media (max-width: 768px) {
        .form-header {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }
        
        .form-card {
            padding: 1rem;
        }
    }
    </style>
</body>
</html>