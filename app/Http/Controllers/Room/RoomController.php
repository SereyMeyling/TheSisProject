<?php

namespace App\Http\Controllers\Room;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa', 'role:admin|doctor|nurse']);
    }
    public function index(Request $request)
    {
        $query = Room::query();

        if ($search = $request->input('search')) {
            $query->where('room_number', 'like', "%{$search}%");
        }
        if ($type = $request->input('room_type')) {
            $query->where('room_type', $type);
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $rooms = $query->orderBy('room_number')->paginate(10)->withQueryString();

        $stats = [
            'total' => Room::count(),
            'available' => Room::where('status', 'available')->count(),
            'occupied' => Room::where('status', 'occupied')->count(),
            'maintenance' => Room::where('status', 'maintenance')->count(),
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('form.room.partials.table', compact('rooms'))->render(),
                'stats' => $stats,
            ]);
        }

        return view('form.room.index', compact('rooms', 'stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_number' => 'required|string|max:20|unique:rooms,room_number',
            'room_type' => ['required', Rule::in(['general', 'private', 'icu', 'isolation'])],
            'status' => ['required', Rule::in(['available', 'occupied', 'maintenance'])],
            'price_per_day' => 'required|numeric|min:0',
        ]);

        Room::create($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'បន្ទប់ត្រូវបានបង្កើតដោយជោគជ័យ']);
        }
        return redirect()->route('room.index')->with('success', 'បន្ទប់ត្រូវបានបង្កើតដោយជោគជ័យ');
    }
    public function edit($id)
    {
        $room = Room::findOrFail($id);
        return response()->json($room);
    }
    public function update(Request $request, $id)
    {
        try {
            $room = Room::findOrFail($id);

            $validated = $request->validate([
                'room_number' => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('rooms', 'room_number')
                        ->ignore($room->room_id, 'room_id'),
                ],

                'room_type' => [
                    'required',
                    Rule::in([
                        'general',
                        'private',
                        'icu',
                        'isolation',
                    ]),
                ],

                'status' => [
                    'required',
                    Rule::in([
                        'available',
                        'occupied',
                        'maintenance',
                    ]),
                ],

                'price_per_day' => [
                    'required',
                    'numeric',
                    'min:0',
                ],
            ]);

            \Log::info('Room update attempt', [
                'room_id' => $room->room_id,
                'old_room_type' => $room->room_type,
                'new_room_type' => $validated['room_type'],
                'data' => $validated,
            ]);

            $room->update($validated);

            \Log::info('Room updated successfully', [
                'room_id' => $room->room_id,
                'room_type' => $room->room_type,
            ]);

            return redirect()
                ->route('room.index')
                ->with(
                    'success',
                    'ព័ត៌មានបន្ទប់ត្រូវបានកែប្រែដោយជោគជ័យ'
                );

        } catch (\Throwable $e) {

            \Log::error('Room update failed', [
                'room_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Room update failed: ' . $e->getMessage()
                );
        }
    }
    public function destroy($id)
    {
        $room = Room::findOrFail($id);
        $room->delete();
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'បន្ទប់ត្រូវបានលុបដោយជោគជ័យ',
            ]);
        }
        return redirect()
            ->route('room.index')
            ->with(
                'success',
                'បន្ទប់ត្រូវបានលុបដោយជោគជ័យ'
            );
    }

}
