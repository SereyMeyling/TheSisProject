<?php

namespace App\Http\Controllers\Room;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa', 'role:admin|doctor|nurse']);
    }

    public function index(Request $request)
    {
        $query = Room::query()->with('activeAdmission.patient');

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
        $room = Room::findOrFail($id);

        // Validation errors are not caught, so the form shows field errors (L5).
        $validated = $request->validate([
            'room_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('rooms', 'room_number')->ignore($room->room_id, 'room_id'),
            ],
            'room_type' => ['required', Rule::in(['general', 'private', 'icu', 'isolation'])],
            'status' => ['required', Rule::in(['available', 'occupied', 'maintenance'])],
            'price_per_day' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $blocked = DB::transaction(function () use ($room, $validated) {
                $locked = Room::where('room_id', $room->room_id)->lockForUpdate()->firstOrFail();

                // A room with a patient in it must stay "occupied" until the patient is discharged (H5).
                if ($validated['status'] !== 'occupied' && $locked->hasActiveAdmission()) {
                    return true;
                }

                $locked->update($validated);
                return false;
            });
        } catch (\Throwable $e) {
            Log::error('Room update failed', [
                'room_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return $this->respond(
                $request,
                false,
                'មិនអាចកែប្រែបន្ទប់បានទេ សូមព្យាយាមម្តងទៀត។ (Failed to update the room.)',
                500
            );
        }

        if ($blocked) {
            return $this->respond(
                $request,
                false,
                'បន្ទប់នេះមានអ្នកជំងឺកំពុងសម្រាក សូមចេញពីមន្ទីរពេទ្យ (Discharge) ជាមុនសិន។ (This room has an active admission; discharge the patient first.)',
                422
            );
        }

        return $this->respond($request, true, 'ព័ត៌មានបន្ទប់ត្រូវបានកែប្រែដោយជោគជ័យ');
    }

    public function destroy($id)
    {
        $room = Room::findOrFail($id);

        // Rooms used by any admission are referenced by FK and keep billing history (M12).
        if ($room->admissions()->exists()) {
            return $this->respond(
                request(),
                false,
                'មិនអាចលុបបន្ទប់នេះបានទេ ព្រោះមានប្រវត្តិសម្រាកព្យាបាល។ (This room has admission history and cannot be deleted.)',
                422
            );
        }

        try {
            $room->delete();
        } catch (\Throwable $e) {
            Log::error('Room delete failed', ['room_id' => $id, 'error' => $e->getMessage()]);
            return $this->respond(
                request(),
                false,
                'មិនអាចលុបបន្ទប់នេះបានទេ ព្រោះកំពុងត្រូវបានប្រើប្រាស់។ (This room is in use and cannot be deleted.)',
                422
            );
        }

        return $this->respond(request(), true, 'បន្ទប់ត្រូវបានលុបដោយជោគជ័យ');
    }

    /**
     * Discharge an admitted patient and release the room (H5).
     */
    public function discharge(Request $request, $id)
    {
        try {
            $result = DB::transaction(function () use ($id) {
                $admission = Admission::where('admission_id', $id)->lockForUpdate()->first();

                if (!$admission) {
                    return 'not_found';
                }
                if ($admission->status !== 'admitted') {
                    return 'not_admitted';
                }

                $room = Room::where('room_id', $admission->room_id)->lockForUpdate()->first();

                $admission->status = 'discharged';
                $admission->discharge_date = now();
                $admission->save();

                if ($room) {
                    $stillInUse = Admission::where('room_id', $room->room_id)
                        ->where('status', 'admitted')
                        ->where('admission_id', '!=', $admission->admission_id)
                        ->exists();

                    // Release the room only if nobody else is in it; leave "maintenance" untouched.
                    if (!$stillInUse && $room->status === 'occupied') {
                        $room->update(['status' => 'available']);
                    }
                }

                return 'ok';
            });
        } catch (\Throwable $e) {
            Log::error('Admission discharge failed', ['admission_id' => $id, 'error' => $e->getMessage()]);
            return $this->respond(
                $request,
                false,
                'មិនអាចចេញពីមន្ទីរពេទ្យបានទេ សូមព្យាយាមម្តងទៀត។ (Failed to discharge the patient.)',
                500
            );
        }

        if ($result === 'not_found') {
            return $this->respond($request, false, 'រកមិនឃើញការសម្រាកព្យាបាលនេះទេ។ (Admission not found.)', 404);
        }
        if ($result === 'not_admitted') {
            return $this->respond(
                $request,
                false,
                'ការសម្រាកព្យាបាលនេះមិនសកម្មទេ។ (This admission is not active.)',
                422
            );
        }

        return $this->respond(
            $request,
            true,
            'អ្នកជំងឺត្រូវបានចេញពីមន្ទីរពេទ្យ ហើយបន្ទប់ត្រូវបានដោះលែង។ (Patient discharged and room released.)'
        );
    }

    /**
     * JSON for AJAX calls, redirect with a flash message otherwise.
     */
    protected function respond(Request $request, bool $success, string $message, int $status = 200)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => $success, 'message' => $message], $success ? 200 : $status);
        }

        return redirect()->route('room.index')->with($success ? 'success' : 'error', $message);
    }
}
