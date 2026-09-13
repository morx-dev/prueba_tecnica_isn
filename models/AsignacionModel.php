<?php
declare(strict_types=1);

/**
 * Modelo para la gestión de asignaciones de herramientas a mecánicos.
 * Encapsula las operaciones de base de datos relacionadas con el ciclo de vida
 * de las asignaciones (crear, consultar historial, cerrar asignaciones mediante transacciones).
 */
class AsignacionModel
{
    private PDO $db;

    /**
     * Constructor del modelo.
     * 
     * @param PDO $conexion Instancia de la conexión a la base de datos.
     */
    public function __construct(PDO $conexion)
    {
        $this->db = $conexion;
    }

    /**
     * Obtiene la asignación activa actual para una herramienta específica.
     * 
     * @param int $idHerramienta Identificador de la herramienta.
     * @return array|null Arreglo con los datos de la asignación o null si no se encuentra activa.
     */
    public function obtenerActivaPorHerramienta(int $idHerramienta): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM asignaciones WHERE id_herramienta = :id AND estado = 'activa'"
        );
        $stmt->execute([':id' => $idHerramienta]);
        $resultado = $stmt->fetch();
        return $resultado !== false ? $resultado : null;
    }

    /**
     * Obtiene el historial completo de asignaciones (activas y finalizadas) de una herramienta,
     * ordenadas de la más reciente a la más antigua, incluyendo el nombre del mecánico.
     */
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
     * 
     * @param int $idHerramienta       Identificador de la herramienta a asignar.
     * @param int $idMecanico          Identificador del mecánico que recibe la herramienta.
     * @param int $idUsuarioRegistro   Identificador del usuario que registra la operación.
     * @return bool True si la transacción es exitosa, false en caso contrario.
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
     * 
     * @param int $idAsignacion  Identificador de la asignación a finalizar.
     * @param int $idHerramienta Identificador de la herramienta devuelta.
     * @return bool True si la transacción es exitosa, false en caso contrario.
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