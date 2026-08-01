@extends('adminlte::page')

@section('title', 'Billing Settings')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 mt-3">
    <h2 class="page-title mb-0">
        <i class="fas fa-file-invoice-dollar"></i> ការកំណត់វិក្កយបត្រ
    </h2>
    <button class="btn btn-success px-4" id="btnSaveSettings">
        <i class="fas fa-save mr-2"></i>
        រក្សាទុក
    </button>
</div>

<div class="alert alert-danger d-none" id="settingsErrors"></div>

<div class="row">
    {{-- ================= FORM ================= --}}
    <div class="col-lg-6">
        <div class="card setting-card">
            <div class="card-body">
                <form id="settingsForm">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>រូបិយវត្ថុ</label>
                                <input type="text" name="currency_symbol" id="f_currency" class="form-control"
                                    placeholder="$ / ៛" value="{{ $settings->currency_symbol }}">
                            </div>
                            <div class="form-group">
                                <label>ពន្ធ %</label>
                                <input type="number" step="0.01" name="tax_percent" id="f_tax" class="form-control"
                                    placeholder="0" value="{{ $settings->tax_percent }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>វិក័យប័ត្រ Prefix</label>
                                <input type="text" name="invoice_prefix" id="f_prefix" class="form-control"
                                    placeholder="INV-" value="{{ $settings->invoice_prefix }}">
                            </div>
                            <div class="form-group">
                                <label>វិក័យប័ត្រ Footer</label>
                                <input type="text" name="invoice_footer" id="f_footer" class="form-control"
                                    placeholder="សូមអរគុណ!" value="{{ $settings->invoice_footer }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>លេខវិក័យប័ត្របន្ទាប់ (Auto)</label>
                                <input type="number" min="1" name="next_invoice_number" id="f_number"
                                    class="form-control" value="{{ $settings->next_invoice_number }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>ព្រីនទំហំ</label>
                                <select name="print_size" id="f_size" class="form-control">
                                    <option value="A4" {{ $settings->print_size == 'A4' ? 'selected' : '' }}>A4</option>
                                    <option value="80mm" {{ $settings->print_size == '80mm' ? 'selected' : '' }}>80mm
                                        (receipt)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="invoice_auto_number" id="f_auto"
                                    {{ $settings->invoice_auto_number ? 'checked' : '' }}>
                                <label class="form-check-label" for="f_auto">បង្កើតលេខវិក័យប័ត្រស្វ័យប្រវត្តិ</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ================= LIVE PREVIEW ================= --}}
    <div class="col-lg-6">
        <div class="card setting-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-eye"></i> មើលជាមុន (Live Preview)</h6>
                <span class="badge badge-secondary" id="previewSizeBadge">A4</span>
            </div>
            <div class="card-body preview-wrapper">
                <div id="invoicePreview" class="invoice-preview size-A4">
                    <div class="p-header text-center">
                        <h5>{{ config('app.name', 'Clinic') }}</h5>
                        <p class="mb-0 small text-muted">(ឈ្មោះ/ទូរស័ព្ទ មកពី ការកំណត់ទូទៅ)</p>
                    </div>

                    <div class="p-info d-flex justify-content-between">
                        <span>វិក្កយបត្រលេខ: <strong id="p_invoice_no">INV-000001</strong></span>
                        <span>ថ្ងៃទី: {{ now()->format('d-M-Y') }}</span>
                    </div>
                    <div class="p-info d-flex justify-content-between">
                        <span>អតិថិជន: សុខ សុភា</span>
                        <span>អាយុ: 32</span>
                    </div>

                    <table class="table table-sm table-bordered mt-2 mb-2">
                        <thead>
                            <tr>
                                <th style="width:28px">N°</th>
                                <th>ថ្នាំ</th>
                                <th style="width:45px">ចំនួន</th>
                                <th style="width:65px">តម្លៃ</th>
                                <th style="width:70px">សរុប</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">1</td>
                                <td>Amoxicillin 500mg</td>
                                <td class="text-center">2</td>
                                <td class="text-right"><span class="p-cur">$</span>0.50</td>
                                <td class="text-right"><span class="p-cur">$</span>1.00</td>
                            </tr>
                            <tr>
                                <td class="text-center">2</td>
                                <td>Paracetamol 500mg</td>
                                <td class="text-center">1</td>
                                <td class="text-right"><span class="p-cur">$</span>0.20</td>
                                <td class="text-right"><span class="p-cur">$</span>0.20</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="p-totals">
                        <div class="d-flex justify-content-between">
                            <span>សរុបរង</span>
                            <span><span class="p-cur">$</span>1.20</span>
                        </div>
                        <div class="d-flex justify-content-between" id="p_tax_row">
                            <span>ពន្ធ (<span id="p_tax_pct">0</span>%)</span>
                            <span><span class="p-cur">$</span><span id="p_tax_amount">0.00</span></span>
                        </div>
                        <div class="d-flex justify-content-between font-weight-bold">
                            <span>សរុបចុងក្រោយ</span>
                            <span><span class="p-cur">$</span><span id="p_grand_total">1.20</span></span>
                        </div>
                    </div>

                    <p class="text-center small text-muted mt-3 mb-0" id="p_footer"></p>
                </div>
            </div>
        </div>
    </div>
</div>

@stop

@section('css')
<style>
    .page-title {
        font-weight: 700;
    }

    .form-group {
        margin-bottom: 22px;
    }

    .form-group label {
        font-weight: 600;
        margin-bottom: 8px;
    }

    .form-control {
        border-radius: 10px;
        height: 45px;
    }

    .preview-wrapper {
        background: #e9ecef;
        padding: 24px;
        display: flex;
        justify-content: center;
    }

    .invoice-preview {
        background: #fff;
        box-shadow: 0 0 8px rgba(0, 0, 0, .15);
        padding: 20px;
        font-size: 12px;
        transition: width .2s ease;
    }

    .invoice-preview.size-A4 {
        width: 100%;
        max-width: 480px;
    }

    .invoice-preview.size-80mm {
        width: 260px;
        font-size: 11px;
    }

    .invoice-preview table {
        font-size: 11px;
    }

    .p-header h5 {
        margin-bottom: 2px;
        font-weight: 700;
    }

    .p-info {
        font-size: 11px;
        margin: 6px 0;
    }

    .p-totals {
        font-size: 12px;
    }
</style>
@stop

@section('js')
@parent
<script>
    $(function () {
        const csrf = "{{ csrf_token() }}";
        const routeUpdate = "{{ route('settingsbillings.update') }}";

        function showToast(message, type = "success") {
            if (!$("#toastContainer").length) {
                $("body").append('<div id="toastContainer" class="toast-container-custom"></div>');
            }
            const icon = type === "success" ? "fa-check-circle" : type === "error" ? "fa-times-circle" : "fa-info-circle";
            const $toast = $(`<div class="toast-custom ${type}"><i class="fas ${icon}"></i><span>${message}</span></div>`);
            $("#toastContainer").append($toast);
            setTimeout(() => {
                $toast.fadeOut(200, function () { $(this).remove(); });
            }, 3000);
        }
        function pad(n) { return String(n).padStart(6, '0'); }

        function renderPreview() {
            const cur = $('#f_currency').val() || '$';
            const tax = parseFloat($('#f_tax').val()) || 0;
            const prefix = $('#f_prefix').val() || '';
            const footer = $('#f_footer').val();
            const number = $('#f_number').val() || 1;
            const size = $('#f_size').val();

            $('#p_invoice_no').text(prefix + pad(number));
            $('.p-cur').text(cur);

            const subtotal = 1.20;
            const taxAmount = subtotal * (tax / 100);
            const grandTotal = subtotal + taxAmount;

            $('#p_tax_pct').text(tax);
            $('#p_tax_amount').text(taxAmount.toFixed(2));
            $('#p_grand_total').text(grandTotal.toFixed(2));
            $('#p_tax_row').toggle(tax > 0);

            $('#p_footer').text(footer || '');

            $('#invoicePreview').removeClass('size-A4 size-80mm').addClass('size-' + size);
            $('#previewSizeBadge').text(size);
        }

        $('#settingsForm input, #settingsForm select').on('input change', renderPreview);
        renderPreview();

        $('#btnSaveSettings').on('click', function () {
            const data = {
                _token: csrf,
                currency_symbol: $('#f_currency').val(),
                tax_percent: $('#f_tax').val(),
                invoice_prefix: $('#f_prefix').val(),
                invoice_footer: $('#f_footer').val(),
                next_invoice_number: $('#f_number').val(),
                print_size: $('#f_size').val(),
                invoice_auto_number: $('#f_auto').is(':checked') ? 1 : 0,
            };

            $('#settingsErrors').addClass('d-none').empty();

            $.ajax({ url: routeUpdate, method: 'POST', data: data })
            $.ajax({ url: routeUpdate, method: 'POST', data: data })
                .done(() => {
                    showToast('រក្សាទុកជោគជ័យ', 'success');
                })
                .fail((xhr) => {
                    let msg = 'មានបញ្ហា សូមព្យាយាមម្តងទៀត';
                    if (xhr.responseJSON?.errors) {
                        msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    }
                    $('#settingsErrors').html(msg).removeClass('d-none');
                    showToast('រក្សាទុកមិនបានសម្រេច', 'error');
                });
        });
    });
</script>
@stop