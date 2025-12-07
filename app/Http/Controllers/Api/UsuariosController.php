<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Tag(
 *     name="Usuarios",
 *     description="Gestión de usuarios del sistema"
 * )
 */
class UsuariosController extends Controller
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

    // ================== LISTAR USUARIOS (ADMIN) ==================
    /**
     * @OA\Get(
     *     path="/api/usuarios",
     *     tags={"Usuarios"},
     *     summary="Listar todos los usuarios",
     *     description="Obtiene la lista de usuarios (solo administradores). Permite filtrar por estado y rol.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="estado",
     *         in="query",
     *         description="Filtrar por estado (0=inactivo, 1=activo)",
     *         required=false,
     *         @OA\Schema(type="integer", enum={0, 1})
     *     ),
     *     @OA\Parameter(
     *         name="role_id",
     *         in="query",
     *         description="Filtrar por ID del rol",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de usuarios",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Juan Pérez"),
     *                 @OA\Property(property="email", type="string", example="juan@universidad.edu"),
     *                 @OA\Property(property="role_id", type="integer", example=2),
     *                 @OA\Property(property="estado", type="integer", example=1),
     *                 @OA\Property(property="must_change_password", type="boolean", example=false),
     *                 @OA\Property(property="role", type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="nombre", type="string", example="usuario")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $this->ensureIsAdmin();

        $query = User::with('role');

        // Filtro opcional por estado
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        // Opcional: filtro por rol
        if ($request->has('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        $usuarios = $query->get();

        return response()->json($usuarios);
    }

    // ================== CREAR USUARIO (ADMIN) ==================
    /**
     * @OA\Post(
     *     path="/api/usuarios",
     *     tags={"Usuarios"},
     *     summary="Crear un nuevo usuario",
     *     description="Crea un nuevo usuario en el sistema (solo administradores). El usuario creado deberá cambiar su contraseña en el primer ingreso.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "role_id"},
     *             @OA\Property(property="name", type="string", example="María García", maxLength=100),
     *             @OA\Property(property="email", type="string", format="email", example="maria@universidad.edu"),
     *             @OA\Property(property="password", type="string", format="password", example="secreto123", minLength=6),
     *             @OA\Property(property="role_id", type="integer", example=2, description="ID del rol (debe existir en la tabla roles)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuario creado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Usuario creado correctamente. Debe cambiar su contraseña al primer ingreso."),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=5),
     *                 @OA\Property(property="name", type="string", example="María García"),
     *                 @OA\Property(property="email", type="string", example="maria@universidad.edu"),
     *                 @OA\Property(property="role_id", type="integer", example=2),
     *                 @OA\Property(property="estado", type="integer", example=1),
     *                 @OA\Property(property="must_change_password", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $this->ensureIsAdmin();

        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id'  => 'required|exists:roles,id',
        ]);

        $usuario = User::create([
            'name'                 => $data['name'],
            'email'                => $data['email'],
            'password'             => Hash::make($data['password']),
            'role_id'              => $data['role_id'],
            'estado'               => 1,
            'must_change_password' => true, // 👈 fuerza cambio de contraseña
        ]);

        return response()->json([
            'message' => 'Usuario creado correctamente. Debe cambiar su contraseña al primer ingreso.',
            'user'    => $usuario,
        ], 201);
    }

    // ================== VER USUARIO (ADMIN) ==================
    /**
     * @OA\Get(
     *     path="/api/usuarios/{id}",
     *     tags={"Usuarios"},
     *     summary="Ver detalles de un usuario",
     *     description="Obtiene la información completa de un usuario específico (solo administradores).",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del usuario",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Información del usuario",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Juan Pérez"),
     *             @OA\Property(property="email", type="string", example="juan@universidad.edu"),
     *             @OA\Property(property="role_id", type="integer", example=2),
     *             @OA\Property(property="estado", type="integer", example=1),
     *             @OA\Property(property="must_change_password", type="boolean", example=false),
     *             @OA\Property(property="role", type="object",
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="nombre", type="string", example="usuario")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuario no encontrado"
     *     )
     * )
     */
    public function show($id)
    {
        $this->ensureIsAdmin();

        $usuario = User::with('role')->find($id);

        if (! $usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        return response()->json($usuario);
    }

    // ================== EDITAR USUARIO (ADMIN) ==================
    /**
     * @OA\Put(
     *     path="/api/usuarios/{id}",
     *     tags={"Usuarios"},
     *     summary="Actualizar un usuario",
     *     description="Actualiza la información de un usuario (solo administradores). No puede desactivar su propio usuario.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del usuario a actualizar",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Juan Pérez Actualizado", maxLength=100),
     *             @OA\Property(property="email", type="string", format="email", example="juan.nuevo@universidad.edu"),
     *             @OA\Property(property="role_id", type="integer", example=3),
     *             @OA\Property(property="estado", type="integer", example=1, enum={0, 1}, description="0=inactivo, 1=activo")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario actualizado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Usuario actualizado correctamente"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Juan Pérez Actualizado"),
     *                 @OA\Property(property="email", type="string", example="juan.nuevo@universidad.edu"),
     *                 @OA\Property(property="role_id", type="integer", example=3),
     *                 @OA\Property(property="estado", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuario no encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación o intento de desactivar su propio usuario"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $this->ensureIsAdmin();

        $usuario = User::find($id);

        if (! $usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $data = $request->validate([
            'name'    => 'sometimes|required|string|max:100',
            'email'   => 'sometimes|required|email|unique:users,email,' . $usuario->id,
            'role_id' => 'sometimes|required|exists:roles,id',
            'estado'  => 'sometimes|required|integer|in:0,1',
        ]);

        // Opcional: evitar que un admin se desactive a sí mismo
        if (isset($data['estado']) && $usuario->id === Auth::guard('api')->id()) {
            return response()->json([
                'message' => 'No puede desactivar su propio usuario.'
            ], 422);
        }

        $usuario->update($data);

        return response()->json([
            'message' => 'Usuario actualizado correctamente',
            'user'    => $usuario,
        ]);
    }

    // ================== DESACTIVAR USUARIO (ADMIN) ==================
    /**
     * @OA\Delete(
     *     path="/api/usuarios/{id}",
     *     tags={"Usuarios"},
     *     summary="Desactivar un usuario",
     *     description="Desactiva un usuario (cambia su estado a 0) en lugar de eliminarlo físicamente (solo administradores). No puede desactivar su propio usuario.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del usuario a desactivar",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario desactivado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Usuario desactivado correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuario no encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Intento de desactivar su propio usuario"
     *     )
     * )
     */
    public function destroy($id)
    {
        $this->ensureIsAdmin();

        $usuario = User::find($id);

        if (! $usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Evitar desactivar al admin que está conectado
        if ($usuario->id === Auth::guard('api')->id()) {
            return response()->json([
                'message' => 'No puede desactivar su propio usuario.'
            ], 422);
        }

        $usuario->estado = 0; // inactivo
        $usuario->save();

        return response()->json([
            'message' => 'Usuario desactivado correctamente',
        ]);
    }

    // ================== PERFIL DEL USUARIO AUTENTICADO ==================
    /**
     * @OA\Put(
     *     path="/api/usuarios/perfil",
     *     tags={"Usuarios"},
     *     summary="Actualizar perfil del usuario autenticado",
     *     description="Permite al usuario autenticado actualizar su nombre y correo electrónico. No se puede modificar el rol ni el estado.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email"},
     *             @OA\Property(property="name", type="string", example="Mi Nuevo Nombre", maxLength=100),
     *             @OA\Property(property="email", type="string", format="email", example="mi.nuevo.email@universidad.edu")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Perfil actualizado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Perfil actualizado correctamente"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Mi Nuevo Nombre"),
     *                 @OA\Property(property="email", type="string", example="mi.nuevo.email@universidad.edu"),
     *                 @OA\Property(property="role_id", type="integer", example=2),
     *                 @OA\Property(property="estado", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación o campos no permitidos"
     *     )
     * )
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::guard('api')->user();

        // 1) Definir campos permitidos
        $allowedFields = ['name', 'email'];

        // 2) Detectar campos extra enviados
        $inputKeys = array_keys($request->all());
        $extraFields = array_diff($inputKeys, $allowedFields);

        if (!empty($extraFields)) {
            return response()->json([
                'message' => 'Solo puede actualizar su nombre y correo electrónico.',
                'invalid_fields' => array_values($extraFields),
            ], 422);
        }

        // 3) Validar SOLO name y email
        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        // 4) Actualizar
        $userModel = User::find($user->id);
        $userModel->update($data);

        return response()->json([
            'message' => 'Perfil actualizado correctamente',
            'user'    => $userModel,
        ]);
    }
}
