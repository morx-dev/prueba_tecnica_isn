<?php
declare(strict_types=1);

/**
 * Modelo para la gestión de mecánicos en la base de datos.
 * Maneja las consultas de listado, filtrado por taller, búsqueda individual,
 * persistencia de datos (creación/actualización), control de estados, validaciones
 * de códigos duplicados y verificación de asignaciones activas de herramientas.
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
     * Obtiene el listado completo de todos los mecánicos registrados con su respectivo taller.
     * 
     * @return array Arreglo asociativo con los registros de mecánicos.
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
     * Obtiene únicamente los mecánicos pertenecientes a un taller específico.
     * Usado cuando quien consulta es un Encargado de Taller: solo ve los suyos.
     * 
     * @param int $idTaller Identificador único del taller.
     * @return array Arreglo asociativo con los mecánicos del taller.
     */
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

    /**
     * Busca y retorna los datos de un mecánico específico por su ID, incluyendo el nombre del taller.
     * 
     * @param int $id Identificador único del mecánico.
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
     * Inserta un nuevo registro de mecánico en la base de datos con estado activo por defecto (1).
     * 
     * @param string      $nombreCompleto Nombre y apellido del mecánico.
     * @param string      $codigoEmpleado Código de identificación interna del empleado.
     * @param int         $idTaller       ID del taller asignado.
     * @param string|null $telefono       Número de teléfono opcional.
     * @param string|null $fechaIngreso   Fecha de alta o ingreso laboral opcional.
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
     * Actualiza la información de un mecánico existente en el sistema.
     * 
     * @param int         $id             ID del mecánico a actualizar.
     * @param string      $nombreCompleto Nuevo nombre completo.
     * @param string      $codigoEmpleado Nuevo código de empleado.
     * @param int         $idTaller       Nuevo ID de taller asignado.
     * @param string|null $telefono       Teléfono actualizado.
     * @param string|null $fechaIngreso   Fecha de ingreso actualizada.
     * @return bool True si la actualización se realizó correctamente, false si no.
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
     * Modifica el estado lógico (activo/inactivo) de un mecánico.
     * 
     * @param int $id     Identificador del mecánico.
     * @param int $estado Nuevo estado (1 = Activo, 0 = Inactivo).
     * @return bool True si la operación fue exitosa, false si falló.
     */
    public function cambiarEstado(int $id, int $estado): bool
    {
        $stmt = $this->db->prepare("UPDATE mecanicos SET estado = :estado WHERE id_mecanico = :id");
        return $stmt->execute([':id' => $id, ':estado' => $estado]);
    }

    /**
     * Verifica si un código de empleado ya se encuentra registrado en el sistema.
     * Permite excluir un ID específico (útil durante procesos de edición).
     * 
     * @param string    $codigo    Código de empleado a consultar.
     * @param int|null  $idExcluir ID opcional del mecánico a ignorar en la búsqueda.
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
     * Comprueba si el mecánico posee alguna asignación activa de herramientas vinculada.
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