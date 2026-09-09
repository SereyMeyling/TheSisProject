<table class="table align-middle mb-0">
    <thead>
        <tr>
            <th>ID</th>
            <th>ឈ្មោះ</th>
            <th>អ៊ីមែល</th>
            <th>ឈ្មោះអ្នកប្រើប្រាស់</th>
            <th>តួនាទី</th>
            <th>2FA</th>
            <th>សកម្មភាព</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($users as $u)
            <tr>
                <td>{{ $u->id }}</td>
                <td>{{ $u->name }}</td>
                <td>{{ $u->email }}</td>
                <td>{{ $u->username ?? '—' }}</td>
                <td>
                    @forelse ($u->roles as $role)
                        <span class="badge badge-info">{{ $role->name }}</span>
                    @empty
                        <span class="badge badge-light text-muted">គ្មានតួនាទី</span>
                    @endforelse
                </td>
                <td>
                    @if ($u->google2fa_secret)
                        <span class="badge badge-success">បានបើក</span>
                    @else
                        <span class="badge badge-secondary">មិនទាន់កំណត់</span>
                    @endif
                </td>
             <td>
    <div class="dropdown">
        <button
            type="button"
            class="btn btn-sm btn-light"
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false"
            title="សកម្មភាព"
        >
            <i class="fas fa-ellipsis-h"></i>
        </button>

        <div class="dropdown-menu dropdown-menu-right">

            {{-- Edit Role --}}
            <button
                type="button"
                class="dropdown-item btn-edit-role"
                data-toggle="modal"
                data-target="#modalUpdateRole"
                data-id="{{ $u->id }}"
                data-name="{{ $u->name }}"
                data-role="{{ $u->roles->first()->name ?? '' }}"
            >
                <i class="fas fa-user-tag mr-2 text-primary"></i>
                កំណត់តួនាទី
            </button>

            {{-- Reset 2FA --}}
            @if ($u->google2fa_secret)
                <button
                    type="button"
                    class="dropdown-item btn-reset-2fa"
                    data-toggle="modal"
                    data-target="#modalReset2FA"
                    data-id="{{ $u->id }}"
                    data-name="{{ $u->name }}"
                >
                    <i class="fas fa-shield-alt mr-2 text-warning"></i>
                    Reset 2FA
                </button>
            @else
                <button
                    type="button"
                    class="dropdown-item text-muted"
                    disabled
                >
                    <i class="fas fa-shield-alt mr-2"></i>
                    Reset 2FA
                </button>
            @endif

        </div>
    </div>
</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">មិនមានទិន្នន័យ</td>
            </tr>
        @endforelse
    </tbody>
</table>
<div class="d-flex justify-content-center pagination-wrapper">
    {!! $users->appends(request()->query())->links('pagination::bootstrap-4') !!}
</div>
