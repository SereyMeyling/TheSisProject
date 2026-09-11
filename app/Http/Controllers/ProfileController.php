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
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => 'nullable|string|max:20',
            'department_id' => 'nullable|exists:departments,department_id',
            'specialization' => 'nullable|string|max:150',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Update Avatar
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('avatar')) {

            $file = $request->file('avatar');

            $validated['avatar'] = base64_encode(
                file_get_contents($file->getRealPath())
            );

            $validated['avatar_mime'] = $file->getClientMimeType();

        } else {

            // Don't overwrite existing avatar
            unset($validated['avatar']);
        }

        /*
        |--------------------------------------------------------------------------
        | Update User
        |--------------------------------------------------------------------------
        */

        $user->update($validated);

        /*
        |--------------------------------------------------------------------------
        | Refresh User + Department
        |--------------------------------------------------------------------------
        */

        $user->refresh();
        $user->load('department');

        /*
        |--------------------------------------------------------------------------
        | AJAX Response
        |--------------------------------------------------------------------------
        */

        if ($request->ajax()) {

            $avatarUrl = $user->avatar
                ? route('profile.avatar', $user->id) . '?v=' . $user->updated_at->timestamp
                : asset('vendor/adminlte/dist/img/user2-160x160.jpg');

            return response()->json([
                'message' => 'ព័ត៌មានផ្ទាល់ខ្លួនត្រូវបានកែប្រែដោយជោគជ័យ',

                'avatar_url' => $avatarUrl,

                'name' => $user->name,

                'department_name' => $user->department->department_name ?? '',
            ]);
        }

        return back()->with(
            'success',
            'ព័ត៌មានផ្ទាល់ខ្លួនត្រូវបានកែប្រែដោយជោគជ័យ'
        );
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {

            $errors = [
                'current_password' => [
                    'ពាក្យសម្ងាត់បច្ចុប្បន្នមិនត្រឹមត្រូវទេ'
                ]
            ];

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $errors
                ], 422);
            }

            return back()->withErrors($errors);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        if ($request->ajax()) {
            return response()->json([
                'message' => 'ពាក្យសម្ងាត់ត្រូវបានផ្លាស់ប្តូរដោយជោគជ័យ'
            ]);
        }

        return back()->with(
            'success',
            'ពាក្យសម្ងាត់ត្រូវបានផ្លាស់ប្តូរដោយជោគជ័យ'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Serve Avatar
    |--------------------------------------------------------------------------
    */

    public function avatar(User $user)
    {
        abort_unless($user->avatar, 404);

        return response(base64_decode($user->avatar))
            ->header(
                'Content-Type',
                $user->avatar_mime ?: 'image/jpeg'
            )
            ->header(
                'Cache-Control',
                'private, max-age=3600'
            );
    }
}
