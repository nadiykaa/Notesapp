<?php 
if ($_SERVER["REQUEST_METHOD"] == "POST") { 
    $username = $_POST['username']; 
    $password = $_POST['password']; 
    $gender = $_POST['gender'];
    $birthdate = $_POST['birthdate'];
    $country = $_POST['country'];
    
    // Серверна перевірка пароля (регулярні вирази) 
    $passwordPattern = "/^(?=.*[a-z])(?=.*\d).{8,}$/"; // Вимоги: мінімум 8 символів, хоча б одна маленька буква та одна цифра
    if (!preg_match($passwordPattern, $password)) {
        $error = "Пароль повинен містити хоча б одну маленьку букву, цифру та бути довжиною мінімум 8 символів.";
    } else {
        // Хешуємо пароль користувача з використанням алгоритму bcrypt і зберігаємо у змінну $passwordHash
        $passwordHash = password_hash($password, PASSWORD_BCRYPT); 
        // Встановлюємо з'єднання з базою даних 'todo_app' на локальному сервері
        $conn = new mysqli('localhost', 'root', '', 'todo_app'); 
        if ($conn->connect_error) { 
            die("Connection failed: " . $conn->connect_error); 
        } 

        // Перевірка наявності користувача з таким ім'ям 
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?"); 
        //Зв'язуємо параметр $username з підготовленим запитом
        $stmt->bind_param("s", $username); 
        $stmt->execute(); 
        $stmt->store_result(); 

        if ($stmt->num_rows > 0) { 
            $error = "Користувач з таким іменем вже існує."; 
        } else { 
            $stmt->close(); 
            $stmt = $conn->prepare("INSERT INTO users (username, password, gender, birthdate, country) VALUES (?, ?, ?, ?, ?)"); 
            $stmt->bind_param("sssss", $username, $passwordHash, $gender, $birthdate, $country); 
            if ($stmt->execute()) { 
                // Якщо запит успішний, перенаправляємо користувача на головну сторінку
                header("Location: index.php"); 
                exit(); 
            } else { 
                $error = "Error: " . $stmt->error; 
            } 
        } 
        // Закриваємо підготовлений запит
        $stmt->close(); 
        // Закриваємо з'єднання з базою даних
        $conn->close(); 
    }
} 
?> 

<!DOCTYPE html> 
<html lang="uk"> 
<head> 
    <meta charset="UTF-8"> 
    <title>Реєстрація</title> 
    <link rel="stylesheet" href="style.css"> 
</head> 
<body> 
    <div class="container"> 
        <div class="login-box"> 
            <h2>Реєстрація</h2> 
            <form method="post" action="register.php"> 
                <div class="textbox"> 
                    <input type="text" placeholder="Логін" name="username" required> 
                </div> 
                <div class="textbox"> 
                    <input type="password" placeholder="Пароль" name="password" required> 
                </div> 
                <div class="textbox">
                    <select name="gender" required>
                        <option value="" disabled selected>Стать</option>
                        <option value="male">Чоловіча</option>
                        <option value="female">Жіноча</option>
                    </select>
                </div>
                <div class="textbox">
                    <input type="date" placeholder="Дата народження" name="birthdate" required>
                </div>
                <div class="textbox">
                    <input type="text" placeholder="Країна" name="country" required>
                </div>
                <input type="submit" class="btn" value="Зареєструватися"> 
            </form> 
            <?php if (isset($error)): ?> 
                <p class="error"><?php echo $error; ?></p> 
            <?php endif; ?> 
        </div> 
    </div> 
</body> 
</html>
