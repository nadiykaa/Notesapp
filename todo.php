<?php
session_start();

// Перевірка, чи користувач увійшов в систему
if (!isset($_SESSION['username'])) {
    header("Location: index.php"); // Перенаправлення на сторінку авторизації, якщо користувач не увійшов в систему
    exit();
}

$user_id = $_SESSION['user_id'];

$conn = new mysqli('localhost', 'root', '', 'todo_app');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Обробка запитів, які надійшли методом POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['task'])) {

        // Додавання нового завдання
        $task = $_POST['task'];
        $stmt = $conn->prepare("INSERT INTO tasks (user_id, task, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("is", $user_id, $task);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['delete_task'])) {

        // Видалення завдання
        $task_id = $_POST['delete_task'];
        $deleted_status = 1; // 1 для видалення
        $stmt = $conn->prepare("UPDATE tasks SET deleted = ?, deleted_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->bind_param("iii", $deleted_status, $task_id, $user_id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['toggle_task'])) {
        // Зміна статусу завдання (виконано/не виконано)
        $task_id = $_POST['toggle_task'];
        $stmt = $conn->prepare("UPDATE tasks SET is_completed = !is_completed, updated_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['logout'])) {
        // Вихід з системи
        session_unset();
        session_destroy();
        header("Location: index.php");
        exit();
    }
}

// Отримання тільки тих завдань, які не видалено
$result = $conn->query("SELECT id, task, is_completed FROM tasks WHERE user_id = $user_id AND deleted = 0 ORDER BY id DESC");
$tasks = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>


<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Мої завдання</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color:blueviolet;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            font-family:Verdana, Geneva, Tahoma, sans-serif;
            color:brown;
            text-align: center;
            
        }
       
        .task-form {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        .task-form input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-right: 10px;
        }
        .task-form input[type="submit"] {
            padding: 10px 20px;
            border: none;
            background-color:#007bff;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }
        .task-form input[type="submit"]:hover {
            background-color:blue;
        }
        .logout-form {
            text-align:right;
            
        }
        .logout-form input[type="submit"] {
            padding: 10px 20px;
            border: none;
            background-color:blueviolet;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            
        }
        .logout-form input[type="submit"]:hover {
            background-color:blue;
        }
         /*Стилізує список завдань. Відключає маркери списку та встановлює нульові відступи.*/
        .task-list {
            list-style-type: none;
            padding: 0;
        }
        /*Стилізує кожен елемент списку завдань*/
        .task-list li {
            padding: 15px;
            margin-bottom: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        .task-list li.done {
            text-decoration: line-through;
            color: #888;
        }
        .task-list .task-actions {
            display: flex;
            gap: 10px;
        }
        .task-list button {
            padding: 5px 10px;
            border: none;
            background-color:#007bff;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }
        .task-list button.done {
            background-color:darkgreen;
        }
        .task-list button:hover {
            opacity: 0.8;
        }
        .welcome-message {
    text-align: center;
    margin-bottom: 20px;
}

.welcome-message p {
    color:brown;
    font-size: 18px;
}

.welcome-message span {
    font-weight: bold;
    color: #007bff; /* Синій колір тексту */
}

    </style>
</head>
<body>
    <div class="container">
    <div class="welcome-message">
    <p>Ласкаво просимо, <span><?= $_SESSION['username'] ?></span>!</p>
</div>

        <h1>Мої завдання</h1>
        <form class="task-form" method="post" action="todo.php">
            <input type="text" name="task" placeholder="Додати нове завдання" required>
            <input type="submit" value="Додати">
        </form>
       
        <ul class="task-list">
            <?php foreach ($tasks as $task): ?>
                <li class="<?= $task['is_completed'] ? 'done' : '' ?>">
                    <?= htmlspecialchars($task['task']) ?>
                    <div class="task-actions">
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="toggle_task" value="<?= $task['id'] ?>">
                            <button class="done" type="submit">
                                <?php if ($task['is_completed']){
                                    echo 'Не виконано';
                                 }else{ 
                                    echo 'Виконано';} 
                                    ?></button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="delete_task" value="<?= $task['id'] ?>">
                            <button type="submit">Видалити</button>
                        </form>
                        
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <form class="logout-form" method="post" action="todo.php">
            <input type="hidden" name="logout">
            <input type="submit" value="Вийти">
        </form>
    </div>
    
</body>
</html>
