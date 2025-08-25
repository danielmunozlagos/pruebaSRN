<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Modelo de Tareas.
 *
 * Campos:
 *  - id (int, PK, autoincrement)
 *  - title (string, 3..255)
 *  - completed (tinyint 0|1)
 *  - created_at, updated_at (timestamps)
 */
class TaskModel extends Model
{
    protected $table         = 'tasks';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['title', 'completed'];
    protected $useTimestamps = true;

    /** @var array<string,string> Reglas de validación del modelo */
    protected $validationRules = [
        'title'     => 'required|string|min_length[3]|max_length[255]',
        'completed' => 'permit_empty|in_list[0,1]',
    ];

    /** @var array<string,array<string,string>> Mensajes de validación */
    protected $validationMessages = [
        'title' => [
            'required'   => 'El título es obligatorio.',
            'min_length' => 'Mínimo 3 caracteres.',
            'max_length' => 'Máximo 255 caracteres.',
        ],
        'completed' => [
            'in_list' => 'completed debe ser 0 o 1.',
        ],
    ];
}
