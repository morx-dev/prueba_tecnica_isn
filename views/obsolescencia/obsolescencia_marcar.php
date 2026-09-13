<?php
/**
 * Variables disponibles aqui (pasadas por ObsolescenciaController::marcar()):
 * array $herramienta
 * array $errores
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Marcar obsoleta - Hogares ISN</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<?php require __DIR__ . '/../partials/navbar.php'; ?>

<h1>Marcar herramienta como obsoleta</h1>

<p>
    <strong><?= htmlspecialchars($herramienta['nombre']) ?></strong>
    (<?= htmlspecialchars($herramienta['medida'] ?? 'sin medida') ?>)
    &mdash; Taller: <?= htmlspecialchars($herramienta['nombre_taller']) ?>
    <?php if ($herramienta['estado'] === 'asignada'): ?>
        <br><em>Actualmente asignada a: <?= htmlspecialchars($herramienta['mecanico_actual']) ?> — esa asignación se cerrará automáticamente.</em>
    <?php endif; ?>
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

<form method="POST" action="/obsolescencia/marcar/<?= (int)$herramienta['id_herramienta'] ?>">
    <label for="motivo">Motivo del reemplazo / obsolescencia</label>
    <textarea id="motivo" name="motivo" rows="3" required><?= htmlspecialchars($_POST['motivo'] ?? '') ?></textarea>

    <button type="submit" class="boton boton-primario">Marcar como obsoleta</button>
    <a href="/herramienta/index" class="boton boton-secundario">Cancelar</a>
</form>

<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>