<?php
declare(strict_types=1);

/**
 * Helper estático de autenticación y control de acceso. No requiere instanciarse.
 * id_rol de referencia (segun el seed de la tabla roles):
 *   1 = Administrador
 *   2 = Encargado de Taller
 */
class Auth
{
    /**
     * Verifica si existe una sesión de usuario activa en el sistema.
     * 
     * @return bool True si el usuario está logueado, false en caso contrario.
     */
    public static function estaLogueado(): bool
    {
        return isset($_SESSION['id_usuario']);
    }

    /**
     * Interrumpe la ejecución y redirige al formulario de inicio de sesión si no hay una sesión activa.
     */
    // Corta la ejecucion y redirige al login si no hay sesion activa
    public static function requerirLogin(): void
    {
        if (!self::estaLogueado()) {
            header('Location: /usuario/login');
            exit;
        }
    }

    /**
     * Exige que el usuario haya iniciado sesión y que su rol esté contenido dentro de la lista permitida.
     * Deniega el acceso con código HTTP 403 si no cumple el requisito.
     * 
     * @param array $idsRolPermitidos Arreglo con los IDs de roles autorizados.
     */
    // Ademas de exigir login, exige que el rol este en la lista permitida
    public static function requerirRol(array $idsRolPermitidos): void
    {
        self::requerirLogin();
        if (!in_array((int)$_SESSION['id_rol'], $idsRolPermitidos, true)) {
            http_response_code(403);
            die('No tienes permiso para acceder a esta sección.');
        }
    }

    /**
     * Comprueba si el usuario autenticado actual posee el rol de Administrador.
     * 
     * @return bool True si es administrador, false en caso contrario.
     */
    public static function esAdministrador(): bool
    {
        return self::estaLogueado() && (int)$_SESSION['id_rol'] === 1;
    }

    /**
     * Valida si el usuario actual pertenece o tiene autoridad sobre un taller determinado.
     * Un administrador pertenece a cualquier taller; un encargado solo al suyo.
     * 
     * @param int $idTaller Identificador del taller a validar.
     * @return bool True si tiene acceso, false si no.
     */
    // Un administrador "pertenece" a cualquier taller. Un encargado, solo al suyo.
    public static function perteneceATaller(int $idTaller): bool
    {
        if (self::esAdministrador()) {
            return true;
        }
        return (int)($_SESSION['id_taller'] ?? 0) === $idTaller;
    }

    /**
     * Recupera un arreglo con los datos básicos del usuario autenticado actual.
     * 
     * @return array|null Arreglo asociativo con los datos del usuario o null si no hay sesión.
     */
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