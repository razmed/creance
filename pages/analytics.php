<?php
/**
 * Page Analytics - Visualisations des données avec Diagramme de Sankey
 * Version avec exclusion des créances à 0 du Sankey mais affichage dans le tableau
 */

$creance = new Creance();
$groupBy = $_GET['groupby'] ?? 'region';
$validGroupBy = ['region', 'secteur', 'nature', 'age_annees'];

if (!in_array($groupBy, $validGroupBy)) {
    $groupBy = 'region';
}

try {
    // Récupérer TOUTES les données (avec les 0)
    $analyticsDataComplete = $creance->getAnalyticsData($groupBy);
    
    // Créer une version filtrée SANS les montants à 0 (pour le Sankey uniquement)
    $analyticsDataFiltered = array_filter($analyticsDataComplete, function($item) {
        return isset($item['montant']) && floatval($item['montant']) > 0;
    });
    
    // Réindexer le tableau filtré
    $analyticsDataFiltered = array_values($analyticsDataFiltered);
    
} catch (Exception $e) {
    $analyticsDataComplete = [];
    $analyticsDataFiltered = [];
    $error = $e->getMessage();
}
?>

<div class="main-content">
    <div class="toolbar">
        <div class="toolbar-left">
            <h2><i class="fas fa-chart-bar"></i> Analytics - Montant Créance</h2>
        </div>
        <div class="toolbar-right">
            <div class="btn-group">
                <button class="btn <?php echo $groupBy === 'region' ? 'btn-primary' : 'btn-secondary'; ?>" 
                        onclick="location.href='?view=analytics&groupby=region'">Région</button>
                <button class="btn <?php echo $groupBy === 'secteur' ? 'btn-primary' : 'btn-secondary'; ?>" 
                        onclick="location.href='?view=analytics&groupby=secteur'">Secteur</button>
                <button class="btn <?php echo $groupBy === 'nature' ? 'btn-primary' : 'btn-secondary'; ?>" 
                        onclick="location.href='?view=analytics&groupby=nature'">Nature</button>
                <button class="btn <?php echo $groupBy === 'age_annees' ? 'btn-primary' : 'btn-secondary'; ?>" 
                        onclick="location.href='?view=analytics&groupby=age_annees'">Âge</button>
            </div>
            <button class="btn btn-success" onclick="exportChart()">
                <i class="fas fa-download"></i> Export PNG
            </button>
            <button class="btn btn-info" onclick="regenerateChart()">
                <i class="fas fa-random"></i> Remélanger
            </button>
        </div>
    </div>
    
    <?php if (empty($analyticsDataComplete)): ?>
        <div class="empty-state">
            <i class="fas fa-chart-bar"></i>
            <h3>Aucune donnée à afficher</h3>
            <p>Il n'y a pas de données pour générer les graphiques.</p>
        </div>
    <?php else: ?>
        <div class="analytics-container">
            <!-- Graphique Sankey principal -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3>Répartition des créances par <?php echo ucfirst($groupBy === 'age_annees' ? 'âge' : $groupBy); ?></h3>
                    <small>Nœuds proportionnels - Créances à 0 exclues du diagramme</small>
                </div>
                <div class="chart-body">
                    <?php if (empty($analyticsDataFiltered)): ?>
                        <div class="empty-state">
                            <i class="fas fa-chart-bar"></i>
                            <p>Toutes les créances ont un montant à 0.<br/>Impossible d'afficher le diagramme Sankey.</p>
                        </div>
                    <?php else: ?>
                        <div id="sankeyChart" style="width: 100%; min-height: 500px; height: auto;"></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tableau récapitulatif avec TOUTES les données -->
            <div class="stats-card">
                <div class="stats-header">
                    <h3>Détails des données</h3>
                </div>
                <div class="stats-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><?php echo ucfirst($groupBy === 'age_annees' ? 'Âge' : $groupBy); ?></th>
                                <th>Montant Total</th>
                                <th>Nombre</th>
                                <th>Pourcentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total = array_sum(array_column($analyticsDataComplete, 'montant'));
                            foreach ($analyticsDataComplete as $row): 
                                $percentage = $total > 0 ? ($row['montant'] / $total) * 100 : 0;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['label']); ?></td>
                                    <td class="amount"><?php echo number_format($row['montant'], 0, ',', ' '); ?> DZD</td>
                                    <td class="center"><?php echo $row['count']; ?></td>
                                    <td class="center">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                            <span class="progress-text"><?php echo number_format($percentage, 1); ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="total-row">
                                <td><strong>TOTAL</strong></td>
                                <td class="amount"><strong><?php echo number_format($total, 0, ',', ' '); ?> DZD</strong></td>
                                <td class="center"><strong><?php echo array_sum(array_column($analyticsDataComplete, 'count')); ?></strong></td>
                                <td class="center"><strong>100%</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.analytics-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.chart-card, .stats-card {
    background: white;
    border-radius: 8px;
    box-shadow: var(--shadow);
    overflow: hidden;
}

.chart-header, .stats-header {
    padding: 1rem;
    background: linear-gradient(135deg, #f5f5f5, #e0e0e0);
    border-bottom: 1px solid var(--border-color);
}

.chart-header small {
    display: block;
    margin-top: 5px;
    color: #666;
    font-style: italic;
}

.chart-body, .stats-body {
    padding: 1.5rem;
}

.btn-group {
    display: flex;
    gap: 0;
}

.btn-group .btn {
    border-radius: 0;
}

.btn-group .btn:first-child {
    border-radius: 6px 0 0 6px;
}

.btn-group .btn:last-child {
    border-radius: 0 6px 6px 0;
}

.progress-bar {
    position: relative;
    height: 24px;
    background: #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
}

.progress-fill {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background: linear-gradient(90deg, var(--primary-color), #1976D2);
    transition: width 0.3s;
}

.progress-text {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    font-weight: 600;
    font-size: 0.75rem;
    color: white;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.total-row {
    background: #f5f5f5;
    border-top: 2px solid var(--primary-color);
}

#sankeyChart {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.sankey-node rect {
    cursor: pointer;
    stroke: none;
    transition: all 0.3s ease;
    filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.25));
}

.sankey-node rect:hover {
    filter: drop-shadow(3px 3px 6px rgba(0,0,0,0.4)) brightness(1.05);
    transform: scale(1.02);
}

.sankey-node text {
    pointer-events: none;
    text-shadow: 0 1px 2px rgba(255,255,255,0.8);
    font-size: 12px;
    font-weight: 600;
    fill: #2c3e50;
    transition: all 0.3s ease;
}

.sankey-link {
    fill: none;
    cursor: pointer;
    transition: all 0.3s ease;
    stroke-opacity: 0.7;
}

.sankey-link:hover {
    filter: brightness(1.1);
    stroke-opacity: 0.9;
}

.tooltip {
    position: absolute;
    text-align: left;
    width: auto;
    height: auto;
    padding: 12px 15px;
    font: 13px 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, rgba(44, 62, 80, 0.95), rgba(52, 73, 94, 0.95));
    color: white;
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 10px;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.3s ease;
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    backdrop-filter: blur(10px);
    max-width: 250px;
    z-index: 1000;
}

.export-mode .sankey-node rect {
    stroke: none !important;
    stroke-width: 0 !important;
    filter: drop-shadow(1px 1px 3px rgba(0,0,0,0.15)) !important;
}

.export-mode .sankey-link {
    stroke-opacity: 0.75 !important;
}

.export-mode text {
    text-shadow: 1px 1px 1px rgba(255,255,255,0.9) !important;
}

@media (max-width: 1024px) {
    .analytics-container {
        grid-template-columns: 1fr;
    }
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.8.5/d3.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/d3-sankey/0.12.3/d3-sankey.min.js"></script>

<script>
// IMPORTANT : Deux ensembles de données distincts
// - analyticsDataFiltered : pour le diagramme Sankey (SANS les 0)
// - analyticsDataComplete : pour le tableau (AVEC les 0)
const analyticsDataFiltered = <?php echo json_encode($analyticsDataFiltered); ?>;
const analyticsDataComplete = <?php echo json_encode($analyticsDataComplete); ?>;

console.log('Données filtrées (Sankey):', analyticsDataFiltered.length, 'entrées');
console.log('Données complètes (Tableau):', analyticsDataComplete.length, 'entrées');

// Palette de couleurs
const colors = [
    '#9B59B6', '#8E44AD', '#6A1B9A', '#4A148C',
    '#00E676', '#00C853', '#00BCD4', '#26C6DA',
    '#E91E63', '#EC407A', '#F06292', '#F48FB1',
    '#FF6B35', '#FF8A65', '#FFB74D', '#FFCC02',
    '#7B1FA2', '#673AB7', '#5E35B1', '#512DA8',
    '#1DE9B6', '#64FFDA', '#18FFFF', '#84FFFF',
    '#FF4081', '#F50057', '#C51162', '#AD1457'
];

let shuffledOrder = [];

function shuffleArray(array) {
    const shuffled = [...array];
    for (let i = shuffled.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }
    return shuffled;
}

function transformDataForSankey(data) {
    const nodes = [];
    const links = [];
    
    nodes.push({
        name: "Total Créances",
        id: 0,
        isSource: true
    });
    
    data.forEach((item, index) => {
        const nodeId = index + 1;
        nodes.push({
            name: item.label,
            id: nodeId,
            originalIndex: index,
            value: parseFloat(item.montant),
            isSource: false
        });
        
        links.push({
            source: 0,
            target: nodeId,
            value: parseFloat(item.montant),
            originalIndex: index
        });
    });
    
    return { nodes, links };
}

function repositionSourceNode(graph, height) {
    const sourceNode = graph.nodes[0];
    const margin = 10;
    sourceNode.y0 = margin;
    sourceNode.y1 = height - margin;
    return graph;
}

function repositionDestinationNodes(graph, height, width, nodeWidth) {
    const destinationNodes = graph.nodes.slice(1);
    const totalValue = destinationNodes.reduce((sum, node) => sum + node.value, 0);
    
    if (shuffledOrder.length === 0) {
        shuffledOrder = shuffleArray(destinationNodes.map((_, index) => index));
    }
    
    const margin = 10;
    const availableHeight = height - (2 * margin);
    let currentY = margin;
    
    shuffledOrder.forEach((originalIndex, displayIndex) => {
        const node = destinationNodes[originalIndex];
        const proportion = node.value / totalValue;
        const nodeHeight = availableHeight * proportion;
        
        node.y0 = currentY;
        node.y1 = currentY + nodeHeight;
        node.x1 = node.x0 + nodeWidth;
        
        currentY += nodeHeight;
    });
    
    return graph;
}

function recalculateLinks(graph) {
    const sourceNode = graph.nodes[0];
    const totalSourceHeight = sourceNode.y1 - sourceNode.y0;
    let currentSourceY = sourceNode.y0;
    
    graph.links.forEach((link, linkIndex) => {
        const targetNode = graph.nodes[link.target.id || link.target];
        const linkProportion = link.value / graph.links.reduce((sum, l) => sum + l.value, 0);
        const linkHeight = totalSourceHeight * linkProportion;
        
        link.y0 = currentSourceY + (linkHeight / 2);
        currentSourceY += linkHeight;
        link.y1 = targetNode.y0 + ((targetNode.y1 - targetNode.y0) / 2);
        link.source = sourceNode;
        link.target = targetNode;
    });
    
    return graph;
}

function createSankeyDiagram() {
    // Vérifier qu'il y a des données à afficher
    if (!analyticsDataFiltered || analyticsDataFiltered.length === 0) {
        console.warn('Aucune donnée filtrée pour le Sankey');
        return;
    }
    
    const container = d3.select("#sankeyChart");
    container.selectAll("*").remove();
    
    const dataLength = analyticsDataFiltered.length;
    const minHeight = 600;
    const heightPerItem = 120;
    const calculatedHeight = Math.max(minHeight, dataLength * heightPerItem);
    
    const margin = { top: 30, right: 200, bottom: 30, left: 200 };
    const width = Math.max(1000, container.node().offsetWidth) - margin.left - margin.right;
    const height = calculatedHeight - margin.top - margin.bottom;
    
    container.style("height", (calculatedHeight + 40) + "px");
    
    const svg = container
        .append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", calculatedHeight)
        .style("background", "linear-gradient(135deg, #f8f9fa, #e9ecef)")
        .style("border-radius", "8px");
    
    const g = svg.append("g")
        .attr("transform", `translate(${margin.left},${margin.top})`);
    
    const sankeyData = transformDataForSankey(analyticsDataFiltered);
    const nodeWidth = 40;
    const sankey = d3.sankey()
        .nodeWidth(nodeWidth)
        .nodePadding(10)
        .extent([[1, 1], [width - 1, height - 1]]);
    
    let graph = sankey(sankeyData);
    graph = repositionSourceNode(graph, height);
    graph = repositionDestinationNodes(graph, height, width, nodeWidth);
    graph = recalculateLinks(graph);
    
    const tooltip = d3.select("body").append("div")
        .attr("class", "tooltip")
        .style("opacity", 0);
    
    const totalValue = graph.links.reduce((sum, link) => sum + link.value, 0);
    
    function highlightFlow(targetLink) {
        links.style("stroke-opacity", d => d === targetLink ? 0.9 : 0.15);
        nodes.select("rect").style("opacity", d => {
            return (d === targetLink.source || d === targetLink.target) ? 1 : 0.25;
        });
        nodes.select("text").style("opacity", d => {
            return (d === targetLink.source || d === targetLink.target) ? 1 : 0.25;
        });
    }
    
    function resetHighlight() {
        links.style("stroke-opacity", 0.7);
        nodes.select("rect").style("opacity", 1);
        nodes.select("text").style("opacity", 1);
    }

    const links = g.append("g")
        .selectAll("path")
        .data(graph.links)
        .enter()
        .append("path")
        .attr("class", "sankey-link")
        .attr("d", d3.sankeyLinkHorizontal())
        .attr("stroke", (d, i) => {
            const gradient = svg.append("defs")
                .append("linearGradient")
                .attr("id", `gradient-${d.originalIndex}`)
                .attr("gradientUnits", "userSpaceOnUse")
                .attr("x1", d.source.x1)
                .attr("x2", d.target.x0);
            
            const colorIndex = d.originalIndex % colors.length;
            const colorIndex2 = (d.originalIndex + 3) % colors.length;
            
            gradient.append("stop")
                .attr("offset", "0%")
                .attr("stop-color", colors[colorIndex])
                .attr("stop-opacity", 0.8);
                
            gradient.append("stop")
                .attr("offset", "100%")
                .attr("stop-color", colors[colorIndex2])
                .attr("stop-opacity", 0.6);
                
            return `url(#gradient-${d.originalIndex})`;
        })
        .attr("stroke-width", d => Math.max(2, d.width || 5))
        .style("stroke-opacity", 0.7)
        .style("fill", "none")
        .on("mouseover", function(event, d) {
            highlightFlow(d);
            
            const percentage = ((d.value / totalValue) * 100);
            const originalData = analyticsDataFiltered[d.originalIndex];
            
            tooltip.transition().duration(200).style("opacity", .9);
            tooltip.html(`
                <strong>Total Créances</strong><br/>
                vers <strong>${originalData.label}</strong><br/>
                <strong>${new Intl.NumberFormat('fr-DZ', {
                    style: 'currency',
                    currency: 'DZD',
                    minimumFractionDigits: 0
                }).format(d.value)}</strong><br/>
                <span style="color: #00E676; font-weight: bold;">${percentage.toFixed(1)}%</span> du total
            `)
            .style("left", (event.pageX + 10) + "px")
            .style("top", (event.pageY - 28) + "px");
        })
        .on("mouseout", function() {
            resetHighlight();
            tooltip.transition().duration(500).style("opacity", 0);
        });
    
    const nodes = g.append("g")
        .selectAll("g")
        .data(graph.nodes)
        .enter()
        .append("g")
        .attr("class", "sankey-node");
    
    nodes.append("rect")
        .attr("x", d => d.x0)
        .attr("y", d => d.y0)
        .attr("height", d => d.y1 - d.y0)
        .attr("width", d => d.x1 - d.x0)
        .attr("fill", (d, i) => {
            if (d.isSource) return "#2C3E50";
            return colors[(d.originalIndex) % colors.length];
        })
        .attr("rx", 8)
        .style("filter", "drop-shadow(2px 2px 4px rgba(0,0,0,0.25))")
        .style("stroke", "none")
        .on("mouseover", function(event, d) {
            if (!d.isSource) {
                const associatedLink = graph.links.find(link => link.target === d);
                if (associatedLink) {
                    highlightFlow(associatedLink);
                }
            }
            
            tooltip.transition().duration(200).style("opacity", .9);
            let content = `<strong>${d.name}</strong><br/>`;
            if (d.isSource) {
                content += `Total: <strong>${new Intl.NumberFormat('fr-DZ', {
                    style: 'currency',
                    currency: 'DZD',
                    minimumFractionDigits: 0
                }).format(totalValue)}</strong>`;
            } else {
                const dataItem = analyticsDataFiltered[d.originalIndex];
                if (dataItem) {
                    const percentage = ((parseFloat(dataItem.montant) / totalValue) * 100);
                    content += `Montant: <strong>${new Intl.NumberFormat('fr-DZ', {
                        style: 'currency',
                        currency: 'DZD',
                        minimumFractionDigits: 0
                    }).format(dataItem.montant)}</strong><br/>`;
                    content += `<span style="color: #00E676; font-weight: bold;">${percentage.toFixed(1)}%</span> du total<br/>`;
                    content += `Nombre: <strong>${dataItem.count}</strong>`;
                }
            }
            tooltip.html(content)
                .style("left", (event.pageX + 10) + "px")
                .style("top", (event.pageY - 28) + "px");
        })
        .on("mouseout", function() {
            resetHighlight();
            tooltip.transition().duration(500).style("opacity", 0);
        });
    
    nodes.append("text")
        .attr("x", d => d.x0 < width / 2 ? d.x1 + 15 : d.x0 - 15)
        .attr("y", d => (d.y1 + d.y0) / 2)
        .attr("dy", "0.35em")
        .attr("text-anchor", d => d.x0 < width / 2 ? "start" : "end")
        .text(d => d.name)
        .style("font-size", "13px")
        .style("font-weight", "600")
        .style("fill", "#2c3e50")
        .style("text-shadow", "1px 1px 2px rgba(255,255,255,0.8)");
    
    return svg.node();
}

function regenerateChart() {
    shuffledOrder = [];
    if (analyticsDataFiltered && analyticsDataFiltered.length > 0) {
        createSankeyDiagram();
    }
}

function exportChart() {
    const svg = document.querySelector("#sankeyChart svg");
    if (!svg) {
        console.error("SVG non trouvé pour l'export");
        showExportNotification("Aucun diagramme à exporter", "error");
        return;
    }
    
    console.log("Début de l'export PNG...");
    
    const svgClone = svg.cloneNode(true);
    
    const nodesInClone = svgClone.querySelectorAll('.sankey-node rect');
    const linksInClone = svgClone.querySelectorAll('.sankey-link');
    
    nodesInClone.forEach((rect) => {
        rect.style.stroke = 'none';
        rect.style.strokeWidth = '0';
        rect.style.filter = 'drop-shadow(1px 1px 3px rgba(0,0,0,0.2))';
    });
    
    linksInClone.forEach((link) => {
        link.style.fill = 'none';
        link.style.outline = 'none';
    });
    
    const textsInClone = svgClone.querySelectorAll('text');
    textsInClone.forEach(text => {
        text.style.textShadow = '1px 1px 1px rgba(255,255,255,0.9)';
    });
    
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    
    const scaleFactor = 2;
    const originalWidth = svg.clientWidth;
    const originalHeight = svg.clientHeight;
    
    canvas.width = originalWidth * scaleFactor;
    canvas.height = originalHeight * scaleFactor;
    
    ctx.scale(scaleFactor, scaleFactor);
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';
    
    const data = new XMLSerializer().serializeToString(svgClone);
    const svgBlob = new Blob([data], {type: 'image/svg+xml;charset=utf-8'});
    const url = URL.createObjectURL(svgBlob);
    
    const img = new Image();
    img.onload = function () {
        ctx.drawImage(img, 0, 0, originalWidth, originalHeight);
        URL.revokeObjectURL(url);
        
        const imgURI = canvas.toDataURL('image/png', 1.0);
        const link = document.createElement('a');
        const groupByValue = '<?php echo $groupBy; ?>';
        const timestamp = new Date().toISOString().slice(0,10);
        
        link.download = `sankey_analytics_${groupByValue}_${timestamp}.png`;
        link.href = imgURI;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showExportNotification("Export PNG réussi!", "success");
    };
    
    img.onerror = function(error) {
        console.error("Erreur lors du chargement de l'image SVG:", error);
        URL.revokeObjectURL(url);
        showExportNotification("Erreur lors de l'export PNG", "error");
    };
    
    img.src = url;
}

function showExportNotification(message, type = "success") {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 600;
        z-index: 10000;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        background: ${type === 'success' ? 'linear-gradient(135deg, #00C851, #007E33)' : 'linear-gradient(135deg, #ff4444, #CC0000)'};
    `;
    
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
        ${message}
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '1';
    }, 100);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('Page chargée');
    console.log('Données filtrées (Sankey):', analyticsDataFiltered.length, 'entrées');
    console.log('Données complètes (Tableau):', analyticsDataComplete.length, 'entrées');
    
    if (analyticsDataFiltered && analyticsDataFiltered.length > 0) {
        createSankeyDiagram();
        console.log("Diagramme de Sankey créé avec", analyticsDataFiltered.length, "catégories");
    } else {
        console.warn("Aucune donnée filtrée disponible pour le Sankey");
    }
});

console.log("✅ Script Sankey chargé - Version avec distinction données Sankey/Tableau");
</script>