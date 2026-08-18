# CHANGELOG WHATSAPP FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## [1.10] - 2026-08-18

### 🔐 Seguridad: corrección de inyección SQL en el webhook (CWE-89)

- **Inyección SQL ciega en el receptor de webhooks (crítico).** El endpoint público `public/webhook/receiver.php` construía la consulta de búsqueda del tercero interpolando directamente el número derivado de `data.key.remoteJid` (entrada del cuerpo JSON, no autenticada). Un atacante remoto podía manipular ese valor para alterar la consulta (demostrable con *time-based blind* `OR SLEEP(n)`). Reportada mediante divulgación coordinada de **INCIBE** (CVSS v4.0 **8.8** — `AV:N/AC:L/AT:N/PR:N/UI:N/VC:H/VI:N/VA:L`).
- **Corrección aplicada (doble barrera):** el identificador se reduce a solo dígitos (`preg_replace('/[^0-9]/','')`) **antes** de intervenir en la consulta, de modo que no puede contener comillas ni palabras clave SQL, y adicionalmente se escapa con `$db->escape()` como defensa en profundidad.
- **Segunda instancia corregida:** el mismo patrón estaba duplicado en el endpoint REST autenticado `class/api_whatsapp.class.php` (`processWebhook`); se aplicó idéntica corrección.
- **Mitigación temporal** para instalaciones que no puedan actualizar de inmediato: desactivar la constante `WHATSAPP_WEBHOOK_ALLOW`.

## [1.9] - 2026-05-27

### ✨ Nueva Funcionalidad: Cola de mensajería con envío escalonado (anti-SPAM)

- **📨 Cola de mensajes WhatsApp**: Nueva tabla `llx_whatsapp_message_queue` y modelo `WhatsappQueue` para encolar envíos (texto, documento/PDF/imagen/vídeo y audio/nota de voz) y entregarlos de forma escalonada, evitando que la conexión Baileys sea marcada como SPAM al enviar muchos mensajes.
- **⏱️ Cadencia configurable "N cada X"**: Nueva tarea programada `processQueue(maxToSend, windowSeconds, jitterSeconds)`. Una guarda interna (`WHATSAPP_QUEUE_LAST_SENT`) garantiza que solo se envía un lote de **N** mensajes cada **X** segundos aunque el cron se ejecute más a menudo, con *jitter* aleatorio opcional para un patrón más humano. Configurable por parámetros del cron y/o por constantes de configuración.
- **🔌 API reutilizable desde otros módulos**: Métodos estáticos `WhatsappQueue::enqueueText()`, `enqueueDocument()` y `enqueueAudio()` para encolar mensajes desde este módulo o desde cualquier otro módulo de Dolibarr.
- **🛡️ Control de errores robusto**: Reclamación atómica (`claim()`) para evitar doble envío entre ejecuciones concurrentes; clasificación de errores **permanentes** (número inválido, HTTP 4xx, configuración faltante y **fichero inexistente en disco**) que se marcan como `Fallido` sin reintentar, frente a errores **temporales** (CURL, timeout, HTTP 5xx, 429) que se reintentan con *backoff* hasta `WHATSAPP_QUEUE_MAX_RETRIES`.
- **📁 Verificación de ficheros**: Antes de enviar audio/documentos se comprueba que el fichero exista y sea legible en disco; si no, se marca como fallo permanente para no reintentar indefinidamente.
- **🖥️ Menú y pantallas de gestión** (en *Utilidades*): Listado con panel de contadores por estado (En cola, Procesando, Reintentando, Fallido, Enviado, Cancelado), filtros, y acciones masivas de **reencolar / cancelar / eliminar**. Ficha de detalle con el último error, código HTTP, reintentos y botones de acción.
- **🔁 Modo cola opcional para recordatorios**: Nueva constante `WHATSAPP_FORCE_QUEUE` que, si se activa, hace que los recordatorios de WhatsApp pasen por la cola en lugar de enviarse inmediatamente.
- **☑️ Casilla "Encolar" en el formulario de envío** de las fichas (terceros, facturas, pedidos, etc.): permite añadir el mensaje a la cola en lugar de enviarlo al instante.
- **📎 Texto + documento en un solo mensaje**: cuando se envía un documento (PDF/imagen/vídeo) junto con texto, el texto (asunto + cuerpo) viaja como pie de foto (*caption*) del documento, en un único mensaje, tanto en envío directo como encolado. Configurable y con límite de seguridad de 1024 caracteres.
- **🧪 Encolar mensajes de prueba desde la configuración**: nueva zona en el setup para encolar un texto o subir un documento de prueba y validar la tarea programada.
- **⚙️ Configuración completa desde el setup**: cadencia (N / ventana / jitter), reintentos, backoff, prefijo de país, reintentos de recordatorios, combinar texto+documento y encolar recordatorios.
- **📝 Mensajes de error de encolado descriptivos**: si falla el encolado se muestra el motivo real (antes solo un mensaje genérico).

### 🗄️ Cambios en Base de Datos
- **Nueva tabla `llx_whatsapp_message_queue`** (charset **utf8mb4** para soportar emojis) con índices, incluido un índice compuesto `(status, date_scheduled, next_retry_date)` para la selección eficiente de los próximos mensajes a enviar.

### 🔐 Permisos
- **3 nuevos permisos**: `queue` → leer / escribir / eliminar.

### ⚙️ Nuevas Constantes de Configuración
- `WHATSAPP_QUEUE_MAX_PER_RUN` (N por ejecución, def. `1`)
- `WHATSAPP_QUEUE_WINDOW_SECONDS` (ventana X en segundos, def. `150`)
- `WHATSAPP_QUEUE_JITTER_SECONDS` (jitter, def. `30`)
- `WHATSAPP_QUEUE_MAX_RETRIES` (reintentos, def. `3`)
- `WHATSAPP_QUEUE_BACKOFF_SECONDS` (backoff base, def. `300`)
- `WHATSAPP_FORCE_QUEUE` (encolar recordatorios, def. desactivado)
- `WHATSAPP_DEFAULT_COUNTRY_CODE` (prefijo de país para normalizar teléfonos, def. `34`)
- `WHATSAPP_REMINDER_MAX_RETRIES` (reintentos de recordatorios, def. `3`)
- `WHATSAPP_COMBINE_TEXT_WITH_DOC` (texto como caption del documento, def. activado)
- Todas configurables desde *Configuración del módulo*.

### 🌐 Traducciones
- **70 nuevas claves** (cola + configuración) añadidas en los 6 idiomas (ES, EN, FR, DE, IT, PT).

### 📝 Archivos Creados
- `sql/llx_whatsapp_message_queue.sql` – Definición de la tabla de la cola
- `sql/llx_whatsapp_message_queue.key.sql` – Índices de la cola
- `class/whatsappqueue.class.php` – Modelo CRUD + API estática de encolado + helpers de procesamiento
- `lib/whatsapp_messagequeue.lib.php` – Tabs de la ficha
- `messagequeue_list.php` – Listado con panel de contadores (tarjetas) y acciones masivas
- `messagequeue_card.php` – Ficha de detalle con acciones
- `docs/cola-mensajeria.md` – Guía de uso de la cola y de encolado desde otros módulos

### 📝 Archivos Modificados
- `core/modules/modWhatsapp.class.php` – Versión 1.9, nueva tarea cron `processQueue` (desactivada por defecto, sin parámetros para usar la config), permisos `queue`, menú en *Utilidades* y constantes
- `class/whatsapputils.class.php` – Método cron `processQueue()`, despachador por tipo con verificación de fichero y *caption*, y soporte de `WHATSAPP_FORCE_QUEUE`
- `class/actions_whatsapp.class.php` – Casilla "Encolar" en el formulario, combinación texto+documento (caption) en envío directo y encolado
- `class/whatsappqueue.class.php` – Mejora de `normalizePhone()` (evita duplicar el prefijo de país) y captura del error real de encolado
- `lib/whatsapp.lib.php` – Nuevas funciones `whatsappApiPost()` (transporte con resultado estructurado) y `whatsappIsPermanentError()` (clasificación de errores)
- `admin/setup.php` – Zona de encolado de prueba y nuevos ajustes de la cola/envío
- `langs/{es_ES,en_US,fr_FR,de_DE,it_IT,pt_PT}/whatsapp.lang` – Nuevas claves de la cola y de configuración (6 idiomas)
- `README.md` – Sección de la cola, parámetros y API de encolado

⚠️ **Nota de instalación**: Requiere desactivar y reactivar el módulo para crear la tabla SQL, los permisos, el menú y la tarea cron. La tarea de la cola se instala **desactivada**: actívala y ajusta sus parámetros antes de usarla.

⚠️ **Charset (upgraders)**: la tabla de la cola usa **utf8mb4** (necesario para emojis). En instalaciones nuevas se crea correctamente. Si por una preversión la tabla quedó en otro charset, ejecuta una vez: `ALTER TABLE llx_whatsapp_message_queue CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`

---

## [1.8] - 2026-05-04

### ⚡ Mejoras de rendimiento

- **🚀 Caché del estado de conexión WhatsApp**: El icono del menú superior ya no realiza una petición HTTP al servidor WhatsApp en cada carga de página. El estado se cachea en sesión durante 300 s (configurable) y solo se refresca por AJAX al abrir el desplegable. Esto reduce drásticamente la latencia de todas las páginas cuando el servidor WhatsApp está lento o caído.
- **⚙️ Nueva configuración `WHATSAPP_STATUS_CACHE_TTL`**: TTL del caché de estado en segundos (por defecto `300`). Se puede ajustar desde la página de configuración del módulo. Usar `0` desactiva la caché.
- **🔄 Refresco del QR vía AJAX**: El desplegable ya no recarga la página entera cada 30 s con `setInterval(window.location.reload)`. Ahora actualiza solo la imagen del QR vía `checkInstanceStatus.php` y solo mientras está abierto. La petición AJAX repuebla la caché de sesión para que la siguiente carga PHP la reutilice.
- **🧹 Invalidación de caché en logout**: Al desconectar la instancia desde el desplegable se invalida la caché para que el siguiente render muestre el estado real.
- **🗃️ Eliminación de N+1 en listados/footer**: Nueva función `countObjectWhatsappMessages()` que usa `SELECT COUNT(*)` en lugar de hidratar todos los `ActionComm` con sus extrafields. Memoizada por request. Aplicada en `getNomUrl` (badge en cada enlace de objeto) y `printCommonFooter` (botón flotante de WhatsApp). En un listado de 100 facturas con 20 mensajes cada una, se pasa de ~2100 consultas SQL a 100.

### 🌐 Traducciones
- **📋 1 nueva clave** añadida en los 6 idiomas (ES, EN, FR, DE, IT, PT):
  - `WHATSAPP_STATUS_CACHE_TTL` y su `Tooltip`

### 📝 Archivos Modificados
- `core/modules/modWhatsapp.class.php` – Versión 1.8
- `class/actions_whatsapp.class.php` – Caché de sesión en `printTopRightMenu`, lazy-load AJAX del desplegable, sustitución de `getObjectWhatsappMessages` por `countObjectWhatsappMessages` en `getNomUrl` y `printCommonFooter`
- `lib/whatsapp.lib.php` – Nueva función `countObjectWhatsappMessages()` con caché por request
- `ajax/checkInstanceStatus.php` – Repuebla la caché de sesión en cada llamada
- `admin/setup.php` – Nueva opción configurable `WHATSAPP_STATUS_CACHE_TTL` (300 s por defecto)
- `langs/{es_ES,en_US,fr_FR,de_DE,it_IT,pt_PT}/whatsapp.lang` – Traducciones de la nueva clave

---

## [1.7] - 2026-03-13

### 🔧 Cambio de infraestructura API

- **🌐 Nuevo endpoint por defecto**: Se actualiza `WHATSAPP_SERVER_URL` para apuntar a `https://newwhapi.easysoft.es`.

### 📝 Archivos Modificados
- `core/modules/modWhatsapp.class.php` – Versión 1.7 y actualización de URL API por defecto

## [1.6] - 2026-03-12

### ✨ Nuevas Funcionalidades

- **📊 Dashboard de resumen de estados**: Barra de resumen visual con badges de colores que muestra conteo de envíos por estado (Enviados, Pendientes, En retraso, Error, Desactivados) con total general.
- **🔄 Reintento automático en error**: El cron job reintenta automáticamente los envíos fallidos hasta un máximo configurable (`WHATSAPP_REMINDER_MAX_RETRIES`, por defecto 3). Se muestra el conteo de reintentos (X/3) junto al badge de error.
- **🔗 Enlace al log de webhook**: Las filas con estado Enviado o Error muestran un enlace directo al registro de webhook para consultar el detalle del envío. Si existe `fk_webhook_log` enlaza a la ficha específica.
- **✏️ Edición inline de fecha**: Las fechas de envíos pendientes o desactivados se pueden editar directamente haciendo clic sobre la fecha/hora, que abre un diálogo jQuery UI con selectores de fecha y hora.
- **🔽 Dropdown colapsable**: La zona de programación de envíos WhatsApp en modo vista ahora es un panel colapsable que se despliega al hacer clic en la cabecera.

### 🗄️ Cambios en Base de Datos
- **`llx_whatsapp_reminder_schedule`**: Nuevas columnas `retry_count` (smallint, DEFAULT 0) y `fk_webhook_log` (integer, NULL)
- **Nuevo índice**: `idx_wa_rs_fk_webhook_log` sobre `fk_webhook_log`

### 🌐 Traducciones
- **📋 5 nuevas claves** añadidas en los 6 idiomas (ES, EN, FR, DE, IT, PT):
  - `WhatsappScheduleRetryCount`, `WhatsappScheduleViewLog`, `WhatsappScheduleClickToEditDate`, `WhatsappScheduleEditDateTitle`, `WhatsappScheduleCannotEditDate`

### 📝 Archivos Modificados
- `core/modules/modWhatsapp.class.php` – Versión 1.6
- `sql/llx_whatsapp_reminder_schedule.sql` – Columnas `retry_count` y `fk_webhook_log`
- `sql/llx_whatsapp_reminder_schedule.key.sql` – Índice `idx_wa_rs_fk_webhook_log`
- `class/reminderschedule.class.php` – Nuevas propiedades, métodos `updateWebhookLog()`, `fetchRetryable()`, `updateReminderDate()`, `getCountsByStatus()`; incremento automático de `retry_count` en `updateStatus()`
- `class/whatsapputils.class.php` – Fase 1.5 de reintento automático en `sendWhAPIReminder()`
- `ajax/reminderSchedule.php` – Acciones `update_date` y `get_summary`; tabla con summary bar, edición inline, enlaces a webhook log
- `tpl/recurrence_view.tpl.php` – Dropdown colapsable, dashboard resumen, edición inline de fecha con jQuery UI dialog, enlaces a webhook log
- `langs/es_ES/whatsapp.lang` – 5 nuevas traducciones
- `langs/en_US/whatsapp.lang` – 5 nuevas traducciones
- `langs/fr_FR/whatsapp.lang` – 5 nuevas traducciones
- `langs/de_DE/whatsapp.lang` – 5 nuevas traducciones
- `langs/it_IT/whatsapp.lang` – 5 nuevas traducciones
- `langs/pt_PT/whatsapp.lang` – 5 nuevas traducciones

⚠️ **Nota**: Requiere reiniciar el módulo para recrear las tablas SQL con las nuevas columnas.

---

## [1.5] - 2026-03-12

### ✨ Nueva Funcionalidad: Sistema de Programación Recurrente de Envíos WhatsApp
- **📅 Tabla `llx_whatsapp_reminder_schedule`**: Nueva tabla dedicada para almacenar fechas programadas de envío con campos: fecha, estado, fecha de envío, mensaje de error, fecha de creación.
- **🔁 Diseñador de recurrencia**: Interfaz visual para generar múltiples fechas de envío automáticamente basadas en frecuencia (semanas, meses, años) y número de repeticiones.
- **➕ Añadir fecha manual**: Posibilidad de añadir fechas individuales de envío junto al generador de recurrencia.
- **📋 Vista de programaciones**: Tabla interactiva con estado (Pendiente, Enviado, Error, Desactivado), detección de envíos en retraso, y acciones de activar/desactivar/eliminar.
- **⚡ Gestión AJAX**: Todas las operaciones de la vista (generar, añadir, toggle, eliminar, listar) funcionan vía AJAX sin recarga de página.
- **📝 Plantilla modo edición** (`recurrence_edit.tpl.php`): Formulario de diseño de recurrencia integrado en la creación/edición de eventos ActionComm.
- **📝 Plantilla modo vista** (`recurrence_view.tpl.php`): Vista interactiva con lista de schedules y acciones en tiempo real.
- **🔄 Cron mejorado**: `sendWhAPIReminder()` ahora procesa primero las entradas de la tabla schedule (Fase 1) y luego los extrafields legacy (Fase 2), con exclusión automática de eventos que ya tienen schedules.
- **🔔 Trigger de creación automática**: Al crear/modificar un evento `AC_SEND_WHATSAPP_NOTIFY`, se generan automáticamente las programaciones basadas en la configuración de recurrencia.
- **🎨 Estilos CSS**: Nuevos estilos para tablas de schedule, indicadores de retraso, badges de estado y formulario del diseñador.
- **✅ Verificación de estado de instancia**: Nuevo endpoint AJAX `checkInstanceStatus.php` para verificar el estado de la conexión WhatsApp.
- **🔧 Configuración mejorada** (`admin/setup.php`): Mejoras en la página de configuración del módulo.

### 🐛 Correcciones
- **✅ Conversión de fechas**: Uso de `dol_mktime()` en lugar de `dol_stringtodate()` para evitar problemas de compatibilidad.
- **✅ jQuery UI dialogs**: Reemplazo de `confirm()` y `alert()` nativos por `$.dialog()` de jQuery UI para mejor UX y compatibilidad.

### 🌐 Traducciones
- **📋 33 nuevas claves de programación** añadidas en los 6 idiomas (ES, EN, FR, DE, IT, PT):
  - Títulos, etiquetas del diseñador, estados, mensajes de confirmación, errores y acciones.

### 📝 Archivos Creados
- `sql/llx_whatsapp_reminder_schedule.sql` – Definición de tabla
- `sql/llx_whatsapp_reminder_schedule.key.sql` – Índices y FK
- `class/reminderschedule.class.php` – Clase CRUD con generación de schedules
- `ajax/reminderSchedule.php` – Endpoint AJAX para operaciones de schedule
- `ajax/checkInstanceStatus.php` – Verificación de estado de instancia
- `tpl/recurrence_edit.tpl.php` – Plantilla modo edición
- `tpl/recurrence_view.tpl.php` – Plantilla modo vista

### 📝 Archivos Modificados
- `core/modules/modWhatsapp.class.php` – Versión 1.5
- `class/actions_whatsapp.class.php` – Hook `formObjectOptions` para inyectar el diseñador
- `class/whatsapputils.class.php` – Fase 1 (schedule) + Fase 2 (legacy) en cron
- `core/triggers/interface_99_modWhatsapp_WhatsappTriggers.class.php` – Trigger auto-generación
- `css/whatsapp.css.php` – Estilos del diseñador y tablas de schedule
- `langs/es_ES/whatsapp.lang` – 33 nuevas traducciones
- `langs/en_US/whatsapp.lang` – 33 nuevas traducciones
- `langs/fr_FR/whatsapp.lang` – 33 nuevas traducciones
- `langs/de_DE/whatsapp.lang` – 33 nuevas traducciones
- `langs/it_IT/whatsapp.lang` – 33 nuevas traducciones
- `langs/pt_PT/whatsapp.lang` – 33 nuevas traducciones

---

## [1.4] - 2026-03-06

### 🐛 Correcciones
- **✅ Colisión de posición de menú al activar el módulo**: Corregido error *"Menu entry (all,1001,) already exists"* que se producía cuando el módulo `eventorganization` u otros módulos con posición 1001 estaban activos simultáneamente.
  - La posición base del menú se calcula ahora dinámicamente consultando `MAX(position)` en `llx_menu`, evitando cualquier colisión futura con otros módulos instalados.

### 📝 Archivos Modificados
- `core/modules/modWhatsapp.class.php` - Versión 1.4 y cálculo dinámico de posición de menú

---

## [1.3] - 2026-01-13

### ✨ Nueva Funcionalidad: Programación de Notificaciones WhatsApp
- **📅 Nuevo campo `whatsapp_notification_datetime`**: Extrafield para ActionComm que permite programar envíos de WhatsApp
  - Permite definir fecha y hora exacta para el envío de notificaciones
  - Integración con el sistema de eventos de Dolibarr

### 🔧 Mejoras en el Cron Job de Recordatorios
- **⚡ Optimización de la consulta SQL**: El cron job ahora usa `whatsapp_notification_datetime` en lugar de `datep`
  - Programación más precisa de los recordatorios
  - Verificación de valores nulos para evitar envíos incorrectos

### 🐛 Correcciones
- **✅ Error SQL en `data.sql`**: Corregido punto y coma faltante antes del último INSERT
- **✅ Espaciado en tooltips**: Corregida inconsistencia en `WHATSAPP_MESSAGE_SENT_ON_TIMETooltip` en todos los archivos de idioma

### 🌐 Traducciones
- **📋 Nuevas traducciones añadidas** (EN, ES, DE, FR, IT, PT):
  - `WHATSAPP_NOTIFICATION_DATETIME`: Etiqueta del campo de fecha de notificación
  - `WHATSAPP_NOTIFICATION_DATETIMETooltip`: Tooltip explicativo del campo

### 📝 Archivos Modificados
- `class/whatsapputils.class.php` - Actualización de la lógica del cron job
- `core/modules/modWhatsapp.class.php` - Versión 1.3 y nuevo extrafield
- `langs/de_DE/whatsapp.lang` - Nuevas traducciones en alemán
- `langs/en_US/whatsapp.lang` - Nuevas traducciones en inglés
- `langs/es_ES/whatsapp.lang` - Nuevas traducciones en español
- `langs/fr_FR/whatsapp.lang` - Nuevas traducciones en francés
- `langs/it_IT/whatsapp.lang` - Nuevas traducciones en italiano
- `langs/pt_PT/whatsapp.lang` - Nuevas traducciones en portugués
- `sql/data.sql` - Corrección de sintaxis SQL

---

## [1.1] - 2025-11-06

### ✨ Rediseño Premium del Botón Flotante (FAB)
- **🎨 Sistema de diseño glassmorphism premium**: Efectos de cristal modernos
- **🔄 Animaciones CSS avanzadas**: Funciones cubic-bezier para transiciones suaves
- **✨ Efectos de sombra multicapa**: Percepción de profundidad mejorada
- **🌟 Efecto de brillo rotativo**: Hover en el botón principal
- **💫 Animación de pulso en badge**: Fondos con gradientes
- **🔲 Backdrop-filter con blur**: UI moderna y elegante
- **🎯 Animaciones elásticas**: Interacciones de tarjetas mejoradas
- **🖼️ Bordes con gradiente**: Jerarquía visual refinada

### 🔧 Mejoras de Interfaz
- **✅ Rediseño completo del FAB**: Apariencia profesional
- **⚡ Interacciones hover mejoradas**: Transiciones suaves
- **📊 Feedback visual optimizado**: Mejor respuesta a interacciones
- **🔢 Capas z-index optimizadas**: Apilamiento correcto de elementos
- **📏 Dimensiones y espaciado refinados**: Mayor usabilidad
- **🏷️ Estilo de badge actualizado**: Apariencia más prominente
- **📱 Diseño responsive mejorado**: Adaptación a dispositivos móviles

### 🐛 Correcciones
- **✅ Problemas de z-index**: Tarjetas de opciones aparecían detrás del botón principal
- **✅ Conflicto hover/JavaScript**: Funcionalidad hover corregida
- **✅ Overflow CSS**: Elementos escalados se recortaban en hover
- **✅ Jerarquía visual**: Problemas en la pila de tarjetas flotantes
- **✅ Animaciones de transformación**: Posicionamiento de elementos corregido

### 🔧 Mejoras Técnicas
- **🚫 Eliminación de toggle JavaScript**: Ahora usa CSS :hover puro
- **⚡ Rendimiento de animaciones**: Aceleración GPU optimizada
- **🌐 Compatibilidad cross-browser**: Prefijos -webkit- añadidos
- **📋 Estructura semántica**: Contenedor FAB mejorado
- **⏱️ Timing de animaciones**: Delays refinados para UX más suave

---

## [1.0] - 2025-01-01

### 🎉 Versión Inicial
- **📱 Integración con WhatsApp API**: Envío de mensajes desde Dolibarr
- **📝 Sistema de plantillas**: Gestión de mensajes predefinidos
- **🔔 Webhook receiver**: Recepción de mensajes entrantes
- **📊 Registro de webhook logs**: Historial de comunicaciones
- **🎤 Texto a audio**: Conversión TTS para mensajes de voz
- **🔗 Integración con terceros**: Envío desde fichas de clientes/contactos
