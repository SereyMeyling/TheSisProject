
<table class="table align-middle mb-0">
    <thead>
        <tr>
            <th>លរ</th>
            <th>ឈ្មោះ</th>
            <th>ការពិពណ៏នា</th>
            <th>ថ្ថ្ងៃបង្កើត</th>
            <th>សកម្មភាព</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($department as $depart)
            <tr>
                <td>{{ $depart->department_id }}</td>
                <td>{{ $depart->department_name }}</td>
                <td>{{ $depart->description }}</td>
                <td>{{ $depart->created_at->format('d M, Y') }}</td>
                <td>
                    <div class="action-icons">
                        {{-- Edit --}}
                        <button class="btn btn-sm btn-outline-secondary btn-edit" data-toggle="modal"
                            data-target="#modalEdit" data-id="{{ $depart->department_id }}">
                            <i class="fas fa-edit"></i>
                        </button>
                        {{-- Delete --}}
                        <button class="btn btn-sm btn-outline-secondary btn-delete" data-toggle="modal"
                            data-id="{{ $depart->department_id }}" data-name="{{ $depart->department_name }}"
                            data-target="#modalDelete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center py-4 text-muted">មិនមានទិន្នន័យ</td>
            </tr>
        @endforelse
    </tbody>
</table>
<div class="d-flex justify-content-center pagination-wrapper">
    {!! $department->appends(request()->query())->links('pagination::bootstrap-4') !!}
</div>
