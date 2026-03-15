<?php
session_start();

// Параметры подключения к БД
$host = 'localhost';
$dbname = 'ваш_логин'; // замените на ваш логин
$username = 'ваш_логин'; // замените на ваш логин
$password = 'ваш_пароль'; // замените на ваш пароль

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

// Валидация года
if (empty($input['year'])) {
    $errors['year'] = 'Поле Год обязательно для заполнения';
} elseif (!is_numeric($input['year']) || $input['year'] < 1900 || $input['year'] > 2100) {
    $errors['year'] = 'Год должен быть числом от 1900 до 2100';
}

// Валидация пола
$allowed_genders = ['male', 'female', 'other'];
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

    // Подготавливаем значения способностей (по умолчанию 0)
    $abilities = [
        'god' => 0,
        'fly' => 0,
        'idclip' => 0,
        'fireball' => 0
    ];
    
    // Обновляем значения из POST
    if (isset($input['abilities'])) {
        foreach ($input['abilities'] as $ability => $value) {
            if (isset($abilities[$ability])) {
                $abilities[$ability] = 1;
            }
        }
    }

    // Вставляем данные в таблицу application
    $sql = "INSERT INTO application (name, year, ability_god, ability_fly, ability_idclip, ability_fireball) 
            VALUES (:name, :year, :ability_god, :ability_fly, :ability_idclip, :ability_fireball)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $input['full_name'],
        ':year' => $input['year'],
        ':ability_god' => $abilities['god'],
        ':ability_fly' => $abilities['fly'],
        ':ability_idclip' => $abilities['idclip'],
        ':ability_fireball' => $abilities['fireball']
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