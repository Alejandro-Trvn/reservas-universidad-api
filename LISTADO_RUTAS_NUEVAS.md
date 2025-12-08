# 🗺️ Listado de Rutas Nuevas - API Reservas

## ✅ Rutas Implementadas (7 nuevas rutas)

### 📦 RECURSOS - Nuevas Rutas (4)

#### 1. Búsqueda Avanzada
```
GET /api/recursos/busqueda-avanzada
```
**Controlador**: `Api\RecursoController@busquedaAvanzada`  
**Descripción**: Buscar recursos por capacidad, ubicación, tipo y nombre  
**Parámetros Query**:
- `tipo_recurso_id` (opcional)
- `capacidad_min` (opcional)
- `ubicacion` (opcional)
- `nombre` (opcional)

---

#### 2. Recursos Más Utilizados
```
GET /api/recursos/reportes/mas-utilizados
```
**Controlador**: `Api\RecursoController@recursosMasUtilizados`  
**Descripción**: Reporte de recursos ordenados por número de reservas  
**Parámetros Query**:
- `limite` (opcional, default: 10)
- `fecha_desde` (opcional)
- `fecha_hasta` (opcional)

---

#### 3. Recursos Disponibles por Tipo
```
GET /api/recursos/tipo/{tipoId}/disponibles
```
**Controlador**: `Api\RecursoController@recursosPorTipoDisponibles`  
**Descripción**: Lista recursos disponibles de un tipo específico  
**Parámetros Path**:
- `tipoId` (requerido) - ID del tipo de recurso

**Parámetros Query**:
- `fecha_inicio` (opcional)
- `fecha_fin` (opcional)

---

#### 4. Verificar Disponibilidad de Recurso
```
GET /api/recursos/{id}/disponibilidad
```
**Controlador**: `Api\RecursoController@verificarDisponibilidad`  
**Descripción**: Verifica si un recurso está disponible en un rango de fechas  
**Parámetros Path**:
- `id` (requerido) - ID del recurso

**Parámetros Query**:
- `fecha_inicio` (requerido) - Formato: Y-m-d H:i:s
- `fecha_fin` (requerido) - Formato: Y-m-d H:i:s

---

### 📋 RESERVAS - Nuevas Rutas (3)

#### 1. Verificar Conflictos de Reserva
```
POST /api/reservas/verificar-conflictos
```
**Controlador**: `Api\ReservaController@verificarConflictos`  
**Descripción**: Verifica si hay conflictos de reserva para un recurso  
**Body (JSON)**:
```json
{
  "recurso_id": 1,
  "fecha_inicio": "2024-12-10 08:00:00",
  "fecha_fin": "2024-12-10 10:00:00",
  "reserva_id_excluir": 5  // opcional
}
```

---

#### 2. Estadísticas de Uso
```
GET /api/reservas/reportes/estadisticas
```
**Controlador**: `Api\ReservaController@estadisticasUso`  
**Descripción**: Estadísticas generales del sistema de reservas  
**Parámetros Query**:
- `fecha_desde` (opcional)
- `fecha_hasta` (opcional)

---

#### 3. Reservas por Usuario
```
GET /api/reservas/reportes/por-usuario/{userId}
```
**Controlador**: `Api\ReservaController@reservasPorUsuario`  
**Descripción**: Reporte completo de reservas de un usuario específico  
**Parámetros Path**:
- `userId` (requerido) - ID del usuario

**Parámetros Query**:
- `estado` (opcional) - "activa" o "cancelada"
- `fecha_desde` (opcional)
- `fecha_hasta` (opcional)

---

## 📊 Resumen de Rutas por Método HTTP

| Método | Cantidad | Rutas |
|--------|----------|-------|
| **GET** | 6 | busqueda-avanzada, reportes/mas-utilizados, tipo/{tipoId}/disponibles, {id}/disponibilidad, reportes/estadisticas, reportes/por-usuario/{userId} |
| **POST** | 1 | verificar-conflictos |

---

## 🔐 Autenticación

**TODAS** las rutas nuevas requieren autenticación JWT.

**Header requerido**:
```
Authorization: Bearer {tu_token_jwt}
```

---

## 📝 Ejemplos de Uso

### Ejemplo 1: Buscar aulas con capacidad mínima de 30
```bash
GET /api/recursos/busqueda-avanzada?tipo_recurso_id=1&capacidad_min=30
```

### Ejemplo 2: Ver recursos más utilizados de este año
```bash
GET /api/recursos/reportes/mas-utilizados?fecha_desde=2024-01-01&fecha_hasta=2024-12-31&limite=10
```

### Ejemplo 3: Verificar disponibilidad de un aula
```bash
GET /api/recursos/1/disponibilidad?fecha_inicio=2024-12-10 08:00:00&fecha_fin=2024-12-10 10:00:00
```

### Ejemplo 4: Verificar conflictos antes de crear una reserva
```bash
POST /api/reservas/verificar-conflictos
Content-Type: application/json

{
  "recurso_id": 1,
  "fecha_inicio": "2024-12-10 08:00:00",
  "fecha_fin": "2024-12-10 10:00:00"
}
```

### Ejemplo 5: Obtener estadísticas del último mes
```bash
GET /api/reservas/reportes/estadisticas?fecha_desde=2024-11-01&fecha_hasta=2024-12-01
```

### Ejemplo 6: Ver todas las reservas de un usuario
```bash
GET /api/reservas/reportes/por-usuario/5
```

### Ejemplo 7: Ver solo reservas activas de un usuario
```bash
GET /api/reservas/reportes/por-usuario/5?estado=activa
```

---

## 🗂️ Listado Completo de Rutas API

### Rutas de Recursos (Total: 10)

| # | Método | Ruta | Descripción | Nuevo |
|---|--------|------|-------------|-------|
| 1 | GET | /api/recursos | Listar recursos | ❌ |
| 2 | POST | /api/recursos | Crear recurso | ❌ |
| 3 | GET | /api/recursos/busqueda-avanzada | Búsqueda avanzada | ✅ |
| 4 | GET | /api/recursos/reportes/mas-utilizados | Recursos más usados | ✅ |
| 5 | GET | /api/recursos/tipo/{tipoId}/disponibles | Recursos disponibles por tipo | ✅ |
| 6 | GET | /api/recursos/{id} | Ver recurso | ❌ |
| 7 | PUT | /api/recursos/{id} | Actualizar recurso | ❌ |
| 8 | DELETE | /api/recursos/{id} | Eliminar recurso | ❌ |
| 9 | GET | /api/recursos/{id}/disponibilidad | Verificar disponibilidad | ✅ |

### Rutas de Reservas (Total: 9)

| # | Método | Ruta | Descripción | Nuevo |
|---|--------|------|-------------|-------|
| 1 | GET | /api/reservas | Listar reservas | ❌ |
| 2 | POST | /api/reservas | Crear reserva | ❌ |
| 3 | GET | /api/reservas/reportes/estadisticas | Estadísticas de uso | ✅ |
| 4 | GET | /api/reservas/reportes/por-usuario/{userId} | Reservas por usuario | ✅ |
| 5 | POST | /api/reservas/verificar-conflictos | Verificar conflictos | ✅ |
| 6 | GET | /api/reservas/{id} | Ver reserva | ❌ |
| 7 | PUT | /api/reservas/{id} | Actualizar reserva | ❌ |
| 8 | PUT | /api/reservas/{id}/cancelar | Cancelar reserva | ❌ |
| 9 | GET | /api/reservas/{id}/historial | Historial de reserva | ❌ |

---

## ✅ Verificación de Implementación

Todas las rutas han sido verificadas con `php artisan route:list`:

```
✅ GET|HEAD  api/recursos/busqueda-avanzada
✅ GET|HEAD  api/recursos/reportes/mas-utilizados
✅ GET|HEAD  api/recursos/tipo/{tipoId}/disponibles
✅ GET|HEAD  api/recursos/{id}/disponibilidad
✅ GET|HEAD  api/reservas/reportes/estadisticas
✅ GET|HEAD  api/reservas/reportes/por-usuario/{userId}
✅ POST      api/reservas/verificar-conflictos
```

**Total de rutas en la API**: 39  
**Rutas nuevas**: 7  
**Rutas existentes**: 32

---

## 🧪 Pruebas Recomendadas

1. **Swagger UI**: `http://localhost:8000/api/documentation`
   - Cada ruta está documentada con ejemplos
   - Puedes probar directamente desde la interfaz

2. **Postman**:
   - Importa la colección existente
   - Añade las nuevas rutas
   - Configura el token JWT

3. **cURL**:
   - Usa los ejemplos de este documento
   - Reemplaza `{tu_token_jwt}` con tu token real

---

## 📞 Información de Contacto con Backend

**Base URL**: `http://localhost:8000/api`  
**Autenticación**: JWT Bearer Token  
**Formato de respuesta**: JSON  
**Formato de fecha**: `Y-m-d H:i:s` (ejemplo: `2024-12-10 08:00:00`)

---

## 🎉 ¡Listo para Usar!

Todas las funcionalidades están implementadas, probadas y documentadas.

**Próximo paso**: Abre Swagger UI y comienza a probar las nuevas rutas.
