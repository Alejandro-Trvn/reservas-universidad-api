# 📋 Nuevas Funcionalidades Implementadas

## Resumen de Cambios

Se han implementado las siguientes funcionalidades en el sistema de reservas:

### ✅ 1. Disponibilidad
- Ver disponibilidad de un recurso en un rango de fechas
- Ver recursos disponibles por tipo

### ✅ 2. Reportes
- Reservas por usuario
- Recursos más utilizados
- Estadísticas de uso

### ✅ 3. Búsqueda Avanzada
- Buscar recursos por capacidad, ubicación, tipo

### ✅ 4. Validaciones
- Verificar disponibilidad antes de reservar
- Ver conflictos de reserva

---

## 📂 Archivos Modificados

### 1. **routes/api.php**
   - **Ubicación**: `routes/api.php`
   - **Cambios**:
     - Se añadieron 4 rutas nuevas en el grupo de recursos:
       - `GET /recursos/{id}/disponibilidad` - Verificar disponibilidad de un recurso
       - `GET /recursos/tipo/{tipoId}/disponibles` - Listar recursos disponibles por tipo
       - `GET /recursos/busqueda-avanzada` - Búsqueda avanzada de recursos
       - `GET /recursos/reportes/mas-utilizados` - Reporte de recursos más utilizados
     
     - Se añadieron 3 rutas nuevas en el grupo de reservas:
       - `POST /reservas/verificar-conflictos` - Verificar conflictos de reserva
       - `GET /reservas/reportes/por-usuario/{userId}` - Reporte de reservas por usuario
       - `GET /reservas/reportes/estadisticas` - Estadísticas generales de uso

### 2. **app/Http/Controllers/Api/RecursoController.php**
   - **Ubicación**: `app/Http/Controllers/Api/RecursoController.php`
   - **Métodos añadidos**:
     
     #### a) `verificarDisponibilidad(Request $request, $id)`
     - **Propósito**: Verifica si un recurso está disponible en un rango de fechas
     - **Parámetros**: 
       - `id` (path): ID del recurso
       - `fecha_inicio` (query): Fecha de inicio
       - `fecha_fin` (query): Fecha de fin
     - **Retorna**: Información de disponibilidad y lista de reservas existentes en el rango
     
     #### b) `recursosPorTipoDisponibles(Request $request, $tipoId)`
     - **Propósito**: Lista recursos disponibles de un tipo específico
     - **Parámetros**:
       - `tipoId` (path): ID del tipo de recurso
       - `fecha_inicio` (query, opcional): Fecha de inicio
       - `fecha_fin` (query, opcional): Fecha de fin
     - **Retorna**: Lista de recursos disponibles del tipo solicitado
     
     #### c) `busquedaAvanzada(Request $request)`
     - **Propósito**: Búsqueda avanzada de recursos por múltiples criterios
     - **Parámetros**:
       - `tipo_recurso_id` (query, opcional): ID del tipo de recurso
       - `capacidad_min` (query, opcional): Capacidad mínima
       - `ubicacion` (query, opcional): Búsqueda parcial en ubicación
       - `nombre` (query, opcional): Búsqueda parcial en nombre
     - **Retorna**: Lista de recursos que coinciden con los criterios
     
     #### d) `recursosMasUtilizados(Request $request)`
     - **Propósito**: Reporte de recursos más utilizados
     - **Parámetros**:
       - `limite` (query, opcional, default: 10): Número de recursos a retornar
       - `fecha_desde` (query, opcional): Fecha de inicio del periodo
       - `fecha_hasta` (query, opcional): Fecha de fin del periodo
     - **Retorna**: Lista de recursos ordenados por número de reservas

### 3. **app/Http/Controllers/Api/ReservaController.php**
   - **Ubicación**: `app/Http/Controllers/Api/ReservaController.php`
   - **Métodos añadidos**:
     
     #### a) `verificarConflictos(Request $request)`
     - **Propósito**: Verifica si hay conflictos de reserva para un recurso
     - **Parámetros**:
       - `recurso_id` (body): ID del recurso
       - `fecha_inicio` (body): Fecha de inicio
       - `fecha_fin` (body): Fecha de fin
       - `reserva_id_excluir` (body, opcional): ID de reserva a excluir
     - **Retorna**: Información sobre conflictos encontrados
     
     #### b) `reservasPorUsuario(Request $request, $userId)`
     - **Propósito**: Reporte de reservas de un usuario específico
     - **Parámetros**:
       - `userId` (path): ID del usuario
       - `estado` (query, opcional): Filtrar por estado
       - `fecha_desde` (query, opcional): Fecha de inicio del periodo
       - `fecha_hasta` (query, opcional): Fecha de fin del periodo
     - **Retorna**: Información detallada de todas las reservas del usuario
     
     #### c) `estadisticasUso(Request $request)`
     - **Propósito**: Obtiene estadísticas generales del sistema
     - **Parámetros**:
       - `fecha_desde` (query, opcional): Fecha de inicio del periodo
       - `fecha_hasta` (query, opcional): Fecha de fin del periodo
     - **Retorna**: Estadísticas completas incluyendo:
       - Totales de reservas (activas, canceladas)
       - Promedios (reservas por usuario, por recurso)
       - Top 5 usuarios con más reservas
       - Distribución de reservas por tipo de recurso

---

## 🚀 Cómo Usar las Nuevas Funcionalidades

### 1. Verificar Disponibilidad de un Recurso

**Endpoint**: `GET /api/recursos/{id}/disponibilidad`

**Ejemplo de petición**:
```bash
GET /api/recursos/1/disponibilidad?fecha_inicio=2024-12-10 08:00:00&fecha_fin=2024-12-10 10:00:00
```

**Respuesta**:
```json
{
  "recurso_id": 1,
  "recurso_nombre": "Aula 101",
  "fecha_inicio": "2024-12-10 08:00:00",
  "fecha_fin": "2024-12-10 10:00:00",
  "disponible": true,
  "reservas_existentes": []
}
```

### 2. Listar Recursos Disponibles por Tipo

**Endpoint**: `GET /api/recursos/tipo/{tipoId}/disponibles`

**Ejemplo de petición**:
```bash
GET /api/recursos/tipo/1/disponibles?fecha_inicio=2024-12-10 08:00:00&fecha_fin=2024-12-10 10:00:00
```

### 3. Búsqueda Avanzada de Recursos

**Endpoint**: `GET /api/recursos/busqueda-avanzada`

**Ejemplo de petición**:
```bash
GET /api/recursos/busqueda-avanzada?capacidad_min=30&ubicacion=Edificio A&tipo_recurso_id=1
```

**Respuesta**:
```json
[
  {
    "id": 1,
    "nombre": "Aula 101",
    "capacidad": 35,
    "ubicacion": "Edificio A, Piso 1",
    "tipo_recurso": {
      "id": 1,
      "nombre": "Aula"
    }
  }
]
```

### 4. Recursos Más Utilizados

**Endpoint**: `GET /api/recursos/reportes/mas-utilizados`

**Ejemplo de petición**:
```bash
GET /api/recursos/reportes/mas-utilizados?limite=5&fecha_desde=2024-01-01&fecha_hasta=2024-12-31
```

**Respuesta**:
```json
[
  {
    "id": 1,
    "nombre": "Aula 101",
    "ubicacion": "Edificio A",
    "capacidad": 30,
    "total_reservas": 45,
    "tipo_recurso": {
      "id": 1,
      "nombre": "Aula"
    }
  }
]
```

### 5. Verificar Conflictos de Reserva

**Endpoint**: `POST /api/reservas/verificar-conflictos`

**Ejemplo de petición**:
```json
{
  "recurso_id": 1,
  "fecha_inicio": "2024-12-10 08:00:00",
  "fecha_fin": "2024-12-10 10:00:00"
}
```

**Respuesta**:
```json
{
  "hay_conflicto": false,
  "mensaje": "No hay conflictos. El recurso está disponible en el rango seleccionado.",
  "conflictos": []
}
```

### 6. Reporte de Reservas por Usuario

**Endpoint**: `GET /api/reservas/reportes/por-usuario/{userId}`

**Ejemplo de petición**:
```bash
GET /api/reservas/reportes/por-usuario/5?estado=activa
```

**Respuesta**:
```json
{
  "usuario": {
    "id": 5,
    "name": "Juan Pérez",
    "email": "juan@example.com"
  },
  "total_reservas": 15,
  "reservas_activas": 8,
  "reservas_canceladas": 7,
  "reservas": [
    {
      "id": 1,
      "recurso": "Aula 101",
      "fecha_inicio": "2024-12-10 08:00:00",
      "fecha_fin": "2024-12-10 10:00:00",
      "estado": "activa",
      "comentarios": "Clase de programación"
    }
  ]
}
```

### 7. Estadísticas de Uso del Sistema

**Endpoint**: `GET /api/reservas/reportes/estadisticas`

**Ejemplo de petición**:
```bash
GET /api/reservas/reportes/estadisticas?fecha_desde=2024-01-01&fecha_hasta=2024-12-31
```

**Respuesta**:
```json
{
  "periodo": {
    "desde": "2024-01-01",
    "hasta": "2024-12-31"
  },
  "totales": {
    "total_reservas": 150,
    "reservas_activas": 85,
    "reservas_canceladas": 65
  },
  "promedios": {
    "reservas_por_usuario": 5.2,
    "reservas_por_recurso": 7.5
  },
  "top_usuarios": [
    {
      "usuario": "Juan Pérez",
      "total_reservas": 12
    }
  ],
  "reservas_por_tipo_recurso": [
    {
      "tipo": "Aula",
      "total_reservas": 80
    },
    {
      "tipo": "Laboratorio",
      "total_reservas": 70
    }
  ]
}
```

---

## 📚 Documentación Swagger

Todas las nuevas rutas están documentadas en Swagger. Puedes acceder a la documentación completa en:

**URL**: `http://localhost:8000/api/documentation`

La documentación incluye:
- Descripción detallada de cada endpoint
- Parámetros requeridos y opcionales
- Ejemplos de peticiones y respuestas
- Códigos de estado HTTP
- Autenticación requerida (JWT)

---

## 🔐 Autenticación

Todas las nuevas rutas requieren autenticación JWT. Asegúrate de incluir el token en el header:

```
Authorization: Bearer {tu_token_jwt}
```

---

## 📝 Notas Importantes

1. **Formato de Fechas**: Todas las fechas deben enviarse en formato `Y-m-d H:i:s` (ejemplo: `2024-12-10 08:00:00`)

2. **Permisos**: 
   - Cualquier usuario autenticado puede consultar disponibilidad y hacer búsquedas
   - Los reportes pueden ser consultados por cualquier usuario autenticado
   - Los administradores tienen acceso completo a todas las funcionalidades

3. **Validaciones**:
   - Los rangos de fechas se validan automáticamente
   - Se verifica que `fecha_fin` sea posterior a `fecha_inicio`
   - Se comprueba que los recursos existan y estén activos

4. **Rendimiento**:
   - Los reportes utilizan índices de base de datos para optimizar las consultas
   - Se recomienda usar filtros de fecha para limitar los resultados en reportes grandes

---

## 🧪 Pruebas

Puedes probar todas estas funcionalidades usando:
1. **Swagger UI**: `http://localhost:8000/api/documentation`
2. **Postman**: Importa la colección `API Sistema de Reservas.postman_collection.json`
3. **cURL**: Ejemplos en la documentación de cada endpoint

---
