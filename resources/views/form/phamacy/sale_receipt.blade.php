{{-- resources/views/form/phamacy/sale_receipt.blade.php --}}
<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>វិក្កយបត្រ - {{ $billing->invoice_prefix }}{{ str_pad($sale->sale_id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        @php
            $isReceipt = $billing->print_size === '80mm';
            $subtotal = (float) $sale->total_amount;
            $taxPct = (float) ($billing->tax_percent ?? 0);
            $taxAmount = $subtotal * ($taxPct / 100);
            $grandTotal = $subtotal + $taxAmount;
            $cur = $billing->currency_symbol ?: '$';
        @endphp

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Khmer OS Battambang', Arial, sans-serif;
            font-size: {{ $isReceipt ? '12px' : '14px' }};
            color: #1a1a1a;
            margin: 0;
            padding: {{ $isReceipt ? '10px' : '30px' }};
            background: #f4f4f4;
        }

        .box {
            max-width: {{ $isReceipt ? '300px' : '750px' }};
            margin: 0 auto;
            background: #fff;
            padding: {{ $isReceipt ? '16px' : '36px' }};
            {{ $isReceipt ? '' : 'border-radius: 10px; box-shadow: 0 2px 12px rgba(0,0,0,0.08);' }}
        }

        /* ===== Header ===== */
        .header {
            text-align: center;
            padding-bottom: {{ $isReceipt ? '10px' : '18px' }};
            border-bottom: {{ $isReceipt ? '1px dashed #999' : '2px solid #198754' }};
            margin-bottom: {{ $isReceipt ? '10px' : '18px' }};
        }

        .header h4 {
            margin: 0 0 4px;
            font-size: {{ $isReceipt ? '14px' : '22px' }};
            font-weight: 700;
            color: #198754;
            letter-spacing: 0.3px;
        }

        .header p {
            margin: 2px 0;
            font-size: {{ $isReceipt ? '10px' : '12px' }};
            color: #666;
        }

        .invoice-label {
            display: inline-block;
            margin-top: 6px;
            font-size: {{ $isReceipt ? '11px' : '13px' }};
            font-weight: 700;
            letter-spacing: 1px;
            color: #999;
            text-transform: uppercase;
        }

        /* ===== Meta info ===== */
        .meta-grid {
            display: flex;
            justify-content: space-between;
            font-size: {{ $isReceipt ? '10px' : '12.5px' }};
            margin-bottom: 4px;
            color: #333;
        }

        .meta-grid .label {
            color: #888;
            margin-right: 4px;
        }

        .meta-block {
            margin-bottom: {{ $isReceipt ? '10px' : '16px' }};
        }

        /* ===== Table ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: {{ $isReceipt ? '10px 0' : '16px 0' }};
        }

        thead th {
            font-size: {{ $isReceipt ? '10px' : '12px' }};
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: {{ $isReceipt ? '#000' : '#fff' }};
            background: {{ $isReceipt ? 'transparent' : '#198754' }};
            text-align: left;
            padding: {{ $isReceipt ? '4px 2px' : '10px 12px' }};
            border-bottom: {{ $isReceipt ? '1px dashed #000' : 'none' }};
        }

        thead th:first-child { border-radius: {{ $isReceipt ? '0' : '6px 0 0 6px' }}; }
        thead th:last-child  { border-radius: {{ $isReceipt ? '0' : '0 6px 6px 0' }}; }

        tbody td {
            padding: {{ $isReceipt ? '5px 2px' : '10px 12px' }};
            font-size: {{ $isReceipt ? '10.5px' : '13px' }};
            border-bottom: 1px solid {{ $isReceipt ? '#eee' : '#f0f0f0' }};
        }

        tbody tr:last-child td {
            border-bottom: {{ $isReceipt ? '1px dashed #000' : 'none' }};
        }

        .num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        /* ===== Totals ===== */
        .totals {
            margin-top: {{ $isReceipt ? '8px' : '14px' }};
            font-size: {{ $isReceipt ? '11px' : '13.5px' }};
        }

        .totals-inner {
            {{ $isReceipt ? '' : 'width: 260px; margin-left: auto;' }}
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: {{ $isReceipt ? '2px 0' : '5px 0' }};
            color: #444;
        }

        .totals-row.grand {
            font-weight: 700;
            font-size: {{ $isReceipt ? '13px' : '17px' }};
            color: #198754;
            border-top: {{ $isReceipt ? '1px solid #000' : '2px solid #198754' }};
            margin-top: 6px;
            padding-top: {{ $isReceipt ? '6px' : '10px' }};
        }

        /* ===== Footer ===== */
        .footer {
            text-align: center;
            margin-top: {{ $isReceipt ? '14px' : '26px' }};
            padding-top: {{ $isReceipt ? '10px' : '16px' }};
            border-top: {{ $isReceipt ? '1px dashed #999' : '1px solid #eee' }};
            font-size: {{ $isReceipt ? '9.5px' : '12px' }};
            color: #888;
            line-height: 1.6;
        }

        .thanks {
            font-weight: 700;
            color: #198754;
            display: block;
            margin-bottom: 4px;
            font-size: {{ $isReceipt ? '11px' : '13px' }};
        }

        /* ===== Print button ===== */
        .no-print {
            text-align: center;
            margin-bottom: 16px;
        }

        .print-btn {
            padding: 8px 20px;
            background: #198754;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(25,135,84,0.3);
        }

        .print-btn:hover {
            background: #157347;
        }

        @media print {
            body {
                background: #fff;
                padding: {{ $isReceipt ? '0' : '0' }};
            }
            .box {
                box-shadow: none;
                border-radius: 0;
                max-width: 100%;
            }
            @page {
                size: {{ $isReceipt ? '80mm auto' : 'A4' }};
                margin: {{ $isReceipt ? '0' : '12mm' }};
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print">
        <button onclick="window.print()" class="print-btn">
            🖨️ បោះពុម្ព
        </button>
    </div>

    <div class="box">

       <div class="header">
            <h4>{{ $general->system_name ?? config('app.name', 'Clinic') }}</h4>
            @if(!empty($general->address))
                <p>{{ $general->address }}</p>
            @endif
            @if(!empty($general->phone))
                <p>Tel: {{ $general->phone }}</p>
            @endif
            <span class="invoice-label">វិក្កយបត្រ</span>
        </div>

        <div class="meta-block">
            <div class="meta-grid">
                <span><span class="label">លេខ:</span>{{ $billing->invoice_prefix }}{{ str_pad($sale->sale_id, 6, '0', STR_PAD_LEFT) }}</span>
                <span>{{ $sale->sale_date->format('d-M-Y h:i A') }}</span>
            </div>
            <div class="meta-grid">
                <span><span class="label">អតិថិជន:</span>{{ $sale->patient->full_name ?? 'អតិថិជនចរណ៍' }}</span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ថ្នាំ</th>
                    <th class="num">ចំនួន</th>
                    <th class="num">តម្លៃ</th>
                    <th class="num">សរុប</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                    <tr>
                        <td>{{ $item->medicine->medicine_name ?? '-' }}</td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">{{ $cur }}{{ number_format($item->unit_price, 2) }}</td>
                        <td class="num">{{ $cur }}{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-inner">
                <div class="totals-row">
                    <span>សរុបរង</span>
                    <span>{{ $cur }}{{ number_format($subtotal, 2) }}</span>
                </div>
                @if($taxPct > 0)
                    <div class="totals-row">
                        <span>ពន្ធ ({{ $taxPct }}%)</span>
                        <span>{{ $cur }}{{ number_format($taxAmount, 2) }}</span>
                    </div>
                @endif
                <div class="totals-row grand">
                    <span>សរុបចុងក្រោយ</span>
                    <span>{{ $cur }}{{ number_format($grandTotal, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="footer">

            @if(!empty($billing->invoice_footer))
                {{ $billing->invoice_footer }}
            @endif
        </div>

    </div>

</body>
</html>
