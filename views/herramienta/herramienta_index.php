<?php
/**
 * Variables disponibles aqui (pasadas por HerramientaController::index()):
 * array $herramientas
 * array|null $mensaje
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Herramientas - Hogares ISN</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<?php require __DIR__ . '/../partials/navbar.php'; ?>

<h1>Herramientas</h1>

<?php if ($mensaje !== null): ?>
    <div class="alerta alerta-<?= htmlspecialchars($mensaje['tipo']) ?>">
        <?= htmlspecialchars($mensaje['texto']) ?>
    </div>
<?php endif; ?>

<a href="/herramienta/crear" class="boton boton-primario">+ Nueva herramienta</a>

<input type="text" class="buscador" data-tabla="tabla-herramientas" placeholder="Buscar herramienta...">

<table class="tabla" id="tabla-herramientas">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Medida</th>
            <th>Precio</th>
            <th>Taller</th>
            <th>Estado</th>
            <th>Asignada a</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($herramientas)): ?>
            <tr><td colspan="8">No hay herramientas registradas todavía.</td></tr>
        <?php else: ?>
            <?php foreach ($herramientas as $herramienta): ?>
                <tr>
                    <td><?= (int)$herramienta['id_herramienta'] ?></td>
                    <td><?= htmlspecialchars($herramienta['nombre']) ?></td>
                    <td><?= htmlspecialchars($herramienta['medida'] ?? '-') ?></td>
                    <td>Q<?= number_format((float)$herramienta['precio_compra'], 2) ?></td>
                    <td><?= htmlspecialchars($herramienta['nombre_taller']) ?></td>
                    <td>
                        <span class="etiqueta etiqueta-estado-<?= htmlspecialchars($herramienta['estado']) ?>">
                            <?= htmlspecialchars(ucfirst($herramienta['estado'])) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($herramienta['mecanico_actual'] ?? '-') ?></td>
                    <td>
                        <?php if (in_array($herramienta['estado'], ['disponible', 'asignada'], true)): ?>
                            <a href="/herramienta/editar/<?= (int)$herramienta['id_herramienta'] ?>">Editar</a>
                        <?php endif; ?>

                        <?php if ($herramienta['estado'] === 'disponible'): ?>
                            <a href="/herramienta/asignar/<?= (int)$herramienta['id_herramienta'] ?>">Asignar</a>
                        <?php elseif ($herramienta['estado'] === 'asignada'): ?>
                            <a href="/herramienta/devolver/<?= (int)$herramienta['id_herramienta'] ?>"
                               data-confirm="¿Marcar esta herramienta como devuelta?">Devolver</a>
                        <?php endif; ?>

                        <?php if (in_array($herramienta['estado'], ['disponible', 'asignada'], true)): ?>
                            <a href="/obsolescencia/marcar/<?= (int)$herramienta['id_herramienta'] ?>"
                               data-confirm="¿Marcar esta herramienta como obsoleta?">Marcar obsoleta</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>