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

// Регулярные выражения для валидации
$patterns = [
    'full_name' => '/^[а-яА-ЯёЁa-zA-Z\s-]{2,150}$/u',
    'phone' => '/^[\+\d\s\(\)\-]{10,20}$/',
    'email' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
    'birth_date' => '/^\d{4}-\d{2}-\d{2}$/'
];

// Валидация ФИО
if (empty($input['full_name'])) {
    $errors['full_name'] = 'Поле ФИО обязательно для заполнения';
} elseif (!preg_match($patterns['full_name'], $input['full_name'])) {
    $errors['full_name'] = 'ФИО должно содержать только буквы, пробелы и дефисы (2-150 символов)';
} elseif (strlen($input['full_name']) > 150) {
    $errors['full_name'] = 'ФИО не должно превышать 150 символов';
}

// Валидация телефона
if (empty($input['phone'])) {
    $errors['phone'] = 'Поле Телефон обязательно для заполнения';
} elseif (!preg_match($patterns['phone'], $input['phone'])) {
    $errors['phone'] = 'Телефон может содержать только цифры, пробелы, +, -, (, ) (10-20 символов)';
}

// Валидация email
if (empty($input['email'])) {
    $errors['email'] = 'Поле E-mail обязательно для заполнения';
} elseif (!preg_match($patterns['email'], $input['email'])) {
    $errors['email'] = 'Введите корректный email адрес (пример: name@domain.com)';
} elseif (strlen($input['email']) > 100) {
    $errors['email'] = 'Email не должен превышать 100 символов';
}

// Валидация даты рождения
if (empty($input['birth_date'])) {
    $errors['birth_date'] = 'Поле Дата рождения обязательно для заполнения';
} elseif (!preg_match($patterns['birth_date'], $input['birth_date'])) {
    $errors['birth_date'] = 'Дата должна быть в формате ГГГГ-ММ-ДД';
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
    $errors['gender'] = 'Выберите корректное значение пола (Мужской или Женский)';
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

// Если есть ошибки, сохраняем в Cookies и возвращаемся к форме
if (!empty($errors)) {
    // Сохраняем ошибки в Cookies (на время сессии)
    setcookie('form_errors', json_encode($errors), 0, '/');
    
    // Сохраняем введенные данные в Cookies (на время сессии)
    foreach ($input as $key => $value) {
        if ($key == 'languages' && is_array($value)) {
            setcookie('saved_' . $key, json_encode($value), 0, '/');
        } else {
            setcookie('saved_' . $key, $value, 0, '/');
        }
    }
    
    header('Location: index.php');
    exit;
}

try {
    // Начинаем транзакцию
    $pdo->beginTransaction();

    // Вставляем данные в таблицу application
    $sql = "INSERT INTO application (full_name, phone, email, birth_date, gender, biography, contract_accepted, created_at) 
            VALUES (:full_name, :phone, :email, :birth_date, :gender, :biography, :contract_accepted, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':full_name' => $input['full_name'],
        ':phone' => $input['phone'],
        ':email' => $input['email'],
        ':birth_date' => $input['birth_date'],
        ':gender' => $input['gender'],
        ':biography' => !empty($input['biography']) ? $input['biography'] : null,
        ':contract_accepted' => 1
    ]);

    // Получаем ID последней вставленной записи
    $application_id = $pdo->lastInsertId();

    // Получаем ID выбранных языков программирования
    if (!empty($input['languages'])) {
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
    }

    // Подтверждаем транзакцию
    $pdo->commit();

    // При успешном сохранении сохраняем данные в Cookies на 1 год
    $expire = time() + 365 * 24 * 60 * 60; // 1 год
    
    foreach ($input as $key => $value) {
        if ($key == 'languages' && is_array($value)) {
            setcookie('saved_' . $key, json_encode($value), $expire, '/');
        } elseif ($key != 'contract_accepted') { // Не сохраняем чекбокс
            setcookie('saved_' . $key, $value, $expire, '/');
        }
    }

    $_SESSION['success_message'] = 'Данные успешно сохранены!';
    
} catch (Exception $e) {
    // Откатываем транзакцию в случае ошибки
    $pdo->rollBack();
    
    // Сохраняем ошибку в Cookies
    $errors['database'] = 'Ошибка при сохранении данных: ' . $e->getMessage();
    setcookie('form_errors', json_encode($errors), 0, '/');
    
    // Сохраняем введенные данные
    foreach ($input as $key => $value) {
        if ($key == 'languages' && is_array($value)) {
            setcookie('saved_' . $key, json_encode($value), 0, '/');
        } else {
            setcookie('saved_' . $key, $value, 0, '/');
        }
    }
}

// Перенаправляем обратно на форму
header('Location: index.php');
exit;
?>