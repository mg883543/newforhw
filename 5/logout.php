<?php
session_start();

// Очищаем все данные сессии
session_unset();
session_destroy();

// Удаляем сессионную cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

header('Location: index.php');
exit;
?>