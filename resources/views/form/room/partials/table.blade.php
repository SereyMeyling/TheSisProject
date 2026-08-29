<table class="table align-middle mb-0">
    <thead>
        <tr>
            <th>ល.រ (ID)</th>
            <th>លេខបន្ទប់ (Room Number)</th>
            <th>ប្រភេទបន្ទប់ (Room Type)</th>
            <th>តម្លៃ/ថ្ងៃ (Price / Day)</th>
            <th>ស្ថានភាព (Status)</th>
            <th>សកម្មភាព (Actions)</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rooms as $room)
            <tr>
                <td>{{ $room->room_id }}</td>
                <td>
                    <span class="badge badge-primary font-weight-bold" style="font-size: 14px; padding: 6px 12px;">
                        <i class="fas fa-door-closed mr-1"></i> {{ $room->room_number }}
                    </span>
                </td>
                <td>
                    @switch($room->room_type)
                        @case('general')
                            <span class="badge badge-secondary"><i class="fas fa-procedures mr-1"></i> បន្ទប់ទូទៅ (General)</span>
                            @break
                        @case('private')
                            <span class="badge badge-info"><i class="fas fa-bed mr-1"></i> បន្ទប់ផ្ទាល់ខ្លួន (Private)</span>
                            @break
                        @case('icu')
                            <span class="badge badge-danger"><i class="fas fa-heartbeat mr-1"></i> បន្ទប់សង្គ្រោះបន្ទាន់ (ICU)</span>
                            @break
                        @case('isolation')
                            <span class="badge badge-warning text-dark"><i class="fas fa-user-shield mr-1"></i> បន្ទប់ដាច់ដោយឡែក (Isolation)</span>
                            @break
                        @default
                            <span class="badge badge-dark">{{ ucfirst($room->room_type) }}</span>
                    @endswitch
                </td>
                <td class="font-weight-bold text-success" style="font-size: 15px;">
                    ${{ number_format($room->price_per_day, 2) }}
                </td>
                <td>
                    @switch($room->status)
                        @case('available')
                            <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i> ទំនេរ (Available)</span>
                            @break
                        @case('occupied')
                            <span class="badge badge-danger"><i class="fas fa-user-check mr-1"></i> មានអ្នកជំងឺ (Occupied)</span>
                            @break
                        @case('maintenance')
                            <span class="badge badge-warning text-dark"><i class="fas fa-tools mr-1"></i> កំពុងជួសជុល (Maintenance)</span>
                            @break
                        @default
                            <span class="badge badge-secondary">{{ ucfirst($room->status) }}</span>
                    @endswitch
                </td>
                <td>
                    <div class="action-icons">
                        {{-- Edit --}}
                        <button class="btn btn-sm btn-outline-primary btn-edit" 
                                data-toggle="modal" 
                                data-target="#modalEdit" 
                                data-id="{{ $room->room_id }}">
                            <i class="fas fa-edit"></i>
                        </button>
                        {{-- Delete --}}
                        <button class="btn btn-sm btn-outline-danger btn-delete" 
                                data-id="{{ $room->room_id }}" 
                                data-name="{{ $room->room_number }}"
                                data-toggle="modal"
                                data-target="#modalDelete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">មិនមានទិន្នន័យបន្ទប់ទេ (No Rooms Found)</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="d-flex justify-content-center pagination-wrapper mt-3">
    {!! $rooms->appends(request()->query())->links('pagination::bootstrap-4') !!}
</div>
