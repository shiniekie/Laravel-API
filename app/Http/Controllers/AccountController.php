<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{

public function index()
{
    $accounts = DB::table('accounts')->get();

    return response()->json([
        'data' => $accounts
    ]);
}

            // GET ONE

        public function show($id)
{
    $account = Account::findOrFail($id);

    return response()->json([
        'data' => $account
    ]);
        $account = Account::where('user_id', $user->id)
            ->findOrFail($id);

        return response()->json([
            'message' => 'Account retrieved successfully',
            'data' => $account
        ]);
    }

    // CREATE
public function store(Request $request)
{
    $data = [
        'site' => $request->site,
        'username' => $request->username,
        'password' => $request->password,
        'user_id' => 1
    ];

    // ✅ HANDLE IMAGE
    if ($request->hasFile('image')) {
        $file = $request->file('image');
        $filename = time() . '.' . $file->getClientOriginalExtension();

        $file->move(public_path('images'), $filename);

        $data['image'] = 'images/' . $filename; // 🔥 THIS IS KEY
    }

    \DB::table('accounts')->insert($data);

    return response()->json([
        'message' => 'Account created successfully'
    ]);
}


    // UPDATE
    public function update(Request $request, $id)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $account = Account::where('user_id', $user->id)
    ->where('id', $id)
    ->firstOrFail();

        $data = $request->only(['site', 'username', 'password']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if ($request->hasFile('image')) {

            if ($account->image && file_exists(public_path($account->image))) {
                unlink(public_path($account->image));
            }

            $file = $request->file('image');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images'), $filename);

            $data['image'] = 'images/' . $filename;
        }

        $account->update($data);

        return response()->json([
            'message' => 'Account updated successfully',
            'data' => $account
        ]);
    }

    // DELETE
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        Account::where('user_id', $user->id)
            ->where('id', $id)
            ->delete();

        return response()->json([
            'message' => 'Account deleted successfully'
        ]);
    }
}
