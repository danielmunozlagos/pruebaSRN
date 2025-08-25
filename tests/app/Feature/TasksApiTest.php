<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class TasksApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    /**
     * Hacemos que las migraciones corran ANTES de cada test
     * y que se refresquen (rollback a 0 y up) para aislar casos.
     * $namespace = null => usa migraciones de TODOS los namespaces (como `migrate --all`)
     * @see https://codeigniter.com/user_guide/testing/database.html
     */
    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace = 'App';

    protected function setUp(): void
    {
        parent::setUp();
        helper('url'); // por si queremos usar base_url() o site_url() en aserciones
    }

    // ---------- helpers ----------

    private function postJson(string $uri, array $body = [])
    {
        return $this->withHeaders(['Accept' => 'application/json'])
                    ->withBodyFormat('json')   // setea body + Content-Type
                    ->post($uri, $body);
    }

    private function putJson(string $uri, array $body = [])
    {
        return $this->withHeaders(['Accept' => 'application/json'])
                    ->withBodyFormat('json')
                    ->put($uri, $body);
    }

    private function createTask(string $title = 'Test task', $completed = false): array
    {
        $res = $this->postJson('/api/tasks', [
            'title'     => $title,
            'completed' => $completed,
        ]);

        $res->assertStatus(201);
        $data = json_decode($res->getJSON(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);

        return [$res, (int) $data['id'], $data];
    }

    // ---------- tests ----------

    /** create inválido (sin title) -> 422 */
    public function testCreateInvalid_Returns422(): void
    {
        $result = $this->postJson('/api/tasks', []); // sin title

        $result->assertStatus(422);

        $payload = json_decode($result->getJSON(), true);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('errors', $payload);
        $this->assertArrayHasKey('title', $payload['errors']); // debe fallar por requerido
    }

    /** create válido -> 201 y Location */
    public function testCreateValid_Returns201_AndLocation(): void
    {
        $title  = 'Tarea creada OK';
        $result = $this->postJson('/api/tasks', ['title' => $title, 'completed' => true]);

        $result->assertStatus(201);
        $result->assertHeader('Location'); // debe existir

        // validamos el cuerpo
        $data = json_decode($result->getJSON(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertSame($title, $data['title']);

        // validamos que Location apunte a /api/tasks/{id}
        $id       = (int) $data['id'];
        $location = $result->response()->getHeaderLine('Location');
        $this->assertNotEmpty($location);
        $this->assertStringEndsWith('/api/tasks/' . $id, rtrim($location, '/'));
    }

    /** get inexistente -> 404 */
    public function testShow_Nonexistent_Returns404(): void
    {
        $result = $this->get('/api/tasks/999999');
        $result->assertStatus(404);
    }

    /** update inválido -> 422 */
    public function testUpdateInvalid_Returns422(): void
    {
        [, $id] = $this->createTask('Para actualizar', false);

        // "title" vacío viola min_length[3]
        $result = $this->putJson('/api/tasks/' . $id, ['title' => '']);

        $result->assertStatus(422);
        $payload = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('errors', $payload);
        $this->assertArrayHasKey('title', $payload['errors']);
    }

    /** delete existente -> 204 y verificar desaparición */
    public function testDeleteExisting_Returns204_AndGone(): void
    {
        [, $id] = $this->createTask('Para borrar', true);

        $del = $this->delete('/api/tasks/' . $id);
        $del->assertStatus(204);

        // 1) endpoint ya no lo encuentra
        $get = $this->get('/api/tasks/' . $id);
        $get->assertStatus(404);

        // 2) tampoco existe en DB (sin soft deletes)
        $this->dontSeeInDatabase('tasks', ['id' => $id]);
    }
}
