# Auditoría de seguridad — WhatsApp for Dolibarr

**Fecha:** 2026-08-18
**Versión auditada:** 1.10 (tras la corrección del SQLi de INCIBE, ver [INCIBE-CVE-sqli-webhook.md](INCIBE-CVE-sqli-webhook.md))
**Alcance:** módulo completo (endpoints públicos, AJAX, REST, admin, manejo de ficheros, secretos).
**Método:** revisión multi-agente (recon → 5 dimensiones → verificación adversarial → síntesis).

## Resultado

11 hallazgos confirmados/plausibles (1 falso positivo descartado). **Todos corregidos en la v1.10.**

| # | Sev. | Problema | Fichero | Corrección |
|---|------|----------|---------|------------|
| 1 | Alto | XSS almacenado/DOM: texto de WhatsApp entrante ejecuta script en la sesión del agente | `js/whatsapp.js.php`, origen `public/webhook/receiver.php` | Escape en el sink (`waEsc`) para `content`, nombre de instancia y `data.error`; escape server-side de nombres/refs de objetos vinculados en `ajax/getMessages.php` |
| 2 | Alto | Webhook sin autenticación de origen (spoofing / inyección) | `public/webhook/receiver.php` | **Corregido en 1.11**: secreto compartido verificado con `hash_equals()`, CORS retirado y límite de tamaño. Ver [AVISO-2026-10-21-autenticacion-webhook.md](AVISO-2026-10-21-autenticacion-webhook.md) |
| 3 | Alto | Lectura arbitraria de ficheros / exfiltración vía `whatsapp_pdf_files[]` | `class/actions_whatsapp.class.php` | Allowlist: `realpath` + prefijo `DOL_DATA_ROOT` + extensión `.pdf`; re-check de permiso en el handler |
| 4 | Medio | `apikey` en claro, visible y buscable en la UI | `class/webhooklog.class.php`, `webhooklog_list/card.php` | `visible=0`, sin `searchall`; `enablepermissioncheck=1` |
| 5 | Medio | IDOR + CSRF en gestión de recordatorios | `ajax/reminderSchedule.php` | Se elimina `NOCSRFCHECK` (valida el token que ya envía el cliente) + exige POST |
| 6 | Medio | Token `WHATSAPP_SERVER_TOKEN` en URL → logs, página sin gate | `admin/plan_usage.php` | `accessforbidden()` para no-admin; HTTPS-only en la petición saliente |
| 7 | Medio | XSS de 2º orden (nombre de tercero vía API REST) | `ajax/getMessages.php` | `dol_escape_htmltag()` en name/ref/label/getFullName |
| 8 | Bajo | `admin/plan_usage.php` sin control de acceso | `admin/plan_usage.php` | Gate de admin (junto con #6) |
| 9 | Bajo | HTML remoto impreso sin escapar | `admin/plan_usage.php` | HTTPS-only + gate de admin (fuente de confianza) |
| 10 | Bajo | `viewer.php` sin auth/CSRF con escritura de fichero | `public/webhook/viewer.php` | **Eliminado** (endpoint obsoleto; los eventos van a BD) |
| 11 | Bajo | REST: excepción interna devuelta al cliente (CWE-209) + `$sender` sin inicializar | `class/api_whatsapp.class.php` | Mensaje genérico + `dol_syslog`; `sender` inicializado |

## Decisión pendiente (fuera de esta versión)

- ~~**Autenticación de origen del webhook (#2):** se recomienda añadir verificación HMAC-SHA256 del cuerpo con un secreto configurable (`WHATSAPP_WEBHOOK_SECRET`, `hash_equals`). No se aplica en 1.10 para no romper la integración con la pasarela en producción; requiere coordinar el despliegue del secreto en ambos extremos.~~
  **Resuelto en 1.11** (2026-10-22) tras el reporte privado del 2026-10-21: secreto compartido
  con `hash_equals()` y varias vías de envío para no romper la pasarela. Ver
  [AVISO-2026-10-21-autenticacion-webhook.md](AVISO-2026-10-21-autenticacion-webhook.md).
  No se implementó HMAC del cuerpo porque el cuerpo lo construye Evolution API y la pasarela
  no puede firmarlo sin hacer de proxy.

## Verificado y limpio

- SQLi de `remoteJid` (v1.10): fix presente y correcto, no reintroducido.
- `contactName` en el chat: insertado con `.text()` (seguro).
- Cron emisor de recordatorios: filtra por `code` y `entity` (limita el impacto del IDOR).
- Páginas admin hermanas (`setup.php`, `about.php`, etc.): ya usan `accessforbidden()`.
- POST de las cards (`globalcard`): el token CSRF del framework sí se valida.
