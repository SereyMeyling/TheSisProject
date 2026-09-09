<div class="action-icons d-flex justify-content-center">
    <div class="dropdown">
        {{-- Three dots --}}
        <button type="button" class="btn btn-sm btn-light action-menu-btn" data-toggle="dropdown" aria-haspopup="true"
            aria-expanded="false">
            <i class="fas fa-ellipsis-h"></i>
        </button>

        {{-- Dropdown menu --}}
        <div class="dropdown-menu dropdown-menu-right">

            {{-- Edit --}}
            <button type="button" class="dropdown-item btn-edit" data-id="{{ $medicine->medicine_id }}">
                <i class="fas fa-edit text-primary mr-2"></i>
                កែប្រែ
            </button>

            {{-- Restock --}}
            <button type="button" class="dropdown-item btn-restock" data-id="{{ $medicine->medicine_id }}"
                data-name="{{ $medicine->medicine_name }}">
                <i class="fas fa-box text-success mr-2"></i>
                បន្ថែមស្តុក
            </button>

            {{-- Details --}}
            <button type="button" class="dropdown-item btn-detail" data-id="{{ $medicine->medicine_id }}">
                <i class="fas fa-list-alt text-info mr-2"></i>
                មើលលម្អិត
            </button>

        </div>
    </div>
</div>
