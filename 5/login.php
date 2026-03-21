<?php
session_start();

$host = 'localhost';
$dbname = 'u82314';
$username = 'u82314';
$password = '2851429';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

$login = $_POST['login'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($login) || empty($password)) {
    $_SESSION['error_messages'] = ['Введите логин и пароль'];
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE login = :login");
$stmt->execute([':login' => $login]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['login'] = $user['login'];
    $_SESSION['success_message'] = 'Вы успешно вошли в систему!';
} else {
    $_SESSION['error_messages'] = ['Неверный логин или пароль'];
}

header('Location: index.php');
exit;
?>