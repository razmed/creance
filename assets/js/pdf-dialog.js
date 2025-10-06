/**
 * Dialogue de sélection des visualisations PDF
 * Gestion complète du dialogue et de la génération
 */

/**
 * Dialogue de sélection des visualisations PDF
 * Version avec support "Aucun graphique"
 */

function showPDFVisualizationDialog() {
    checkRadarAvailability().then(canShowRadar => {
        const dialog = document.createElement('div');
        dialog.id = 'pdfVisualizationDialog';
        dialog.className = 'modal';
        dialog.style.display = 'flex';
        
        dialog.innerHTML = `
            <div class="modal-content modal-medium">
                <div class="modal-header">
                    <h3><i class="fas fa-chart-pie"></i> Visualisations PDF</h3>
                    <button type="button" class="modal-close" onclick="closePDFDialog()">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="dialog-intro">Sélectionnez les visualisations à inclure dans le PDF :</p>
                    
                    <div class="visualization-options">
                        <label class="viz-option">
                            <input type="checkbox" id="opt_bar_chart" name="bar_chart">
                            <div class="viz-info">
                                <div class="viz-icon">
                                    <i class="fas fa-chart-bar" style="color: #0088FE;"></i>
                                </div>
                                <div class="viz-details">
                                    <span class="viz-title">Bar Chart par Région</span>
                                    <span class="viz-desc">Créances vs Provisions par région</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="viz-option">
                            <input type="checkbox" id="opt_pie_chart" name="pie_chart">
                            <div class="viz-info">
                                <div class="viz-icon">
                                    <i class="fas fa-chart-pie" style="color: #00C49F;"></i>
                                </div>
                                <div class="viz-details">
                                    <span class="viz-title">Diagramme Circulaire par Secteur</span>
                                    <span class="viz-desc">Répartition des créances par secteur</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="viz-option ${canShowRadar ? '' : 'disabled'}">
                            <input type="checkbox" id="opt_radar_chart" name="radar_chart" 
                                   ${canShowRadar ? '' : 'disabled'}>
                            <div class="viz-info">
                                <div class="viz-icon">
                                    <i class="fas fa-project-diagram" style="color: #FF8042;"></i>
                                </div>
                                <div class="viz-details">
                                    <span class="viz-title">Spider Radar par Nature</span>
                                    <span class="viz-desc">
                                        ${canShowRadar 
                                            ? 'Créances et provisions par nature' 
                                            : 'Non disponible (minimum 3 natures requises)'}
                                    </span>
                                </div>
                            </div>
                        </label>
                    </div>
                    
                    <div class="dialog-note">
                        <i class="fas fa-info-circle"></i>
                        <span>Si aucun graphique n'est sélectionné, seul le tableau des données sera exporté.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="generatePDFWithOptions()">
                        <i class="fas fa-file-pdf"></i> Générer le PDF
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closePDFDialog()">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(dialog);
    });
}

/**
 * Vérifier si le radar chart peut être généré
 */
async function checkRadarAvailability() {
    try {
        const response = await fetch('pages/ajax/check_radar.php');
        const data = await response.json();
        return data.canGenerate === true;
    } catch (error) {
        console.error('Erreur vérification radar:', error);
        return false;
    }
}

/**
 * Générer le PDF avec les options sélectionnées
 */
function generatePDFWithOptions() {
    // Récupérer les valeurs des checkboxes
    const barChart = document.getElementById('opt_bar_chart')?.checked ? '1' : '0';
    const pieChart = document.getElementById('opt_pie_chart')?.checked ? '1' : '0';
    const radarChart = document.getElementById('opt_radar_chart')?.checked ? '1' : '0';
    
    
    // Afficher l'indicateur de chargement
    showLoadingIndicator('Génération du PDF en cours...');
    
    // Préparer les données du formulaire
    const formData = new FormData();
    formData.append('bar_chart', barChart);
    formData.append('pie_chart', pieChart);
    formData.append('radar_chart', radarChart);
    formData.append('archived', '0');
    
    // Envoyer la requête AJAX
    fetch('pages/ajax/generate_pdf.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur HTTP: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        hideLoadingIndicator();
        
        if (data.success) {
            closePDFDialog();
            
            // Télécharger automatiquement le fichier
            const link = document.createElement('a');
            link.href = data.download_url;
            link.download = data.filename;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            
            // Nettoyer après un délai
            setTimeout(() => {
                document.body.removeChild(link);
            }, 100);

            // Message adapté selon le nombre de graphiques
            const chartMsg = data.charts_included > 0 
                ? ` avec ${data.charts_included} graphique(s)` 
                : ' (tableau uniquement)';
            
            showNotification('PDF généré avec succès ! (' + data.size + ')', 'success');
        } else {
            showNotification('Erreur : ' + data.error, 'error');
        }
    })
    .catch(error => {
        hideLoadingIndicator();
        console.error('Erreur génération PDF:', error);
        showNotification('Erreur lors de la génération du PDF: ' + error.message, 'error');
    });
}

/**
 * Fermer le dialogue
 */
function closePDFDialog() {
    const dialog = document.getElementById('pdfVisualizationDialog');
    if (dialog) {
        dialog.remove();
    }
}

/**
 * Afficher l'indicateur de chargement
 */
function showLoadingIndicator(message) {
    // Supprimer l'ancien loader s'il existe
    hideLoadingIndicator();
    
    const loader = document.createElement('div');
    loader.id = 'pdfLoadingIndicator';
    loader.className = 'loading-overlay';
    loader.innerHTML = `
        <div class="loading-content">
            <div class="spinner"></div>
            <p>${message}</p>
        </div>
    `;
    document.body.appendChild(loader);
}

/**
 * Masquer l'indicateur de chargement
 */
function hideLoadingIndicator() {
    const loader = document.getElementById('pdfLoadingIndicator');
    if (loader) {
        loader.remove();
    }
}

// Rendre les fonctions disponibles globalement
window.showPDFVisualizationDialog = showPDFVisualizationDialog;
window.closePDFDialog = closePDFDialog;
window.generatePDFWithOptions = generatePDFWithOptions;