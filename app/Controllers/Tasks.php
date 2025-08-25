<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controlador REST de Tareas.
 *
 * Endpoints:
 *  - GET    /api/tasks           Listado paginado (frontend)
 *  - POST   /api/tasks           Crea tarea (201 + Location)
 *  - GET    /api/tasks/{id}      Muestra una tarea
 *  - PUT    /api/tasks/{id}      Actualiza una tarea
 *  - DELETE /api/tasks/{id}      Elimina una tarea (204)
 */
class Tasks extends ResourceController
{
    protected $modelName = 'App\Models\TaskModel';
    protected $format    = 'json';

    /**
     * Listado paginado para frontend.
     *
     * Query params:
     *  - page (int>=1)                por defecto: 1
     *  - per_page (1..100)            por defecto: 10
     *  - search (string)              busca en title
     *  - completed (0|1|true|false)   filtra por estado
     *  - sort (id,-id,title,-title,created_at,-created_at,updated_at,-updated_at)
     *
     * Respuesta 200:
     *  {
     *    "data": [ ...tareas... ],
     *    "meta": { "page":1, "per_page":10, "total":123, "total_pages":13, "sort":"-created_at", "filters":{...} },
     *    "links": { "self":"...", "next":"...", "prev":"...", "first":"...", "last":"..." }
     *  }
     * Headers: X-Total-Count, Link
     */
    public function index()
    {
        $q = $this->request->getGet();

        $rules = [
            'page'      => 'if_exist|is_natural_no_zero',
            'per_page'  => 'if_exist|is_natural_no_zero|less_than_equal_to[100]',
            'search'    => 'if_exist|string|min_length[1]|max_length[255]',
            'completed' => 'if_exist|in_list[0,1,true,false,TRUE,FALSE,on,off,yes,no]',
            'sort'      => 'if_exist|in_list[id,-id,title,-title,created_at,-created_at,updated_at,-updated_at]',
        ];
        if (!$this->validate($rules)) {
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY) // 422
                ->setJSON(['errors' => $this->validator->getErrors()]);
        }

        $page      = (int)($q['page']     ?? 1);
        $perPage   = (int)($q['per_page'] ?? 10);
        $search    = $q['search']    ?? null;
        $completed = $q['completed'] ?? null;
        $sort      = $q['sort']      ?? '-created_at';

        $model = $this->model;

        if ($completed !== null) {
            $bool = filter_var($completed, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $model = $model->where('completed', $bool ? 1 : 0);
        }
        if ($search) {
            $model = $model->like('title', $search);
        }

        $field = ltrim($sort, '-');
        $dir   = str_starts_with($sort, '-') ? 'DESC' : 'ASC';
        $model = $model->orderBy($field, $dir);

        $data  = $model->paginate($perPage, 'default', $page);
        $pager = $model->pager;

        $total = $pager->getTotal();
        $last  = (int) ceil(max(1, $total) / max(1, $perPage));

        $queryNoPage = $q;
        unset($queryNoPage['page'], $queryNoPage['per_page']);
        $base = base_url('api/tasks');

        $buildLink = function (int $p) use ($base, $queryNoPage, $perPage) {
            $query = array_merge($queryNoPage, ['page' => $p, 'per_page' => $perPage]);
            return $base . '?' . http_build_query($query);
        };

        $links = [
            'self'  => $buildLink($page),
            'next'  => $page < $last ? $buildLink($page + 1) : null,
            'prev'  => $page > 1     ? $buildLink($page - 1) : null,
            'first' => $buildLink(1),
            'last'  => $buildLink($last),
        ];

        // Link header (RFC5988)
        $linkHeaderParts = [];
        if ($links['next'])  $linkHeaderParts[] = '<' . $links['next']  . '>; rel="next"';
        if ($links['prev'])  $linkHeaderParts[] = '<' . $links['prev']  . '>; rel="prev"';
        $linkHeaderParts[] = '<' . $links['first'] . '>; rel="first"';
        $linkHeaderParts[] = '<' . $links['last']  . '>; rel="last"';
        $linkHeader = implode(', ', $linkHeaderParts);

        $payload = [
            'data' => $data,
            'meta' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => $last,
                'sort'        => $sort,
                'filters'     => [
                    'search'    => $search,
                    'completed' => $completed,
                ],
            ],
            'links' => $links,
        ];

        return $this->response
            ->setStatusCode(ResponseInterface::HTTP_OK)
            ->setHeader('X-Total-Count', (string) $total)
            ->setHeader('Link', $linkHeader)
            ->setJSON($payload);
    }

    /** @return ResponseInterface 201 Created + Location + body JSON | 422 con {errors} */
    public function create()
    {
        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        // Normaliza 'completed' si viene
        if (array_key_exists('completed', $data)) {
            $bool = filter_var($data['completed'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($bool === null) {
                return $this->response->setStatusCode(422)->setJSON([
                    'errors' => ['completed' => 'Valor inválido para completed.']
                ]);
            }
            $data['completed'] = $bool ? 1 : 0;
        }

        $rules = [
            'title'     => 'required|string|min_length[3]|max_length[255]',
            'completed' => 'if_exist|in_list[0,1]',
        ];

        // ✅ Inicializa y ejecuta el validador
        if (! $this->validateData($data, $rules)) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors()]);
        }

        // Si necesitas acceder a los datos "limpios"
        $valid = $this->validator->getValidated();

        $id = $this->model->insert($valid, true);
        if (! $id) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $this->model->errors()]);
        }

        $resource = $this->model->find($id);
        $location = url_to(self::class . '::show', $id);

        return $this->response
            ->setJSON($resource)
            ->setStatusCode(201)
            ->setHeader('Location', $location);
    }


    /** @return ResponseInterface 200 con JSON o 404 */
    public function show($id = null)
    {
        $resource = $this->model->find($id);
        if (!$resource) {
            return $this->failNotFound('Task not found.');
        }
        return $this->response->setJSON($resource)->setStatusCode(ResponseInterface::HTTP_OK);
    }

    /** @return ResponseInterface 200 con JSON | 404 | 422 con {errors} */
    public function update($id = null)
    {
        $existing = $this->model->find($id);
        if (! $existing) {
            return $this->failNotFound('Task not found.');
        }

        $data = $this->request->getJSON(true) ?? $this->request->getRawInput();

        if (array_key_exists('completed', $data)) {
            $bool = filter_var($data['completed'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($bool === null) {
                return $this->response->setStatusCode(422)->setJSON([
                    'errors' => ['completed' => 'Valor inválido para completed.']
                ]);
            }
            $data['completed'] = $bool ? 1 : 0;
        }

        $rules = [
            'title'     => 'if_exist|string|min_length[3]|max_length[255]',
            'completed' => 'if_exist|in_list[0,1]',
        ];

        // ✅ Usa validateData para setear $this->validator
        if (! $this->validateData($data, $rules)) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors()]);
        }

        $valid = $this->validator->getValidated();

        if (! $this->model->update($id, $valid)) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $this->model->errors()]);
        }

        $resource = $this->model->find($id);
        return $this->response->setJSON($resource)->setStatusCode(200);
    }


    /** @return ResponseInterface 204 No Content | 404 */
    public function delete($id = null)
    {
        $existing = $this->model->find($id);
        if (!$existing) {
            return $this->failNotFound('Task not found.');
        }

        $this->model->delete($id);
        return $this->response->setStatusCode(ResponseInterface::HTTP_NO_CONTENT);
    }
}
