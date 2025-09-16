<?php

/**
 * Clase HtmlToWhatsApp
 * Convierte HTML en texto formateado compatible con WhatsApp, con soporte para emojis
 */
class HtmlToWhatsApp {

    /**
     * Convierte HTML a formato WhatsApp
     *
     * @param string $html El contenido HTML a convertir
     * @return string Texto formateado para WhatsApp
     */
    public static function convert($html) {
        // Preservar emojis Unicode y convertir entidades de emojis
        $text = self::preserveEmojis($html);

        // Decodificar entidades HTML
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Convertir etiquetas básicas de formato
        $text = self::convertBasicFormatting($text);

        // Convertir enlaces
        $text = self::convertLinks($text);

        // Convertir saltos de línea
        $text = self::convertLineBreaks($text);

        // Convertir listas
        $text = self::convertLists($text);

        // Convertir imágenes de emojis a texto alternativo
        $text = self::convertEmojiImages($text);

        // Eliminar etiquetas HTML restantes
        $text = strip_tags($text);

        // Limpiar espacios en blanco excesivos
        $text = self::cleanWhitespace($text);


        return $text;
    }

    /**
     * Preserva emojis Unicode y convierte entidades de emojis a caracteres Unicode
     */
    private static function preserveEmojis($html) {
        // Convertir entidades numéricas HTML que representan emojis a sus caracteres Unicode
        $html = preg_replace_callback('/&#x([0-9a-f]+);/i', function($matches) {
            return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UCS-2BE');
        }, $html);

        // Convertir entidades decimales HTML a caracteres Unicode
        $html = preg_replace_callback('/&#([0-9]+);/', function($matches) {
            return mb_chr($matches[1], 'UTF-8');
        }, $html);

        return $html;
    }

    /**
     * Convierte imágenes de emojis a su texto alternativo o emoji equivalente
     */
    private static function convertEmojiImages($html) {
        // Buscar imágenes con clase "emoji" o alt que contiene emojis
        return preg_replace_callback('/<img[^>]*(?:class=["\']?(?:[^"\']*\s)?emoji(?:\s[^"\']*)?["\']?)[^>]*alt=["\']([^"\']*)["\'][^>]*>|<img[^>]*alt=["\']([^"\']*)["\'][^>]*(?:class=["\']?(?:[^"\']*\s)?emoji(?:\s[^"\']*)?["\']?)[^>]*>/i',
            function($matches) {
                // Devolver el contenido del atributo alt que debería contener el emoji
                return !empty($matches[1]) ? $matches[1] : (!empty($matches[2]) ? $matches[2] : '');
            },
        $html);
    }

    /**
     * Convierte etiquetas básicas de formato HTML a equivalentes de WhatsApp
     */
    private static function convertBasicFormatting($text) {
        // Negrita: <strong>, <b> → *texto*
        $text = preg_replace('/<(strong|b)>(.*?)<\/(strong|b)>/is', '*$2*', $text);

        // Cursiva: <em>, <i> → _texto_
        $text = preg_replace('/<(em|i)>(.*?)<\/(em|i)>/is', '_$2_', $text);

        // Tachado: <s>, <strike>, <del> → ~texto~
        $text = preg_replace('/<(s|strike|del)>(.*?)<\/(s|strike|del)>/is', '~$2~', $text);

        // Monoespaciado: <code>, <pre> → ```texto```
        $text = preg_replace('/<(code|pre)>(.*?)<\/(code|pre)>/is', '```$2```', $text);

        return $text;
    }

    /**
     * Convierte enlaces HTML en texto con la URL
     */
    private static function convertLinks($text) {
        // Extraer enlaces y colocarlos después del texto
        return preg_replace_callback('/<a\s+[^>]*href=(["\'])(.*?)\1[^>]*>(.*?)<\/a>/is',
            function($matches) {
                $url = $matches[2];
                $linkText = $matches[3];

                // Si el texto del enlace es igual a la URL, solo devolver la URL
                if (trim($linkText) == trim($url)) {
                    return trim($url);
                }

                // De lo contrario, devolver "texto: URL"
                return trim($linkText) . ': ' . trim($url);
            },
        $text);
    }

    /**
     * Convierte saltos de línea HTML en saltos de línea reales
     */
    private static function convertLineBreaks($text) {
        // Convertir <br>, <p>, </p>, <div>, etc. en saltos de línea
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
        $text = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n\n", $text);
        $text = preg_replace('/<\/(div|h[1-6]|table|tr|blockquote)>\s*<(div|h[1-6]|table|tr|blockquote)[^>]*>/i', "\n\n", $text);
        $text = preg_replace('/<(p|div|h[1-6]|table|tr|blockquote)[^>]*>/i', "", $text);
        $text = preg_replace('/<\/(p|div|h[1-6]|table|tr|blockquote)>/i', "\n", $text);

        return $text;
    }

    /**
     * Convierte listas HTML en formato de texto con viñetas
     */
    private static function convertLists($text) {
        // Extraer listas y formatearlas con viñetas simples
        $text = preg_replace_callback('/<(ol|ul)>(.*?)<\/\1>/is',
            function($matches) {
                $listType = $matches[1];
                $listContent = $matches[2];

                // Extraer elementos de la lista
                preg_match_all('/<li>(.*?)<\/li>/is', $listContent, $items);

                $result = "";
                foreach ($items[1] as $index => $item) {
                    // Para listas ordenadas (ol), usar números; para listas no ordenadas (ul), usar viñetas
                    $bullet = ($listType == 'ol') ? ($index + 1) . '. ' : '• ';
                    $result .= $bullet . trim($item) . "\n";
                }

                return "\n" . $result;
            },
        $text);

        return $text;
    }

    /**
     * Limpia espacios en blanco excesivos
     */
    private static function cleanWhitespace($text) {
        // Reemplazar múltiples espacios en blanco con uno solo
        $text = preg_replace('/[ \t]+/', ' ', $text);

        // Reemplazar múltiples saltos de línea con un máximo de dos
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }


}

/* // Ejemplo de uso
$html = '🗻Estimado Alberto Luque Rivas, le remitimos la factura del pago final para el viaje escolar de su hijo, <strong>el pago se debe realizar mediante tarjeta de crédito o por bizzum por la pasarela de pago</strong>.<br />
Para la realización del pago, tienen una semana desde la recepción del email.<br />
<br />
<strong>Recuerde que este importe, es el pago final o parcial, por tanto no debe volver a registrarse en la plataforma, solo debe entrar en el link que aparece a continuación:</strong><br />
<br />
<a href="__REDSYS_PAYMENT_URL__">LINK PAGO</a><br />
O pulse aquí: __REDSYS_PAYMENT_URL__<br />
<br />
Aquí dispone de nuestro portal de facturación donde podrá acceder directamente desde este <strong><a href="https://portalreservas.aventurocio.com/central-facturacion/facturas.php?token_person=__PERSON_TOKEN__">ENLACE PORTAL FACTURACIÓN</a></strong> que es único para <strong>__PERSON_FULLNAME__</strong> o en este otro <strong><a href="https://portalreservas.aventurocio.com/central-facturacion">ENLACE PORTAL FACTURACIÓN</a></strong> donde ingresando DNI + (CORREO o TELEFONO) del registro del alumno puede acceder.<br />
En este Portal de Facturación tendréis disponible tanto el pago, los enlaces, y toda la información correspondiente a las facturas emitidas a su nombre, tanto pendiente de pago como pagadas.<br />';

echo HtmlToWhatsApp::convert($html); */
