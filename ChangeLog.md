# CHANGELOG WHATSAPP FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

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
