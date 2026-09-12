<?php
/**
 * Vista de formulario para la creación y edición de mecánicos.
 * 
 * Variables disponibles (pasadas por MecanicoController::crear() / editar()):
 * @var string     $modo     Modo de operación actual ('crear' | 'editar').
 * @var array|null $mecanico Datos del mecánico (null si es creación limpia sin errores previos).
 * @var array      $errores  Lista de mensajes de validación generados en el controlador.
 * @var array      $talleres Filas de la tabla talleres, para poblar el selector dinámicamente.
 */
$esEdicion = $modo === 'editar';
// Define de forma dinámica la ruta de acción del formulario según el modo activo
$accion = $esEdicion ? "/mecanico/editar/{$mecanico['id_mecanico']}" : '/mecanico/crear';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $esEdicion ? 'Editar' : 'Nuevo' ?> mecánico - Hogares ISN</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<?php 
// Inclusión de la barra de navegación compartida del sistema
require __DIR__ . '/../partials/navbar.php'; 
?>

<h1><?= $esEdicion ? 'Editar mecánico' : 'Nuevo mecánico' ?></h1>

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
    <label for="nombre_completo">Nombre completo</label>
    <!-- Campo de texto para el nombre del mecánico con persistencia de valor -->
    <input type="text" id="nombre_completo" name="nombre_completo"
           value="<?= htmlspecialchars($mecanico['nombre_completo'] ?? '') ?>" required>

    <label for="codigo_empleado">Código de empleado</label>
    <!-- Campo de texto para el código único de identificación interna -->
    <input type="text" id="codigo_empleado" name="codigo_empleado"
           value="<?= htmlspecialchars($mecanico['codigo_empleado'] ?? '') ?>" required>

    <label for="id_taller">Taller</label>
    <!-- Selector desplegable de talleres, con selección automática si ya está asignado -->
    <select id="id_taller" name="id_taller" required>
        <option value="">-- Selecciona un taller --</option>
        <?php foreach ($talleres as $taller): ?>
            <option value="<?= (int)$taller['id_taller'] ?>"
                <?= (isset($mecanico['id_taller']) && (int)$mecanico['id_taller'] === (int)$taller['id_taller']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($taller['nombre']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="telefono">Teléfono</label>
    <!-- Campo opcional para el número telefónico de contacto -->
    <input type="text" id="telefono" name="telefono"
           value="<?= htmlspecialchars($mecanico['telefono'] ?? '') ?>">

    <label for="fecha_ingreso">Fecha de ingreso</label>
    <!-- Selector de fecha para registrar el alta laboral -->
    <input type="date" id="fecha_ingreso" name="fecha_ingreso"
           value="<?= htmlspecialchars($mecanico['fecha_ingreso'] ?? '') ?>">

    <!-- Botón de envío que adapta su etiqueta según el modo (Crear vs. Editar) -->
    <button type="submit" class="boton boton-primario">
        <?= $esEdicion ? 'Guardar cambios' : 'Crear mecánico' ?>
    </button>
    <a href="/mecanico/index" class="boton boton-secundario">Cancelar</a>
</form>

</body>
</html>