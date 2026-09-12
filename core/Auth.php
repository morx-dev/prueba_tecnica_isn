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
    /**
     * Verifica si existe una sesión activa de usuario.
     * 
     * @return bool True si el usuario ha iniciado sesión, false en caso contrario.
     */
    public static function estaLogueado(): bool
    {
        return isset($_SESSION['id_usuario']);
    }

    /**
     * Corta la ejecución y redirige al login si no hay sesión activa.
     */
    public static function requerirLogin(): void
    {
        if (!self::estaLogueado()) {
            header('Location: /usuario/login');
            exit;
        }
    }

    /**
     * Además de exigir login, exige que el rol del usuario actual esté en la lista permitida.
     * 
     * @param array $idsRolPermitidos Lista de identificadores de roles con acceso autorizado.
     */
    public static function requerirRol(array $idsRolPermitidos): void
    {
        self::requerirLogin();
        if (!in_array((int)$_SESSION['id_rol'], $idsRolPermitidos, true)) {
            http_response_code(403);
            die('No tienes permiso para acceder a esta sección.');
        }
    }

    /**
     * Obtiene los datos básicos del usuario actualmente autenticado desde la sesión.
     * 
     * @return array|null Arreglo asociativo con la información del usuario o null si no hay sesión.
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