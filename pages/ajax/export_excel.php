<?php
/**
 * AJAX - Exporter en Excel avec filtres automatiques
 * Gestion des Créances - Version Web
 */

session_start();
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../classes/Database.php';
require_once '../../classes/Creance.php';

// Vérifier que Composer est installé
if (!file_exists('../../vendor/autoload.php')) {
    die('Veuillez installer les dépendances avec "composer install"');
}

require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

try {
    $creance = new Creance();
    
    // Récupérer les paramètres
    $archived = intval($_GET['archived'] ?? 0);
    $filters = $_SESSION['filters'] ?? [];
    $search = $_SESSION['last_search'] ?? '';
    
    // Récupérer les données
    $donnees = $creance->getAll($filters, $archived, 1, MAX_EXPORT_ROWS, $search);
    
    if (empty($donnees)) {
        die('Aucune donnée à exporter');
    }
    
    // Créer un nouveau spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Titre du document
    $titre = $archived === 1 ? 'Archive des Créances' : 'Gestion des Créances';
    $sheet->setTitle(substr($titre, 0, 31));
    
    // En-têtes
    $headers = [
        'RÉGION', 'SECTEUR', 'CLIENT', 'INTITULÉ MARCHÉ', 'N° FACTURE / SITUATION',
        'DATE', 'NATURE', 'MONTANT TOTAL', 'ENCAISSEMENT', 'MONTANT CRÉANCE', 
        'ÂGE DE LA CRÉANCE', '% PROVISION', 'PROVISION 2024', 'OBSERVATION'
    ];
    
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $col++;
    }
    
    // Style des en-têtes
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 11
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '607D8B']
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ];
    
    $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(25);
    
    // Remplir les données
    $row = 2;
    foreach ($donnees as $ligne) {
        $sheet->setCellValue('A' . $row, $ligne['region']);
        $sheet->setCellValue('B' . $row, $ligne['secteur']);
        $sheet->setCellValue('C' . $row, $ligne['client']);
        $sheet->setCellValue('D' . $row, $ligne['intitule_marche']);
        $sheet->setCellValue('E' . $row, $ligne['num_facture_situation']);
        $sheet->setCellValue('F' . $row, $ligne['date_str']);
        $sheet->setCellValue('G' . $row, $ligne['nature']);
        $sheet->setCellValue('H' . $row, $ligne['montant_total']);
        $sheet->setCellValue('I' . $row, $ligne['montant_creance'] == 0 ? '' : $ligne['encaissement']);
        $sheet->setCellValue('J' . $row, $ligne['montant_creance']);
        $sheet->setCellValue('K' . $row, $ligne['age_annees'] . ' ans');
        $sheet->setCellValue('L' . $row, $ligne['pct_provision'] . '%');
        $sheet->setCellValue('M' . $row, $ligne['provision_2024']);
        $sheet->setCellValue('N' . $row, $ligne['observation'] ?? '');
        
        if ($row % 2 == 0) {
            $sheet->getStyle('A' . $row . ':N' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('F5F5F5');
        }
        
        $row++;
    }
    
    // **AJOUT CRITIQUE : Activer les filtres automatiques sur toutes les colonnes**
    // La plage va de A1 (première cellule en-tête) à N et la dernière ligne de données
    $lastRow = $row - 1; // Dernière ligne avec des données
    $sheet->setAutoFilter('A1:N' . $lastRow);
    
    // **AJOUT RECOMMANDÉ : Figer la première ligne pour garder les en-têtes visibles**
    $sheet->freezePane('A2');
    
    // Bordures
    $dataStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'CCCCCC']
            ]
        ],
        'alignment' => [
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ];
    
    $sheet->getStyle('A1:N' . ($row - 1))->applyFromArray($dataStyle);
    
    // Format numérique
    $sheet->getStyle('H2:H' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle('I2:I' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle('J2:J' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle('M2:M' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
    
    // Largeurs
    $columnWidths = [
        'A' => 15, 'B' => 15, 'C' => 20, 'D' => 30, 'E' => 20,
        'F' => 12, 'G' => 15, 'H' => 15, 'I' => 15, 'J' => 15,
        'K' => 15, 'L' => 12, 'M' => 15, 'N' => 20
    ];
    
    foreach ($columnWidths as $col => $width) {
        $sheet->getColumnDimension($col)->setWidth($width);
    }
    
    // Feuille stats (non archivé seulement)
    if ($archived === 0) {
        $stats = $creance->getStats($filters);
        
        $statsSheet = $spreadsheet->createSheet();
        $statsSheet->setTitle('Créance-Provision');
        
        $statsHeaders = ['CRÉANCE EN TTC', 'CRÉANCE EN HT', 'PROVISIONS EN TTC', 'PROVISIONS EN HT'];
        $col = 'A';
        foreach ($statsHeaders as $header) {
            $statsSheet->setCellValue($col . '1', $header);
            $col++;
        }
        
        $statsSheet->getStyle('A1:D1')->applyFromArray($headerStyle);
        
        $statsSheet->setCellValue('A2', $stats['creance_ttc']);
        $statsSheet->setCellValue('B2', $stats['creance_ht']);
        $statsSheet->setCellValue('C2', $stats['provision_ttc']);
        $statsSheet->setCellValue('D2', $stats['provision_ht']);
        
        $statsSheet->getStyle('A2:D2')->getNumberFormat()->setFormatCode('#,##0.00');
        $statsSheet->getStyle('A2:D2')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E8F5E9');
        
        foreach (range('A', 'D') as $col) {
            $statsSheet->getColumnDimension($col)->setWidth(20);
        }
        
        // **AJOUT : Filtres automatiques sur la feuille stats aussi**
        $statsSheet->setAutoFilter('A1:D2');
        
        // **AJOUT : Figer la première ligne de la feuille stats**
        $statsSheet->freezePane('A2');
        
        $spreadsheet->setActiveSheetIndex(0);
    }
    
    // Téléchargement
    $filename = 'creances_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);
    
} catch (Exception $e) {
    error_log("EXPORT_EXCEL - Erreur: " . $e->getMessage());
    http_response_code(500);
    echo "Erreur lors de l'export Excel: " . $e->getMessage();
}
?>