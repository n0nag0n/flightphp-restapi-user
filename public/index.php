<?php
require __DIR__.'/../vendor/autoload.php';
$config = require __DIR__.'/../config/config.php';
require __DIR__.'/../middleware/AuthMiddleware.php';

Flight::register('db', \flight\database\PdoWrapper::class, [ 'sqlite:'.$config['database_path'] ], function($db){
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
});

// This will create the database and the users table if it doesn't exist already
if(file_exists($config['database_path']) === false) {
    $db = Flight::db();
    $db->runQuery("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, password TEXT NOT NULL)");
}

// A group helps group together similar routes for convenience
Flight::group('/users', function(\flight\net\Router $router) {

    // Get all users
    $router->get('', function(){
        $db = Flight::db();
        $users = $db->fetchAll("SELECT * FROM users");
        Flight::json($users);
    });

    // Get user by id
    $router->get('/@id', function($id){
        $db = Flight::db();
        $user = $db->fetchRow("SELECT * FROM users WHERE id = :id", [ ':id' => $id ]);
        if (!empty($user['id'])) {
            Flight::json($user);
        } else {
            Flight::jsonHalt([ 'message' => 'User not found' ], 404);
        }
    });

    // Create new user
    $router->post('', function(){
        $data = Flight::request()->data;
        $db = Flight::db();
        $result = $db->runQuery("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)", [ 
            ':name' => $data['name'], 
            ':email' => $data['email'], 
            ':password' => password_hash($data['password'], PASSWORD_BCRYPT) 
        ]);
        Flight::json([ 'id' => $db->lastInsertId() ], 201);
    });

    // Update user
    $router->put('/@id', function($id){
        $data = Flight::request()->data->getData();
        $db = Flight::db();
        $result = $db->runQuery("UPDATE users SET name = :name, email = :email, password = :password WHERE id = :id", [
            ':id' => $id,
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':password' => password_hash($data['password'], PASSWORD_BCRYPT)
        ]);
        Flight::json([ 'message' => 'User updated successfully' ]);
    });

    // Delete user
    $router->delete('/@id', function($id){
        $db = Flight::db();
        $stmt = $db->runQuery("DELETE FROM users WHERE id = :id", [ ':id' => $id ]);
        Flight::json([ 'message' => 'User deleted successfully' ]);
    });

}, [ new AuthMiddleware() ]);

Flight::start();