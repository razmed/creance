<?php
/**
 * Classe PDF - SANS WARNINGS
 */

// Supprimer warnings AVANT require TCPDF
error_reporting(E_ERROR | E_PARSE);

require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';

// Réactiver après chargement TCPDF
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

require_once __DIR__ . '/../config/constants.php';
require_once 'Creance.php';

class CreancePDF extends TCPDF {
    protected $title;
    protected $isLandscape;
    
    // ... reste identique jusqu'aux fonctions de graphiques
    public function __construct($title = 'Rapport de Créances', $landscape = true) {
        $this->title = $title;
        $this->isLandscape = $landscape;
        
        $orientation = $landscape ? 'L' : 'P';
        $unit = 'mm';
        $format = 'A4';
        
        parent::__construct($orientation, $unit, $format, true, 'UTF-8', false);
        
        // Configuration du document
        $this->SetCreator(PDF_AUTHOR);
        $this->SetAuthor(PDF_AUTHOR);
        $this->SetTitle($this->title);
        $this->SetSubject('Rapport de gestion des créances');
        $this->SetKeywords('créances, provisions, rapport, gestion');
        
        // Configuration des marges
        $this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->SetHeaderMargin(5);
        $this->SetFooterMargin(10);
        
        // Configuration de l'auto page break
        $this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        
        // Configuration de la police
        $this->SetFont('helvetica', '', 8);
    }
    
    /**
     * En-tête du document
     */
    public function Header() {
        // Titre
        $this->SetFont('helvetica', 'B', 16);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 10, $this->title, 0, 1, 'C');
        
        // Date de génération
        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 5, 'Généré le : ' . date('d/m/Y à H:i'), 0, 1, 'C');
        
        $this->Ln(5);
    }
    
    /**
     * Pied de page
     */
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
    
    /**
     * Générer le rapport principal avec données
     */
    public function generateReport($donnees, $stats = null, $filters = []) {
        $this->AddPage();
        
        // Informations sur les filtres appliqués
        if (!empty($filters)) {
            $this->addFiltersInfo($filters);
        }
        
        // Statistiques globales
        if ($stats) {
            $this->addStatsTable($stats);
        }
        
        // Tableau des créances
        $this->addCreancesTable($donnees);
    }
    
    /**
     * Ajouter les informations sur les filtres
     */
    private function addFiltersInfo($filters) {
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 8, 'Filtres appliqués :', 0, 1, 'L');
        
        $this->SetFont('helvetica', '', 9);
        foreach ($filters as $column => $values) {
            if (!empty($values)) {
                $valuesList = is_array($values) ? implode(', ', $values) : $values;
                $this->Cell(0, 6, "• {$column} : {$valuesList}", 0, 1, 'L');
            }
        }
        
        $this->Ln(5);
    }
    
    /**
     * Ajouter le tableau des statistiques
     */
    private function addStatsTable($stats) {
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 8, 'Tableau Créance-Provision', 0, 1, 'L');
        
        // En-têtes du tableau stats
        $headers = ['CRÉANCE EN TTC', 'CRÉANCE EN HT', 'PROVISIONS EN TTC', 'PROVISIONS EN HT'];
        $values = [
            number_format($stats['creance_ttc'], 2, ',', ' '),
            number_format($stats['creance_ht'], 2, ',', ' '),
            number_format($stats['provision_ttc'], 2, ',', ' '),
            number_format($stats['provision_ht'], 2, ',', ' ')
        ];
        
        // Largeur des colonnes
        $cellWidth = $this->isLandscape ? 65 : 45;
        
        // En-têtes
        $this->SetFont('helvetica', 'B', 9);
        $this->SetFillColor(200, 200, 200);
        foreach ($headers as $header) {
            $this->Cell($cellWidth, 8, $header, 1, 0, 'C', true);
        }
        $this->Ln();
        
        // Valeurs
        $this->SetFont('helvetica', '', 9);
        $this->SetFillColor(240, 240, 240);
        foreach ($values as $value) {
            $this->Cell($cellWidth, 8, $value, 1, 0, 'C', true);
        }
        $this->Ln();
        
        $this->Ln(10);
    }
    
    /**
     * Ajouter le tableau principal des créances
     */
    private function addCreancesTable($donnees) {
        if (empty($donnees)) {
            $this->SetFont('helvetica', 'I', 12);
            $this->Cell(0, 10, 'Aucune donnée à afficher', 0, 1, 'C');
            return;
        }
        
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 8, 'Détail des Créances', 0, 1, 'L');
        
        // En-têtes des colonnes
        $headers = [
            'RÉGION', 'SECTEUR', 'CLIENT', 'INTITULÉ\nMARCHÉ', 'N° FACTURE\n/ SITUATION',
            'DATE', 'NATURE', 'MONTANT\nTOTAL', 'ENCAISSE-\nMENT', 'MONTANT\nCRÉANCE',
            'ÂGE DE LA\nCRÉANCE', '%\nPROVISION', 'PROVISION\n2024', 'OBSERVATION'
        ];
        
        // Largeurs des colonnes
        $colWidths = $this->isLandscape ? 
            [18, 18, 22, 25, 22, 15, 18, 20, 20, 20, 18, 15, 20, 20] :
            [12, 12, 15, 18, 15, 10, 12, 14, 14, 14, 12, 10, 14, 14];
        
        // En-têtes
        $this->SetFont('helvetica', 'B', 7);
        $this->SetFillColor(180, 180, 180);
        $this->SetTextColor(0, 0, 0);
        
        foreach ($headers as $i => $header) {
            $this->Cell($colWidths[$i], 12, $header, 1, 0, 'C', true);
        }
        $this->Ln();
        
        // Données
        $this->SetFont('helvetica', '', 6);
        $fillColor1 = [245, 245, 245];
        $fillColor2 = [255, 255, 255];
        $currentFill = true;
        
        foreach ($donnees as $ligne) {
            // Alterner les couleurs de fond
            $this->SetFillColor($currentFill ? $fillColor1[0] : $fillColor2[0], 
                               $currentFill ? $fillColor1[1] : $fillColor2[1], 
                               $currentFill ? $fillColor1[2] : $fillColor2[2]);
            
            $rowData = [
                $this->truncateText($ligne['region'], 15),
                $this->truncateText($ligne['secteur'], 15),
                $this->truncateText($ligne['client'], 18),
                $this->truncateText($ligne['intitule_marche'], 22),
                $this->truncateText($ligne['num_facture_situation'], 18),
                $ligne['date_str'],
                $ligne['nature'],
                number_format($ligne['montant_total'], 0, ',', ' '),
                $ligne['montant_creance'] == 0 ? '' : number_format($ligne['encaissement'], 0, ',', ' '),
                number_format($ligne['montant_creance'], 0, ',', ' '),
                $ligne['age_annees'] . ' ans',
                $ligne['pct_provision'] . '%',
                number_format($ligne['provision_2024'], 0, ',', ' '),
                $this->truncateText($ligne['observation'] ?? '', 15)
            ];
            
            foreach ($rowData as $i => $data) {
                $this->Cell($colWidths[$i], 8, $data, 1, 0, 'C', true);
            }
            $this->Ln();
            
            $currentFill = !$currentFill;
            
            // Vérifier si on doit créer une nouvelle page
            if ($this->GetY() > 180) {
                $this->AddPage();
                
                // Répéter les en-têtes sur la nouvelle page
                $this->SetFont('helvetica', 'B', 7);
                $this->SetFillColor(180, 180, 180);
                foreach ($headers as $i => $header) {
                    $this->Cell($colWidths[$i], 12, $header, 1, 0, 'C', true);
                }
                $this->Ln();
                $this->SetFont('helvetica', '', 6);
            }
        }
    }
    
    /**
     * Ajouter des graphiques au PDF
     */
    public function addCharts($chartImages) {
        if (empty($chartImages)) {
            return;
        }
        
        $this->AddPage();
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 10, 'Visualisations', 0, 1, 'C');
        $this->Ln(10);
        
        foreach ($chartImages as $title => $imagePath) {
            if (file_exists($imagePath)) {
                // Titre du graphique
                $this->SetFont('helvetica', 'B', 12);
                $this->Cell(0, 8, $title, 0, 1, 'C');
                $this->Ln(5);
                
                // Calculer la taille de l'image
                $maxWidth = $this->isLandscape ? 250 : 180;
                $maxHeight = 100;
                
                // Centrer l'image
                $x = ($this->getPageWidth() - $maxWidth) / 2;
                $this->Image($imagePath, $x, $this->GetY(), $maxWidth, $maxHeight);
                $this->Ln($maxHeight + 15);
                
                // Vérifier si on a besoin d'une nouvelle page
                if ($this->GetY() > 150) {
                    $this->AddPage();
                }
            }
        }
    }
    
    /**
     * Tronquer le texte si trop long
     */
    private function truncateText($text, $maxLength) {
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }
        
        return mb_substr($text, 0, $maxLength - 3) . '...';
    }
    
    /**
     * Formater une valeur en K, M, B
     */
    private function formatValue($val) {
        if (abs($val) >= 1e9) {
            return number_format($val / 1e9, 2, ',', ' ') . 'B';
        } elseif (abs($val) >= 1e6) {
            return number_format($val / 1e6, 2, ',', ' ') . 'M';
        } elseif (abs($val) >= 1e3) {
            return number_format($val / 1e3, 2, ',', ' ') . 'K';
        } else {
            return number_format($val, 2, ',', ' ');
        }
    }
    
    /**
     * Bar Chart - CORRECTIONS CAST
     */
    public function generateBarChart($donnees, $outputPath) {
        $regionData = [];
        foreach ($donnees as $ligne) {
            $region = $ligne['region'];
            if (!isset($regionData[$region])) {
                $regionData[$region] = ['creances' => 0, 'provisions' => 0];
            }
            $regionData[$region]['creances'] += floatval($ligne['montant_creance']);
            $regionData[$region]['provisions'] += floatval($ligne['provision_2024']);
        }
        
        if (empty($regionData)) {
            return false;
        }
        
        $width = 1200;
        $height = 600;
        $image = imagecreatetruecolor($width, $height);
        
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $blue = imagecolorallocate($image, 0, 136, 254);
        $orange = imagecolorallocate($image, 255, 128, 66);
        $gray = imagecolorallocate($image, 200, 200, 200);
        
        imagefill($image, 0, 0, $white);
        imagestring($image, 5, 400, 30, 'Creances vs Provisions par Region', $black);
        
        $chartX = 80;
        $chartY = 80;
        $chartWidth = $width - 150;
        $chartHeight = $height - 150;
        
        $maxValue = 0;
        foreach ($regionData as $data) {
            $maxValue = max($maxValue, $data['creances'], $data['provisions']);
        }
        
        if ($maxValue == 0) {
            imagedestroy($image);
            return false;
        }
        
        imageline($image, $chartX, $chartY, $chartX, $chartY + $chartHeight, $black);
        imageline($image, $chartX, $chartY + $chartHeight, $chartX + $chartWidth, $chartY + $chartHeight, $black);
        
        for ($i = 0; $i <= 5; $i++) {
            $y = (int)($chartY + $chartHeight - ($i * $chartHeight / 5));
            $value = ($maxValue / 5) * $i;
            imageline($image, $chartX - 5, $y, $chartX, $y, $gray);
            imagestring($image, 2, 10, $y - 5, $this->formatValue($value), $black);
        }
        
        $regions = array_keys($regionData);
        $numRegions = count($regions);
        $barGroupWidth = $chartWidth / ($numRegions + 1);
        $barWidth = ($barGroupWidth / 3);
        
        foreach ($regions as $index => $region) {
            $x = (int)($chartX + ($index + 0.5) * $barGroupWidth);
            
            // CAST EXPLICITE pour éviter deprecated
            $creanceHeight = (int)(($regionData[$region]['creances'] / $maxValue) * $chartHeight);
            $creanceY = (int)($chartY + $chartHeight - $creanceHeight);
            imagefilledrectangle($image, 
                $x, $creanceY, 
                (int)($x + $barWidth), (int)($chartY + $chartHeight), 
                $blue);
            
            $provisionHeight = (int)(($regionData[$region]['provisions'] / $maxValue) * $chartHeight);
            $provisionY = (int)($chartY + $chartHeight - $provisionHeight);
            imagefilledrectangle($image, 
                (int)($x + $barWidth + 5), $provisionY, 
                (int)($x + 2 * $barWidth + 5), (int)($chartY + $chartHeight), 
                $orange);
            
            $regionLabel = substr($region, 0, 10);
            imagestring($image, 2, $x, (int)($chartY + $chartHeight + 10), $regionLabel, $black);
        }
        
        $legendX = $width - 200;
        $legendY = 100;
        imagefilledrectangle($image, $legendX, $legendY, $legendX + 30, $legendY + 20, $blue);
        imagestring($image, 3, $legendX + 40, $legendY + 5, 'Creances', $black);
        imagefilledrectangle($image, $legendX, $legendY + 30, $legendX + 30, $legendY + 50, $orange);
        imagestring($image, 3, $legendX + 40, $legendY + 35, 'Provisions', $black);
        
        imagepng($image, $outputPath);
        imagedestroy($image);
        
        return file_exists($outputPath);
    }

    /**
     * Générer un graphique en secteurs (secteur)
     */
    public function generatePieChart($donnees, $outputPath) {
        // Regrouper les données par secteur
        $secteurData = [];
        foreach ($donnees as $ligne) {
            $secteur = $ligne['secteur'];
            if (!isset($secteurData[$secteur])) {
                $secteurData[$secteur] = 0;
            }
            $secteurData[$secteur] += floatval($ligne['montant_creance']);
        }
        
        if (empty($secteurData)) {
            return false;
        }
        
        $width = 800;
        $height = 700;
        $image = imagecreatetruecolor($width, $height);
        
        // Couleurs
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $colors = [
            imagecolorallocate($image, 0, 136, 254),
            imagecolorallocate($image, 0, 196, 159),
            imagecolorallocate($image, 255, 187, 40),
            imagecolorallocate($image, 255, 128, 66),
            imagecolorallocate($image, 136, 132, 216),
            imagecolorallocate($image, 255, 99, 132),
            imagecolorallocate($image, 54, 162, 235)
        ];
        
        imagefill($image, 0, 0, $white);
        
        // Titre
        imagestring($image, 5, 220, 30, 'Repartition par Secteur', $black);
        
        // Calculer le total
        $total = array_sum($secteurData);
        if ($total == 0) {
            imagedestroy($image);
            return false;
        }
        
        $centerX = 400;
        $centerY = 350;
        $radius = 180;
        
        $currentAngle = 0;
        $colorIndex = 0;
        
        foreach ($secteurData as $secteur => $montant) {
            $sliceAngle = ($montant / $total) * 360;
            $endAngle = $currentAngle + $sliceAngle;
            
            // Dessiner la part
            imagefilledarc($image, $centerX, $centerY, $radius * 2, $radius * 2, 
                          $currentAngle, $endAngle, $colors[$colorIndex % count($colors)], IMG_ARC_PIE);
            
            // Label avec pourcentage
            $labelAngle = deg2rad($currentAngle + ($sliceAngle / 2));
            $labelX = $centerX + cos($labelAngle) * ($radius + 50);
            $labelY = $centerY + sin($labelAngle) * ($radius + 50);
            $percentage = ($montant / $total) * 100;
            $label = substr($secteur, 0, 12) . "\n" . number_format($percentage, 1) . '%';
            imagestring($image, 3, $labelX - 30, $labelY - 10, $label, $black);
            
            $currentAngle = $endAngle;
            $colorIndex++;
        }
        
        // Sauvegarder l'image
        imagepng($image, $outputPath);
        imagedestroy($image);
        
        return file_exists($outputPath);
    }
    
    /**
     * Radar Chart - CORRECTION imagepolygon deprecated
     */
    public function generateRadarChart($donnees, $outputPath) {
        // ... (code préparation identique jusqu'aux appels imagepolygon)
        
        // REMPLACER:
        // imagefilledpolygon($image, $pointsCreances, $n, $blue);
        // imagepolygon($image, $pointsCreances, $n, $blue);
        
        // PAR (PHP 8+):
        if ($n >= 3) {
            imagefilledpolygon($image, $pointsCreances, $blue); // SANS $n
            imagesetthickness($image, 3);
            imagepolygon($image, $pointsCreances, $blue); // SANS $n
            imagesetthickness($image, 1);
        }
        
        // ... même correction pour $pointsProvisions
        
        imagesetthickness($image, 3);
        if ($n >= 3) {
            imagepolygon($image, $pointsProvisions, $orange); // SANS $n
        }
        imagesetthickness($image, 1);
        
        // ... reste identique
        // Points et valeurs pour provisions
        for ($i = 0; $i < $n; $i++) {
            $x = $pointsProvisions[$i * 2];
            $y = $pointsProvisions[$i * 2 + 1];
            imagefilledellipse($image, $x, $y, 12, 12, $orange);
            
            $value = $naturesData[$natures[$i]]['provisions'];
            $valueText = $this->formatValue($value);
            imagestring($image, 3, $x + 15, $y + 5, $valueText, $orange);
        }
        
        // Légende
        $legendX = 80;
        $legendY = 850;
        
        imagefilledrectangle($image, $legendX - 15, $legendY - 15, $legendX + 250, $legendY + 80, $lightGray);
        imagerectangle($image, $legendX - 15, $legendY - 15, $legendX + 250, $legendY + 80, $gray);
        
        imagefilledrectangle($image, $legendX, $legendY, $legendX + 40, $legendY + 25, $blue);
        imagestring($image, 4, $legendX + 50, $legendY + 5, 'Creances', $black);
        
        imagefilledrectangle($image, $legendX, $legendY + 40, $legendX + 40, $legendY + 65, $orange);
        imagestring($image, 4, $legendX + 50, $legendY + 45, 'Provisions', $black);
        
        // Sauvegarder
        imagepng($image, $outputPath);
        imagedestroy($image);
        
        return file_exists($outputPath);
    }
    // ... reste des méthodes
    /**
     * Vérifier si le radar chart peut être généré
     */
    public function canGenerateRadarChart($donnees) {
        $naturesUniques = [];
        foreach ($donnees as $ligne) {
            $nature = $ligne['nature'];
            if (!in_array($nature, $naturesUniques)) {
                $naturesUniques[] = $nature;
            }
        }
        return count($naturesUniques) >= 3;
    }
    
    /**
     * Nettoyer les fichiers temporaires
     */
    public static function cleanupTempFiles($directory, $olderThanHours = 24) {
        if (!is_dir($directory)) {
            return;
        }
        
        $files = glob($directory . '/*');
        $now = time();
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= $olderThanHours * 3600) {
                    @unlink($file);
                }
            }
        }
    }
}

/**
 * Classe utilitaire pour la génération des rapports
 */
class ReportGenerator {
    private $creance;
    private $tempDir;
    
    public function __construct() {
        $this->creance = new Creance();
        $this->tempDir = ROOT_PATH . '/temp/';
        
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }
    
    /**
     * Générer un rapport PDF complet avec options de visualisation
     * CORRECTION MAJEURE : Accepte maintenant $chartOptions
     * 
     * @param array $filters Filtres appliqués aux données
     * @param int $archived 0 pour données actives, 1 pour archives
     * @param array $chartOptions Options des graphiques: ['bar_chart' => bool, 'pie_chart' => bool, 'radar_chart' => bool]
     * @return array Résultat avec success, filename, filepath, ou error
     */
    public function generateFullReport($filters = [], $archived = 0, $chartOptions = []) {
        try {
            // Obtenir les données FILTRÉES
            $donnees = $this->creance->getAll($filters, $archived, 1, 10000);
            
            if (empty($donnees)) {
                throw new Exception('Aucune donnée à exporter');
            }
            
            // Obtenir les statistiques sur les données filtrées
            $stats = $archived === 0 ? $this->creance->getStats($filters) : null;
            
            // Créer le PDF
            $title = $archived ? 'Rapport d\'Archive des Créances' : 'Rapport de Gestion des Créances';
            $pdf = new CreancePDF($title, true);
            
            // Générer le rapport principal avec les données FILTRÉES
            $pdf->generateReport($donnees, $stats, $filters);
            
            // Ajouter les graphiques si demandé ET si on n'est pas en mode archive
            $chartImages = [];
            if ($archived === 0 && !empty($chartOptions)) {
                
                // Bar Chart par Région
                if (!empty($chartOptions['bar_chart'])) {
                    $barChartPath = $this->tempDir . 'bar_chart_' . uniqid() . '.png';
                    if ($pdf->generateBarChart($donnees, $barChartPath)) {
                        $chartImages['Créances vs Provisions par Région'] = $barChartPath;
                    }
                }
                
                // Pie Chart par Secteur
                if (!empty($chartOptions['pie_chart'])) {
                    $pieChartPath = $this->tempDir . 'pie_chart_' . uniqid() . '.png';
                    if ($pdf->generatePieChart($donnees, $pieChartPath)) {
                        $chartImages['Répartition par Secteur'] = $pieChartPath;
                    }
                }
                
                // Radar Chart par Nature (avec vérification)
                if (!empty($chartOptions['radar_chart']) && $pdf->canGenerateRadarChart($donnees)) {
                    $radarChartPath = $this->tempDir . 'radar_chart_' . uniqid() . '.png';
                    if ($pdf->generateRadarChart($donnees, $radarChartPath)) {
                        $chartImages['Spider Radar par Nature'] = $radarChartPath;
                    }
                }
                
                // Ajouter au PDF
                if (!empty($chartImages)) {
                    $pdf->addCharts($chartImages);
                }
            }
            
            // Créer le répertoire exports s'il n'existe pas
            $exportsDir = ROOT_PATH . '/exports/';
            if (!is_dir($exportsDir)) {
                mkdir($exportsDir, 0755, true);
            }
            
            // Générer le nom de fichier
            $filename = 'rapport_creances_' . date('Y-m-d_H-i-s') . '.pdf';
            $filepath = $exportsDir . $filename;
            
            // Sauvegarder le PDF
            $pdf->Output($filepath, 'F');
            
            // Nettoyer les fichiers temporaires
            foreach ($chartImages as $imagePath) {
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath),
                'nb_lignes' => count($donnees),
                'filters_applied' => !empty($filters),
                'charts_count' => count($chartImages)
            ];
            
        } catch (Exception $e) {
            error_log("Erreur génération PDF: " . $e->getMessage() . " - " . $e->getTraceAsString());
            
            // Nettoyer les fichiers temporaires en cas d'erreur
            if (isset($chartImages)) {
                foreach ($chartImages as $imagePath) {
                    if (file_exists($imagePath)) {
                        @unlink($imagePath);
                    }
                }
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Vérifier si le radar chart peut être généré pour les données données
     * @param array $filters
     * @param int $archived
     * @return bool
     */
    public function canGenerateRadar($filters = [], $archived = 0) {
        try {
            $donnees = $this->creance->getAll($filters, $archived, 1, 10000);
            $pdf = new CreancePDF();
            return $pdf->canGenerateRadarChart($donnees);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Générer un rapport d'analyse groupé
     */
    public function generateAnalyticsReport($groupBy = 'region') {
        try {
            $analyticsData = $this->creance->getAnalyticsData($groupBy);
            
            $pdf = new CreancePDF('Rapport d\'Analyse - ' . ucfirst($groupBy), false);
            $pdf->AddPage();
            
            // Titre
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Analyse par ' . ucfirst($groupBy), 0, 1, 'C');
            $pdf->Ln(10);
            
            // Tableau des données
            $headers = [ucfirst($groupBy), 'Montant Total', 'Nombre de Créances', 'Pourcentage'];
            $colWidths = [60, 50, 40, 30];
            
            // En-têtes
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetFillColor(200, 200, 200);
            foreach ($headers as $i => $header) {
                $pdf->Cell($colWidths[$i], 8, $header, 1, 0, 'C', true);
            }
            $pdf->Ln();
            
            // Calculer le total pour les pourcentages
            $totalMontant = array_sum(array_column($analyticsData, 'montant'));
            
            // Données
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetFillColor(240, 240, 240);
            
            foreach ($analyticsData as $row) {
                $pourcentage = $totalMontant > 0 ? ($row['montant'] / $totalMontant) * 100 : 0;
                
                $pdf->Cell($colWidths[0], 8, $row['label'], 1, 0, 'L', true);
                $pdf->Cell($colWidths[1], 8, number_format($row['montant'], 0, ',', ' '), 1, 0, 'R', true);
                $pdf->Cell($colWidths[2], 8, $row['count'], 1, 0, 'C', true);
                $pdf->Cell($colWidths[3], 8, number_format($pourcentage, 1) . '%', 1, 0, 'R', true);
                $pdf->Ln();
            }
            
            // Total
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($colWidths[0], 8, 'TOTAL', 1, 0, 'L', true);
            $pdf->Cell($colWidths[1], 8, number_format($totalMontant, 0, ',', ' '), 1, 0, 'R', true);
            $pdf->Cell($colWidths[2], 8, array_sum(array_column($analyticsData, 'count')), 1, 0, 'C', true);
            $pdf->Cell($colWidths[3], 8, '100%', 1, 0, 'R', true);
            
            // Créer le répertoire exports s'il n'existe pas
            $exportsDir = ROOT_PATH . '/exports/';
            if (!is_dir($exportsDir)) {
                mkdir($exportsDir, 0755, true);
            }
            
            $filename = 'analyse_' . $groupBy . '_' . date('Y-m-d_H-i-s') . '.pdf';
            $filepath = $exportsDir . $filename;
            
            $pdf->Output($filepath, 'F');
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Nettoyer les anciens fichiers d'export
     */
    public function cleanupOldExports($daysOld = 7) {
        CreancePDF::cleanupTempFiles(ROOT_PATH . '/exports/', $daysOld * 24);
        CreancePDF::cleanupTempFiles($this->tempDir, 1);
    }
    
    /**
     * Obtenir la liste des exports disponibles
     */
    public function getAvailableExports() {
        $exportsDir = ROOT_PATH . '/exports/';
        if (!is_dir($exportsDir)) {
            return [];
        }
        
        $files = glob($exportsDir . '*.pdf');
        $exports = [];
        
        foreach ($files as $file) {
            $exports[] = [
                'filename' => basename($file),
                'filepath' => $file,
                'size' => filesize($file),
                'date' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
        
        usort($exports, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return $exports;
    }
}