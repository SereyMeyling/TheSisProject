@extends('adminlte::page')

@section('title', 'Billing Settings')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 mt-3">
    <h2 class="page-title mb-0"></h2>
    <button class="btn btn-primary px-4" id="btnSaveSettings">
        <i class="fas fa-save mr-2"></i>
        រក្សាទុក
    </button>
</div>

<div class="alert alert-danger d-none" id="settingsErrors"></div>

<div class="row">
    {{-- ================= FORM ================= --}}
    <div class="col-lg-12">
        <div class="card setting-card">
            <div class="card-body">
                <form id="settingsForm">

                    <div class="row">

                        {{-- Currency --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>រូបិយវត្ថុចម្បង</label>
                                <input type="text" name="currency_symbol" id="f_currency" class="form-control"
                                    placeholder="$" value="{{ $settings->currency_symbol ?? '$' }}" required>
                            </div>
                        </div>

                        {{-- Tax --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>ពន្ធ %</label>
                                <input type="number" step="0.01" min="0" max="100" name="tax_percent" id="f_tax"
                                    class="form-control" placeholder="0" value="{{ $settings->tax_percent ?? 0 }}"
                                    required>
                            </div>
                        </div>

                        {{-- Secondary Currency --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>រូបិយវត្ថុទីពីរ</label>
                                <input type="text" name="secondary_currency_symbol" id="f_currency2"
                                    class="form-control" placeholder="៛"
                                    value="{{ $settings->secondary_currency_symbol ?? '៛' }}" required>
                            </div>
                        </div>

                        {{-- Exchange Rate --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    អត្រាប្តូរប្រាក់
                                    (1 <span id="f_cur1_label">$</span> = ? <span id="f_cur2_label">៛</span>)
                                </label>

                                <input type="number" step="0.01" min="0.01" name="exchange_rate" id="f_rate"
                                    class="form-control" placeholder="4100.00"
                                    value="{{ $settings->exchange_rate ?? 4100 }}" required>
                            </div>
                        </div>

                    </div>

                    {{-- Footer --}}
                    <div class="form-group">
                        <label>វិក្កយបត្រ Footer</label>

                        <input type="text" name="invoice_footer" id="f_footer" class="form-control"
                            placeholder="សូមអរគុណ!" value="{{ $settings->invoice_footer ?? '' }}">
                    </div>

                    {{-- Exchange Preview --}}
                    <div class="alert alert-light border" id="rateHint">
                        1 <span class="p-cur1">$</span>
                        =
                        <strong>
                            <span id="p_converted">4,100.00</span>
                        </strong>
                        <span class="p-cur2">៛</span>
                    </div>

                </form>
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

    #rateHint {
        font-size: 13px;
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
                $("body").append(
                    '<div id="toastContainer" class="toast-container-custom"></div>'
                );
            }

            const icon = type === "success"
                ? "fa-check-circle"
                : "fa-times-circle";

            const $toast = $(`
            <div class="toast-custom ${type}">
                <i class="fas ${icon}"></i>
                <span>${message}</span>
            </div>
        `);

            $("#toastContainer").append($toast);

            setTimeout(() => {
                $toast.fadeOut(200, function () {
                    $(this).remove();
                });
            }, 3000);
        }

        function renderPreview() {

            const cur1 = $('#f_currency').val() || '$';
            const cur2 = $('#f_currency2').val() || '៛';

            const rate = parseFloat($('#f_rate').val()) || 0;

            $('#f_cur1_label').text(cur1);
            $('#f_cur2_label').text(cur2);

            $('.p-cur1').text(cur1);
            $('.p-cur2').text(cur2);

            $('#p_converted').text(
                rate.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                })
            );
        }

        $('#settingsForm input').on('input change', renderPreview);

        renderPreview();

        $('#btnSaveSettings').on('click', function () {

            const data = {
                _token: csrf,

                currency_symbol: $('#f_currency').val(),
                secondary_currency_symbol: $('#f_currency2').val(),
                exchange_rate: $('#f_rate').val(),
                tax_percent: $('#f_tax').val(),
                invoice_footer: $('#f_footer').val(),
            };

            $('#settingsErrors')
                .addClass('d-none')
                .empty();

            $.ajax({
                url: routeUpdate,
                method: 'POST',
                data: data
            })
                .done(function () {

                    showToast(
                        'រក្សាទុកជោគជ័យ',
                        'success'
                    );

                })
                .fail(function (xhr) {

                    let msg = 'មានបញ្ហា សូមព្យាយាមម្តងទៀត';

                    if (xhr.responseJSON?.errors) {

                        msg = Object.values(
                            xhr.responseJSON.errors
                        )
                            .flat()
                            .join('<br>');
                    }

                    $('#settingsErrors')
                        .html(msg)
                        .removeClass('d-none');

                    showToast(
                        'រក្សាទុកមិនបានសម្រេច',
                        'error'
                    );
                });
        });
    });

</script>
@stop