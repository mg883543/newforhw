<?php
$host = 'localhost';
$dbname = 'u82314';
$username = 'u82314';
$password = '2851429';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Содержимое таблицы application</h2>";
    
    // Получаем все записи
    $stmt = $pdo->query("SELECT * FROM application ORDER BY id DESC");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($applications) > 0) {
        echo "<table border='1' cellpadding='5'>";
        // Заголовки таблицы
        echo "<tr>";
        foreach (array_keys($applications[0]) as $column) {
            echo "<th>" . htmlspecialchars($column) . "</th>";
        }
        echo "</tr>";
        
        // Данные
        foreach ($applications as $app) {
            echo "<tr>";
            foreach ($app as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p>Всего записей: " . count($applications) . "</p>";
    } else {
        echo "<p>Таблица пуста</p>";
    }
    
    // Показываем языки для каждой заявки
    echo "<h2>Выбранные языки программирования</h2>";
    $stmt = $pdo->query("
        SELECT a.full_name, pl.name as language
        FROM application a
        JOIN application_languages al ON a.id = al.application_id
        JOIN programming_languages pl ON al.language_id = pl.id
        ORDER BY a.id
    ");
    $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($languages) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ФИО</th><th>Язык программирования</th></tr>";
        foreach ($languages as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['language']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Нет данных о языках</p>";
    }
    
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>