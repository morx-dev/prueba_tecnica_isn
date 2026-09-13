<?php
/**
 * Vista para el formulario de creación y edición de herramientas.
 * 
 * Variables disponibles (pasadas por HerramientaController::crear() / editar()):
 * @var string     $modo        'crear' | 'editar'
 * @var array|null $herramienta Datos de la herramienta (en modo edición)
 * @var array      $errores     Lista de errores de validación
 * @var array      $talleres    Listado de talleres disponibles
 */
$esEdicion = $modo === 'editar';
$accion = $esEdicion ? "/herramienta/editar/{$herramienta['id_herramienta']}" : '/herramienta/crear';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $esEdicion ? 'Editar' : 'Nueva' ?> herramienta - Hogares ISN</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<?php require __DIR__ . '/../partials/navbar.php'; ?>

<h1><?= $esEdicion ? 'Editar herramienta' : 'Nueva herramienta' ?></h1>

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
           value="<?= htmlspecialchars($herramienta['nombre'] ?? '') ?>" required>

    <label for="medida">Medida</label>
    <input type="text" id="medida" name="medida"
           value="<?= htmlspecialchars($herramienta['medida'] ?? '') ?>">

    <label for="precio_compra">Precio de compra (Q)</label>
    <input type="number" step="0.01" min="0.01" id="precio_compra" name="precio_compra"
           value="<?= htmlspecialchars((string)($herramienta['precio_compra'] ?? '')) ?>" required>

    <label for="id_taller">Taller</label>
    <select id="id_taller" name="id_taller" required>
        <option value="">-- Selecciona un taller --</option>
        <?php foreach ($talleres as $taller): ?>
            <option value="<?= (int)$taller['id_taller'] ?>"
                <?= (isset($herramienta['id_taller']) && (int)$herramienta['id_taller'] === (int)$taller['id_taller']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($taller['nombre']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <?php if (!$esEdicion): ?>
        <label for="fecha_ingreso">Fecha de ingreso</label>
        <input type="date" id="fecha_ingreso" name="fecha_ingreso"
               value="<?= htmlspecialchars($herramienta['fecha_ingreso'] ?? date('Y-m-d')) ?>">
    <?php endif; ?>

    <button type="submit" class="boton boton-primario">
        <?= $esEdicion ? 'Guardar cambios' : 'Crear herramienta' ?>
    </button>
    <a href="/herramienta/index" class="boton boton-secundario">Cancelar</a>
</form>

<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>