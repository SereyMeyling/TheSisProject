<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\AppointmentNotification;
use App\Helpers\NotifiesRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa', 'role:admin|doctor|nurse']);
    }

    /**
     * Length of one appointment slot in minutes. Two scheduled appointments of the same
     * doctor closer than this are a double-booking.
     */
    public const SLOT_MINUTES = 30;

    protected const CONFLICT_MESSAGE = 'វេជ្ជបណ្ឌិតរូបនេះមានការណាត់ជួបផ្សេងទៀតក្នុងចន្លោះពេលនេះរួចហើយ សូមជ្រើសរើសម៉ោងផ្សេង។ (The doctor already has an appointment within this time slot.)';

    /**
     * Check if the doctor has another scheduled appointment within ±SLOT_MINUTES.
     * Must be called inside a transaction: it locks the doctor's row and that day's
     * appointments so two requests can't book the same slot at the same time.
     */
    protected function hasConflict($userId, $appointmentDate, $excludeId = null): bool
    {
        $date = Carbon::parse($appointmentDate);
        $windowStart = $date->copy()->subMinutes(self::SLOT_MINUTES);
        $windowEnd = $date->copy()->addMinutes(self::SLOT_MINUTES);

        // Serialize bookings per doctor (works even when the day has no rows yet).
        User::whereKey($userId)->lockForUpdate()->first();

        Appointment::where('user_id', $userId)
            ->whereBetween('appointment_date', [
                $windowStart->copy()->startOfDay(),
                $windowEnd->copy()->endOfDay(),
            ])
            ->lockForUpdate()
            ->get(['appointment_id']);

        return Appointment::where('user_id', $userId)
            ->where('status', 'scheduled')
            ->where('appointment_date', '>', $windowStart)
            ->where('appointment_date', '<', $windowEnd)
            ->when($excludeId, function ($query) use ($excludeId) {
                $query->where('appointment_id', '!=', $excludeId);
            })
            ->exists();
    }

    /**
     * Validation rule: user_id must belong to a user with the doctor role.
     */
    protected function doctorRule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if (!User::role('doctor')->whereKey($value)->exists()) {
                $fail('សូមជ្រើសរើសវេជ្ជបណ្ឌិតដែលត្រឹមត្រូវ។ (Please select a valid doctor.)');
            }
        };
    }

    /**
     * Admin and nurse manage all appointments; a doctor only their own.
     */
    protected function canManage(Appointment $appointment): bool
    {
        $user = auth()->user();
        if ($user->hasAnyRole(['admin', 'nurse'])) {
            return true;
        }
        return (int) $appointment->user_id === (int) $user->id;
    }
    /**
     * Display appointments with search, filters and statistics.
     */
    public function index(Request $request)
    {
        $appointments = $this->getFilteredAppointments($request);
        $totalAppointments = Appointment::count();
        $scheduledCount = Appointment::where('status', 'scheduled')->count();
        $completedCount = Appointment::where('status', 'completed')->count();
        $cancelledCount = Appointment::where('status', 'cancelled')->count();
        $overdueCount = Appointment::where('status', 'scheduled')
            ->where('appointment_date', '<', now())
            ->count();
        $patients = Patient::orderBy('full_name', 'asc')->get();
        // Spatie roles
        $doctors = User::role('doctor')
            ->orderBy('name', 'asc')
            ->get();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view(
                    'form.appointment.partials.table',
                    compact('appointments')
                )->render(),

                'total' => $totalAppointments,
                'scheduled' => $scheduledCount,
                'completed' => $completedCount,
                'cancelled' => $cancelledCount,
                'overdue' => $overdueCount,
            ]);
        }
        return view('form.appointment.appointment', [
            'appointments' => $appointments,
            'totalAppointments' => $totalAppointments,
            'scheduledCount' => $scheduledCount,
            'completedCount' => $completedCount,
            'cancelledCount' => $cancelledCount,
            'overdueCount' => $overdueCount,
            'patients' => $patients,
            'doctors' => $doctors,
            'isDoctorOnly' => $this->isDoctorOnly(),
        ]);
    }

    /**
     * Filter appointments.
     */
    protected function getFilteredAppointments(Request $request)
    {
        $query = Appointment::with([
            'patient',
            'doctor',
        ]);

        if ($request->filled('search')) {

            $searchTerm = '%' . trim($request->search) . '%';

            $query->where(function ($q) use ($searchTerm) {

                // Patient search
                $q->whereHas('patient', function ($pq) use ($searchTerm) {

                    $pq->where('full_name', 'LIKE', $searchTerm)
                        ->orWhere('patient_code', 'LIKE', $searchTerm)
                        ->orWhere('phone', 'LIKE', $searchTerm);

                })

                    // Doctor/User search
                    ->orWhereHas('doctor', function ($dq) use ($searchTerm) {

                        $dq->where('name', 'LIKE', $searchTerm)
                            ->orWhere('specialization', 'LIKE', $searchTerm)
                            ->orWhere('phone', 'LIKE', $searchTerm);

                    })

                    // Reason search
                    ->orWhere('reason', 'LIKE', $searchTerm);
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'overdue') {
                $query->where('status', 'scheduled')
                    ->where('appointment_date', '<', now());
            } else {
                $query->where('status', $request->status);
            }
        }
        if ($request->filled('date')) {
            $query->whereDate(
                'appointment_date',
                $request->date
            );
        }

        return $query
            ->orderBy('appointment_date', 'desc')
            ->paginate(10)
            ->appends($request->query());
    }

    /**
     * Store appointment.
     */
    public function store(Request $request)
    {
        if ($this->isDoctorOnly()) {
            $request->merge(['user_id' => auth()->id()]);
        }
        $validator = Validator::make($request->all(), [
            'patient_id' => ['required', 'exists:patients,patient_id'],
            'user_id' => ['required', 'integer', $this->doctorRule()],
            'appointment_date' => ['required', 'date', 'after_or_equal:now'],
            'status' => ['required', 'in:scheduled,completed,cancelled'],
            'reason' => ['nullable', 'string', 'max:255'],
        ], [
            'appointment_date.after_or_equal' => 'មិនអាចជ្រើសរើសកាលបរិច្ឆេទ ឬម៉ោងក្នុងអតីតកាលបានទេ សូមជ្រើសរើសពេលវេលាថ្ងៃនេះ ឬថ្ងៃខាងមុខ។',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->with('error', $validator->errors()->first());
        }

        $validated = $validator->validated();

        try {
            $appointment = DB::transaction(function () use ($validated) {
                if ($validated['status'] === 'scheduled'
                    && $this->hasConflict($validated['user_id'], $validated['appointment_date'])) {
                    return null;
                }
                return Appointment::create($validated);
            });
        } catch (\Throwable $e) {
            Log::error('Appointment create failed', ['error' => $e->getMessage()]);
            return redirect()->back()->withInput()->with(
                'error',
                'មិនអាចបង្កើតការណាត់ជួបបានទេ សូមព្យាយាមម្តងទៀត។ (Failed to create the appointment.)'
            );
        }

        if (!$appointment) {
            return redirect()->back()->withInput()->with('error', self::CONFLICT_MESSAGE);
        }

        // Notify the assigned doctor AND everyone with the admin role


        return redirect()->back()->with('success', 'ការណាត់ជួបត្រូវបានបង្កើតដោយជោគជ័យ');
    }

    /**
     * Show appointment details.
     */
    public function show($id)
    {
        $appointment = Appointment::with([
            'patient',
            'doctor',
        ])->find($id);

        if (!$appointment) {
            return redirect()
                ->route('appointment.index')
                ->with(
                    'error',
                    'រកមិនឃើញការណាត់ជួបទេ'
                );
        }

        return view(
            'form.appointment.show',
            compact('appointment')
        );
    }

    /**
     * Edit appointment.
     */
    public function edit($id)
    {
        $appointment = Appointment::with([
            'patient',
            'doctor',
        ])->find($id);

        if (!$appointment) {
            return response()->json([
                'error' => 'រកមិនឃើញការណាត់ជួបទេ',
            ], 404);
        }
        if (!$this->canManage($appointment)) {
            return response()->json([
                'error' => 'អ្នកអាចកែប្រែបានតែការណាត់ជួបរបស់អ្នកប៉ុណ្ណោះ។ (You can only edit your own appointments.)',
            ], 403);
        }

        return response()->json([
            'appointment_id' => $appointment->appointment_id,
            'patient_id' => $appointment->patient_id,
            'user_id' => $appointment->user_id,
            'doctor_name' => $appointment->doctor->name ?? '',
            'appointment_date' => $appointment->appointment_date
                ? $appointment->appointment_date->format('Y-m-d\TH:i')
                : '',
            'status' => $appointment->status,
            'reason' => $appointment->reason,
        ]);
    }

    /**
     * Update appointment.
     */
    public function update(Request $request, $id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return redirect()->back()->with('error', 'រកមិនឃើញការណាត់ជួបទេ');
        }
        if (!$this->canManage($appointment)) {
            return redirect()->back()->with(
                'error',
                'អ្នកអាចកែប្រែបានតែការណាត់ជួបរបស់អ្នកប៉ុណ្ណោះ។ (You can only edit your own appointments.)'
            );
        }
        if ($this->isDoctorOnly()) {
            // a doctor cannot hand the appointment over to another doctor
            $request->merge(['user_id' => $appointment->user_id]);
        }

        $validator = Validator::make($request->all(), [
            'patient_id' => ['required', 'exists:patients,patient_id'],
            'user_id' => ['required', 'integer', $this->doctorRule()],
            'appointment_date' => ['required', 'date'],
            'status' => ['required', 'in:scheduled,completed,cancelled'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->with('error', $validator->errors()->first());
        }

        $validated = $validator->validated();

        try {
            $saved = DB::transaction(function () use ($appointment, $validated) {
                if ($validated['status'] === 'scheduled'
                    && $this->hasConflict($validated['user_id'], $validated['appointment_date'], $appointment->appointment_id)) {
                    return false;
                }

                $dateChanged = !$appointment->appointment_date
                    || !$appointment->appointment_date->equalTo(Carbon::parse($validated['appointment_date']));

                $appointment->update($validated);

                if ($dateChanged) {
                    $appointment->forceFill([
                        'reminder_sent_at' => null,
                        'reminder_called_at' => null,
                        'reminder_called_by' => null,
                    ])->save();
                }
                return true;
            });
        } catch (\Throwable $e) {
            Log::error('Appointment update failed', ['appointment_id' => $id, 'error' => $e->getMessage()]);
            return redirect()->back()->withInput()->with(
                'error',
                'មិនអាចកែប្រែការណាត់ជួបបានទេ សូមព្យាយាមម្តងទៀត។ (Failed to update the appointment.)'
            );
        }

        if (!$saved) {
            return redirect()->back()->withInput()->with('error', self::CONFLICT_MESSAGE);
        }

        // Notify the assigned doctor AND everyone with the admin role

        return redirect()->back()->with('success', 'ការណាត់ជួបត្រូវបានកែប្រែដោយជោគជ័យ');
    }

    /**
     * Delete appointment.
     */
    public function destroy($id)
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return redirect()
                ->back()
                ->with(
                    'error',
                    'រកមិនឃើញការណាត់ជួបទេ'
                );
        }
        if (!$this->canManage($appointment)) {
            return redirect()->back()->with(
                'error',
                'អ្នកអាចលុបបានតែការណាត់ជួបរបស់អ្នកប៉ុណ្ណោះ។ (You can only delete your own appointments.)'
            );
        }

        $appointment->delete();

        return redirect()
            ->back()
            ->with(
                'success',
                'ការណាត់ជួបត្រូវបានលុបដោយជោគជ័យ'
            );
    }

    protected function isDoctorOnly(): bool
    {
        $user = auth()->user();
        return $user->hasRole('doctor') && !$user->hasRole('admin');
    }
}
