@extends('adminlte::page')

@section('title', 'ព័ត៌មានអ្នកជំងឺ')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h4 class="m-0 text-dark font-weight-bold">ព័ត៌មានអ្នកជំងឺ</h4>
        <a href="{{ route('patients.create') }}" class="btn btn-success px-3 rounded-pill font-weight-bold shadow-sm">
            <i class="fas fa-user-plus mr-1"></i> ចុះឈ្មោះអ្នកជំងឺថ្មី
        </a>
    </div>
@stop

@section('content')
    <div class="container-fluid">

        <div class="row mb-3">
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box shadow-sm border-0 rounded-lg">
                    <span class="info-box-icon bg-light text-success"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-muted small">អ្នកជំងឺសរុបថ្ងៃនេះ</span>
                        <span class="info-box-number text-dark h4 mb-0">{{ $todayCount }} នាក់</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box shadow-sm border-0 rounded-lg">
                    <span class="info-box-icon bg-light text-info"><i class="fas fa-user-plus"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-muted small">អ្នកជំងឺថ្មី</span>
                        <span class="info-box-number text-dark h4 mb-0">{{ $newPatients }} នាក់</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box shadow-sm border-0 rounded-lg">
                    <span class="info-box-icon bg-light text-danger"><i class="fas fa-history"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-muted small">អ្នកជំងឺចាស់</span>
                        <span class="info-box-number text-dark h4 mb-0">{{ $oldPatients }} នាក់</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box shadow-sm border-0 rounded-lg">
                    <span class="info-box-icon bg-light text-warning"><i class="fas fa-hourglass-half"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-muted small">អ្នកជំងឺកំពុងរង់ចាំ</span>
                        <span class="info-box-number text-warning h4 mb-0">{{ $waitingCount }} នាក់</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-9">
                <div class="card border-0 shadow-sm rounded-lg">
                    <div class="card-body p-3">

                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        {{-- Filter Forms --}}
                        <form method="GET" action="{{ route('patients.index') }}" class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label class="small text-muted mb-1">ស្វែងរកអ្នកជំងឺ</label>
                                <input type="text" name="search" class="form-control form-control-sm bg-light border-0"
                                    placeholder="ស្វែងរកតាមឈ្មោះ ឬ លេខសម្គាល់..." value="{{ request('search') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted mb-1">ជម្រើសតាមកាលបរិច្ឆេទ</label>
                                <input type="date" name="date" class="form-control form-control-sm bg-light border-0"
                                    value="{{ request('date') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted mb-1">ជម្រើសតាមភេទ</label>
                                <select name="gender" class="form-control form-control-sm bg-light border-0">
                                    <option value="">ទាំងអស់</option>
                                    <option value="Male" {{ request('gender') == 'Male' ? 'selected' : '' }}>ប្រុស</option>
                                    <option value="Female" {{ request('gender') == 'Female' ? 'selected' : '' }}>ស្រី</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-sm btn-success btn-block font-weight-bold">
                                    <i class="fas fa-filter mr-1"></i> Filter
                                </button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle border-0">
                                <thead class="bg-light">
                                    <tr class="text-secondary small">
                                        <th>លេខសម្គាល់</th>
                                        <th>ឈ្មោះពេញ</th>
                                        <th>ភេទ</th>
                                        <th>អាយុ / ថ្ងៃកំណើត</th>
                                        <th>លេខទូរស័ព្ទ</th>
                                        <th>ថ្ងៃចុះឈ្មោះ</th>
                                        <th>សកម្មភាព</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($patients as $patient)
                                        <tr>
                                            <td class="font-weight-bold text-dark">{{ $patient->patient_code }}</td>
                                            <td class="font-weight-bold">{{ $patient->full_name }}</td>
                                            <td>{{ $patient->sex == 'Male' ? 'ប្រុស' : 'ស្រី' }}</td>
                                            <td>{{ $patient->date_of_birth }}</td>
                                            <td>{{ $patient->phone ?? '-' }}</td>
                                            <td>{{ $patient->created_at ? $patient->created_at->format('d/m/Y') : '-' }}</td>
                                            <td>
                                                <div class="d-flex align-items-center" style="gap: 5px;">
                                                    <a href="{{ route('patients.show', $patient->patient_id) }}" class="btn btn-sm btn-light text-info rounded-circle" title="មើលព័ត៌មាន">
                                                        <i class="fas fa-eye"></i>
                                                    </a>

                                                    <a href="{{ route('patients.print', $patient->patient_id) }}" target="_blank" class="btn btn-sm btn-light text-success rounded-circle" title="បោះពុម្ព">
                                                        <i class="fas fa-print"></i>
                                                    </a>

                                                    <a href="{{ route('patients.edit', $patient->patient_id) }}" class="btn btn-sm btn-light text-primary rounded-circle" title="កែប្រែ">
                                                        <i class="fas fa-edit"></i>
                                                    </a>

                                                    <form action="{{ route('patients.destroy', $patient->patient_id) }}" method="POST" onsubmit="return confirm('តើអ្នកពិតជាចង់លុបទិន្នន័យអ្នកជំងឺនេះមែនទេ?');" style="display:inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-light text-danger rounded-circle" title="លុប">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                មិនទាន់មានទិន្នន័យអ្នកជំងឺឡើយ</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-end mt-2">
                            {{ $patients->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3">
                <div class="card border-0 shadow-sm rounded-lg">
                    <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
                        <h6 class="font-weight-bold mb-0 small"><i class="fas fa-clock mr-1"></i> ជួររង់ចាំផ្ទាល់</h6>
                        <span class="badge badge-light text-success font-weight-bold">{{ $waitingPatients->count() }} នាក់</span>
                    </div>
                    <div class="card-body p-2" style="max-height: 480px; overflow-y: auto;">
                        @forelse($waitingPatients as $record)
                            <div class="card border mb-2 shadow-sm rounded-lg bg-light">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="badge badge-success px-2">{{ $record->patient->patient_code ?? 'A-001' }}</span>
                                        <small class="text-muted"><i class="far fa-clock mr-1"></i>{{ $record->created_at ? $record->created_at->diffForHumans() : '' }}</small>
                                    </div>
                                    <strong class="text-dark d-block mb-1">{{ $record->patient->full_name ?? 'N/A' }}</strong>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted text-center my-3 small">គ្មានអ្នកជំងឺរង់ចាំឡើយ</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

    </div>
@stop
