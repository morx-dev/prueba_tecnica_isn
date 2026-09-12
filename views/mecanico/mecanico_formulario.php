<?php
/**
 * Variables disponibles aqui (pasadas por MecanicoController::crear() / editar()):
 * string $modo          'crear' | 'editar'
 * array|null $mecanico  datos del mecanico (null si es creacion sin errores previos)
 * array $errores        lista de mensajes de validacion
 * array $talleres       filas de la tabla talleres, para el select
 */
$esEdicion = $modo === 'editar';
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

<?php require __DIR__ . '/../partials/navbar.php'; ?>

<h1><?= $esEdicion ? 'Editar mecánico' : 'Nuevo mecánico' ?></h1>

<?php if (!empty($errores)): ?>
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
    <input type="text" id="nombre_completo" name="nombre_completo"
           value="<?= htmlspecialchars($mecanico['nombre_completo'] ?? '') ?>" required>

    <label for="codigo_empleado">Código de empleado</label>
    <input type="text" id="codigo_empleado" name="codigo_empleado"
           value="<?= htmlspecialchars($mecanico['codigo_empleado'] ?? '') ?>" required>

    <label for="id_taller">Taller</label>
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
    <input type="text" id="telefono" name="telefono"
           value="<?= htmlspecialchars($mecanico['telefono'] ?? '') ?>">

    <label for="fecha_ingreso">Fecha de ingreso</label>
    <input type="date" id="fecha_ingreso" name="fecha_ingreso"
           value="<?= htmlspecialchars($mecanico['fecha_ingreso'] ?? '') ?>">

    <button type="submit" class="boton boton-primario">
        <?= $esEdicion ? 'Guardar cambios' : 'Crear mecánico' ?>
    </button>
    <a href="/mecanico/index" class="boton boton-secundario">Cancelar</a>
</form>

</body>
</html>