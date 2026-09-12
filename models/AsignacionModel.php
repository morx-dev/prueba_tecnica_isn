<?php
declare(strict_types=1);

class AsignacionModel
{
    private PDO $db;

    public function __construct(PDO $conexion)
    {
        $this->db = $conexion;
    }

    public function obtenerActivaPorHerramienta(int $idHerramienta): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM asignaciones WHERE id_herramienta = :id AND estado = 'activa'"
        );
        $stmt->execute([':id' => $idHerramienta]);
        $resultado = $stmt->fetch();
        return $resultado !== false ? $resultado : null;
    }

    // Historial completo de una herramienta (activa y finalizadas), mas reciente primero
    public function obtenerHistorialPorHerramienta(int $idHerramienta): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, mec.nombre_completo AS nombre_mecanico
             FROM asignaciones a
             INNER JOIN mecanicos mec ON mec.id_mecanico = a.id_mecanico
             WHERE a.id_herramienta = :id
             ORDER BY a.fecha_asignacion DESC"
        );
        $stmt->execute([':id' => $idHerramienta]);
        return $stmt->fetchAll();
    }

    /**
     * Crea la asignacion Y marca la herramienta como 'asignada' en una sola transaccion.
     * Si cualquiera de los dos pasos falla, ninguno queda guardado.
     */
    public function asignar(int $idHerramienta, int $idMecanico, int $idUsuarioRegistro): bool
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO asignaciones (id_herramienta, id_mecanico, id_usuario_registro, fecha_asignacion, estado)
                 VALUES (:id_herramienta, :id_mecanico, :id_usuario_registro, NOW(), 'activa')"
            );
            $stmt->execute([
                ':id_herramienta' => $idHerramienta,
                ':id_mecanico' => $idMecanico,
                ':id_usuario_registro' => $idUsuarioRegistro
            ]);

            $stmtHerramienta = $this->db->prepare(
                "UPDATE herramientas SET estado = 'asignada' WHERE id_herramienta = :id"
            );
            $stmtHerramienta->execute([':id' => $idHerramienta]);

            $this->db->commit();
            return true;
        } catch (PDOException $excepcion) {
            $this->db->rollBack();
            error_log('Error al asignar herramienta: ' . $excepcion->getMessage());
            return false;
        }
    }

    /**
     * Cierra la asignacion activa Y regresa la herramienta a 'disponible', en una sola transaccion.
     */
    public function devolver(int $idAsignacion, int $idHerramienta): bool
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                "UPDATE asignaciones SET estado = 'finalizada', fecha_devolucion = NOW() WHERE id_asignacion = :id"
            );
            $stmt->execute([':id' => $idAsignacion]);

            $stmtHerramienta = $this->db->prepare(
                "UPDATE herramientas SET estado = 'disponible' WHERE id_herramienta = :id"
            );
            $stmtHerramienta->execute([':id' => $idHerramienta]);

            $this->db->commit();
            return true;
        } catch (PDOException $excepcion) {
            $this->db->rollBack();
            error_log('Error al devolver herramienta: ' . $excepcion->getMessage());
            return false;
        }
    }
}