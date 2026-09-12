<?php
/**
 * Variable disponible aqui (pasada por UsuarioController::login()):
 * string|null $error
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión - Hogares ISN</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<h1>Iniciar sesión</h1>

<?php if ($error !== null): ?>
    <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="/usuario/login">
    <label for="usuario">Usuario</label>
    <input type="text" id="usuario" name="usuario" required autofocus>

    <label for="password">Contraseña</label>
    <input type="password" id="password" name="password" required>

    <button type="submit" class="boton boton-primario">Entrar</button>
</form>

</body>
</html>