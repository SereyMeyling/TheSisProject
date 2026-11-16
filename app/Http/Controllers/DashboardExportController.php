<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Pharmacy\MedicineBatch;
use App\Models\Pharmacy\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * One Excel workbook for the admin dashboard.
 *   ?month=2026-11&scope=month  -> one sheet for that month
 *   ?month=2026-11&scope=year   -> 12 sheets (Jan..Dec) of that year
 *
 * Each month sheet stacks the clinic's three Excel sheets:
 *   1) Income statement (per patient per day)
 *   2) Daily expending
 *   3) Patients by diagnosis and sex (per day)
 * Amounts are in the system currency (USD).
 */
class DashboardExportController extends Controller
{
    private const GROUP_COLORS = [
        'DMII' => 'E6E600',
        'HTN' => 'A8B0C0',
        'RA' => 'C9967B',
        'HYPERTHYROID' => '00B050',
        'GAD' => '2E9BDA',
        'LIVER' => 'E0A000',
        'CARDIOLOGY' => 'B5651D',
        'DYSPEPSIA' => 'D2B55B',
        'DENGUE' => 'BFBFBF',
        'OTHER' => '9BBB8A',
    ];

    public function export(Request $request)
    {
        abort_unless(Auth::user() && Auth::user()->hasRole('admin'), 403);

        $month = $this->resolveMonth($request->query('month'));
        $scope = $request->query('scope') === 'year' ? 'year' : 'month';

        $months = [];
        if ($scope === 'year') {
            for ($m = 1; $m <= 12; $m++) {
                $months[] = Carbon::create($month->year, $m, 1)->startOfMonth();
            }
        } else {
            $months[] = $month->copy();
        }

        $spreadsheet = new Spreadsheet();
        foreach ($months as $i => $m) {
            $sheet = $i === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $sheet->setTitle($m->format('M-Y'));
            $this->buildMonthSheet($sheet, $m);
        }
        $spreadsheet->setActiveSheetIndex(0);

        $filename = $scope === 'year'
            ? 'PrumSantepheap_' . $month->year . '_12-months.xlsx'
            : 'PrumSantepheap_' . $month->format('Y-m') . '.xlsx';

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ------------------------------------------------------------------
    // One month = one sheet
    // ------------------------------------------------------------------
    private function buildMonthSheet(Worksheet $sheet, Carbon $m): void
    {
        $start = $m->copy()->startOfMonth();
        $end = $m->copy()->endOfMonth();
        $label = strtoupper($m->format('F Y'));

        $incomeRows = $this->incomeRows($start, $end);
        $expenseRows = $this->expenseRows($start, $end);

        $paidTotal = array_sum(array_column($incomeRows, 'paid'));
        $pendingTotal = array_sum(array_column($incomeRows, 'pending'));
        $salesTotal = (float) Sale::whereBetween('created_at', [$start, $end])->sum('total_amount');
        $expenseTotal = array_sum(array_column($expenseRows, 'amount'));

        // ---- summary ----
        $sheet->setCellValue('A1', 'PRUM SANTEPHEAP CLINIC - ' . $label);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $summary = [
            ['Invoice payments (USD)', $paidTotal],
            ['Pharmacy sales (USD)', $salesTotal],
            ['TOTAL INCOME', $paidTotal + $salesTotal],
            ['Total expenses', $expenseTotal],
            ['NET (income - expenses)', $paidTotal + $salesTotal - $expenseTotal],
            ['Pending invoices (unpaid)', $pendingTotal],
        ];
        $r = 3;
        foreach ($summary as [$name, $value]) {
            $sheet->setCellValue("B{$r}", $name);
            $sheet->setCellValue("C{$r}", round($value, 2));
            $r++;
        }
        $sheet->getStyle('C3:C8')->getNumberFormat()->setFormatCode('#,##0.00');
        $this->box($sheet, 'B3:C8');
        $this->bold($sheet, 'B5:C5');
        $this->bold($sheet, 'B7:C7');
        $this->fill($sheet, 'B5:C5', 'DDEBF7');
        $this->fill($sheet, 'B7:C7', 'FFE699');

        // ---- the three sections ----
        $row = 10;
        $row = $this->incomeSection($sheet, $row, $label, $incomeRows) + 2;
        $row = $this->expenseSection($sheet, $row, $label, $expenseRows) + 2;
        $this->diseaseSection($sheet, $row, $start, $end, $label);

        // ---- widths ----
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(28);
        foreach (range('C', 'J') as $c) {
            $sheet->getColumnDimension($c)->setWidth(13);
        }
        for ($i = 11; $i <= 22; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(8);
        }
    }

    // ------------------------------------------------------------------
    // 1) Income statement
    // ------------------------------------------------------------------
    private function incomeRows(Carbon $start, Carbon $end): array
    {
        $rows = [];

        // visits (medical records)
        $visits = MedicalRecord::whereBetween('visit_date', [$start, $end])->orderBy('visit_date')->get();
        foreach ($visits as $v) {
            $date = $v->visit_date->toDateString();
            $key = $date . '|' . (int) $v->patient_id;
            if (isset($rows[$key])) {
                continue;
            }
            $rows[$key] = $this->blankRow($date, (int) $v->patient_id);
            $rows[$key]['dia'] = MedicalRecord::DIAGNOSIS_GROUPS[$v->diagnosis_group] ?? ($v->diagnosis ?: '');
            $rows[$key]['visit'] = MedicalRecord::VISIT_TYPES[$v->visit_type] ?? '';
        }

        // payments received that day
        $payments = InvoicePayment::join('invoices', 'invoices.id', '=', 'invoice_payments.invoice_id')
            ->whereBetween('invoice_payments.paid_at', [$start, $end])
            ->selectRaw('DATE(invoice_payments.paid_at) as d, invoices.patient_id, SUM(invoice_payments.amount) as total, GROUP_CONCAT(DISTINCT invoice_payments.payment_method) as methods')
            ->groupBy('d', 'invoices.patient_id')
            ->get();
        foreach ($payments as $p) {
            $key = $p->d . '|' . (int) $p->patient_id;
            $rows[$key] ??= $this->blankRow($p->d, (int) $p->patient_id);
            $rows[$key]['paid'] += (float) $p->total;
            $rows[$key]['methods'] = (string) $p->methods;
        }

        // invoices created that day and still unpaid / partial = "Pending"
        $pending = Invoice::whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['unpaid', 'partial'])
            ->selectRaw('DATE(created_at) as d, patient_id, SUM(balance) as bal')
            ->groupBy('d', 'patient_id')
            ->get();
        foreach ($pending as $p) {
            $key = $p->d . '|' . (int) $p->patient_id;
            $rows[$key] ??= $this->blankRow($p->d, (int) $p->patient_id);
            $rows[$key]['pending'] += (float) $p->bal;
        }

        // patient code + sex
        $ids = collect($rows)->pluck('pid')->filter()->unique()->all();
        $patients = Patient::whereIn('patient_id', $ids)->get()->keyBy('patient_id');
        foreach ($rows as &$r) {
            $pt = $patients[$r['pid']] ?? null;
            $r['code'] = $pt->patient_code ?? '-';
            $r['sex'] = $pt ? ($pt->sex === 'male' ? 'M' : ($pt->sex === 'female' ? 'F' : '')) : '';
        }
        unset($r);

        uasort($rows, fn($a, $b) => strcmp($a['date'], $b['date']));

        return array_values($rows);
    }

    private function blankRow(string $date, int $pid): array
    {
        return [
            'date' => $date, 'pid' => $pid, 'code' => '-', 'sex' => '',
            'dia' => '', 'visit' => '', 'paid' => 0.0, 'methods' => '', 'pending' => 0.0,
        ];
    }

    private function incomeSection(Worksheet $sheet, int $row, string $label, array $rows): int
    {
        $sheet->setCellValue("A{$row}", "Income statement for {$label}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
        $row++;

        $top = $row;
        $this->headerRow($sheet, $row, ['DATE', 'PT CODE', 'PAID (USD)', 'PAYMENT', 'PENDING (USD)', 'DIA', 'GENDER', 'PT VISIT', 'No', 'NOTE'], 'DDEBF7');
        $row++;

        $byDate = [];
        foreach ($rows as $r) {
            $byDate[$r['date']][] = $r;
        }
        ksort($byDate);

        $monthPaid = $monthPending = 0.0;
        foreach ($byDate as $date => $items) {
            $n = 0;
            $dayPaid = $dayPending = 0.0;
            foreach ($items as $r) {
                $n++;
                $payment = $r['paid'] > 0 ? strtoupper(str_replace(',', '/', $r['methods'])) : ($r['pending'] > 0 ? 'Pending' : '');
                $sheet->setCellValue("A{$row}", $n === 1 ? Carbon::parse($date)->format('d.m.y') : '');
                $sheet->setCellValue("B{$row}", $r['code']);
                $sheet->setCellValue("C{$row}", $r['paid'] > 0 ? round($r['paid'], 2) : '');
                $sheet->setCellValue("D{$row}", $payment);
                $sheet->setCellValue("E{$row}", $r['pending'] > 0 ? round($r['pending'], 2) : '');
                $sheet->setCellValue("F{$row}", $r['dia']);
                $sheet->setCellValue("G{$row}", $r['sex']);
                $sheet->setCellValue("H{$row}", $r['visit']);
                $sheet->setCellValue("I{$row}", $n);
                $sheet->setCellValue("J{$row}", '');
                $dayPaid += $r['paid'];
                $dayPending += $r['pending'];
                $row++;
            }
            // daily total (blue row, like the clinic's sheet)
            $sheet->setCellValue("B{$row}", 'DAILY TOTAL');
            $sheet->setCellValue("C{$row}", round($dayPaid, 2));
            $sheet->setCellValue("E{$row}", round($dayPending, 2));
            $this->fill($sheet, "A{$row}:J{$row}", '9DC3E6');
            $this->bold($sheet, "A{$row}:J{$row}");
            $monthPaid += $dayPaid;
            $monthPending += $dayPending;
            $row++;
        }

        // month total (orange)
        $sheet->setCellValue("B{$row}", 'MONTH TOTAL');
        $sheet->setCellValue("C{$row}", round($monthPaid, 2));
        $sheet->setCellValue("E{$row}", round($monthPending, 2));
        $this->fill($sheet, "A{$row}:J{$row}", 'FFC000');
        $this->bold($sheet, "A{$row}:J{$row}");

        $sheet->getStyle("C" . ($top + 1) . ":C{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("E" . ($top + 1) . ":E{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $this->box($sheet, "A{$top}:J{$row}");
        $sheet->getStyle("G{$top}:I{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return $row + 1;
    }

    // ------------------------------------------------------------------
    // 2) Daily expending
    // ------------------------------------------------------------------
    private function expenseRows(Carbon $start, Carbon $end): array
    {
        $rows = [];

        // medicine purchases come from stock batches
        $batches = MedicineBatch::with('medicine')->whereBetween('created_at', [$start, $end])->orderBy('created_at')->get();
        foreach ($batches as $b) {
            $qty = (int) $b->quantity_initial;
            $rows[] = [
                'date' => $b->created_at->toDateString(),
                'desc' => $b->medicine->medicine_name ?? 'Medicine',
                'qty' => $qty,
                'amount' => (float) $b->purchase_price * $qty,
                'cat' => 'Medicine',
                'pay' => '',
            ];
        }

        // other expenses (doctor fee, Echo, supplies ...)
        $others = Expense::whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('expense_date')->get();
        foreach ($others as $e) {
            $rows[] = [
                'date' => $e->expense_date->toDateString(),
                'desc' => $e->description,
                'qty' => (int) $e->quantity,
                'amount' => (float) $e->amount,
                'cat' => Expense::CATEGORIES[$e->category] ?? $e->category,
                'pay' => strtoupper($e->payment_method),
            ];
        }

        usort($rows, fn($a, $b) => strcmp($a['date'], $b['date']));

        return $rows;
    }

    private function expenseSection(Worksheet $sheet, int $row, string $label, array $rows): int
    {
        $sheet->setCellValue("A{$row}", "Daily Expending for {$label}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
        $row++;

        $top = $row;
        $this->headerRow($sheet, $row, ['DATE', 'DESCRIPTION', 'QUANTITY', 'AMOUNT (USD)', 'CATEGORY', 'PAYMENT'], 'FFFF00');
        $row++;

        $byDate = [];
        foreach ($rows as $r) {
            $byDate[$r['date']][] = $r;
        }
        ksort($byDate);

        $monthTotal = 0.0;
        foreach ($byDate as $date => $items) {
            $first = true;
            $dayTotal = 0.0;
            foreach ($items as $r) {
                $sheet->setCellValue("A{$row}", $first ? Carbon::parse($date)->format('d.m.y') : '');
                $sheet->setCellValue("B{$row}", $r['desc']);
                $sheet->setCellValue("C{$row}", $r['qty']);
                $sheet->setCellValue("D{$row}", round($r['amount'], 2));
                $sheet->setCellValue("E{$row}", $r['cat']);
                $sheet->setCellValue("F{$row}", $r['pay']);
                $dayTotal += $r['amount'];
                $first = false;
                $row++;
            }
            $sheet->setCellValue("B{$row}", 'DAILY TOTAL');
            $sheet->setCellValue("D{$row}", round($dayTotal, 2));
            $this->fill($sheet, "A{$row}:F{$row}", 'FFE699');
            $this->bold($sheet, "A{$row}:F{$row}");
            $monthTotal += $dayTotal;
            $row++;
        }

        $sheet->setCellValue("B{$row}", 'MONTH TOTAL');
        $sheet->setCellValue("D{$row}", round($monthTotal, 2));
        $this->fill($sheet, "A{$row}:F{$row}", 'FFC000');
        $this->bold($sheet, "A{$row}:F{$row}");

        $sheet->getStyle("D" . ($top + 1) . ":D{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $this->box($sheet, "A{$top}:F{$row}");

        return $row + 1;
    }

    // ------------------------------------------------------------------
    // 3) Patients by diagnosis and sex (the monthly sheet with M / F columns)
    // ------------------------------------------------------------------
    private function diseaseSection(Worksheet $sheet, int $row, Carbon $start, Carbon $end, string $label): int
    {
        $groups = MedicalRecord::DIAGNOSIS_GROUPS;
        $keys = array_keys($groups);

        $sheet->setCellValue("A{$row}", "Patients by diagnosis and sex for {$label}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
        $row++;

        $data = MedicalRecord::join('patients', 'patients.patient_id', '=', 'medical_records.patient_id')
            ->whereBetween('medical_records.visit_date', [$start, $end])
            ->selectRaw("DATE(medical_records.visit_date) as d, COALESCE(NULLIF(medical_records.diagnosis_group, ''), 'OTHER') as grp, patients.sex, COUNT(*) as total")
            ->groupBy('d', 'grp', 'patients.sex')
            ->get();

        $matrix = [];
        foreach ($data as $r) {
            $g = isset($groups[$r->grp]) ? $r->grp : 'OTHER';
            $s = $r->sex === 'male' ? 'M' : ($r->sex === 'female' ? 'F' : null);
            if ($s === null) {
                continue;
            }
            $matrix[$r->d][$g][$s] = ($matrix[$r->d][$g][$s] ?? 0) + (int) $r->total;
        }

        // header (2 rows)
        $h1 = $row;
        $h2 = $row + 1;
        $sheet->setCellValue("A{$h1}", 'DATE');
        $sheet->mergeCells("A{$h1}:A{$h2}");
        $sheet->setCellValue("B{$h1}", 'DAY');
        $sheet->mergeCells("B{$h1}:B{$h2}");
        foreach ($keys as $i => $k) {
            $c1 = 3 + $i * 2;
            $c2 = $c1 + 1;
            $a = $this->cell($c1, $h1);
            $b = $this->cell($c2, $h1);
            $color = self::GROUP_COLORS[$k] ?? 'D9D9D9';
            $sheet->setCellValue($a, $groups[$k]);
            $sheet->mergeCells("{$a}:{$b}");
            $sheet->setCellValue($this->cell($c1, $h2), 'M');
            $sheet->setCellValue($this->cell($c2, $h2), 'F');
            $this->fill($sheet, $a . ':' . $this->cell($c2, $h2), $color);
        }
        $lastCol = 2 + count($keys) * 2;
        $this->bold($sheet, "A{$h1}:" . $this->cell($lastCol, $h2));
        $row = $h2 + 1;

        // one row per day
        $totals = array_fill(0, count($keys) * 2, 0);
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $date = $d->toDateString();
            $sheet->setCellValue("A{$row}", $d->format('d-m-y'));
            $sheet->setCellValue("B{$row}", $d->format('D'));
            if (isset($matrix[$date])) {
                foreach ($keys as $i => $k) {
                    $mVal = $matrix[$date][$k]['M'] ?? 0;
                    $fVal = $matrix[$date][$k]['F'] ?? 0;
                    $sheet->setCellValue($this->cell(3 + $i * 2, $row), $mVal);
                    $sheet->setCellValue($this->cell(4 + $i * 2, $row), $fVal);
                    $totals[$i * 2] += $mVal;
                    $totals[$i * 2 + 1] += $fVal;
                }
            } else {
                $this->fill($sheet, "C{$row}:" . $this->cell($lastCol, $row), 'EDEDED');
            }
            $row++;
        }

        // total row (red numbers, like the clinic's sheet)
        $sheet->setCellValue("A{$row}", 'TOTAL');
        $sheet->mergeCells("A{$row}:B{$row}");
        foreach ($totals as $i => $t) {
            $sheet->setCellValue($this->cell(3 + $i, $row), $t);
        }
        $range = "A{$row}:" . $this->cell($lastCol, $row);
        $this->bold($sheet, $range);
        $sheet->getStyle($range)->getFont()->getColor()->setRGB('C00000');

        $full = "A{$h1}:" . $this->cell($lastCol, $row);
        $this->box($sheet, $full);
        $sheet->getStyle($full)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        return $row + 1;
    }

    // ------------------------------------------------------------------
    // helpers
    // ------------------------------------------------------------------
    private function resolveMonth(?string $value): Carbon
    {
        try {
            return Carbon::createFromFormat('!Y-m', $value ?: now()->format('Y-m'))->startOfMonth();
        } catch (\Throwable $e) {
            return now()->startOfMonth();
        }
    }

    private function cell(int $col, int $row): string
    {
        return Coordinate::stringFromColumnIndex($col) . $row;
    }

    private function headerRow(Worksheet $sheet, int $row, array $headers, string $rgb): void
    {
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($this->cell($i + 1, $row), $h);
        }
        $range = 'A' . $row . ':' . $this->cell(count($headers), $row);
        $this->fill($sheet, $range, $rgb);
        $this->bold($sheet, $range);
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function fill(Worksheet $sheet, string $range, string $rgb): void
    {
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rgb);
    }

    private function bold(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true);
    }

    private function box(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }
}
