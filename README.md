# 📱 WHATSAPP FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

**Módulo profesional de integración WhatsApp para Dolibarr ERP/CRM**

Integra de forma completa WhatsApp en tu sistema Dolibarr utilizando la API profesional de **EasySoft Tech S.L** (www.easysoft.es), proporcionando comunicación bidireccional, automatización de mensajes y gestión avanzada de conversaciones.

## 🌟 Características Principales

### 📤 **Envío de Mensajes**
- ✅ **Mensajes de texto** con formato WhatsApp (negrita, cursiva, tachado, código)
- ✅ **Documentos y archivos** (PDF, imágenes, Excel, Word, etc.)
- ✅ **Notas de voz** y archivos de audio
- ✅ **Conversión de texto a voz** automática
- ✅ **Plantillas de mensajes** personalizables
- ✅ **Variables dinámicas** y sustitución automática
- ✅ **Conversión HTML a WhatsApp** automática

### 📥 **Recepción de Mensajes**
- ✅ **Webhooks en tiempo real** para mensajes entrantes
- ✅ **Registro automático** en la agenda de Dolibarr
- ✅ **Asociación automática** con terceros y contactos
- ✅ **Logs detallados** de toda la actividad

### 🔄 **Automatización**
- ✅ **Recordatorios automáticos** via cron
- ✅ **Triggers personalizados** para eventos de Dolibarr
- ✅ **Envío masivo** de mensajes
- ✅ **Programación de mensajes** diferidos
- ✅ **Cola de mensajería con envío escalonado** (anti-SPAM): N mensajes cada X minutos, con reintentos y control de errores

### 🎯 **Integración Completa**
- ✅ **Terceros** (Sociétés) - Envío directo desde fichas
- ✅ **Contactos** - Gestión de números móviles
- ✅ **Facturas** - Envío automático de facturas
- ✅ **Propuestas comerciales** - Notificaciones automáticas
- ✅ **Agenda** - Recordatorios de citas
- ✅ **Proyectos** - Comunicación del equipo

### ⚙️ **Gestión y Administración**
- ✅ **Panel de administración** completo
- ✅ **Control de uso y plan** en tiempo real
- ✅ **Gestión de instancias** WhatsApp
- ✅ **Sistema de permisos** granular
- ✅ **Logs de actividad** detallados
- ✅ **API REST** para integraciones externas

## 🛠️ Tecnología

- **Proveedor API**: EasySoft Tech S.L (www.easysoft.es)
- **Arquitectura**: Webhooks bidireccionales
- **Formato**: REST API con JSON
- **Seguridad**: Tokens API y autenticación segura

Other external modules are available on [Dolistore.com](https://www.dolistore.com).

## 📋 Casos de Uso

### 🏢 **Empresas de Servicios**
- Confirmación automática de citas
- Recordatorios de mantenimiento
- Envío de facturas y presupuestos
- Comunicación con técnicos en campo

### 🏪 **Comercio y Retail**
- Notificaciones de pedidos
- Promociones y ofertas
- Confirmación de reservas
- Atención al cliente inmediata

### 🏥 **Sector Sanitario**
- Recordatorios de citas médicas
- Envío de resultados
- Comunicación con pacientes
- Gestión de urgencias

### 🎓 **Educación**
- Comunicación con padres
- Envío de circulares
- Recordatorios de eventos
- Gestión de ausencias

## 🚀 Instalación

### Requisitos Previos

- Dolibarr 15.0 o superior
- Cuenta activa en EasySoft Tech S.L (www.easysoft.es)
- Instancia WhatsApp configurada
- PHP 7.4 o superior

### Desde archivo ZIP

1. Descarga el módulo desde [Dolistore](https://www.dolistore.com) o desde el repositorio
2. Ve a `Inicio → Configuración → Módulos → Desplegar módulo externo`
3. Sube el archivo ZIP del módulo
4. Activa el módulo desde la lista de módulos

### Desde repositorio GIT

```bash
cd /path/to/dolibarr/htdocs/custom
git clone https://github.com/usuario/whatsapp.git whatsapp
```

### Configuración Inicial

1. **Activar el módulo**:
   - Ir a `Configuración → Módulos`
   - Buscar "WhatsApp" y activarlo

2. **Configurar API**:
   - Ir a `Configuración → Módulos → WhatsApp → Configuración`
   - Introducir URL del servidor de EasySoft Tech
   - Introducir token de API proporcionado por EasySoft Tech
   - Configurar webhook si se desea recepción de mensajes

3. **Configurar permisos**:
   - Asignar permisos de WhatsApp a usuarios
   - Configurar tokens personales si es necesario

## ⚙️ Configuración Detallada

### 🔧 Parámetros Principales

| Parámetro | Descripción | Obligatorio |
|-----------|-------------|-------------|
| `WHATSAPP_SERVER_URL` | URL del servidor API de EasySoft Tech | ✅ |
| `WHATSAPP_SERVER_TOKEN` | Token de autenticación API | ✅ |
| `WHATSAPP_WEBHOOK_ALLOW` | Permitir recepción de webhooks | ❌ |
| `WHATSAPP_WEBHOOK_USER_ID` | Usuario para procesar webhooks | ❌ |
| `WHATSAPP_WEBHOOK_SECRET` | Secreto compartido que debe enviar la pasarela (se genera solo al activar el webhook) | ❌ |
| `WHATSAPP_SHOW_ON_GETNOMURL` | Mostrar botón WhatsApp en fichas | ❌ |
| `MAIN_WHATSAPP_PHONE_PREFIX` | Prefijo telefónico por defecto | ❌ |
| `WHATSAPP_QUEUE_MAX_PER_RUN` | Mensajes a enviar por ejecución del cron (N) | ❌ |
| `WHATSAPP_QUEUE_WINDOW_SECONDS` | Ventana mínima entre lotes en segundos (X) | ❌ |
| `WHATSAPP_QUEUE_JITTER_SECONDS` | Segundos aleatorios añadidos a la ventana | ❌ |
| `WHATSAPP_QUEUE_MAX_RETRIES` | Reintentos máximos ante error temporal | ❌ |
| `WHATSAPP_QUEUE_BACKOFF_SECONDS` | Backoff base entre reintentos (× nº de reintento) | ❌ |
| `WHATSAPP_FORCE_QUEUE` | Encolar los recordatorios en lugar de enviarlos al instante | ❌ |
| `WHATSAPP_COMBINE_TEXT_WITH_DOC` | Enviar el texto como pie (caption) del documento, en un único mensaje | ❌ |
| `WHATSAPP_DEFAULT_COUNTRY_CODE` | Prefijo de país (sin `+`) para normalizar números | ❌ |
| `WHATSAPP_REMINDER_MAX_RETRIES` | Reintentos máximos de los recordatorios programados | ❌ |

### 🔗 Configuración de Webhooks

Desde la 1.11 la pantalla de configuración del módulo muestra la URL del webhook y el
secreto compartido, con botones para copiarlos y el estado de protección. Es lo que hay
que pegar en la pasarela (Mensaru, Evolution API u otra):

1. Activar `WHATSAPP_WEBHOOK_ALLOW = 1`. El módulo genera `WHATSAPP_WEBHOOK_SECRET`.
2. Copiar la **URL del webhook** desde la configuración:
   ```
   https://tu-dominio.com/custom/whatsapp/public/webhook/receiver.php
   ```
3. Pegar también el **secreto** en la pasarela, en una de estas vías (se aceptan todas):
   - cabecera `X-Webhook-Secret: <secreto>`
   - cabecera `Authorization: Bearer <secreto>`
   - campo `apikey` del cuerpo JSON
   - parámetro de URL `?secret=<secreto>`
4. Seleccionar el usuario que procesará los mensajes entrantes.

Sin secreto configurado el receptor responde **503**; si el secreto no coincide, **401**.
Si no se define `WHATSAPP_WEBHOOK_SECRET`, se acepta `WHATSAPP_SERVER_TOKEN` como secreto
para no romper instalaciones que ya compartían ese token.

⚠️ En Mensaru, la pantalla de instancia reescribe la configuración del webhook y puede
perder las cabeceras personalizadas. Si ocurre, usa la vía del parámetro `?secret=` en la
URL, que sobrevive a esa reescritura.

⚠️ Con Evolution API 2.2.x: el campo `apikey` del cuerpo puede llegar `null` (depende de
`AUTHENTICATION_EXPOSE_IN_FETCH_INSTANCES`) y su valor es la credencial de la instancia, no
un secreto de webhook; úsalo solo como compatibilidad. El parámetro `?secret=` deja de
funcionar si el webhook tiene `byEvents: true`, porque Evolution concatena el nombre del
evento al final de la URL. La vía recomendada es la cabecera `X-Webhook-Secret`.

### 👥 Sistema de Permisos

- **whatsapp:read** - Ver mensajes y configuración
- **whatsapp:write** - Enviar mensajes
- **whatsapp:admin** - Administración completa

## 📱 Uso del Módulo

### Envío de Mensajes Individuales

1. **Desde ficha de tercero/contacto**:
   - Botón "WhatsApp" en la ficha
   - Seleccionar tipo de mensaje
   - Escribir o seleccionar plantilla
   - Enviar

2. **Desde agenda**:
   - Crear evento tipo "Envío WhatsApp"
   - Programar fecha y hora
   - El sistema enviará automáticamente

### Plantillas de Mensajes

Las plantillas soportan variables dinámicas:

```
Estimado __THIRDPARTY_NAME__,

Su factura __INVOICE_REF__ por importe de __INVOICE_TOTAL_TTC__ 
está pendiente de pago.

Puede descargarla desde: __INVOICE_URL__

Saludos,
__COMPANY_NAME__
```

### Conversión HTML a WhatsApp

El módulo convierte automáticamente HTML a formato WhatsApp:

- `<strong>texto</strong>` → `*texto*` (negrita)
- `<em>texto</em>` → `_texto_` (cursiva)  
- `<s>texto</s>` → `~texto~` (tachado)
- `<code>texto</code>` → ``` `texto` ``` (código)
- Enlaces se convierten a texto + URL

### Texto a Voz

Para mensajes de audio automáticos:

```php
// El módulo puede convertir texto a audio automáticamente
$audio = textToSpeech("Hola, este es un mensaje de prueba");
sendWhapiAudio($object, $phone, $audio, "Mensaje de prueba");
```

## 🔄 Automatización y Cron

### Recordatorios Automáticos

Configurar tarea cron para envío automático:

```bash
# Cada 5 minutos
*/5 * * * * /usr/bin/php /path/to/dolibarr/scripts/cron/cron_run_jobs.php whatsapp sendWhAPIReminder
```

### Cola de mensajería (envío escalonado anti-SPAM)

Cuando se envían muchos mensajes, conviene espaciarlos para que la conexión no sea marcada como SPAM. La **cola** permite encolar mensajes (texto, documento/PDF, audio) y entregarlos poco a poco mediante la tarea programada `processQueue`.

- **Cadencia "N cada X"**: la tarea acepta parámetros `processQueue(maxToSend, windowSeconds, jitterSeconds)`. Por ejemplo `1, 150, 30` envía **1 mensaje cada 150-180 segundos**. Una guarda interna respeta la ventana aunque el cron se ejecute más a menudo. Los valores también pueden fijarse con las constantes `WHATSAPP_QUEUE_*` desde la configuración.
- **Control de errores**: los errores permanentes (número inválido, HTTP 4xx, configuración faltante o **fichero inexistente en disco**) se marcan como *Fallido* sin reintentar; los temporales (red, timeout, 5xx, 429) se reintentan con *backoff* hasta `WHATSAPP_QUEUE_MAX_RETRIES`.
- **Gestión**: en `Utilidades → Cola de WhatsApp` hay un listado con contadores por estado y acciones de reencolar / cancelar / eliminar, y una ficha de detalle con el último error y el código HTTP.

> La tarea de la cola se instala **desactivada**. Actívala en `Configuración → Módulos → Tareas programadas` y ajusta sus parámetros.

```bash
# Cada minuto (la guarda interna controla la cadencia real)
* * * * * /usr/bin/php /path/to/dolibarr/scripts/cron/cron_run_jobs.php whatsapp processQueue
```

### Triggers Automáticos

El módulo se integra con eventos de Dolibarr:

- **Nueva factura** → Envío automático
- **Cita creada** → Recordatorio programado
- **Pago recibido** → Confirmación al cliente
- **Propuesta aceptada** → Notificación al comercial

## 📊 Monitorización y Logs

### Panel de Control

- **Uso del plan**: Mensajes enviados/límite
- **Estado de instancia**: Conectada/Desconectada
- **Logs de actividad**: Historial completo
- **Estadísticas**: Mensajes por tipo/período

### Logs de Webhooks

Todos los mensajes entrantes se registran automáticamente:

- Fecha y hora
- Remitente
- Contenido del mensaje
- Estado de procesamiento
- Asociación con tercero/contacto

## 🛡️ Seguridad y Mejores Prácticas

### Seguridad

- ✅ Tokens API encriptados
- ✅ Validación de webhooks
- ✅ Control de acceso por usuario
- ✅ Logs de auditoría completos
- ✅ Sanitización de contenido

### Mejores Prácticas

1. **Gestión de números**:
   - Verificar formato internacional (+34...)
   - Validar números antes del envío
   - Mantener listas de opt-out

2. **Contenido de mensajes**:
   - Respetar límites de caracteres
   - Usar plantillas consistentes
   - Incluir información de contacto

3. **Frecuencia de envío**:
   - No saturar con mensajes
   - Respetar horarios comerciales
   - Usar recordatorios espaciados

## 🔧 Desarrollo y API

### API REST Interna

El módulo expone una API para integraciones:

```bash
# Registrar webhook
POST /api/index.php/whatsapp/register_webhook_logs
Content-Type: application/json
DOLAPIKEY: tu-api-key

{
  "event": "messages.upsert",
  "instance": "mi-instancia",
  "data": { ... }
}
```

### Funciones Principales

```php
// Enviar mensaje de texto
sendWhapiText($object, $phone, $message, $options);

// Enviar documento
sendWhapiDocument($object, $phone, $media, $fileName, $mediatype);

// Enviar audio
sendWhapiAudio($object, $phone, $audioBase64, $transcription);

// Obtener estado de instancia
getInstanceStatus($token, $server);

// Insertar en agenda
insertActionIntoAgenda($object, $title, $body, $actiontype, $elementid, $elementtype);
```

### Encolar mensajes desde otros módulos

La clase `WhatsappQueue` expone una API estática reutilizable para encolar mensajes desde este o cualquier otro módulo. El cron `processQueue` los enviará de forma escalonada.

```php
dol_include_once('/whatsapp/class/whatsappqueue.class.php');

// Texto
WhatsappQueue::enqueueText('+34600111222', 'Hola, su pedido está listo', array(
    'fk_soc'        => $societe->id,   // opcional
    'priority'      => 50,             // menor = antes
    'date_scheduled' => dol_now() + 3600, // opcional: no enviar antes de esta fecha
    'source_module' => 'mimodulo',     // para trazabilidad
));

// Documento / PDF (se valida que el fichero exista al enviar)
WhatsappQueue::enqueueDocument('+34600111222', '/ruta/a/factura.pdf', 'factura.pdf', 'document');

// Audio / nota de voz
WhatsappQueue::enqueueAudio('+34600111222', '/ruta/a/nota.ogg', 'Transcripción opcional');
```

Devuelven el `rowid` de la fila encolada (`>0`) o un valor negativo si falla.

📖 **Guía completa de la cola** (cadencia, estados, control de errores, opciones de encolado y referencia): [docs/cola-mensajeria.md](docs/cola-mensajeria.md).

### Hooks Disponibles

- `getNomUrl` - Botón WhatsApp en fichas
- `doActions` - Procesamiento de envíos
- `printCommonFooter` - Interfaz de envío
- `printTopRightMenu` - Estado de conexión

## 🆘 Soporte y Troubleshooting

### Problemas Comunes

**❌ "No se puede conectar al servidor"**
- Verificar URL y token de API
- Comprobar conectividad de red
- Validar configuración en EasySoft Tech

**❌ "Número de teléfono no válido"**
- Usar formato internacional (+34...)
- Verificar que el número esté en WhatsApp
- Comprobar configuración de prefijo

**❌ "Mensajes no se reciben"**
- Verificar configuración de webhook
- Comprobar permisos de usuario
- Revisar logs de error

### Logs de Debug

Activar logs detallados en `conf.php`:

```php
$dolibarr_main_prod = 0;  // Modo debug
$dolibarr_syslog_level = LOG_DEBUG;
```

Los logs se guardan en:
- `documents/admin/temp/dolibarr.log`
- Tabla `llx_whatsapp_webhooklog`

## 🤝 Soporte Técnico

### Proveedores

- **Desarrollo del módulo**: Alberto Luque Rivas (aluquerivasdev@gmail.com)
- **API WhatsApp**: EasySoft Tech S.L (www.easysoft.es)
- **Soporte Dolibarr**: Comunidad Dolibarr

### Recursos

- [Documentación oficial Dolibarr](https://www.dolibarr.org/documentation)
- [API EasySoft Tech](https://www.easysoft.es)
- [Repositorio del módulo](https://github.com/usuario/whatsapp)

## Translations

Translations can be completed manually by editing files into directories *langs*.

<!--
This module contains also a sample configuration for Transifex, under the hidden directory [.tx](.tx), so it is possible to manage translation using this service.

For more informations, see the [translator's documentation](https://wiki.dolibarr.org/index.php/Translator_documentation).

There is a [Transifex project](https://transifex.com/projects/p/dolibarr-module-template) for this module.
-->

<!--

## Installation

### From the ZIP file and GUI interface

If the module is a ready to deploy zip file, so with a name module_xxx-version.zip (like when downloading it from a market place like [Dolistore](https://www.dolistore.com)),
go into menu ```Home - Setup - Modules - Deploy external module``` and upload the zip file.

Note: If this screen tell you that there is no "custom" directory, check that your setup is correct:

- In your Dolibarr installation directory, edit the ```htdocs/conf/conf.php``` file and check that following lines are not commented:

    ```php
    //$dolibarr_main_url_root_alt ...
    //$dolibarr_main_document_root_alt ...
    ```

- Uncomment them if necessary (delete the leading ```//```) and assign a sensible value according to your Dolibarr installation

    For example :

    - UNIX:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
        ```

    - Windows:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
        ```

### From a GIT repository

Clone the repository in ```$dolibarr_main_document_root_alt/whatsapp```

```sh
cd ....../custom
git clone git@github.com:gitlogin/whatsapp.git whatsapp
```

### <a name="final_steps"></a>Final steps

From your browser:

  - Log into Dolibarr as a super-administrator
  - Go to "Setup" -> "Modules"
  - You should now be able to find and enable the module

-->

## 📄 Licenses

### Main code

GPLv3 or (at your option) any later version. See file COPYING for more information.

### Documentation

All texts and readmes are licensed under GFDL.

---

**📱 WhatsApp for Dolibarr** - Desarrollado con ❤️ por Alberto Luque Rivas  
**🚀 Powered by EasySoft Tech S.L** - API WhatsApp profesional  
**⭐ Si te gusta el módulo, ¡déjanos una estrella en GitHub!**
