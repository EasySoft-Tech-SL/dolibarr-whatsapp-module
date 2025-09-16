<?php

/**
 * Clase para convertir texto a voz usando APIs de terceros
 */
class TextToSpeech
{
    /**
     * Utiliza la API gratuita de Google Translate TTS sin autenticación
     * NOTA: Esta API no es oficial y podría cambiar sin previo aviso. Usar solo para pruebas.
     *
     * @param string $text Texto a convertir (máximo 200 caracteres)
     * @param string $language Código de idioma (es, en, etc.)
     * @param string $outFile Ruta donde guardar el archivo MP3
     * @param array $options Opciones adicionales (speed, voice)
     * @param bool $clearDirectory Si es true, limpia todos los archivos del directorio de salida
     * @return bool Éxito de la operación
     */
    public static function googleTranslateTTS($text, $language, $outFile, array $options = [], $clearDirectory = false)
    {
        try {
            // Limpiar directorio si se solicitó
            if ($clearDirectory && is_dir(dirname($outFile))) {
                self::clearDirectory(dirname($outFile));
            }

            // Opciones disponibles y valores por defecto
            $defaultOptions = [
                'speed' => 0.5,   // Velocidad: 0 (muy lento) a 1 (rápido)
                'voice' => ''     // Voz específica (depende del idioma)
            ];

            $options = array_merge($defaultOptions, $options);

            // Asegurar que la velocidad esté dentro del rango permitido (0-1)
            $speed = max(0, min(1, $options['speed']));

            // Ajustar el código de idioma según la voz seleccionada
            $langCode = $language;

            // Mapeo de voces disponibles por idioma
            $voiceMap = [
                'es' => [
                    'female' => 'es-ES', // Voz femenina (default para español)
                    'male' => 'es-US',   // Variante masculina (español de EE.UU.)
                    'es-ES' => 'es-ES',  // España
                    'es-MX' => 'es-MX',  // México
                    'es-US' => 'es-US'   // EE.UU.
                ],
                'en' => [
                    'female' => 'en-US', // Voz femenina (default para inglés)
                    'male' => 'en-GB',   // Variante masculina (inglés británico)
                    'en-US' => 'en-US',  // EE.UU.
                    'en-GB' => 'en-GB',  // Reino Unido
                    'en-AU' => 'en-AU'   // Australia
                ]
            ];

            // Aplicar selección de voz si está disponible
            if (!empty($options['voice'])) {
                $baseLanguage = explode('-', $language)[0]; // Obtener idioma base (es, en, etc.)
                if (isset($voiceMap[$baseLanguage]) && isset($voiceMap[$baseLanguage][$options['voice']])) {
                    $langCode = $voiceMap[$baseLanguage][$options['voice']];
                }
            }

            // Limitar texto a 200 caracteres para evitar errores
            $text = substr($text, 0, 200);

            // Formato de URL para Google Translate TTS incluyendo el parámetro ttsspeed
            $url = 'https://translate.google.com/translate_tts?ie=UTF-8&client=tw-ob&tl=' .
                urlencode($langCode) .
                '&q=' . urlencode($text) .
                '&ttsspeed=' . $speed; // Añadir parámetro de velocidad

            // Configurar User-Agent para evitar bloqueos
            $options_context = [
                'http' => [
                    'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n"
                ]
            ];

            $context = stream_context_create($options_context);
            $audio = file_get_contents($url, false, $context);

            if ($audio === false) {
                return false;
            }

            // Guardar el audio original (ya no necesitamos ajustar la velocidad con FFmpeg)
            file_put_contents($outFile, $audio);
            return file_exists($outFile);

            // Eliminamos el código de procesamiento con FFmpeg ya que la velocidad se controla directamente en la API
        } catch (Exception $e) {
            error_log('Error en TextToSpeech::googleTranslateTTS: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Método simple para textos largos usando Google Translate TTS
     * Divide el texto en fragmentos para superar la limitación de 200 caracteres
     * y los combina en un solo archivo MP3
     *
     * @param string $text Texto largo a convertir
     * @param string $language Código de idioma (es, en, etc.)
     * @param string $outFile Ruta donde guardar el archivo MP3
     * @param array $options Opciones adicionales (speed, voice, pitch, etc.)
     * @param bool $clearDirectory Si es true, limpia todos los archivos del directorio de salida
     * @return bool Éxito de la operación
     */
    public static function googleTranslateTTSLong($text, $language, $outFile, array $options = [], $clearDirectory = false)
    {
        try {
            // Directorio de salida
            $outputDir = dirname($outFile);

            // Limpiar directorio si se solicitó
            if ($clearDirectory && is_dir($outputDir)) {
                self::clearDirectory($outputDir);
            }

            // Directorio temporal para fragmentos
            $tempDir = $outputDir . '/temp_audio_' . uniqid();
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Dividir el texto en fragmentos de máximo 200 caracteres
            // intentando no cortar palabras
            $words = explode(' ', $text);
            $chunks = [];
            $currentChunk = '';

            foreach ($words as $word) {
                if (strlen($currentChunk . ' ' . $word) <= 200) {
                    $currentChunk .= ($currentChunk ? ' ' : '') . $word;
                } else {
                    $chunks[] = $currentChunk;
                    $currentChunk = $word;
                }
            }

            if ($currentChunk) {
                $chunks[] = $currentChunk;
            }

            // Generar MP3 para cada fragmento
            $chunkFiles = [];
            foreach ($chunks as $index => $chunk) {
                $chunkFile = $tempDir . '/chunk_' . $index . '.mp3';
                if (self::googleTranslateTTS($chunk, $language, $chunkFile, $options)) {
                    $chunkFiles[] = $chunkFile;
                }
            }

            // Alternativa: concatenar archivos directamente
            $fp = fopen($outFile, 'wb');
            foreach ($chunkFiles as $file) {
                $content = file_get_contents($file);
                fwrite($fp, $content);
                @unlink($file);  // Eliminar archivo temporal
            }
            fclose($fp);

            // Limpiar directorio temporal
            @rmdir($tempDir);

            return file_exists($outFile);
        } catch (Exception $e) {
            error_log('Error en TextToSpeech::googleTranslateTTSLong: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener lista de idiomas y voces disponibles
     *
     * @return array Lista de idiomas y voces soportados
     */
    public static function getAvailableVoices()
    {
        return [
            'es' => [
                'es-ES' => 'Español (España) - Mujer',
                'es-MX' => 'Español (México) - Mujer',
                'es-US' => 'Español (EE.UU.) - Hombre',
                'es-AR' => 'Español (Argentina) - Mujer',
                'es-CL' => 'Español (Chile) - Mujer',
                'es-CO' => 'Español (Colombia) - Mujer',
                'male' => 'Español - Hombre (es-US)',
                'female' => 'Español - Mujer (es-ES)'
            ],
            'en' => [
                'en-US' => 'Inglés (EE.UU.) - Mujer',
                'en-GB' => 'Inglés (Reino Unido) - Hombre',
                'en-AU' => 'Inglés (Australia) - Mujer'
            ],
            'fr' => [
                'fr-FR' => 'Francés - Mujer',
                'fr-CA' => 'Francés (Canadá) - Mujer'
            ],
            'de' => [
                'de-DE' => 'Alemán - Mujer'
            ],
            'it' => [
                'it-IT' => 'Italiano - Mujer'
            ],
            'pt' => [
                'pt-BR' => 'Portugués (Brasil) - Mujer',
                'pt-PT' => 'Portugués (Portugal) - Hombre'
            ],
            'ru' => [
                'ru-RU' => 'Ruso - Mujer'
            ],
            'ja' => [
                'ja-JP' => 'Japonés - Mujer'
            ],
            'zh' => [
                'zh-CN' => 'Chino (Mandarín) - Mujer',
                'zh-TW' => 'Chino (Taiwán) - Mujer'
            ],
            'ar' => [
                'ar-SA' => 'Árabe - Hombre'
            ]
        ];
    }

    /**
     * Obtener velocidades de voz predefinidas con descripciones
     *
     * @return array Velocidades disponibles
     */
    public static function getAvailableSpeeds()
    {
        return [
            0.2 => 'Muy lento',
            0.3 => 'Lento',
            0.5 => 'Normal',
            0.7 => 'Rápido',
            0.9 => 'Muy rápido'
        ];
    }

    /**
     * Limpia todos los archivos de audio en un directorio
     *
     * @param string $directory Directorio a limpiar
     * @param array $extensions Extensiones de archivo a eliminar (por defecto mp3)
     * @return bool Éxito de la operación
     */
    public static function clearDirectory($directory, $extensions = ['mp3', 'wav'])
    {
        if (!is_dir($directory)) {
            return false;
        }

        $success = true;

        try {
            $files = new \DirectoryIterator($directory);
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $extension = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                    if (in_array($extension, $extensions)) {
                        if (!unlink($file->getPathname())) {
                            $success = false;
                        }
                    }
                }
            }
            return $success;
        } catch (\Exception $e) {
            error_log('Error al limpiar directorio: ' . $e->getMessage());
            return false;
        }
    }
}
