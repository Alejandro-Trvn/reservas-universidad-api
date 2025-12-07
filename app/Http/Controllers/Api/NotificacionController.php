<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Notificaciones",
 *     description="Gestión de notificaciones del usuario autenticado"
 * )
 */
class NotificacionController extends Controller
{
    private function currentUser()
    {
        return Auth::guard('api')->user();
    }

    // GET /api/notificaciones?solo_no_leidas=true
    /**
     * @OA\Get(
     *     path="/api/notificaciones",
     *     tags={"Notificaciones"},
     *     summary="Listar notificaciones del usuario",
     *     description="Obtiene la lista de notificaciones del usuario autenticado, ordenadas por fecha de creación (más recientes primero). Permite filtrar solo las no leídas.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="solo_no_leidas",
     *         in="query",
     *         description="Filtrar solo notificaciones no leídas",
     *         required=false,
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de notificaciones",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=5),
     *                 @OA\Property(property="tipo", type="string", example="reserva_creada"),
     *                 @OA\Property(property="titulo", type="string", example="Reserva creada correctamente"),
     *                 @OA\Property(property="mensaje", type="string", example="Has reservado el recurso Aula 101 del 2025-12-10 08:00:00 al 2025-12-10 10:00:00."),
     *                 @OA\Property(property="leida", type="boolean", example=false),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-12-07T10:30:00.000000Z")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $this->currentUser();

        $query = Notificacion::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        if ($request->boolean('solo_no_leidas')) {
            $query->where('leida', false);
        }

        $notificaciones = $query->get();

        return response()->json($notificaciones);
    }

    // PUT /api/notificaciones/{id}/leer
    /**
     * @OA\Put(
     *     path="/api/notificaciones/{id}/leer",
     *     tags={"Notificaciones"},
     *     summary="Marcar notificación como leída",
     *     description="Marca una notificación específica del usuario autenticado como leída. Solo el propietario de la notificación puede realizar esta acción.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la notificación",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notificación marcada como leída",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Notificación marcada como leída"),
     *             @OA\Property(property="notificacion", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=5),
     *                 @OA\Property(property="tipo", type="string", example="reserva_creada"),
     *                 @OA\Property(property="titulo", type="string", example="Reserva creada correctamente"),
     *                 @OA\Property(property="mensaje", type="string", example="Has reservado el recurso Aula 101."),
     *                 @OA\Property(property="leida", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-12-07T10:30:00.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notificación no encontrada"
     *     )
     * )
     */
    public function marcarComoLeida($id)
    {
        $user = $this->currentUser();

        $notificacion = Notificacion::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $notificacion) {
            return response()->json(['message' => 'Notificación no encontrada'], 404);
        }

        if (! $notificacion->leida) {
            $notificacion->leida = true;
            $notificacion->save();
        }

        return response()->json([
            'message'      => 'Notificación marcada como leída',
            'notificacion' => $notificacion,
        ]);
    }

    // PUT /api/notificaciones/marcar-todas-leidas
    /**
     * @OA\Put(
     *     path="/api/notificaciones/marcar-todas-leidas",
     *     tags={"Notificaciones"},
     *     summary="Marcar todas las notificaciones como leídas",
     *     description="Marca todas las notificaciones no leídas del usuario autenticado como leídas en una sola operación.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Todas las notificaciones marcadas como leídas",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Todas las notificaciones han sido marcadas como leídas")
     *         )
     *     )
     * )
     */
    public function marcarTodasComoLeidas()
    {
        $user = $this->currentUser();

        Notificacion::where('user_id', $user->id)
            ->where('leida', false)
            ->update(['leida' => true]);

        return response()->json([
            'message' => 'Todas las notificaciones han sido marcadas como leídas',
        ]);
    }
}
