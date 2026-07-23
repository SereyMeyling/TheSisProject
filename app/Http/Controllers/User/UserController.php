<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->getFilteredUsers($request);
        $totalUser = User::count();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('form.user.partials.table', compact('user'))->render(),
                'total' => $totalUser,
            ]);
        }

        return view('form.user.user', compact('user', 'totalUser'));
    }

    protected function getFilteredUsers(Request $request)
    {
        $query = User::query()
            ->select(['id', 'name', 'email', 'username', 'google2fa_secret', 'google2fa_enabled', 'created_at']);

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', $searchTerm)
                    ->orWhere('email', 'LIKE', $searchTerm)
                    ->orWhere('username', 'LIKE', $searchTerm);
            });
        }

        return $query->orderBy('name')->paginate(10)->appends($request->query());
    }

    /**
     * Reset the target user's Two-Factor Authentication enrollment.
     * Does not delete the user or touch their password.
     */
    public function resetTwoFactor($id)
    {
        $user = User::find($id);

        if (!$user) {
            return redirect()->back()->with(['error' => 'រកមិនឃើញអ្នកប្រើប្រាស់']);
        }

        $user->resetTwoFactorAuthentication();

        return redirect()->back()->with(['success' => 'Two-Factor Authentication របស់ ' . $user->name . ' ត្រូវបានកំណត់ឡើងវិញដោយជោគជ័យ']);
    }
}
