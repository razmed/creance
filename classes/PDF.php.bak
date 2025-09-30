<?php
/**
 * Classe PDF - Génération de rapports PDF
 * Gestion des Créances - Version Web
 */

require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
require_once __DIR__ . '/../config/constants.php';
require_once 'Creance.php';

class CreancePDF extends TCPDF {
    protected $title;
    protected $isLandscape;
    
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
        // Logo (optionnel)
        // $this->Image('logo.png', 15, 10, 30);
        
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
        
        // En-têtes des colonnes (sans les colonnes d'action)
        $headers = [
            'RÉGION', 'SECTEUR', 'CLIENT', 'INTITULÉ\nMARCHÉ', 'N° FACTURE\n/ SITUATION',
            'DATE', 'NATURE', 'MONTANT\nTOTAL', 'ENCAISSE-\nMENT', 'MONTANT\nCRÉANCE',
            'ÂGE DE LA\nCRÉANCE', '%\nPROVISION', 'PROVISION\n2024', 'OBSERVATION'
        ];
        
        // Largeurs des colonnes (ajustées pour le format paysage)
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
     * Générer un graphique en barres (région)
     */
    public function generateBarChart($donnees, $outputPath) {
        // Regrouper les données par région
        $regionData = [];
        foreach ($donnees as $ligne) {
            $region = $ligne['region'];
            if (!isset($regionData[$region])) {
                $regionData[$region] = ['creances' => 0, 'provisions' => 0];
            }
            $regionData[$region]['creances'] += $ligne['montant_creance'];
            $regionData[$region]['provisions'] += $ligne['provision_2024'];
        }
        
        // Créer l'image du graphique (simulation - nécessiterait une vraie librairie de graphiques)
        $width = 800;
        $height = 400;
        $image = imagecreate($width, $height);
        
        // Couleurs
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $blue = imagecolorallocate($image, 0, 136, 254);
        $orange = imagecolorallocate($image, 255, 128, 66);
        
        // Fond blanc
        imagefill($image, 0, 0, $white);
        
        // Titre
        imagestring($image, 5, 250, 20, 'Creances vs Provisions par Region', $black);
        
        // Dessiner les barres (version simplifiée)
        $barWidth = 60;
        $barSpacing = 20;
        $x = 50;
        $maxValue = max(array_column($regionData, 'creances'));
        $scale = 300 / $maxValue;
        
        foreach ($regionData as $region => $data) {
            $creanceHeight = $data['creances'] * $scale;
            $provisionHeight = $data['provisions'] * $scale;
            
            // Barre créances
            imagefilledrectangle($image, $x, 350 - $creanceHeight, $x + 25, 350, $blue);
            
            // Barre provisions
            imagefilledrectangle($image, $x + 30, 350 - $provisionHeight, $x + 55, 350, $orange);
            
            // Label région
            imagestring($image, 2, $x, 360, substr($region, 0, 8), $black);
            
            $x += $barWidth + $barSpacing;
            
            if ($x > $width - 100) break; // Éviter le débordement
        }
        
        // Légende
        imagefilledrectangle($image, 600, 50, 620, 65, $blue);
        imagestring($image, 3, 625, 52, 'Creances', $black);
        imagefilledrectangle($image, 600, 70, 620, 85, $orange);
        imagestring($image, 3, 625, 72, 'Provisions', $black);
        
        // Sauvegarder l'image
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
            $secteurData[$secteur] += $ligne['montant_creance'];
        }
        
        $width = 500;
        $height = 400;
        $image = imagecreate($width, $height);
        
        // Couleurs
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $colors = [
            imagecolorallocate($image, 0, 136, 254),
            imagecolorallocate($image, 0, 196, 159),
            imagecolorallocate($image, 255, 187, 40),
            imagecolorallocate($image, 255, 128, 66),
            imagecolorallocate($image, 136, 132, 216)
        ];
        
        imagefill($image, 0, 0, $white);
        
        // Titre
        imagestring($image, 5, 120, 20, 'Repartition par Secteur', $black);
        
        // Calculer les angles
        $total = array_sum($secteurData);
        $centerX = 250;
        $centerY = 200;
        $radius = 100;
        
        $currentAngle = 0;
        $colorIndex = 0;
        
        foreach ($secteurData as $secteur => $montant) {
            $sliceAngle = ($montant / $total) * 360;
            
            // Dessiner la part (version simplifiée)
            $endAngle = $currentAngle + $sliceAngle;
            
            // Pour chaque degré de la part
            for ($i = $currentAngle; $i <= $endAngle; $i++) {
                $x1 = $centerX + cos(deg2rad($i)) * $radius;
                $y1 = $centerY + sin(deg2rad($i)) * $radius;
                imageline($image, $centerX, $centerY, $x1, $y1, $colors[$colorIndex % count($colors)]);
            }
            
            // Label
            $labelAngle = $currentAngle + ($sliceAngle / 2);
            $labelX = $centerX + cos(deg2rad($labelAngle)) * ($radius + 30);
            $labelY = $centerY + sin(deg2rad($labelAngle)) * ($radius + 30);
            imagestring($image, 2, $labelX, $labelY, substr($secteur, 0, 10), $black);
            
            $currentAngle = $endAngle;
            $colorIndex++;
        }
        
        // Sauvegarder l'image
        imagepng($image, $outputPath);
        imagedestroy($image);
        
        return file_exists($outputPath);
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
                    unlink($file);
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
     * Générer un rapport PDF complet
     */
    public function generateFullReport($filters = [], $archived = 0, $includeCharts = false) {
        try {
            // Obtenir les données
            $donnees = $this->creance->getAll($filters, $archived, 1, 10000); // Limite haute pour export
            $stats = $this->creance->getStats($filters);
            
            // Créer le PDF
            $title = $archived ? 'Rapport d\'Archive des Créances' : 'Rapport de Gestion des Créances';
            $pdf = new CreancePDF($title, true);
            
            // Générer le rapport principal
            $pdf->generateReport($donnees, $stats, $filters);
            
            // Ajouter les graphiques si demandé
            if ($includeCharts && !$archived) {
                $chartImages = [];
                
                // Générer les graphiques
                $barChartPath = $this->tempDir . 'bar_chart_' . uniqid() . '.png';
                if ($pdf->generateBarChart($donnees, $barChartPath)) {
                    $chartImages['Créances vs Provisions par Région'] = $barChartPath;
                }
                
                $pieChartPath = $this->tempDir . 'pie_chart_' . uniqid() . '.png';
                if ($pdf->generatePieChart($donnees, $pieChartPath)) {
                    $chartImages['Répartition par Secteur'] = $pieChartPath;
                }
                
                // Ajouter au PDF
                if (!empty($chartImages)) {
                    $pdf->addCharts($chartImages);
                }
                
                // Nettoyer les fichiers temporaires
                foreach ($chartImages as $imagePath) {
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
            }
            
            // Générer le nom de fichier
            $filename = 'rapport_creances_' . date('Y-m-d_H-i-s') . '.pdf';
            $filepath = ROOT_PATH . '/exports/' . $filename;
            
            // Sauvegarder le PDF
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
     * Générer un rapport d'analyse
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
            
            $filename = 'analyse_' . $groupBy . '_' . date('Y-m-d_H-i-s') . '.pdf';
            $filepath = ROOT_PATH . '/exports/' . $filename;
            
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
        CreancePDF::cleanupTempFiles($this->tempDir, 1); // Nettoyer les temp files après 1h
    }
}
?>