<div class="text-nowrap">
    <button class="btn btn-sm btn-outline-primary btn-edit" data-id="{{ $medicine->medicine_id }}" title="កែប្រែ">
        <i class="fas fa-edit"></i>
    </button>
    <button class="btn btn-sm btn-outline-success btn-restock" data-id="{{ $medicine->medicine_id }}"
        data-name="{{ $medicine->medicine_name }}" title="បន្ថែមស្តុក">
        <i class="fas fa-box"></i>
    </button>
    <button class="btn btn-sm btn-outline-info btn-detail" data-id="{{ $medicine->medicine_id }}" title="មើលលម្អិត">
        <i class="fas fa-list-alt"></i>
    </button>

</div>