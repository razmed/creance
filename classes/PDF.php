<?php
/**
 * Classe PDF - AVEC VRAIES POLICES TRUETYPE
 */

error_reporting(E_ERROR | E_PARSE);
require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

require_once __DIR__ . '/../config/constants.php';
require_once 'Creance.php';

class CreancePDF extends TCPDF {
    protected $title;
    protected $isLandscape;
    
    // Chemins des polices TrueType
    private $fontRegular;
    private $fontBold;
    
    public function __construct($title = 'Rapport de Créances', $landscape = true) {
        $this->title = $title;
        $this->isLandscape = $landscape;
        
        // Définir chemins des polices
        $this->fontRegular = __DIR__ . '/../assets/fonts/Merriweather.ttf';
        $this->fontBold = __DIR__ . '/../vendor/tecnickcom/tcpdf/fonts/dejavusans_bold.ttf';
        
        // Vérifier si les polices existent, sinon utiliser chemin alternatif
        if (!file_exists($this->fontRegular)) {
            $this->fontRegular = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
        }
        if (!file_exists($this->fontBold)) {
            $this->fontBold = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
        }
        
        $orientation = $landscape ? 'L' : 'P';
        parent::__construct($orientation, 'mm', 'A4', true, 'UTF-8', false);
        
        $this->SetCreator(PDF_AUTHOR);
        $this->SetAuthor(PDF_AUTHOR);
        $this->SetTitle($this->title);
        $this->SetSubject('Rapport de gestion des créances');
        $this->SetKeywords('créances, provisions, rapport, gestion');
        
        $this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->SetHeaderMargin(5);
        $this->SetFooterMargin(10);
        $this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $this->SetFont('helvetica', '', 8);
    }
    
    public function Header() {
        $this->SetY(10);
        $this->SetFont('helvetica', 'B', 18);
        $this->SetTextColor(33, 37, 41);
        $this->Cell(0, 10, $this->title, 0, 1, 'C');
        
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(108, 117, 125);
        $this->SetDrawColor(200, 200, 200);
        $this->SetLineWidth(0.3);
        $y = $this->GetY() + 1;
        $this->SetY($y + 3);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
    
    public function generateReport($donnees, $stats = null, $filters = []) {
        $this->AddPage();
        
        if (!empty($filters)) {
            $this->addFiltersInfo($filters);
        }
        
        if ($stats) {
            $this->addStatsTable($stats);
        }
        
        $this->addCreancesTable($donnees);
    }
    
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
    
    private function addStatsTable($stats) {
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 8, 'Tableau Créance-Provision', 0, 1, 'L');
        $this->Ln(2);
        
        $headers = ['CRÉANCE EN TTC', 'CRÉANCE EN HT', 'PROVISIONS EN TTC', 'PROVISIONS EN HT'];
        $values = [
            number_format($stats['creance_ttc'], 2, ',', ' '),
            number_format($stats['creance_ht'], 2, ',', ' '),
            number_format($stats['provision_ttc'], 2, ',', ' '),
            number_format($stats['provision_ht'], 2, ',', ' ')
        ];
        
        $cellWidth = $this->isLandscape ? 68 : 45;
        
        $this->SetFont('helvetica', 'B', 9);
        $this->SetFillColor(100, 100, 100);
        $this->SetTextColor(255, 255, 255);
        
        $startY = $this->GetY();
        $currentX = $this->GetX();
        
        foreach ($headers as $header) {
            $this->SetXY($currentX, $startY);
            $this->MultiCell($cellWidth, 10, $header, 1, 'C', true, 0);
            $currentX += $cellWidth;
        }
        $this->Ln(10);
        
        $this->SetFont('helvetica', 'B', 10);
        $this->SetFillColor(230, 230, 230);
        $this->SetTextColor(0, 0, 0);
        
        foreach ($values as $value) {
            $this->Cell($cellWidth, 10, $value, 1, 0, 'C', true);
        }
        $this->Ln();
        
        $this->Ln(10);
    }
    
    private function addCreancesTable($donnees) {
        if (empty($donnees)) {
            $this->SetFont('helvetica', 'I', 12);
            $this->Cell(0, 10, 'Aucune donnée à afficher', 0, 1, 'C');
            return;
        }
        
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 8, 'Détail des Créances', 0, 1, 'L');
        $this->Ln(2);
        
        $headers = [
            'RÉGION', 'SECTEUR', 'CLIENT', "INTITULÉ\nMARCHÉ", 
            "N° FACTURE\n/ SITUATION", 'DATE', 'NATURE', 
            "MONTANT\nTOTAL", "ENCAISSE-\nMENT", "MONTANT\nCRÉANCE",
            "ÂGE", "%\nPROV.", "PROVISION\n2024", 'OBS.'
        ];
        
        $colWidths = [18, 18, 23, 28, 23, 16, 18, 20, 20, 20, 16, 13, 20, 14];
        
        $this->SetFont('helvetica', 'B', 6.5);
        $this->SetFillColor(60, 60, 60);
        $this->SetTextColor(255, 255, 255);
        
        $startY = $this->GetY();
        $maxHeight = 12;
        $currentX = $this->GetX();
        
        foreach ($headers as $i => $header) {
            $this->SetXY($currentX, $startY);
            $this->MultiCell($colWidths[$i], $maxHeight, $header, 1, 'C', true, 0);
            $currentX += $colWidths[$i];
        }
        
        $this->Ln($maxHeight);
        
        $this->SetFont('helvetica', '', 6);
        $this->SetTextColor(0, 0, 0);
        $fillColor1 = [245, 245, 245];
        $fillColor2 = [255, 255, 255];
        $currentFill = true;
        
        foreach ($donnees as $ligne) {
            $this->SetFillColor(
                $currentFill ? $fillColor1[0] : $fillColor2[0], 
                $currentFill ? $fillColor1[1] : $fillColor2[1], 
                $currentFill ? $fillColor1[2] : $fillColor2[2]
            );
            
            // SANS TRONCATURE
            $rowData = [
                $ligne['region'],
                $ligne['secteur'],
                $ligne['client'],
                $ligne['intitule_marche'],
                $ligne['num_facture_situation'],
                $ligne['date_str'],
                $ligne['nature'],
                number_format($ligne['montant_total'], 2, ',', ' '),
                $ligne['montant_creance'] == 0 ? '' : number_format($ligne['encaissement'], 2, ',', ' '),
                number_format($ligne['montant_creance'], 2, ',', ' '),
                $ligne['age_annees'] . 'a',
                $ligne['pct_provision'] . '%',
                number_format($ligne['provision_2024'], 2, ',', ' '),
                $ligne['observation'] ?? ''
            ];
            
            // Calculer hauteur nécessaire
            $maxLines = 1;
            foreach ($rowData as $i => $data) {
                $lines = $this->getNumLines($data, $colWidths[$i]);
                if ($lines > $maxLines) {
                    $maxLines = $lines;
                }
            }
            
            $rowHeight = max(8, 4 + ($maxLines * 4));
            
            $startX = $this->GetX();
            $startY = $this->GetY();
            
            foreach ($rowData as $i => $data) {
                $this->SetXY($startX, $startY);
                // CENTRAGE VERTICAL 'M' = Middle
                $this->MultiCell($colWidths[$i], $rowHeight, $data, 1, 'C', true, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $startX += $colWidths[$i];
            }
            
            $this->Ln($rowHeight);
            $currentFill = !$currentFill;
            
            if ($this->GetY() > 175) {
                $this->AddPage();
                
                $this->SetFont('helvetica', 'B', 6.5);
                $this->SetFillColor(60, 60, 60);
                $this->SetTextColor(255, 255, 255);
                
                $startY = $this->GetY();
                $currentX = $this->GetX();
                
                foreach ($headers as $i => $header) {
                    $this->SetXY($currentX, $startY);
                    $this->MultiCell($colWidths[$i], $maxHeight, $header, 1, 'C', true, 0);
                    $currentX += $colWidths[$i];
                }
                
                $this->Ln($maxHeight);
                $this->SetFont('helvetica', '', 6);
                $this->SetTextColor(0, 0, 0);
            }
        }
    }
    
    public function addCharts($chartImages) {
        if (empty($chartImages)) {
            return;
        }
        
        foreach ($chartImages as $title => $imagePath) {
            if (!file_exists($imagePath)) {
                continue;
            }
            
            $this->AddPage();
            
            $this->SetFont('helvetica', 'B', 14);
            $this->Cell(0, 10, $title, 0, 1, 'C');
            $this->Ln(5);
            
            $imageInfo = @getimagesize($imagePath);
            if ($imageInfo === false) {
                continue;
            }
            
            list($originalWidth, $originalHeight) = $imageInfo;
            
            $maxWidth = 260;
            $maxHeight = 160;
            
            $widthRatio = $maxWidth / $originalWidth;
            $heightRatio = $maxHeight / $originalHeight;
            $ratio = min($widthRatio, $heightRatio);
            
            $finalWidth = $originalWidth * $ratio;
            $finalHeight = $originalHeight * $ratio;
            
            $x = ($this->getPageWidth() - $finalWidth) / 2;
            $y = $this->GetY();
            
            $this->Image($imagePath, $x, $y, $finalWidth, $finalHeight, 'PNG');
        }
    }
    
    private function formatValue($val) {
        if (abs($val) >= 1e9) {
            return number_format($val / 1e9, 2, '.', '') . 'B';
        } elseif (abs($val) >= 1e6) {
            return number_format($val / 1e6, 2, '.', '') . 'M';
        } elseif (abs($val) >= 1e3) {
            return number_format($val / 1e3, 0, '.', '') . 'K';
        } else {
            return number_format($val, 0, '.', '');
        }
    }
    
    /**
     * HELPER: Dessiner texte centré avec TrueType
     */
    private function drawCenteredText($image, $text, $x, $y, $fontSize, $color, $fontPath) {
        if (file_exists($fontPath)) {
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
            $textWidth = abs($bbox[4] - $bbox[0]);
            $textHeight = abs($bbox[5] - $bbox[1]);
            $drawX = $x - ($textWidth / 2);
            $drawY = $y + ($textHeight / 2);
            imagettftext($image, $fontSize, 0, (int)$drawX, (int)$drawY, $color, $fontPath, $text);
        } else {
            // Fallback bitmap
            $textWidth = imagefontwidth(5) * strlen($text);
            imagestring($image, 5, (int)($x - $textWidth/2), (int)($y - 8), $text, $color);
        }
    }
    
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
        if ($image === false) {
            return false;
        }
        
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $blue = imagecolorallocate($image, 0, 136, 254);
        $orange = imagecolorallocate($image, 255, 128, 66);
        $gray = imagecolorallocate($image, 200, 200, 200);
        
        imagefill($image, 0, 0, $white);
        
        // Titre avec TrueType
        $this->drawCenteredText($image, 'Creances vs Provisions par Region', 600, 40, 20, $black, $this->fontBold);
        
        $chartX = 80;
        $chartY = 100;
        $chartWidth = $width - 150;
        $chartHeight = $height - 180;
        
        $maxValue = 0;
        foreach ($regionData as $data) {
            $maxValue = max($maxValue, $data['creances'], $data['provisions']);
        }
        
        if ($maxValue == 0) {
            imagedestroy($image);
            return false;
        }
        
        imagesetthickness($image, 2);
        imageline($image, $chartX, $chartY, $chartX, $chartY + $chartHeight, $black);
        imageline($image, $chartX, $chartY + $chartHeight, $chartX + $chartWidth, $chartY + $chartHeight, $black);
        
        for ($i = 0; $i <= 5; $i++) {
            $y = (int)($chartY + $chartHeight - ($i * $chartHeight / 5));
            $value = ($maxValue / 5) * $i;
            imageline($image, $chartX - 5, $y, $chartX, $y, $gray);
            
            $valueText = $this->formatValue($value);
            if (file_exists($this->fontRegular)) {
                imagettftext($image, 10, 0, 10, $y + 4, $black, $this->fontRegular, $valueText);
            } else {
                imagestring($image, 3, 10, $y - 5, $valueText, $black);
            }
        }
        
        $regions = array_keys($regionData);
        $numRegions = count($regions);
        $barGroupWidth = $chartWidth / ($numRegions + 1);
        $barWidth = ($barGroupWidth / 3);
        
        foreach ($regions as $index => $region) {
            $x = (int)($chartX + ($index + 0.5) * $barGroupWidth);
            
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
            
            $regionLabel = strlen($region) > 12 ? substr($region, 0, 12) . '...' : $region;
            if (file_exists($this->fontRegular)) {
                $bbox = imagettfbbox(9, 0, $this->fontRegular, $regionLabel);
                $textWidth = abs($bbox[4] - $bbox[0]);
                imagettftext($image, 9, 0, (int)($x + $barWidth - $textWidth/2), (int)($chartY + $chartHeight + 20), $black, $this->fontRegular, $regionLabel);
            } else {
                imagestring($image, 3, $x, (int)($chartY + $chartHeight + 10), $regionLabel, $black);
            }
        }
        
        // Légende
        $legendX = $width - 220;
        $legendY = 120;
        imagefilledrectangle($image, $legendX, $legendY, $legendX + 40, $legendY + 25, $blue);
        if (file_exists($this->fontRegular)) {
            imagettftext($image, 12, 0, $legendX + 50, $legendY + 18, $black, $this->fontRegular, 'Creances');
        } else {
            imagestring($image, 4, $legendX + 45, $legendY + 8, 'Creances', $black);
        }
        
        imagefilledrectangle($image, $legendX, $legendY + 40, $legendX + 40, $legendY + 65, $orange);
        if (file_exists($this->fontRegular)) {
            imagettftext($image, 12, 0, $legendX + 50, $legendY + 58, $black, $this->fontRegular, 'Provisions');
        } else {
            imagestring($image, 4, $legendX + 45, $legendY + 48, 'Provisions', $black);
        }
        
        imagepng($image, $outputPath);
        imagedestroy($image);
        
        return file_exists($outputPath);
    }

    public function generatePieChart($donnees, $outputPath) {
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
        
        $width = 1600;
        $height = 1400;
        $image = @imagecreatetruecolor($width, $height);
        
        if ($image === false) {
            return false;
        }
        
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
        imageantialias($image, true);
        
        // TITRE AVEC TRUETYPE - PLUS GRAND
        $this->drawCenteredText($image, 'Repartition par Secteur', 800, 60, 28, $black, $this->fontBold);
        
        $total = array_sum($secteurData);
        if ($total == 0) {
            imagedestroy($image);
            return false;
        }
        
        $centerX = 700;
        $centerY = 600;
        $radius = 400;
        
        $currentAngle = 0;
        $colorIndex = 0;
        $slices = [];
        
        foreach ($secteurData as $secteur => $montant) {
            $sliceAngle = ($montant / $total) * 360;
            $endAngle = $currentAngle + $sliceAngle;
            
            imagefilledarc($image, $centerX, $centerY, $radius * 2, $radius * 2, 
                           (int)$currentAngle, (int)$endAngle, 
                           $colors[$colorIndex % count($colors)], IMG_ARC_PIE);
            
            $labelAngle = $currentAngle + ($sliceAngle / 2);
            $percentage = ($montant / $total) * 100;
            
            $slices[] = [
                'secteur' => $secteur,
                'montant' => $montant,
                'percentage' => $percentage,
                'labelAngle' => deg2rad($labelAngle),
                'color' => $colors[$colorIndex % count($colors)]
            ];
            
            $currentAngle = $endAngle;
            $colorIndex++;
        }
        
        usort($slices, function($a, $b) {
            return $b['montant'] <=> $a['montant'];
        });
        
        $placedLabels = [];
        $bgColor = imagecolorallocatealpha($image, 255, 255, 255, 20);
        
        $useTTF = file_exists($this->fontRegular);
        $fontSize = 13; // TAILLE AUGMENTÉE
        
        foreach ($slices as $slice) {
            $secteur = $slice['secteur'];
            $percentage = $slice['percentage'];
            $labelAngle = $slice['labelAngle'];
            
            $secteurLabel = strlen($secteur) > 20 ? substr($secteur, 0, 20) . '...' : $secteur;
            $percentLabel = number_format($percentage, 1) . '%';
            
            if ($useTTF) {
                $bbox1 = imagettfbbox($fontSize, 0, $this->fontRegular, $secteurLabel);
                $bbox2 = imagettfbbox($fontSize, 0, $this->fontRegular, $percentLabel);
                $labelWidth = max(abs($bbox1[4] - $bbox1[0]), abs($bbox2[4] - $bbox2[0]));
                $labelHeight = abs($bbox1[5] - $bbox1[1]) + abs($bbox2[5] - $bbox2[1]) + 15;
            } else {
                $labelWidth = max(strlen($secteurLabel), strlen($percentLabel)) * 10;
                $labelHeight = 40;
            }
            
            $placed = false;
            $extraDist = 0;
            $maxExtra = 350;
            
            while (!$placed && $extraDist <= $maxExtra) {
                $dist = $radius + 50 + $extraDist;
                $anchorX = (int)($centerX + cos($labelAngle) * $dist);
                $anchorY = (int)($centerY + sin($labelAngle) * $dist);
                
                $isRight = cos($labelAngle) > 0;
                if ($isRight) {
                    $left = $anchorX + 15;
                } else {
                    $left = $anchorX - $labelWidth - 15;
                }
                
                if ($left < 10 || $left + $labelWidth > $width - 10) {
                    $extraDist += 40;
                    continue;
                }
                
                $top = $anchorY - ($labelHeight / 2);
                
                $bgX = $left - 10;
                $bgY = $top - 10;
                $bgW = $labelWidth + 20;
                $bgH = $labelHeight + 20;
                
                $box = [
                    'minx' => $bgX,
                    'miny' => $bgY,
                    'maxx' => $bgX + $bgW,
                    'maxy' => $bgY + $bgH
                ];
                
                $intersects = false;
                foreach ($placedLabels as $prev) {
                    $prevBox = $prev['box'];
                    if (!($box['maxx'] < $prevBox['minx'] || $box['minx'] > $prevBox['maxx'] || 
                          $box['maxy'] < $prevBox['miny'] || $box['miny'] > $prevBox['maxy'])) {
                        $intersects = true;
                        break;
                    }
                }
                
                if (!$intersects) {
                    $placed = true;
                    $placedLabels[] = [
                        'box' => $box,
                        'bgX' => $bgX,
                        'bgY' => $bgY,
                        'bgW' => $bgW,
                        'bgH' => $bgH,
                        'left' => $left,
                        'top' => $top,
                        'sectLabel' => $secteurLabel,
                        'percentLabel' => $percentLabel,
                        'labelAngle' => $labelAngle,
                        'isRight' => $isRight,
                        'labelWidth' => $labelWidth
                    ];
                } else {
                    $extraDist += 40;
                }
            }
        }
        
        // Dessiner lignes de connexion
        imagesetthickness($image, 2);
        foreach ($placedLabels as $label) {
            $labelAngle = $label['labelAngle'];
            $edgeX = (int)($centerX + cos($labelAngle) * $radius);
            $edgeY = (int)($centerY + sin($labelAngle) * $radius);
            $connectX = $label['isRight'] ? $label['bgX'] : $label['bgX'] + $label['bgW'];
            $connectY = $label['bgY'] + ($label['bgH'] / 2);
            
            imageline($image, $edgeX, $edgeY, $connectX, $connectY, $black);
        }
        
        // Dessiner fonds
        foreach ($placedLabels as $label) {
            imagefilledrectangle($image, $label['bgX'], $label['bgY'], 
                               $label['bgX'] + $label['bgW'], $label['bgY'] + $label['bgH'], $bgColor);
        }
        
        // Dessiner textes avec TrueType
        foreach ($placedLabels as $label) {
            if ($useTTF) {
                $sectY = $label['top'] + 20;
                $percentY = $sectY + 25;
                
                imagettftext($image, $fontSize, 0, $label['left'], $sectY, $black, $this->fontBold, $label['sectLabel']);
                imagettftext($image, $fontSize, 0, $label['left'], $percentY, $black, $this->fontRegular, $label['percentLabel']);
            } else {
                imagestring($image, 5, $label['left'], $label['top'], $label['sectLabel'], $black);
                imagestring($image, 5, $label['left'], $label['top'] + 20, $label['percentLabel'], $black);
            }
        }
        
        // Légende
        $legendY = 1150;
        $legendX = 100;
        $colorIndex = 0;
        foreach ($secteurData as $secteur => $montant) {
            imagefilledrectangle($image, $legendX, $legendY, $legendX + 45, $legendY + 30, 
                                $colors[$colorIndex % count($colors)]);
            imagerectangle($image, $legendX, $legendY, $legendX + 45, $legendY + 30, $black);
            
            $secteurLabel = strlen($secteur) > 24 ? substr($secteur, 0, 24) . '...' : $secteur;
            
            if ($useTTF) {
                imagettftext($image, 11, 0, $legendX + 55, $legendY + 21, $black, $this->fontRegular, $secteurLabel);
            } else {
                imagestring($image, 4, $legendX + 50, $legendY + 8, $secteurLabel, $black);
            }
            
            $legendX += 280;
            if ($legendX > 1400) {
                $legendX = 100;
                $legendY += 45;
            }
            $colorIndex++;
        }
        
        $result = @imagepng($image, $outputPath);
        imagedestroy($image);
        
        return $result && file_exists($outputPath);
    }
    
    /**
     * SPIDER RADAR - VERSION ULTRA ZOOMÉE + TRUETYPE
     */
    public function generateRadarChart($donnees, $outputPath) {
        $naturesData = [];
        
        foreach ($donnees as $ligne) {
            $nature = $ligne['nature'];
            $montantCreance = floatval($ligne['montant_creance']);
            $provision = floatval($ligne['provision_2024']);
            
            if (!isset($naturesData[$nature])) {
                $naturesData[$nature] = ['creances' => 0.0, 'provisions' => 0.0];
            }
            $naturesData[$nature]['creances'] += $montantCreance;
            $naturesData[$nature]['provisions'] += $provision;
        }
        
        if (count($naturesData) < 3) {
            return false;
        }
        
        // DIMENSIONS OPTIMALES POUR ZOOM MAXIMUM
        $width = 1000;
        $height = 1000;
        
        $image = @imagecreatetruecolor($width, $height);
        if ($image === false) {
            return false;
        }
        
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $darkText = imagecolorallocate($image, 44, 62, 80);
        
        $blueStroke = imagecolorallocate($image, 0, 136, 254);
        $orangeStroke = imagecolorallocate($image, 255, 128, 66);
        
        $blueFill = imagecolorallocatealpha($image, 0, 136, 254, 90);
        $orangeFill = imagecolorallocatealpha($image, 255, 128, 66, 90);
        
        $gridColor = imagecolorallocate($image, 220, 220, 220);
        $gridLight = imagecolorallocate($image, 240, 240, 240);
        
        imagefill($image, 0, 0, $white);
        imageantialias($image, true);
        
        // TITRE AVEC TRUETYPE - GRAND
        $useTTF = file_exists($this->fontBold);
        if ($useTTF) {
            $titleText = 'Spider Radar par Nature';
            $bbox = imagettfbbox(22, 0, $this->fontBold, $titleText);
            $titleWidth = abs($bbox[4] - $bbox[0]);
            imagettftext($image, 22, 0, (int)(($width - $titleWidth) / 2), 50, $darkText, $this->fontBold, $titleText);
        } else {
            imagestring($image, 5, 300, 30, 'Spider Radar par Nature', $darkText);
        }
        
        // PARAMÈTRES RADAR - MAXIMUM ZOOM
        $centerX = 500;
        $centerY = 530;
        $maxRadius = 400; // RAYON MAXIMUM
        
        $maxValue = 0;
        foreach ($naturesData as $data) {
            $maxValue = max($maxValue, $data['creances'], $data['provisions']);
        }
        
        if ($maxValue == 0) {
            imagedestroy($image);
            return false;
        }
        
        $natures = array_keys($naturesData);
        $n = count($natures);
        
        // GRILLE CIRCULAIRE
        imagesetthickness($image, 1);
        
        for ($level = 1; $level <= 5; $level++) {
            $radius = ($maxRadius / 5) * $level;
            $color = ($level == 5) ? $gridColor : $gridLight;
            
            imageellipse($image, $centerX, $centerY, (int)($radius * 2), (int)($radius * 2), $color);
            
            $levelValue = ($maxValue / 5) * $level;
            $labelText = $this->formatValue($levelValue);
            
            $labelX = $centerX + (int)$radius + 20;
            $labelY = $centerY;
            
            if ($useTTF) {
                imagettftext($image, 11, 0, $labelX, $labelY + 4, $darkText, $this->fontRegular, $labelText);
            } else {
                imagestring($image, 3, $labelX, $labelY - 6, $labelText, $darkText);
            }
        }
        
        // AXES RADIAUX
        imagesetthickness($image, 1);
        for ($i = 0; $i < $n; $i++) {
            $angle = (2 * M_PI / $n) * $i - (M_PI / 2);
            $x = $centerX + (int)(cos($angle) * $maxRadius);
            $y = $centerY + (int)(sin($angle) * $maxRadius);
            imageline($image, $centerX, $centerY, $x, $y, $gridColor);
        }
        
        // LABELS DES NATURES - POLICE GRANDE
        $labelDistance = $maxRadius + 50;
        
        for ($i = 0; $i < $n; $i++) {
            $angle = (2 * M_PI / $n) * $i - (M_PI / 2);
            
            $labelX = $centerX + (int)(cos($angle) * $labelDistance);
            $labelY = $centerY + (int)(sin($angle) * $labelDistance);
            
            $nature = $natures[$i];
            $natureLabel = strlen($nature) > 15 ? substr($nature, 0, 15) . '...' : $nature;
            
            if ($useTTF) {
                $bbox = imagettfbbox(12, 0, $this->fontBold, $natureLabel);
                $textWidth = abs($bbox[4] - $bbox[0]);
                $textHeight = abs($bbox[5] - $bbox[1]);
                
                if ($angle >= -M_PI/4 && $angle <= M_PI/4) {
                    // Droite
                    $drawX = $labelX + 15;
                    $drawY = $labelY + ($textHeight / 2);
                } elseif ($angle > M_PI/4 && $angle < 3*M_PI/4) {
                    // Bas
                    $drawX = $labelX - ($textWidth / 2);
                    $drawY = $labelY + $textHeight + 15;
                } elseif ($angle >= 3*M_PI/4 || $angle <= -3*M_PI/4) {
                    // Gauche
                    $drawX = $labelX - $textWidth - 15;
                    $drawY = $labelY + ($textHeight / 2);
                } else {
                    // Haut
                    $drawX = $labelX - ($textWidth / 2);
                    $drawY = $labelY - 10;
                }
                
                imagettftext($image, 12, 0, (int)$drawX, (int)$drawY, $darkText, $this->fontBold, $natureLabel);
            } else {
                $textWidth = imagefontwidth(5) * strlen($natureLabel);
                
                if ($angle >= -M_PI/4 && $angle <= M_PI/4) {
                    $labelX += 12;
                } elseif ($angle > M_PI/4 && $angle < 3*M_PI/4) {
                    $labelX -= (int)($textWidth / 2);
                    $labelY += 12;
                } elseif ($angle >= 3*M_PI/4 || $angle <= -3*M_PI/4) {
                    $labelX -= $textWidth + 12;
                } else {
                    $labelX -= (int)($textWidth / 2);
                    $labelY -= 12;
                }
                
                imagestring($image, 5, $labelX, $labelY, $natureLabel, $darkText);
            }
        }
        
        // POLYGONE CRÉANCES (BLEU)
        $pointsCreances = [];
        for ($i = 0; $i < $n; $i++) {
            $angle = (2 * M_PI / $n) * $i - (M_PI / 2);
            $value = $naturesData[$natures[$i]]['creances'];
            $radius = ($value / $maxValue) * $maxRadius;
            $pointsCreances[] = $centerX + (int)(cos($angle) * $radius);
            $pointsCreances[] = $centerY + (int)(sin($angle) * $radius);
        }
        
        if ($n >= 3 && count($pointsCreances) >= 6) {
            imagefilledpolygon($image, $pointsCreances, $blueFill);
        }
        
        imagesetthickness($image, 4);
        if ($n >= 3 && count($pointsCreances) >= 6) {
            imagepolygon($image, $pointsCreances, $blueStroke);
        }
        imagesetthickness($image, 1);
        
        // Points bleus avec valeurs
        for ($i = 0; $i < $n; $i++) {
            $x = $pointsCreances[$i * 2];
            $y = $pointsCreances[$i * 2 + 1];
            
            imagefilledellipse($image, $x, $y, 18, 18, $white);
            imagefilledellipse($image, $x, $y, 16, 16, $blueStroke);
            
            $value = $naturesData[$natures[$i]]['creances'];
            $valueText = $this->formatValue($value);
            
            $angle = (2 * M_PI / $n) * $i - (M_PI / 2);
            $offsetX = cos($angle) * 35;
            $offsetY = sin($angle) * 35;
            
            $textX = $x + (int)$offsetX;
            $textY = $y + (int)$offsetY;
            
            if ($useTTF) {
                $bbox = imagettfbbox(11, 0, $this->fontBold, $valueText);
                $textWidth = abs($bbox[4] - $bbox[0]);
                imagettftext($image, 11, 0, (int)($textX - $textWidth/2), (int)($textY + 4), $blueStroke, $this->fontBold, $valueText);
            } else {
                imagestring($image, 4, $textX, $textY - 6, $valueText, $blueStroke);
            }
        }
        
        // POLYGONE PROVISIONS (ORANGE)
        $pointsProvisions = [];
        for ($i = 0; $i < $n; $i++) {
            $angle = (2 * M_PI / $n) * $i - (M_PI / 2);
            $value = $naturesData[$natures[$i]]['provisions'];
            $radius = ($value / $maxValue) * $maxRadius;
            $pointsProvisions[] = $centerX + (int)(cos($angle) * $radius);
            $pointsProvisions[] = $centerY + (int)(sin($angle) * $radius);
        }
        
        if ($n >= 3 && count($pointsProvisions) >= 6) {
            imagefilledpolygon($image, $pointsProvisions, $orangeFill);
        }
        
        imagesetthickness($image, 4);
        if ($n >= 3 && count($pointsProvisions) >= 6) {
            imagepolygon($image, $pointsProvisions, $orangeStroke);
        }
        imagesetthickness($image, 1);
        
        // Points orange avec valeurs
        for ($i = 0; $i < $n; $i++) {
            $x = $pointsProvisions[$i * 2];
            $y = $pointsProvisions[$i * 2 + 1];
            
            imagefilledellipse($image, $x, $y, 18, 18, $white);
            imagefilledellipse($image, $x, $y, 16, 16, $orangeStroke);
            
            $value = $naturesData[$natures[$i]]['provisions'];
            $valueText = $this->formatValue($value);
            
            $angle = (2 * M_PI / $n) * $i - (M_PI / 2);
            $offsetX = cos($angle) * 35;
            $offsetY = sin($angle) * 35;
            
            $textX = $x + (int)$offsetX;
            $textY = $y + (int)$offsetY;
            
            if ($useTTF) {
                $bbox = imagettfbbox(11, 0, $this->fontBold, $valueText);
                $textWidth = abs($bbox[4] - $bbox[0]);
                imagettftext($image, 11, 0, (int)($textX - $textWidth/2), (int)($textY + 15), $orangeStroke, $this->fontBold, $valueText);
            } else {
                imagestring($image, 4, $textX, $textY + 8, $valueText, $orangeStroke);
            }
        }
        
        // LÉGENDE - POLICE GRANDE
        $legendX = 100;
        $legendY = $height - 100;
        
        imagefilledellipse($image, $legendX, $legendY, 18, 18, $white);
        imagefilledellipse($image, $legendX, $legendY, 16, 16, $blueStroke);
        
        if ($useTTF) {
            imagettftext($image, 14, 0, $legendX + 25, $legendY + 5, $darkText, $this->fontRegular, 'Creances');
        } else {
            imagestring($image, 5, $legendX + 25, $legendY - 8, 'Creances', $darkText);
        }
        
        imagefilledellipse($image, $legendX, $legendY + 45, 18, 18, $white);
        imagefilledellipse($image, $legendX, $legendY + 45, 16, 16, $orangeStroke);
        
        if ($useTTF) {
            imagettftext($image, 14, 0, $legendX + 25, $legendY + 50, $darkText, $this->fontRegular, 'Provisions');
        } else {
            imagestring($image, 5, $legendX + 25, $legendY + 37, 'Provisions', $darkText);
        }
        
        $result = @imagepng($image, $outputPath, 9);
        imagedestroy($image);
        
        return $result && file_exists($outputPath);
    }
    
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
 * Classe ReportGenerator
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
    
    public function generateFullReport($filters = [], $archived = 0, $chartOptions = []) {
        try {
            $donnees = $this->creance->getAll($filters, $archived, 1, 10000);
            
            if (empty($donnees)) {
                throw new Exception('Aucune donnée à exporter');
            }
            
            $stats = $archived === 0 ? $this->creance->getStats($filters) : null;
            
            $title = $archived ? 'Rapport d\'Archive des Créances' : 'Rapport de Gestion des Créances';
            $pdf = new CreancePDF($title, true);
            
            $pdf->generateReport($donnees, $stats, $filters);
            
            $chartImages = [];
            if ($archived === 0 && !empty($chartOptions)) {
                
                if (!empty($chartOptions['bar_chart'])) {
                    $barChartPath = $this->tempDir . 'bar_chart_' . uniqid() . '.png';
                    if ($pdf->generateBarChart($donnees, $barChartPath)) {
                        $chartImages['Créances vs Provisions par Région'] = $barChartPath;
                    }
                }
                
                if (!empty($chartOptions['pie_chart'])) {
                    $pieChartPath = $this->tempDir . 'pie_chart_' . uniqid() . '.png';
                    if ($pdf->generatePieChart($donnees, $pieChartPath)) {
                        $chartImages['Répartition par Secteur'] = $pieChartPath;
                    }
                }
                
                if (!empty($chartOptions['radar_chart']) && $pdf->canGenerateRadarChart($donnees)) {
                    $radarChartPath = $this->tempDir . 'radar_chart_' . uniqid() . '.png';
                    if ($pdf->generateRadarChart($donnees, $radarChartPath)) {
                        $chartImages['Spider Radar par Nature'] = $radarChartPath;
                    }
                }
                
                if (!empty($chartImages)) {
                    $pdf->addCharts($chartImages);
                }
            }
            
            $exportsDir = ROOT_PATH . '/exports/';
            if (!is_dir($exportsDir)) {
                mkdir($exportsDir, 0755, true);
            }
            
            $filename = 'rapport_creances_' . date('Y-m-d_H-i-s') . '.pdf';
            $filepath = $exportsDir . $filename;
            
            $pdf->Output($filepath, 'F');
            
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
            error_log("Erreur génération PDF: " . $e->getMessage());
            
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
    
    public function canGenerateRadar($filters = [], $archived = 0) {
        try {
            $donnees = $this->creance->getAll($filters, $archived, 1, 10000);
            $pdf = new CreancePDF();
            return $pdf->canGenerateRadarChart($donnees);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function cleanupOldExports($daysOld = 7) {
        CreancePDF::cleanupTempFiles(ROOT_PATH . '/exports/', $daysOld * 24);
        CreancePDF::cleanupTempFiles($this->tempDir, 1);
    }
}
?>