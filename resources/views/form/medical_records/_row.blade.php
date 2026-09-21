<tr id="row-{{ $record->record_id }}"
    data-record="{{ json_encode($record->toModalData(), JSON_UNESCAPED_UNICODE) }}">
    <td class="font-weight-bold text-dark">#MR-{{ $record->record_id }}</td>
    <td>
        <span class="font-weight-bold text-success d-block">{{ $record->patient->full_name ?? 'N/A' }}</span>
        <small class="text-muted">{{ $record->patient->patient_code ?? '' }}</small>
    </td>
    <td>{{ $record->visit_date ? $record->visit_date->format('d/m/Y H:i') : '-' }}</td>
    <td>
        <small class="d-block"><strong>BP:</strong> {{ $record->bp_systolic ?? '-' }}/{{ $record->bp_diastolic ?? '-' }} mmHg</small>
        <small class="d-block"><strong>HR:</strong> {{ $record->heart_rate ?? '-' }} bpm | <strong>Temp:</strong> {{ $record->temperature ?? '-' }}°C</small>
    </td>
    <td>{{ Str::limit($record->diagnosis ?? 'មិនទាន់មាន', 30) }}</td>
    <td>{{ $record->doctor->name ?? 'N/A' }}</td>
    <td>
        <button type="button" class="btn btn-sm btn-light text-info rounded-circle btn-view"><i class="fas fa-eye"></i></button>
        <button type="button" class="btn btn-sm btn-light text-primary rounded-circle btn-edit"><i class="fas fa-edit"></i></button>
    </td>
</tr>
