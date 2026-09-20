@extends('adminlte::page')

@section('title', 'កែប្រែព័ត៌មានអ្នកជំងឺ')

@section('content_header')
<h1>កែប្រែព័ត៌មានអ្នកជំងឺ</h1>
@stop

@section('content')
@php
    $sex = strtolower(old('sex', $patient->sex));
    $dobText = old('dob_display', $patient->date_of_birth
        ? \Carbon\Carbon::parse($patient->date_of_birth)->format('d/m/Y')
        : '');
@endphp

<div class="card">
    <div class="card-body">

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 pl-3">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('patients.update', $patient->patient_id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label>ឈ្មោះអ្នកជំងឺ <span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control"
                    value="{{ old('full_name', $patient->full_name) }}" required>
            </div>

            <div class="form-group">
                <label>អត្តសញ្ញាណប័ណ្ណ (ID Card) <span class="text-danger">*</span></label>
                <input type="text" name="id_card" class="form-control" value="{{ old('id_card', $patient->id_card) }}"
                    required>
            </div>

            <div class="form-group">
                <label>ថ្ងៃខែឆ្នាំកំណើត <span class="text-danger">*</span></label>
                <input type="text" id="dob_display" name="dob_display" class="form-control"
                    placeholder="ថ្ងៃ/ខែ/ឆ្នាំ  (ឧ. 15/03/1990)" inputmode="numeric" maxlength="10" autocomplete="off"
                    required value="{{ $dobText }}">
                <input type="hidden" name="date_of_birth" id="dob_value">
                <small id="dob_hint" class="form-text"></small>
            </div>

            <div class="form-group">
                <label>ភេទ <span class="text-danger">*</span></label>
                <select name="sex" class="form-control" required>
                    <option value="male" {{ $sex === 'male' ? 'selected' : '' }}>ប្រុស</option>
                    <option value="female" {{ $sex === 'female' ? 'selected' : '' }}>ស្រី</option>
                    <option value="other" {{ $sex === 'other' ? 'selected' : '' }}>ផ្សេងៗ</option>
                </select>
            </div>

            <div class="form-group">
                <label>លេខទូរសព្ទ <span class="text-danger">*</span></label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $patient->phone) }}"
                    required>
            </div>

            <div class="form-group">
                <label>អាសយដ្ឋាន <span class="text-danger">*</span></label>
                <textarea name="address" class="form-control"
                    required>{{ old('address', $patient->address) }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary">រក្សាទុកការកែប្រែ</button>
            <a href="{{ route('patients.index') }}" class="btn btn-secondary">បោះបង់</a>
        </form>
    </div>
</div>
@stop

@section('js')
<script>
    (function () {
        const display = document.getElementById('dob_display');
        const value = document.getElementById('dob_value');
        const hint = document.getElementById('dob_hint');
        const pad = n => String(n).padStart(2, '0');

        function check(text) {
            value.value = '';
            display.setCustomValidity('');
            hint.textContent = '';
            hint.className = 'form-text';

            if (text.length === 0) return;

            if (text.length < 10) {
                display.setCustomValidity('សូមបញ្ចូលជាទម្រង់ ថ្ងៃ/ខែ/ឆ្នាំ');
                return;
            }

            const [dd, mm, yyyy] = text.split('/').map(Number);
            const d = new Date(yyyy, mm - 1, dd);
            const today = new Date();
            const valid = yyyy >= 1900 &&
                d.getFullYear() === yyyy && d.getMonth() === mm - 1 && d.getDate() === dd &&
                d <= today;

            if (!valid) {
                display.setCustomValidity('ថ្ងៃខែឆ្នាំកំណើតមិនត្រឹមត្រូវ');
                hint.textContent = 'ថ្ងៃខែឆ្នាំមិនត្រឹមត្រូវ';
                hint.className = 'form-text text-danger';
                return;
            }

            value.value = yyyy + '-' + pad(mm) + '-' + pad(dd);

            let age = today.getFullYear() - yyyy;
            if (today < new Date(today.getFullYear(), mm - 1, dd)) age--;
            hint.textContent = 'អាយុ ' + age + ' ឆ្នាំ';
            hint.className = 'form-text text-success';
        }

        display.addEventListener('input', function () {
            const d = this.value.replace(/\D/g, '').slice(0, 8);
            let out = d;
            if (d.length > 4) out = d.slice(0, 2) + '/' + d.slice(2, 4) + '/' + d.slice(4);
            else if (d.length > 2) out = d.slice(0, 2) + '/' + d.slice(2);
            this.value = out;
            check(out);
        });

        // Fill the hidden field for the date that is already saved
        check(display.value);
    })();
</script>
@stop