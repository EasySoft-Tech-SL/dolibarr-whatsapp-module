<?php
// Determinar el número de página actual
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
/**
 * Visor Mejorado de Eventos de Webhook
 *
 * Este script muestra los eventos guardados por webhook_receiver.php
 * con una interfaz moderna y funcionalidades avanzadas:
 * - Filtros mejorados
 * - Eliminación de eventos individuales
 * - Eliminación de todos los eventos
 * - Visualización mejorada con pestañas
 * - Diseño responsive
 */

// Configuración
$logFile = 'webhook_events.json';
$maxEvents = 1000;

// Manejar acciones
$message = '';
$messageType = '';

// Acción: Eliminar un evento específico
if (isset($_POST['delete_event']) && isset($_POST['event_index'])) {
    $index = (int)$_POST['event_index'];

    if (file_exists($logFile) && filesize($logFile) > 0) {
        $events = json_decode(file_get_contents($logFile), true);

        if (is_array($events) && isset($events[$index])) {
            // Guardar el tipo de evento para el mensaje de confirmación
            $eventType = isset($events[$index]['event']) ? $events[$index]['event'] : 'desconocido';
            $instanceName = isset($events[$index]['instance']) ? $events[$index]['instance'] : 'desconocida';

            // Eliminar el evento
            array_splice($events, $index, 1);

            // Guardar el archivo actualizado
            file_put_contents($logFile, json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            $message = "Evento '{$eventType}' de la instancia '{$instanceName}' eliminado correctamente.";
            $messageType = 'success';
        }
    }
}

// Acción: Eliminar todos los eventos
if (isset($_POST['delete_all_events']) && isset($_POST['confirm_delete_all']) && $_POST['confirm_delete_all'] === 'yes') {
    // Crear un array vacío y guardarlo
    file_put_contents($logFile, json_encode([], JSON_PRETTY_PRINT));

    $message = "Todos los eventos han sido eliminados correctamente.";
    $messageType = 'success';
}

// Función para mostrar JSON formateado
function prettyJson($json) {
    $result = '';
    $level = 0;
    $in_quotes = false;
    $in_escape = false;
    $ends_line_level = NULL;
    $json_length = strlen($json);

    for ($i = 0; $i < $json_length; $i++) {
        $char = $json[$i];
        $new_line_level = NULL;
        $post = "";
        if ($ends_line_level !== NULL) {
            $new_line_level = $ends_line_level;
            $ends_line_level = NULL;
        }
        if ($in_escape) {
            $in_escape = false;
        } else if ($char === '"') {
            $in_quotes = !$in_quotes;
        } else if (!$in_quotes) {
            switch ($char) {
                case '}': case ']':
                    $level--;
                    $ends_line_level = NULL;
                    $new_line_level = $level;
                    break;
                case '{': case '[':
                    $level++;
                    $ends_line_level = $level;
                    break;
                case ',':
                    $ends_line_level = $level;
                    break;
                case ':':
                    $post = " ";
                    break;
                case " ": case "\t": case "\n": case "\r":
                    $char = "";
                    $ends_line_level = $new_line_level;
                    $new_line_level = NULL;
                    break;
            }
        } else if ($char === '\\') {
            $in_escape = true;
        }
        if ($new_line_level !== NULL) {
            $result .= "\n".str_repeat("  ", $new_line_level);
        }
        $result .= $char.$post;
    }

    return $result;
}

// Cargar eventos
$events = [];
if (file_exists($logFile) && filesize($logFile) > 0) {
    $fileContent = file_get_contents($logFile);
    $events = json_decode($fileContent, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $error = "Error al decodificar el archivo JSON: " . json_last_error_msg();
        $events = [];
    }
}

// Filtrar eventos según parámetros
$eventType = isset($_GET['event']) ? $_GET['event'] : '';
$instance = isset($_GET['instance']) ? $_GET['instance'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Extraer tipos de eventos y nombres de instancias únicos para los selectores
$eventTypes = [];
$instances = [];
foreach ($events as $event) {
    if (isset($event['event'])) {
        $eventTypes[$event['event']] = true;
    }
    if (isset($event['instance'])) {
        $instances[$event['instance']] = true;
    }
}
$eventTypes = array_keys($eventTypes);
$instances = array_keys($instances);
sort($eventTypes);
sort($instances);

// Filtrar eventos según los criterios seleccionados
$filteredEvents = [];
foreach ($events as $index => $event) {
    // Filtro por tipo de evento
    $matchEvent = empty($eventType) || (isset($event['event']) && $event['event'] === $eventType);

    // Filtro por instancia
    $matchInstance = empty($instance) || (isset($event['instance']) && $event['instance'] === $instance);

    // Filtro por búsqueda de texto
    $matchSearch = empty($search) || (
        json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !== false &&
        stripos(json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $search) !== false
    );

    // Filtro por fecha desde
    $matchDateFrom = empty($dateFrom) || (
        isset($event['timestamp']) &&
        strtotime($event['timestamp']) >= strtotime($dateFrom . ' 00:00:00')
    );

    // Filtro por fecha hasta
    $matchDateTo = empty($dateTo) || (
        isset($event['timestamp']) &&
        strtotime($event['timestamp']) <= strtotime($dateTo . ' 23:59:59')
    );

    // Aplicar todos los filtros
    if ($matchEvent && $matchInstance && $matchSearch && $matchDateFrom && $matchDateTo) {
        $filteredEvents[$index] = $event;
    }
}

// Determinar el número de eventos por página
$eventsPerPageOptions = [10, 25, 50];
$eventsPerPage = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], $eventsPerPageOptions)
                ? (int)$_GET['per_page']
                : 10;
$totalFilteredEvents = count($filteredEvents);
$totalPages = ceil($totalFilteredEvents / $eventsPerPage);
$currentPage = min($currentPage, max(1, $totalPages));
$startIndex = ($currentPage - 1) * $eventsPerPage;

// Obtener solo los eventos para la página actual
$pagedEvents = array_slice($filteredEvents, $startIndex, $eventsPerPage, true);

// Construir los parámetros de paginación para URLs
$paginationParams = http_build_query(array_filter([
    'event' => $eventType,
    'instance' => $instance,
    'search' => $search,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'per_page' => $eventsPerPage
]));
$paginationBase = "?" . ($paginationParams ? $paginationParams . "&" : "");

// HTML para la página
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor de Eventos</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 1400px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
            padding: 15px 20px;
        }
        .event-card {
            transition: all 0.3s ease;
        }
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        .event-header {
            background-color: #f0f7ff;
            border-bottom: 1px solid #d1e7ff;
        }
        .event-content {
            background-color: #fdfdfd;
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 14px;
            padding: 20px;
            border-radius: 0 0 10px 10px;
        }
        .event-type-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .event-time {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .event-instance {
            font-size: 0.9rem;
            color: #495057;
        }
        .nav-pills .nav-link {
            border-radius: 20px;
            padding: 8px 15px;
            margin-right: 5px;
            font-size: 0.9rem;
        }
        .nav-pills .nav-link.active {
            background-color: #0d6efd;
        }
        .cursor-pointer {
            cursor: pointer;
        }
        .pre-wrap {
            white-space: pre-wrap;
            word-break: break-word;
        }
        .pagination {
            justify-content: center;
            margin-top: 30px;
        }
        .pagination .page-item .page-link {
            padding: 8px 16px;
            color: #0d6efd;
            background-color: #fff;
            border: 1px solid #dee2e6;
        }
        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }
        .btn-event-action {
            border-radius: 20px;
            font-size: 0.85rem;
            padding: 4px 12px;
        }
        .btn-delete-all {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }
        .modal-content {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .badge-message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-weight: normal;
        }
        .badge-success {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }
        .badge-error {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }
        .event-date-filter {
            max-width: 150px;
        }
        /* Animaciones */
        .fade-in {
            animation: fadeIn 0.5s;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .search-highlight {
            background-color: #ffffcc;
            font-weight: bold;
        }
        .action-buttons {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h1 class="mb-0">
                        <i class="fas fa-exchange-alt me-2 text-primary"></i>
                        Monitor de Eventos
                    </h1>

                    <!-- Botón para borrar todos los eventos -->
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAllModal">
                        <i class="fas fa-trash-alt me-2"></i> Eliminar Todos
                    </button>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="badge-message badge-<?php echo $messageType; ?> fade-in">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="badge-message badge-error fade-in">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-filter me-2"></i> Filtros
                        </h5>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" method="GET">
                            <div class="col-md-3">
                                <label for="event" class="form-label">Tipo de Evento</label>
                                <select name="event" id="event" class="form-select">
                                    <option value="">Todos los eventos</option>
                                    <?php foreach ($eventTypes as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $eventType === $type ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="instance" class="form-label">Instancia</label>
                                <select name="instance" id="instance" class="form-select">
                                    <option value="">Todas las instancias</option>
                                    <?php foreach ($instances as $inst): ?>
                                        <option value="<?php echo htmlspecialchars($inst); ?>" <?php echo $instance === $inst ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($inst); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="search" class="form-label">Buscar en Contenido</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="search" name="search" placeholder="Texto a buscar..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label for="date_from" class="form-label">Fecha Desde</label>
                                <input type="date" class="form-control event-date-filter" id="date_from" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                            </div>

                            <div class="col-md-3">
                                <label for="date_to" class="form-label">Fecha Hasta</label>
                                <input type="date" class="form-control event-date-filter" id="date_to" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                            </div>

                            <div class="col-md-6 d-flex align-items-end">
                                <div class="d-flex gap-2 w-100">
                                    <button type="submit" class="btn btn-primary flex-grow-1">
                                        <i class="fas fa-filter me-2"></i> Aplicar Filtros
                                    </button>
                                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-secondary flex-grow-1">
                                        <i class="fas fa-sync-alt me-2"></i> Limpiar Filtros
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Información de eventos -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2 text-info"></i>
                            Mostrando <?php echo count($pagedEvents); ?> de <?php echo $totalFilteredEvents; ?> eventos filtrados
                            (<?php echo count($events); ?> eventos totales)
                        </h6>
                    </div>

                    <!-- Selector de tamaño de página (futura implementación) -->
                    <div>
                        <div class="btn-group" role="group" aria-label="Eventos por página">
                            <span class="btn btn-outline-secondary disabled">Eventos por página:</span>
                            <?php foreach ($eventsPerPageOptions as $option): ?>
                                <a href="<?php echo $paginationBase . 'per_page=' . $option; ?>"
                                   class="btn btn-outline-primary <?php echo $eventsPerPage == $option ? 'active' : ''; ?>">
                                    <?php echo $option; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Lista de eventos -->
                <div class="event-list">
                    <?php if (empty($pagedEvents)): ?>
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h4>No hay eventos para mostrar</h4>
                                <p class="text-muted">Intenta con otros filtros o borra los filtros actuales</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pagedEvents as $index => $event): ?>
                            <?php
                                // Determinar el color del badge según el tipo de evento
                                $eventTypeClass = 'primary'; // por defecto
                                $eventName = isset($event['event']) ? $event['event'] : 'desconocido';

                                if (strpos($eventName, 'messages') !== false) {
                                    $eventTypeClass = 'success';
                                } elseif (strpos($eventName, 'error') !== false) {
                                    $eventTypeClass = 'danger';
                                } elseif (strpos($eventName, 'connection') !== false) {
                                    $eventTypeClass = 'info';
                                } elseif (strpos($eventName, 'qrcode') !== false) {
                                    $eventTypeClass = 'warning';
                                } elseif (strpos($eventName, 'group') !== false) {
                                    $eventTypeClass = 'secondary';
                                }
                            ?>
                            <div class="card event-card fade-in" id="event-<?php echo $index; ?>">
                                <div class="card-header event-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-<?php echo $eventTypeClass; ?> me-2 event-type-badge">
                                            <?php echo isset($event['event']) ? htmlspecialchars($event['event']) : 'Desconocido'; ?>
                                        </span>
                                        <span class="event-instance">
                                            <i class="fas fa-mobile-alt me-1"></i>
                                            <?php echo isset($event['instance']) ? htmlspecialchars($event['instance']) : 'Desconocida'; ?>
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <span class="event-time me-3">
                                            <i class="far fa-clock me-1"></i>
                                            <?php echo isset($event['timestamp']) ? htmlspecialchars($event['timestamp']) : ''; ?>
                                        </span>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-outline-primary btn-event-action me-1"
                                                    onclick="toggleEventDetails(<?php echo $index; ?>)">
                                                <i class="fas fa-eye"></i> Ver
                                            </button>

                                            <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este evento?');">
                                                <input type="hidden" name="event_index" value="<?php echo $index; ?>">
                                                <button type="submit" name="delete_event" class="btn btn-sm btn-outline-danger btn-event-action">
                                                    <i class="fas fa-trash-alt"></i> Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="event-content" id="event-details-<?php echo $index; ?>" style="display: none;">
                                    <ul class="nav nav-pills mb-3" id="event-tab-<?php echo $index; ?>" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="pretty-tab-<?php echo $index; ?>" data-bs-toggle="pill"
                                                    data-bs-target="#pretty-<?php echo $index; ?>" type="button" role="tab">
                                                <i class="fas fa-indent me-1"></i> Vista Formateada
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="raw-tab-<?php echo $index; ?>" data-bs-toggle="pill"
                                                    data-bs-target="#raw-<?php echo $index; ?>" type="button" role="tab">
                                                <i class="fas fa-code me-1"></i> JSON Raw
                                            </button>
                                        </li>
                                    </ul>
                                    <div class="tab-content">
                                        <div class="tab-pane fade show active" id="pretty-<?php echo $index; ?>" role="tabpanel">
                                            <pre class="pre-wrap mb-0"><?php
                                                $formattedJson = prettyJson(json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

                                                // Resaltar la búsqueda si existe
                                                if (!empty($search)) {
                                                    $formattedJson = preg_replace(
                                                        '/(' . preg_quote($search, '/') . ')/i',
                                                        '<span class="search-highlight">$1</span>',
                                                        htmlspecialchars($formattedJson)
                                                    );
                                                    echo $formattedJson;
                                                } else {
                                                    echo htmlspecialchars($formattedJson);
                                                }
                                            ?></pre>
                                        </div>
                                        <div class="tab-pane fade" id="raw-<?php echo $index; ?>" role="tabpanel">
                                            <pre class="pre-wrap mb-0"><?php
                                                echo htmlspecialchars(json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                                            ?></pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Paginación -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Navegación de páginas">
                                <ul class="pagination">
                                    <li class="page-item <?php echo ($currentPage <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo $paginationBase; ?>page=<?php echo $currentPage - 1; ?>" aria-label="Anterior">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>

                                    <?php
                                    // Mostrar enlaces de paginación
                                    $startPage = max(1, $currentPage - 2);
                                    $endPage = min($totalPages, $startPage + 4);

                                    if ($startPage > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="' . $paginationBase . 'page=1">1</a></li>';
                                        if ($startPage > 2) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                    }

                                    for ($i = $startPage; $i <= $endPage; $i++) {
                                        echo '<li class="page-item ' . ($i == $currentPage ? 'active' : '') . '">';
                                        echo '<a class="page-link" href="' . $paginationBase . 'page=' . $i . '">' . $i . '</a>';
                                        echo '</li>';
                                    }

                                    if ($endPage < $totalPages) {
                                        if ($endPage < $totalPages - 1) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                        echo '<li class="page-item"><a class="page-link" href="' . $paginationBase . 'page=' . $totalPages . '">' . $totalPages . '</a></li>';
                                    }
                                    ?>

                                    <li class="page-item <?php echo ($currentPage >= $totalPages) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo $paginationBase; ?>page=<?php echo $currentPage + 1; ?>" aria-label="Siguiente">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para confirmar eliminación de todos los eventos -->
    <div class="modal fade" id="deleteAllModal" tabindex="-1" aria-labelledby="deleteAllModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteAllModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i> Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar <strong>todos los eventos</strong>?</p>
                    <p class="text-danger"><strong>Esta acción no se puede deshacer.</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST">
                        <input type="hidden" name="confirm_delete_all" value="yes">
                        <button type="submit" name="delete_all_events" class="btn btn-danger">
                            <i class="fas fa-trash-alt me-2"></i> Sí, Eliminar Todos
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS y Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para alternar la visibilidad de los detalles de un evento
        function toggleEventDetails(index) {
            const detailsElement = document.getElementById('event-details-' + index);
            if (detailsElement) {
                if (detailsElement.style.display === 'none') {
                    detailsElement.style.display = 'block';
                } else {
                    detailsElement.style.display = 'none';
                }
            }
        }

        // Cuando se carga la página, abrir automáticamente el primer evento
        document.addEventListener('DOMContentLoaded', function() {
            // Si hay eventos y hay un parámetro de búsqueda, abrir todos los eventos que contienen la búsqueda
            const searchParam = "<?php echo htmlspecialchars($search); ?>";
            if (searchParam) {
                const eventCards = document.querySelectorAll('.event-card');
                eventCards.forEach(function(card) {
                    const cardId = card.id;
                    if (cardId) {
                        const index = cardId.replace('event-', '');
                        const detailsElement = document.getElementById('event-details-' + index);
                        if (detailsElement) {
                            detailsElement.style.display = 'block';
                        }
                    }
                });
            } else {
                // Si no hay búsqueda, solo abrir el primer evento si existe
                const firstEventDetails = document.querySelector('.event-content');
                if (firstEventDetails) {
                    firstEventDetails.style.display = 'block';
                }
            }
        });

        // Función para copiar al portapapeles
        function copyToClipboard(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);

            // Mostrar mensaje de copiado
            const toast = document.createElement('div');
            toast.className = 'position-fixed bottom-0 end-0 p-3';
            toast.style.zIndex = '5';
            toast.innerHTML = `
                <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header bg-success text-white">
                        <strong class="me-auto">Copiado</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        Contenido copiado al portapapeles
                    </div>
                </div>
            `;
            document.body.appendChild(toast);

            // Auto-cerrar el toast después de 3 segundos
            setTimeout(function() {
                document.body.removeChild(toast);
            }, 3000);
        }

        // Auto-actualización (opcional, para activar descomentar)
        /*
        function autoRefresh() {
            const autoRefreshEnabled = localStorage.getItem('autoRefreshEnabled') === 'true';
            if (autoRefreshEnabled) {
                location.reload();
            }
        }

        function toggleAutoRefresh() {
            const autoRefreshEnabled = localStorage.getItem('autoRefreshEnabled') === 'true';
            localStorage.setItem('autoRefreshEnabled', !autoRefreshEnabled);
            updateAutoRefreshButton();

            if (!autoRefreshEnabled) {
                // Iniciar el temporizador cuando se activa
                setTimeout(autoRefresh, 30000); // 30 segundos
            }
        }

        function updateAutoRefreshButton() {
            const autoRefreshEnabled = localStorage.getItem('autoRefreshEnabled') === 'true';
            const button = document.getElementById('autoRefreshButton');
            if (button) {
                button.innerHTML = autoRefreshEnabled ?
                    '<i class="fas fa-sync-alt fa-spin me-1"></i> Auto-actualización: ON' :
                    '<i class="fas fa-sync-alt me-1"></i> Auto-actualización: OFF';
                button.className = autoRefreshEnabled ?
                    'btn btn-sm btn-success' :
                    'btn btn-sm btn-outline-secondary';
            }
        }

        // Inicializar auto-actualización
        document.addEventListener('DOMContentLoaded', function() {
            updateAutoRefreshButton();
            if (localStorage.getItem('autoRefreshEnabled') === 'true') {
                setTimeout(autoRefresh, 30000); // 30 segundos
            }
        });
        */
    </script>
</body>
</html>
