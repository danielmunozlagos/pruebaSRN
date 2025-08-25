<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->group('api', static function ($routes) {
    $routes->resource('tasks', [
        'controller' => 'Tasks',
        'only'       => ['index', 'show', 'create', 'update', 'delete'],
    ]);
});