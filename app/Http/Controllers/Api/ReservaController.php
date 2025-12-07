<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use App\Models\Recurso;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\HistorialReserva;

/**
 * @OA\Tag(
 *     name="Reservas",
 *     description="Gestión de reservas de recursos"
 * )
 */
class ReservaController extends Controller
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

    private function ensureIsAdmin(): void
    {
        if (! $this->isAdmin()) {
            abort(response()->json([
                'message' => 'No autorizado. Solo un administrador puede realizar esta acción.'
            ], 403));
        }
    }

    private function enviarNotificaciones(array $userIds, string $tipo, string $titulo, string $mensaje): void
    {
        foreach ($userIds as $userId) {
            Notificacion::create([
                'user_id' => $userId,
                'tipo'    => $tipo,
                'titulo'  => $titulo,
                'mensaje' => $mensaje,
            ]);
        }
    }

    private function obtenerAdminsIds(): array
    {
        return User::whereHas('role', function ($q) {
            $q->where('nombre', 'admin');
        })
            ->pluck('id')
            ->toArray();
    }

    private function logHistorial(Reserva $reserva, int $actorUserId, string $accion, ?string $detalle = null): void
    {
        HistorialReserva::create([
            'reserva_id' => $reserva->id,
            'user_id'    => $actorUserId,
            'accion'     => $accion,
            'detalle'    => $detalle,
        ]);
    }


    // ============= LISTAR RESERVAS =============
    /**
     * @OA\Get(
     *     path="/api/reservas",
     *     tags={"Reservas"},
     *     summary="Listar reservas",
     *     description="Obtiene la lista de reservas. Los administradores ven todas las reservas con filtros opcionales. Los usuarios normales solo ven sus propias reservas.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="estado",
     *         in="query",
     *         description="Filtrar por estado (solo admin)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"activa", "cancelada"})
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Filtrar por ID de usuario (solo admin)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="recurso_id",
     *         in="query",
     *         description="Filtrar por ID de recurso (solo admin)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="desde",
     *         in="query",
     *         description="Filtrar reservas desde esta fecha (solo admin)",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time", example="2025-12-01 00:00:00")
     *     ),
     *     @OA\Parameter(
     *         name="hasta",
     *         in="query",
     *         description="Filtrar reservas hasta esta fecha (solo admin)",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time", example="2025-12-31 23:59:59")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de reservas",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=5),
     *                 @OA\Property(property="recurso_id", type="integer", example=3),
     *                 @OA\Property(property="fecha_inicio", type="string", format="date-time", example="2025-12-10 08:00:00"),
     *                 @OA\Property(property="fecha_fin", type="string", format="date-time", example="2025-12-10 10:00:00"),
     *                 @OA\Property(property="estado", type="string", example="activa"),
     *                 @OA\Property(property="comentarios", type="string", example="Reserva para clase de programación"),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="name", type="string", example="Juan Pérez")
     *                 ),
     *                 @OA\Property(property="recurso", type="object",
     *                     @OA\Property(property="id", type="integer", example=3),
     *                     @OA\Property(property="nombre", type="string", example="Aula 101")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user    = $this->currentUser();
        $esAdmin = $this->isAdmin();

        if ($esAdmin) {
            $query = Reserva::with(['user', 'recurso']);

            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('recurso_id')) {
                $query->where('recurso_id', $request->recurso_id);
            }

            if ($request->filled('desde')) {
                $query->where('fecha_inicio', '>=', $request->desde);
            }

            if ($request->filled('hasta')) {
                $query->where('fecha_fin', '<=', $request->hasta);
            }

            $reservas = $query->orderBy('fecha_inicio', 'desc')->get();
        } else {
            $reservas = Reserva::with('recurso')
                ->where('user_id', $user->id)
                ->orderBy('fecha_inicio', 'desc')
                ->get();
        }

        return response()->json($reservas);
    }

    // ============= VER UNA RESERVA =============
    /**
     * @OA\Get(
     *     path="/api/reservas/{id}",
     *     tags={"Reservas"},
     *     summary="Ver detalles de una reserva",
     *     description="Obtiene la información detallada de una reserva específica. Los usuarios normales solo pueden ver sus propias reservas.",
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
     *         description="Información de la reserva",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="user_id", type="integer", example=5),
     *             @OA\Property(property="recurso_id", type="integer", example=3),
     *             @OA\Property(property="fecha_inicio", type="string", format="date-time", example="2025-12-10 08:00:00"),
     *             @OA\Property(property="fecha_fin", type="string", format="date-time", example="2025-12-10 10:00:00"),
     *             @OA\Property(property="estado", type="string", example="activa"),
     *             @OA\Property(property="comentarios", type="string", example="Reserva para clase de programación"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=5),
     *                 @OA\Property(property="name", type="string", example="Juan Pérez")
     *             ),
     *             @OA\Property(property="recurso", type="object",
     *                 @OA\Property(property="id", type="integer", example=3),
     *                 @OA\Property(property="nombre", type="string", example="Aula 101")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado para ver esta reserva"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Reserva no encontrada"
     *     )
     * )
     */
    public function show($id)
    {
        $user    = $this->currentUser();
        $esAdmin = $this->isAdmin();

        $reserva = Reserva::with(['user', 'recurso'])->find($id);

        if (! $reserva) {
            return response()->json(['message' => 'Reserva no encontrada'], 404);
        }

        if (! $esAdmin && $reserva->user_id !== $user->id) {
            return response()->json(['message' => 'No autorizado para ver esta reserva'], 403);
        }

        return response()->json($reserva);
    }

    // ============= CREAR RESERVA =============
    /**
     * @OA\Post(
     *     path="/api/reservas",
     *     tags={"Reservas"},
     *     summary="Crear una nueva reserva",
     *     description="Crea una nueva reserva para el usuario autenticado. Valida disponibilidad del recurso y que no haya traslapes con otras reservas activas. Envía notificaciones al usuario y a los administradores.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"recurso_id", "fecha_inicio", "fecha_fin"},
     *             @OA\Property(property="recurso_id", type="integer", example=3, description="ID del recurso a reservar"),
     *             @OA\Property(property="fecha_inicio", type="string", format="date-time", example="2025-12-10 08:00:00", description="Debe ser posterior a la fecha actual"),
     *             @OA\Property(property="fecha_fin", type="string", format="date-time", example="2025-12-10 10:00:00", description="Debe ser posterior a fecha_inicio"),
     *             @OA\Property(property="comentarios", type="string", example="Reserva para clase de programación", maxLength=500)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Reserva creada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reserva creada correctamente"),
     *             @OA\Property(property="reserva", type="object",
     *                 @OA\Property(property="id", type="integer", example=15),
     *                 @OA\Property(property="user_id", type="integer", example=5),
     *                 @OA\Property(property="recurso_id", type="integer", example=3),
     *                 @OA\Property(property="fecha_inicio", type="string", format="date-time", example="2025-12-10 08:00:00"),
     *                 @OA\Property(property="fecha_fin", type="string", format="date-time", example="2025-12-10 10:00:00"),
     *                 @OA\Property(property="estado", type="string", example="activa"),
     *                 @OA\Property(property="comentarios", type="string", example="Reserva para clase de programación")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación, recurso no disponible o hay traslape con otra reserva"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $user = $this->currentUser();

        $data = $request->validate([
            'recurso_id'    => 'required|exists:recursos,id',
            'fecha_inicio'  => 'required|date_format:Y-m-d H:i:s|after:now',
            'fecha_fin'     => 'required|date_format:Y-m-d H:i:s|after:fecha_inicio',
            'comentarios'   => 'nullable|string|max:500',
        ]);

        $recurso = Recurso::where('id', $data['recurso_id'])
            ->where('estado', 1)
            ->where('disponibilidad_general', true)
            ->first();

        if (! $recurso) {
            return response()->json([
                'message' => 'El recurso no está disponible para reservas (inactivo o no disponible).'
            ], 422);
        }

        $inicio = $data['fecha_inicio'];
        $fin    = $data['fecha_fin'];

        $hayTraslape = Reserva::where('recurso_id', $data['recurso_id'])
            ->where('estado', 'activa')
            ->where(function ($q) use ($inicio, $fin) {
                $q->where('fecha_inicio', '<', $fin)
                    ->where('fecha_fin', '>', $inicio);
            })
            ->exists();

        if ($hayTraslape) {
            return response()->json([
                'message' => 'El recurso ya está reservado en el intervalo seleccionado.'
            ], 422);
        }

        $reserva = Reserva::create([
            'user_id'      => $user->id,
            'recurso_id'   => $data['recurso_id'],
            'fecha_inicio' => $inicio,
            'fecha_fin'    => $fin,
            'estado'       => 'activa',
            'comentarios'  => $data['comentarios'] ?? null,
        ]);

        // Notificación para el usuario (confirmación)
        $this->enviarNotificaciones(
            [$user->id],
            'reserva_creada',
            'Reserva creada correctamente',
            "Has reservado el recurso {$recurso->nombre} del {$inicio} al {$fin}."
        );

        // Notificaciones para admins
        $adminIds = $this->obtenerAdminsIds();
        if (!empty($adminIds)) {
            $this->enviarNotificaciones(
                $adminIds,
                'reserva_creada',
                'Nueva reserva creada',
                "El usuario {$user->name} ha reservado el recurso {$recurso->nombre} del {$inicio} al {$fin}."
            );
        }

        // Historial: creación
        $this->logHistorial(
            $reserva,
            $user->id,
            'creada',
            "Reserva creada para el recurso {$recurso->nombre} del {$inicio} al {$fin}."
        );


        return response()->json([
            'message' => 'Reserva creada correctamente',
            'reserva' => $reserva,
        ], 201);
    }

    // ============= ACTUALIZAR RESERVA (ADMIN O DUEÑO) =============
    /**
     * @OA\Put(
     *     path="/api/reservas/{id}",
     *     tags={"Reservas"},
     *     summary="Actualizar una reserva",
     *     description="Actualiza una reserva existente. Los administradores pueden editar recurso, fechas, estado y comentarios. Los usuarios solo pueden editar fechas y comentarios de sus propias reservas activas.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la reserva a actualizar",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="recurso_id", type="integer", example=4, description="Solo editable por admin"),
     *             @OA\Property(property="fecha_inicio", type="string", format="date-time", example="2025-12-10 09:00:00", description="Para usuarios debe ser posterior a ahora"),
     *             @OA\Property(property="fecha_fin", type="string", format="date-time", example="2025-12-10 11:00:00", description="Debe ser posterior a fecha_inicio"),
     *             @OA\Property(property="estado", type="string", example="activa", enum={"activa", "cancelada"}, description="Solo editable por admin"),
     *             @OA\Property(property="comentarios", type="string", example="Comentarios actualizados", maxLength=500)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reserva actualizada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reserva actualizada correctamente"),
     *             @OA\Property(property="reserva", type="object",
     *                 @OA\Property(property="id", type="integer", example=15),
     *                 @OA\Property(property="user_id", type="integer", example=5),
     *                 @OA\Property(property="recurso_id", type="integer", example=4),
     *                 @OA\Property(property="fecha_inicio", type="string", format="date-time", example="2025-12-10 09:00:00"),
     *                 @OA\Property(property="fecha_fin", type="string", format="date-time", example="2025-12-10 11:00:00"),
     *                 @OA\Property(property="estado", type="string", example="activa"),
     *                 @OA\Property(property="comentarios", type="string", example="Comentarios actualizados")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado para editar esta reserva"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Reserva no encontrada"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación, campos no permitidos, reserva cancelada, o hay traslape"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $user    = $this->currentUser();
        $esAdmin = $this->isAdmin();

        $reserva = Reserva::find($id);

        if (! $reserva) {
            return response()->json(['message' => 'Reserva no encontrada'], 404);
        }

        $esDueno = $reserva->user_id === $user->id;

        if (! $esAdmin && ! $esDueno) {
            return response()->json(['message' => 'No autorizado para editar esta reserva'], 403);
        }

        // Nunca se puede cambiar user_id
        if ($request->has('user_id')) {
            return response()->json([
                'message'        => 'No puede cambiar el usuario de una reserva existente.',
                'invalid_fields' => ['user_id'],
            ], 422);
        }

        // ========== RAMA ADMIN ==========
        if ($esAdmin) {
            $allowedFields = ['recurso_id', 'fecha_inicio', 'fecha_fin', 'estado', 'comentarios'];

            $inputKeys   = array_keys($request->all());
            $extraFields = array_diff($inputKeys, $allowedFields);

            if (!empty($extraFields)) {
                return response()->json([
                    'message'        => 'Solo puede actualizar recurso, fechas, estado y comentarios.',
                    'invalid_fields' => array_values($extraFields),
                ], 422);
            }

            $data = $request->validate([
                'recurso_id'   => 'sometimes|required|exists:recursos,id',
                'fecha_inicio' => 'sometimes|required|date_format:Y-m-d H:i:s',
                'fecha_fin'    => 'sometimes|required|date_format:Y-m-d H:i:s|after:fecha_inicio',
                'estado'       => 'sometimes|required|in:activa,cancelada',
                'comentarios'  => 'nullable|string|max:500',
            ]);

            $nuevoRecursoId   = $data['recurso_id']   ?? $reserva->recurso_id;
            $nuevoInicio      = $data['fecha_inicio'] ?? $reserva->fecha_inicio;
            $nuevoFin         = $data['fecha_fin']    ?? $reserva->fecha_fin;
            $nuevoEstado      = $data['estado']       ?? $reserva->estado;
            $nuevoComentarios = $data['comentarios']  ?? $reserva->comentarios;

            if ($nuevoEstado === 'activa') {
                $recurso = Recurso::where('id', $nuevoRecursoId)
                    ->where('estado', 1)
                    ->where('disponibilidad_general', true)
                    ->first();

                if (! $recurso) {
                    return response()->json([
                        'message' => 'El recurso no está disponible para reservas (inactivo o no disponible).'
                    ], 422);
                }

                $hayTraslape = Reserva::where('recurso_id', $nuevoRecursoId)
                    ->where('estado', 'activa')
                    ->where('id', '!=', $reserva->id)
                    ->where(function ($q) use ($nuevoInicio, $nuevoFin) {
                        $q->where('fecha_inicio', '<', $nuevoFin)
                            ->where('fecha_fin', '>', $nuevoInicio);
                    })
                    ->exists();

                if ($hayTraslape) {
                    return response()->json([
                        'message' => 'El recurso ya está reservado en el nuevo intervalo seleccionado.'
                    ], 422);
                }
            }

            $reserva->recurso_id   = $nuevoRecursoId;
            $reserva->fecha_inicio = $nuevoInicio;
            $reserva->fecha_fin    = $nuevoFin;
            $reserva->estado       = $nuevoEstado;
            $reserva->comentarios  = $nuevoComentarios;
            $reserva->save();

            // Historial: actualización por admin
            $this->logHistorial(
                $reserva,
                $user->id,
                'actualizada_admin',
                "Reserva actualizada por admin. Estado: {$reserva->estado}, fechas: {$reserva->fecha_inicio} - {$reserva->fecha_fin}."
            );

            // Notificar al dueño si el admin edita
            if ($reserva->user_id !== $user->id) {
                $this->enviarNotificaciones(
                    [$reserva->user_id],
                    'reserva_actualizada',
                    'Tu reserva ha sido modificada por un administrador',
                    "Tu reserva del recurso {$reserva->recurso->nombre} ha sido modificada por un administrador."
                );
            }

            return response()->json([
                'message' => 'Reserva actualizada correctamente',
                'reserva' => $reserva,
            ]);
        }

        // ========== RAMA DUEÑO (USUARIO NORMAL) ==========
        if ($reserva->estado === 'cancelada') {
            return response()->json([
                'message' => 'No puede modificar una reserva cancelada. Cree una nueva reserva.'
            ], 422);
        }

        $allowedFields = ['fecha_inicio', 'fecha_fin', 'comentarios'];

        $inputKeys   = array_keys($request->all());
        $extraFields = array_diff($inputKeys, $allowedFields);

        if (!empty($extraFields)) {
            return response()->json([
                'message'        => 'Solo puede actualizar fecha de inicio, fecha de fin y comentarios.',
                'invalid_fields' => array_values($extraFields),
            ], 422);
        }

        $data = $request->validate([
            'fecha_inicio' => 'required|date_format:Y-m-d H:i:s|after:now',
            'fecha_fin'    => 'required|date_format:Y-m-d H:i:s|after:fecha_inicio',
            'comentarios'  => 'nullable|string|max:500',
        ]);

        $nuevoInicio      = $data['fecha_inicio'];
        $nuevoFin         = $data['fecha_fin'];
        $nuevoComentarios = $data['comentarios'] ?? $reserva->comentarios;

        $recurso = Recurso::where('id', $reserva->recurso_id)
            ->where('estado', 1)
            ->where('disponibilidad_general', true)
            ->first();

        if (! $recurso) {
            return response()->json([
                'message' => 'El recurso ya no está disponible para reservas (inactivo o no disponible).'
            ], 422);
        }

        $hayTraslape = Reserva::where('recurso_id', $reserva->recurso_id)
            ->where('estado', 'activa')
            ->where('id', '!=', $reserva->id)
            ->where(function ($q) use ($nuevoInicio, $nuevoFin) {
                $q->where('fecha_inicio', '<', $nuevoFin)
                    ->where('fecha_fin', '>', $nuevoInicio);
            })
            ->exists();

        if ($hayTraslape) {
            return response()->json([
                'message' => 'El recurso ya está reservado en el nuevo intervalo seleccionado.'
            ], 422);
        }

        $reserva->fecha_inicio = $nuevoInicio;
        $reserva->fecha_fin    = $nuevoFin;
        $reserva->comentarios  = $nuevoComentarios;
        $reserva->save();

        // Historial: actualización por usuario
        $this->logHistorial(
            $reserva,
            $user->id,
            'actualizada_usuario',
            "Reserva actualizada por el usuario. Nuevas fechas: {$reserva->fecha_inicio} - {$reserva->fecha_fin}."
        );

        // Notificar a admins que el usuario cambió su reserva
        $adminIds = $this->obtenerAdminsIds();
        if (!empty($adminIds)) {
            $this->enviarNotificaciones(
                $adminIds,
                'reserva_actualizada',
                'Reserva modificada por el usuario',
                "El usuario {$user->name} ha cambiado la reserva del recurso {$reserva->recurso->nombre}."
            );
        }

        return response()->json([
            'message' => 'Reserva actualizada correctamente',
            'reserva' => $reserva,
        ]);
    }

    // ============= CANCELAR RESERVA (ADMIN O DUEÑO) =============
    /**
     * @OA\Post(
     *     path="/api/reservas/{id}/cancelar",
     *     tags={"Reservas"},
     *     summary="Cancelar una reserva",
     *     description="Cambia el estado de una reserva a 'cancelada'. Los usuarios pueden cancelar sus propias reservas y los administradores pueden cancelar cualquier reserva. Envía notificaciones y registra en el historial.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la reserva a cancelar",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reserva cancelada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reserva cancelada correctamente"),
     *             @OA\Property(property="reserva", type="object",
     *                 @OA\Property(property="id", type="integer", example=15),
     *                 @OA\Property(property="user_id", type="integer", example=5),
     *                 @OA\Property(property="recurso_id", type="integer", example=3),
     *                 @OA\Property(property="fecha_inicio", type="string", format="date-time", example="2025-12-10 08:00:00"),
     *                 @OA\Property(property="fecha_fin", type="string", format="date-time", example="2025-12-10 10:00:00"),
     *                 @OA\Property(property="estado", type="string", example="cancelada"),
     *                 @OA\Property(property="comentarios", type="string", example="Reserva para clase de programación")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado para cancelar esta reserva"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Reserva no encontrada"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="La reserva ya está cancelada"
     *     )
     * )
     */
    public function cancel($id)
    {
        $user    = $this->currentUser();
        $esAdmin = $this->isAdmin();

        $reserva = Reserva::find($id);

        if (! $reserva) {
            return response()->json(['message' => 'Reserva no encontrada'], 404);
        }

        $esDueno = $reserva->user_id === $user->id;

        if (! $esAdmin && ! $esDueno) {
            return response()->json(['message' => 'No autorizado para cancelar esta reserva'], 403);
        }

        if ($reserva->estado === 'cancelada') {
            return response()->json(['message' => 'La reserva ya está cancelada'], 422);
        }

        $reserva->estado = 'cancelada';
        $reserva->save();

        // Historial: cancelación
        if ($esAdmin && ! $esDueno) {
            $this->logHistorial(
                $reserva,
                $user->id,
                'cancelada_admin',
                "Reserva cancelada por el administrador {$user->name}."
            );
        } elseif (! $esAdmin && $esDueno) {
            $this->logHistorial(
                $reserva,
                $user->id,
                'cancelada_usuario',
                "Reserva cancelada por el usuario {$user->name}."
            );
        }


        // Notificaciones según quién cancela
        if ($esAdmin && ! $esDueno) {
            // Admin cancela la reserva de otro usuario
            $this->enviarNotificaciones(
                [$reserva->user_id],
                'reserva_cancelada',
                'Tu reserva ha sido cancelada por un administrador',
                "Tu reserva del recurso {$reserva->recurso->nombre} ha sido cancelada por un administrador."
            );
        } elseif (! $esAdmin && $esDueno) {
            // Usuario cancela su propia reserva -> avisar admins
            $adminIds = $this->obtenerAdminsIds();
            if (!empty($adminIds)) {
                $this->enviarNotificaciones(
                    $adminIds,
                    'reserva_cancelada',
                    'Reserva cancelada por el usuario',
                    "El usuario {$user->name} ha cancelado su reserva del recurso {$reserva->recurso->nombre}."
                );
            }
        }

        return response()->json([
            'message' => 'Reserva cancelada correctamente',
            'reserva' => $reserva,
        ]);
    }
}
