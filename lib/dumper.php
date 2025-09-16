<?php

function dumper($param, $exit = false)
{
	//Param to json
	$json = json_encode($param);
	$uniqueHex = bin2hex(random_bytes(4)); // Genera un valor hexadecimal único
	echo '<!DOCTYPE html>
          <html lang="es">
          <head>
              <meta charset="UTF-8">
              <title>Visualización JSON</title>
              <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsoneditor@9.4.2/dist/jsoneditor.min.css">
              <script src="https://cdn.jsdelivr.net/npm/jsoneditor@9.4.2/dist/jsoneditor.min.js"></script>
          </head>
          <body>
              <div id="jsoneditor_' . $uniqueHex . '" style="width: 100% !important; height: 100% !important"></div>
              <script>
                  // Asegúrate de que el JSON esté bien formateado antes de pasarlo a JSON Editor
                  var nodosPropiedad = null;
                  var json = ' . json_encode(json_decode($json)) . ';
                  var container = document.getElementById("jsoneditor_' . $uniqueHex . '");
                  var options = {
                      mode: "view",
                      modes: ["code", "form", "text", "tree", "view"],
                      onError: function (err) {
                          alert(err.toString());
                      },
                      language: "es", // Establece el idioma a español
                      theme: "dark", // Establece el tema a oscuro (negro)
                      valueDisplayMode: "value",
                  };
                  var editor = new JSONEditor(container, options, json);

                  // Extensión para mostrar el tipo de dato
                  editor.setMode("view"); // Asegura que estás en modo de vista para que funcione la extensión

                  // Función para obtener el tipo de dato
                  function obtenerTipoDato(valor) {
                      return Array.isArray(valor) ? "Array" : typeof valor;
                  }

                  // Recorre todas las propiedades y muestra el tipo de dato a la izquierda
                  function mostrarTiposDeDatos(objeto, ruta) {
                      for (var propiedad in objeto) {
                          if (objeto.hasOwnProperty(propiedad)) {
                              var rutaCompleta = ruta ? ruta + "." + propiedad : propiedad;
                              var tipoDato = obtenerTipoDato(objeto[propiedad]);

                              // Busca todos los nodos de propiedad y encuentra el correcto por el texto interno
                              nodosPropiedad = container.querySelectorAll(\'.property-name\');
                              for (var i = 0; i < nodosPropiedad.length; i++) {
                                  if (nodosPropiedad[i].innerText.trim() === propiedad) {
                                      // Crea un nuevo elemento span para mostrar el tipo de dato
                                      var tipoDatoSpan = document.createElement("span");
                                      tipoDatoSpan.style.color = "#888"; // Color del tipo de dato en modo oscuro
                                      tipoDatoSpan.style.marginRight = "5px";
                                      tipoDatoSpan.textContent = "[" + tipoDato + "] ";

                                      // Inserta el tipo de dato antes del nodo de la propiedad
                                      nodosPropiedad[i].parentNode.insertBefore(tipoDatoSpan, nodosPropiedad[i]);

                                      // Expande la propiedad si no está expandida
                                      if (!nodosPropiedad[i].parentNode.classList.contains("expanded")) {
                                          editor.nodeClick(nodosPropiedad[i]);
                                      }

                                      break;
                                  }
                              }
                          }
                      }
                  }

                  // Expande todas las propiedades por defecto
                  editor.expandAll();

                  mostrarTiposDeDatos(json, ""); // Llama a la función con el JSON completo
              </script>
          </body>
          </html>';
	if ($exit)
		exit(0);
}
