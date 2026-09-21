@extends('adminlte::page')

@section('title', 'បញ្ជី Medical Records')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.4rem;"></h1>
    <button type="button" id="btnCreate" class="btn btn-primary px-3 rounded-pill shadow-sm"
        style="background-color: #00695c;">
        <i class="fas fa-plus mr-1"></i> បង្កើត Record ថ្មី
    </button>
</div>
@stop

@section('content')
<div class="container-fluid">
    <div id="flash">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
                <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        @endif
    </div>

    <div class="card border-0 shadow-sm rounded-lg">
        <div class="card-body p-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-0">
                    <thead class="bg-light">
                        <tr class="text-secondary small">
                            <th>ID</th>
                            <th>អ្នកជំងឺ</th>
                            <th>កាលបរិច្ឆេទពិនិត្យ</th>
                            <th>សញ្ញាជីវិត (Vitals)</th>
                            <th>ការវិនិច្ឆ័យ (Diagnosis)</th>
                            <th>គ្រូពេទ្យ</th>
                            <th>សកម្មភាព</th>
                        </tr>
                    </thead>
                    <tbody id="records-body">
                        @forelse($records as $record)
                            @include('form.medical_records._row', ['record' => $record])
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">មិនទាន់មានទិន្នន័យ Medical Record ឡើយ
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-2">
                {{ $records->links() }}
            </div>
        </div>
    </div>
</div>

{{-- ============ VIEW MODAL ============ --}}
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header modal-header-custom ">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-file-invoice text-success mr-2"></i> ព័ត៌មានលម្អិត #MR-<span data-f="id"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row border-bottom pb-3 mb-3">
                    <div class="col-md-6">
                        <h5 class="font-weight-bold text-success" data-f="patient_name"></h5>
                        <p class="text-muted mb-0">លេខកូដអ្នកជំងឺ: <span data-f="patient_code"></span></p>
                        <p class="text-muted mb-0">ភេទ: <span data-f="sex"></span> | អាយុ: <span data-f="age"></span>
                            ឆ្នាំ</p>
                    </div>
                    <div class="col-md-6 text-md-right">
                        <p class="mb-1"><strong>កាលបរិច្ឆេទពិនិត្យ:</strong> <span data-f="visit_date"></span></p>
                        <p class="mb-0"><strong>គ្រូពេទ្យទទួលខុសត្រូវ:</strong> <span data-f="doctor"></span></p>
                    </div>
                </div>

                <h6 class="font-weight-bold text-dark mb-3">
                    <i class="fas fa-heartbeat text-danger mr-2"></i> សញ្ញាជីវិត (Vital Signs)
                </h6>
                <div class="row text-center mb-3">
                    @foreach([['BP', 'bp', 'mmHg'], ['Heart Rate', 'heart_rate', 'bpm'], ['Resp. Rate', 'respiratory_rate', ''], ['Temp', 'temperature', '°C'], ['SpO2', 'spo2', '%'], ['Weight', 'weight', 'kg']] as [$label, $key, $unit])
                        <div class="col-md-2 col-4 mb-2">
                            <div class="p-2 bg-light rounded border">
                                <small class="text-muted d-block">{{ $label }}</small>
                                <strong data-f="{{ $key }}"></strong> <small>{{ $unit }}</small>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mb-3">
                    <h6 class="font-weight-bold text-dark">ការវិនិច្ឆ័យរោគ (Diagnosis)</h6>
                    <div class="p-3 bg-light rounded border" style="white-space: pre-line;" data-f="diagnosis"
                        data-empty="គ្មានការវិនិច្ឆ័យ"></div>
                </div>
                <div>
                    <h6 class="font-weight-bold text-dark">ចំណាំបន្ថែម (Notes)</h6>
                    <div class="p-3 bg-light rounded border" style="white-space: pre-line;" data-f="notes"
                        data-empty="គ្មានចំណាំ"></div>
                </div>
            </div>
        </div>
    </div>
</div>


{{-- ============ CREATE MODAL ============ --}}
<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <form id="createForm" method="POST" action="{{ route('medical-records.store') }}">
                @csrf

                <div class="modal-header modal-header-custom ">
                    <h5 class="modal-title font-weight-bold">
                        <i class="fas fa-plus-circle text-success mr-2"></i> បង្កើត Medical Record ថ្មី
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <div class="modal-body">
                    <div id="createErrors" class="alert alert-danger d-none"></div>

                    <div class="row">
                        <div class="col-md-5 form-group">
                            <label class="small font-weight-bold text-secondary">ជ្រើសរើសអ្នកជំងឺ <span
                                    class="text-danger">*</span></label>
                            <select name="patient_id" class="form-control" required>
                                <option value="" disabled selected>-- ជ្រើសរើសអ្នកជំងឺ --</option>
                                @foreach($patients as $patient)
                                    <option value="{{ $patient->patient_id }}">
                                        {{ $patient->patient_code ?? 'ID: ' . $patient->patient_id }} -
                                        {{ $patient->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label class="small font-weight-bold text-secondary">គ្រូពេទ្យពិនិត្យ (Doctor)</label>
                            <input type="text" class="form-control bg-light" value="{{ auth()->user()->name }}"
                                readonly>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="small font-weight-bold text-secondary">កាលបរិច្ឆេទពិនិត្យ <span
                                    class="text-danger">*</span></label>
                            <input type="datetime-local" name="visit_date" class="form-control" required>
                        </div>
                    </div>

                    <h6 class="font-weight-bold text-success mb-3">
                        <i class="fas fa-heartbeat text-danger mr-2"></i> សញ្ញាជីវិត (Vital Signs)
                    </h6>
                    <div class="row">
                        @foreach([['bp_systolic', 'BP Systolic (mmHg)', '', '120'], ['bp_diastolic', 'BP Diastolic (mmHg)', '', '80'], ['heart_rate', 'Heart Rate (bpm)', '', '72'], ['respiratory_rate', 'Respiratory Rate', '', '18'], ['temperature', 'Temperature (°C)', '0.1', '36.5'], ['spo2', 'SpO2 (%)', '0.1', '98'], ['weight', 'Weight (kg)', '0.1', '65']] as [$name, $label, $step, $ph])
                            <div class="col-md-3 form-group">
                                <label class="small font-weight-bold text-secondary">{{ $label }}</label>
                                <input type="number" name="{{ $name }}" class="form-control" placeholder="{{ $ph }}"
                                    @if($step) step="{{ $step }}" @endif>
                            </div>
                        @endforeach
                    </div>

                    <div class="form-group">
                        <label class="small font-weight-bold text-secondary">ការវិនិច្ឆ័យរោគ (Diagnosis)</label>
                        <textarea name="diagnosis" class="form-control" rows="3"
                            placeholder="បញ្ចូលលទ្ធផលនៃការវិនិច្ឆ័យ..."></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold text-secondary">ចំណាំបន្ថែម (Notes / Symptoms)</label>
                        <textarea name="notes" class="form-control" rows="2"
                            placeholder="បញ្ចូលរោគសញ្ញា ឬការកត់សម្គាល់បន្ថែម..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">បោះបង់</button>
                    <button type="submit" id="createSave" class="btn btn-primary px-4 font-weight-bold">
                        <i class="fas fa-save mr-1"></i> រក្សាទុក Medical Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============ EDIT MODAL ============ --}}
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <form id="editForm" method="POST" action="">
                @csrf
                @method('PUT')

                <div class="modal-header modal-header-custom ">
                    <h5 class="modal-title font-weight-bold">
                        <i class="fas fa-edit text-primary mr-2"></i> កែប្រែ <span id="editTitle"></span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <div class="modal-body">
                    <div id="editErrors" class="alert alert-danger d-none"></div>

                    <div class="row">
                        <div class="col-md-8 form-group">
                            <label class="small font-weight-bold text-secondary">ជ្រើសរើសអ្នកជំងឺ</label>
                            <select name="patient_id" class="form-control" required>
                                @foreach($patients as $patient)
                                    <option value="{{ $patient->patient_id }}">
                                        {{ $patient->full_name }} ({{ $patient->patient_code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="small font-weight-bold text-secondary">គ្រូពេទ្យពិនិត្យ (Doctor)</label>
                            <input type="text" id="editDoctor" class="form-control bg-light" readonly>
                        </div>
                    </div>

                    <div class="row">
                        @foreach([['bp_systolic', 'BP Systolic (mmHg)', ''], ['bp_diastolic', 'BP Diastolic (mmHg)', ''], ['heart_rate', 'Heart Rate (bpm)', ''], ['respiratory_rate', 'Respiratory Rate', ''], ['temperature', 'Temperature (°C)', '0.1'], ['spo2', 'SpO2 (%)', '0.1'], ['weight', 'Weight (kg)', '0.1']] as [$name, $label, $step])
                            <div class="col-md-3 form-group">
                                <label class="small font-weight-bold text-secondary">{{ $label }}</label>
                                <input type="number" name="{{ $name }}" class="form-control" @if($step) step="{{ $step }}"
                                @endif>
                            </div>
                        @endforeach
                    </div>

                    <div class="form-group">
                        <label class="small font-weight-bold text-secondary">ការវិនិច្ឆ័យរោគ (Diagnosis)</label>
                        <textarea name="diagnosis" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold text-secondary">ចំណាំបន្ថែម (Notes / Symptoms)</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">បោះបង់</button>
                    <button type="submit" id="editSave" class="btn btn-primary px-4">
                        <i class="fas fa-save mr-1"></i> រក្សាទុកការកែប្រែ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
    $(function () {
        var PER_PAGE = 10;

        var $view = $('#viewModal');
        var $edit = $('#editModal'), $editForm = $('#editForm'), $editErrors = $('#editErrors'), $editSave = $('#editSave');
        var $create = $('#createModal'), $createForm = $('#createForm'), $createErrors = $('#createErrors'), $createSave = $('#createSave');

        function flash(message, type) {
            var $a = $('<div class="alert alert-' + type + ' alert-dismissible fade show border-0 shadow-sm mb-3">')
                .text(message)
                .append('<button type="button" class="close" data-dismiss="alert">&times;</button>');
            $('#flash').empty().append($a);
            setTimeout(function () { $a.alert('close'); }, 3500);
        }

        function nowLocal() {
            var d = new Date();
            d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
            return d.toISOString().slice(0, 16); // yyyy-MM-ddTHH:mm
        }

        // Shared AJAX submit for the create and edit forms
        function ajaxSubmit($form, $errors, $btn, icon, onSuccess) {
            $errors.addClass('d-none').empty();
            $btn.prop('disabled', true).find('i').removeClass(icon).addClass('fa-spinner fa-spin');

            fetch($form.attr('action'), {
                method: 'POST', // edit form sends _method=PUT inside the form data
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $form.find('input[name=_token]').val()
                },
                body: new FormData($form[0])
            })
                .then(function (res) {
                    return res.json().then(function (json) { return { ok: res.ok, json: json }; });
                })
                .then(function (r) {
                    if (!r.ok) {
                        var msgs = r.json.errors
                            ? Object.values(r.json.errors).map(function (e) { return e[0]; })
                            : [r.json.message || 'មានបញ្ហា'];
                        $errors.html(msgs.join('<br>')).removeClass('d-none');
                        return;
                    }
                    onSuccess(r.json);
                })
                .catch(function () {
                    $errors.text('មានបញ្ហាការតភ្ជាប់ សូមព្យាយាមម្តងទៀត').removeClass('d-none');
                })
                .finally(function () {
                    $btn.prop('disabled', false).find('i').removeClass('fa-spinner fa-spin').addClass(icon);
                });
        }

        // ---- CREATE: open ----
        function openCreate(patientId) {
            $createErrors.addClass('d-none').empty();
            $createForm[0].reset();
            $createForm.find('[name=visit_date]').val(nowLocal());
            if (patientId) $createForm.find('[name=patient_id]').val(String(patientId));
            $create.modal('show');
        }

        $('#btnCreate').on('click', function () { openCreate(); });

        // Opened via redirect from the old create route: ?create=1&patient_id=...
        var params = new URLSearchParams(window.location.search);
        if (params.get('create')) {
            openCreate(params.get('patient_id'));
            history.replaceState(null, '', window.location.pathname); // so refresh doesn't reopen it
        }

        // ---- CREATE: save, add the new row on top ----
        $createForm.on('submit', function (e) {
            e.preventDefault();
            ajaxSubmit($createForm, $createErrors, $createSave, 'fa-save', function (json) {
                $('#empty-row').remove();
                $('#records-body').prepend(json.row);

                // keep the table at one page of rows
                var $rows = $('#records-body tr[id^=row-]');
                if ($rows.length > PER_PAGE) $rows.last().remove();

                $create.modal('hide');
                flash(json.message, 'success');
            });
        });

        // ---- VIEW: fill from the row's data, no request ----
        $('#records-body').on('click', '.btn-view', function () {
            var d = $(this).closest('tr').data('record');

            $view.find('[data-f]').each(function () {
                var v = d[$(this).data('f')];
                var empty = $(this).data('empty') || '-';
                $(this).text(v === null || v === undefined || v === '' ? empty : v);
            });

            $view.modal('show');
        });

        // ---- EDIT: fill the form from the row's data, no request ----
        $('#records-body').on('click', '.btn-edit', function () {
            var d = $(this).closest('tr').data('record');

            $editErrors.addClass('d-none').empty();
            $editForm.attr('action', d.update_url);
            $('#editTitle').text('#MR-' + d.id);
            $('#editDoctor').val(d.doctor);

            $editForm.find('[name]').each(function () {
                if (this.name === '_token' || this.name === '_method') return;
                var v = d[this.name];
                $(this).val(v === null || v === undefined ? '' : v);
            });

            $edit.modal('show');
        });

        // ---- EDIT: save, replace only the changed row ----
        $editForm.on('submit', function (e) {
            e.preventDefault();
            ajaxSubmit($editForm, $editErrors, $editSave, 'fa-save', function (json) {
                var id = $editForm.attr('action').split('/').pop();
                $('#row-' + id).replaceWith(json.row);
                $edit.modal('hide');
                flash(json.message, 'success');
            });
        });
    });
</script>
@stop
