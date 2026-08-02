@extends('adminlte::page')

@section('title', 'កែប្រែព័ត៌មានអ្នកជំងឺ')

@section('content_header')
    <h1>កែប្រែព័ត៌មានអ្នកជំងឺ</h1>
@stop

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('patients.update', $patient->patient_id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label>ឈ្មោះអ្នកជំងឺ</label>
                <input type="text" name="full_name" class="form-control" value="{{ $patient->full_name }}" required>
            </div>

            <div class="form-group">
                <label>អត្តសញ្ញាណប័ណ្ណ (ID Card)</label>
                <input type="text" name="id_card" class="form-control" value="{{ $patient->id_card }}">
            </div>

            <div class="form-group">
                <label>ថ្ងៃខែឆ្នាំកំណើត</label>
                <input type="date" name="date_of_birth" class="form-control" value="{{ $patient->date_of_birth }}" required>
            </div>

            <div class="form-group">
                <label>ភេទ</label>
                <select name="sex" class="form-control" required>
                    <option value="Male" {{ $patient->sex == 'Male' ? 'selected' : '' }}>ប្រុស</option>
                    <option value="Female" {{ $patient->sex == 'Female' ? 'selected' : '' }}>ស្រី</option>
                </select>
            </div>

            <div class="form-group">
                <label>លេខទូរសព្ទ</label>
                <input type="text" name="phone" class="form-control" value="{{ $patient->phone }}">
            </div>

            <div class="form-group">
                <label>អាសយដ្ឋាន</label>
                <textarea name="address" class="form-control">{{ $patient->address }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary">រក្សាទុកការកែប្រែ</button>
            <a href="{{ route('patients.index') }}" class="btn btn-secondary">បោះបង់</a>
        </form>
    </div>
</div>
@stop
