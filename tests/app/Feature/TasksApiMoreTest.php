<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class TasksApiMoreTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $refresh     = true;
    protected $migrateOnce = false;
    protected $namespace   = 'App';

    private function seedTasks(): void
    {
        $now = date('Y-m-d H:i:s');
        // Inserta directo en DB para poblar index() sin pasar por create()
        $this->hasInDatabase('tasks', ['title' => 'Alpha',   'completed' => 0, 'created_at' => $now, 'updated_at' => $now]);
        $this->hasInDatabase('tasks', ['title' => 'Beta',    'completed' => 1, 'created_at' => $now, 'updated_at' => $now]);
        $this->hasInDatabase('tasks', ['title' => 'Gamma',   'completed' => 0, 'created_at' => $now, 'updated_at' => $now]);
        $this->hasInDatabase('tasks', ['title' => 'Delta',   'completed' => 1, 'created_at' => $now, 'updated_at' => $now]);
        $this->hasInDatabase('tasks', ['title' => 'Alameda', 'completed' => 1, 'created_at' => $now, 'updated_at' => $now]);
    }

    public function testIndex_BasicPaginationAndHeaders(): void
    {
        $this->seedTasks();

        $res = $this->get('/api/tasks?per_page=2&page=1&sort=-created_at');

        $res->assertStatus(200);
        $res->assertHeader('X-Total-Count');
        $res->assertHeader('Link');

        $payload = json_decode($res->getJSON(), true);
        $this->assertArrayHasKey('data', $payload);
        $this->assertCount(2, $payload['data']);
        $this->assertArrayHasKey('links', $payload);
        $this->assertNotEmpty($payload['links']['next']);
    }

    public function testIndex_FiltersCompletedAndSearch(): void
    {
        $this->seedTasks();

        $res = $this->get('/api/tasks?completed=true&search=Al');
        $res->assertStatus(200);

        $payload = json_decode($res->getJSON(), true);
        $this->assertGreaterThanOrEqual(1, count($payload['data']));
        foreach ($payload['data'] as $row) {
            $this->assertSame(1, (int) $row['completed']);
            $this->assertStringContainsStringIgnoringCase('al', $row['title']);
        }
    }

    public function testIndex_InvalidQuery_Returns422(): void
    {
        $res = $this->get('/api/tasks?per_page=1000&sort=invalid');
        $res->assertStatus(422);

        $payload = json_decode($res->getJSON(), true);
        $this->assertArrayHasKey('errors', $payload);
        $this->assertArrayHasKey('per_page', $payload['errors']);
        $this->assertArrayHasKey('sort', $payload['errors']);
    }

    public function testUpdateValid_Returns200_AndPersists(): void
    {
        // Crea por API para respetar normalización de completed
        $create = $this->withBodyFormat('json')
                       ->withHeaders(['Accept' => 'application/json'])
                       ->post('/api/tasks', ['title' => 'Original', 'completed' => false]);
        $create->assertStatus(201);
        $id = (int) json_decode($create->getJSON(), true)['id'];

        $update = $this->withBodyFormat('json')
                       ->withHeaders(['Accept' => 'application/json'])
                       ->put('/api/tasks/' . $id, ['title' => 'Modificada', 'completed' => true]);

        $update->assertStatus(200);
        $data = json_decode($update->getJSON(), true);
        $this->assertSame('Modificada', $data['title']);
        $this->assertSame(1, (int) $data['completed']);
    }

    public function testCreate_InvalidCompleted_Returns422(): void
    {
        $res = $this->withBodyFormat('json')
                    ->withHeaders(['Accept' => 'application/json'])
                    ->post('/api/tasks', ['title' => 'X', 'completed' => 'maybe']);

        $res->assertStatus(422);
        $payload = json_decode($res->getJSON(), true);
        $this->assertArrayHasKey('completed', $payload['errors']);
    }
}
