
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use app\Controllers\AuthController;
require __DIR__ . '/../vendor/autoload.php';



use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->setAsGlobal();
// 启动Eloquent
$capsule->bootEloquent();
// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
// / 查看所有环境变量
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => $_ENV['DB_HOST'],
    'database'  => $_ENV['DB_DATABASE'],
    'username'  => $_ENV['DB_USERNAME'],
    'password'  => $_ENV['DB_PASSWORD'],
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

// 设置全局静态可访问

// var_dump($_ENV); 
// Create Container
$container = new Container();

// Set container to create App with on AppFactory
AppFactory::setContainer($container);

// Create App
$app = AppFactory::create();
$app->addBodyParsingMiddleware();

$app->addErrorMiddleware(true, true, true);


// Add Error Middleware
$app->addErrorMiddleware(true, true, true);
$app->get('/', function ($request, $response, $args) {
        $response->getBody()->write("Slim API is running!");
        return $response;
    });
// Add routes
// $app->post('/api/login','App\Controllers\AuthController:login');

$app->group('/api', function (RouteCollectorProxy $group) {

    
    // Auth routes
    $group->post('/login', 'App\Controllers\AuthController:login');
    $group->post('/register', 'App\Controllers\AuthController:register');
    
    // Protected routes
    $group->group('', function (RouteCollectorProxy $group) {
        // Resource management
        $group->get('/resources', 'App\Controllers\ResourceController:list');
        $group->post('/resources', 'App\Controllers\ResourceController:create');
        $group->put('/resources/{id}', 'App\Controllers\ResourceController:update');
        $group->delete('/resources/{id}', 'App\Controllers\ResourceController:delete');
        
        // Equipment management
        $group->get('/equipment', 'App\Controllers\EquipmentController:list');
        $group->post('/equipment', 'App\Controllers\EquipmentController:create');
        $group->put('/equipment/{id}', 'App\Controllers\EquipmentController:update');
        $group->delete('/equipment/{id}', 'App\Controllers\EquipmentController:delete');
        
        // Maintenance management
        $group->get('/maintenance', 'App\Controllers\MaintenanceController:list');
        $group->post('/maintenance', 'App\Controllers\MaintenanceController:create');
        $group->put('/maintenance/{id}', 'App\Controllers\MaintenanceController:update');
        
        // User management
        $group->get('/users', 'App\Controllers\UserController:list');
        $group->post('/users', 'App\Controllers\UserController:create');
        $group->put('/users/{id}', 'App\Controllers\UserController:update');
        $group->delete('/users/{id}', 'App\Controllers\UserController:delete');
    })->add('App\Middleware\AuthMiddleware');
});

// Run app
$app->run(); 