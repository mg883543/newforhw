<?php
session_start();

// Параметры подключения к БД
$host = 'localhost';
$dbname = 'u82314';
$username = 'u82314';
$password = '2851429';

function generateLogin($full_name) {
    // Убираем все символы, кроме букв (латиница и кириллица)
    $name = preg_replace('/[^a-zA-Zа-яА-Я]/u', '', $full_name);
    
    // Если строка пустая, используем 'user'
    if (empty($name)) {
        $name = 'user';
    }
    
    // Берем первые 10 символов с корректной работой с UTF-8
    $chars = preg_split('//u', $name, -1, PREG_SPLIT_NO_EMPTY);
    $name = implode('', array_slice($chars, 0, 10));
    
    $random = rand(100, 999);
    return strtolower($name) . $random;
}

function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

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
    setcookie('form_errors', json_encode($errors), 0, '/');
    
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
    $pdo->beginTransaction();

    $is_authenticated = isset($_SESSION['user_id']);
    $user_id = $_SESSION['user_id'] ?? null;

    if ($is_authenticated && $user_id) {
        // Обновление существующей записи
        $stmt = $pdo->prepare("SELECT id FROM application WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $user_id]);
        $application = $stmt->fetch();
        
        if ($application) {
            // Обновляем данные
            $sql = "UPDATE application SET 
                    full_name = :full_name,
                    phone = :phone,
                    email = :email,
                    birth_date = :birth_date,
                    gender = :gender,
                    biography = :biography,
                    contract_accepted = :contract_accepted,
                    updated_at = NOW()
                    WHERE user_id = :user_id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':full_name' => $input['full_name'],
                ':phone' => $input['phone'],
                ':email' => $input['email'],
                ':birth_date' => $input['birth_date'],
                ':gender' => $input['gender'],
                ':biography' => !empty($input['biography']) ? $input['biography'] : null,
                ':contract_accepted' => 1,
                ':user_id' => $user_id
            ]);
            
            $application_id = $application['id'];
            
            // Удаляем старые связи с языками
            $stmt = $pdo->prepare("DELETE FROM application_languages WHERE application_id = :application_id");
            $stmt->execute([':application_id' => $application_id]);
        } else {
            throw new Exception("Заявка не найдена для пользователя ID: $user_id");
        }
    } else {
        // Создание новой записи
        $login = generateLogin($input['full_name']);
        $plain_password = generatePassword();
        $password_hash = password_hash($plain_password, PASSWORD_DEFAULT);
        
        // Создаем пользователя
        $stmt = $pdo->prepare("INSERT INTO users (login, password_hash) VALUES (:login, :password_hash)");
        $stmt->execute([
            ':login' => $login,
            ':password_hash' => $password_hash
        ]);
        
        $user_id = $pdo->lastInsertId();
        
        // Сохраняем данные в сессию для отображения логина/пароля
        $_SESSION['generated_login'] = $login;
        $_SESSION['generated_password'] = $plain_password;
        
        // Создаем заявку
        $sql = "INSERT INTO application (user_id, full_name, phone, email, birth_date, gender, biography, contract_accepted, created_at) 
                VALUES (:user_id, :full_name, :phone, :email, :birth_date, :gender, :biography, :contract_accepted, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $user_id,
            ':full_name' => $input['full_name'],
            ':phone' => $input['phone'],
            ':email' => $input['email'],
            ':birth_date' => $input['birth_date'],
            ':gender' => $input['gender'],
            ':biography' => !empty($input['biography']) ? $input['biography'] : null,
            ':contract_accepted' => 1
        ]);
        
        $application_id = $pdo->lastInsertId();
    }
    
    // Сохраняем языки
    if (!empty($input['languages'])) {
        $placeholders = implode(',', array_fill(0, count($input['languages']), '?'));
        $stmt = $pdo->prepare("SELECT id, name FROM programming_languages WHERE name IN ($placeholders)");
        $stmt->execute($input['languages']);
        $language_ids = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $sql = "INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        
        foreach ($input['languages'] as $language_name) {
            if (isset($language_ids[$language_name])) {
                $stmt->execute([$application_id, $language_ids[$language_name]]);
            }
        }
    }
    
    $pdo->commit();
    
    // Сохраняем данные в Cookies на 1 год (только для неавторизованных)
    if (!$is_authenticated) {
        $expire = time() + 365 * 24 * 60 * 60;
        foreach ($input as $key => $value) {
            if ($key == 'languages' && is_array($value)) {
                setcookie('saved_' . $key, json_encode($value), $expire, '/');
            } elseif ($key != 'contract_accepted') {
                setcookie('saved_' . $key, $value, $expire, '/');
            }
        }
    }
    
    $_SESSION['success_message'] = $is_authenticated ? 'Данные успешно обновлены!' : 'Данные успешно сохранены!';
    
    
} catch (Exception $e) {
    $pdo->rollBack();
    $errors['database'] = 'Ошибка при сохранении данных: ' . $e->getMessage();
    setcookie('form_errors', json_encode($errors), 0, '/');
    
    foreach ($input as $key => $value) {
        if ($key == 'languages' && is_array($value)) {
            setcookie('saved_' . $key, json_encode($value), 0, '/');
        } else {
            setcookie('saved_' . $key, $value, 0, '/');
        }
    }
}

header('Location: index.php');
exit;
?>