<?php
declare(strict_types=1);

class UsuarioModel
{
    private PDO $db;

    public function __construct(PDO $conexion)
    {
        $this->db = $conexion;
    }

    // Usado por el login. Solo trae usuarios activos.
    public function obtenerPorUsuario(string $usuario): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE usuario = :usuario AND estado = 1");
        $stmt->execute([':usuario' => $usuario]);
        $resultado = $stmt->fetch();
        return $resultado !== false ? $resultado : null;
    }

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

    // El hash de la contraseña se genera aqui adentro, nunca en el controlador
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

    public function existeUsuario(string $usuario): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE usuario = :usuario");
        $stmt->execute([':usuario' => $usuario]);
        $fila = $stmt->fetch();
        return $fila !== false && (int)$fila['total'] > 0;
    }

    public function cambiarEstado(int $id, int $estado): bool
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET estado = :estado WHERE id_usuario = :id");
        return $stmt->execute([':id' => $id, ':estado' => $estado]);
    }

    public function obtenerRoles(): array
    {
        $stmt = $this->db->query("SELECT * FROM roles ORDER BY id_rol ASC");
        return $stmt->fetchAll();
    }
}