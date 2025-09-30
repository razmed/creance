<?php
require_once '../config/database.php';
require_once '../classes/PDF.php';

$generator = new ReportGenerator();
$generator->cleanupOldExports(7); // Nettoyer exports > 7 jours
echo "Nettoyage terminé\n";