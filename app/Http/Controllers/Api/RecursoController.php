<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recurso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Recursos",
 *     description="Gestión de recursos físicos (aulas, laboratorios, equipos, etc.)"
 * )
 */
class RecursoController extends Controller
{
    private function ensureIsAdmin()
    {
        $user = Auth::guard('api')->user();

        if (!$user || !$user->role || $user->role->nombre !== 'admin') {
            abort(response()->json([
                'message' => 'No autorizado. Solo un administrador puede realizar esta acción.'
            ], 403));
        }
    }

    // Listar recursos (cualquier autenticado, con filtros opcionales)
    /**
     * @OA\Get(
     *     path="/api/recursos",
     *     tags={"Recursos"},
     *     summary="Listar recursos activos",
     *     description="Obtiene la lista de recursos activos con opciones de filtrado por tipo y disponibilidad (cualquier usuario autenticado).",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="tipo_recurso_id",
     *         in="query",
     *         description="Filtrar por ID del tipo de recurso",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="disponible",
     *         in="query",
     *         description="Filtrar por disponibilidad general (0=no disponible, 1=disponible)",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de recursos",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="tipo_recurso_id", type="integer", example=1),
     *                 @OA\Property(property="nombre", type="string", example="Aula 101"),
     *                 @OA\Property(property="descripcion", type="string", example="Aula con capacidad para 30 personas"),
     *                 @OA\Property(property="ubicacion", type="string", example="Edificio A, Primer Piso"),
     *                 @OA\Property(property="capacidad", type="integer", example=30),
     *                 @OA\Property(property="disponibilidad_general", type="boolean", example=true),
     *                 @OA\Property(property="estado", type="integer", example=1),
     *                 @OA\Property(property="tipo_recurso", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nombre", type="string", example="Aula")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Recurso::with('tipoRecurso')
            ->where('estado', 1);

        if ($request->has('tipo_recurso_id')) {
            $query->where('tipo_recurso_id', $request->tipo_recurso_id);
        }

        if ($request->has('disponible')) {
            $disponible = $request->disponible ? 1 : 0;
            $query->where('disponibilidad_general', $disponible);
        }

        $recursos = $query->get();

        return response()->json($recursos);
    }

    // Ver recurso
    /**
     * @OA\Get(
     *     path="/api/recursos/{id}",
     *     tags={"Recursos"},
     *     summary="Ver detalles de un recurso",
     *     description="Obtiene la información detallada de un recurso específico (cualquier usuario autenticado).",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del recurso",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Información del recurso",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="tipo_recurso_id", type="integer", example=1),
     *             @OA\Property(property="nombre", type="string", example="Laboratorio de Computación 1"),
     *             @OA\Property(property="descripcion", type="string", example="Laboratorio con 25 computadoras"),
     *             @OA\Property(property="ubicacion", type="string", example="Edificio B, Segundo Piso"),
     *             @OA\Property(property="capacidad", type="integer", example=25),
     *             @OA\Property(property="disponibilidad_general", type="boolean", example=true),
     *             @OA\Property(property="estado", type="integer", example=1),
     *             @OA\Property(property="tipo_recurso", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nombre", type="string", example="Laboratorio")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Recurso no encontrado"
     *     )
     * )
     */
    public function show($id)
    {
        $recurso = Recurso::with('tipoRecurso')->find($id);

        if (!$recurso || $recurso->estado == 2) {
            return response()->json(['message' => 'Recurso no encontrado'], 404);
        }

        return response()->json($recurso);
    }

    // Crear recurso (solo admin)
    /**
     * @OA\Post(
     *     path="/api/recursos",
     *     tags={"Recursos"},
     *     summary="Crear un nuevo recurso",
     *     description="Crea un nuevo recurso en el sistema (solo administradores).",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tipo_recurso_id", "nombre"},
     *             @OA\Property(property="tipo_recurso_id", type="integer", example=1, description="ID del tipo de recurso (debe existir)"),
     *             @OA\Property(property="nombre", type="string", example="Aula Magna", maxLength=100),
     *             @OA\Property(property="descripcion", type="string", example="Auditorio principal con capacidad para 200 personas"),
     *             @OA\Property(property="ubicacion", type="string", example="Edificio Principal, Tercer Piso", maxLength=150),
     *             @OA\Property(property="capacidad", type="integer", example=200, minimum=1),
     *             @OA\Property(property="disponibilidad_general", type="boolean", example=true, description="Si no se especifica, por defecto es true")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Recurso creado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=10),
     *             @OA\Property(property="tipo_recurso_id", type="integer", example=1),
     *             @OA\Property(property="nombre", type="string", example="Aula Magna"),
     *             @OA\Property(property="descripcion", type="string", example="Auditorio principal con capacidad para 200 personas"),
     *             @OA\Property(property="ubicacion", type="string", example="Edificio Principal, Tercer Piso"),
     *             @OA\Property(property="capacidad", type="integer", example=200),
     *             @OA\Property(property="disponibilidad_general", type="boolean", example=true),
     *             @OA\Property(property="estado", type="integer", example=1)
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
            'tipo_recurso_id' => 'required|exists:tipo_recursos,id',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'ubicacion' => 'nullable|string|max:150',
            'capacidad' => 'nullable|integer|min:1',
            'disponibilidad_general' => 'nullable|boolean',
        ]);

        if (!isset($data['disponibilidad_general'])) {
            $data['disponibilidad_general'] = true;
        }

        $recurso = Recurso::create($data);

        return response()->json($recurso, 201);
    }

    // Actualizar recurso (solo admin)
    // ================== UPDATE ==================
    /**
     * @OA\Put(
     *     path="/api/recursos/{id}",
     *     tags={"Recursos"},
     *     summary="Actualizar un recurso",
     *     description="Actualiza un recurso existente (solo administradores). Si tiene reservas activas, solo permite actualizar nombre, descripción y ubicación.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del recurso a actualizar",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="tipo_recurso_id", type="integer", example=2, description="Solo editable si no hay reservas activas"),
     *             @OA\Property(property="nombre", type="string", example="Aula 101 - Renovada", maxLength=100),
     *             @OA\Property(property="descripcion", type="string", example="Aula renovada con proyector y aire acondicionado"),
     *             @OA\Property(property="ubicacion", type="string", example="Edificio A, Primer Piso, Ala Norte", maxLength=150),
     *             @OA\Property(property="capacidad", type="integer", example=35, minimum=1, description="Solo editable si no hay reservas activas"),
     *             @OA\Property(property="disponibilidad_general", type="boolean", example=true, description="Solo editable si no hay reservas activas"),
     *             @OA\Property(property="estado", type="integer", example=1, enum={0, 1, 2}, description="0=inactivo, 1=activo, 2=eliminado. Solo editable si no hay reservas activas")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recurso actualizado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Recurso actualizado correctamente"),
     *             @OA\Property(property="recurso", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="tipo_recurso_id", type="integer", example=2),
     *                 @OA\Property(property="nombre", type="string", example="Aula 101 - Renovada"),
     *                 @OA\Property(property="descripcion", type="string", example="Aula renovada con proyector y aire acondicionado"),
     *                 @OA\Property(property="ubicacion", type="string", example="Edificio A, Primer Piso, Ala Norte"),
     *                 @OA\Property(property="capacidad", type="integer", example=35),
     *                 @OA\Property(property="disponibilidad_general", type="boolean", example=true),
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
     *         description="Recurso no encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación o intento de editar campos no permitidos cuando tiene reservas activas"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $this->ensureIsAdmin();

        $recurso = Recurso::find($id);

        if (!$recurso || $recurso->estado == 2) {
            return response()->json(['message' => 'Recurso no encontrado'], 404);
        }

        $tieneReservas = $recurso->reservas()
            ->where('estado', 'activa')
            ->exists();

        if ($tieneReservas) {
            $allowedFields = ['nombre', 'descripcion', 'ubicacion'];

            $inputKeys = array_keys($request->all());
            $extraFields = array_diff($inputKeys, $allowedFields);

            if (!empty($extraFields)) {
                return response()->json([
                    'message' => 'Este recurso tiene reservas asociadas. Solo puede editar nombre, descripción y ubicación.',
                    'invalid_fields' => array_values($extraFields),
                ], 422);
            }

            $data = $request->validate([
                'nombre' => 'required|string|max:100',
                'descripcion' => 'nullable|string',
                'ubicacion' => 'nullable|string|max:150',
            ]);

            $recurso->update($data);
        } else {
            $data = $request->validate([
                'tipo_recurso_id' => 'sometimes|required|exists:tipos_recursos,id',
                'nombre' => 'sometimes|required|string|max:100',
                'descripcion' => 'nullable|string',
                'ubicacion' => 'nullable|string|max:150',
                'capacidad' => 'nullable|integer|min:1',
                'disponibilidad_general' => 'nullable|boolean',
                'estado' => 'nullable|integer|in:0,1,2',
            ]);

            $recurso->update($data);
        }

        return response()->json([
            'message' => 'Recurso actualizado correctamente',
            'recurso' => $recurso,
        ]);
    }

    // ================== DESTROY ==================
    /**
     * @OA\Delete(
     *     path="/api/recursos/{id}",
     *     tags={"Recursos"},
     *     summary="Eliminar un recurso",
     *     description="Elimina lógicamente un recurso (cambia su estado a 2) solo si no tiene reservas activas (solo administradores). Si tiene reservas activas, se sugiere marcarlo como inactivo.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del recurso a eliminar",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recurso eliminado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Recurso eliminado correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Recurso no encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="No se puede eliminar porque tiene reservas activas"
     *     )
     * )
     */
    public function destroy($id)
    {
        $this->ensureIsAdmin();

        $recurso = Recurso::find($id);

        if (!$recurso || $recurso->estado == 2) {
            return response()->json(['message' => 'Recurso no encontrado'], 404);
        }

        // ¿Tiene reservas activas?
        $tieneReservasActivas = $recurso->reservas()
            ->where('estado', 'activa')
            ->exists();

        if ($tieneReservasActivas) {
            return response()->json([
                'message' => 'No se puede eliminar este recurso porque tiene reservas activas. ' .
                    'Puede marcarlo como inactivo (estado = 0) para evitar nuevas reservas.'
            ], 422);
        }

        $recurso->estado = 2; // eliminado soft
        $recurso->save();

        return response()->json(['message' => 'Recurso eliminado correctamente']);
    }

    // ================== VERIFICAR DISPONIBILIDAD ==================
    /**
     * @OA\Get(
     *     path="/api/recursos/{id}/disponibilidad",
     *     tags={"Recursos"},
     *     summary="Verificar disponibilidad de un recurso en un rango de fechas",
     *     description="Verifica si un recurso está disponible en un rango específico de fechas, mostrando las reservas existentes en ese periodo",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del recurso",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="fecha_inicio",
     *         in="query",
     *         description="Fecha y hora de inicio (formato: Y-m-d H:i:s)",
     *         required=true,
     *         @OA\Schema(type="string", format="date-time", example="2024-12-10 08:00:00")
     *     ),
     *     @OA\Parameter(
     *         name="fecha_fin",
     *         in="query",
     *         description="Fecha y hora de fin (formato: Y-m-d H:i:s)",
     *         required=true,
     *         @OA\Schema(type="string", format="date-time", example="2024-12-10 10:00:00")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Información de disponibilidad del recurso",
     *         @OA\JsonContent(
     *             @OA\Property(property="recurso_id", type="integer", example=1),
     *             @OA\Property(property="recurso_nombre", type="string", example="Aula 101"),
     *             @OA\Property(property="disponible", type="boolean", example=true),
     *             @OA\Property(property="reservas_existentes", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="fecha_inicio", type="string", example="2024-12-10 08:00:00"),
     *                     @OA\Property(property="fecha_fin", type="string", example="2024-12-10 10:00:00"),
     *                     @OA\Property(property="usuario", type="string", example="Juan Pérez")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Recurso no encontrado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function verificarDisponibilidad(Request $request, $id)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
        ]);

        $recurso = Recurso::find($id);
        if (!$recurso || $recurso->estado == 2) {
            return response()->json(['message' => 'Recurso no encontrado'], 404);
        }

        $fechaInicio = $request->fecha_inicio;
        $fechaFin = $request->fecha_fin;

        // Buscar reservas activas que se traslapen con el rango solicitado
        $reservas = $recurso->reservas()
            ->where('estado', 'activa')
            ->where(function ($query) use ($fechaInicio, $fechaFin) {
                $query->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin])
                    ->orWhereBetween('fecha_fin', [$fechaInicio, $fechaFin])
                    ->orWhere(function ($q) use ($fechaInicio, $fechaFin) {
                        $q->where('fecha_inicio', '<=', $fechaInicio)
                            ->where('fecha_fin', '>=', $fechaFin);
                    });
            })
            ->with('user')
            ->get();

        $disponible = $reservas->isEmpty();

        return response()->json([
            'recurso_id' => $recurso->id,
            'recurso_nombre' => $recurso->nombre,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'disponible' => $disponible,
            'reservas_existentes' => $reservas->map(function ($reserva) {
                return [
                    'id' => $reserva->id,
                    'fecha_inicio' => $reserva->fecha_inicio,
                    'fecha_fin' => $reserva->fecha_fin,
                    'usuario' => $reserva->user->name ?? 'N/A',
                ];
            }),
        ]);
    }

    // ================== RECURSOS DISPONIBLES POR TIPO ==================
    /**
     * @OA\Get(
     *     path="/api/recursos/tipo/{tipoId}/disponibles",
     *     tags={"Recursos"},
     *     summary="Listar recursos disponibles por tipo en un rango de fechas",
     *     description="Obtiene todos los recursos de un tipo específico que están disponibles en un rango de fechas",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="tipoId",
     *         in="path",
     *         description="ID del tipo de recurso",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="fecha_inicio",
     *         in="query",
     *         description="Fecha y hora de inicio (formato: Y-m-d H:i:s)",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time", example="2024-12-10 08:00:00")
     *     ),
     *     @OA\Parameter(
     *         name="fecha_fin",
     *         in="query",
     *         description="Fecha y hora de fin (formato: Y-m-d H:i:s)",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time", example="2024-12-10 10:00:00")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de recursos disponibles",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nombre", type="string", example="Aula 101"),
     *                 @OA\Property(property="capacidad", type="integer", example=30),
     *                 @OA\Property(property="ubicacion", type="string", example="Edificio A"),
     *                 @OA\Property(property="disponible", type="boolean", example=true)
     *             )
     *         )
     *     )
     * )
     */
    public function recursosPorTipoDisponibles(Request $request, $tipoId)
    {
        $recursos = Recurso::where('tipo_recurso_id', $tipoId)
            ->where('estado', 1)
            ->where('disponibilidad_general', true)
            ->get();

        // Si se proporcionan fechas, filtrar por disponibilidad
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $request->validate([
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after:fecha_inicio',
            ]);

            $fechaInicio = $request->fecha_inicio;
            $fechaFin = $request->fecha_fin;

            $recursosDisponibles = $recursos->filter(function ($recurso) use ($fechaInicio, $fechaFin) {
                $reservas = $recurso->reservas()
                    ->where('estado', 'activa')
                    ->where(function ($query) use ($fechaInicio, $fechaFin) {
                        $query->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin])
                            ->orWhereBetween('fecha_fin', [$fechaInicio, $fechaFin])
                            ->orWhere(function ($q) use ($fechaInicio, $fechaFin) {
                                $q->where('fecha_inicio', '<=', $fechaInicio)
                                    ->where('fecha_fin', '>=', $fechaFin);
                            });
                    })
                    ->exists();

                return !$reservas;
            });

            return response()->json($recursosDisponibles->values());
        }

        return response()->json($recursos);
    }

    // ================== BÚSQUEDA AVANZADA ==================
    /**
     * @OA\Get(
     *     path="/api/recursos/busqueda-avanzada",
     *     tags={"Recursos"},
     *     summary="Búsqueda avanzada de recursos",
     *     description="Busca recursos por múltiples criterios: capacidad, ubicación y tipo",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="tipo_recurso_id",
     *         in="query",
     *         description="ID del tipo de recurso",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="capacidad_min",
     *         in="query",
     *         description="Capacidad mínima requerida",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="ubicacion",
     *         in="query",
     *         description="Búsqueda parcial en ubicación (ejemplo: 'Edificio A')",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="nombre",
     *         in="query",
     *         description="Búsqueda parcial en nombre del recurso",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recursos que coinciden con los criterios de búsqueda",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nombre", type="string", example="Aula 101"),
     *                 @OA\Property(property="capacidad", type="integer", example=30),
     *                 @OA\Property(property="ubicacion", type="string", example="Edificio A, Piso 1"),
     *                 @OA\Property(property="tipo_recurso", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nombre", type="string", example="Aula")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function busquedaAvanzada(Request $request)
    {
        $query = Recurso::with('tipoRecurso')
            ->where('estado', 1);

        // Filtrar por tipo de recurso
        if ($request->has('tipo_recurso_id')) {
            $query->where('tipo_recurso_id', $request->tipo_recurso_id);
        }

        // Filtrar por capacidad mínima
        if ($request->has('capacidad_min')) {
            $query->where('capacidad', '>=', $request->capacidad_min);
        }

        // Filtrar por ubicación (búsqueda parcial)
        if ($request->has('ubicacion')) {
            $query->where('ubicacion', 'like', '%' . $request->ubicacion . '%');
        }

        // Filtrar por nombre (búsqueda parcial)
        if ($request->has('nombre')) {
            $query->where('nombre', 'like', '%' . $request->nombre . '%');
        }

        $recursos = $query->get();

        return response()->json($recursos);
    }

    // ================== RECURSOS MÁS UTILIZADOS ==================
    /**
     * @OA\Get(
     *     path="/api/recursos/reportes/mas-utilizados",
     *     tags={"Recursos"},
     *     summary="Reporte de recursos más utilizados",
     *     description="Obtiene los recursos más utilizados basándose en el número de reservas",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="limite",
     *         in="query",
     *         description="Número máximo de recursos a retornar (por defecto 10)",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="fecha_desde",
     *         in="query",
     *         description="Fecha de inicio del periodo (formato: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="fecha_hasta",
     *         in="query",
     *         description="Fecha de fin del periodo (formato: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de recursos ordenados por número de reservas",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nombre", type="string", example="Aula 101"),
     *                 @OA\Property(property="total_reservas", type="integer", example=45),
     *                 @OA\Property(property="tipo_recurso", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nombre", type="string", example="Aula")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function recursosMasUtilizados(Request $request)
    {
        $limite = $request->get('limite', 10);

        $query = Recurso::with('tipoRecurso')
            ->withCount([
                'reservas' => function ($q) use ($request) {
                    if ($request->has('fecha_desde')) {
                        $q->where('fecha_inicio', '>=', $request->fecha_desde);
                    }
                    if ($request->has('fecha_hasta')) {
                        $q->where('fecha_fin', '<=', $request->fecha_hasta);
                    }
                }
            ])
            ->orderBy('reservas_count', 'desc')
            ->limit($limite);

        $recursos = $query->get()->map(function ($recurso) {
            return [
                'id' => $recurso->id,
                'nombre' => $recurso->nombre,
                'ubicacion' => $recurso->ubicacion,
                'capacidad' => $recurso->capacidad,
                'total_reservas' => $recurso->reservas_count,
                'tipo_recurso' => $recurso->tipoRecurso ? [
                    'id' => $recurso->tipoRecurso->id,
                    'nombre' => $recurso->tipoRecurso->nombre,
                ] : null,
            ];
        });

        return response()->json($recursos);
    }
}

