<?php
declare(strict_types=1);

/**
 * Modelo para la gestión de usuarios, interactuando con la tabla de usuarios, roles y talleres en la base de datos.
 */
class UsuarioModel
{
    private PDO $db;

    /**
     * Inicializa el modelo de usuarios con una conexión PDO activa.
     * 
     * @param PDO $conexion Instancia de conexión a la base de datos.
     */
    public function __construct(PDO $conexion)
    {
        $this->db = $conexion;
    }

    /**
     * Busca un usuario activo por su nombre de usuario (utilizado en el proceso de autenticación).
     * 
     * @param string $usuario Nombre de usuario a buscar.
     * @return array|null Arreglo asociativo con los datos del usuario o null si no existe o está inactivo.
     */
    public function obtenerPorUsuario(string $usuario): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE usuario = :usuario AND estado = 1");
        $stmt->execute([':usuario' => $usuario]);
        $resultado = $stmt->fetch();
        return $resultado !== false ? $resultado : null;
    }

    /**
     * Obtiene los detalles completos de un usuario específico, incluyendo información de su rol y taller asociado.
     * 
     * @param int $id Identificador único del usuario.
     * @return array|null Arreglo asociativo con los datos del usuario y relaciones, o null si no se encuentra.
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, r.nombre_rol, t.nombre AS nombre_taller
             FROM usuarios u
             INNER JOIN roles r ON r.id_rol = u.id_rol
             LEFT JOIN talleres t ON t.id_taller = u.id_taller
             WHERE u.id_usuario = :id"
        );
        $stmt->execute([':id' => $id]);
        $resultado = $stmt->fetch();
        return $resultado !== false ? $resultado : null;
    }

    /**
     * Obtiene la lista completa de todos los usuarios registrados ordenados por ID de forma descendente.
     * 
     * @return array Arreglo con todos los registros de usuarios, roles y talleres.
     */
    public function obtenerTodos(): array
    {
        $stmt = $this->db->query(
            "SELECT u.*, r.nombre_rol, t.nombre AS nombre_taller
             FROM usuarios u
             INNER JOIN roles r ON r.id_rol = u.id_rol
             LEFT JOIN talleres t ON t.id_taller = u.id_taller
             ORDER BY u.id_usuario DESC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Registra un nuevo usuario en la base de datos. 
     * El hash de la contraseña se genera aqui adentro, nunca en el controlador.
     * 
     * @param string $nombreCompleto Nombre completo del usuario.
     * @param string $usuario Identificador único para el login.
     * @param string $passwordPlano Contraseña en texto plano a cifrar.
     * @param int $idRol Identificador del rol asignado.
     * @param int|null $idTaller Identificador del taller asignado (opcional).
     * @return bool True si la inserción fue exitosa, false en caso contrario.
     */
    public function crear(string $nombreCompleto, string $usuario, string $passwordPlano, int $idRol, ?int $idTaller): bool
    {
        $hash = password_hash($passwordPlano, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare(
            "INSERT INTO usuarios (nombre_completo, usuario, password_hash, id_rol, id_taller, estado, fecha_creacion)
             VALUES (:nombre_completo, :usuario, :password_hash, :id_rol, :id_taller, 1, NOW())"
        );
        return $stmt->execute([
            ':nombre_completo' => $nombreCompleto,
            ':usuario' => $usuario,
            ':password_hash' => $hash,
            ':id_rol' => $idRol,
            ':id_taller' => $idTaller
        ]);
    }

    /**
     * Verifica si ya existe un nombre de usuario registrado en el sistema.
     * 
     * @param string $usuario Nombre de usuario a comprobar.
     * @return bool True si ya existe, false si está disponible.
     */
    public function existeUsuario(string $usuario): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE usuario = :usuario");
        $stmt->execute([':usuario' => $usuario]);
        $fila = $stmt->fetch();
        return $fila !== false && (int)$fila['total'] > 0;
    }

    /**
     * Cambia el estado lógico (activo/inactivo) de un usuario específico.
     * 
     * @param int $id Identificador del usuario.
     * @param int $estado Nuevo estado (1 para activo, 0 para inactivo).
     * @return bool True si la actualización fue exitosa, false en caso contrario.
     */
    public function cambiarEstado(int $id, int $estado): bool
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET estado = :estado WHERE id_usuario = :id");
        return $stmt->execute([':id' => $id, ':estado' => $estado]);
    }

    /**
     * Obtiene el listado de todos los roles disponibles en el sistema.
     * 
     * @return array Arreglo con los roles existentes.
     */
    public function obtenerRoles(): array
    {
        $stmt = $this->db->query("SELECT * FROM roles ORDER BY id_rol ASC");
        return $stmt->fetchAll();
    }
}