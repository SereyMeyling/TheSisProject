<table class="table mb-0">
    <thead>
        <tr>
            <th>លេខបន្ទប់</th>
            <th>ប្រភេទ</th>
            <th>ស្ថានភាព</th>
            <th>តម្លៃ/ថ្ងៃ</th>
            <th>សកម្មភាព</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rooms as $room)
            <tr>
                <td>
                    <span class="room-type-bar type-{{ $room->room_type }}"></span>
                    <strong>{{ $room->room_number }}</strong>
                </td>
                <td>{{ $room->type_label }}</td>
                <td><span class="badge badge-{{ $room->status }}">{{ $room->status_label }}</span></td>
                <td>${{ number_format($room->price_per_day, 2) }}</td>
                <td>
                    <div class="action-icons justify-content-center">
                        <div class="dropdown">

                            {{-- Three dots --}}
                            <button type="button" class="btn btn-sm btn-light action-menu-btn" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false" title="សកម្មភាព">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>

                            {{-- Dropdown menu --}}
                            <div class="dropdown-menu dropdown-menu-right">

                                {{-- Edit --}}
                                <a href="#" class="dropdown-item btn-edit-room" data-id="{{ $room->room_id }}"
                                    data-room-number="{{ $room->room_number }}" data-room-type="{{ $room->room_type }}"
                                    data-status="{{ $room->status }}" data-price="{{ $room->price_per_day }}">
                                    <i class="fas fa-edit text-primary mr-2"></i>
                                    កែប្រែ
                                </a>

                                {{-- Delete --}}
                                <a href="#" class="dropdown-item btn-delete-room" data-id="{{ $room->room_id }}"
                                    data-number="{{ $room->room_number }}">
                                    <i class="fas fa-trash text-danger mr-2"></i>
                                    លុប
                                </a>

                            </div>
                        </div>
                    </div>
                </td>

            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center text-muted py-4">មិនមានទិន្នន័យបន្ទប់ទេ</td>
            </tr>
        @endforelse
    </tbody>
</table>
<div class="px-3 pb-3">{{ $rooms->links() }}</div>
