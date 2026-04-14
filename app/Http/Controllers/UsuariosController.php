<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class UsuariosController extends Controller
{
    /**
     * Muestra la lista de usuarios.
     */
    public function index(): View
    {
        $users = User::query()->orderBy('name')->get();

        // Stats rápidos
        $total         = $users->count();
        $administrador = $users->where('role', 'Administrador')->count();
        $coordinador  = $users->where('role', 'Coordinador')->count();
        $auxiliar      = $users->where('role', 'Auxiliar')->count();

        return view('usuarios.index', compact(
            'users',
            'total',
            'administrador',
            'coordinador',
            'auxiliar'
        ));
    }

    /**
     * Crea un nuevo usuario.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email'    => 'required|email|max:255|unique:users,email',
            'role'     => 'required|in:Auxiliar,Administrador,Coordinador',
            'password' => 'required|string|min:8',
        ]);

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return response()->json([
            'ok'      => true,
            'user'    => $user,
            'message' => "Usuario \"{$user->name}\" creado correctamente.",
        ], 201);
    }

    /**
     * Actualiza un usuario existente.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
        } catch (Throwable) {
            return response()->json(['ok' => false, 'message' => 'Usuario no encontrado.'], 404);
        }

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role'     => 'required|in:Auxiliar,Administrador,Coordinador',
            'password' => 'nullable|string|min:8',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            'ok'      => true,
            'user'    => $user->fresh(),
            'message' => "Usuario \"{$user->name}\" actualizado correctamente.",
        ]);
    }

    /**
     * Elimina un usuario.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            
            // Evitar que un usuario se elimine a sí mismo si hubiera auth, 
            // pero para este CRUD básico permitimos todo.
            
            $name = $user->name;
            $user->delete();

            return response()->json(['ok' => true, 'message' => "Usuario \"{$name}\" eliminado."]);
        } catch (Throwable) {
            return response()->json(['ok' => false, 'message' => 'Usuario no encontrado.'], 404);
        }
    }
}
