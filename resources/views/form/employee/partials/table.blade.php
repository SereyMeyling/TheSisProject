<table class="table align-middle mb-0">
    <thead>
        <tr>
            <th>ល.រ (ID)</th>
            <th>កូដបុគ្គលិក</th>
            <th>ឈ្មោះបុគ្គលិក</th>
            <th>ដេប៉ាតឺម៉ង់</th>
            <th>តួនាទី</th>
            <th>ជំនាញ</th>
            <th>លេខទូរស័ព្ទ</th>
            <th>ស្ថានភាព</th>
            <th>សកម្មភាព</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($employees as $employee)
            <tr>
                <td>{{ $employee->employee_id }}</td>
                <td><span class="badge badge-secondary">{{ $employee->employee_code }}</span></td>
                <td class="font-weight-bold">{{ $employee->first_name }} {{ $employee->last_name }}</td>
                <td>{{ $employee->department_name }}</td>
                <td>
                    @switch($employee->role)
                        @case('doctor')
                            <span class="badge badge-info"><i class="fas fa-user-md mr-1"></i> វេជ្ជបណ្ឌិត</span>
                            @break
                        @case('nurse')
                            <span class="badge badge-success"><i class="fas fa-user-nurse mr-1"></i> គិលានុបដ្ឋាក</span>
                            @break
                        @case('pharmacist')
                            <span class="badge badge-warning text-dark"><i class="fas fa-pills mr-1"></i> ឱសថការី</span>
                            @break
                        @case('lab_technician')
                            <span class="badge badge-primary"><i class="fas fa-microscope mr-1"></i> ផ្នែកមន្ទីរពិសោធន៍</span>
                            @break
                        @case('admin')
                            <span class="badge badge-danger"><i class="fas fa-user-shield mr-1"></i> អ្នកគ្រប់គ្រង</span>
                            @break
                        @default
                            <span class="badge badge-secondary">{{ ucfirst($employee->role) }}</span>
                    @endswitch
                </td>
                <td>{{ $employee->specialization ?? '-' }}</td>
                <td>{{ $employee->phone ?? '-' }}</td>
                <td>
                    @if ($employee->status === 'active')
                        <span class="badge badge-success">សកម្ម (Active)</span>
                    @else
                        <span class="badge badge-danger">អសកម្ម (Inactive)</span>
                    @endif
                </td>
                <td>
                    <div class="action-icons">
                        {{-- Edit --}}
                        <button class="btn btn-sm btn-outline-primary btn-edit" 
                                data-toggle="modal" 
                                data-target="#modalEdit" 
                                data-id="{{ $employee->employee_id }}">
                            <i class="fas fa-edit"></i>
                        </button>
                        {{-- Delete --}}
                        <button class="btn btn-sm btn-outline-danger btn-delete" 
                                data-id="{{ $employee->employee_id }}" 
                                data-name="{{ $employee->first_name }} {{ $employee->last_name }}"
                                data-toggle="modal"
                                data-target="#modalDelete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center py-4 text-muted">មិនមានទិន្នន័យបុគ្គលិកទេ</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="d-flex justify-content-center pagination-wrapper mt-3">
    {!! $employees->appends(request()->query())->links('pagination::bootstrap-4') !!}
</div>
