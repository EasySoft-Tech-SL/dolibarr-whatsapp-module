# CHANGELOG WHATSAPP FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

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
