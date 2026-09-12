<?php
declare(strict_types=1);

/**
 * Modelo para la gestión y control del catálogo de mecánicos en los talleres.
 */
class MecanicoModel
{
    private PDO $db;

    /**
     * Inicializa el modelo recibiendo la conexión activa de PDO.
     * 
     * @param PDO $conexion Instancia de conexión a la base de datos.
     */
    public function __construct(PDO $conexion)
    {
        $this->db = $conexion;
    }

    /**
     * Obtiene todos los mecánicos registrados con información de su taller correspondiente.
     * 
     * @return array Arreglo asociativo con todos los mecánicos.
     */
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

    /**
     * Obtiene los mecánicos pertenecientes a un taller específico.
     * 
     * @param int $idTaller Identificador del taller.
     * @return array Arreglo asociativo con los mecánicos del taller.
     */
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

    // Usado por el formulario de asignacion de herramientas: solo mecanicos activos de ese taller
    /**
     * Obtiene únicamente los mecánicos activos de un taller específico.
     * 
     * @param int $idTaller Identificador del taller.
     * @return array Arreglo asociativo con los mecánicos activos ordenados por nombre.
     */
    public function obtenerActivosPorTaller(int $idTaller): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM mecanicos WHERE id_taller = :id_taller AND estado = 1 ORDER BY nombre_completo ASC"
        );
        $stmt->execute([':id_taller' => $idTaller]);
        return $stmt->fetchAll();
    }

    /**
     * Busca un mecánico específico por su identificador único.
     * 
     * @param int $id Identificador del mecánico.
     * @return array|null Arreglo con los datos del mecánico o null si no existe.
     */
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

    /**
     * Registra un nuevo mecánico en la base de datos con estado activo inicial.
     * 
     * @param string      $nombreCompleto Nombre completo del empleado.
     * @param string      $codigoEmpleado Código único de empleado.
     * @param int         $idTaller       Identificador del taller al que pertenece.
     * @param string|null $telefono       Número de teléfono de contacto.
     * @param string|null $fechaIngreso   Fecha de ingreso a la institución.
     * @return bool True si la inserción fue exitosa, false en caso contrario.
     */
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

    /**
     * Actualiza la información del perfil y datos de un mecánico existente.
     * 
     * @param int         $id             Identificador único del mecánico.
     * @param string      $nombreCompleto Nombre completo actualizado.
     * @param string      $codigoEmpleado Código de empleado actualizado.
     * @param int         $idTaller       Identificador del taller asignado.
     * @param string|null $telefono       Teléfono actualizado.
     * @param string|null $fechaIngreso   Fecha de ingreso actualizada.
     * @return bool True si la actualización fue exitosa, false en caso contrario.
     */
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

    /**
     * Modifica el estado activo o inactivo de un mecánico.
     * 
     * @param int $id     Identificador del mecánico.
     * @param int $estado Nuevo estado (1 para activo, 0 para inactivo).
     * @return bool True si la operación se ejecutó correctamente, false en caso contrario.
     */
    public function cambiarEstado(int $id, int $estado): bool
    {
        $stmt = $this->db->prepare("UPDATE mecanicos SET estado = :estado WHERE id_mecanico = :id");
        return $stmt->execute([':id' => $id, ':estado' => $estado]);
    }

    /**
     * Verifica si un código de empleado ya se encuentra registrado en el sistema,
     * permitiendo excluir opcionalmente a un ID durante procesos de edición.
     * 
     * @param string   $codigo      Código de empleado a verificar.
     * @param int|null $idExcluir   Identificador del mecánico a ignorar en la consulta (opcional).
     * @return bool True si el código ya existe, false si está disponible.
     */
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

    /**
     * Comprueba si un mecánico cuenta con herramientas asignadas con estado activo.
     * 
     * @param int $id Identificador del mecánico.
     * @return bool True si tiene asignaciones activas, false en caso contrario.
     */
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