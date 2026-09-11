<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Employee;
use App\Models\Patient;
use App\Notifications\AppointmentNotification;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa', 'role:admin|doctor|nurse']);
    }

    /**
     * Display a listing of appointments with search, filters, and summary stats.
     */
    public function index(Request $request)
    {
        $appointments = $this->getFilteredAppointments($request);
        $totalAppointments = Appointment::count();
        $scheduledCount = Appointment::where('status', 'scheduled')->count();
        $completedCount = Appointment::where('status', 'completed')->count();
        $cancelledCount = Appointment::where('status', 'cancelled')->count();

        $patients = Patient::orderBy('full_name', 'asc')->get();
        $doctors = Employee::where('role', 'doctor')->orderBy('first_name', 'asc')->get();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('form.appointment.partials.table', compact('appointments'))->render(),
                'total' => $totalAppointments,
                'scheduled' => $scheduledCount,
                'completed' => $completedCount,
                'cancelled' => $cancelledCount,
            ]);
        }

        return view('form.appointment.appointment', compact(
            'appointments',
            'totalAppointments',
            'scheduledCount',
            'completedCount',
            'cancelledCount',
            'patients',
            'doctors'
        ));
    }

    /**
     * Filter query for appointments.
     */
    protected function getFilteredAppointments(Request $request)
    {
        $query = Appointment::with(['patient', 'doctor']);

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('patient', function ($pq) use ($searchTerm) {
                    $pq->where('full_name', 'LIKE', $searchTerm)
                        ->orWhere('patient_code', 'LIKE', $searchTerm)
                        ->orWhere('phone', 'LIKE', $searchTerm);
                })
                    ->orWhereHas('doctor', function ($dq) use ($searchTerm) {
                        $dq->where('first_name', 'LIKE', $searchTerm)
                            ->orWhere('last_name', 'LIKE', $searchTerm)
                            ->orWhere('specialization', 'LIKE', $searchTerm);
                    })
                    ->orWhere('reason', 'LIKE', $searchTerm);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('appointment_date', $request->date);
        }

        $query->orderBy('appointment_date', 'desc');

        return $query->paginate(10)->appends($request->query());
    }

    /**
     * Store a newly created appointment in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'employee_id' => 'required|exists:employees,employee_id',
            'appointment_date' => 'required|date',
            'status' => 'required|in:scheduled,completed,cancelled',
            'reason' => 'nullable|string|max:255',
        ]);

        // Create appointment
        $appointment = Appointment::create([
            'patient_id' => $request->patient_id,
            'employee_id' => $request->employee_id,
            'appointment_date' => $request->appointment_date,
            'status' => $request->status,
            'reason' => $request->reason,
        ]);

        // Find the doctor/employee and related user
        $doctor = Employee::with('user')
            ->where('employee_id', $request->employee_id)
            ->first();

        // Send notification to the doctor's user account
        if ($doctor && $doctor->user) {
            $doctor->user->notify(
                new AppointmentNotification($appointment, 'created')
            );
        }

        return redirect()
            ->back()
            ->with([
                'success' => 'ការណាត់ជួបត្រូវបានបង្កើតដោយជោគជ័យ'
            ]);
    }

    public function show($id)
    {
        $appointment = Appointment::with([
            'patient',
            'doctor',
        ])->find($id);

        if (!$appointment) {
            return redirect()
                ->route('appointments.index')
                ->with('error', 'រកមិនឃើញការណាត់ជួបទេ');
        }

        return view('form.appointment.show', compact('appointment'));
    }

    /**
     * Show the form for editing the specified appointment (returns JSON).
     */
    public function edit($id)
    {
        $appointment = Appointment::with(['patient', 'doctor'])->find($id);
        if (!$appointment) {
            return response()->json(['error' => 'រកមិនឃើញការណាត់ជួបទេ'], 404);
        }

        return response()->json([
            'appointment_id' => $appointment->appointment_id,
            'patient_id' => $appointment->patient_id,
            'employee_id' => $appointment->employee_id,
            'appointment_date' => $appointment->appointment_date ? $appointment->appointment_date->format('Y-m-d\TH:i') : '',
            'status' => $appointment->status,
            'reason' => $appointment->reason,
        ]);
    }

    /**
     * Update the specified appointment in storage.
     */
    public function update(Request $request, $id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return redirect()->back()->with(['error' => 'រកមិនឃើញការណាត់ជួបទេ']);
        }

        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'employee_id' => 'required|exists:employees,employee_id',
            'appointment_date' => 'required|date',
            'status' => 'required|in:scheduled,completed,cancelled',
            'reason' => 'nullable|string|max:255',
        ]);

        $appointment->update([
            'patient_id' => $request->patient_id,
            'employee_id' => $request->employee_id,
            'appointment_date' => $request->appointment_date,
            'status' => $request->status,
            'reason' => $request->reason,
        ]);

        return redirect()->back()->with(['success' => 'ការណាត់ជួបត្រូវបានកែប្រែដោយជោគជ័យ (Appointment updated successfully)']);
    }

    /**
     * Remove the specified appointment from storage.
     */
    public function destroy($id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return redirect()->back()->with(['error' => 'រកមិនឃើញការណាត់ជួបទេ']);
        }

        $appointment->delete();

        return redirect()->back()->with(['success' => 'ការណាត់ជួបត្រូវបានលុបដោយជោគជ័យ (Appointment deleted successfully)']);
    }
}
