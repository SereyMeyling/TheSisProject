<?php

namespace App\Http\Controllers;

use App\Models\Pharmacy\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $supplier = Supplier::create($validator->validated());

        return response()->json([
            'message' => 'បន្ថែមអ្នកផ្គត់ផ្គង់ជោគជ័យ',
            'supplier' => $supplier,
        ]);
    }
}
