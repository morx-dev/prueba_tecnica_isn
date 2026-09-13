<?php
/**
 * Vista para la asignación de herramientas a mecánicos activos de un taller.
 * 
 * Variables disponibles (pasadas por HerramientaController::asignar()):
 * @var array $herramienta Datos de la herramienta (validada como 'disponible')
 * @var array $mecanicos   Mecánicos activos del mismo taller que la herramienta
 * @var array $errores     Lista de mensajes de error a mostrar si los hay
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignar herramienta - Hogares ISN</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<?php require __DIR__ . '/../partials/navbar.php'; ?>

<h1>Asignar herramienta</h1>

<p>
    <strong><?= htmlspecialchars($herramienta['nombre']) ?></strong>
    (<?= htmlspecialchars($herramienta['medida'] ?? 'sin medida') ?>)
    &mdash; Taller: <?= htmlspecialchars($herramienta['nombre_taller']) ?>
</p>

<?php if (!empty($errores)): ?>
    <div class="alerta alerta-error">
        <ul>
            <?php foreach ($errores as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (empty($mecanicos)): ?>
    <div class="alerta alerta-error">
        No hay mecánicos activos en este taller para asignar esta herramienta.
        Primero registra o activa un mecánico en <a href="/mecanico/index">Mecánicos</a>.
    </div>
<?php else: ?>
    <form method="POST" action="/herramienta/asignar/<?= (int)$herramienta['id_herramienta'] ?>">
        <label for="id_mecanico">Mecánico</label>
        <select id="id_mecanico" name="id_mecanico" required>
            <option value="">-- Selecciona un mecánico --</option>
            <?php foreach ($mecanicos as $mecanico): ?>
                <option value="<?= (int)$mecanico['id_mecanico'] ?>">
                    <?= htmlspecialchars($mecanico['nombre_completo']) ?> (<?= htmlspecialchars($mecanico['codigo_empleado']) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="boton boton-primario">Asignar</button>
        <a href="/herramienta/index" class="boton boton-secundario">Cancelar</a>
    </form>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>