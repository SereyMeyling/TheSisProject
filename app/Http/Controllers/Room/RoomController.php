<?php

namespace App\Http\Controllers\Room;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa', 'role:admin|doctor|nurse']);
    }

    /**
     * Display a listing of rooms with search, filters, and summary stats.
     */
    public function index(Request $request)
    {
        $rooms = $this->getFilteredRooms($request);
        $totalRooms = Room::count();
        $availableRooms = Room::where('status', 'available')->count();
        $occupiedRooms = Room::where('status', 'occupied')->count();
        $maintenanceRooms = Room::where('status', 'maintenance')->count();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html'        => view('form.room.partials.table', compact('rooms'))->render(),
                'total'       => $totalRooms,
                'available'   => $availableRooms,
                'occupied'    => $occupiedRooms,
                'maintenance' => $maintenanceRooms,
            ]);
        }

        return view('form.room.room', compact(
            'rooms',
            'totalRooms',
            'availableRooms',
            'occupiedRooms',
            'maintenanceRooms'
        ));
    }

    /**
     * Filter query for rooms.
     */
    protected function getFilteredRooms(Request $request)
    {
        $query = Room::query();

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('room_number', 'LIKE', $searchTerm)
                    ->orWhere('room_type', 'LIKE', $searchTerm)
                    ->orWhere('price_per_day', 'LIKE', $searchTerm);
            });
        }

        if ($request->filled('room_type')) {
            $query->where('room_type', $request->room_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $query->orderBy('room_id', 'asc');

        return $query->paginate(10)->appends($request->query());
    }

    /**
     * Store a newly created room in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'room_number'   => 'required|string|max:20|unique:rooms,room_number',
            'room_type'     => 'required|in:general,private,icu,isolation',
            'status'        => 'required|in:available,occupied,maintenance',
            'price_per_day' => 'required|numeric|min:0',
        ]);

        Room::create([
            'room_number'   => $request->room_number,
            'room_type'     => $request->room_type,
            'status'        => $request->status,
            'price_per_day' => $request->price_per_day,
        ]);

        return redirect()->back()->with(['success' => 'បន្ទប់ត្រូវបានបង្កើតដោយជោគជ័យ (Room created successfully)']);
    }

    /**
     * Show the form for editing the specified room (returns JSON).
     */
    public function edit($id)
    {
        $room = Room::find($id);
        if (!$room) {
            return response()->json(['error' => 'រកមិនឃើញបន្ទប់ទេ'], 404);
        }

        return response()->json($room);
    }

    /**
     * Update the specified room in storage.
     */
    public function update(Request $request, $id)
    {
        $room = Room::find($id);
        if (!$room) {
            return redirect()->back()->with(['error' => 'រកមិនឃើញបន្ទប់ទេ']);
        }

        $request->validate([
            'room_number'   => 'required|string|max:20|unique:rooms,room_number,' . $id . ',room_id',
            'room_type'     => 'required|in:general,private,icu,isolation',
            'status'        => 'required|in:available,occupied,maintenance',
            'price_per_day' => 'required|numeric|min:0',
        ]);

        $room->update([
            'room_number'   => $request->room_number,
            'room_type'     => $request->room_type,
            'status'        => $request->status,
            'price_per_day' => $request->price_per_day,
        ]);

        return redirect()->back()->with(['success' => 'ព័ត៌មានបន្ទប់ត្រូវបានកែប្រែដោយជោគជ័យ (Room updated successfully)']);
    }

    /**
     * Remove the specified room from storage.
     */
    public function destroy($id)
    {
        $room = Room::find($id);
        if (!$room) {
            return redirect()->back()->with(['error' => 'រកមិនឃើញបន្ទប់ទេ']);
        }

        $room->delete();

        return redirect()->back()->with(['success' => 'បន្ទប់ត្រូវបានលុបដោយជោគជ័យ (Room deleted successfully)']);
    }
}
