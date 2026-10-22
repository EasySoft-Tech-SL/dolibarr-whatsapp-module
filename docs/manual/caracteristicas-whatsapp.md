# Características de WhatsApp for Dolibarr

WhatsApp for Dolibarr conecta el ERP con WhatsApp a través de un servidor de mensajería compatible con la API de EasySoft Tech (Mensaru / whAPI). Permite enviar mensajes desde cualquier ficha y recibir los entrantes dentro de Dolibarr.

## Envío

- Mensajes de texto desde la ficha del tercero, presupuestos, pedidos, facturas y otros objetos.
- Envío de documentos en PDF como adjunto.
- Audio grabado o generado a partir de un texto.
- Texto y documento en un solo mensaje cuando la pasarela lo admite.
- Conversión de HTML a texto plano para que el mensaje llegue legible.
- Plantillas de mensajes con campos del objeto.
- Mensaje de prueba desde la pantalla de configuración.

## Recepción

- Webhook público que recibe los eventos de la pasarela.
- Registro de la conversación en la agenda del tercero, buscando por teléfono.
- Asociación automática con el tercero mediante el teléfono o el campo de WhatsApp del tercero.
- Historial de mensajes en una pestaña de la ficha.
- Log de todos los eventos recibidos, con filtros por evento e instancia.

## Automatización

- Cola de mensajería con envío escalonado y ventana entre lotes, pensada para no parecer spam.
- Reintentos con espera creciente ante errores temporales.
- Recordatorios programados por cron, con número máximo de reintentos.
- Encolado de mensajes desde otros módulos mediante la API interna.

## Administración

- Conexión de la instancia con código QR desde la propia configuración.
- Estado de la conexión y cierre de sesión.
- Permisos separados de lectura, escritura y administración.
- Compatibilidad con Dolibarr 11 y posteriores.

## Webhook autenticado (desde 1.11)

- Secreto compartido obligatorio, comparado en tiempo constante.
- Acepta el secreto por cabecera, Bearer, campo del cuerpo o parámetro de URL.
- Rechaza peticiones sin secreto (401) y configuraciones sin secreto (503).
- Límite de tamaño del cuerpo y sin CORS abierto.
- La pantalla de configuración muestra la URL y el secreto listos para copiar en Mensaru o Evolution API.
