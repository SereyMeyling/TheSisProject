@extends('adminlte::page')

@section('title', 'ការរំលឹកការណាត់ជួប')

@section('content')
<div class="card border-0 shadow-sm mt-3">
    <div class="card-header bg-white">
        <h5 class="mb-0 font-weight-bold">
            <i class="fas fa-phone-volume text-primary mr-2"></i>
            ទូរស័ព្ទរំលឹកអ្នកជំងឺ សម្រាប់ថ្ងៃស្អែក ({{ \Carbon\Carbon::tomorrow()->format('d/m/Y') }})
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="bg-light">
                    <tr>
                        <th>ថ្ងៃ</th>
                        <th>ម៉ោង</th>
                        <th>អ្នកជំងឺ</th>
                        <th>លេខទូរស័ព្ទ</th>
                        <th>វេជ្ជបណ្ឌិត</th>
                        <th>ស្ថានភាព</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($appointments as $a)
                        <tr>
                            <td>{{ $a->appointment_date->format('d/m/Y') }}</td>
                            <td class="font-weight-bold">{{ $a->appointment_date->format('h:i A') }}</td>
                            <td>{{ $a->patient->full_name ?? 'N/A' }}</td>
                            <td>
                                @if($a->patient && $a->patient->phone)
                                    <a href="tel:{{ preg_replace('/\s+/', '', $a->patient->phone) }}">
                                        <i class="fas fa-phone mr-1"></i>{{ $a->patient->phone }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $a->doctor->name ?? 'N/A' }}</td>
                            <td class="call-status">
                                @if($a->reminder_called_at)
                                    <span class="text-success">
                                        <i class="fas fa-check-circle"></i>
                                        បានទូរស័ព្ទ {{ $a->reminder_called_at->format('h:i A') }}
                                        <small class="text-muted">({{ $a->caller->name ?? '-' }})</small>
                                    </span>
                                @else
                                    <span class="badge badge-warning mr-2">មិនទាន់បានទូរស័ព្ទ</span>
                                    <button type="button" class="btn btn-sm btn-outline-success btn-called"
                                        data-url="{{ route('appointment.reminders.called', $a->appointment_id) }}">
                                        <i class="fas fa-phone-alt mr-1"></i> បានទូរស័ព្ទហើយ
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">គ្មានការណាត់ជួបត្រូវរំលឹកសម្រាប់ថ្ងៃស្អែកទេ</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
$(function () {
    $(document).on('click', '.btn-called', function () {
        var $btn = $(this);
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true).find('i').removeClass('fa-phone-alt').addClass('fa-spinner fa-spin');

        fetch($btn.data('url'), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
        .then(function (r) {
            if (!r.ok) throw new Error(r.j.message || 'error');
            var $cell = $btn.closest('.call-status').empty();
            $cell.append(
                $('<span class="text-success">').append(
                    '<i class="fas fa-check-circle"></i> ',
                    document.createTextNode('បានទូរស័ព្ទ ' + r.j.at + ' (' + r.j.by + ')')
                )
            );
        })
        .catch(function () {
            $btn.prop('disabled', false).find('i').removeClass('fa-spinner fa-spin').addClass('fa-phone-alt');
            alert('មានបញ្ហា សូមព្យាយាមម្តងទៀត');
        });
    });
});
</script>
@stop