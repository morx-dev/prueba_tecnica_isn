<?php
declare(strict_types=1);

/**
 * Enrutador Frontal Principal (Front Controller)
 * Intercepta todas las peticiones HTTP web, analiza la URL amigable,
 * instancia el controlador correspondiente y ejecuta la acción solicitada.
 */

// Carga las configuraciones generales del sistema (sesiones, autoloader)
require_once __DIR__ . '/../config/bootstrap.php';
// Carga la capa de conexión a la base de datos
require_once __DIR__ . '/../config/database.php';

// Captura robusta de la URL amigable proveniente de la reescritura de Apache
$url = $_GET['url'] ?? '';
if ($url === '' && isset($_SERVER['REQUEST_URI'])) {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $url = trim($uri, '/');
}

// Limpieza, saneamiento y división de los segmentos de la URL
$url = rtrim($url, '/');
$url = filter_var($url, FILTER_SANITIZE_URL);
$urlPartes = $url ? explode('/', $url) : [];

// Mapeo dinámico de segmentos a Controlador, Acción y Parámetros (por defecto carga TallerController::index)
$nombreControlador = isset($urlPartes[0]) && $urlPartes[0] !== '' ? ucfirst($urlPartes[0]) . 'Controller' : 'TallerController';
$metodoAccion = $urlPartes[1] ?? 'index';
$parametros = array_slice($urlPartes, 2);

// Ruta completa del archivo físico del controlador solicitado
$archivoControlador = __DIR__ . '/../controllers/' . $nombreControlador . '.php';

// Verificación de existencia, carga y ejecución controlada del flujo MVC
if (file_exists($archivoControlador)) {
    require_once $archivoControlador;
    
    if (class_exists($nombreControlador)) {
        $controlador = new $nombreControlador();
        
        if (method_exists($controlador, $metodoAccion)) {
            // Ejecuta el método del controlador inyectando los parámetros de la URL
            call_user_func_array([$controlador, $metodoAccion], $parametros);
        } else {
            // Manejo de error HTTP 404: La acción (método) no existe en el controlador
            http_response_code(404);
            echo "<h1>404 Not Found</h1><p>La acción solicitada no existe.</p>";
        }
    } else {
        // Manejo de error HTTP 500: La clase no está definida correctamente
        http_response_code(500);
        echo "<h1>500 Internal Error</h1><p>La clase del controlador no está bien definida.</p>";
    }
} else {
    // Manejo de error HTTP 404: El archivo del controlador no fue encontrado
    http_response_code(404);
    echo "<h1>404 Not Found</h1><p>El controlador no existe.</p>";
}