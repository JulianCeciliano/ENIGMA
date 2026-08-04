Documento único y canónico de reglas para cualquier desarrollador o asistente que trabaje en este repositorio. Ante cualquier ambigüedad no cubierta aquí, **este archivo manda** y, si algo no queda claro, se pregunta antes de decidir.

Stack: Laravel 13 (PHP 8.4) + Inertia.js v3 + React 19 + TailwindCSS v4 + SQLite

---

## 1. Alcance de modificación (regla más importante)

**Se puede crear/editar:**
- `app/Http/Controllers/**` — cualquier método (GET, POST, PATCH, DELETE), no solo escritura.
- `app/Services/**`
- `app/Models/**`
- `app/Http/Middleware/**`
- `routes/web.php` (y `bootstrap/app.php` si se necesita registrar un alias de middleware nuevo)

**Nunca se toca, bajo ninguna circunstancia:**
- `resources/js/**` (Pages, Components, Layouts, Utils, hooks, types, actions, routes de Wayfinder)
- Ningún archivo `.tsx`, `.ts` del frontend.

**Consecuencia práctica:** si una feature de backend cambia el shape de los datos que se envían a una página Inertia (nuevo campo, campo renombrado, nueva prop), el trabajo termina en el backend. Se debe **indicar explícitamente** en la respuesta qué archivo(s) de `types/*.ts` y qué `Pages/*.tsx` necesitan actualizarse a mano — pero no se editan directamente. Esto es intencional: la sincronización backend↔frontend en este proyecto es manual y responsabilidad de otra persona/proceso.

---

## 2. Estructura de Carpetas

### Backend — `app/`

```
app/
├── Http/
│   ├── Controllers/            Controladores Inertia (web)
│   ├── Api/                     Endpoints JSON (hoy: solo webhook SSO) — namespace App\Http\Api
│   └── Middleware/
├── Services/                    Lógica de dominio y casos de uso
└── Models/                      Eloquent + Concerns/
```

Reglas de ubicación:
- Ninguna lógica de negocio en Controllers o Models si puede vivir en `Services/`.
- Cada recurso de dominio tiene su Service correspondiente, aunque hoy sea pequeño.
- Endpoints de API (JSON, sin Inertia) van bajo `app/Http/Api/`, namespace `App\Http\Api` (no van dentro de `Controllers/`).
- No se crean archivos fuera de esta estructura.

### Frontend — `resources/js/` (referencia únicamente, no editable)

Se documenta para que el asistente sepa qué indicarle al humano que actualice, pero **no se debe generar ni modificar nada aquí**:

```
resources/js/
├── Pages/            Páginas Inertia
├── Layouts/
├── Components/ui/     Genéricos
├── Components/domain/ Específicos de recurso
├── Utils/             Funciones puras
├── hooks/
├── types/             Interfaces TS de dominio + PageProps ← sincronizar a mano tras cambios de backend
├── actions/App/Http/Controllers/   Wayfinder (generado en build, no a mano)
└── routes/            Wayfinder (generado en build, no a mano)
```

---

## 3. Convenciones de Código (PHP)

### 3.1 Formato

- Indentación: **4 espacios**, nunca tabs.
- Llaves en nueva línea (**PSR-12**), en clases, métodos y estructuras de control.
- Espacio después de comas, alrededor de operadores, y entre palabras clave y paréntesis.
- Comillas simples por defecto; comillas dobles solo si hay interpolación.
- Comparaciones estrictas (`===`, `!==`) siempre que sea posible.
- Comentar solo cuando la lógica no sea evidente; evitar comentarios redundantes.
- Un método no debería superar ~30–40 líneas; si lo hace, considerar dividirlo. Cada método, una sola responsabilidad.

### 3.2 Nombres

| Elemento | Convención | Ejemplos |
|---|---|---|
| Clases | PascalCase | `LoanController`, `LoanService`, `Loan`, `AuthenticateWithSSO` |
| Métodos | camelCase, **en inglés**, inicia con verbo, estilo Laravel/REST | `index`, `store`, `show`, `update`, `destroy`, `paginateForUser`, `assignToLoan`, `returnLoan` |
| Variables | camelCase, descriptivas (nunca `$a`, `$x`, `$temp`) | `$fullName`, `$totalAmount`, `$dueDate` |
| Constantes | MAYÚSCULAS con guion bajo | `MAX_ATTEMPTS`, `STATUS_ACTIVE` |

> Nota: los mensajes visibles al usuario (flash messages, textos de validación) sí pueden y deben ir en español, ya que la app es en español. La convención en inglés aplica solo a identificadores de código (nombres de clases, métodos, variables).

### 3.3 Imports y orden de clase

- `use` ordenados alfabéticamente; sin imports sin usar.
- Orden dentro de una clase: 1) Constantes → 2) Propiedades → 3) Constructor → 4) Métodos públicos → 5) Métodos privados.

---

## 4. Flujo de Comunicación Backend ↔ Frontend

### 4.1 Flujo GET (página)

```
Navegador ──GET /loans──> Route (web.php)
        │                        │
        │                   middleware: web (SecurityHeaders, HandleInertiaRequests)
        │                   middleware: sso.auth, permission:*
        │                        ▼
        │                   Controller::index()
        │                        │  inyecta Services vía constructor
        │                        ▼
        │                   Inertia::render('Loans/Index', [props])
        ▼
   Frontend resuelve la página (fuera del alcance del asistente)
```

### 4.2 Flujo de Escrituras (POST/PATCH/DELETE)

```
Request → Route (web.php) → Middleware → Controller (valida + llama Service)
        → RedirectResponse .with('success'|'error')
        → Inertia recarga props + flash messages
```

- Las escrituras se hacen hacia endpoints Laravel (no API JSON), salvo el webhook SSO.
- El backend responde con `redirect()` o página Inertia nueva; nunca JSON manual salvo en `App\Http\Api`.

---

## 5. Responsabilidad por Capa

### 5.1 Middleware — Autenticación y Autorización de Sección

- **Autenticación**: `sso.auth` (`AuthenticateWithSSO`) valida sesión activa + `user->is_active`.
- **Autorización de sección/ruta**: middleware `permission:` (Spatie) sobre grupos de rutas — la regla que aplica a todo un grupo va aquí, nunca repetida en cada Controller.
- No contiene lógica de negocio ni consultas complejas a la base de datos.
- Si rechaza: 401 (no autenticado) o 403 (sin permiso/inactivo), gestionado por el framework. Nunca se reimplementa en el Controller.

### 5.2 Controller — Validación + Orquestación HTTP

1. Valida el input: `$request->validate([...])` inline, o `FormRequest` si la validación supera ~6–8 reglas o se reutiliza en más de un método.
2. Llama al Service pasando **datos ya validados** (arrays/primitivos/modelos), nunca el objeto `Request` completo.
3. Devuelve `Inertia::render(...)` o `redirect()->with(...)`.
4. **No ejecuta ninguna query** (ni `find()`, ni `where()`, ni queries triviales) — toda obtención de datos vive en el Service.
5. No contiene lógica de negocio ni transacciones.
6. Puede tener cualquier método (`index`, `show`, `store`, `update`, `destroy`, acciones custom vía POST como `returnLoan`).

### 5.3 Service — Lógica de Negocio Pura

- Recibe arrays/DTOs/modelos ya resueltos. **Nunca** recibe `Illuminate\Http\Request`.
- No conoce Inertia ni HTTP: no retorna `Response` ni `RedirectResponse`.
- Retorna Models, Collections, arrays, o lanza excepciones de dominio.
- Aquí van transacciones (`DB::transaction`), orquestación entre Services, reglas de negocio.
- Un Service puede depender de otro Service (DI por constructor).
- Excepciones de dominio (`KeyAlreadyAssignedException`, `InsufficientStockException`) se lanzan desde aquí y se capturan en el Controller → `redirect()->with('error', ...)`.

### 5.4 Model — Persistencia

Solo Eloquent: relaciones, `$casts`, scopes, accessors/mutators, métodos de estado simples (`$loan->isOverdue()`). Nada de lógica multi-tabla ni llamadas a Services.

---

## 6. Rutas

- CRUD estándar vía `Route::resource`.
- Acciones no-CRUD con ruta y método explícitos (ej. `POST loans/{loan}/return` → `LoanController::returnLoan`). Nunca se sobrecarga `update` para otra cosa.
- Un Controller por recurso principal. Endpoints de API JSON bajo `App\Http\Api`.
- **Todas las rutas se definen con `Route::middleware([...])->group()`**, nunca sueltas con middleware repetido.

```php
Route::middleware(['sso.auth'])->group(function () {
    Route::prefix('loans')->name('loans.')->group(function () {
        Route::get('/', [LoanController::class, 'index'])->name('index');

        Route::middleware('permission:loans.create')->group(function () {
            Route::post('/', [LoanController::class, 'store'])->name('store');
        });

        Route::middleware('permission:loans.manage')->group(function () {
            Route::post('/{loan}/return', [LoanController::class, 'returnLoan'])->name('return');
        });
    });
});
```

### Rutas de API (JSON)

Las rutas que apuntan a controladores bajo `App\Http\Api` (namespace `App\Http\Api`, no `App\Http\Controllers`) se agrupan aparte, con su propio middleware, y nunca se mezclan con las rutas Inertia del mismo grupo:

```php
Route::prefix('api')->name('api.')->middleware(['sso.auth'])->group(function () {
    Route::post('/sso/webhook', [\App\Http\Api\SsoWebhookController::class, 'handle'])->name('sso.webhook');
});
```

- El controlador vive físicamente en `app/Http/Api/`, con namespace `App\Http\Api` — nunca en `app/Http/Controllers/Api/` ni con namespace `App\Http\Controllers\Api`.
- Aplica el mismo principio de la sección 5.2: el controlador de API valida y orquesta, nunca ejecuta queries ni contiene lógica de negocio; delega a un Service.

### Permisos del Sistema (Spatie)

Estructura obligatoria del nombre del permiso: **`[nombre del sistema].[módulo].[acción]`**.

> Se usa `prestamos` como slug del sistema en los ejemplos (a partir de "Sistema Préstamos TI"). Si el slug oficial es otro, se ajusta aquí y en todos los permisos ya definidos.

| Permiso | Alcance |
|---|---|
| `prestamos.item-types.manage` | CRUD de tipos de ítem |
| `prestamos.inventory.manage` | CRUD de inventario |
| `prestamos.loan-requests.create` | Crear solicitudes de préstamo |
| `prestamos.loan-requests.approve` | Aprobar/rechazar solicitudes |
| `prestamos.loans.create` | Crear préstamos |
| `prestamos.loans.manage` | Devolver/cancelar préstamos |
| `prestamos.asset-assignments.create` | Crear asignaciones de activos |
| `prestamos.documents.view` / `prestamos.documents.manage` | Ver / CRUD de plantillas |
| `prestamos.notifications.manage` | CRUD de destinatarios |
| `prestamos.reports.view` | Ver reportes |

---

## 7. Contrato con el Frontend (sin editarlo)

Aunque el asistente no toca `resources/js/**`, sí debe **respetar el contrato existente** y **avisar** cuando lo rompe:

- El shape de los datos que un Controller envía vía `Inertia::render()` debe coincidir con lo que espera `types/*.ts` — si el asistente cambia ese shape, debe decir explícitamente: *"esto requiere actualizar `types/loan.ts` y posiblemente `Pages/Loans/Index.tsx`"*.
- Todo dato mutable enviado por el frontend llega vía `useForm` de Inertia; los errores de `$request->validate()` (422) llegan automáticamente a `errors` sin mapeo manual — no se debe romper esa convención devolviendo estructuras de error distintas.
- Las URLs del lado frontend se generan con **Wayfinder** a partir de las rutas/Controllers — si se agrega o renombra una ruta, Wayfinder la regenera en el próximo build; el asistente no necesita tocar nada de eso, pero debe usar nombres de ruta estables y explícitos.

---

## 8. Manejo de Errores

| Origen | Mecanismo | Llega al frontend como |
|---|---|---|
| Middleware `sso.auth` (no autenticado) | 401 → redirect a login | Inertia redirige la página completa |
| Middleware `sso.auth` (usuario inactivo) | 403 | Página de error 403 |
| Middleware `permission` (sin permiso) | 403 | Página de error 403 |
| Validación en Controller | 422 + `ValidationException` | `errors` en `useForm` |
| Excepción de negocio en Service | Capturada en Controller → `redirect()->with('error', ...)` | `flash.error` (prop compartida) |
| Excepción no controlada | `app/Exceptions/Handler.php` | Página de error genérica |

---

## 9. Reglas No Negociables

1. Un Service **nunca** recibe `Request` ni retorna `Response`/`RedirectResponse`.
2. La validación de requests vive en el Controller (inline o `FormRequest`), no en el Service.
3. La autorización de sección/ruta va exclusivamente en middleware `permission:`, agrupada por ruta.
4. Ningún Controller ejecuta ninguna query directa — toda obtención de datos vive en el Service.
5. Los Services viven en `app/Services/` (no `app/Http/Services/`).
6. Los nombres de métodos, clases y variables son en inglés, estilo Laravel/REST. Los textos visibles al usuario son en español.
7. **El frontend (`resources/js/**`) no se toca nunca.** Si un cambio de backend rompe el contrato con el frontend, se avisa explícitamente qué archivos requieren actualización manual.
8. No se crean archivos fuera de la estructura de carpetas de la sección 2.
9. No se sobrecargan métodos REST estándar con comportamiento distinto al que su nombre indica.
10. Las rutas se definen con `Route::middleware([...])->group()`, nunca sueltas con middleware repetido.
11. Los controladores de endpoints JSON (sin Inertia) viven en `app/Http/Api/` bajo el namespace `App\Http\Api` — nunca dentro de `app/Http/Controllers/` ni de `App\Http\Controllers`, ni siquiera en un subnamespace `Api`.
12. Todo permiso de Spatie sigue estrictamente el formato `[nombre del sistema].[módulo].[acción]` (ej. `prestamos.loans.manage`) — no se crean permisos con otro formato ni se omite alguno de los tres segmentos.
13. Ante ambigüedad sobre dónde vive una regla (Controller vs Service) o sobre el alcance de un cambio, se pregunta antes de decidir.

---

## 10. Checklist por Feature Nueva (alcance del asistente)

- [ ] Ruta agregada/editada en `routes/web.php` bajo el middleware de auth/permiso correcto
- [ ] Validación en Controller (inline o `FormRequest` si crece)
- [ ] Service recibe solo datos primitivos/modelos, nunca `Request`; vive en `app/Services/`
- [ ] Excepciones de dominio definidas si el Service puede fallar de forma esperada
- [ ] Nombres de clases/métodos/variables siguen la convención (sección 3.2)
- [ ] Ningún archivo de `resources/js/**` fue tocado
- [ ] Si el shape de datos enviado a Inertia cambió: se avisó explícitamente qué `types/*.ts` y `Pages/*.tsx` debe actualizar el humano
