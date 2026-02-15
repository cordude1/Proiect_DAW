<?php
require_once __DIR__.'/lib/fpdf/fpdf.php';

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'PDF functioneaza!',0,1);
$pdf->Output();
