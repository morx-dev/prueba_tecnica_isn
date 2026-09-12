<?php
declare(strict_types=1);

/**
 * Clase TallerModel
 * Capa de modelo orientada a la gestión de datos de talleres.
 * Encapsula todas las operaciones de consultas, inserciones, actualizaciones 
 * y validaciones con la base de datos utilizando PDO de forma segura.
 */
class TallerModel
{
    /** @var PDO Instancia de la conexión activa a la base de datos */
    private PDO $db;

    /**
     * Constructor del modelo.
     * Recibe la conexión PDO inyectada desde el controlador.
     * 
     * @param PDO $conexion Objeto de conexión activo.
     */
    public function __construct(PDO $conexion)
    {
        $this->db = $conexion;
    }

    /**
     * Obtener todos los talleres.
     * Consulta y retorna el listado completo de registros ordenados de forma descendente por ID.
     * 
     * @return array Arreglo asociativo con todos los talleres.
     */
    public function obtenerTodos(): array
    {
        $stmt = $this->db->query("SELECT * FROM talleres ORDER BY id_taller DESC");
        return $stmt->fetchAll();
    }

    /**
     * Obtener un taller por su ID.
     * Realiza una consulta segura filtrada por el identificador único del taller.
     * 
     * @param int $id Identificador del taller.
     * @return array|null Arreglo asociativo con los datos del taller o nulo si no existe.
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM talleres WHERE id_taller = :id");
        $stmt->execute([':id' => $id]);
        $resultado = $stmt->fetch();
        return $resultado !== false ? $resultado : null;
    }

    /**
     * Crear un nuevo taller.
     * Inserta un registro en la tabla con estado inicial activo (1) y fecha actual.
     * 
     * @param string $nombre Nombre del taller.
     * @param string $direccion Dirección del taller.
     * @param string|null $telefono Teléfono de contacto opcional.
     * @return bool True si la inserción fue exitosa, false en caso contrario.
     */
    public function crear(string $nombre, string $direccion, ?string $telefono): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO talleres (nombre, direccion, telefono, estado, fecha_creacion)
            VALUES (:nombre, :direccion, :telefono, 1, NOW())"
        );
        return $stmt->execute([
            ':nombre' => $nombre,
            ':direccion' => $direccion,
            ':telefono' => $telefono
        ]);
    }

    /**
     * Actualizar los datos de un taller existente.
     * Modifica los campos principales asociados a un ID específico.
     * 
     * @param int $id Identificador del taller a actualizar.
     * @param string $nombre Nuevo nombre.
     * @param string $direccion Nueva dirección.
     * @param string|null $telefono Nuevo teléfono de contacto.
     * @return bool True si la actualización fue exitosa, false en caso contrario.
     */
    public function actualizar(int $id, string $nombre, string $direccion, ?string $telefono): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE talleres SET nombre = :nombre, direccion = :direccion, telefono = :telefono
            WHERE id_taller = :id"
        );
        return $stmt->execute([
            ':id' => $id,
            ':nombre' => $nombre,
            ':direccion' => $direccion,
            ':telefono' => $telefono
        ]);
    }

    /**
     * Activar o desactivar un taller (borrado logico, no fisico).
     * Modifica únicamente el campo estado del registro seleccionado.
     * 
     * @param int $id Identificador del taller.
     * @param int $estado Nuevo estado (1 para activo, 0 para inactivo).
     * @return bool True si el cambio de estado fue exitoso, false en caso contrario.
     */
    public function cambiarEstado(int $id, int $estado): bool
    {
        $stmt = $this->db->prepare("UPDATE talleres SET estado = :estado WHERE id_taller = :id");
        return $stmt->execute([
            ':id' => $id,
            ':estado' => $estado
        ]);
    }

    /**
     * Verifica si el taller tiene mecanicos o herramientas asociadas.
     * Util antes de permitir un borrado fisico real o validación de integridad referencial.
     * 
     * @param int $id Identificador del taller a comprobar.
     * @return bool True si existen dependencias asociadas, false si está libre.
     */
    public function tieneRegistrosAsociados(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT
                (SELECT COUNT(*) FROM mecanicos WHERE id_taller = :id1) +
                (SELECT COUNT(*) FROM herramientas WHERE id_taller = :id2) AS total"
        );
        $stmt->execute([':id1' => $id, ':id2' => $id]);
        $fila = $stmt->fetch();
        return $fila !== false && (int)$fila['total'] > 0;
    }
}