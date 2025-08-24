<?php 
namespace Tests\App\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class TaskControllerTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $basePath = '/api/tasks';

    public function testCreateTask()
    {
        $data = [
            'title' => 'Tarea de prueba',
            'completed' => false,
        ];

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->withBody(json_encode($data))
          ->post($this->basePath);

        $response->assertStatus(201);

        $responseData = json_decode($response->getJSON(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals('Tarea de prueba', $responseData['title']);
    }

    public function testGetTask()
    {
        $createData = ['title' => 'Tarea para GET'];

        $createResponse = $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->withBody(json_encode($createData))
          ->post($this->basePath);

        $createResponse->assertStatus(201);
        $createdData = json_decode($createResponse->getJSON(), true);
        $this->assertIsArray($createdData);
        $id = $createdData['id'] ?? null;
        $this->assertNotNull($id);

        $response = $this->withHeaders(['Accept' => 'application/json'])
                         ->get($this->basePath . '/' . $id);

        $response->assertStatus(200);
        $responseData = json_decode($response->getJSON(), true);
        $this->assertIsArray($responseData);
        $this->assertEquals('Tarea para GET', $responseData['title']);
    }

    public function testDeleteTask()
    {
        $createData = ['title' => 'Tarea a eliminar'];

        $createResponse = $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->withBody(json_encode($createData))
          ->post($this->basePath);

        $createResponse->assertStatus(201);
        $createdData = json_decode($createResponse->getJSON(), true);
        $this->assertIsArray($createdData);
        $id = $createdData['id'] ?? null;
        $this->assertNotNull($id);

        $deleteResponse = $this->withHeaders(['Accept' => 'application/json'])
                               ->delete($this->basePath . '/' . $id);

        $deleteResponse->assertStatus(200);
        $deleteData = json_decode($deleteResponse->getJSON(), true);
        $this->assertIsArray($deleteData);
        $this->assertArrayHasKey('message', $deleteData);
        $this->assertEquals('Tarea eliminada.', $deleteData['message']);
    }
}
