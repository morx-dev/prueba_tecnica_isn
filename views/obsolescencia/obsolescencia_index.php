<?php
/**
 * Variables disponibles aqui (pasadas por ObsolescenciaController::index()):
 * array $enBodega
 * array|null $mensaje
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bodega de obsoletos - Hogares ISN</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<?php require __DIR__ . '/../partials/navbar.php'; ?>

<h1>Bodega de obsoletos</h1>
<p><a href="/obsolescencia/historial">Ver historial de herramientas ya recicladas &rarr;</a></p>

<?php if ($mensaje !== null): ?>
    <div class="alerta alerta-<?= htmlspecialchars($mensaje['tipo']) ?>">
        <?= htmlspecialchars($mensaje['texto']) ?>
    </div>
<?php endif; ?>

<input type="text" class="buscador" data-tabla="tabla-obsoletos" placeholder="Buscar...">

<table class="tabla" id="tabla-obsoletos">
    <thead>
        <tr>
            <th>ID</th>
            <th>Herramienta</th>
            <th>Taller</th>
            <th>Solicitada por</th>
            <th>Motivo</th>
            <th>Fecha</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($enBodega)): ?>
            <tr><td colspan="7">No hay herramientas obsoletas pendientes de reciclar.</td></tr>
        <?php else: ?>
            <?php foreach ($enBodega as $obsoleto): ?>
                <tr>
                    <td><?= (int)$obsoleto['id_obsoleto'] ?></td>
                    <td><?= htmlspecialchars($obsoleto['nombre_herramienta']) ?></td>
                    <td><?= htmlspecialchars($obsoleto['nombre_taller']) ?></td>
                    <td><?= htmlspecialchars($obsoleto['nombre_mecanico_solicito'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($obsoleto['motivo']) ?></td>
                    <td><?= htmlspecialchars($obsoleto['fecha_obsolescencia']) ?></td>
                    <td>
                        <a href="/obsolescencia/reciclar/<?= (int)$obsoleto['id_obsoleto'] ?>">Registrar reciclaje</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>