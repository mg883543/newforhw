<?php
session_start();

// Функция для генерации случайного логина
function generateLogin($full_name) {
    $name = preg_replace('/[^a-zA-Zа-яА-Я]/u', '', $full_name);
    $name = mb_substr($name, 0, 10);
    $random = rand(100, 999);
    return strtolower($name) . $random;
}

// Функция для генерации случайного пароля
function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

// Функция для получения значений из Cookies
function getCookieValue($key, $default = '') {
    return isset($_COOKIE['saved_' . $key]) ? htmlspecialchars($_COOKIE['saved_' . $key]) : $default;
}

// Функция для получения массива из Cookies (для языков)
function getCookieArray($key, $default = []) {
    if (isset($_COOKIE['saved_' . $key])) {
        return json_decode($_COOKIE['saved_' . $key], true) ?? $default;
    }
    return $default;
}

// Проверка авторизации
$is_authenticated = isset($_SESSION['user_id']) && isset($_SESSION['login']);
$user_id = $_SESSION['user_id'] ?? null;
$user_login = $_SESSION['login'] ?? null;

// Получаем данные авторизованного пользователя из БД
$user_data = null;
$user_languages = [];

if ($is_authenticated && $user_id) {
    try {
        $host = 'localhost';
        $dbname = 'u82314';
        $username = 'u82314';
        $password = '2851429';
        
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Получаем данные пользователя
        $stmt = $pdo->prepare("SELECT * FROM application WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $user_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Получаем выбранные языки
        if ($user_data) {
            $stmt = $pdo->prepare("
                SELECT pl.name 
                FROM programming_languages pl
                JOIN application_languages al ON pl.id = al.language_id
                WHERE al.application_id = :application_id
            ");
            $stmt->execute([':application_id' => $user_data['id']]);
            $user_languages = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        $pdo = null;
    } catch (PDOException $e) {
        error_log("DB Error: " . $e->getMessage());
    }
}

// Получаем ошибки из Cookies
$error_messages = [];
if (isset($_COOKIE['form_errors'])) {
    $error_messages = json_decode($_COOKIE['form_errors'], true) ?? [];
    setcookie('form_errors', '', time() - 3600, '/');
}

// Получаем ранее введенные значения из Cookies (если есть и нет авторизации)
$old_input = [];
if (!$is_authenticated || !$user_data) {
    $cookie_fields = ['full_name', 'phone', 'email', 'birth_date', 'gender', 'biography', 'contract_accepted'];
    foreach ($cookie_fields as $field) {
        $old_input[$field] = getCookieValue($field);
    }
    $old_input['languages'] = getCookieArray('languages');
} elseif ($user_data) {
    $old_input = [
        'full_name' => $user_data['full_name'],
        'phone' => $user_data['phone'],
        'email' => $user_data['email'],
        'birth_date' => $user_data['birth_date'],
        'gender' => $user_data['gender'],
        'biography' => $user_data['biography'],
        'contract_accepted' => $user_data['contract_accepted'],
        'languages' => $user_languages
    ];
}

// Сообщение об успехе
$success_message = $_SESSION['success_message'] ?? '';
$generated_login = $_SESSION['generated_login'] ?? '';
$generated_password = $_SESSION['generated_password'] ?? '';
unset($_SESSION['success_message']);
unset($_SESSION['generated_login']);
unset($_SESSION['generated_password']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Анкета</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1e3c72;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .header {
            background: #2a5298;
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .auth-info {
            background: #f8f9fa;
            padding: 15px 30px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .auth-info span {
            font-size: 14px;
            color: #666;
        }

        .auth-info strong {
            color: #2a5298;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 5px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s ease;
        }

        .logout-btn:hover {
            background: #c82333;
        }

        .login-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .login-form input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .login-form button {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .login-form button:hover {
            background: #218838;
        }

        .content {
            padding: 40px;
        }

        .generated-credentials {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .generated-credentials h3 {
            margin-bottom: 10px;
        }

        .generated-credentials p {
            margin: 5px 0;
            font-family: monospace;
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group label.required::after {
            content: " *";
            color: #e74c3c;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #2a5298;
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.1);
        }

        .form-control.error {
            border-color: #e74c3c;
            background-color: #fff8f8;
        }

        .error-message {
            color: #e74c3c;
            font-size: 13px;
            margin-top: 5px;
            display: block;
            padding: 5px 10px;
            background-color: #fff3f3;
            border-radius: 4px;
            border-left: 3px solid #e74c3c;
        }

        .global-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .global-error ul {
            margin-left: 20px;
            margin-top: 5px;
        }

        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .radio-group {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .radio-option {
            display: flex;
            align-items: center;
        }

        .radio-option input[type="radio"] {
            margin-right: 8px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .radio-option label {
            margin-bottom: 0;
            font-weight: normal;
            cursor: pointer;
        }

        select[multiple] {
            height: 150px;
        }

        select[multiple] option {
            padding: 8px 12px;
        }

        select[multiple] option:checked {
            background: #2a5298;
            color: white;
        }

        select[multiple].error {
            border-color: #e74c3c;
            background-color: #fff8f8;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
        }

        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin-bottom: 0;
            font-weight: normal;
            cursor: pointer;
        }

        .btn-submit {
            background: #2a5298;
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .btn-submit:hover {
            background: #1e3c72;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(30, 60, 114, 0.4);
        }

        .info-text {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }

        hr {
            border: none;
            border-top: 2px solid #e0e0e0;
            margin: 30px 0;
        }

        @media (max-width: 768px) {
            .content {
                padding: 25px;
            }
            
            .radio-group {
                gap: 15px;
            }
            
            .auth-info {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Анкета</h1>
            <p>Пожалуйста, заполните все обязательные поля</p>
        </div>
        
        <div class="auth-info">
            <?php if ($is_authenticated): ?>
                <span>Вы авторизованы как: <strong><?php echo htmlspecialchars($user_login); ?></strong></span>
                <a href="logout.php" class="logout-btn">Выйти</a>
            <?php else: ?>
                <form method="POST" action="login.php" class="login-form">
                    <input type="text" name="login" placeholder="Логин" required>
                    <input type="password" name="password" placeholder="Пароль" required>
                    <button type="submit">Войти</button>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="content">
            <?php if ($success_message): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($generated_login && $generated_password): ?>
                <div class="generated-credentials">
                    <h3>Ваши данные для входа!</h3>
                    <p><strong>Логин:</strong> <?php echo htmlspecialchars($generated_login); ?></p>
                    <p><strong>Пароль:</strong> <?php echo htmlspecialchars($generated_password); ?></p>
                    <p><small>Сохраните эти данные, они понадобятся для редактирования анкеты.</small></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_messages)): ?>
                <div class="global-error">
                    <strong>Пожалуйста, исправьте следующие ошибки:</strong>
                    <ul>
                        <?php foreach ($error_messages as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="form.php" novalidate>
                <div class="form-group">
                    <label for="full_name" class="required">ФИО</label>
                    <input type="text" 
                           class="form-control <?php echo isset($error_messages['full_name']) ? 'error' : ''; ?>" 
                           id="full_name" 
                           name="full_name" 
                           value="<?php echo htmlspecialchars($old_input['full_name'] ?? ''); ?>"
                           placeholder="Иванов Иван Иванович"
                           required>
                    <div class="info-text">Только буквы, пробелы и дефисы, не более 150 символов</div>
                    <?php if (isset($error_messages['full_name'])): ?>
                        <div class="error-message"><?php echo $error_messages['full_name']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="phone" class="required">Телефон</label>
                    <input type="tel" 
                           class="form-control <?php echo isset($error_messages['phone']) ? 'error' : ''; ?>" 
                           id="phone" 
                           name="phone" 
                           value="<?php echo htmlspecialchars($old_input['phone'] ?? ''); ?>"
                           placeholder="+7 (999) 123-45-67"
                           required>
                    <div class="info-text">Формат: +7 (999) 123-45-67 или 89991234567</div>
                    <?php if (isset($error_messages['phone'])): ?>
                        <div class="error-message"><?php echo $error_messages['phone']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email" class="required">E-mail</label>
                    <input type="email" 
                           class="form-control <?php echo isset($error_messages['email']) ? 'error' : ''; ?>" 
                           id="email" 
                           name="email" 
                           value="<?php echo htmlspecialchars($old_input['email'] ?? ''); ?>"
                           placeholder="example@domain.com"
                           required>
                    <div class="info-text">Формат: name@domain.xx</div>
                    <?php if (isset($error_messages['email'])): ?>
                        <div class="error-message"><?php echo $error_messages['email']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="birth_date" class="required">Дата рождения</label>
                    <input type="date" 
                           class="form-control <?php echo isset($error_messages['birth_date']) ? 'error' : ''; ?>" 
                           id="birth_date" 
                           name="birth_date" 
                           value="<?php echo htmlspecialchars($old_input['birth_date'] ?? ''); ?>"
                           required>
                    <div class="info-text">Формат: ГГГГ-ММ-ДД</div>
                    <?php if (isset($error_messages['birth_date'])): ?>
                        <div class="error-message"><?php echo $error_messages['birth_date']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="required">Пол</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" 
                                   id="gender_male" 
                                   name="gender" 
                                   value="male"
                                   <?php echo (isset($old_input['gender']) && $old_input['gender'] == 'male') ? 'checked' : ''; ?>
                                   required>
                            <label for="gender_male">Мужской</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" 
                                   id="gender_female" 
                                   name="gender" 
                                   value="female"
                                   <?php echo (isset($old_input['gender']) && $old_input['gender'] == 'female') ? 'checked' : ''; ?>
                                   required>
                            <label for="gender_female">Женский</label>
                        </div>
                    </div>
                    <?php if (isset($error_messages['gender'])): ?>
                        <div class="error-message"><?php echo $error_messages['gender']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="languages" class="required">Любимый язык программирования</label>
                    <select multiple 
                            class="form-control <?php echo isset($error_messages['languages']) ? 'error' : ''; ?>" 
                            id="languages" 
                            name="languages[]" 
                            size="6"
                            required>
                        <?php
                        $languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
                        $selected_languages = $old_input['languages'] ?? [];
                        foreach ($languages as $lang):
                        ?>
                            <option value="<?php echo $lang; ?>" 
                                <?php echo in_array($lang, $selected_languages) ? 'selected' : ''; ?>>
                                <?php echo $lang; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="info-text">Для множественного выбора используйте Ctrl/Cmd + клик</div>
                    <?php if (isset($error_messages['languages'])): ?>
                        <div class="error-message"><?php echo $error_messages['languages']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="biography">Биография</label>
                    <textarea class="form-control" 
                              id="biography" 
                              name="biography" 
                              rows="5"
                              placeholder="Расскажите о себе..."><?php echo htmlspecialchars($old_input['biography'] ?? ''); ?></textarea>
                    <div class="info-text">Необязательное поле</div>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" 
                               id="contract_accepted" 
                               name="contract_accepted" 
                               value="1"
                               <?php echo (isset($old_input['contract_accepted']) && $old_input['contract_accepted'] == '1') ? 'checked' : ''; ?>
                               required>
                        <label for="contract_accepted" class="required">С контрактом ознакомлен(а)</label>
                    </div>
                    <?php if (isset($error_messages['contract_accepted'])): ?>
                        <div class="error-message"><?php echo $error_messages['contract_accepted']; ?></div>
                    <?php endif; ?>
                </div>

                <hr>

                <button type="submit" class="btn-submit">Сохранить</button>
            </form>
        </div>
    </div>
</body>
</html>