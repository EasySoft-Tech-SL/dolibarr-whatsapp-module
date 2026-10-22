# Guía de usuario de WhatsApp for Dolibarr

Esta guía explica cómo dejar el módulo conectado, cómo enviar mensajes y cómo recibir los entrantes dentro de Dolibarr.

## Antes de empezar

Necesitas:

- Dolibarr 11 o posterior con acceso de administrador.
- Un servidor de mensajería Mensaru / whAPI con su URL y su token de API.
- Una instancia de WhatsApp conectada por QR en ese servidor.
- HTTPS en el Dolibarr que va a recibir los webhooks. La pasarela no debe enviar mensajes a una URL sin certificado válido.

## Instalación y activación

1. Copia la carpeta `whatsapp` del ZIP en `custom/` de tu Dolibarr, de modo que quede `custom/whatsapp/`.
2. Entra en Configuración, Módulos.
3. Busca WhatsApp, actívalo y confirma.
4. Asigna los permisos de WhatsApp a los usuarios que vayan a usarlo.

## Conectar con el servidor de mensajería

En Configuración, Módulos, WhatsApp, Configuración:

- URL del servidor: la dirección de tu Mensaru / whAPI, por ejemplo la que te haya facilitado EasySoft Tech.
- Token de API: el token de tu cuenta. Puede ser el global o el del usuario, si tu instalación lo tiene configurado así.

Guarda los cambios. Si los datos son correctos, la pantalla muestra el estado de la instancia. Si está desconectada, aparece el código QR: escanéalo desde WhatsApp en el móvil de la línea que vas a usar. Cuando la conexión se establece, la página se recarga y muestra el estado conectado.

Desde esa misma pantalla puedes cerrar la sesión de WhatsApp y enviar un mensaje de prueba. Úsalo antes de tocar nada más: si el mensaje de prueba llega, la conexión y el token están bien.

## Recibir mensajes: el webhook

Para que los mensajes entrantes aparezcan en Dolibarr, la pasarela tiene que enviarlos a la URL del webhook. El módulo necesita saber quién llama, así que exige un secreto compartido.

### Dónde están la URL y el secreto

En la pantalla de configuración del módulo, bloque «Webhook de entrada»:

- URL del webhook: la dirección completa del receptor. Tiene botón para copiarla.
- Secreto compartido: la cadena que debe enviar la pasarela. También tiene botón de copia.
- Estado: protegido, compatible con el token de la API, o bloqueado.

Al activar la opción de permitir webhook, el módulo genera un secreto si no existía. Si necesitas uno nuevo, pulsa «Generar secreto nuevo». El anterior deja de valer en ese momento, así que copia el nuevo a la pasarela justo después.

### Cómo se lo mandas a la pasarela

El receptor acepta el secreto de cuatro formas. Usa la que te permita tu pasarela:

- Cabecera `X-Webhook-Secret` con el valor del secreto.
- Cabecera `Authorization: Bearer <secreto>`.
- Campo `apikey` dentro del cuerpo JSON.
- Parámetro `secret` en la propia URL: `.../receiver.php?secret=<secreto>`.

En Mensaru, pega la URL en la configuración del webhook de la instancia y añade la cabecera si esa pantalla lo permite. Si no lo permite, o si al guardar la URL se pierden las cabeceras, usa el parámetro `secret` en la URL.

En Evolution API, el webhook se configura por instancia con `POST /webhook/set/{instancia}` y admite un objeto `headers`. La cabecera es la vía recomendada. Dos avisos si usas el parámetro en la URL: deja de funcionar con `byEvents: true`, porque Evolution añade el nombre del evento al final de la URL, y el secreto queda en los logs del proxy. El campo `apikey` del cuerpo lo pone Evolution con el token de la instancia y puede llegar vacío, así que úsalo solo como compatibilidad.

### Qué ocurre con cada petición

- Si el secreto coincide, el evento se guarda en el log. Si es un mensaje entrante, se busca el tercero por teléfono y se crea la entrada en su agenda.
- Si el secreto no coincide, la pasarela recibe 401 y no se guarda nada.
- Si el módulo no tiene ningún secreto configurado, recibe 503.
- Si el cuerpo supera 1 MiB, recibe 413.

El secreto no se guarda en el log. El campo `apikey` se guarda vacío cuando coincide con el secreto configurado.

## Enviar mensajes

Desde la ficha de un tercero o contacto, el botón de WhatsApp abre el formulario de envío: escribe el texto, elige plantilla si la tienes y adjunta un documento si hace falta. El módulo convierte el HTML de la plantilla al formato de WhatsApp y envía el PDF como documento.

Desde presupuestos, pedidos y facturas puedes enviar el documento al cliente con el mismo botón. Si el módulo está configurado para combinar texto y documento, el texto viaja como pie del archivo en un solo mensaje.

## Cola de mensajería

Cuando envías muchos mensajes seguidos, conviene espaciarlos para que la línea no se marque como spam. La cola guarda los mensajes y los entrega poco a poco.

En Configuración, Módulos, WhatsApp, Configuración están los parámetros: máximo por ejecución, ventana entre lotes, margen aleatorio, reintentos y espera entre reintentos. La tarea programada `processQueue` lee esos valores si no se le pasan parámetros.

La tarea se instala desactivada. Actívala en Configuración, Módulos, Tareas programadas, y ajústala a una ejecución por minuto. La guarda interna del módulo controla la cadencia real, así que ejecutarla a menudo no acelera los envíos.

En Utilidades, Cola de WhatsApp tienes el listado con el estado de cada mensaje, el último error y las acciones de reencolar, cancelar o eliminar. Los errores permanentes, como un número inválido o un fichero que ya no está en disco, se marcan como fallidos sin reintentar.

## Recordatorios

Los recordatorios se programan desde la agenda y se envían con la tarea `sendWhAPIReminder`. Configura el cron con la frecuencia que necesites; cinco minutos es un valor habitual. El número de reintentos se controla con el parámetro correspondiente de la configuración.

## Recepción y agenda

Cada mensaje entrante se registra en el log y, si el teléfono coincide con un tercero, se crea una entrada en su agenda con el texto recibido. La búsqueda usa el teléfono del tercero y el campo de WhatsApp de sus campos extra.

En la ficha del tercero, la pestaña de historial muestra los mensajes enviados y recibidos asociados.

## Logs

En el listado de logs de webhook ves todos los eventos recibidos: tipo, instancia, fecha, remitente y resultado. Sirve para comprobar si la pasarela está llegando y con qué secreto.

## Permisos

- Lectura: consultar mensajes, logs y configuración.
- Escritura: enviar mensajes y encolar.
- Administración: cambiar la configuración del módulo y gestionar la conexión.

El administrador de Dolibarr no hereda estos permisos. Asígnalos en la ficha del usuario.

## Problemas frecuentes

**El webhook responde 401 y no llega nada.** El secreto que envía la pasarela no coincide con el del módulo. Copia el valor actual desde la configuración y pégalo en la pasarela. Si acabas de regenerarlo, actualiza también el emisor.

**El webhook responde 503.** No hay secreto configurado ni token de API que sirva como compatibilidad. Abre la configuración y genera uno.

**El webhook responde 413.** El cuerpo supera 1 MiB. Revisa si la pasarela está enviando el binario del mensaje; el receptor solo necesita el evento.

**Los mensajes entrantes no aparecen en la agenda pero sí en el log.** El teléfono del remitente no coincide con ningún tercero. Revisa el formato del número y el campo de WhatsApp del tercero.

**El mensaje de prueba no llega.** Comprueba que la instancia aparece conectada, que el número va en formato internacional y que el token tiene saldo o plan activo.
