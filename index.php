<?php
session_start();
//// Якщо користувач вже авторизований (є user_id в сесії), перенаправляємо його на сторінку todo.php
if (isset($_SESSION['user_id'])) {
    header("Location: todo.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $conn = new mysqli('localhost', 'root', '', 'todo_app');
    // Перевіряємо, чи було встановлено з'єднання з базою даних, якщо ні - виводимо помилку і припиняємо виконання скрипта
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT id, password, login_count FROM users WHERE username = ?");
    // Зв'язуємо параметр $username з підготовленим запитом
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hashed_password, $login_count);

    if ($stmt->num_rows > 0) {
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;

            $login_count++;
            $update_stmt = $conn->prepare("UPDATE users SET login_count = ? WHERE id = ?");
            $update_stmt->bind_param("ii", $login_count, $id);
            $update_stmt->execute();
            $update_stmt->close();

            header("Location: todo.php");
            exit();
        } else {
            $error = "Невірний пароль.";
        }
    } else {
        $error = "Користувача з таким ім'ям не знайдено.";
    }
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Вхід</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="login-box">
            <h2>Вхід в систему</h2>
            <form method="post" action="index.php">
                <div class="textbox">
                    <input type="text" placeholder="Логін" name="username" required>
                </div>
                <div class="textbox">
                    <input type="password" placeholder="Пароль" name="password" required>
                </div>
                <input type="submit" class="btn" value="Увійти">
                <p>У вас немає акаунта? <a href="register.php">Зареєструватися</a></p>
            </form>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
