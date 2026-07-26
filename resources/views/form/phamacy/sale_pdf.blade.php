<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @font-face {
            font-family: 'Khmer OS';
            font-weight: normal;
            src: url({{ storage_path('fonts/KhmerOS.ttf') }}) format('truetype');
        }
        body { font-family: 'Khmer OS', sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h2 { margin: 0; font-size: 16px; }
        .header p { margin: 2px 0; font-size: 10px; }
        .info-row { display: flex; justify-content: space-between; margin: 12px 0; font-size: 12px; }
        .info-row span { display: inline-block; border-bottom: 1px dotted #000; min-width: 120px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 5px 6px; font-size: 11px; text-align: left; }
        th { background: #f0f0f0; text-align: center; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .sign-row { display: flex; justify-content: space-between; margin-top: 40px; font-size: 11px; }
        .sign-box { text-align: center; width: 45%; }
        .sign-box .line { border-top: 1px solid #000; margin-top: 40px; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>ឱសថស្ថាន PRUM SANTEPHEAP CLINIC</h2>
        <p>ទូរស័ព្ទ: 088 28 07 495 / 096 50 69 839 / 095 45 22 96</p>
    </div>

    <div class="info-row">
        <span>Patient ID: {{ $sale->patient->patient_id ?? '-' }}</span>
        <span>ថ្ងៃទី: {{ $sale->sale_date->format('d-M-Y') }}</span>
    </div>
    <div class="info-row">
        <span>Name: {{ $sale->patient->name ?? 'អតិថិជនចរណ៍' }}</span>
        <span>Age: {{ $sale->patient->age ?? '-' }}</span>
        <span>Sex: {{ $sale->patient->sex ?? '-' }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:30px">N°</th>
                <th>ថ្នាំ (Medicines / strength)</th>
                <th style="width:70px">ចំនួន</th>
                <th style="width:80px">តម្លៃ/គ្រាប់</th>
                <th style="width:80px">សរុប</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $i => $item)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $item->medicine->medicine_name }} {{ $item->medicine->strength }}{{ $item->medicine->dosage_unit }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">${{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
            @for($i = $sale->items->count(); $i < 12; $i++)
                <tr><td class="text-center">{{ $i + 1 }}</td><td></td><td></td><td></td><td></td></tr>
            @endfor
            <tr>
                <td colspan="4" class="text-right"><strong>សរុប</strong></td>
                <td class="text-right"><strong>${{ number_format($sale->total_amount, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="sign-row">
        <div class="sign-box">
            <div class="line">Attending Physician Signature</div>
        </div>
        <div class="sign-box">
            <div class="line">Pharmacist Signature ({{ $sale->employee->name ?? Auth::user()->name ?? '' }})</div>
        </div>
    </div>
</body>
</html>
