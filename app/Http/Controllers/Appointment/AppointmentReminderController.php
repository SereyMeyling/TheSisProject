<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Carbon\Carbon;

class AppointmentReminderController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa', 'role:cashier']);
    }

    public function index()
    {
        $appointments = Appointment::with(['patient', 'doctor', 'caller'])
            ->where('status', 'scheduled')
            ->whereDate('appointment_date', Carbon::tomorrow()->toDateString())
            ->orderBy('appointment_date')
            ->get();

        return view('form.appointment.reminders', compact('appointments'));
    }

    public function markCalled($id)
    {
        abort_unless(auth()->user()->hasRole('cashier'), 403);

        $appointment = Appointment::with('caller')->findOrFail($id);

        if ($appointment->status !== 'scheduled') {
            return response()->json(['message' => 'ការណាត់ជួបនេះមិនមែនជាស្ថានភាព scheduled ទេ'], 422);
        }

        // already marked (double click / another cashier): return what is stored
        if ($appointment->reminder_called_at) {
            return response()->json([
                'by' => $appointment->caller->name ?? '-',
                'at' => $appointment->reminder_called_at->format('h:i A'),
            ]);
        }

        // the caller is always the logged-in cashier, never a value from the request
        Appointment::where('appointment_id', $appointment->appointment_id)
            ->whereNull('reminder_called_at')
            ->update([
                'reminder_called_at' => now(),
                'reminder_called_by' => auth()->id(),
            ]);

        return response()->json([
            'by' => auth()->user()->name,
            'at' => now()->format('h:i A'),
        ]);
    }
}
