# Cola de mensajería WhatsApp

Guía rápida para usar la cola de mensajes y para que **cualquier otro módulo de Dolibarr** pueda encolar mensajes (texto, documento/PDF, imagen, vídeo o audio) que se entregarán de forma **escalonada** para evitar que la conexión Baileys sea marcada como SPAM.

---

## 1. Idea general

En vez de enviar el mensaje al instante, se guarda en la tabla `llx_whatsapp_message_queue`. Una **tarea programada** (`processQueue`) recoge los mensajes pendientes y los envía poco a poco: **N mensajes cada X segundos**.

- **Encolar** → `WhatsappQueue::enqueueText() / enqueueDocument() / enqueueAudio()`
- **Enviar** → tarea cron `WhatsappUtils::processQueue()`
- **Ver / gestionar** → menú *Utilidades → Cola de WhatsApp* (`messagequeue_list.php`)

---

## 2. La tarea programada (cadencia)

Método: `WhatsappUtils::processQueue(maxToSend, windowSeconds, jitterSeconds)`

**La cadencia y los reintentos se configuran desde la Configuración del módulo** (sección *Cola de mensajería*). La tarea programada lee esos valores cuando sus parámetros están vacíos (lo normal):

| Ajuste (constante) | Por defecto | Significado |
|--------------------|-------------|-------------|
| Mensajes por ejecución — `WHATSAPP_QUEUE_MAX_PER_RUN` | `1` | Nº máximo de mensajes por ejecución (**N**) |
| Ventana entre lotes — `WHATSAPP_QUEUE_WINDOW_SECONDS` | `150` | Segundos mínimos entre lotes (**X**) |
| Jitter — `WHATSAPP_QUEUE_JITTER_SECONDS` | `30` | Segundos aleatorios añadidos a la ventana |
| Reintentos máximos — `WHATSAPP_QUEUE_MAX_RETRIES` | `3` | Reintentos ante error temporal antes de marcar Fallido |
| Backoff — `WHATSAPP_QUEUE_BACKOFF_SECONDS` | `300` | Espera base entre reintentos (× nº de reintento) |

Ejemplo: N=1, ventana=150, jitter=30 → **como máximo 1 mensaje cada 150-180 s**.

> **Override opcional**: puedes forzar la cadencia poniendo parámetros posicionales en la tarea (`maxToSend, windowSeconds, jitterSeconds`, p.ej. `1, 150, 30`). Si están vacíos, manda la configuración del módulo. Precedencia: **parámetro del cron → constante → valor por defecto**.

> Una **guarda interna** (`WHATSAPP_QUEUE_LAST_SENT`) garantiza el espaciado aunque el cron se ejecute más a menudo: si no ha pasado la ventana, la ejecución no envía nada. Por eso puedes poner el cron a "cada 1 minuto" y dejar que la ventana controle la cadencia real.

La tarea se instala **desactivada**. Actívala en *Configuración → Tareas programadas*.

---

## 3. Estados de un mensaje

| Estado | Constante | Descripción |
|--------|-----------|-------------|
| En cola | `STATUS_PENDING` (0) | Esperando, listo para enviar |
| Procesando | `STATUS_PROCESSING` (1) | Reclamado por una ejecución del cron |
| Enviado | `STATUS_SENT` (2) | Enviado correctamente |
| Reintentando | `STATUS_ERROR` (3) | Error temporal, se reintentará |
| Fallido | `STATUS_FAILED` (4) | Error permanente o reintentos agotados |
| Cancelado | `STATUS_CANCELLED` (5) | Cancelado manualmente |

### Control de errores

- **Permanente** (no se reintenta → `Fallido`): número inválido, HTTP 4xx, configuración faltante, **fichero inexistente o ilegible en disco**, tipo de mensaje desconocido.
- **Temporal** (se reintenta con *backoff* hasta `WHATSAPP_QUEUE_MAX_RETRIES`): errores de red/CURL, timeouts (408), límite de tasa (429), errores de servidor (5xx).

Cada fila se reclama de forma atómica (`claim()`) antes de enviar, así dos ejecuciones simultáneas del cron nunca envían el mismo mensaje dos veces.

---

## 4. Encolar desde otro módulo

Incluye la clase y llama a uno de los métodos estáticos. Devuelven el `rowid` de la fila encolada (`>0`) o un valor negativo si falla.

```php
dol_include_once('/whatsapp/class/whatsappqueue.class.php');

// --- Texto ---
$id = WhatsappQueue::enqueueText('+34600111222', 'Hola, su pedido está listo');

// --- Documento / PDF / imagen / vídeo ---
// El fichero debe existir en disco en el momento del envío.
$id = WhatsappQueue::enqueueDocument(
    '+34600111222',
    '/ruta/absoluta/factura.pdf',   // ruta en disco
    'factura.pdf',                  // nombre mostrado (opcional, por defecto basename)
    'document'                      // 'document' | 'image' | 'video'
);

// --- Audio / nota de voz ---
$id = WhatsappQueue::enqueueAudio('+34600111222', '/ruta/absoluta/nota.ogg', 'Transcripción opcional');

if ($id > 0) {
    // encolado OK
}
```

### Opciones (último parámetro `$options`)

Todos los métodos aceptan un array de opciones final:

```php
WhatsappQueue::enqueueText('+34600111222', 'Texto', array(
    'recipient_name' => 'Juan Pérez',     // nombre para mostrar en la lista
    'fk_soc'         => $societe->id,      // tercero asociado (opcional)
    'fk_contact'     => $contact->id,      // contacto asociado (opcional)
    'fk_actioncomm'  => $event->id,        // evento de agenda asociado (opcional)
    'date_scheduled' => dol_now() + 3600,  // timestamp: no enviar antes de esta fecha
    'priority'       => 50,                // menor = se envía antes (def. 50)
    'instance'       => '',                // nombre de instancia (opcional)
    'max_retries'    => 5,                 // reintentos para este mensaje (vacío = global)
    'source_module'  => 'mimodulo',        // trazabilidad: quién encoló
    'entity'         => $conf->entity,     // multientidad (opcional)
    'fk_user_author' => $user,             // objeto User o id (opcional)
));
```

> El teléfono se normaliza al enviar (se quitan espacios y se añade el prefijo por defecto `WHATSAPP_DEFAULT_COUNTRY_CODE`, p.ej. `34`, si falta el `+`). Puedes pasarlo con o sin `+`.

---

## 5. Encolar los recordatorios en vez de enviarlos al instante

Si activas la constante `WHATSAPP_FORCE_QUEUE`, los recordatorios de WhatsApp (`AC_SEND_WHATSAPP_NOTIFY`) se **encolan** en lugar de enviarse de inmediato, quedando sujetos a la cadencia de la cola.

---

## 6. Probar rápido

Desde *Configuración del módulo → Encolar mensajes de prueba* puedes encolar un texto o subir un documento de prueba y luego ejecutar la tarea `processQueue` para comprobar el flujo completo (incluida la verificación de fichero en disco).

---

## 7. Referencia de ficheros

| Fichero | Rol |
|---------|-----|
| `class/whatsappqueue.class.php` | Modelo + API estática de encolado + helpers de proceso |
| `class/whatsapputils.class.php` | `processQueue()` (cron) y despachador por tipo |
| `lib/whatsapp.lib.php` | `whatsappApiPost()` (transporte) y `whatsappIsPermanentError()` |
| `messagequeue_list.php` / `messagequeue_card.php` | Listado y ficha de gestión |
| `sql/llx_whatsapp_message_queue.sql` | Tabla de la cola |
