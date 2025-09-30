/**
 * JavaScript principal de l'application
 * Gestion des Créances - Version Web
 */

// Variables globales
let confirmCallback = null;

/**
 * Initialisation de l'application
 */
function initializeApp() {
    // Initialiser les événements du formulaire
    initFormEvents();
    
    // Initialiser la validation des dates
    initDateValidation();
    
    // Initialiser les tooltips
    initTooltips();
    
    // Charger les clients dynamiquement
    loadClients();
}

/**
 * Initialiser les événements du formulaire
 */
function initFormEvents() {
    // Checkbox "Nouveau" pour région
    const regionNouveau = document.getElementById('regionNouveau');
    if (regionNouveau) {
        regionNouveau.addEventListener('change', function() {
            const regionSelect = document.getElementById('region');
            const regionNew = document.getElementById('regionNew');
            
            if (this.checked) {
                regionSelect.disabled = true;
                regionSelect.removeAttribute('required');
                regionNew.style.display = 'block';
                regionNew.setAttribute('required', 'required');
                regionNew.setAttribute('name', 'region');
                regionSelect.removeAttribute('name');
            } else {
                regionSelect.disabled = false;
                regionSelect.setAttribute('required', 'required');
                regionNew.style.display = 'none';
                regionNew.removeAttribute('required');
                regionNew.removeAttribute('name');
                regionSelect.setAttribute('name', 'region');
            }
        });
    }
    
    // Checkbox "Nouveau" pour client
    const clientNouveau = document.getElementById('clientNouveau');
    if (clientNouveau) {
        clientNouveau.addEventListener('change', function() {
            const clientSelect = document.getElementById('client');
            const clientNew = document.getElementById('clientNew');
            
            if (this.checked) {
                clientSelect.disabled = true;
                clientSelect.removeAttribute('required');
                clientNew.style.display = 'block';
                clientNew.setAttribute('required', 'required');
                clientNew.setAttribute('name', 'client');
                clientSelect.removeAttribute('name');
            } else {
                clientSelect.disabled = false;
                clientSelect.setAttribute('required', 'required');
                clientNew.style.display = 'none';
                clientNew.removeAttribute('required');
                clientNew.removeAttribute('name');
                clientSelect.setAttribute('name', 'client');
            }
        });
    }
    
    // Checkbox "Include" pour observation
    const observationInclude = document.getElementById('observationInclude');
    if (observationInclude) {
        observationInclude.addEventListener('change', function() {
            const observation = document.getElementById('observation');
            if (this.checked) {
                observation.disabled = false;
            } else {
                observation.disabled = true;
                observation.value = '';
            }
        });
    }
    
    // Checkbox "Zéro" pour encaissement
    const encaissementZero = document.getElementById('encaissementZero');
    if (encaissementZero) {
        encaissementZero.addEventListener('change', function() {
            const encaissement = document.getElementById('encaissement');
            const montantTotal = document.getElementById('montant_total');
            
            if (this.checked) {
                encaissement.disabled = true;
                encaissement.value = montantTotal.value || 0;
            } else {
                encaissement.disabled = false;
                encaissement.value = 0;
            }
        });
    }
    
    // Validation du montant total et encaissement
    const montantTotal = document.getElementById('montant_total');
    const encaissement = document.getElementById('encaissement');
    
    if (montantTotal && encaissement) {
        montantTotal.addEventListener('change', function() {
            if (encaissementZero && encaissementZero.checked) {
                encaissement.value = this.value;
            }
            validateMontants();
        });
        
        encaissement.addEventListener('change', validateMontants);
    }
    
    // Soumission du formulaire
    const creanceForm = document.getElementById('creanceForm');
    if (creanceForm) {
        creanceForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (validateForm()) {
                this.submit();
            }
        });
    }
}

/**
 * Validation des montants
 */
function validateMontants() {
    const montantTotal = parseFloat(document.getElementById('montant_total').value) || 0;
    const encaissement = parseFloat(document.getElementById('encaissement').value) || 0;
    
    if (encaissement > montantTotal) {
        alert('L\'encaissement ne peut pas être supérieur au montant total.');
        document.getElementById('encaissement').value = montantTotal;
        return false;
    }
    
    return true;
}

/**
 * Validation du formulaire complet
 */
function validateForm() {
    // Valider la date
    const dateStr = document.getElementById('date_str').value;
    if (!validateDate(dateStr)) {
        alert('Format de date invalide. Utilisez DD/MM/YYYY');
        return false;
    }
    
    // Valider les montants
    if (!validateMontants()) {
        return false;
    }
    
    return true;
}

/**
 * Initialiser la validation des dates
 */
function initDateValidation() {
    const dateInput = document.getElementById('date_str');
    if (dateInput) {
        dateInput.addEventListener('blur', function() {
            if (this.value && !validateDate(this.value)) {
                this.classList.add('error');
                alert('Format de date invalide. Utilisez DD/MM/YYYY');
            } else {
                this.classList.remove('error');
            }
        });
        
        // Auto-formatter la date
        dateInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2);
            }
            if (value.length >= 5) {
                value = value.slice(0, 5) + '/' + value.slice(5, 9);
            }
            
            e.target.value = value;
        });
    }
}

/**
 * Valider le format de date DD/MM/YYYY
 */
function validateDate(dateStr) {
    const regex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
    const match = dateStr.match(regex);
    
    if (!match) return false;
    
    const day = parseInt(match[1], 10);
    const month = parseInt(match[2], 10);
    const year = parseInt(match[3], 10);
    
    if (month < 1 || month > 12) return false;
    if (day < 1 || day > 31) return false;
    
    const date = new Date(year, month - 1, day);
    return date.getFullYear() === year && 
           date.getMonth() === month - 1 && 
           date.getDate() === day;
}

/**
 * Charger la liste des clients
 */
function loadClients() {
    fetch('pages/ajax/get_clients.php')
        .then(response => response.json())
        .then(data => {
            const clientSelect = document.getElementById('client');
            if (clientSelect && data.success) {
                clientSelect.innerHTML = '<option value="">Sélectionner...</option>';
                data.clients.forEach(client => {
                    const option = document.createElement('option');
                    option.value = client;
                    option.textContent = client;
                    clientSelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading clients:', error));
}

/**
 * Ouvrir le modal
 */
function openModal() {
    document.getElementById('formModal').style.display = 'flex';
}

/**
 * Fermer le modal
 */
function closeModal() {
    document.getElementById('formModal').style.display = 'none';
    document.getElementById('creanceForm').reset();
}

/**
 * Afficher le modal de confirmation
 */
function showConfirm(title, message, callback) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    confirmCallback = callback;
    
    document.getElementById('confirmModal').style.display = 'flex';
    
    // Attacher l'événement au bouton "Oui"
    document.getElementById('confirmYes').onclick = function() {
        if (confirmCallback) {
            confirmCallback();
        }
        closeConfirmModal();
    };
}

/**
 * Fermer le modal de confirmation
 */
function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
    confirmCallback = null;
}

/**
 * Initialiser les tooltips
 */
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            tooltip.style.position = 'absolute';
            tooltip.style.zIndex = '10000';
            
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
            tooltip.style.left = (rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2)) + 'px';
            
            this._tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                this._tooltip = null;
            }
        });
    });
}

/**
 * Formater un nombre avec séparateurs de milliers
 */
function formatNumber(num, decimals = 2) {
    return parseFloat(num).toLocaleString('fr-FR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

/**
 * Débounce function pour optimiser les événements
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Copier dans le presse-papiers
 */
function copyToClipboard(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
        document.execCommand('copy');
        showNotification('Copié dans le presse-papiers', 'success');
    } catch (err) {
        console.error('Error copying to clipboard:', err);
        showNotification('Erreur lors de la copie', 'error');
    }
    
    document.body.removeChild(textarea);
}

/**
 * Afficher une notification
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `toast-notification toast-${type}`;
    
    const icon = type === 'success' ? 'check-circle' : 
                 type === 'error' ? 'exclamation-circle' : 
                 'info-circle';
    
    notification.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <span>${message}</span>
    `;

    // Style inline (ou ajouter au CSS)
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-weight: 500;
        animation: slideInRight 0.3s ease;
        max-width: 400px;
    `;
    
    document.body.appendChild(notification);
    
    // Auto-suppression après 3 secondes
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Ajouter les animations CSS (si pas déjà dans style.css)
const style = document.createElement('style');
style.textContent = `
@keyframes slideInRight {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutRight {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(400px);
        opacity: 0;
    }
}
`;
document.head.appendChild(style);

/**
 * Fermer les modals en cliquant à l'extérieur
 */
window.addEventListener('click', function(event) {
    const formModal = document.getElementById('formModal');
    const confirmModal = document.getElementById('confirmModal');
    const statsModal = document.getElementById('statsModal');
    
    if (event.target === formModal) {
        closeModal();
    }
    if (event.target === confirmModal) {
        closeConfirmModal();
    }
    if (event.target === statsModal) {
        statsModal.style.display = 'none';
    }
});

/**
 * Gestion de la touche Escape
 */
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
        closeConfirmModal();
        const statsModal = document.getElementById('statsModal');
        if (statsModal) {
            statsModal.style.display = 'none';
        }
    }
});

// Export des fonctions pour utilisation globale
window.initializeApp = initializeApp;
window.openModal = openModal;
window.closeModal = closeModal;
window.showConfirm = showConfirm;
window.closeConfirmModal = closeConfirmModal;
window.loadClients = loadClients;
window.formatNumber = formatNumber;
window.copyToClipboard = copyToClipboard;
window.showNotification = showNotification;