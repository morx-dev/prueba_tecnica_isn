<?php
/**
 * Vista de Formulario de Talleres (Crear / Editar).
 * Renderiza un formulario HTML dinámico reutilizable tanto para el registro 
 * como para la actualización de información de talleres, gestionando la 
 * persistencia de datos previos ante errores de validación.
 * 
 * Variables disponibles:
 * @var string     $modo     Modo de operación actual ('crear' | 'editar').
 * @var array|null $taller   Datos del taller (null si es creación limpia sin errores previos).
 * @var array      $errores  Lista de mensajes de validación generados en el controlador.
 */

$modo = $modo ?? 'crear';
$taller = $taller ?? null;
$errores = $errores ?? [];
$esEdicion = $modo === 'editar';
// Define de forma dinámica la ruta de acción del formulario según el modo y la existencia del ID
$accion = $esEdicion && isset($taller['id_taller']) ? "/taller/editar/{$taller['id_taller']}" : '/taller/crear';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $esEdicion ? 'Editar' : 'Nuevo' ?> taller - Hogares ISN</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<?php 
// Inclusión de la barra de navegación compartida del sistema
require __DIR__ . '/../partials/navbar.php'; 
?>

<h1><?= $esEdicion ? 'Editar taller' : 'Nuevo taller' ?></h1>

<?php 
// Renderiza el contenedor de alertas si la lista de errores no está vacía
if (!empty($errores)): 
?>
    <div class="alerta alerta-error">
        <ul>
            <?php foreach ($errores as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="<?= htmlspecialchars($accion) ?>">
    <label for="nombre">Nombre</label>
    <!-- Campo de texto para el nombre del taller con persistencia de valor y placeholder -->
    <input type="text" id="nombre" name="nombre"
           value="<?= htmlspecialchars($taller['nombre'] ?? '') ?>" placeholder="Nombre del taller" required>

    <label for="direccion">Dirección</label>
    <!-- Campo de texto para la dirección física del taller -->
    <input type="text" id="direccion" name="direccion"
           value="<?= htmlspecialchars($taller['direccion'] ?? '') ?>" placeholder="Dirección del taller" required>

    <label for="telefono">Teléfono</label>
    <!-- Campo opcional para el número telefónico de contacto del taller -->
    <input type="text" id="telefono" name="telefono"
           value="<?= htmlspecialchars($taller['telefono'] ?? '') ?>" placeholder="Teléfono del taller">

    <!-- Botón de envío que adapta su etiqueta dinámicamente según el modo activo -->
    <button type="submit" class="boton boton-primario">
        <?= $esEdicion ? 'Guardar cambios' : 'Crear taller' ?>
    </button>
    <a href="/taller/index" class="boton boton-secundario">Cancelar</a>
</form>

<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>