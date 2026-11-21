# Gestión de Proyectos — API REST con JWT

API REST con operaciones CRUD sobre base de datos, construida con **Laravel 12** y **Eloquent ORM**,
protegida con autenticación **JWT**. Incluye además un CRUD web en Blade y un panel visual para
probar la API sin herramientas externas.

Es la evolución de [gestor-proyectos-auth](https://github.com/Steeein/gestor-proyectos-auth):
la misma base de datos, ahora expuesta como API.

---

## Stack

| Componente | Versión |
|---|---|
| PHP | 8.2 |
| Laravel | 12 |
| Base de datos | MySQL 8 |
| ORM | Eloquent |
| Autenticación | `php-open-source-saver/jwt-auth` 2.8 |

---

## Requisitos previos

- PHP 8.2 o superior
- Composer
- MySQL 8 (o Laragon / XAMPP)

---

## Cómo levantar el proyecto

### 1. Clonar el repositorio

```bash
git clone https://github.com/Steeein/gestor-proyectos-api-jwt.git
```

```bash
cd gestor-proyectos-api-jwt
```

### 2. Instalar dependencias

```bash
composer install
```

> La carpeta `vendor/` no se versiona; este comando la reconstruye.

### 3. Crear el archivo de entorno

```bash
cp .env.example .env
```

En Windows (PowerShell):

```bash
Copy-Item .env.example .env
```

### 4. Generar las claves de la aplicación

```bash
php artisan key:generate
```

```bash
php artisan jwt:secret
```

### 5. Crear la base de datos

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS desarrollo_software_1 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Verifique que `.env` contenga estas credenciales (son solo para desarrollo local):

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=desarrollo_software_1
DB_USERNAME=root
DB_PASSWORD=desarrollo_software_1
```

### 6. Ejecutar migraciones y datos de prueba

```bash
php artisan migrate:fresh --seed
```

Esto crea las tablas `usuarios` y `proyectos`, un usuario administrador y tres proyectos de ejemplo.

### 7. Levantar el servidor

```bash
php artisan serve
```

| Recurso | URL |
|---|---|
| API | `http://127.0.0.1:8000/api` |
| Panel visual de pruebas | `http://127.0.0.1:8000/panel` |
| CRUD web (Blade) | `http://127.0.0.1:8000/proyectos` |

---

## Cómo probar la API

Todos los endpoints del CRUD requieren un token. **Paso 1 obligatorio:**

```
POST http://127.0.0.1:8000/api/auth/login
Content-Type: application/json

{
    "correo": "admin@techsolutions.cl",
    "clave": "desarrollo_software_1"
}
```

La respuesta contiene `data.access_token`. Envíelo en todas las demás peticiones:

```
Authorization: Bearer <access_token>
```

> **Atajo recomendado:** importe `docs/api.postman_collection.json` en Postman,
> ejecute el request **"00 - Login"** y el token se guarda automáticamente para el resto
> de la colección. Luego puede ejecutar cualquier petición sin configurar nada más.

**Alternativa sin Postman:** abra `http://127.0.0.1:8000/panel`, pulse *Iniciar sesión* y
opere el CRUD desde la interfaz. El panel muestra en pantalla el código HTTP de cada respuesta.

---

## Endpoints del CRUD

| Método | Ruta | Éxito | Errores |
|---|---|---|---|
| `POST` | `/api/proyectos` | **201 Created** | 422 validación · 401 sin token |
| `GET` | `/api/proyectos` | **200 OK** (`[]` si no hay datos) | 401 sin token |
| `GET` | `/api/proyectos/{id}` | **200 OK** | **404** si no existe · 401 |
| `PUT` \| `PATCH` | `/api/proyectos/{id}` | **200 OK** | **404** si no existe · 422 · 401 |
| `DELETE` | `/api/proyectos/{id}` | **204 No Content** (cuerpo vacío) | **404** si no existe · 401 |

El listado admite filtros dinámicos opcionales: `?nombre=`, `?estado=`, `?responsable=`.

### Endpoints de autenticación

| Método | Ruta | Protección | Éxito |
|---|---|---|---|
| `POST` | `/api/auth/register` | Pública | 201 · 422 |
| `POST` | `/api/auth/login` | Pública | 200 (token) · 401 |
| `GET` | `/api/auth/me` | JWT | 200 · 401 |
| `POST` | `/api/auth/logout` | JWT | 200 · 401 |
| `POST` | `/api/auth/refresh` | JWT | 200 · 401 |

---

## Ejemplo de uso

**Crear un proyecto (201 Created):**

```bash
curl -i -X POST http://127.0.0.1:8000/api/proyectos -H "Authorization: Bearer TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" -d "{\"nombre\":\"Nuevo CRM\",\"fecha_inicio\":\"2026-09-01\",\"estado\":\"Pendiente\",\"responsable\":\"Diego\",\"monto\":9500000,\"created_by\":1}"
```

Respuesta:

```json
{
    "id": 4,
    "nombre": "Nuevo CRM",
    "fecha_inicio": "2026-09-01",
    "estado": "Pendiente",
    "responsable": "Diego",
    "monto": "9500000.00",
    "created_by": 1,
    "created_at": "2026-09-02T00:40:49.000000Z",
    "updated_at": "2026-09-02T00:40:49.000000Z"
}
```

**Eliminar un proyecto (204 No Content, cuerpo vacío):**

```bash
curl -i -X DELETE http://127.0.0.1:8000/api/proyectos/3 -H "Authorization: Bearer TOKEN" -H "Accept: application/json"
```

---

## Modelo de datos

**Tabla `proyectos`**

| Campo | Tipo | Obligatorio |
|---|---|---|
| `id` | bigint PK | auto |
| `nombre` | varchar(255) | sí |
| `fecha_inicio` | date | sí |
| `estado` | varchar(50) | sí — `Pendiente`, `En Proceso`, `Finalizado`, `Cancelado` |
| `responsable` | varchar(255) | sí |
| `monto` | decimal(12,2) | sí |
| `created_by` | FK → `usuarios.id` | sí |

**Tabla `usuarios`:** `id`, `nombre`, `correo` (único), `clave` (cifrada con bcrypt).

Relación: `Proyecto belongsTo Usuario` · `Usuario hasMany Proyecto`.

---

## Estructura del proyecto

```
app/
├── DTOs/
│   └── ApiResponseDTO.php              Respuesta estándar {code, message, data}
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── ProyectoApiController.php   CRUD de la API (los 5 métodos)
│   │   │   └── AuthApiController.php       Autenticación JWT
│   │   ├── ProyectoController.php          CRUD web (vistas Blade)
│   │   └── AuthController.php              Login/registro web
│   └── Requests/
│       ├── StoreProyectoRequest.php        Validaciones POST → 422
│       └── UpdateProyectoRequest.php       Validaciones PUT/PATCH → 422
└── Models/
    ├── Proyecto.php                        Modelo Eloquent + relación
    └── Usuario.php                         Modelo de usuario + JWTSubject

database/
├── migrations/
│   ├── ..._create_usuarios_table.php
│   └── ..._create_proyectos_table.php
└── seeders/
    └── ProyectoSeeder.php

routes/
├── api.php                                 Rutas públicas y protegidas con auth:api
└── web.php                                 Rutas web + /panel

resources/views/
├── api/panel.blade.php                     Panel visual de pruebas de la API
├── proyectos/                              Vistas del CRUD web
└── auth/                                   Login y registro web

docs/
├── api.postman_collection.json             Colección de pruebas
└── capturas/                               Capturas de las respuestas HTTP
```

---

## Comportamiento del CRUD

| Operación | Implementación | Códigos HTTP |
|---|---|---|
| **Insertar** | `ProyectoApiController::store()` + `StoreProyectoRequest` | 201 · 422 |
| **Consultar** | `ProyectoApiController::index()` y `show()` | 200 · 404 |
| **Actualizar** | `ProyectoApiController::update()` + `UpdateProyectoRequest` | 200 · 404 · 422 |
| **Eliminar** | `ProyectoApiController::destroy()` (dentro de una transacción) | 204 · 404 |

Cada método declara su código de respuesta de forma explícita con las constantes
`Response::HTTP_*`, y las validaciones están delegadas a clases Form Request en lugar de
vivir en el controlador.

---

## Decisiones de diseño

**1. `PUT` / `PATCH` devuelven 200, no 201.**
`201 Created` se reserva para la creación de recursos. Una actualización no crea nada nuevo,
así que responde `200 OK` con el recurso ya modificado.

**2. Los endpoints `GET` devuelven el recurso en la raíz del JSON, sin envoltorio.**
Así `GET /api/proyectos` retorna directamente un arreglo (`[]` cuando no hay registros) y el
cliente no tiene que desempaquetar nada. El formato `{code, message, data}` (`ApiResponseDTO`)
se reserva para los endpoints de autenticación, donde la respuesta no es un recurso sino el
resultado de una operación.

**3. `created_by` se completa automáticamente.**
El campo es obligatorio en el modelo, pero si la petición llega con un token válido y no lo
envía, `StoreProyectoRequest::prepareForValidation()` lo rellena con el id del usuario
autenticado.

**4. Toda la API va detrás de `auth:api`.**
El middleware JWT protege las cinco rutas del CRUD; sin token la respuesta es `401`. La
colección de Postman guarda el token tras el login para poder recorrer los endpoints sin
configuración manual.

---

## Credenciales de prueba

| Usuario | Correo | Clave |
|---|---|---|
| Administrador | `admin@techsolutions.cl` | `desarrollo_software_1` |

---

## Notas

- El archivo `.env` no se versiona (contiene claves). Use `.env.example` como plantilla.
- La carpeta `vendor/` no se versiona; se reconstruye con `composer install`.
- Las vistas web (`/proyectos`, `/login`, `/registro`, `/inicio`) se mantienen operativas y
  persisten en la misma base de datos que consume la API.
