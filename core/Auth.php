<?php
declare(strict_types=1);

/**
 * Helper estatico de autenticacion. No requiere instanciarse.
 * id_rol de referencia (segun el seed de la tabla roles):
 *   1 = Administrador
 *   2 = Encargado de Taller
 */
class Auth
{
    public static function estaLogueado(): bool
    {
        return isset($_SESSION['id_usuario']);
    }

    // Corta la ejecucion y redirige al login si no hay sesion activa
    public static function requerirLogin(): void
    {
        if (!self::estaLogueado()) {
            header('Location: /usuario/login');
            exit;
        }
    }

    // Ademas de exigir login, exige que el rol este en la lista permitida
    public static function requerirRol(array $idsRolPermitidos): void
    {
        self::requerirLogin();
        if (!in_array((int)$_SESSION['id_rol'], $idsRolPermitidos, true)) {
            http_response_code(403);
            die('No tienes permiso para acceder a esta sección.');
        }
    }

    public static function esAdministrador(): bool
    {
        return self::estaLogueado() && (int)$_SESSION['id_rol'] === 1;
    }

    // Un administrador "pertenece" a cualquier taller. Un encargado, solo al suyo.
    public static function perteneceATaller(int $idTaller): bool
    {
        if (self::esAdministrador()) {
            return true;
        }
        return (int)($_SESSION['id_taller'] ?? 0) === $idTaller;
    }

    public static function usuarioActual(): ?array
    {
        if (!self::estaLogueado()) {
            return null;
        }
        return [
            'id_usuario' => $_SESSION['id_usuario'],
            'nombre_completo' => $_SESSION['nombre_completo'] ?? '',
            'id_rol' => $_SESSION['id_rol'] ?? null,
            'id_taller' => $_SESSION['id_taller'] ?? null,
        ];
    }
}