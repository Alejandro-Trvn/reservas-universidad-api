<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoRecurso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Tipos de Recurso",
 *     description="Gestión de tipos de recursos (aulas, laboratorios, equipos, etc.)"
 * )
 */
class TipoRecursoController extends Controller
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

    // Listar tipos (cualquier autenticado)
    /**
     * @OA\Get(
     *     path="/api/tipos-recurso",
     *     tags={"Tipos de Recurso"},
     *     summary="Listar tipos de recursos activos",
     *     description="Obtiene la lista de todos los tipos de recursos con estado activo (cualquier usuario autenticado).",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de tipos de recursos",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nombre", type="string", example="Aula"),
     *                 @OA\Property(property="descripcion", type="string", example="Salas de clase para cursos"),
     *                 @OA\Property(property="estado", type="integer", example=1)
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $tipos = TipoRecurso::where('estado', 1)->get();
        return response()->json($tipos);
    }

    // Ver un tipo (cualquier autenticado)
    /**
     * @OA\Get(
     *     path="/api/tipos-recurso/{id}",
     *     tags={"Tipos de Recurso"},
     *     summary="Ver detalles de un tipo de recurso",
     *     description="Obtiene la información de un tipo de recurso específico (cualquier usuario autenticado).",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del tipo de recurso",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Información del tipo de recurso",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="nombre", type="string", example="Laboratorio"),
     *             @OA\Property(property="descripcion", type="string", example="Laboratorios de computación"),
     *             @OA\Property(property="estado", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tipo de recurso no encontrado"
     *     )
     * )
     */
    public function show($id)
    {
        $tipo = TipoRecurso::find($id);

        if (! $tipo || $tipo->estado == 2) {
            return response()->json(['message' => 'Tipo de recurso no encontrado'], 404);
        }

        return response()->json($tipo);
    }

    // Crear (solo admin)
    /**
     * @OA\Post(
     *     path="/api/tipos-recurso",
     *     tags={"Tipos de Recurso"},
     *     summary="Crear un nuevo tipo de recurso",
     *     description="Crea un nuevo tipo de recurso en el sistema (solo administradores).",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre"},
     *             @OA\Property(property="nombre", type="string", example="Auditorio", maxLength=50),
     *             @OA\Property(property="descripcion", type="string", example="Espacios para eventos y conferencias", maxLength=255)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tipo de recurso creado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=5),
     *             @OA\Property(property="nombre", type="string", example="Auditorio"),
     *             @OA\Property(property="descripcion", type="string", example="Espacios para eventos y conferencias"),
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
            'nombre'      => 'required|string|max:50',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $tipo = TipoRecurso::create($data);

        return response()->json($tipo, 201);
    }

    // Actualizar (solo admin)
    // ================== UPDATE ==================
    /**
     * @OA\Put(
     *     path="/api/tipos-recurso/{id}",
     *     tags={"Tipos de Recurso"},
     *     summary="Actualizar un tipo de recurso",
     *     description="Actualiza un tipo de recurso (solo administradores). Si tiene recursos asociados, solo permite actualizar el nombre.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del tipo de recurso a actualizar",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nombre", type="string", example="Aula Actualizada", maxLength=50),
     *             @OA\Property(property="descripcion", type="string", example="Nueva descripción", maxLength=255),
     *             @OA\Property(property="estado", type="integer", example=1, enum={0, 1, 2}, description="0=inactivo, 1=activo, 2=eliminado (solo si no tiene recursos asociados)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de recurso actualizado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Tipo de recurso actualizado correctamente"),
     *             @OA\Property(property="tipo", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nombre", type="string", example="Aula Actualizada"),
     *                 @OA\Property(property="descripcion", type="string", example="Nueva descripción"),
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
     *         description="Tipo de recurso no encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación o intento de editar campos no permitidos cuando tiene recursos asociados"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $this->ensureIsAdmin();

        $tipo = TipoRecurso::find($id);

        if (! $tipo || $tipo->estado == 2) {
            return response()->json(['message' => 'Tipo de recurso no encontrado'], 404);
        }

        // ¿Tiene recursos asociados (activos o inactivos)?
        $tieneRecursos = $tipo->recursos()
            ->whereIn('estado', [0, 1])
            ->exists();

        if ($tieneRecursos) {
            $allowedFields = ['nombre'];

            $inputKeys   = array_keys($request->all());
            $extraFields = array_diff($inputKeys, $allowedFields);

            if (!empty($extraFields)) {
                return response()->json([
                    'message'        => 'Este tipo de recurso está asociado a recursos. Solo puede editar el nombre.',
                    'invalid_fields' => array_values($extraFields),
                ], 422);
            }

            $data = $request->validate([
                'nombre' => 'required|string|max:50',
            ]);

            $tipo->update($data);
        } else {
            $data = $request->validate([
                'nombre'      => 'sometimes|required|string|max:50',
                'descripcion' => 'nullable|string|max:255',
                'estado'      => 'nullable|integer|in:0,1,2',
            ]);

            $tipo->update($data);
        }

        return response()->json([
            'message' => 'Tipo de recurso actualizado correctamente',
            'tipo'    => $tipo,
        ]);
    }

    // Eliminar (solo admin)
    // ================== DESTROY ==================
    /**
     * @OA\Delete(
     *     path="/api/tipos-recurso/{id}",
     *     tags={"Tipos de Recurso"},
     *     summary="Eliminar un tipo de recurso",
     *     description="Elimina lógicamente un tipo de recurso (cambia su estado a 2) solo si no tiene recursos asociados (solo administradores).",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del tipo de recurso a eliminar",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de recurso eliminado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Tipo de recurso eliminado correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tipo de recurso no encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="No se puede eliminar porque tiene recursos asociados"
     *     )
     * )
     */
    public function destroy($id)
    {
        $this->ensureIsAdmin();

        $tipo = TipoRecurso::find($id);

        if (! $tipo || $tipo->estado == 2) {
            return response()->json(['message' => 'Tipo de recurso no encontrado'], 404);
        }

        $tieneRecursos = $tipo->recursos()
            ->whereIn('estado', [0, 1])
            ->exists();

        if ($tieneRecursos) {
            return response()->json([
                'message' => 'No se puede eliminar este tipo de recurso porque tiene recursos asociados. ' .
                    'Primero debe eliminar o inactivar esos recursos.'
            ], 422);
        }

        $tipo->estado = 2; // eliminado soft
        $tipo->save();

        return response()->json(['message' => 'Tipo de recurso eliminado correctamente']);
    }
}
