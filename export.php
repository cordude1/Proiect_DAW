<?php
declare(strict_types=1);

require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Acces interzis');
}

$table  = $_GET['table']  ?? '';
$format = strtolower($_GET['format'] ?? 'csv');

/* Tabele permise */
$allowedTables = [
    'useri',
    'pacheteturistice',
    'rezervari',
    'destinatii'
];

if (!in_array($table, $allowedTables, true)) {
    exit('Tabel invalid');
}

$db  = new DatabaseConnector();
$pdo = $db->connect();

/* Luăm toate datele */
$stmt = $pdo->query("SELECT * FROM {$table}");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$data) {
    exit('Nu există date.');
}

/* EXCLUDERI AUTOMATE */
$excludedFields = ['parola']; // parola nu trebuie exportată

$headers = array_keys($data[0]);
$headers = array_values(array_diff($headers, $excludedFields));

/* Filtrăm datele */
$filteredData = [];
foreach ($data as $row) {
    foreach ($excludedFields as $field) {
        unset($row[$field]);
    }
    $filteredData[] = $row;
}

switch ($format) {

    case 'csv':

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$table}.csv\"");

        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);

        foreach ($filteredData as $row) {
            fputcsv($out, $row);
        }

        fclose($out);
        exit;

    case 'excel':

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray($headers, NULL, 'A1');
        $sheet->fromArray($filteredData, NULL, 'A2');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$table}.xlsx\"");

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;

    case 'pdf':

        $dompdf = new Dompdf();

        $html = "
        <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
            h2 { text-align: center; }
            table { width:100%; border-collapse: collapse; }
            th, td { border:1px solid #000; padding:6px; }
            th { background:#f0f0f0; }
        </style>";

        $html .= "<h2>Export tabel: {$table}</h2>";
        $html .= "<p>Data export: ".date('d-m-Y H:i')."</p>";
        $html .= "<table><tr>";

        foreach ($headers as $h) {
            $html .= "<th>{$h}</th>";
        }

        $html .= "</tr>";

        foreach ($filteredData as $row) {
            $html .= "<tr>";
            foreach ($row as $cell) {
                $html .= "<td>".htmlspecialchars((string)$cell)."</td>";
            }
            $html .= "</tr>";
        }

        $html .= "</table>";

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream("{$table}.pdf", ["Attachment" => true]);
        exit;

    default:
        exit('Format invalid');
}
