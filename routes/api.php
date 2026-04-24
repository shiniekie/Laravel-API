<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Password;


use App\Models\User;
use App\Models\Account;
use App\Http\Controllers\AccountController;

/*
|--------------------------------------------------------------------------
| USER (PROFILE)
|--------------------------------------------------------------------------
*/

// GET LOGGED-IN USER
Route::get('/user/{id}', function ($id) {

    $user = \App\Models\User::findOrFail($id);

    return response()->json([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'image' => $user->image,
    ]);
});

// UPDATE PROFILE
Route::post('/profile', function (Request $request) {

    try {

        $userId = $request->input('user_id');

        $user = \App\Models\User::find($userId);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->validate([
            'name' => 'required',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id)
            ]
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if (!empty($request->password)) {
            $user->password = Hash::make($request->password);
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');

            if ($file && $file->isValid()) {
                $filename = time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('images'), $filename);

                $user->image = 'images/' . $filename;
            }
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'line' => $e->getLine()
        ], 500);
    }

});


/*
|--------------------------------------------------------------------------
| ACCOUNT ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/accounts', [AccountController::class, 'index']);
Route::get('/accounts/{id}', [AccountController::class, 'show']);
Route::post('/accounts', [AccountController::class, 'store']);
Route::post('/accounts/update/{id}', function (Request $request, $id) {

    try {
        $account = Account::find($id);

        if (!$account) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        $account->site = $request->site;
        $account->username = $request->username;

        if ($request->password) {
            $account->password = $request->password;
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');

            if ($file && $file->isValid()) {
                $filename = time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('images'), $filename);

                $account->image = 'images/' . $filename;
            }
        }

        $account->save();

        return response()->json([
            'message' => 'Account updated successfully'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
});

// DELETE
Route::delete('/accounts/{id}', function ($id) {

    $account = Account::find($id);

    if (!$account) {
        return response()->json(['message' => 'Account not found'], 404);
    }

    $account->delete();

    return response()->json([
        'message' => 'Account deleted successfully'
    ]);
});


/*
|--------------------------------------------------------------------------
| AUTH (LOGIN VIA EMAIL LINK)
|--------------------------------------------------------------------------
*/

Route::post('/login-link', function (Request $request) {

    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    $user = User::where('email', $request->email)->first();

    // 1️⃣ CHECK USER EXISTS
    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    // 2️⃣ CHECK PASSWORD FIRST
    if (!Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    // 3️⃣ 🔥 THEN CHECK VERIFICATION
    if (!$user->email_verified_at) {
        return response()->json([
            'message' => 'Please verify your email first'
        ], 403);
    }

    // 4️⃣ SEND LOGIN LINK
    $url = URL::temporarySignedRoute(
        'login.verify',
        now()->addMinutes(30),
        ['id' => $user->id]
    );

    Mail::raw("Click to login: $url", function ($message) use ($user) {
        $message->to($user->email)->subject('Login Link');
    });

    return response()->json([
        'message' => 'Check your email to login'
    ]);
});


Route::get('/login/verify/{id}', function ($id) {

    $user = User::findOrFail($id);

    $token = $user->createToken('auth_token')->plainTextToken;

    return redirect()->away(
    "http://127.0.0.1:8000/frontend/index.html?token=" . rawurlencode($token)
    . "&email=" . urlencode($user->email)
);

})->middleware('signed')->name('login.verify');


/*
|--------------------------------------------------------------------------
| LOGOUT VIA EMAIL LINK
|--------------------------------------------------------------------------
*/



Route::post('/logout-link', function (\Illuminate\Http\Request $request) {

    $email = $request->input('email');

    if (!$email) {
        return response()->json(['message' => 'Email missing']);
    }

    $user = User::where('email', $email)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found']);
    }

    $url = URL::temporarySignedRoute(
        'logout.verify',
        now()->addMinutes(30),
        ['id' => $user->id]
    );

    // 🔥 SAFE MAIL (with fallback)
    try {
        Mail::raw("Logout confirmation link:\n\n$url", function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Logout Confirmation');
        });
    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'Mail failed',
            'link' => $url // fallback
        ]);
    }

    return response()->json([
        'message' => 'Logout link sent (check your email)',
        'debug_link' => $url // optional (for testing)
    ]);
});


Route::get('/logout/verify/{id}', function ($id) {

    $user = User::findOrFail($id);

    // delete all tokens (logout)
    $user->tokens()->delete();

    return redirect("http://127.0.0.1:8000/frontend/index.html");

})->middleware('signed')->name('logout.verify');




Route::post('/register', function (Request $request) {

    $request->validate([
        'name' => 'required',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:6'
    ]);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    // 🔥 CREATE VERIFY LINK
    $url = URL::temporarySignedRoute(
        'verify.email',
        now()->addMinutes(30),
        ['id' => $user->id]
    );

    // 🔥 SEND EMAIL
    Mail::raw("Verify your account:\n\n$url", function ($message) use ($user) {
        $message->to($user->email)->subject('Verify Your Email');
    });

    return response()->json([
        'message' => 'Registered successfully. Check your email to verify.'
    ]);
});


Route::get('/verify-email/{id}', function ($id) {

    $user = User::findOrFail($id);

    $user->email_verified_at = now();
    $user->save();

    return redirect("http://127.0.0.1:8000/frontend/index.html?verified=1");

})->middleware('signed')->name('verify.email');



Route::post('/forgot-password', function (\Illuminate\Http\Request $request) {

    $request->validate([
        'email' => 'required|email'
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'message' => 'User not found'
        ], 404);
    }

    // generate reset link
    $url = URL::temporarySignedRoute(
        'password.reset',
        now()->addMinutes(30),
        ['id' => $user->id, 'email' => $user->email]
    );

    // send email (same as your login system)
    Mail::raw("Reset your password: $url", function ($message) use ($user) {
        $message->to($user->email)
                ->subject('Password Reset');
    });

    return response()->json([
        'message' => 'Reset link sent (check your email)'
    ]);
});



Route::get('/reset-password/{id}', function (Request $request, $id) {

    // validate signed URL
    if (!$request->hasValidSignature()) {
        abort(403, 'Invalid or expired link');
    }

    $email = $request->query('email');

    // generate a simple token (can reuse signature)
    $token = $request->query('signature');

    return redirect(
        "http://127.0.0.1:8000/frontend/index.html"
        . "?reset_token=$token&email=" . urlencode($email)
    );

})->name('password.reset');



Route::post('/reset-password', function (Request $request) {

    $request->validate([
        'email' => 'required|email',
        'password' => 'required|min:6|confirmed',
        'token' => 'required'
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'message' => 'User not found'
        ], 404);
    }

    // update password
    $user->password = Hash::make($request->password);
    $user->save();

    return response()->json([
        'message' => 'Password reset successful'
    ]);
});
