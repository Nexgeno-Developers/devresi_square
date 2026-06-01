<?php

namespace App\Http\Controllers\Auth;

// use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Hash as FacadesHash;

class PasswordResetController extends Controller
{
    /**
     * Show the password reset form.
     */
    public function showResetForm($token, Request $request)
    {
        $record = DB::table('password_reset_tokens')->get()->first(function ($row) use ($token) {
            return Hash::check($token, $row->token);
        });

        if (! $record) {
            abort(403, 'Invalid or expired reset link.');
        }
        
        return view('auth.passwords.reset', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Handle the reset request.
     */
    /*public function reset(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Attempt to reset
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, $password) {
                $user->password = Hash::make($password);
                $user->save();

                Log::info("Password reset successful for user ID {$user->id}");
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        return back()->withErrors(['email' => __($status)]);
    }*/

    public function reset(Request $request)
    {
        // Log::info('Password reset request received', $request->all());

        $request->validate([
            'token' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);
        // Log::info('Validation passed');

        $record = DB::table('password_reset_tokens')->get()->first(function ($row) use ($request) {
            $match = Hash::check($request->token, $row->token);
            // Log::info("Checking token for email {$row->email}: " . ($match ? 'MATCH' : 'NO MATCH'));
            return $match;
        });

        if (! $record) {
            // Log::warning('No matching password reset record found for token', ['token' => $request->token]);
            return back()->withErrors(['token' => 'Invalid or expired reset link.']);
        }
        // Log::info('Found matching password reset record', ['email' => $record->email]);

        $user = User::where('email', $record->email)->firstOrFail();
        // Log::info('User fetched', ['user_id' => $user->id, 'email' => $user->email]);

        $user->password = Hash::make($request->password);
        $user->save();
        // Log::info('Password updated successfully for user', ['user_id' => $user->id]);

        // Delete token
        DB::table('password_reset_tokens')->where('email', $record->email)->delete();
        // Log::info('Password reset token deleted', ['email' => $record->email]);

        flash('Password reset successful!')->success();
        // Log::info('Redirecting to login page');
        return redirect()->route('login');
    }


}