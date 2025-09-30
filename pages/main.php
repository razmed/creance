<?php
/**
 * Page principale - Tableau des créances
 * Gestion des Créances - Version Web
 */

// Initialiser les variables
$creance = new Creance();
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(MAX_ITEMS_PER_PAGE, max(10, (int)($_GET['limit'] ?? ITEMS_PER_PAGE)));
$search = trim($_GET['search'] ?? '');
$filters = [];

// Traiter les filtres depuis la session ou l'URL
if (isset($_SESSION['filters'])) {
    $filters = $_SESSION['filters'];
}

// Récupérer les données
try {
    $donnees = $creance->getAll($filters, STATUS_ACTIVE, $page, $limit, $search);
    $totalCount = $creance->getCount($filters, STATUS_ACTIVE, $search);
    $totalPages = ceil($totalCount / $limit);
    $stats = $creance->getStats($filters);
    
    // Récupérer les valeurs uniques pour les filtres
    $uniqueValues = [];
    foreach (COLONNES_FILTRABLES as $col) {
        $uniqueValues[$col] = $creance->getUniqueValues($col);
    }
    
} catch (Exception $e) {
    $donnees = [];
    $totalCount = 0;
    $totalPages = 0;
    $stats = ['creance_ttc' => 0, 'creance_ht' => 0, 'provision_ttc' => 0, 'provision_ht' => 0];
    $uniqueValues = [];
    $error = $e->getMessage();

    // DEBUG
    error_log("Erreur SQL : " . $e->getMessage());
    echo "<pre style='color:red'>DEBUG : " . $e->getMessage() . "</pre>";
}
?>

<div class="main-content">
    <!-- Barre d'outils -->
    <div class="toolbar">
        <div class="toolbar-left">
            <button class="btn btn-primary" onclick="openFormModal()">
                <i class="fas fa-plus"></i> Nouvelle Créance
            </button>
            
            <button class="btn btn-info" onclick="showStatsModal()">
                <i class="fas fa-calculator"></i> Créance-Provision
            </button>
            
            <div class="dropdown">
                <button class="btn btn-warning dropdown-toggle" onclick="toggleDropdown('exportDropdown')">
                    <i class="fas fa-file-export"></i> Exporter
                </button>
                <div class="dropdown-menu" id="exportDropdown">
                    <a href="#" onclick="exportPDF()" class="dropdown-item">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    <a href="#" onclick="exportExcel()" class="dropdown-item">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="#" onclick="exportCSV()" class="dropdown-item">
                        <i class="fas fa-file-csv"></i> CSV
                    </a>
                </div>
            </div>
        </div>
        
        <div class="toolbar-right">
            <div class="results-info">
                <span class="results-count"><?php echo number_format($totalCount); ?> résultat(s)</span>
                <?php if (!empty($search)): ?>
                    <span class="search-indicator">
                        <i class="fas fa-filter"></i> Recherche active
                    </span>
                <?php endif; ?>
                <?php if (!empty(array_filter($filters))): ?>
                    <span class="filter-indicator">
                        <i class="fas fa-funnel-dollar"></i> Filtres actifs
                    </span>
                <?php endif; ?>
            </div>
            
            <button class="btn btn-secondary" onclick="resetFilters()">
                <i class="fas fa-undo"></i> Réinitialiser
            </button>
        </div>
    </div>
    
    <!-- Filtres -->
    <div class="filters-container" id="filtersContainer">
        <div class="filters-header">
            <h4>Filtres</h4>
            <button class="btn-link" onclick="toggleFilters()">
                <i class="fas fa-chevron-down" id="filtersToggle"></i>
            </button>
        </div>
        
        <div class="filters-content" id="filtersContent">
            <div class="filters-row">
                <?php foreach (COLONNES_FILTRABLES as $col): ?>
                    <div class="filter-group">
                        <label><?php echo $col; ?></label>
                        <div class="filter-dropdown">
                            <button class="filter-btn" onclick="toggleFilterDropdown('<?php echo $col; ?>')">
                                <span id="filter-<?php echo $col; ?>-text">Tous</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="filter-options" id="filter-<?php echo $col; ?>-options">
                                <div class="filter-search">
                                    <input type="text" placeholder="Rechercher..." onkeyup="filterOptions('<?php echo $col; ?>', this.value)">
                                </div>
                                <div class="filter-select-all">
                                    <label>
                                        <input type="checkbox" onchange="selectAllOptions('<?php echo $col; ?>', this.checked)" checked>
                                        Sélectionner tout
                                    </label>
                                </div>
                                <div class="filter-list" id="filter-<?php echo $col; ?>-list">
                                    <?php if (isset($uniqueValues[$col])): ?>
                                        <?php foreach ($uniqueValues[$col] as $value): ?>
                                            <label class="filter-option">
                                                <input type="checkbox" value="<?php echo htmlspecialchars($value); ?>" 
                                                       onchange="updateFilter('<?php echo $col; ?>')" checked>
                                                <span><?php echo htmlspecialchars($value); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="filter-actions">
                                    <button class="btn btn-sm btn-primary" onclick="applyFilter('<?php echo $col; ?>')">Appliquer</button>
                                    <button class="btn btn-sm btn-secondary" onclick="clearFilter('<?php echo $col; ?>')">Effacer</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Tableau des créances -->
    <div class="table-container">
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                Erreur lors du chargement des données: <?php echo htmlspecialchars($error); ?>
            </div>
        <?php elseif (empty($donnees)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>Aucune créance trouvée</h3>
                <p>Il n'y a aucune créance qui correspond à vos critères de recherche.</p>
                <button class="btn btn-primary" onclick="openFormModal()">
                    <i class="fas fa-plus"></i> Ajouter la première créance
                </button>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table" id="creancesTable">
                    <thead>
                        <tr>
                            <?php foreach (COLONNES as $col): ?>
                                <th class="sortable" onclick="sortTable('<?php echo $col; ?>')">
                                    <?php echo $col; ?>
                                    <i class="fas fa-sort sort-icon"></i>
                                </th>
                            <?php endforeach; ?>
                            <th class="actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($donnees as $ligne): ?>
                            <tr data-id="<?php echo $ligne['id']; ?>" data-version="<?php echo $ligne['version']; ?>">
                                <td><?php echo htmlspecialchars($ligne['region']); ?></td>
                                <td><?php echo htmlspecialchars($ligne['secteur']); ?></td>
                                <td title="<?php echo htmlspecialchars($ligne['client']); ?>">
                                    <?php echo htmlspecialchars(mb_substr($ligne['client'], 0, 30) . (mb_strlen($ligne['client']) > 30 ? '...' : '')); ?>
                                </td>
                                <td title="<?php echo htmlspecialchars($ligne['intitule_marche']); ?>">
                                    <?php echo htmlspecialchars(mb_substr($ligne['intitule_marche'], 0, 40) . (mb_strlen($ligne['intitule_marche']) > 40 ? '...' : '')); ?>
                                </td>
                                <td><?php echo htmlspecialchars($ligne['num_facture_situation']); ?></td>
                                <td><?php echo htmlspecialchars($ligne['date_str']); ?></td>
                                <td><?php echo htmlspecialchars($ligne['nature']); ?></td>
                                <td class="amount"><?php echo number_format($ligne['montant_total'], 2, ',', ' '); ?></td>
                                <td class="amount">
                                    <?php echo $ligne['montant_creance'] == 0 ? '' : number_format($ligne['encaissement'], 2, ',', ' '); ?>
                                </td>
                                <td class="amount"><?php echo number_format($ligne['montant_creance'], 2, ',', ' '); ?></td>
                                <td class="age"><?php echo $ligne['age_annees']; ?> ans</td>
                                <td class="percentage"><?php echo number_format($ligne['pct_provision'], 1); ?>%</td>
                                <td class="amount"><?php echo number_format($ligne['provision_2024'], 2, ',', ' '); ?></td>
                                <td class="observation">
                                    <?php if (!empty($ligne['observation'])): ?>
                                        <span class="badge badge-<?php echo $ligne['observation'] === 'CONTENTIEUX' ? 'danger' : ($ligne['observation'] === 'PRE-CONTENTIEUX' ? 'warning' : 'info'); ?>">
                                            <?php echo htmlspecialchars($ligne['observation']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <div class="action-buttons">
                                        <button class="btn-action btn-edit" onclick="editCreance(<?php echo $ligne['id']; ?>)" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-action btn-archive" onclick="archiveCreance(<?php echo $ligne['id']; ?>)" title="Archiver">
                                            <i class="fas fa-archive"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination-info">
                        Affichage de <?php echo ($page - 1) * $limit + 1; ?> à 
                        <?php echo min($page * $limit, $totalCount); ?> sur <?php echo number_format($totalCount); ?> résultats
                    </div>
                    
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=1&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>" class="pagination-link">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                            <a href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>" class="pagination-link">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <a href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>" 
                               class="pagination-link <?php echo $i === $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>" class="pagination-link">
                                <i class="fas fa-angle-right"></i>
                            </a>
                            <a href="?page=<?php echo $totalPages; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>" class="pagination-link">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="pagination-controls">
                        <select onchange="changePageSize(this.value)" class="form-control">
                            <option value="25" <?php echo $limit === 25 ? 'selected' : ''; ?>>25 par page</option>
                            <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50 par page</option>
                            <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100 par page</option>
                            <option value="200" <?php echo $limit === 200 ? 'selected' : ''; ?>>200 par page</option>
                        </select>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal des statistiques -->
<div id="statsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Tableau Créance-Provision</h3>
            <button type="button" class="modal-close" onclick="closeStatsModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="stats-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>CRÉANCE EN TTC</th>
                            <th>CRÉANCE EN HT</th>
                            <th>PROVISIONS EN TTC</th>
                            <th>PROVISIONS EN HT</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="amount"><?php echo number_format($stats['creance_ttc'], 2, ',', ' '); ?></td>
                            <td class="amount"><?php echo number_format($stats['creance_ht'], 2, ',', ' '); ?></td>
                            <td class="amount"><?php echo number_format($stats['provision_ttc'], 2, ',', ' '); ?></td>
                            <td class="amount"><?php echo number_format($stats['provision_ht'], 2, ',', ' '); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="stats-info">
                <p><strong>Note:</strong></p>
                <ul>
                    <li>Les créances incluent <strong>toutes</strong> les données actives (sans filtres)</li>
                    <li>Les provisions incluent <strong>seulement</strong> les données filtrées actuellement visibles</li>
                    <li>Les montants HT sont calculés en divisant les montants TTC par 1.19</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal/Form card (iframe) -->
<style>
#formModalIframeBG {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.45);
  z-index: 1200;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  backdrop-filter: blur(2px);
}

iframe#formIframe {
    height: -webkit-fill-available;
}

#formModalIframe {
  background: #fff;
  width: 100%;
  max-width: 980px;
  height: 85vh;
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
  border: none;
}

@media (max-width: 768px) {
  #formModalIframe {
    width: 100%;
    height: 100vh;
    border-radius: 0;
    max-width: none;
  }
  
  #formModalIframeBG {
    padding: 0;
  }
}
</style>

<div id="formModalIframeBG" role="dialog" aria-hidden="true">
    <div style="background: #fff; width: 100%; max-width: 980px; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); display: flex; flex-direction: column; overflow: hidden; height: 92vh;">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid #e0e0e0; background: linear-gradient(135deg, #f8f9fa, #e9ecef); flex-shrink: 0;">
            <strong id="formModalTitle" style="font-size: 1.2rem; font-weight: 600; color: #2c3e50; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-file-alt" style="color: var(--primary-color);"></i>
                Formulaire
            </strong>
            <div style="display: flex; gap: 0.5rem;">
                <button onclick="document.getElementById('formIframe').contentWindow.location.reload();" title="Recharger le formulaire" style="background: #fff; border: 1px solid #ddd; border-radius: 6px; padding: 0.5rem 0.75rem; cursor: pointer; transition: all 0.3s; font-size: 1.1rem;">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button onclick="closeFormModal()" title="Fermer" style="background: transparent; border: none; font-size: 1.5rem; cursor: pointer; padding: 0.25rem 0.5rem; color: #666; transition: color 0.3s;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <iframe id="formIframe" src="" title="Formulaire créance" style="width: 100%; flex: 1; border: none;"></iframe>
    </div>
</div>

<script>

// ============================================
// VARIABLES GLOBALES
// ============================================

function debugAjaxResponse(stage, data) {
    console.log('=== DEBUG AJAX ===');
    console.log('Stage:', stage);
    console.log('Data:', data);
    console.log('Type:', typeof data);
    if (data && data.data) {
        console.log('data.data:', data.data);
        if (data.data.creances) {
            console.log('Nombre de créances:', data.data.creances.length);
            console.log('Première créance:', data.data.creances[0]);
        }
    }
    console.log('==================');
}

let currentSort = null;
let sortDirection = 'asc';
let activeFilters = <?php echo json_encode($filters); ?>;

let searchTimeout = null;
let lastSearchValue = '';
let isSearching = false;

console.log('Filtres initiaux:', activeFilters);

// ============================================
// INITIALISATION AU CHARGEMENT DE LA PAGE
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('Page chargée - Initialisation des événements de recherche');
    initSearchEvents();
});

function initSearchEvents() {
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    
    if (!searchInput || !searchBtn) {
        console.warn('Éléments de recherche non trouvés');
        return;
    }
    
    console.log('Initialisation des événements de recherche...');
    
    searchBtn.addEventListener('click', function(e) {
        e.preventDefault();
        console.log('Clic sur bouton loupe');
        performSearch(true);
    });
    
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            console.log('Touche Entrée pressée');
            performSearch(true);
        } else if (e.key === 'Escape') {
            console.log('Touche Escape pressée');
            clearSearch();
        }
    });
    
    searchInput.addEventListener('input', function(e) {
        console.log('Input changé:', e.target.value);
        performSearch(false);
    });
    
    searchInput.addEventListener('focus', function() {
        this.select();
    });
    
    console.log('Événements de recherche initialisés avec succès');
}

// ============================================
// FONCTION PRINCIPALE DE RECHERCHE
// ============================================

function performSearch(immediate = false) {
    const searchInput = document.getElementById('searchInput');
    
    if (!searchInput) {
        console.error('Input de recherche introuvable');
        return;
    }
    
    const searchValue = searchInput.value.trim();
    
    console.log('performSearch appelé - immediate:', immediate, 'valeur:', searchValue);
    
    if (searchValue === lastSearchValue && isSearching) {
        console.log('Recherche déjà en cours pour cette valeur, ignoré');
        return;
    }
    
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    
    if (immediate) {
        console.log('Recherche immédiate');
        executeSearch(searchValue);
    } else {
        console.log('Recherche avec délai de 500ms');
        searchTimeout = setTimeout(function() {
            executeSearch(searchValue);
        }, 500);
    }
}

function executeSearch(searchValue) {
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    
    console.log('executeSearch - Début avec valeur:', searchValue);
    
    lastSearchValue = searchValue;
    isSearching = true;
    
    if (searchInput) {
        searchInput.classList.add('searching');
        searchInput.classList.remove('has-results', 'no-results');
    }
    
    if (searchBtn) {
        searchBtn.classList.add('searching');
        searchBtn.disabled = true;
    }
    
    const normalizedSearch = normalizeSearchInput(searchValue);
    console.log('Recherche normalisée:', normalizedSearch);
    
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page') || 1;
    const limit = urlParams.get('limit') || <?php echo ITEMS_PER_PAGE; ?>;
    
    const params = {
        filters: JSON.stringify(convertFiltersToBackend(activeFilters)),
        search: normalizedSearch,
        page: 1,
        limit: limit,
        archived: 0,
        get_unique_values: '0'
    };
    
    const queryString = new URLSearchParams(params).toString();
    const url = 'pages/ajax/search_filter.php?' + queryString;
    
    console.log('URL de recherche:', url);
    
    showLoader();
    
    fetch(url, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('Réponse reçue - Status:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('Texte de réponse:', text.substring(0, 200) + '...');
        
        try {
            const data = JSON.parse(text);
            debugAjaxResponse('Après parsing JSON', data);
            
            hideLoader();
            isSearching = false;
            
            if (searchInput) {
                searchInput.classList.remove('searching');
            }
            if (searchBtn) {
                searchBtn.classList.remove('searching');
                searchBtn.disabled = false;
            }
            
            if (data.success) {
                console.log('Recherche réussie:', data.data.creances.length, 'résultats');
                
                if (data.data.creances.length > 0) {
                    if (searchInput) searchInput.classList.add('has-results');
                } else {
                    if (searchInput) searchInput.classList.add('no-results');
                }
                
                updateTableContent(data.data.creances);
                
                if (data.data.pagination) {
                    updatePaginationInfo(data.data.pagination);
                    updateResultsCount(data.data.pagination.total_count);
                }
                
                updateSearchIndicator(searchValue, data.data.pagination.total_count);
                
            } else {
                console.error('Erreur recherche:', data.error);
                alert('Erreur lors de la recherche: ' + data.error);
                
                if (searchInput) searchInput.classList.add('no-results');
            }
            
        } catch (e) {
            console.error('Erreur parsing JSON:', e);
            console.error('Texte reçu:', text);
            
            hideLoader();
            isSearching = false;
            
            if (searchInput) {
                searchInput.classList.remove('searching');
                searchInput.classList.add('no-results');
            }
            if (searchBtn) {
                searchBtn.classList.remove('searching');
                searchBtn.disabled = false;
            }
            
            alert('Erreur: Réponse serveur invalide');
        }
    })
    .catch(error => {
        console.error('Exception recherche:', error);
        
        hideLoader();
        isSearching = false;
        
        if (searchInput) {
            searchInput.classList.remove('searching');
            searchInput.classList.add('no-results');
        }
        if (searchBtn) {
            searchBtn.classList.remove('searching');
            searchBtn.disabled = false;
        }
        
        alert('Erreur de connexion: ' + error.message);
    });
}

function updateSearchIndicator(searchValue, resultCount) {
    const oldIndicator = document.querySelector('.search-indicator');
    if (oldIndicator) {
        oldIndicator.remove();
    }
    
    if (searchValue) {
        const toolbar = document.querySelector('.toolbar-right .results-info');
        if (toolbar) {
            const indicator = document.createElement('span');
            indicator.className = 'search-indicator';
            indicator.innerHTML = `
                <i class="fas fa-search"></i> 
                "${escapeHtml(searchValue)}" 
                (${resultCount} résultat${resultCount > 1 ? 's' : ''})
                <button type="button" onclick="clearSearch()" class="clear-search-btn" title="Effacer la recherche">
                    <i class="fas fa-times"></i>
                </button>
            `;
            toolbar.appendChild(indicator);
        }
    }
}

function clearSearch() {
    console.log('clearSearch appelé');
    
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
        searchInput.classList.remove('has-results', 'no-results', 'searching');
    }
    
    const indicator = document.querySelector('.search-indicator');
    if (indicator) {
        indicator.remove();
    }
    
    performSearch(true);
}

function normalizeSearchInput(s) {
    if (!s) return '';
    let t = s.trim();

    if (/^[\d\s.,+-]+$/.test(t)) {
        t = t.replace(/\s+/g, '');
        t = t.replace(/,/g, '.');
    }

    return t;
}

// ============================================
// FONCTIONS DE MISE À JOUR DU TABLEAU
// ============================================

function reloadTableData() {
    console.log('reloadTableData - DÉBUT');
    console.log('reloadTableData - activeFilters:', activeFilters);
    
    const rawSearch = document.getElementById('searchInput') ? document.getElementById('searchInput').value : '';
    const normalizedSearch = normalizeSearchInput(rawSearch);
    const urlParams = new URLSearchParams(window.location.search);

    const page = urlParams.get('page') || 1;
    const limit = urlParams.get('limit') || <?php echo ITEMS_PER_PAGE; ?>;
    
    const params = {
        filters: JSON.stringify(convertFiltersToBackend(activeFilters)),
        search: normalizedSearch,
        page: page,
        limit: limit,
        archived: 0,
        get_unique_values: '0'
    };
    
    console.log('reloadTableData - Params:', params);
    
    const queryString = new URLSearchParams(params).toString();
    const url = 'pages/ajax/search_filter.php?' + queryString;
    
    showLoader();
    
    fetch(url, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            
            hideLoader();
            
            if (data.success) {
                updateTableContent(data.data.creances);
                
                if (data.data.pagination) {
                    updatePaginationInfo(data.data.pagination);
                    updateResultsCount(data.data.pagination.total_count);
                }
            } else {alert('Erreur: ' + data.error);
            }
        } catch (e) {
            hideLoader();
            alert('Erreur: Réponse serveur invalide');
        }
    })
    .catch(error => {
        hideLoader();
        alert('Erreur de connexion: ' + error.message);
    });
}

function updateTableContent(creances) {
    const tbody = document.querySelector('#creancesTable tbody');
    
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (!creances || creances.length === 0) {
        const emptyRow = document.createElement('tr');
        emptyRow.innerHTML = `
            <td colspan="15" style="text-align: center; padding: 2rem;">
                <div class="empty-state">
                    <i class="fas fa-inbox" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                    <h3>Aucune créance trouvée</h3>
                    <p>Aucune créance ne correspond à vos critères de recherche.</p>
                </div>
            </td>
        `;
        tbody.appendChild(emptyRow);
        return;
    }
    
    creances.forEach((ligne) => {
        const tr = document.createElement('tr');
        tr.setAttribute('data-id', ligne.id);
        tr.setAttribute('data-version', ligne.version);
        
        const observationHtml = ligne.observation ? 
            `<span class="badge badge-${getBadgeClass(ligne.observation)}">${escapeHtml(ligne.observation)}</span>` : 
            '';
        
        tr.innerHTML = `
            <td>${escapeHtml(ligne.region || '')}</td>
            <td>${escapeHtml(ligne.secteur || '')}</td>
            <td title="${escapeHtml(ligne.client || '')}">
                ${escapeHtml(truncateText(ligne.client || '', 30))}
            </td>
            <td title="${escapeHtml(ligne.intitule_marche || '')}">
                ${escapeHtml(truncateText(ligne.intitule_marche || '', 40))}
            </td>
            <td>${escapeHtml(ligne.num_facture_situation || '')}</td>
            <td>${escapeHtml(ligne.date_str || '')}</td>
            <td>${escapeHtml(ligne.nature || '')}</td>
            <td class="amount">${formatNumber(ligne.montant_total, 2)}</td>
            <td class="amount">${ligne.montant_creance == 0 ? '' : formatNumber(ligne.encaissement, 2)}</td>
            <td class="amount">${formatNumber(ligne.montant_creance, 2)}</td>
            <td class="age">${ligne.age_annees || 0} ans</td>
            <td class="percentage">${formatNumber(ligne.pct_provision, 1)}%</td>
            <td class="amount">${formatNumber(ligne.provision_2024, 2)}</td>
            <td class="observation">${observationHtml}</td>
            <td class="actions">
                <div class="action-buttons">
                    <button class="btn-action btn-edit" onclick="editCreance(${ligne.id})" title="Modifier">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-action btn-archive" onclick="archiveCreance(${ligne.id})" title="Archiver">
                        <i class="fas fa-archive"></i>
                    </button>
                </div>
            </td>
        `;
        
        tbody.appendChild(tr);
    });
}

function updatePaginationInfo(pagination) {
    const infoElement = document.querySelector('.pagination-info');
    if (infoElement) {
        infoElement.textContent = `Affichage de ${pagination.start_index} à ${pagination.end_index} sur ${formatNumber(pagination.total_count, 0)} résultats`;
    }
}

function updateResultsCount(count) {
    const countElement = document.querySelector('.results-count');
    if (countElement) {
        countElement.textContent = `${formatNumber(count, 0)} résultat(s)`;
    }
}

// ============================================
// FONCTIONS UTILITAIRES
// ============================================

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function truncateText(text, maxLength) {
    if (!text) return '';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}

function formatNumber(num, decimals = 0) {
    if (num === null || num === undefined) return '0';
    return new Intl.NumberFormat('fr-DZ', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(num);
}

function getBadgeClass(observation) {
    switch(observation) {
        case 'CONTENTIEUX': return 'danger';
        case 'PRE-CONTENTIEUX': return 'warning';
        case 'ACTION JURIDIQUE': return 'info';
        default: return 'secondary';
    }
}

function showLoader() {
    let loader = document.getElementById('tableLoader');
    if (!loader) {
        loader = document.createElement('div');
        loader.id = 'tableLoader';
        loader.className = 'table-loader';
        loader.innerHTML = `
            <div class="loader-spinner">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Chargement...</span>
            </div>
        `;
        document.querySelector('.table-container').appendChild(loader);
    }
    loader.style.display = 'flex';
}

function hideLoader() {
    const loader = document.getElementById('tableLoader');
    if (loader) {
        loader.style.display = 'none';
    }
}

// ============================================
// FONCTIONS FORMULAIRE ET MODAL
// ============================================

function openFormModal() {
    document.getElementById('formModalTitle').textContent = 'Ajouter une créance';
    document.getElementById('formIframe').src = 'pages/form.php?action=add&embedded=1';
    document.getElementById('formModalIframeBG').style.display = 'flex';
}

function editCreance(id) {
    document.getElementById('formModalTitle').textContent = 'Modifier une créance';
    document.getElementById('formIframe').src = `pages/form.php?action=edit&id=${id}&embedded=1`;
    document.getElementById('formModalIframeBG').style.display = 'flex';
}

function closeFormModal() {
    document.getElementById('formModalIframeBG').style.display = 'none';
    document.getElementById('formIframe').src = 'about:blank';
}

window.addEventListener('message', function(ev) {
    try {
        const data = (typeof ev.data === 'string') ? JSON.parse(ev.data) : ev.data;
        if (!data || !data.type) return;

        if (data.type === 'creanceSaved') {
            closeFormModal();
            reloadTableData();
            showNotification('Créance enregistrée avec succès', 'success');
        } 
        else if (data.type === 'closeModal') {
            closeFormModal();
        }
    } catch (e) {
        console.warn('Message reçu invalide', e);
    }
}, false);

function archiveCreance(id) {
    showConfirm(
        'Confirmer l\'archivage',
        'Êtes-vous sûr de vouloir archiver cette créance ?',
        () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="archive">
                <input type="hidden" name="id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    );
}

function sortTable(column) {
    if (currentSort === column) {
        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        currentSort = column;
        sortDirection = 'asc';
    }
    
    const params = new URLSearchParams(window.location.search);
    params.set('sort', column);
    params.set('dir', sortDirection);
    window.location.search = params.toString();
}

function changePageSize(size) {
    const params = new URLSearchParams(window.location.search);
    params.set('limit', size);
    params.set('page', 1);
    window.location.search = params.toString();
}

function showStatsModal() {
    document.getElementById('statsModal').style.display = 'flex';
}

function closeStatsModal() {
    document.getElementById('statsModal').style.display = 'none';
}

function exportPDF() {
    const includeCharts = confirm('Voulez-vous inclure des graphiques dans le PDF ?');
    window.location.href = `pages/ajax/generate_pdf.php?charts=${includeCharts ? '1' : '0'}`;
}

function exportExcel() {
    window.location.href = 'pages/ajax/export_excel.php';
}

function exportCSV() {
    window.location.href = 'pages/ajax/export_csv.php';
}

// ============================================
// FONCTIONS FILTRES
// ============================================

function resetFilters() {
    activeFilters = {};
    document.querySelectorAll('.filter-option input[type="checkbox"]').forEach(cb => cb.checked = true);
    
    <?php foreach (COLONNES_FILTRABLES as $col): ?>
        const lbl_<?php echo $col; ?> = document.getElementById('filter-<?php echo $col; ?>-text');
        if (lbl_<?php echo $col; ?>) lbl_<?php echo $col; ?>.textContent = 'Tous';
    <?php endforeach; ?>

    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
        searchInput.classList.remove('has-results', 'no-results');
    }

    const searchIndicator = document.querySelector('.search-indicator');
    if (searchIndicator) searchIndicator.remove();
    
    const filterIndicator = document.querySelector('.filter-indicator');
    if (filterIndicator) filterIndicator.remove();

    const params = new URLSearchParams(window.location.search);
    params.delete('search');
    params.set('page', 1);
    const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    window.history.replaceState({}, '', newUrl);

    fetch('pages/ajax/set_filters.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({filters: {}})
    })
    .then(() => reloadTableData())
    .catch(() => reloadTableData());
}

function toggleFilterDropdown(column) {
    const dropdown = document.getElementById(`filter-${column}-options`);
    const isVisible = dropdown.style.display === 'block';
    
    document.querySelectorAll('.filter-options').forEach(d => d.style.display = 'none');
    dropdown.style.display = isVisible ? 'none' : 'block';
}

function filterOptions(column, searchText) {
    const list = document.getElementById(`filter-${column}-list`);
    const options = list.querySelectorAll('.filter-option');
    
    options.forEach(option => {
        const text = option.textContent.toLowerCase();
        option.style.display = text.includes(searchText.toLowerCase()) ? 'block' : 'none';
    });
}

function selectAllOptions(column, checked) {
    const checkboxes = document.querySelectorAll(`#filter-${column}-list input[type="checkbox"]`);
    checkboxes.forEach(cb => {
        if (cb.parentElement.style.display !== 'none') {
            cb.checked = checked;
        }
    });
}

function updateFilter(column) {
    // Cette fonction est appelée quand une checkbox change
}

const COLUMN_MAPPING = {
    'REGION': 'REGION',
    'SECTEUR': 'SECTEUR',
    'CLIENT': 'CLIENT',
    'NATURE': 'NATURE',
    'DATE': 'date_str'
};

function convertFiltersToBackend(filters) {
    const converted = {};
    for (const [key, value] of Object.entries(filters)) {
        const backendKey = COLUMN_MAPPING[key] || key;
        converted[backendKey] = value;
    }
    return converted;
}

function applyFilter(column) {
    const checkboxes = document.querySelectorAll(`#filter-${column}-list input[type="checkbox"]:checked`);
    const values = Array.from(checkboxes).map(cb => cb.value);
    const totalOptions = document.querySelectorAll(`#filter-${column}-list input[type="checkbox"]`).length;
    
    if (values.length === 0) {
        activeFilters[column] = [];
        document.getElementById(`filter-${column}-text`).textContent = 'Aucun';
    } else if (values.length === totalOptions) {
        delete activeFilters[column];
        document.getElementById(`filter-${column}-text`).textContent = 'Tous';
    } else {
        activeFilters[column] = values;
        document.getElementById(`filter-${column}-text`).textContent = `${values.length} sélectionné(s)`;
    }
    
    toggleFilterDropdown(column);
    showLoader();

    fetch('pages/ajax/set_filters.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({filters: convertFiltersToBackend(activeFilters)})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            reloadTableData();
        } else {
            hideLoader();
            alert('Erreur lors de l\'application du filtre');
        }
    })
    .catch(error => {
        hideLoader();
        console.error('Erreur:', error);
        alert('Erreur de connexion au serveur');
    });
}

function clearFilter(column) {
    document.querySelectorAll(`#filter-${column}-list input[type="checkbox"]`).forEach(cb => cb.checked = false);
    activeFilters[column] = [];
    document.getElementById(`filter-${column}-text`).textContent = 'Tous';
    reloadTableData();
}

function toggleFilters() {
    const content = document.getElementById('filtersContent');
    const toggle = document.getElementById('filtersToggle');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        toggle.classList.remove('fa-chevron-down');
        toggle.classList.add('fa-chevron-up');
    } else {
        content.style.display = 'none';
        toggle.classList.remove('fa-chevron-up');
        toggle.classList.add('fa-chevron-down');
    }
}

function toggleDropdown(id) {
    const dropdown = document.getElementById(id);
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

// Fermer les dropdowns en cliquant à l'extérieur
document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown') && !event.target.closest('.filter-dropdown')) {
        document.querySelectorAll('.dropdown-menu, .filter-options').forEach(d => {
            d.style.display = 'none';
        });
    }
});

</script>
