<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $departments = Department::orderBy('department_name')->get();

        return view('form.profile.edit', compact('user', 'departments'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'username'        => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone'           => 'nullable|string|max:20',
            'department_id'   => 'nullable|exists:departments,department_id',
            'specialization'  => 'nullable|string|max:150',
            'avatar'          => 'nullable|image|mimes:jpg,jpeg,png|max:1024', // 1MB, png/jpg only
        ]);

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $validated['avatar']      = base64_encode(file_get_contents($file->getRealPath()));
            $validated['avatar_mime'] = $file->getClientMimeType(); // image/jpeg | image/png
        } else {
            unset($validated['avatar']); // កុំសរសេរជាន់លើរូបចាស់ បើគ្មានរូបថ្មី
        }

        $user->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'ព័ត៌មានផ្ទាល់ខ្លួនត្រូវបានកែប្រែដោយជោគជ័យ']);
        }
        return back()->with('success', 'ព័ត៌មានផ្ទាល់ខ្លួនត្រូវបានកែប្រែដោយជោគជ័យ');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            $errors = ['current_password' => ['ពាក្យសម្ងាត់បច្ចុប្បន្នមិនត្រឹមត្រូវទេ']];
            if ($request->ajax()) {
                return response()->json(['message' => 'Validation error', 'errors' => $errors], 422);
            }
            return back()->withErrors($errors);
        }

        $user->update(['password' => Hash::make($request->password)]);

        if ($request->ajax()) {
            return response()->json(['message' => 'ពាក្យសម្ងាត់ត្រូវបានផ្លាស់ប្តូរដោយជោគជ័យ']);
        }
        return back()->with('success', 'ពាក្យសម្ងាត់ត្រូវបានផ្លាស់ប្តូរដោយជោគជ័យ');
    }

    // ── Serves the avatar stored as base64 in the DB ──
    public function avatar(User $user)
    {
        abort_unless($user->avatar, 404);

        return response(base64_decode($user->avatar))
            ->header('Content-Type', $user->avatar_mime ?: 'image/jpeg')
            ->header('Cache-Control', 'private, max-age=3600');
    }
}
