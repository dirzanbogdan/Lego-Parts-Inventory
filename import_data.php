<?php
require_once __DIR__ . '/app/autoload.php';

use App\Services\ImportService;

$importer = new ImportService();
$importer->importAll();
