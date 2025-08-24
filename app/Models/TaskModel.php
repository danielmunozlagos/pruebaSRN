<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Modelo TaskModel
 *
 * Administra la tabla `tasks` para operaciones CRUD.
 */
class TaskModel extends Model
{
    /**
     * @var string Nombre de la tabla
     */
    protected $table = 'tasks';

    /**
     * @var string Clave primaria
     */
    protected $primaryKey = 'id';

    /**
     * @var array Campos permitidos para inserción/actualización masiva
     */
    protected $allowedFields = ['title', 'completed'];

    /**
     * @var string Tipo de dato que retorna el modelo (array, object, etc.)
     */
    protected $returnType = 'array';

    /**
     * @var bool Habilita la gestión automática de timestamps
     */
    protected $useTimestamps = true;

    /**
     * @var string Nombre del campo de creación automática
     */
    protected $createdField = 'created_at';
    
    /**
     * @var string Nombre del campo de actualización automática (opcional)
     */
    protected $updatedField = 'updated_at'; // Se usa por defecto en CI, es buena práctica mantenerlo

    /**
     * Reglas de validación para los datos del modelo.
     * * 'title' debe tener entre 3 y 255 caracteres.
     * 'completed' debe ser booleano y es opcional para la validación.
     */
    protected $validationRules = [
        'title'     => 'required|min_length[3]|max_length[255]',
        'completed' => 'permit_empty|in_list[0,1]',

    ];
}