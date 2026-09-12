<?php
/**
 * Vista de Formulario de Talleres (Crear / Editar)
 * Renderiza un formulario HTML dinámico reutilizable tanto para el registro 
 * como para la actualización de información de talleres, gestionando la 
 * persistencia de datos previos ante errores de validación.
 */

$modo = $modo ?? 'crear';
$taller = $taller ?? null;
$errores = $errores ?? [];
$esEdicion = $modo === 'editar';
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

<h1><?= $esEdicion ? 'Editar taller' : 'Nuevo taller' ?></h1>

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
    <label for="nombre">Nombre</label>
    <input type="text" id="nombre" name="nombre"
           value="<?= htmlspecialchars($taller['nombre'] ?? '') ?>" placeholder="Nombre del taller" required>

    <label for="direccion">Dirección</label>
    <input type="text" id="direccion" name="direccion"
           value="<?= htmlspecialchars($taller['direccion'] ?? '') ?>" placeholder="Dirección del taller" required>

    <label for="telefono">Teléfono</label>
    <input type="text" id="telefono" name="telefono"
           value="<?= htmlspecialchars($taller['telefono'] ?? '') ?>" placeholder="Teléfono del taller">

    <button type="submit" class="boton boton-primario">
        <?= $esEdicion ? 'Guardar cambios' : 'Crear taller' ?>
    </button>
    <a href="/taller/index" class="boton boton-secundario">Cancelar</a>
</form>

</body>
</html>