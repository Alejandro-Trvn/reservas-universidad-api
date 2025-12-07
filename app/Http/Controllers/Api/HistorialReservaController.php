<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistorialReserva;
use App\Models\Reserva;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Historial de Reservas",
 *     description="Consulta del historial de cambios de las reservas"
 * )
 */
class HistorialReservaController extends Controller
{
    private function currentUser()
    {
        return Auth::guard('api')->user();
    }

    private function isAdmin(): bool
    {
        $user = $this->currentUser();
        return $user && $user->role && $user->role->nombre === 'admin';
    }

    // GET /api/reservas/{id}/historial
    /**
     * @OA\Get(
     *     path="/api/reservas/{id}/historial",
     *     tags={"Historial de Reservas"},
     *     summary="Ver historial de una reserva",
     *     description="Obtiene el historial completo de cambios de una reserva específica, ordenado cronológicamente. Los usuarios normales solo pueden ver el historial de sus propias reservas.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la reserva",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Historial de la reserva",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="reserva_id", type="integer", example=15),
     *                 @OA\Property(property="user_id", type="integer", example=5),
     *                 @OA\Property(property="accion", type="string", example="creada", description="Tipo de acción: creada, actualizada_admin, actualizada_usuario, cancelada_admin, cancelada_usuario"),
     *                 @OA\Property(property="detalle", type="string", example="Reserva creada para el recurso Aula 101 del 2025-12-10 08:00:00 al 2025-12-10 10:00:00."),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-12-07T10:30:00.000000Z"),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="name", type="string", example="Juan Pérez"),
     *                     @OA\Property(property="email", type="string", example="juan@universidad.edu")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado para ver el historial de esta reserva"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Reserva no encontrada"
     *     )
     * )
     */
    public function porReserva($id)
    {
        $user    = $this->currentUser();
        $esAdmin = $this->isAdmin();

        $reserva = Reserva::find($id);

        if (! $reserva) {
            return response()->json(['message' => 'Reserva no encontrada'], 404);
        }

        // Seguridad: usuario normal solo puede ver el historial de sus propias reservas
        if (! $esAdmin && $reserva->user_id !== $user->id) {
            return response()->json(['message' => 'No autorizado para ver el historial de esta reserva'], 403);
        }

        $historial = HistorialReserva::with('user')
            ->where('reserva_id', $reserva->id)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($historial);
    }
}
