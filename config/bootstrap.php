<?php
declare(strict_types=1);

session_start();

/**
 * Autoload simple por nombre exacto de archivo.
 * Implementa el autocargador estándar para la resolución automática de clases.
 * No usa namespaces ni strtolower() porque los nombres de archivo
 * son PascalCase (TallerController.php) y en un servidor Linux
 * el sistema de archivos SI distingue mayusculas/minusculas.
 * 
 * @param string $clase Nombre de la clase que se intenta instanciar.
 */
spl_autoload_register(function (string $clase): void {
    // Define la ruta base un nivel arriba de la carpeta actual (config/)
    $rutaBase = __DIR__ . '/../';

    // Carpetas donde puede vivir una clase, en orden de busqueda
    $carpetas = ['config/', 'core/', 'models/', 'controllers/'];

    // Itera sobre las carpetas permitidas para localizar y cargar el archivo correspondiente
    foreach ($carpetas as $carpeta) {
        $archivo = $rutaBase . $carpeta . $clase . '.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            return;
        }
    }
});