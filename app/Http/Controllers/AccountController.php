<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    // GET ALL
    public function index()
    {
        return response()->json([
            'message' => 'Accounts retrieved successfully',
            'data' => Account::all()
        ]);
    }

    // CREATE
    public function store(Request $request)
    {
        $account = Account::create([
            'site' => $request->site,
            'username' => $request->username,
            'password' => $request->password
        ]);

        return response()->json([
            'message' => 'Account created successfully',
            'data' => $account
        ]);
    }

    // GET ONE
    public function show($id)
    {
        $account = Account::findOrFail($id);

        return response()->json([
            'message' => 'Account retrieved successfully',
            'data' => $account
        ]);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $account = Account::findOrFail($id);

        $account->update([
            'site' => $request->site,
            'username' => $request->username,
            'password' => $request->password
        ]);

        return response()->json([
            'message' => 'Account updated successfully',
            'data' => $account
        ]);
    }

    // DELETE
    public function destroy($id)
    {
        Account::destroy($id);

        return response()->json([
            'message' => 'Account deleted successfully'
        ]);
    }
}