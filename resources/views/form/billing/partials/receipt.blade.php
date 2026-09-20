@php
    $setting = $setting ?? null;

    $clinicName  = data_get($setting, 'system_name') ?: config('app.name');
    $clinicPhone = data_get($setting, 'phone');
    $clinicEmail = data_get($setting, 'email');
    $clinicAddr  = data_get($setting, 'address');
    $clinicHours = data_get($setting, 'working_hours');
    $logo        = data_get($setting, 'logo');
    $logoUrl     = $logo ? (str_starts_with($logo, 'http') ? $logo : asset('storage/' . $logo)) : null;

    $typeLabels   = ['service' => 'សេវាកម្ម', 'room' => 'បន្ទប់សម្រាក', 'medicine' => 'ថ្នាំពេទ្យ', 'lab' => 'មន្ទីរពិសោធន៍'];
    $statusLabels = ['paid' => 'បានទូទាត់រួច', 'partial' => 'បង់ខ្លះ', 'unpaid' => 'មិនទាន់បង់', 'cancelled' => 'បានលុបចោល'];
    $methodLabels = ['cash' => 'សាច់ប្រាក់', 'card' => 'កាតធនាគារ', 'online' => 'Online / KHQR'];

    $isIpd  = !empty($invoice->admission_id);
    $room   = optional(optional($invoice->admission)->room)->room_number;
    $cashier = optional(optional($invoice->payments->last())->processor)->name
               ?? optional($invoice->creator)->name;
@endphp

<style>
    .invoice-sheet {
        --teal: #17756a;
        --teal-light: #e2f0ee;
        --line: #e5e7eb;
        background: #fff;
        color: #2d3748;
        font-family: 'Kantumruy Pro', 'Khmer OS Battambang', 'Noto Sans Khmer', sans-serif;
        font-size: 13px;
        line-height: 1.7;
        padding: 10px 14px;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .invoice-sheet * { box-sizing: border-box; }
    .invoice-sheet .inv-title { font-size: 34px; font-weight: 800; color: var(--teal); margin-bottom: 14px; }
    .invoice-sheet .inv-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-bottom: 18px; }
    .invoice-sheet .inv-clinic { display: flex; gap: 12px; align-items: flex-start; flex: 1; }
    .invoice-sheet .inv-logo { width: 64px; height: 64px; object-fit: contain; }
    .invoice-sheet .inv-clinic-name { font-size: 18px; font-weight: 800; color: var(--teal); }
    .invoice-sheet .inv-muted { color: #6b7280; font-size: 12px; }
    .invoice-sheet .dot { margin: 0 4px; }

    .invoice-sheet .inv-meta { border: 1px solid var(--teal); border-collapse: collapse; min-width: 250px; }
    .invoice-sheet .inv-meta td { padding: 4px 10px; border-bottom: 1px solid var(--line); font-size: 12px; }
    .invoice-sheet .inv-meta tr:last-child td { border-bottom: 0; }
    .invoice-sheet .inv-meta td:first-child { background: var(--teal-light); color: #4b5563; }
    .invoice-sheet .inv-meta td:last-child { font-weight: 700; text-align: right; }

    .invoice-sheet .inv-patient { background: var(--teal-light); padding: 12px 16px; margin-bottom: 18px; }
    .invoice-sheet .inv-section-title { font-weight: 800; color: var(--teal); margin-bottom: 4px; }
    .invoice-sheet .inv-patient-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 20px; }

    .invoice-sheet .inv-items { width: 100%; border-collapse: collapse; }
    .invoice-sheet .inv-items thead th { background: var(--teal); color: #fff; padding: 8px 10px; font-weight: 700; }
    .invoice-sheet .inv-items td { padding: 8px 10px; border-bottom: 1px solid var(--line); }
    .invoice-sheet .inv-items tr.empty-row td { height: 34px; }
    .invoice-sheet .text-c { text-align: center; }
    .invoice-sheet .text-r { text-align: right; }

    .invoice-sheet .inv-bottom { display: flex; justify-content: space-between; gap: 24px; margin-top: 18px; border-top: 1px solid var(--teal); padding-top: 16px; }
    .invoice-sheet .inv-extra { flex: 1; }
    .invoice-sheet .inv-extra .pay-line { font-size: 12px; color: #4b5563; }
    .invoice-sheet .inv-note { font-style: italic; color: #4b5563; margin-top: 8px; font-size: 12px; }

    .invoice-sheet .inv-totals { width: 270px; border: 1px solid var(--teal); border-collapse: collapse; align-self: flex-start; }
    .invoice-sheet .inv-totals td { padding: 6px 12px; }
    .invoice-sheet .inv-totals td:last-child { text-align: right; font-weight: 700; }
    .invoice-sheet .inv-totals tr.grand td { background: var(--teal); color: #fff; font-size: 15px; font-weight: 800; }
    .invoice-sheet .inv-totals tr.paid td:last-child { color: #15803d; }
    .invoice-sheet .inv-totals tr.due td:last-child { color: #be123c; }

    .invoice-sheet .inv-sign { display: flex; justify-content: space-around; margin-top: 46px; text-align: center; }
    .invoice-sheet .inv-sign .box { width: 220px; }
    .invoice-sheet .inv-sign .sign-line { border-top: 1px dotted #6b7280; padding-top: 6px; }
    .invoice-sheet .inv-sign .sign-role { font-weight: 800; color: var(--teal); }

    .invoice-sheet .inv-footer { text-align: center; color: var(--teal); font-weight: 700; margin-top: 26px; border-top: 1px solid var(--line); padding-top: 8px; }
    .invoice-sheet .inv-cancelled { border: 1px solid #be123c; color: #be123c; background: #fff1f2; padding: 8px 12px; margin-bottom: 14px; }
</style>

<div class="invoice-sheet">

    <div class="inv-title">វិក្កយបត្រ</div>

    @if($invoice->status === 'cancelled')
        <div class="inv-cancelled">
            <b>បានលុបចោល</b> — មូលហេតុ: {{ $invoice->cancel_reason }}
        </div>
    @endif

    {{-- Clinic + invoice meta --}}
    <div class="inv-top">
        <div class="inv-clinic">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" class="inv-logo" alt="Logo">
            @endif
            <div>
                <div class="inv-clinic-name">{{ $clinicName }}</div>
                @if($clinicHours)<div class="inv-muted">{{ $clinicHours }}</div>@endif
                @if($clinicAddr)<div class="inv-muted">{{ $clinicAddr }}</div>@endif
                @if($clinicPhone || $clinicEmail)
                    <div class="inv-muted">
                        @if($clinicPhone)ទូរស័ព្ទ {{ $clinicPhone }}@endif
                        @if($clinicPhone && $clinicEmail)<span class="dot">•</span>@endif
                        @if($clinicEmail){{ $clinicEmail }}@endif
                    </div>
                @endif
            </div>
        </div>

        <table class="inv-meta">
            <tr><td>លេខវិក្កយបត្រ</td><td>{{ $invoice->invoice_number }}</td></tr>
            <tr><td>កាលបរិច្ឆេទ</td><td>{{ $invoice->created_at->format('d/m/Y h:i A') }}</td></tr>
            <tr><td>ស្ថានភាព</td><td>{{ $statusLabels[$invoice->status] ?? $invoice->status }}</td></tr>
        </table>
    </div>

    {{-- Patient info --}}
    <div class="inv-patient">
        <div class="inv-section-title">ព័ត៌មានអ្នកជំងឺ</div>
        <div class="inv-patient-grid">
            <div><b>ឈ្មោះ ៖</b> {{ $invoice->patient_name }}</div>
            <div><b>លេខកូដអ្នកជំងឺ ៖</b> {{ optional($invoice->patient)->patient_code ?? '—' }}</div>
            <div><b>ទូរស័ព្ទ ៖</b> {{ $invoice->patient_phone ?: '—' }}</div>
            <div>
                <b>ប្រភេទ ៖</b>
                {{ $isIpd ? 'អ្នកជំងឺសម្រាក (IPD)' : 'អ្នកជំងឺក្រៅ (OPD)' }}
                @if($isIpd && $room) — បន្ទប់ {{ $room }} @endif
            </div>
        </div>
    </div>

    {{-- Items --}}
    <table class="inv-items">
        <thead>
            <tr>
                <th style="width:50px" class="text-c">ល.រ</th>
                <th>សេវាពិនិត្យ / ថ្នាំព្យាបាល</th>
                <th style="width:120px" class="text-c">ប្រភេទ</th>
                <th style="width:70px" class="text-c">ចំនួន</th>
                <th style="width:110px" class="text-r">តម្លៃឯកតា</th>
                <th style="width:110px" class="text-r">ជាប្រាក់</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $i => $item)
                <tr>
                    <td class="text-c">{{ $i + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="text-c">{{ $typeLabels[$item->item_type] ?? $item->item_type }}</td>
                    <td class="text-c">{{ $item->qty }}</td>
                    <td class="text-r">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-r"><b>${{ number_format($item->subtotal, 2) }}</b></td>
                </tr>
            @endforeach
            @for($k = $invoice->items->count(); $k < 5; $k++)
                <tr class="empty-row"><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td></tr>
            @endfor
        </tbody>
    </table>

    {{-- Extra info + totals --}}
    <div class="inv-bottom">
        <div class="inv-extra">
            <div class="inv-section-title">ព័ត៌មានបន្ថែម</div>
            @forelse($invoice->payments as $p)
                <div class="pay-line">
                    ✓ {{ $methodLabels[$p->payment_method] ?? $p->payment_method }}
                    ${{ number_format($p->amount, 2) }}
                    ({{ \Carbon\Carbon::parse($p->paid_at)->format('d/m/Y H:i') }})
                    @if($p->transaction_ref) — {{ $p->transaction_ref }} @endif
                </div>
            @empty
                <div class="pay-line">មិនទាន់មានការទូទាត់</div>
            @endforelse

            @if($invoice->notes)
                <div class="inv-note">កំណត់សម្គាល់ ៖ {{ $invoice->notes }}</div>
            @endif
        </div>

        <table class="inv-totals">
            <tr class="grand"><td>សរុបរួម</td><td>${{ number_format($invoice->total_amount, 2) }}</td></tr>
            <tr class="paid"><td>បានបង់</td><td>${{ number_format($invoice->paid_amount, 2) }}</td></tr>
            <tr class="due"><td>ប្រាក់ជំពាក់</td><td>${{ number_format($invoice->balance, 2) }}</td></tr>
        </table>
    </div>

    {{-- Signatures --}}
    <div class="inv-sign">
        <div class="box">
            <div class="sign-line">
                <div class="sign-role">អ្នកជំងឺ / អាណាព្យាបាល</div>
                <div class="inv-muted">(ឈ្មោះ និង ហត្ថលេខា)</div>
            </div>
        </div>
        <div class="box">
            <div class="sign-line">
                <div class="sign-role">អ្នកទទួលប្រាក់</div>
                <div class="inv-muted">{{ $cashier ?: '(ឈ្មោះ និង ហត្ថលេខា)' }}</div>
            </div>
        </div>
    </div>

    <div class="inv-footer">សូមអរគុណ · សូមរក្សាសុខភាពឲ្យបានល្អ</div>
</div>
