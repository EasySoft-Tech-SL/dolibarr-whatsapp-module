# Política de Privacidad - Módulo WhatsApp para Dolibarr

**Última actualización:** 15 de diciembre de 2025  
**Versión:** 2.0  
**Módulo:** WhatsApp Integration for Dolibarr  
**Desarrollador:** EasySoft Tech S.L.

---

## 📋 Tabla de Contenidos

1. [Información de la Empresa](#información-de-la-empresa)
2. [Introducción y Principios](#introducción-y-principios)
3. [Datos Almacenados](#lo-que-almacena-el-módulo)
4. [Procesamiento de Datos](#datos-procesados)
5. [Finalidad del Tratamiento](#finalidad-del-tratamiento)
6. [Base Legal](#base-legal)
7. [Medidas de Seguridad](#medidas-de-seguridad)
8. [Retención de Datos](#retención-de-datos)
9. [Derechos del Usuario](#derechos-del-usuario)
10. [Contacto](#contacto)

---

## Información de la Empresa

El **Módulo WhatsApp para Dolibarr** es un software de código abierto desarrollado por **EasySoft Tech S.L.**, empresa registrada en España con CIF **B16885766**.

### 📦 Sobre el Módulo

Este módulo es una extensión para Dolibarr ERP/CRM que permite:
- Enviar mensajes de WhatsApp desde Dolibarr
- Recibir mensajes de WhatsApp en Dolibarr
- Integrar comunicaciones con terceros, facturas, pedidos, etc.
- Conectarse a servicios externos de mensajería WhatsApp

**Licencia:** GNU General Public License v3.0 (GPLv3)  
**Repositorio:** [https://github.com/EasySoft-Tech-SL/dolibarr-whatsapp-module](https://github.com/EasySoft-Tech-SL/dolibarr-whatsapp-module)

### 🏢 Datos de Contacto

**EasySoft Tech S.L.**
- 📧 Email: [info@easysoft.es](mailto:info@easysoft.es)
- 🏢 Dirección: Calle Gil de Jaz, Núm. 4 - Planta 1, Puerta N, 33201 Oviedo, Asturias, España
- 🆔 CIF: B16885766
- 🌐 Web: [https://www.easysoft.es](https://www.easysoft.es)

### ⚠️ Importante: Responsabilidad de Datos

**EasySoft Tech S.L.** es el desarrollador del módulo de software. Como usuario del módulo:
- **Usted es el responsable del tratamiento** de los datos almacenados en su instalación de Dolibarr
- Los datos se almacenan en **su servidor**, no en servidores de EasySoft Tech S.L.
- EasySoft Tech S.L. **no tiene acceso** a los datos procesados por su instalación del módulo
- La conexión a servicios de API de WhatsApp es **directa entre su sistema y el proveedor de API**

---

## Introducción y Principios

El **Módulo WhatsApp para Dolibarr** es una integración que permite enviar y recibir mensajes de WhatsApp directamente desde su sistema Dolibarr, desarrollado por **EasySoft Tech S.L.**.

### 🎯 Características Clave

- **Integración Local:** El módulo funciona en su instalación de Dolibarr
- **Control de Datos:** Usted mantiene control total sobre sus datos
- **API Externa:** Se conecta a servicios externos de mensajería WhatsApp
- **Almacenamiento Local:** Los mensajes se guardan en su base de datos de Dolibarr

### 💡 Principios de Privacidad

- **Transparencia:** Información clara sobre qué datos se procesan y dónde se almacenan
- **Control del Usuario:** Usted gestiona la retención y eliminación de datos
- **Minimización:** Solo se procesan datos necesarios para el funcionamiento
- **Seguridad:** Comunicaciones cifradas mediante HTTPS/TLS
- **Cumplimiento:** Normativa RGPD, LOPDGDD y legislación aplicable

### 🔍 Responsabilidades

| Responsable | Ámbito | Datos |
|-------------|--------|-------|
| **Usuario (Usted)** | Sistema Dolibarr local | Mensajes, webhooks, configuración almacenados localmente |
| **Proveedor de Servicios WhatsApp** | Servicio de mensajería WhatsApp | Datos en tránsito para entrega de mensajes |
| **EasySoft Tech S.L.** | Desarrollo del módulo | No tiene acceso a sus datos |

**Importante:** Esta política describe el tratamiento de datos del módulo. Los datos almacenados en su instalación de Dolibarr están bajo su control y responsabilidad.

---

## Datos Procesados

El módulo WhatsApp para Dolibarr procesa y almacena los siguientes datos:

### 📊 Datos Enviados a la API de WhatsApp

| Dato | Descripción | Finalidad |
|------|-------------|-----------|
| **API Key** | Token de autenticación de tu cuenta WhatsApp API | Autenticación y autorización |
| **Número de Teléfono Destinatario** | Número WhatsApp al que se envía el mensaje | Enrutamiento de mensajes |
| **Contenido del Mensaje** | Texto, audio o documentos enviados | Comunicación con clientes |
| **Nombre de Archivo** | Nombre de documentos/medios adjuntos | Identificación de archivos enviados |
| **Tipo de Medio** | Tipo de archivo (document, image, video) | Procesamiento correcto del medio |
| **Dirección IP** | IP desde la que se realiza la petición | Seguridad y prevención de fraude |

### 💾 Datos Almacenados en su Sistema Dolibarr

| Dato | Ubicación | Descripción | Retención |
|------|-----------|-------------|-----------|
| **Contenido de Mensajes** | Tabla actioncomm (Agenda) | Mensajes enviados y recibidos por WhatsApp | Según configuración de Dolibarr |
| **Datos de Webhook** | Tabla webhooklog | Payload completo de eventos de WhatsApp (incluye contenido de mensajes) | Configurable |
| **Metadatos de Mensaje** | Tabla actioncomm | Fecha, hora, usuario, elemento relacionado (factura, pedido, etc.) | Según configuración de Dolibarr |
| **Información de Instancia** | Tabla actioncomm/webhooklog | Nombre de la instancia de WhatsApp utilizada | Según configuración de Dolibarr |
| **API Keys** | Configuración de usuario/global | Tokens de autenticación para la API | Mientras esté activo |
| **Números de Teléfono** | Ficha de terceros | Números de WhatsApp de clientes | Según datos de terceros |

**Importante:** Todos los mensajes enviados y recibidos se almacenan en su instalación local de Dolibarr. Los datos NO se envían a servidores de EasySoft Tech S.L., permanecen en su propio sistema.

---

## Lo Que Almacena el Módulo

### ✅ Datos Almacenados en Dolibarr

El módulo WhatsApp para Dolibarr almacena **localmente en su instalación** los siguientes datos:

#### 📝 Mensajes de WhatsApp

- ✓ Texto completo de mensajes enviados
- ✓ Texto completo de mensajes recibidos
- ✓ Transcripciones de mensajes de voz
- ✓ Nombres de archivos adjuntos (PDFs, imágenes, videos)
- ✓ Metadatos: fecha, hora, usuario, elemento relacionado
- ✓ Estado de envío/recepción

#### 🔔 Webhooks Recibidos

- ✓ Eventos completos de WhatsApp (payload JSON completo)
- ✓ Tipo de evento (mensaje recibido, mensaje enviado, etc.)
- ✓ Nombre de instancia de WhatsApp
- ✓ Remitente y destinatario
- ✓ Timestamp del evento

#### ⚙️ Configuración

- ✓ URL del servidor API de WhatsApp
- ✓ API Keys de autenticación
- ✓ Números de teléfono de clientes (en fichas de terceros)
- ✓ Preferencias de envío

**Importante:** 
- Todos estos datos se almacenan **únicamente en su instalación local de Dolibarr**
- EasySoft Tech S.L. **NO tiene acceso** a estos datos almacenados en su sistema
- Usted mantiene control total sobre estos datos y su retención

---

## Finalidad del Tratamiento

Los datos procesados por el módulo WhatsApp se utilizan para:

### 1. **Funcionalidad del Módulo**
- Envío y recepción de mensajes de WhatsApp desde Dolibarr
- Integración de comunicaciones con terceros, facturas, pedidos, etc.
- Registro de historial de comunicaciones en la agenda
- Seguimiento de conversaciones con clientes

### 2. **Prestación del Servicio de Mensajería**
- Enrutamiento y entrega de mensajes a través de WhatsApp
- Autenticación y autorización de peticiones
- Confirmación de entrega de mensajes

### 3. **Auditoría y Trazabilidad**
- Registro de eventos de webhook para debugging
- Historial de comunicaciones con clientes
- Cumplimiento de obligaciones de trazabilidad empresarial

### 4. **Seguridad**
- Prevención de fraude y accesos no autorizados
- Detección de anomalías en el uso del servicio
- Control de acceso mediante API Keys

### 🚫 Lo Que NO Se Hace con los Datos

- ✗ Marketing o publicidad no solicitada
- ✗ Venta de datos a terceros
- ✗ Compartir datos sin su consentimiento
- ✗ Crear perfiles de comportamiento para otros fines
- ✗ Combinar datos con fuentes externas sin autorización
- ✗ Transferencia fuera de su instalación de Dolibarr (salvo API de WhatsApp para envío)

### 📍 Ubicación de los Datos

**Importante:** Los mensajes y datos de comunicación se almacenan **únicamente en su servidor de Dolibarr**, bajo su control y responsabilidad. Los datos en tránsito se transmiten únicamente para la entrega de mensajes a través de WhatsApp.

---

## Base Legal

### 📜 Responsable del Tratamiento

**Importante:** Como usuario del módulo WhatsApp para Dolibarr, **usted es el responsable del tratamiento** de los datos personales almacenados en su sistema.

### ⚖️ Bases Legales Aplicables

El tratamiento de datos que usted realiza mediante este módulo puede fundamentarse en:

#### 1. Consentimiento del Interesado (Art. 6.1.a RGPD)
Si obtiene consentimiento de sus clientes para enviarles comunicaciones por WhatsApp.

#### 2. Ejecución de Contrato (Art. 6.1.b RGPD)
Si el envío de mensajes es necesario para la ejecución de un contrato con el cliente (ej: notificaciones de pedidos, facturas).

#### 3. Interés Legítimo (Art. 6.1.f RGPD)
Si tiene un interés legítimo en comunicarse con clientes existentes, siempre que prevalezca sobre los derechos del interesado.

#### 4. Obligación Legal (Art. 6.1.c RGPD)
Si está obligado legalmente a conservar registros de comunicaciones.

### 🛡️ Responsabilidades del Usuario

Como responsable del tratamiento, debe:
- ✓ Obtener bases legales adecuadas para el tratamiento
- ✓ Informar a los interesados sobre el tratamiento de sus datos
- ✓ Garantizar los derechos de los interesados (acceso, rectificación, supresión, etc.)
- ✓ Implementar medidas de seguridad adecuadas
- ✓ Cumplir con el RGPD y legislación aplicable
- ✓ Mantener registro de actividades de tratamiento
- ✓ Realizar evaluaciones de impacto si es necesario

### 👨‍💼 Rol de EasySoft Tech S.L.

EasySoft Tech S.L. actúa como:
- **Desarrollador de software:** Crea y mantiene el módulo
- **NO es encargado del tratamiento:** No tiene acceso a sus datos
- **NO es responsable del tratamiento:** Usted controla completamente los datos

---

## Medidas de Seguridad

### 🔐 Seguridad del Módulo

El módulo WhatsApp para Dolibarr implementa las siguientes medidas de seguridad:

#### Cifrado de Comunicaciones
- **HTTPS/TLS 1.2+:** Todas las comunicaciones con la API de WhatsApp se cifran
- **API Key en Headers:** Las claves de autenticación se transmiten de forma segura
- **Validación de Certificados SSL:** Verificación de certificados en conexiones

#### Almacenamiento Seguro
- **Hashing de API Keys:** Opcionalmente puede almacenar keys hasheadas
- **Permisos de Dolibarr:** Control de acceso basado en permisos de usuario
- **Segregación de Datos:** Los datos están aislados por entidad en Dolibarr multiempresa

#### Validación de Datos
- **Sanitización de Inputs:** Limpieza de datos de entrada
- **Prepared Statements:** Uso de consultas parametrizadas para prevenir SQL injection
- **Validación de Webhooks:** Verificación de webhooks entrantes

### 🛡️ Responsabilidades de Seguridad

| Responsable | Ámbito de Seguridad |
|-------------|---------------------|
| **Usuario (Usted)** | - Seguridad del servidor Dolibarr<br>- Copias de seguridad<br>- Control de acceso a Dolibarr<br>- Actualizaciones del sistema<br>- Protección de credenciales de API |
| **Proveedor de Servicios WhatsApp** | - Seguridad del servicio de mensajería<br>- Entrega de mensajes<br>- Cifrado end-to-end de WhatsApp |
| **EasySoft Tech S.L.** | - Código seguro del módulo<br>- Corrección de vulnerabilidades<br>- Actualizaciones de seguridad |

### 🔍 Recomendaciones de Seguridad

Para maximizar la seguridad al usar el módulo:

1. **Mantenga Dolibarr actualizado** a la última versión estable
2. **Use HTTPS** en su instalación de Dolibarr
3. **Proteja sus API Keys** - no las comparta ni las almacene en código
4. **Realice copias de seguridad** regulares de su base de datos
5. **Limite permisos** de usuarios al módulo según necesidad
6. **Revise logs** de webhook regularmente para detectar anomalías
7. **Use contraseñas fuertes** para cuentas de Dolibarr
8. **Active autenticación de dos factores** si está disponible
9. **Mantenga el módulo actualizado** a la última versión

### 🚨 Reporte de Vulnerabilidades

Si encuentra una vulnerabilidad de seguridad en el módulo:
- 📧 Contacte: info@easysoft.es
- 🔒 Proporcione detalles técnicos de forma confidencial
- ⏱️ Recibirá respuesta en 48-72 horas

---

## Retención de Datos

### 📋 Datos en su Sistema Dolibarr

**Usted controla la retención de datos** almacenados en su instalación de Dolibarr:

#### Mensajes en Agenda (actioncomm)
- **Retención:** Según su configuración de Dolibarr
- **Control:** Puede eliminar manualmente desde la agenda
- **Ubicación:** Base de datos local de Dolibarr

#### Logs de Webhook (webhooklog)
- **Retención:** Según su configuración del módulo
- **Control:** Puede limpiar o eliminar desde el módulo
- **Ubicación:** Base de datos local de Dolibarr
- **Recomendación:** Limpieza periódica (ej: cada 90 días)

#### Archivos Adjuntos
- **Retención:** Los archivos PDF/imágenes enviados se convierten a base64 y se transmiten
- **Almacenamiento:** No se almacenan copias adicionales, están en el sistema de documentos de Dolibarr
- **Control:** Gestión a través del sistema de documentos de Dolibarr

### 📋 Datos en Servidores del Proveedor de Servicios

Los datos procesados por el servicio de mensajería WhatsApp (gestionado por el proveedor externo) siguen sus propias políticas de retención. Consulte la documentación de su proveedor de servicios de mensajería.

### 🗑️ Eliminación de Datos

Para eliminar datos del módulo:

1. **Mensajes de Agenda:** 
   - Acceda a la agenda de Dolibarr
   - Elimine eventos de tipo WhatsApp manualmente
   
2. **Logs de Webhook:**
   - Acceda al módulo WhatsApp > Webhook Logs
   - Elimine registros individualmente o en masa

3. **Configuración y API Keys:**
   - Acceda a Setup del módulo
   - Elimine o modifique credenciales según necesite

4. **Desinstalación Completa:**
   - La desactivación del módulo preserva los datos
   - La desinstalación completa puede eliminar tablas (según configuración)

---

## Derechos de los Interesados (Sus Clientes)

### ⚠️ Importante: Responsabilidad del Usuario

Como **responsable del tratamiento**, **usted debe garantizar** los derechos de las personas (sus clientes) cuyos datos procesa mediante este módulo.

Los derechos que deben poder ejercer sus clientes ante **usted** son:

### 🔍 Derecho de Acceso (Art. 15 RGPD)

Sus clientes pueden solicitar:
- Confirmación de si trata sus datos
- Copia de los datos personales que tiene sobre ellos
- Información sobre el tratamiento

**Cómo facilitarlo con el módulo:**
- Revise la agenda de Dolibarr filtrada por tercero
- Consulte los webhooks relacionados con ese número de teléfono
- Exporte los datos en formato legible

### ✏️ Derecho de Rectificación (Art. 16 RGPD)

Sus clientes pueden solicitar corrección de datos inexactos o incompletos.

**Cómo facilitarlo con el módulo:**
- Edite los registros en la agenda de Dolibarr
- Actualice los datos del tercero
- Corrija información incorrecta en webhooks si es necesario

### 🗑️ Derecho de Supresión "Derecho al Olvido" (Art. 17 RGPD)

Sus clientes pueden solicitar eliminación de sus datos cuando:
- Los datos ya no sean necesarios
- Retiren su consentimiento
- Se opongan al tratamiento
- Los datos se hayan tratado ilícitamente

**Cómo facilitarlo con el módulo:**
- Elimine eventos de agenda relacionados con ese cliente
- Elimine webhooks que contengan sus datos
- Considere anonimizar en lugar de eliminar si hay obligación legal de conservación

### ⛔ Derecho de Limitación (Art. 18 RGPD)

Sus clientes pueden solicitar que limite el tratamiento mientras resuelve disputas.

**Cómo facilitarlo con el módulo:**
- Marque el tercero como inactivo
- No envíe más mensajes a ese contacto
- Conserve los datos pero no los use activamente

### 🔄 Derecho de Portabilidad (Art. 20 RGPD)

Sus clientes pueden solicitar recibir sus datos en formato estructurado y legible por máquina.

**Cómo facilitarlo con el módulo:**
- Exporte eventos de agenda en CSV/JSON
- Exporte webhooks relacionados
- Proporcione copia de mensajes enviados/recibidos

### 📢 Derecho de Oposición (Art. 21 RGPD)

Sus clientes pueden oponerse al tratamiento basado en interés legítimo o para marketing directo.

**Cómo facilitarlo con el módulo:**
- Añada campo en Dolibarr para registrar oposición
- No envíe más mensajes de WhatsApp a ese contacto
- Documente la solicitud de oposición

### 🤖 Decisiones Automatizadas (Art. 22 RGPD)

El módulo **no toma decisiones automatizadas** que produzcan efectos legales. Los mensajes son enviados manualmente o mediante acciones configuradas por usted.

---

### 📋 Procesamiento de Solicitudes

**Como responsable del tratamiento, usted debe:**

1. **Responder en plazo:** Máximo 30 días (prorrogable 60 días más si es complejo)
2. **Verificar identidad:** Asegúrese de que la solicitud es del interesado real
3. **Sin costo:** Las solicitudes son gratuitas (salvo abuso manifiesto)
4. **Documentar:** Registre todas las solicitudes y respuestas

### 📧 Sistema de Gestión de Solicitudes

Implemente un proceso en su organización para:
- Recibir y registrar solicitudes de derechos
- Verificar identidad del solicitante
- Localizar datos en Dolibarr (agenda, webhooks, terceros)
- Procesar la solicitud según el derecho ejercido
- Responder al interesado en plazo
- Documentar todo el proceso

---

### ℹ️ Información para sus Clientes

**Debe informar a sus clientes** sobre el tratamiento de sus datos de WhatsApp, incluyendo:
- Qué datos recoge (mensajes, número de teléfono, etc.)
- Para qué los usa (comunicación comercial, notificaciones, etc.)
- Cuánto tiempo los conserva
- Base legal del tratamiento
- Sus derechos y cómo ejercerlos
- Cómo contactar con usted para cuestiones de privacidad

**Puede incluir esta información en:**
- Su política de privacidad web
- Contratos con clientes
- Primera comunicación por WhatsApp
- Consentimientos específicos si es necesario

---

## Contacto

### 📧 Contacto para Cuestiones sobre el Módulo

**EasySoft Tech S.L. - Soporte del Módulo**

- **Email General:** [info@easysoft.es](mailto:info@easysoft.es)
- **Dirección:** Calle Gil de Jaz, Núm. 4 - Planta 1, Puerta N, 33201 Oviedo, Asturias, España
- **CIF:** B16885766
- **Web:** [https://www.easysoft.es](https://www.easysoft.es)
- **GitHub:** [https://github.com/EasySoft-Tech-SL/dolibarr-whatsapp-module](https://github.com/EasySoft-Tech-SL/dolibarr-whatsapp-module)

### ⚠️ Importante: Consultas sobre Datos Personales

**Para ejercer derechos sobre datos personales** (acceso, rectificación, supresión, etc.):
- Si usted es **cliente** de una empresa que usa este módulo → Contacte con esa empresa, no con EasySoft
- Si usted es **usuario** del módulo → Gestione los derechos de sus clientes directamente en Dolibarr

EasySoft Tech S.L. **no almacena ni accede** a los datos personales procesados por el módulo en su instalación.



## Actualizaciones de la Política

Esta política de privacidad puede actualizarse para reflejar:
- Cambios en el funcionamiento del módulo
- Nuevas funcionalidades
- Cambios en la legislación aplicable
- Mejoras en las prácticas de privacidad

### 📢 Notificación de Cambios

Los cambios se comunicarán mediante:
- ✉️ Actualización del archivo PRIVACY.md en el repositorio GitHub
- 📋 Inclusión en las notas de versión (ChangeLog.md)
- 🔔 Notificación en actualizaciones del módulo
- 📧 Email a usuarios registrados (si aplica)

### 📅 Historial de Versiones

- **Versión 2.0** (15/12/2025): Política actualizada para reflejar correctamente el funcionamiento del módulo y el almacenamiento local de datos
- **Versión 1.0** (15/12/2025): Política inicial

### ✅ Aceptación

El uso continuado del módulo tras la actualización de esta política implica la aceptación de los cambios.

**Recomendación:** Revise periódicamente esta política para mantenerse informado sobre cómo el módulo maneja los datos.

---

## Términos Especiales

### Proveedor de Servicios de Mensajería WhatsApp

El módulo se conecta a servicios externos de mensajería WhatsApp de terceros. Estos proveedores tienen sus propias políticas de privacidad y términos de servicio.

**Responsabilidades:**
- **Usted debe:** Revisar y aceptar los términos del proveedor de servicios que elija
- **El proveedor de servicios:** Procesa los mensajes para su entrega a través de WhatsApp
- **WhatsApp/Meta:** Maneja la entrega final del mensaje con cifrado end-to-end

**Enlaces útiles:**
- [Política de Privacidad de WhatsApp](https://www.whatsapp.com/legal/privacy-policy)
- [Términos de Servicio de WhatsApp Business](https://www.whatsapp.com/legal/business-terms)

### Obligaciones Legales

Los datos pueden divulgarse si es requerido por:
- Orden judicial
- Autoridades administrativas competentes
- Fuerzas y cuerpos de seguridad
- Organismos reguladores

En estos casos, **usted es responsable** de proporcionar los datos almacenados en su sistema Dolibarr.

### Dolibarr ERP/CRM

Este módulo es una extensión para Dolibarr y utiliza la infraestructura de este sistema:
- Se aplican las políticas de seguridad y privacidad de su instalación Dolibarr
- El módulo respeta los permisos y roles de usuario de Dolibarr
- Los datos se almacenan siguiendo el modelo de datos de Dolibarr

**Más información:**
- [Dolibarr Official Website](https://www.dolibarr.org)
- [Dolibarr Documentation](https://wiki.dolibarr.org)

### Transferencias Internacionales

Los datos almacenados en su instalación de Dolibarr permanecen en la ubicación que usted haya elegido para su servidor.

Las transferencias de datos ocurren solo cuando:
- **Envío de mensajes:** Los datos se transmiten al servicio de mensajería WhatsApp (consulte la política de su proveedor de servicios)
- **Recepción de webhooks:** Los datos se reciben desde el servicio de mensajería WhatsApp

**Importante:** EasySoft Tech S.L. no realiza transferencias internacionales de sus datos, ya que no los almacena ni accede a ellos. La responsabilidad de las transferencias es entre usted y su proveedor de servicios de mensajería.

---

## Información Adicional

### Cookies y Tecnologías Similares

El módulo de Dolibarr utiliza las mismas políticas de cookies que su instalación de Dolibarr:
- **Cookies técnicas:** Necesarias para el funcionamiento (sesiones, autenticación)
- **Cookies de Dolibarr:** Según configuración de su instalación

El módulo en sí **no añade cookies adicionales**. Consulte la documentación de Dolibarr para más información sobre cookies.

### Tracking

- El módulo **no utiliza** cookies de seguimiento para publicidad
- No comparte datos con redes publicitarias
- No realiza seguimiento entre sitios web
- Las estadísticas de uso son locales en su sistema Dolibarr

---

## Glosario de Términos

### Términos de Privacidad

- **RGPD:** Reglamento General de Protección de Datos (UE 2016/679)
- **LOPDGDD:** Ley Orgánica de Protección de Datos Personales y garantía de los derechos digitales (España, Ley 3/2018)
- **Dato Personal:** Información que identifica o puede identificar a una persona física
- **Tratamiento:** Cualquier operación realizada sobre datos (recopilación, almacenamiento, uso, transmisión, eliminación, etc.)
- **Responsable del Tratamiento:** Persona o entidad que decide cómo y por qué se procesan los datos (en este caso, **usted** como usuario del módulo)
- **Encargado del Tratamiento:** Organización que procesa datos en nombre del responsable (ej: proveedor de API de WhatsApp)
- **Interesado:** Persona física cuyos datos personales son objeto de tratamiento (sus clientes)
- **Consentimiento:** Manifestación de voluntad libre, específica, informada e inequívoca
- **PII:** Información Personalmente Identificable (Personally Identifiable Information)

### Términos Técnicos del Módulo

- **Dolibarr:** Sistema ERP/CRM de código abierto donde se instala el módulo
- **Módulo WhatsApp:** Extensión de Dolibarr para integración con WhatsApp
- **Servicio de Mensajería WhatsApp:** Servicio externo que permite enviar mensajes de WhatsApp programáticamente
- **API Key:** Clave de autenticación para acceder al servicio de mensajería
- **Webhook:** Notificación automática que recibe su sistema cuando ocurre un evento en WhatsApp
- **Payload:** Contenido de datos enviado o recibido en comunicaciones
- **Instancia:** Conexión específica al servicio de mensajería WhatsApp (puede tener múltiples instancias)
- **Tercero:** En Dolibarr, entidad comercial (cliente, proveedor, etc.)
- **Agenda (actioncomm):** Sistema de eventos/actividades de Dolibarr donde se registran comunicaciones
- **Base64:** Formato de codificación para transmitir archivos binarios como texto

### Acrónimos

- **ERP:** Enterprise Resource Planning (Planificación de Recursos Empresariales)
- **CRM:** Customer Relationship Management (Gestión de Relaciones con Clientes)
- **API:** Application Programming Interface (Interfaz de Programación de Aplicaciones)
- **GPLv3:** GNU General Public License version 3 (Licencia de software libre)
- **TLS:** Transport Layer Security (Seguridad de la Capa de Transporte)
- **HTTPS:** HyperText Transfer Protocol Secure (Protocolo seguro de transferencia de hipertexto)
- **JSON:** JavaScript Object Notation (formato de intercambio de datos)
- **SQL:** Structured Query Language (Lenguaje de consulta estructurado)

---

## Documento Oficial

**Política de Privacidad - Módulo WhatsApp para Dolibarr**  
Desarrollado por **EasySoft Tech S.L.**  
CIF: B16885766

**Versión:** 2.0  
**Fecha de Vigencia:** 15 de diciembre de 2025  
**Última Actualización:** 15/12/2025  
**Licencia del Software:** GNU GPLv3

---

**© 2025 EasySoft Tech S.L. Todos los derechos reservados.**

Para consultas sobre esta política de privacidad, contacte con:
- [info@easysoft.es](mailto:info@easysoft.es)

---

### Avisos Legales

1. **Software de Código Abierto:** Este módulo es software libre bajo licencia GPLv3
2. **Sin Garantías:** El software se proporciona "tal cual" sin garantías de ningún tipo
3. **Responsabilidad:** El usuario es responsable del tratamiento de datos en su instalación
4. **API de Terceros:** El uso de APIs de WhatsApp está sujeto a sus propios términos y condiciones
