<?php


$db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');

$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$uri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$input = json_decode(file_get_contents('php://input'), true);

if ($uri[0] !== 'tasks') {
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
    exit;
}

$id = $uri[1] ?? null;


if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($task){
            echo json_encode($task);
        } else{
            http_response_code(404);
        }

    } else {
        $tasks = $db->query("SELECT * FROM tasks")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($tasks);
    }
    exit;
}


if ($method === 'POST') {
    if (empty($input['title'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Title is required']);
        exit;
    }

    $title = $input['title'];
    $description = $input['description'] ?? '';
    $status = $input['status'] ?? 'pending';

    $stmt = $db->prepare("INSERT INTO tasks (title, description, status) VALUES (?, ?, ?)");
    $stmt->execute([$title, $description, $status]);
    
    $taskId = $db->lastInsertId();
    $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$taskId]);
    $newTask = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($newTask);
    exit;
}


if ($method === 'PUT' && $id) {

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }


    $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        http_response_code(404);
        echo json_encode(['error' => 'Task not found']);
        exit;
    }


    $title = array_key_exists('title', $input) ? $input['title'] : $existing['title'];
    $description = array_key_exists('description', $input) ? $input['description'] : $existing['description'];
    $status = array_key_exists('status', $input) ? $input['status'] : $existing['status'];


    if (array_key_exists('title', $input) && trim($title) === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Title cannot be empty']);
        exit;
    }


    $stmt = $db->prepare("UPDATE tasks SET title = ?, description = ?, status = ? WHERE id = ?");
    $stmt->execute([$title, $description, $status, $id]);


    $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$id]);
    $updated = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($updated);
    exit;
}


if ($method === 'DELETE' && $id) {
    $stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['deleted' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
