<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;

use App\Models\Pharmacy\Medicine;
use App\Models\Pharmacy\MedicineBatch;
use App\Models\Pharmacy\MedicineStockMovement;
use App\Models\Pharmacy\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PharmacyController extends Controller
{
    public function index(Request $request)
    {
        $stats = $this->dashboardStats();
        $suppliers = Supplier::orderBy('name')->get(['supplier_id', 'name']);

        return view('form.phamacy.pharmacyPage', compact('stats', 'suppliers'));
    }

    public function stats()
    {
        return response()->json($this->dashboardStats());
    }

    protected function dashboardStats(): array
    {
        return Cache::remember('pharmacy.dashboard_stats', 15, function () {
            $totalMedicine = Medicine::where('is_active', true)->count();

            $stockValue = MedicineBatch::join('medicines', 'medicines.medicine_id', '=', 'medicine_batches.medicine_id')
                ->where('medicine_batches.remaining_quantity', '>', 0)
                ->sum(DB::raw('medicine_batches.remaining_quantity * medicines.selling_price'));

            $lowStockList = Medicine::where('is_active', true)
                ->select('medicines.medicine_id', 'medicines.medicine_name', 'medicines.reorder_level')
                ->selectSub(
                    MedicineBatch::selectRaw('COALESCE(SUM(remaining_quantity), 0)')
                        ->whereColumn('medicine_batches.medicine_id', 'medicines.medicine_id'),
                    'stock_total'
                )
                ->havingRaw('stock_total <= COALESCE(medicines.reorder_level, 20)')
                ->orderBy('stock_total', 'asc')
                ->limit(10)
                ->get();

            $lowStock = Medicine::where('is_active', true)
                ->select('medicines.medicine_id')
                ->leftJoin('medicine_batches', 'medicine_batches.medicine_id', '=', 'medicines.medicine_id')
                ->groupBy('medicines.medicine_id', 'medicines.reorder_level')
                ->havingRaw('COALESCE(SUM(medicine_batches.remaining_quantity), 0) <= COALESCE(medicines.reorder_level, 20)')
                ->get()
                ->count();

            $expiringSoon = MedicineBatch::expiringSoon(30)
                ->where('remaining_quantity', '>', 0)
                ->count();

            return compact('totalMedicine', 'stockValue', 'lowStock', 'expiringSoon', 'lowStockList');
        });
    }

    protected function clearStatsCache(): void
    {
        Cache::forget('pharmacy.dashboard_stats');
    }

    public function data(Request $request)
    {
        $sortableColumns = [
            0 => 'medicine_name',
            1 => 'category',
            2 => null,
            3 => 'stock_total',
            4 => 'selling_price',
            5 => 'nearest_expiry',
            6 => null,
        ];

        $base = Medicine::query()
            ->select('medicines.*')
            ->selectSub(
                MedicineBatch::selectRaw('COALESCE(SUM(remaining_quantity), 0)')
                    ->whereColumn('medicine_batches.medicine_id', 'medicines.medicine_id'),
                'stock_total'
            )
            ->selectSub(
                MedicineBatch::selectRaw('MIN(expiry_date)')
                    ->whereColumn('medicine_batches.medicine_id', 'medicines.medicine_id')
                    ->where('remaining_quantity', '>', 0),
                'nearest_expiry'
            )

            ->selectRaw(
                "CASE
                    WHEN (SELECT COALESCE(SUM(remaining_quantity), 0) FROM medicine_batches WHERE medicine_batches.medicine_id = medicines.medicine_id) <= medicines.reorder_level THEN 1
                    WHEN (SELECT MIN(expiry_date) FROM medicine_batches WHERE medicine_batches.medicine_id = medicines.medicine_id AND remaining_quantity > 0) <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1
                    ELSE 0
                END as urgency_flag"
            )
            ->where('is_active', true);

        $recordsTotal = (clone $base)->count();

        // ---- search ----
        $searchValue = $request->input('search.value');
        if (!empty($searchValue)) {
            $base->where(function ($q) use ($searchValue) {
                $q->where('medicine_name', 'like', "%{$searchValue}%")
                    ->orWhere('category', 'like', "%{$searchValue}%")
                    ->orWhere('ndc_code', 'like', "%{$searchValue}%");
            });
        }

        // ---- quick filter triggered by dashboard cards ----
        $filter = $request->input('filter');
        if ($filter === 'low_stock') {
            $base->havingRaw('stock_total <= medicines.reorder_level');
        } elseif ($filter === 'expiring') {
            $base->havingRaw('nearest_expiry IS NOT NULL AND nearest_expiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)');
        }

        $recordsFiltered = (clone $base)->count();

        // ---- order: urgent rows always float to the top, then the requested column ----
        $orderCol = (int) $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $orderField = $sortableColumns[$orderCol] ?? 'medicine_name';

        $base->orderByRaw('urgency_flag DESC')
            ->orderBy($orderField, $orderDir);

        // ---- paging ----
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        if ($length > 0) {
            $base->skip($start)->take($length);
        }

        $rows = $base->get();

        $data = $rows->map(function (Medicine $medicine) {
            $isLowStock = (int) $medicine->stock_total <= (int) $medicine->reorder_level;
            $isExpiringSoon = $medicine->nearest_expiry
                && \Carbon\Carbon::parse($medicine->nearest_expiry)->lte(now()->addDays(30));

            return [
                'medicine_name' => $medicine->medicine_name,
                'category' => $medicine->category,
                'strength_display' => trim(($medicine->strength ?? '') . ($medicine->dosage_unit ?? '')),
                'stock_total' => (int) $medicine->stock_total,
                'selling_price' => (float) $medicine->selling_price,
                'nearest_expiry' => $medicine->nearest_expiry
                    ? \Carbon\Carbon::parse($medicine->nearest_expiry)->format('d-M-Y')
                    : '-',
                'actions' => view('form.phamacy._row_actions', compact('medicine'))->render(),
                'is_low_stock' => $isLowStock,
                'is_expiring_soon' => $isExpiringSoon,
            ];
        });

        //-----------filter-------------

        if ($request->filled('f_name')) {
            $base->where('medicine_name', 'like', '%' . $request->f_name . '%');
        }
        if ($request->filled('f_price_min')) {
            $base->where('selling_price', '>=', (float) $request->f_price_min);
        }
        if ($request->filled('f_price_max')) {
            $base->where('selling_price', '<=', (float) $request->f_price_max);
        }
        if ($request->filled('f_stock')) {
            if ($request->f_stock === 'out') {
                $base->havingRaw('stock_total = 0');
            } elseif ($request->f_stock === 'low') {
                $base->havingRaw('stock_total > 0 AND stock_total <= medicines.reorder_level');
            } elseif ($request->f_stock === 'normal') {
                $base->havingRaw('stock_total > medicines.reorder_level');
            }
        }
        if ($request->filled('f_expiry_days')) {
            $base->havingRaw('nearest_expiry IS NOT NULL AND nearest_expiry <= DATE_ADD(CURDATE(), INTERVAL ? DAY)', [(int) $request->f_expiry_days]);
        }
        //-----------end filter-------------
        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    protected function rules(): array
    {
        return [
            'medicine_name' => 'required|string|max:100',
            'ndc_code' => 'nullable|string|max:50',
            'category' => 'required|string|max:50',
            'unit' => 'required|string|max:20',
            'dosage_unit' => 'required|string|max:10',
            'strength' => 'required|string|max:30',
            'unit_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'pieces_per_unit' => 'required|integer|min:1',
            'reorder_level' => 'nullable|integer|min:0',
            'quantity_initial' => 'required|integer|min:0',
            'expiry_date' => 'required|date|after:today',
            'supplier_id' => 'nullable|exists:suppliers,supplier_id',
            'purchase_price' => 'required|numeric|min:0',
            'batch_number' => 'required|string|max:50|unique:medicine_batches,batch_number',
        ];
    }

    public function store(Request $request)
    {
        $rules = $this->rules();
        unset($rules['unit_price']);

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $data = $validator->validated();

        DB::transaction(function () use ($data) {
            $medicine = Medicine::create([
                'medicine_name' => $data['medicine_name'],
                'ndc_code' => $data['ndc_code'] ?? null,
                'category' => $data['category'],
                'unit' => $data['unit'],
                'dosage_unit' => $data['dosage_unit'],
                'strength' => $data['strength'],
                'unit_price' => $data['purchase_price'],
                'selling_price' => $data['selling_price'],
                'pieces_per_unit' => $data['pieces_per_unit'],
                'reorder_level' => $data['reorder_level'] ?? 20,
                'is_active' => true,
            ]);

            $piecesTotal = $data['quantity_initial'] * $data['pieces_per_unit'];

            $batch = MedicineBatch::create([
                'medicine_id' => $medicine->medicine_id,
                'supplier_id' => $data['supplier_id'] ?? null,
                'batch_number' => $data['batch_number'],
                'expiry_date' => $data['expiry_date'],
                'quantity_initial' => $piecesTotal,
                'remaining_quantity' => $piecesTotal,
                'purchase_price' => $data['purchase_price'],
            ]);

            MedicineStockMovement::create([
                'batch_id' => $batch->batch_id,
                'movement_type' => 'IN',
                'quantity' => $piecesTotal,
                'reference_type' => 'PURCHASE',
                'reference_id' => $batch->batch_id,
                'movement_date' => now(),
            ]);
        });

        $this->clearStatsCache();

        return response()->json(['message' => 'រក្សាទុកជោគជ័យ']);
    }

    public function edit(Medicine $medicine)
    {
        return response()->json($medicine->load([
            'batches' => function ($q) {
                $q->orderByDesc('created_at');
            }
        ]));
    }

    public function details($medicineId)
    {
        $medicine = Medicine::findOrFail($medicineId);

        $batches = MedicineBatch::where('medicine_id', $medicineId)
            ->orderByDesc('created_at')
            ->get();

        $movements = MedicineStockMovement::whereIn('batch_id', $batches->pluck('batch_id'))
            ->where('movement_type', 'OUT')
            ->orderBy('movement_date')
            ->get()
            ->groupBy('batch_id');

        $data = $batches->map(function ($batch) use ($movements) {
            return [
                'batch_number' => $batch->batch_number,
                'date_in' => optional($batch->created_at)->format('d-M-Y'),
                'quantity_initial' => $batch->quantity_initial,
                'expiry_date' => $batch->expiry_date ? \Carbon\Carbon::parse($batch->expiry_date)->format('d-M-Y') : '-',
                'purchase_price' => $batch->purchase_price,
                'remaining_quantity' => $batch->remaining_quantity,
                'outs' => ($movements->get($batch->batch_id) ?? collect())->map(fn($m) => [
                    'date' => \Carbon\Carbon::parse($m->movement_date)->format('d-M-Y'),
                    'quantity' => $m->quantity,
                ])->values(),
            ];
        });

        return response()->json([
            'medicine_name' => $medicine->medicine_name,
            'selling_price' => $medicine->selling_price,
            'batches' => $data,
        ]);
    }

    public function update(Request $request, Medicine $medicine)
    {
        $rules = $this->rules();

        unset(
            $rules['quantity_initial'],
            $rules['expiry_date'],
            $rules['supplier_id'],
            $rules['purchase_price'],
            $rules['batch_number']
        );

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $medicine->update($validator->validated());
        $this->clearStatsCache();

        return response()->json(['message' => 'កែប្រែជោគជ័យ']);
    }

    public function destroy(Medicine $medicine)
    {
        try {
            $medicine->update(['is_active' => false]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'លុបមិនបានសម្រេច: ' . $e->getMessage()], 500);
        }

        $this->clearStatsCache();

        return response()->json(['message' => 'លុបជោគជ័យ']);
    }

    public function addBatch(Request $request, Medicine $medicine)
    {
        $data = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,supplier_id',
            'batch_number' => 'required|string|max:50|unique:medicine_batches,batch_number',
            'expiry_date' => 'required|date|after:today',
            'quantity_initial' => 'required|integer|min:1',
            'purchase_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($data, $medicine) {
            $piecesTotal = $data['quantity_initial'] * $medicine->pieces_per_unit;

            $batch = MedicineBatch::create([
                'medicine_id' => $medicine->medicine_id,
                'supplier_id' => $data['supplier_id'] ?? null,
                'batch_number' => $data['batch_number'],
                'expiry_date' => $data['expiry_date'],
                'quantity_initial' => $piecesTotal,
                'remaining_quantity' => $piecesTotal,
                'purchase_price' => $data['purchase_price'],
            ]);

            MedicineStockMovement::create([
                'batch_id' => $batch->batch_id,
                'movement_type' => 'IN',
                'quantity' => $piecesTotal,
                'reference_type' => 'PURCHASE',
                'reference_id' => $batch->batch_id,
                'movement_date' => now(),
            ]);
        });

        $this->clearStatsCache();

        return response()->json(['message' => 'បន្ថែមស្តុកជោគជ័យ']);
    }


    public function export()
    {
        $medicines = Medicine::where('is_active', true)
            ->select('medicines.*')
            ->selectSub(
                MedicineBatch::selectRaw('COALESCE(SUM(remaining_quantity), 0)')
                    ->whereColumn('medicine_batches.medicine_id', 'medicines.medicine_id'),
                'stock_total'
            )
            ->selectSub(
                MedicineBatch::selectRaw('MIN(expiry_date)')
                    ->whereColumn('medicine_batches.medicine_id', 'medicines.medicine_id')
                    ->where('remaining_quantity', '>', 0),
                'nearest_expiry'
            )
            ->orderBy('medicine_name')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pharmacy Stock');

        $headers = ['ល.ខ', 'ឈ្មោះថ្នាំ', 'ប្រភេទ', 'ដូស', 'ស្តុកសរុប (គ្រាប់)', 'កម្រិតជូនដំណឹង', 'តម្លៃលក់ ($)', 'ថ្ងៃផុតកំណត់ជិតបំផុត', 'ស្ថានភាព'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        $row = 2;
        foreach ($medicines as $i => $medicine) {
            $isLowStock = $medicine->stock_total <= $medicine->reorder_level;
            $isExpiring = $medicine->nearest_expiry
                && \Carbon\Carbon::parse($medicine->nearest_expiry)->lte(now()->addDays(30));

            $status = $isLowStock && $isExpiring
                ? 'ស្តុកទាប + ជិតផុតកំណត់'
                : ($isLowStock ? 'ស្តុកទាប' : ($isExpiring ? 'ជិតផុតកំណត់' : 'ធម្មតា'));

            $sheet->fromArray([
                $i + 1,
                $medicine->medicine_name,
                $medicine->category,
                trim(($medicine->strength ?? '') . ($medicine->dosage_unit ?? '')),
                (int) $medicine->stock_total,
                $medicine->reorder_level,
                (float) $medicine->selling_price,
                $medicine->nearest_expiry ? \Carbon\Carbon::parse($medicine->nearest_expiry)->format('d-M-Y') : '-',
                $status,
            ], null, "A{$row}");

            if ($isLowStock && $isExpiring) {
                $color = 'E2D3F0'; // ស្វាយចាស់ — ទាំងពីរ
            } elseif ($isLowStock) {
                $color = 'FFF3CD'; // លឿង — ស្តុកជិតអស់
            } elseif ($isExpiring) {
                $color = 'F8D7DA'; // ក្រហម — ជិតផុតកំណត់
            } else {
                $color = null;
            }

            if ($color) {
                $sheet->getStyle("A{$row}:I{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB($color);
            }

            $row++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'pharmacy_stock_' . now()->format('Y-m-d_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }


    /**
     * Export the "Drug List Name" — just the names, active medicines only.
     */
    public function exportNames()
    {
        $names = Medicine::where('is_active', true)
            ->orderBy('medicine_name')
            ->pluck('medicine_name');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Drug List Name');

        $sheet->setCellValue('A1', 'Drug List Name');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A2', 'Name');
        $sheet->getStyle('A2')->getFont()->setBold(true);

        $row = 3;
        foreach ($names as $name) {
            $sheet->setCellValue("A{$row}", $name);
            $row++;
        }
        $sheet->getColumnDimension('A')->setWidth(45);

        $filename = 'drug_list_name_' . now()->format('Y-m-d_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportStockReport()
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        $medicines = Medicine::where('is_active', true)->orderBy('medicine_name')->get();

        $movements = MedicineStockMovement::join('medicine_batches', 'medicine_batches.batch_id', '=', 'medicine_stock_movements.batch_id')
            ->whereBetween('movement_date', [$start, $end])
            ->select(
                'medicine_batches.medicine_id',
                'medicine_stock_movements.movement_type',
                DB::raw('SUM(medicine_stock_movements.quantity) as total_qty')
            )
            ->groupBy('medicine_batches.medicine_id', 'medicine_stock_movements.movement_type')
            ->get()
            ->groupBy('medicine_id');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock Report');

        $title = 'REPORT STOCK MEDICINE ' . strtoupper($start->format('F Y'));
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = ['ល.ខ', 'ឈ្មោះប្រភេទថ្នំា', 'ថ្នំាក្នុងដៃ', 'ទិញចូល', 'ក្នុងស្តុកសរុប', 'លក់ចេញ', 'ថ្នំាចុងគ្រា', 'ផ្សេងៗ'];
        $sheet->fromArray($headers, null, 'A3');
        $sheet->getStyle('A3:H3')->getFont()->setBold(true);

        $row = 4;
        foreach ($medicines as $i => $medicine) {
            $medMovements = $movements->get($medicine->medicine_id, collect());
            $in = (int) optional($medMovements->firstWhere('movement_type', 'IN'))->total_qty;
            $out = (int) optional($medMovements->firstWhere('movement_type', 'OUT'))->total_qty;
            $closing = (int) $medicine->batches()->sum('remaining_quantity');
            $opening = $closing - $in + $out;
            $total = $opening + $in;

            $sheet->fromArray(
                [$i + 1, $medicine->medicine_name, $opening, $in, $total, $out, $closing, ''],
                null,
                "A{$row}"
            );
            $row++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'stock_report_' . $start->format('Y-m') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }


    public function expiringDetail()
    {
        $rows = MedicineBatch::expiringSoon(30)
            ->join('medicines', 'medicines.medicine_id', '=', 'medicine_batches.medicine_id')
            ->where('medicine_batches.remaining_quantity', '>', 0)
            ->select(
                'medicines.medicine_name',
                'medicine_batches.batch_number',
                'medicine_batches.remaining_quantity',
                'medicine_batches.expiry_date'
            )
            ->orderBy('medicine_batches.expiry_date', 'asc')
            ->get()
            ->map(function ($row) {
                return [
                    'medicine_name' => $row->medicine_name,
                    'batch_number' => $row->batch_number,
                    'remaining_quantity' => (int) $row->remaining_quantity,
                    'expiry_date' => \Carbon\Carbon::parse($row->expiry_date)->format('d-M-Y'),
                    'days_left' => (int) now()->diffInDays(\Carbon\Carbon::parse($row->expiry_date), false),
                ];
            });

        return response()->json(['data' => $rows]);
    }

}
