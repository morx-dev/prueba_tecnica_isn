<?php
/**
 * Vista del historial de herramientas recicladas.
 * Muestra el listado de elementos que han sido procesados y reciclados,
 * junto con un cálculo del valor total estimado recuperado y detalles por registro.
 * 
 * Variable disponible aqui (pasada por ObsolescenciaController::historial()):
 * array $reciclados Listado de registros de herramientas recicladas.
 */
$valorTotal = array_sum(array_column($reciclados, 'valor_estimado'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de reciclaje - Hogares ISN</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<?php require __DIR__ . '/../partials/navbar.php'; ?>

<h1>Historial de herramientas recicladas</h1>
<p><a href="/obsolescencia/index">&larr; Volver a la bodega de obsoletos</a></p>

<p><strong>Valor total estimado recuperado: Q<?= number_format((float)$valorTotal, 2) ?></strong></p>

<table class="tabla">
    <thead>
        <tr>
            <th>ID</th>
            <th>Herramienta</th>
            <th>Taller</th>
            <th>Motivo original</th>
            <th>Fecha de reciclaje</th>
            <th>Peso (kg)</th>
            <th>Valor estimado</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($reciclados)): ?>
            <tr><td colspan="7">Todavía no se ha reciclado ninguna herramienta.</td></tr>
        <?php else: ?>
            <?php foreach ($reciclados as $reciclado): ?>
                <tr>
                    <td><?= (int)$reciclado['id_reciclaje'] ?></td>
                    <td><?= htmlspecialchars($reciclado['nombre_herramienta']) ?></td>
                    <td><?= htmlspecialchars($reciclado['nombre_taller']) ?></td>
                    <td><?= htmlspecialchars($reciclado['motivo']) ?></td>
                    <td><?= htmlspecialchars($reciclado['fecha_reciclaje']) ?></td>
                    <td><?= $reciclado['peso_kg'] !== null ? htmlspecialchars($reciclado['peso_kg']) : '-' ?></td>
                    <td><?= $reciclado['valor_estimado'] !== null ? 'Q' . number_format((float)$reciclado['valor_estimado'], 2) : '-' ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>