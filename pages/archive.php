<?php
/**
 * Page Archive - Tableau des créances archivées
 */

$creance = new Creance();
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(MAX_ITEMS_PER_PAGE, max(10, (int)($_GET['limit'] ?? ITEMS_PER_PAGE)));
$search = trim($_GET['search'] ?? '');

try {
    $donnees = $creance->getAll([], STATUS_ARCHIVED, $page, $limit, $search);
    $totalCount = $creance->getCount([], STATUS_ARCHIVED, $search);
    $totalPages = ceil($totalCount / $limit);
} catch (Exception $e) {
    $donnees = [];
    $totalCount = 0;
    $totalPages = 0;
    $error = $e->getMessage();
}
?>

<div class="main-content">
    <div class="toolbar">
        <div class="toolbar-left">
            <h2><i class="fas fa-archive"></i> Archives</h2>
        </div>
        <div class="toolbar-right">
            <div class="results-info">
                <span class="results-count"><?php echo number_format($totalCount); ?> créance(s) archivée(s)</span>
            </div>
        </div>
    </div>
    
    <div class="table-container">
        <?php if (empty($donnees)): ?>
            <div class="empty-state">
                <i class="fas fa-archive"></i>
                <h3>Aucune créance archivée</h3>
                <p>Les créances archivées apparaîtront ici.</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <?php foreach (COLONNES as $col): ?>
                                <th><?php echo $col; ?></th>
                            <?php endforeach; ?>
                            <th class="actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($donnees as $ligne): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ligne['region']); ?></td>
                                <td><?php echo htmlspecialchars($ligne['secteur']); ?></td>
                                <td><?php echo htmlspecialchars(mb_substr($ligne['client'], 0, 30) . (mb_strlen($ligne['client']) > 30 ? '...' : '')); ?></td>
                                <td><?php echo htmlspecialchars(mb_substr($ligne['intitule_marche'], 0, 40) . (mb_strlen($ligne['intitule_marche']) > 40 ? '...' : '')); ?></td>
                                <td><?php echo htmlspecialchars($ligne['num_facture_situation']); ?></td>
                                <td><?php echo htmlspecialchars($ligne['date_str']); ?></td>
                                <td><?php echo htmlspecialchars($ligne['nature']); ?></td>
                                <td class="amount"><?php echo number_format($ligne['montant_total'], 2, ',', ' '); ?></td>
                                <td class="amount"><?php echo $ligne['montant_creance'] == 0 ? '' : number_format($ligne['encaissement'], 2, ',', ' '); ?></td>
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
                                        <button class="btn-action btn-success" onclick="restoreCreance(<?php echo $ligne['id']; ?>)" title="Restaurer">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                        <button class="btn-action btn-delete" onclick="deleteCreance(<?php echo $ligne['id']; ?>)" title="Supprimer définitivement">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination-info">
                        Affichage de <?php echo ($page - 1) * $limit + 1; ?> à 
                        <?php echo min($page * $limit, $totalCount); ?> sur <?php echo number_format($totalCount); ?> résultats
                    </div>
                    
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?view=archive&page=1&limit=<?php echo $limit; ?>" class="pagination-link"><i class="fas fa-angle-double-left"></i></a>
                            <a href="?view=archive&page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>" class="pagination-link"><i class="fas fa-angle-left"></i></a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?view=archive&page=<?php echo $i; ?>&limit=<?php echo $limit; ?>" 
                               class="pagination-link <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?view=archive&page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>" class="pagination-link"><i class="fas fa-angle-right"></i></a>
                            <a href="?view=archive&page=<?php echo $totalPages; ?>&limit=<?php echo $limit; ?>" class="pagination-link"><i class="fas fa-angle-double-right"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function restoreCreance(id) {
    showConfirm('Confirmer la restauration', 'Êtes-vous sûr de vouloir restaurer cette créance ?', () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="action" value="restore"><input type="hidden" name="id" value="${id}">`;
        document.body.appendChild(form);
        form.submit();
    });
}

function deleteCreance(id) {
    showConfirm('Confirmer la suppression', 'Êtes-vous sûr de vouloir supprimer DÉFINITIVEMENT cette créance ? Cette action est IRRÉVERSIBLE !', () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${id}">`;
        document.body.appendChild(form);
        form.submit();
    });
}
</script>