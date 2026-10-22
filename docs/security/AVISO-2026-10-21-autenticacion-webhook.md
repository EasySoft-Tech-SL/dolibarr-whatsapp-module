# Aviso de seguridad — Webhook sin autenticación de origen (CWE-306 / CWE-345)

**Módulo:** WhatsApp for Dolibarr
**Versiones afectadas:** 1.9 y anteriores, y 1.10 (todas las que incluyen `public/webhook/receiver.php`)
**Versión corregida:** 1.11
**Fecha de corrección:** 2026-10-22
**Origen:** reporte privado de un investigador externo por correo electrónico (2026-10-21), no coordinado por un CNA
**CWE:** CWE-306 (Missing Authentication for Critical Function) / CWE-345 (Insufficient Verification of Data Authenticity)
**CVSS v4.0:** 5.3 — `AV:N/AC:L/AT:N/PR:N/UI:N/VC:N/VI:L/VA:L/SC:N/SI:N/SA:N` (vector del informe, suscrito por el fabricante)

---

## 1. Resumen

El endpoint público `public/webhook/receiver.php` se ejecuta sin sesión de Dolibarr
(`NOLOGIN`, `NOCSRFCHECK`), que es lo esperable en un receptor de webhooks, pero su única
comprobación era la constante `WHATSAPP_WEBHOOK_ALLOW`: indica si el webhook está activo,
no quién llama. El campo `apikey` del cuerpo JSON se almacenaba en el log y nunca se
comparaba con ningún secreto configurado.

Con el webhook activo, cualquier cliente anónimo que conociera la URL podía enviar un
JSON con `event = messages.upsert` y:

- crear entradas falsas en la agenda de un tercero, si el teléfono enviado coincidía con
  uno existente (bajo el usuario configurado en `WHATSAPP_WEBHOOK_USER_ID`), y
- insertar una fila por petición en la tabla de logs, sin límite.

No se demostró lectura de datos ni ejecución de código. El impacto es de integridad
(registros de comunicaciones falsos) y de disponibilidad limitada (crecimiento de la
tabla de logs).

## 2. Detalle técnico

```php
// Código vulnerable (hasta 1.10)
if (! defined('NOCSRFCHECK')) define('NOCSRFCHECK', '1');
if (! defined('NOLOGIN'))     define('NOLOGIN', '1');
...
if (!$conf->global->WHATSAPP_WEBHOOK_ALLOW) {
    http_response_code(403); die(...);   // solo "está activo"
}
...
$apikey = isset($eventData['apikey']) ? $eventData['apikey'] : '';
...
$webhookLog->apikey = $apikey;           // se guarda, no se compara
```

No existía ninguna llamada a `hash_equals()` ni comparación de token en el handler, y la
respuesta llevaba `Access-Control-Allow-Origin: *`.

## 3. Corrección aplicada (1.11)

1. **Secreto compartido obligatorio.** El receptor construye la lista de secretos
   configurados (`WHATSAPP_WEBHOOK_SECRET` y, como compatibilidad,
   `WHATSAPP_SERVER_TOKEN`) y compara el valor presentado con `hash_equals()`, en tiempo
   constante. Sin secreto configurado responde **503**; con un valor distinto, **401**.
2. **Vías aceptadas**, para no obligar a cambiar de pasarela: cabecera
   `X-Webhook-Secret`, `Authorization: Bearer`, campo `apikey` del cuerpo (lo que envía
   Evolution API) y parámetro `secret` de la URL.
3. **El secreto no se persiste.** Si el `apikey` recibido coincide con un secreto
   configurado, se guarda vacío en el log.
4. **Endurecimiento:** se retira el CORS abierto, se rechazan cuerpos de más de 1 MiB
   antes de decodificarlos y se exige que el JSON decodificado sea un objeto.
5. **Bug adyacente:** el `WHERE` tenía precedencia incorrecta (`A OR B AND entity`), de
   modo que la restricción de entidad solo se aplicaba a la segunda rama. Se agrupa con
   paréntesis en el receptor y en la API REST.

La lógica de comparación vive en `class/whatsappwebhookauth.class.php` (PHP puro, sin
dependencias de Dolibarr) y tiene pruebas unitarias en
`test/phpunit/WhatsappWebhookAuthTest.php`.

## 4. Despliegue

- Al activar el webhook, el módulo genera `WHATSAPP_WEBHOOK_SECRET` si no existe.
- La pantalla de configuración muestra la URL y el secreto con botones de copia, el
  estado de protección y las instrucciones para Mensaru y Evolution API.
- Si la pasarela no admite cabeceras personalizadas, el secreto puede ir en la propia URL
  (`?secret=...`), que sobrevive a las reescrituras de configuración de Mensaru.
- Las instalaciones que ya comparten `WHATSAPP_SERVER_TOKEN` con la pasarela siguen
  funcionando sin tocar nada.

## 5. Crédito

Reportado de forma privada por **kta1kri** el 2026-10-21, con reproducción sobre el código
público y sin pruebas contra sistemas en producción. El hallazgo coincidía con el punto
#2 (Alto) de la auditoría interna del 2026-08-18, que se había diferido por compatibilidad.
