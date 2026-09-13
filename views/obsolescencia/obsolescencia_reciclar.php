<?php
/**
 * Vista para registrar el reciclaje de una herramienta obsoleta.
 * Muestra los detalles de la herramienta en bodega (nombre, taller y motivo),
 * el manejo de errores de validación y el formulario para ingresar los datos de reciclaje
 * (peso en kg, valor estimado en Quetzales y observaciones).
 * 
 * Variables disponibles aqui (pasadas por ObsolescenciaController::reciclar()):
 * array $obsoleto Datos del registro obsoleto a procesar.
 * array $errores  Listado de errores de validación del formulario (si existen).
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar reciclaje - Hogares ISN</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<?php require __DIR__ . '/../partials/navbar.php'; ?>

<h1>Registrar reciclaje</h1>

<p>
    <strong><?= htmlspecialchars($obsoleto['nombre_herramienta']) ?></strong>
    &mdash; Taller: <?= htmlspecialchars($obsoleto['nombre_taller']) ?>
    <br>Motivo de obsolescencia: <?= htmlspecialchars($obsoleto['motivo']) ?>
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

<form method="POST" action="/obsolescencia/reciclar/<?= (int)$obsoleto['id_obsoleto'] ?>">
    <label for="peso_kg">Peso del metal (kg)</label>
    <input type="number" step="0.01" min="0" id="peso_kg" name="peso_kg">

    <label for="valor_estimado">Valor estimado (Q)</label>
    <input type="number" step="0.01" min="0" id="valor_estimado" name="valor_estimado">

    <label for="observaciones">Observaciones</label>
    <textarea id="observaciones" name="observaciones" rows="3"></textarea>

    <button type="submit" class="boton boton-primario">Registrar reciclaje</button>
    <a href="/obsolescencia/index" class="boton boton-secundario">Cancelar</a>
</form>

<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>