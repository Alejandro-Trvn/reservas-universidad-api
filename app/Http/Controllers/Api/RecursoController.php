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

        if (! $user || ! $user->role || $user->role->nombre !== 'admin') {
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

        if (! $recurso || $recurso->estado == 2) {
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
            'tipo_recurso_id'        => 'required|exists:tipos_recursos,id',
            'nombre'                 => 'required|string|max:100',
            'descripcion'            => 'nullable|string',
            'ubicacion'              => 'nullable|string|max:150',
            'capacidad'              => 'nullable|integer|min:1',
            'disponibilidad_general' => 'nullable|boolean',
        ]);

        if (! isset($data['disponibilidad_general'])) {
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

        if (! $recurso || $recurso->estado == 2) {
            return response()->json(['message' => 'Recurso no encontrado'], 404);
        }

        $tieneReservas = $recurso->reservas()
            ->where('estado', 'activa')
            ->exists();

        if ($tieneReservas) {
            $allowedFields = ['nombre', 'descripcion', 'ubicacion'];

            $inputKeys   = array_keys($request->all());
            $extraFields = array_diff($inputKeys, $allowedFields);

            if (!empty($extraFields)) {
                return response()->json([
                    'message'        => 'Este recurso tiene reservas asociadas. Solo puede editar nombre, descripción y ubicación.',
                    'invalid_fields' => array_values($extraFields),
                ], 422);
            }

            $data = $request->validate([
                'nombre'      => 'required|string|max:100',
                'descripcion' => 'nullable|string',
                'ubicacion'   => 'nullable|string|max:150',
            ]);

            $recurso->update($data);
        } else {
            $data = $request->validate([
                'tipo_recurso_id'        => 'sometimes|required|exists:tipos_recursos,id',
                'nombre'                 => 'sometimes|required|string|max:100',
                'descripcion'            => 'nullable|string',
                'ubicacion'              => 'nullable|string|max:150',
                'capacidad'              => 'nullable|integer|min:1',
                'disponibilidad_general' => 'nullable|boolean',
                'estado'                 => 'nullable|integer|in:0,1,2',
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

        if (! $recurso || $recurso->estado == 2) {
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
}
