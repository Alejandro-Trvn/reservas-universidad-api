<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RolesController extends Controller
{
    // ================== HELPER: SOLO ADMIN ==================
    private function ensureIsAdmin()
    {
        $user = Auth::guard('api')->user();

        if (! $user || ! $user->role || $user->role->nombre !== 'admin') {
            abort(response()->json([
                'message' => 'No autorizado. Solo un administrador puede realizar esta acción.'
            ], 403));
        }
    }

    // LISTAR ROLES (ADMIN)
    public function index(Request $request)
    {
        $this->ensureIsAdmin();

        $query = Role::query();

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        $roles = $query->get();

        return response()->json($roles);
    }

    // CREAR ROL (ADMIN)
    public function store(Request $request)
    {
        $this->ensureIsAdmin();

        $data = $request->validate([
            'nombre' => 'required|string|max:100|unique:roles,nombre',
            'descripcion' => 'sometimes|nullable|string',
        ]);

        $role = Role::create([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'estado' => 1,
        ]);

        return response()->json([
            'message' => 'Rol creado correctamente',
            'role' => $role,
        ], 201);
    }

    // VER ROL (ADMIN)
    public function show($id)
    {
        $this->ensureIsAdmin();

        $role = Role::find($id);

        if (! $role) {
            return response()->json(['message' => 'Rol no encontrado'], 404);
        }

        return response()->json($role);
    }

    // ACTUALIZAR ROL (ADMIN)
    public function update(Request $request, $id)
    {
        $this->ensureIsAdmin();

        $role = Role::find($id);

        if (! $role) {
            return response()->json(['message' => 'Rol no encontrado'], 404);
        }

        $data = $request->validate([
            'nombre' => 'sometimes|required|string|max:100|unique:roles,nombre,' . $role->id,
            'descripcion' => 'sometimes|nullable|string',
            'estado' => 'sometimes|required|integer|in:0,1',
        ]);

        $role->update($data);

        return response()->json([
            'message' => 'Rol actualizado correctamente',
            'role' => $role,
        ]);
    }

    // DESACTIVAR ROL (ADMIN) -> soft delete mediante estado=0
    public function destroy($id)
    {
        $this->ensureIsAdmin();

        $role = Role::find($id);

        if (! $role) {
            return response()->json(['message' => 'Rol no encontrado'], 404);
        }

        $role->estado = 0;
        $role->save();

        return response()->json(['message' => 'Rol desactivado correctamente']);
    }
}
