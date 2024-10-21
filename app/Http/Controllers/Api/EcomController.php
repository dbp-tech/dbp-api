<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EcomProduct;
use App\Repositories\EcomRepository;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class EcomController extends Controller
{
    protected $ecomRepo;

    public function __construct()
    {
        $this->ecomRepo = new EcomRepository();
    }

    public function categoryIndex(Request $request)
    {
        $filters = $request->only([]);
        return response()->json($this->ecomRepo->categoryIndex($filters, $request->header('company_id')));
    }

    public function categorySave(Request $request)
    {
        return response()->json($this->ecomRepo->categorySave($request->all(), $request->header('company_id')));
    }

    public function categoryDelete(Request $request, $id = null)
    {
        return response()->json($this->ecomRepo->categoryDelete($id, $request->header('company_id')));
    }

    public function productIndex(Request $request)
    {
        $filters = $request->only([]);
        return response()->json($this->ecomRepo->productIndex($filters, $request->header('company_id')));
    }

    public function productSave(Request $request)
    {
        return response()->json($this->ecomRepo->productSave($request->all(), $request->header('company_id')));
    }

    public function productDelete(Request $request, $id = null)
    {
        return response()->json($this->ecomRepo->productDelete($id, $request->header('company_id')));
    }

    public function getProductOnly($id, Request $request)
    {
        return response()->json($this->ecomRepo->getProductOnly($id, $request->header('company_id')));
    }

    public function deleteProductOnly($id)
    {
        return response()->json($this->ecomRepo->deleteProductOnly($id));
    }

    public function setProductOnly(Request $request)
    {
        return response()->json($this->ecomRepo->setProductOnly($request->all(), $request->header('company_id')));
    }

    public function storeIndex(Request $request)
    {
        $filters = $request->only([]);
        return response()->json($this->ecomRepo->storeIndex($filters, $request->header('company_id')));
    }

    public function storeSave(Request $request)
    {
        return response()->json($this->ecomRepo->storeSave($request->all(), $request->header('company_id')));
    }

    public function storeDelete(Request $request, $id = null)
    {
        return response()->json($this->ecomRepo->storeDelete($id, $request->header('company_id')));
    }

    public function marketplaceDetail(Request $request, $id = null)
    {
        return response()->json($this->ecomRepo->marketplaceDetail($id, $request->header('company_id')));
    }

    public function marketplaceProduct(Request $request, $id = null)
    {
        return response()->json($this->ecomRepo->marketplaceProduct($id, $request->all(), $request->header('company_id')));
    }

    public function storeMarketplaceProduct(Request $request)
    {
        return response()->json($this->ecomRepo->storeMarketplaceProduct($request->all(), $request->header('company_id')));
    }

    public function marketplaceOrder(Request $request, $id = null)
    {
        return response()->json($this->ecomRepo->marketplaceOrder($id, $request->all(), $request->header('company_id')));
    }

    public function marketplaceOrderDetail(Request $request, $orderId = null)
    {
        return response()->json($this->ecomRepo->marketplaceOrderDetail($orderId, $request->header('company_id')));
    }

    public function inquirySave(Request $request)
    {
        return response()->json($this->ecomRepo->inquirySave($request->all(), $request->header('company_id')));
    }

    public function inquiryDetail(Request $request)
    {
        return response()->json($this->ecomRepo->inquiryDetail($request->header('company_id')));
    }

    public function masterStatusIndex(Request $request)
    {
        return response()->json($this->ecomRepo->masterStatusIndex($request->header('company_id')));
    }

    public function masterStatusChangeStatus(Request $request)
    {
        return response()->json($this->ecomRepo->masterStatusChangeStatus($request->all(), $request->header('company_id')));
    }

    public function masterStatusDeleteStatus(Request $request, $id)
    {
        return response()->json($this->ecomRepo->masterStatusDeleteStatus($id, $request->header('company_id')));
    }

    public function masterFollowupIndex(Request $request)
    {
        return response()->json($this->ecomRepo->masterFollowupIndex($request->header('company_id')));
    }

    public function masterFollowupUpdate(Request $request, $id)
    {
        return response()->json($this->ecomRepo->masterFollowupUpdate($id, $request->all(), $request->header('company_id')));
    }

    public function uploadOrderFromOo(Request $request) {
        return response()->json($this->ecomRepo->uploadOrderFromOo($request));
    }

    public function downloadProductList(Request $request) {
        $filters = $request->only(['company_id']);
        $ecomProducts = EcomProduct::with([])
            ->where('company_id', $filters['company_id'])
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $fileName = 'product-list-'.date('Y-m-d H:i:s');
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

        $sheet->setCellValue('A1', 'Product ID')->getStyle('A1')->applyFromArray($headerStyle);
        $sheet->setCellValue('B1', 'SKU')->getStyle('B1')->applyFromArray($headerStyle);
        $sheet->setCellValue('C1', 'Name')->getStyle('C1')->applyFromArray($headerStyle);

        $num = 2;
        foreach ($ecomProducts as $item){
            $sheet->setCellValue('A'.$num, $item["id"])
                ->getStyle('A'.$num)->applyFromArray($fillStyle);
            $sheet->setCellValue('B'.$num, $item["sku"])
                ->getStyle('B'.$num)->applyFromArray($fillStyle);
            $sheet->setCellValue('C'.$num, $item['name'])
                ->getStyle('C'.$num)->applyFromArray($fillStyle);
            $num++;
        }


        $writer = new Xlsx($spreadsheet);
        ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$fileName.'".xlsx"');
        $writer->save("php://output");
        exit;
    }
}