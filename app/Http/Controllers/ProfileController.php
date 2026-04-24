<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function update(Request $request)
{
    $user = $request->user();

    $data = $request->validate([
        'name' => 'required',
        'email' => 'required|email|unique:users,email,' . $user->id,
        'password' => 'nullable',
    ]);

    // HANDLE PASSWORD
    if (!empty($data['password'])) {
        $data['password'] = bcrypt($data['password']);
    } else {
        unset($data['password']);
    }

    // HANDLE IMAGE
    if ($request->hasFile('image')) {
        $file = $request->file('image');
        $filename = time() . '.' . $file->getClientOriginalExtension();

        $file->move(public_path('images'), $filename);

        $data['image'] = 'images/' . $filename;
    }

    $user->update($data);

    return response()->json([
        'message' => 'Profile updated successfully',
        'data' => $user
    ]);
}
}