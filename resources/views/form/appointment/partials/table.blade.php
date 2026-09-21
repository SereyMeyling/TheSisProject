<table class="table align-middle mb-0">
    <thead>
        <tr>
            <th>ល.រ (ID)</th>
            <th>អ្នកជំងឺ (Patient)</th>
            <th>វេជ្ជបណ្ឌិត (Doctor)</th>
            <th>កាលបរិច្ឆេទ & ម៉ោង</th>
            <th>មូលហេតុ (Reason)</th>
            <th>ស្ថានភាព (Status)</th>
            <th>សកម្មភាព (Actions)</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($appointments as $app)
            <tr class="{{ $app->is_overdue ? 'table-danger' : '' }}">
                <td>{{ $app->appointment_id }}</td>
                <td>
                    <div class="font-weight-bold text-dark">
                        {{ $app->patient ? $app->patient->full_name : 'N/A' }}
                    </div>
                    <small class="text-muted"><i class="fas fa-phone mr-1"></i> {{ $app->patient ? $app->patient->phone : '-' }}</small>
                </td>
                <td>
                    <div class="font-weight-bold text-primary">
                        <i class="fas fa-user-md mr-1"></i>
                        {{ $app->doctor ? $app->doctor->name : 'N/A' }}
                    </div>
                    <small class="text-muted">
                        {{ $app->doctor ? ($app->doctor->specialization ?? 'វេជ្ជបណ្ឌិត') : '' }}
                    </small>
                </td>
                <td class="{{ $app->is_overdue ? 'text-danger' : '' }}">
                    <div class="font-weight-bold">
                        <i class="far fa-calendar-alt {{ $app->is_overdue ? '' : 'text-info' }} mr-1"></i>
                        {{ $app->appointment_date ? $app->appointment_date->format('d M, Y') : '-' }}
                    </div>
                    <small class="{{ $app->is_overdue ? '' : 'text-muted' }}">
                        <i class="far fa-clock mr-1"></i>
                        {{ $app->appointment_date ? $app->appointment_date->format('h:i A') : '-' }}
                    </small>
                    @if($app->is_overdue)
                        <div><small class="font-weight-bold"><i class="fas fa-exclamation-circle mr-1"></i> ហួសកំណត់ សូមធ្វើបច្ចុប្បន្នភាពស្ថានភាព</small></div>
                    @endif
                </td>
                <td>{{ $app->reason ?? '-' }}</td>
                <td>
                    @if($app->is_overdue)
                        <span class="badge badge-danger"><i class="fas fa-exclamation-triangle mr-1"></i> ហួសកំណត់</span>
                    @else
                        @switch($app->status)
                            @case('scheduled')
                                <span class="badge badge-info"><i class="fas fa-clock mr-1"></i> បានណាត់</span>
                                @break
                            @case('completed')
                                <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i> បានរួចរាល់</span>
                                @break
                            @case('cancelled')
                                <span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i> បានបោះបង់</span>
                                @break
                            @default
                                <span class="badge badge-secondary">{{ ucfirst($app->status) }}</span>
                        @endswitch
                    @endif
                </td>
                <td>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-light" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false" title="សកម្មភាព">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>

                        <div class="dropdown-menu dropdown-menu-right">
                            <button type="button" class="dropdown-item btn-edit" data-toggle="modal"
                                data-target="#modalEdit" data-id="{{ $app->appointment_id }}">
                                <i class="fas fa-edit mr-2 text-primary"></i> កែប្រែ
                            </button>

                            <button type="button" class="dropdown-item btn-delete"
                                data-id="{{ $app->appointment_id }}"
                                data-name="{{ $app->patient ? $app->patient->full_name : 'ID: ' . $app->appointment_id }}"
                                data-toggle="modal" data-target="#modalDelete">
                                <i class="fas fa-trash mr-2 text-danger"></i> លុប
                            </button>
                        </div>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">មិនមានទិន្នន័យការណាត់ជួបទេ</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="d-flex justify-content-center pagination-wrapper mt-3">
    {!! $appointments->appends(request()->query())->links('pagination::bootstrap-4') !!}
</div>
