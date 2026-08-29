<table class="table align-middle mb-0">
    <thead class="bg-light">
        <tr>
            <th>កូដតេស្ត</th>
            <th>ឈ្មោះតេស្តពិសោធន៍</th>
            <th>កម្រិតធម្មតា (Normal Range)</th>
            <th>ខ្នាត (Unit)</th>
            <th>តម្លៃ ($)</th>
            <th class="text-right">សកម្មភាព</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($labTests as $test)
            <tr>
                <td class="font-weight-bold text-dark">{{ $test->test_code ?? 'T-' . $test->test_id }}</td>
                <td>
                    <div class="font-weight-bold text-primary">{{ $test->test_name }}</div>
                </td>
                <td>
                    <span class="badge badge-light border text-dark">{{ $test->normal_range ?? '—' }}</span>
                </td>
                <td>
                    <span class="text-muted">{{ $test->unit ?? '—' }}</span>
                </td>
                <td class="font-weight-bold text-success">${{ number_format($test->price, 2) }}</td>
                <td>
                    <div class="d-flex justify-content-end align-items-center" style="gap: 4px;">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-warning btn-edit-test"
                            data-id="{{ $test->test_id }}"
                            data-name="{{ $test->test_name }}"
                            data-code="{{ $test->test_code }}"
                            data-range="{{ $test->normal_range }}"
                            data-unit="{{ $test->unit }}"
                            data-price="{{ $test->price }}"
                            style="border-radius: 8px;"
                        >
                            <i class="fas fa-edit"></i>
                        </button>
                        <form action="{{ route('lab.tests.destroy', $test->test_id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('តើអ្នកពិតជាចង់លុបតេស្តនេះមែនទេ?');" style="border-radius: 8px;">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center py-5 text-muted">
                    <i class="fas fa-microscope fa-3x mb-3 text-muted opacity-50"></i>
                    <p class="font-weight-bold mb-1">មិនទាន់មានបញ្ជីតេស្តពិសោធន៍ទេ</p>
                    <small>សូមបន្ថែមតេស្តពិសោធន៍ថ្មីក្នុងកាតាឡុក</small>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
