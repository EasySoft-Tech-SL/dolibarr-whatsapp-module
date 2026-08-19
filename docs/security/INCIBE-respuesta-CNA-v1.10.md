# Respuesta a INCIBE (CNA) — CWE-89 Inyección SQL en webhook

**De:** EasySoft Tech S.L. — Equipo de desarrollo del módulo "WhatsApp for Dolibarr"
**Para:** INCIBE-CERT (Coordinador Nacional / CNA de España)
**Referencia:** Aviso de vulnerabilidad CWE-89 (SQL Injection) en `public/webhook/receiver.php` del módulo WhatsApp for Dolibarr v1.9
**Fecha de remisión:** 2026-08-19
**Clasificación:** Respuesta a notificación de divulgación coordinada

---

## 1. Confirmación de la vulnerabilidad

Confirmamos la existencia de la vulnerabilidad de **inyección SQL (CWE-89)** en el
endpoint público `public/webhook/receiver.php` del módulo **WhatsApp for Dolibarr**
(v1.9 y anteriores), tal y como se describe en el aviso. Suscribimos la valoración de
severidad **CVSS v4.0 = 8.8** (`AV:N/AC:L/AT:N/PR:N/UI:N/VC:H/VI:N/VA:L`).

### Causa raíz

El endpoint, que no requiere autenticación (`NOLOGIN`) y se expone únicamente cuando la
constante `WHATSAPP_WEBHOOK_ALLOW` está habilitada, construía una consulta SQL
interpolando directamente el número de teléfono derivado del campo `data.key.remoteJid`
del cuerpo JSON de la petición, sin saneamiento ni parametrización:

```php
// Código vulnerable (v1.9)
$phoneext = explode('@', $phoneDestination)[0];
$phone = substr($phoneext, 2);
...
$sql .= " WHERE REGEXP_REPLACE(s.phone, '[^0-9]', '') = '$phone'
          OR REGEXP_REPLACE(se.whatsapp_phone_number, '[^0-9]', '') = '$phone'";
```

Un atacante remoto no autenticado podía manipular `remoteJid` para alterar la consulta
(ej. `XX' OR SLEEP(5)-- -@s.whatsapp.net`), demostrándose la explotabilidad mediante
*time-based blind SQL injection*, con impacto en confidencialidad (exfiltración) y
disponibilidad (degradación del servicio).

## 2. Corrección aplicada

El problema se corrigió en la **versión 1.10** con doble barrera en el fichero
`public/webhook/receiver.php`:

1. **Saneamiento semántico:** el identificador se reduce a solo dígitos **antes** de
   intervenir en la consulta, de modo que no puede contener comillas ni palabras clave SQL.
2. **Defensa en profundidad:** escape adicional mediante la capa de base de datos.

```php
// Código corregido (v1.10)
$phoneext = explode('@', $phoneDestination)[0];
$phoneext = preg_replace('/[^0-9]/', '', $phoneext); // solo dígitos
$phone = substr($phoneext, 2);
if (!empty($phone)) {
    $phone = $db->escape($phone); // defensa en profundidad
    $sql .= " WHERE REGEXP_REPLACE(s.phone, '[^0-9]', '') = '" . $phone . "'
              OR REGEXP_REPLACE(se.whatsapp_phone_number, '[^0-9]', '') = '" . $phone . "'";
    ...
}
```

### Verificación del saneamiento

| Entrada `remoteJid`                  | `$phone` resultante                |
|--------------------------------------|------------------------------------|
| `34661431341@s.whatsapp.net`         | `661431341` (número real: intacto) |
| `XX' OR SLEEP(5)-- -@s.whatsapp.net` | vacío → no se ejecuta la consulta  |
| `00' UNION SELECT 1-- @x`            | `1` (solo dígitos, inofensivo)     |

Se comprobó además que un `.pdf` real fuera de `DOL_DATA_ROOT` es rechazado en el
manejo de ficheros y que `php -l` no reporta errores en los ficheros modificados.

## 3. Alcance ampliado

- **Segunda instancia (misma raíz):** el mismo patrón vulnerable estaba duplicado en el
  endpoint REST `class/api_whatsapp.class.php` (método `processWebhook`, requiere API
  key). Se corrigió con idéntico criterio.
- **Auditoría complementaria:** se realizó una auditoría completa del módulo
  (11 hallazgos: XSS, LFI, CSRF/IDOR, exposición de secretos, CWE-209, etc.), **todos
  corregidos en la v1.10**. Documentación: `docs/security/AUDITORIA-2026-08-18.md`.

## 4. Publicación y trazabilidad

La corrección está publicada en el repositorio oficial:

- Repositorio: `https://github.com/EasySoft-Tech-SL/dolibarr-whatsapp-module`
- Commit de la corrección: `8108537` (fix SQLi webhook, CWE-89)
- Tag: **`v1.10`** (publicado el 2026-08-19)
- GitHub Security Advisory **GHSA-53wq-3hgf-rg4j** (estado draft/embargo, con el detalle
  técnico completo y el PoC, a disposición de INCIBE para validación):
  `https://github.com/EasySoft-Tech-SL/dolibarr-whatsapp-module/security/advisories/GHSA-53wq-3hgf-rg4j`
- Ficheros modificados: `public/webhook/receiver.php`, `class/api_whatsapp.class.php`,
  `core/modules/modWhatsapp.class.php` (versión → 1.10), `ChangeLog.md`

## 5. Mitigación temporal

Para instalaciones que no puedan actualizar a 1.10 de inmediato: **desactivar la
constante `WHATSAPP_WEBHOOK_ALLOW`**, lo que deja de exponer el endpoint público.

## 6. Coordinación

Quedamos a disposición de INCIBE para coordinar la fecha de publicación del CVE y el
crédito al investigador que reportó la vulnerabilidad.

---

**EasySoft Tech S.L.** — Equipo de desarrollo WhatsApp for Dolibarr
Firma electrónica a petición.
