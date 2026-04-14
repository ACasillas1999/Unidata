<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class RolesController extends Controller
{
    // ── Vista principal ───────────────────────────────────────────────────────

    public function index(): View
    {
        $roles = Role::withCount(['users'])->orderBy('is_system', 'desc')->orderBy('name')->get();

        return view('roles.index', [
            'roles'       => $roles,
            'totalUsers'  => User::count(),
        ]);
    }

    // ── Crear rol ─────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:64|unique:roles,name',
            'description' => 'nullable|string|max:255',
            'color'       => 'nullable|string|max:7',
        ]);

        $data['slug']        = Str::slug($data['name']);
        $data['is_system']   = false;
        $data['permissions'] = Role::defaultPermissions();

        // Asegurar slug único
        $baseSlug = $data['slug'];
        $counter  = 1;
        while (Role::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $baseSlug . '-' . $counter++;
        }

        $role = Role::create($data);

        return response()->json([
            'ok'      => true,
            'role'    => $role->load('users'),
            'message' => "Rol \"{$role->name}\" creado correctamente.",
        ], 201);
    }

    // ── Actualizar permisos / metadata ────────────────────────────────────────

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $role = Role::findOrFail($id);
        } catch (Throwable) {
            return response()->json(['ok' => false, 'message' => 'Rol no encontrado.'], 404);
        }

        $data = $request->validate([
            'name'        => "nullable|string|max:64|unique:roles,name,{$id}",
            'description' => 'nullable|string|max:255',
            'color'       => 'nullable|string|max:7',
            'permissions' => 'nullable|array',
        ]);

        // Si cambia el nombre de un rol del sistema, actualizar todos sus usuarios
        if (isset($data['name']) && $data['name'] !== $role->name) {
            User::where('role', $role->name)->update(['role' => $data['name']]);
            $data['slug'] = Str::slug($data['name']);
        }

        // Solo actualizar los permisos que se envíen (merge profundo)
        if (isset($data['permissions'])) {
            $current = $role->permissions ?? Role::defaultPermissions();
            foreach ($data['permissions'] as $section => $items) {
                foreach ($items as $key => $value) {
                    $current[$section][$key] = (bool) $value;
                }
            }
            $data['permissions'] = $current;
        }

        $role->update($data);

        return response()->json([
            'ok'      => true,
            'role'    => $role->fresh()->loadCount('users'),
            'message' => "Rol \"{$role->name}\" actualizado.",
        ]);
    }

    // ── Eliminar rol ──────────────────────────────────────────────────────────

    public function destroy(int $id): JsonResponse
    {
        try {
            $role = Role::findOrFail($id);
        } catch (Throwable) {
            return response()->json(['ok' => false, 'message' => 'Rol no encontrado.'], 404);
        }

        if ($role->is_system) {
            return response()->json([
                'ok'      => false,
                'message' => "El rol \"{$role->name}\" es un rol del sistema y no puede eliminarse.",
            ], 403);
        }

        $usersCount = User::where('role', $role->name)->count();
        if ($usersCount > 0) {
            return response()->json([
                'ok'      => false,
                'message' => "No se puede eliminar: {$usersCount} usuario(s) tienen asignado este rol. Reasígnalos primero.",
            ], 422);
        }

        $name = $role->name;
        $role->delete();

        return response()->json(['ok' => true, 'message' => "Rol \"{$name}\" eliminado."]);
    }

    // ── Duplicar rol ──────────────────────────────────────────────────────────

    public function duplicate(int $id): JsonResponse
    {
        try {
            $original = Role::findOrFail($id);
        } catch (Throwable) {
            return response()->json(['ok' => false, 'message' => 'Rol no encontrado.'], 404);
        }

        $newName = $original->name . ' (copia)';
        $suffix  = 2;
        while (Role::where('name', $newName)->exists()) {
            $newName = $original->name . " (copia {$suffix})";
            $suffix++;
        }

        $slug = Str::slug($newName);
        $baseSLug = $slug;
        $counter  = 1;
        while (Role::where('slug', $slug)->exists()) {
            $slug = $baseSLug . '-' . $counter++;
        }

        $clone = Role::create([
            'name'        => $newName,
            'slug'        => $slug,
            'description' => $original->description,
            'color'       => $original->color,
            'is_system'   => false,
            'permissions' => $original->permissions,
        ]);

        return response()->json([
            'ok'      => true,
            'role'    => $clone->loadCount('users'),
            'message' => "Rol \"{$clone->name}\" creado como copia de \"{$original->name}\".",
        ], 201);
    }
}
