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
                        <div class="dropdown">
                            {{-- Three dots button --}}
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>

                            {{-- Dropdown menu --}}
                            <div class="dropdown-menu dropdown-menu-right">

                                {{-- Edit --}}
                                <button type="button" class="dropdown-item btn-edit" data-toggle="modal"
                                    data-target="#modalEdit" data-id="{{ $depart->department_id }}">
                                    <i class="fas fa-edit text-primary mr-2"></i>
                                    កែប្រែ
                                </button>

                                {{-- Delete --}}
                                <button type="button" class="dropdown-item btn-delete" data-toggle="modal"
                                    data-target="#modalDelete" data-id="{{ $depart->department_id }}"
                                    data-name="{{ $depart->department_name }}">
                                    <i class="fas fa-trash text-danger mr-2"></i>
                                    លុប
                                </button>

                            </div>
                        </div>
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
