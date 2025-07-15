<?php
$db = new PDO('sqlite:guestbook.db');

$db->exec("
    CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        message TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if(!empty($message)){
        $name = empty($name) ? 'Анонимно' : $name;

        $stmt = $db->prepare('INSERT INTO messages (name, message) VALUES (:name, :message)');
        $stmt->execute(['name' => $name, 'message' => $message]);

        header('Location: guestbook.php');
        exit;
    }
}

$stmt = $db->query('SELECT name, message, created_at FROM messages ORDER BY created_at DESC');
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Гостевая книга</title>

    <style>
        body {
            max-width: 600px; 
            margin: 0 auto; 
            padding: 20px; 
        }
        .message { 
            border: 1px solid black; 
            padding: 10px; 
            margin-bottom: 10px; 
            position: relative; 
        }
        .date { 
            position: absolute; 
            top: 5px; 
            left: 10px; 
            font-size: 12px;
        }
        .name { 
            position: absolute; 
            top: 5px; 
            right: 10px;  
        }
        .text { 
            margin: 20px 0 0 0; 
            padding-top: 5px; 
        }
        form { 
            margin-top: 20px; 
        }
        input, textarea { 
            width: 100%; 
            margin-bottom: 10px; 
            padding: 8px; 
            box-sizing: border-box; 
        }
    </style>
</head>
<body>
    <?php foreach ($messages as $msg): ?>
        <div class="message">
            <div class="date"><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($msg['created_at']))); ?></div>
            <div class="name"><?php echo htmlspecialchars($msg['name']); ?></div>
            <div class="text"><?php echo htmlspecialchars($msg['message']); ?></div>
        </div>
    <?php endforeach; ?>
    <?php if (empty($messages)): ?>
        <p style="text-align: center;">Пока нет сообщений.</p>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="name" placeholder="Имя" value="">
        <textarea name="message" rows="4" placeholder="Сообщение" required></textarea>
        <button type="submit">Отправить</button>
    </form>
</body>
</html>