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
                    <div class="action-icons">
                        {{-- Edit Role --}}
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-primary btn-edit-role"
                            data-toggle="modal"
                            data-target="#modalUpdateRole"
                            data-id="{{ $u->id }}"
                            data-name="{{ $u->name }}"
                            data-role="{{ $u->roles->first()->name ?? '' }}"
                            title="កំណត់តួនាទី"
                        >
                            <i class="fas fa-user-tag"></i> តួនាទី
                        </button>

                        {{-- Reset 2FA --}}
                        @if ($u->google2fa_secret)
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-warning btn-reset-2fa"
                                data-toggle="modal"
                                data-target="#modalReset2FA"
                                data-id="{{ $u->id }}"
                                data-name="{{ $u->name }}"
                                title="Reset 2FA"
                            >
                                <i class="fas fa-shield-alt"></i> Reset 2FA
                            </button>
                        @else
                            <button type="button" class="btn btn-sm btn-outline-warning" disabled title="គ្មាន 2FA ត្រូវកំណត់ឡើងវិញទេ">
                                <i class="fas fa-shield-alt"></i> Reset 2FA
                            </button>
                        @endif
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
