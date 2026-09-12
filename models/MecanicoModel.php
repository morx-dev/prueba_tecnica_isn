<?php
declare(strict_types=1);

class MecanicoModel
{
    private PDO $db;

    public function __construct(PDO $conexion)
    {
        $this->db = $conexion;
    }

    public function obtenerTodos(): array
    {
        $stmt = $this->db->query(
            "SELECT m.*, t.nombre AS nombre_taller
             FROM mecanicos m
             INNER JOIN talleres t ON t.id_taller = m.id_taller
             ORDER BY m.id_mecanico DESC"
        );
        return $stmt->fetchAll();
    }

    // Usado cuando quien consulta es un Encargado de Taller: solo ve los suyos
    public function obtenerPorTaller(int $idTaller): array
    {
        $stmt = $this->db->prepare(
            "SELECT m.*, t.nombre AS nombre_taller
             FROM mecanicos m
             INNER JOIN talleres t ON t.id_taller = m.id_taller
             WHERE m.id_taller = :id_taller
             ORDER BY m.id_mecanico DESC"
        );
        $stmt->execute([':id_taller' => $idTaller]);
        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT m.*, t.nombre AS nombre_taller
             FROM mecanicos m
             INNER JOIN talleres t ON t.id_taller = m.id_taller
             WHERE m.id_mecanico = :id"
        );
        $stmt->execute([':id' => $id]);
        $resultado = $stmt->fetch();
        return $resultado !== false ? $resultado : null;
    }

    public function crear(
        string $nombreCompleto,
        string $codigoEmpleado,
        int $idTaller,
        ?string $telefono,
        ?string $fechaIngreso
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO mecanicos (nombre_completo, codigo_empleado, id_taller, telefono, estado, fecha_ingreso)
             VALUES (:nombre_completo, :codigo_empleado, :id_taller, :telefono, 1, :fecha_ingreso)"
        );
        return $stmt->execute([
            ':nombre_completo' => $nombreCompleto,
            ':codigo_empleado' => $codigoEmpleado,
            ':id_taller' => $idTaller,
            ':telefono' => $telefono,
            ':fecha_ingreso' => $fechaIngreso
        ]);
    }

    public function actualizar(
        int $id,
        string $nombreCompleto,
        string $codigoEmpleado,
        int $idTaller,
        ?string $telefono,
        ?string $fechaIngreso
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE mecanicos
             SET nombre_completo = :nombre_completo,
                 codigo_empleado = :codigo_empleado,
                 id_taller = :id_taller,
                 telefono = :telefono,
                 fecha_ingreso = :fecha_ingreso
             WHERE id_mecanico = :id"
        );
        return $stmt->execute([
            ':id' => $id,
            ':nombre_completo' => $nombreCompleto,
            ':codigo_empleado' => $codigoEmpleado,
            ':id_taller' => $idTaller,
            ':telefono' => $telefono,
            ':fecha_ingreso' => $fechaIngreso
        ]);
    }

    public function cambiarEstado(int $id, int $estado): bool
    {
        $stmt = $this->db->prepare("UPDATE mecanicos SET estado = :estado WHERE id_mecanico = :id");
        return $stmt->execute([':id' => $id, ':estado' => $estado]);
    }

    public function existeCodigoEmpleado(string $codigo, ?int $idExcluir = null): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM mecanicos WHERE codigo_empleado = :codigo";
        $parametros = [':codigo' => $codigo];

        if ($idExcluir !== null) {
            $sql .= " AND id_mecanico != :id_excluir";
            $parametros[':id_excluir'] = $idExcluir;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);
        $fila = $stmt->fetch();
        return $fila !== false && (int)$fila['total'] > 0;
    }

    public function tieneAsignacionesActivas(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total FROM asignaciones WHERE id_mecanico = :id AND estado = 'activa'"
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila !== false && (int)$fila['total'] > 0;
    }
}