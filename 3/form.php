<?php
session_start();

// Параметры подключения к БД
$host = 'localhost';
$dbname = 'u82314';
$username = 'u82314';
$password = '2851429';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Массивы для хранения ошибок и данных
$errors = [];
$input = $_POST;

// Валидация ФИО
if (empty($input['full_name'])) {
    $errors['full_name'] = 'Поле ФИО обязательно для заполнения';
} elseif (strlen($input['full_name']) > 128) {
    $errors['full_name'] = 'ФИО не должно превышать 128 символов';
} elseif (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s-]+$/u', $input['full_name'])) {
    $errors['full_name'] = 'ФИО должно содержать только буквы, пробелы и дефисы';
}

// Валидация телефона
if (empty($input['phone'])) {
    $errors['phone'] = 'Поле Телефон обязательно для заполнения';
} elseif (!preg_match('/^[\+\d\s\(\)\-]{10,20}$/', $input['phone'])) {
    $errors['phone'] = 'Введите корректный номер телефона';
}

// Валидация email
if (empty($input['email'])) {
    $errors['email'] = 'Поле E-mail обязательно для заполнения';
} elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Введите корректный email адрес';
} elseif (strlen($input['email']) > 100) {
    $errors['email'] = 'Email не должен превышать 100 символов';
}

// Валидация даты рождения
if (empty($input['birth_date'])) {
    $errors['birth_date'] = 'Поле Дата рождения обязательно для заполнения';
} else {
    $date = DateTime::createFromFormat('Y-m-d', $input['birth_date']);
    if (!$date || $date->format('Y-m-d') !== $input['birth_date']) {
        $errors['birth_date'] = 'Введите корректную дату рождения';
    } elseif ($date > new DateTime()) {
        $errors['birth_date'] = 'Дата рождения не может быть в будущем';
    }
}

// Валидация пола
$allowed_genders = ['male', 'female'];
if (empty($input['gender'])) {
    $errors['gender'] = 'Поле Пол обязательно для выбора';
} elseif (!in_array($input['gender'], $allowed_genders)) {
    $errors['gender'] = 'Выберите корректное значение пола';
}

// Валидация языков программирования
$allowed_languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
if (empty($input['languages']) || !is_array($input['languages'])) {
    $errors['languages'] = 'Выберите хотя бы один язык программирования';
} else {
    foreach ($input['languages'] as $lang) {
        if (!in_array($lang, $allowed_languages)) {
            $errors['languages'] = 'Выбран недопустимый язык программирования';
            break;
        }
    }
}

// Валидация чекбокса
if (!isset($input['contract_accepted']) || $input['contract_accepted'] != '1') {
    $errors['contract_accepted'] = 'Необходимо подтвердить ознакомление с контрактом';
}

// Если есть ошибки, возвращаемся к форме
if (!empty($errors)) {
    $_SESSION['error_messages'] = $errors;
    $_SESSION['old_input'] = $input;
    header('Location: index.php');
    exit;
}

try {
    // Начинаем транзакцию
    $pdo->beginTransaction();

    $sql = "INSERT INTO application (full_name, phone, email, birth_date, gender, biography, contract_accepted, created_at) 
            VALUES (:full_name, :phone, :email, :birth_date, :gender, :biography, :contract_accepted, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':full_name' => $input['full_name'],
        ':phone' => $input['phone'],
        ':email' => $input['email'],
        ':birth_date' => $input['birth_date'],
        ':gender' => $input['gender'],
        ':biography' => $input['biography'] ?? null,
        ':contract_accepted' => 1
    ]);

    // Получаем ID последней вставленной записи
    $application_id = $pdo->lastInsertId();

    // Получаем ID выбранных языков программирования
    $placeholders = implode(',', array_fill(0, count($input['languages']), '?'));
    $stmt = $pdo->prepare("SELECT id, name FROM programming_languages WHERE name IN ($placeholders)");
    $stmt->execute($input['languages']);
    $language_ids = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Вставляем связи в таблицу application_languages
    $sql = "INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    
    foreach ($input['languages'] as $language_name) {
        if (isset($language_ids[$language_name])) {
            $stmt->execute([$application_id, $language_ids[$language_name]]);
        }
    }

    // Подтверждаем транзакцию
    $pdo->commit();

    $_SESSION['success_message'] = 'Данные успешно сохранены!';
    
} catch (PDOException $e) {
    // Откатываем транзакцию в случае ошибки
    $pdo->rollBack();
    $_SESSION['error_messages'] = ['database' => 'Ошибка при сохранении данных: ' . $e->getMessage()];
    $_SESSION['old_input'] = $input;
}

// Перенаправляем обратно на форму
header('Location: index.php');
exit;
?>