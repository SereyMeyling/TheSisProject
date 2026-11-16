<div class="table-responsive">
    <table class="table table-hover align-middle border-0">
        <thead class="bg-light">
            <tr class="text-secondary small">
                <th>លេខសម្គាល់</th>
                <th>ឈ្មោះពេញ</th>
                <th>ភេទ</th>
                <th>ថ្ងៃខែឆ្នាំកំណើត</th>
                <th>លេខទូរស័ព្ទ</th>
                <th>ថ្ងៃចុះឈ្មោះ</th>
                <th>សកម្មភាព</th>
            </tr>
        </thead>
        <tbody>
            @forelse($patients as $patient)
                <tr>
                    <td class="font-weight-bold text-dark">{{ $patient->patient_code }}</td>
                    <td class="font-weight-bold">{{ $patient->full_name }}</td>
                    <td>{{ strtolower($patient->sex) == 'male' ? 'ប្រុស' : 'ស្រី' }}</td>
                    <td>{{ $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->format('Y-m-d') : '-' }}
                    </td>
                    <td>{{ $patient->phone ?? '-' }}</td>
                    <td>{{ $patient->created_at ? $patient->created_at->format('d/m/Y') : '-' }}
                    </td>
                    <td>
                        <div class="dropdown">
                            <button type="button" class="btn btn-sm btn-light" data-toggle="dropdown" aria-haspopup="true"
                                aria-expanded="false" title="សកម្មភាព">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a href="{{ route('patients.show', $patient->patient_id) }}" class="dropdown-item">
                                    <i class="fas fa-eye mr-2 text-info"></i> មើលព័ត៌មាន
                                </a>
                                <a href="{{ route('patients.print', $patient->patient_id) }}" target="_blank"
                                    class="dropdown-item">
                                    <i class="fas fa-print mr-2 text-success"></i> បោះពុម្ព
                                </a>
                                <a href="{{ route('patients.edit', $patient->patient_id) }}" class="dropdown-item">
                                    <i class="fas fa-edit mr-2 text-primary"></i> កែប្រែ
                                </a>
                                <button type="button" class="dropdown-item text-danger btn-delete-patient"
                                    data-id="{{ $patient->patient_id }}" data-name="{{ $patient->full_name }}"
                                    data-toggle="modal" data-target="#modalDeletePatient">

                                    <i class="fas fa-trash mr-2"></i> លុប
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        មិនទាន់មានទិន្នន័យអ្នកជំងឺឡើយ</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="d-flex justify-content-end mt-2">
    {{ $patients->appends(request()->query())->links() }}
</div>