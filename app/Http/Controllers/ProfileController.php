<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index()
    {
        return view('perfil.index');
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'apellidos' => 'nullable|string|max:255',
            'email' => ['required', 'email', 'max:255', "unique:users,email,{$user->id}"],
            'telefono' => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        return back()->with('success', 'Tus datos se actualizaron correctamente.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => 'required|string|min:8|confirmed',
        ]);

        $request->user()->update([
            'password' => $request->input('password'),
        ]);

        return back()->with('success', 'Tu contraseña se actualizó correctamente.');
    }
}