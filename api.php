<?php
header("Content-Type: application/json");
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$resource = $path[1] ?? null;
$id = $path[2] ?? null;

// GET all books or with filters
if ($method == 'GET' && $resource == 'books') {
    $sql = "SELECT * FROM books WHERE 1";
    $params = [];

    if (!empty($_GET['title'])) {
        $sql .= " AND title LIKE ?";
        $params[] = "%" . $_GET['title'] . "%";
    }

    if (!empty($_GET['author'])) {
        $sql .= " AND author = ?";
        $params[] = $_GET['author'];
    }

    if (!empty($_GET['publication_year'])) {
        $sql .= " AND publication_year = ?";
        $params[] = $_GET['publication_year'];
    }

    $stmt = $conn->prepare($sql);
    if ($params) {
        $types = str_repeat("s", count($params));
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $books = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($books);
}

// GET book by ID
elseif ($method == 'GET' && $resource == 'book' && $id) {
    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();
    echo json_encode($book ?: ["error" => "Book not found"]);
}

// CREATE book
elseif ($method == 'POST' && $resource == 'book') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data['title'] || !$data['author'] || !$data['isbn'] || !$data['publication_year']) {
        http_response_code(400);
        echo json_encode(["error" => "All fields are required"]);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO books (title, author, isbn, publication_year) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $data['title'], $data['author'], $data['isbn'], $data['publication_year']);
    $stmt->execute();
    echo json_encode(["message" => "Book added", "id" => $stmt->insert_id]);
}

// UPDATE book
elseif ($method == 'PUT' && $resource == 'book' && $id) {
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $conn->prepare("UPDATE books SET title=?, author=?, isbn=?, publication_year=? WHERE id=?");
    $stmt->bind_param("sssii", $data['title'], $data['author'], $data['isbn'], $data['publication_year'], $id);
    $stmt->execute();
    echo json_encode(["message" => "Book updated"]);
}

// DELETE book
elseif ($method == 'DELETE' && $resource == 'book' && $id) {
    $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(["message" => "Book deleted"]);
}

else {
    http_response_code(404);
    echo json_encode(["error" => "Invalid endpoint or method"]);
}
?>
