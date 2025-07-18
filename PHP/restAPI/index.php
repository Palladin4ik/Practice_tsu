<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/database.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed', 'message' => $e->getMessage()]));
}

$pdo->exec("CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    price REAL NOT NULL,
    description TEXT
)");

$app->get('/api/products/', function (Request $request, Response $response) use ($pdo) {
    try {
        $stmt = $pdo->query('SELECT id, name, price, description FROM products');
        $products = $stmt->fetchAll();
        $response->getBody()->write(json_encode($products));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (Exception $e) {
        $error = ['error' => 'Internal Server Error', 'message' => $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->get('/api/products/{id}/', function (Request $request, Response $response, array $args) use ($pdo) {
    $id = (int)$args['id'];
    if ($id <= 0) {
        $error = ['error' => 'Bad Request', 'message' => 'Invalid product ID'];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
    try {
        $stmt = $pdo->prepare('SELECT id, name, price, description FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if (!$product) {
            $error = ['error' => 'Not Found', 'message' => "Product with ID $id not found"];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        $response->getBody()->write(json_encode($product));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (Exception $e) {
        $error = ['error' => 'Internal Server Error', 'message' => $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->post('/api/products/', function (Request $request, Response $response) use ($pdo) {
    $rawBody = $request->getBody()->getContents();
    $data = json_decode($rawBody, true);
    error_log('POST raw body: ' . $rawBody);
    error_log('POST data: ' . print_r($data, true));
    if (empty($data['name']) || !isset($data['price']) || !is_numeric($data['price']) || $data['price'] < 0) {
        $error = ['error' => 'Bad Request', 'message' => 'Name and valid price are required'];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
    try {
        $stmt = $pdo->prepare('INSERT INTO products (name, price, description) VALUES (?, ?, ?)');
        $stmt->execute([$data['name'], (float)$data['price'], $data['description'] ?? null]);
        $id = $pdo->lastInsertId();
        $response->getBody()->write(json_encode(['id' => $id, 'message' => 'Product created']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } catch (Exception $e) {
        $error = ['error' => 'Internal Server Error', 'message' => $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->put('/api/products/{id}/', function (Request $request, Response $response, array $args) use ($pdo) {
    $id = (int)$args['id'];
    if ($id <= 0) {
        $error = ['error' => 'Bad Request', 'message' => 'Invalid product ID'];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
    $rawBody = $request->getBody()->getContents();
    $data = json_decode($rawBody, true);
    error_log('PUT raw body: ' . $rawBody);
    error_log('PUT data: ' . print_r($data, true));
    if (empty($data['name']) || !isset($data['price']) || !is_numeric($data['price']) || $data['price'] < 0) {
        $error = ['error' => 'Bad Request', 'message' => 'Name and valid price are required'];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
    try {
        $stmt = $pdo->prepare('SELECT id FROM products WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            $error = ['error' => 'Not Found', 'message' => "Product with ID $id not found"];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        $stmt = $pdo->prepare('UPDATE products SET name = ?, price = ?, description = ? WHERE id = ?');
        $stmt->execute([$data['name'], (float)$data['price'], $data['description'] ?? null, $id]);
        $response->getBody()->write(json_encode(['message' => "Product $id updated"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (Exception $e) {
        $error = ['error' => 'Internal Server Error', 'message' => $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->delete('/api/products/{id}/', function (Request $request, Response $response, array $args) use ($pdo) {
    $id = (int)$args['id'];
    if ($id <= 0) {
        $error = ['error' => 'Bad Request', 'message' => 'Invalid product ID'];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
    try {
        $stmt = $pdo->prepare('SELECT id FROM products WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            $error = ['error' => 'Not Found', 'message' => "Product with ID $id not found"];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $response->getBody()->write(json_encode(['message' => "Product $id deleted"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (Exception $e) {
        $error = ['error' => 'Internal Server Error', 'message' => $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->run();
?>