<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use Exception;

/**
 * Class Tasks
 * Controlador REST para gestionar tareas (To-Do).
 *
 * Endpoints disponibles:
 * - GET    /tasks           → index()
 * - GET    /tasks/{id}      → show($id)
 * - POST   /tasks           → create()
 * - PUT    /tasks/{id}      → update($id)
 * - DELETE /tasks/{id}      → delete($id)
 */
class Tasks extends ResourceController
{
    /**
     * @var string Nombre del modelo utilizado
     */
    protected $modelName = 'App\Models\TaskModel';

    /**
     * @var string Formato de respuesta (JSON)
     */
    protected $format = 'json';

    /**
     * Lista todas las tareas.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function index()
    {
        try {
            $tasks = $this->model->findAll();
            return $this->respond($tasks);
        } catch (Exception $e) {
            return $this->failServerError('Error al obtener las tareas.');
        }
    }

    /**
     * Muestra una tarea específica por ID.
     *
     * @param int|null $id ID de la tarea
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function show($id = null)
    {
        try {
            $task = $this->model->find($id);
            if (!$task) {
                return $this->failNotFound('Tarea no encontrada.');
            }
            return $this->respond($task);
        } catch (Exception $e) {
            return $this->failServerError('Error al obtener la tarea.');
        }
    }

    /**
     * Crea una nueva tarea.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validación mínima del título
            if (!isset($data['title']) || empty($data['title'])) {
                return $this->failValidationErrors('El título es obligatorio.');
            }

            // Normalizar el valor de "completed" (1 o 0 como string)
            $data['completed'] = !empty($data['completed']) && $data['completed'] ? '1' : '0';

            // Insertar y obtener ID generado
            $id = $this->model->insert($data);

            if (!$id) {
                return $this->failValidationErrors($this->model->errors());
            }

            // Añadir el id al array para devolverlo
            $data['id'] = $id;

            return $this->respondCreated($data);
        } catch (Exception $e) {
            return $this->failServerError('Error al crear la tarea.');
        }
    }


    /**
     * Actualiza una tarea existente.
     *
     * @param int|null $id ID de la tarea
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function update($id = null)
    {
        try {
            $data = $this->request->getJSON(true);

            // Si el campo 'completed' no está presente, lo marcamos como 0 (falso)
            $data['completed'] = !empty($data['completed']) && $data['completed'] ? '1' : '0';

            if (!$this->model->update($id, $data)) {
                return $this->failValidationErrors($this->model->errors());
            }

            return $this->respond($data);
        } catch (Exception $e) {
            return $this->failServerError('Error al actualizar la tarea.');
        }
    }

    /**
     * Elimina una tarea por ID.
     *
     * @param int|null $id ID de la tarea
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function delete($id = null)
    {
        try {
            if (!$this->model->find($id)) {
                return $this->failNotFound('Tarea no encontrada.');
            }

            if (!$this->model->delete($id)) {
                return $this->failServerError('No se pudo eliminar la tarea.');
            }

            return $this->respondDeleted(['message' => 'Tarea eliminada.']);
        } catch (Exception $e) {
            return $this->failServerError('Error al eliminar la tarea.');
        }
    }
}
