<?php

namespace App\Http\Controllers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class WebController extends Controller {
    public function rsOrderPrint() {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $fileName = 'export-order-'.date('Y-m-d H:i:s');
        $headerStyle = [
            'fill' => [
                'fillType' => Fill::FILL_GRADIENT_LINEAR,
                'rotation' => 90,
                'startColor' => [
                    'argb' => 'BFBF3F',
                ],
                'endColor' => [
                    'argb' => 'BFBF3F',
                ],
            ],
            'font' => [
                'name'  => 'Times New Roman',
                'bold'  => true,
                'size'  => 11,
            ],
            'alignment' => [
                'wrapText'  => true,
                'horizontal'    => Alignment::HORIZONTAL_CENTER,
                'vertical'      => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000'],
                ],
            ],
        ];

        $fillStyle = [
            'font' => [
                'name'  => 'Times New Roman',
                'size'  => 11,
            ],
            'alignment' => [
                'wrapText'  => true,
                'horizontal'    => Alignment::HORIZONTAL_CENTER,
                'vertical'      => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000'],
                ],
            ],
        ];
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);

        $sheet->setCellValue('A1', 'Order Number')->getStyle('A1')->applyFromArray($headerStyle);
        $sheet->setCellValue('B1', 'Table Number')->getStyle('B1')->applyFromArray($headerStyle);
        $sheet->setCellValue('C1', 'Name')->getStyle('C1')->applyFromArray($headerStyle);
        $sheet->setCellValue('D1', 'Order Type')->getStyle('D1')->applyFromArray($headerStyle);
        $sheet->setCellValue('E1', 'Total')->getStyle('E1')->applyFromArray($headerStyle);
        $sheet->setCellValue('F1', 'Created At')->getStyle('F1')->applyFromArray($headerStyle);


        $writer = new Xlsx($spreadsheet);
        ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$fileName.'".xlsx"');
        $writer->save("php://output");
        exit;
    }
}