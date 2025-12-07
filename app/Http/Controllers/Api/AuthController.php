<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * @OA\Info(
 *     title="API Sistema de Reservas - Universidad",
 *     version="1.0.0",
 *     description="Backend para la gestión de reservas de recursos (salas, equipos, etc.) con autenticación JWT y roles admin/usuario."
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Introduce el token JWT con el formato: Bearer {token}"
 * )
 */

class AuthController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Iniciar sesión y obtener token JWT",
     *     description="Recibe email y password, valida credenciales y devuelve un token JWT para usar en el resto de endpoints protegidos.",
     *     tags={"Autenticación"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", example="admin@uni.com"),
     *             @OA\Property(property="password", type="string", example="secret123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login exitoso",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOi..."),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=7200)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Credenciales inválidas",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Credenciales inválidas")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Los datos proporcionados no son válidos")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return response()->json([
                'message' => 'Credenciales inválidas',
            ], 401);
        }

        $user = Auth::guard('api')->user();

        // SI DEBE CAMBIAR CONTRASEÑA, NO LE DAMOS TOKEN
        if ($user->must_change_password) {
            // invalidamos el token que se generó
            Auth::guard('api')->logout();

            return response()->json([
                'message'             => 'Debe cambiar su contraseña antes de continuar',
                'must_change_password' => true,
                'user_id'             => $user->id,
                'email'               => $user->email,
            ], 403);
        }

        return $this->respondWithToken($token);
    }


    /**
     * @OA\Get(
     *     path="/api/me",
     *     summary="Obtener información del usuario autenticado",
     *     tags={"Autenticación"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Usuario autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Admin Universidad"),
     *             @OA\Property(property="email", type="string", example="admin@uni.com"),
     *             @OA\Property(property="role", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nombre", type="string", example="admin")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado"
     *     )
     * )
     */
    public function me()
    {
        $user = Auth::guard('api')->user();

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role ? $user->role->nombre : null,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Cerrar sesión (invalidar token actual)",
     *     description="Invalida el token JWT actual. El token deja de ser válido y el usuario deberá volver a iniciar sesión.",
     *     tags={"Autenticación"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout exitoso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Sesión cerrada correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token inválido o no enviado"
     *     )
     * )
     */
    public function logout()
    {
        Auth::guard('api')->logout();

        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }

    protected function respondWithToken(string $token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => JWTAuth::factory()->getTTL() * 60, // en segundos
            'user'         => Auth::guard('api')->user(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/change-password-first-login",
     *     summary="Cambiar contraseña en el primer inicio de sesión",
     *     description="Permite a un usuario cambiar su contraseña obligatoriamente en el primer inicio de sesión.",
     *     tags={"Autenticación"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id","new_password","new_password_confirmation"},
     *             @OA\Property(property="user_id", type="integer", example=2),
     *             @OA\Property(property="new_password", type="string", example="nuevaSecret123"),
     *             @OA\Property(property="new_password_confirmation", type="string", example="nuevaSecret123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contraseña cambiada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Contraseña cambiada correctamente. Ahora puede iniciar sesión.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Los datos proporcionados no son válidos")
     *         )
     *     )
     * )
     */
    public function changePasswordFirstLogin(Request $request)
    {
        $data = $request->validate([
            'user_id'                  => 'required|exists:users,id',
            'new_password'             => 'required|min:6|confirmed',
        ]);

        $user = User::findOrFail($data['user_id']);

        $user->password = Hash::make($data['new_password']);
        $user->must_change_password = false;
        $user->save();

        return response()->json([
            'message' => 'Contraseña cambiada correctamente. Ahora puede iniciar sesión.',
        ]);
    }
}
