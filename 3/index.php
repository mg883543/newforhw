<?php
session_start();

// Очищаем сообщения после отображения
$success_message = $_SESSION['success_message'] ?? '';
$error_messages = $_SESSION['error_messages'] ?? [];
$old_input = $_SESSION['old_input'] ?? [];

unset($_SESSION['success_message']);
unset($_SESSION['error_messages']);
unset($_SESSION['old_input']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Анкета супергероя</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1e3c72;  /* Темно-синий фон */
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
            background: #2a5298;  /* Синий фон для шапки */
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

        .content {
            padding: 40px;
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
            border-color: #2a5298;  /* Синий при фокусе */
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.1);
        }

        .form-control.error {
            border-color: #e74c3c;
        }

        .error-message {
            color: #e74c3c;
            font-size: 13px;
            margin-top: 5px;
            display: block;
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

        .abilities-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .ability-item {
            display: flex;
            align-items: center;
        }

        .ability-item input[type="checkbox"] {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .ability-item label {
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
            background: #2a5298;  /* Синий для выбранных опций */
            color: white;
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
            background: #2a5298;  /* Синий фон кнопки */
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
            background: #1e3c72;  /* Темно-синий при наведении */
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(30, 60, 114, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
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
            
            .abilities-group {
                grid-template-columns: 1fr 1fr;
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
        
        <div class="content">
            <?php if ($success_message): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success_message); ?>
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
                    <div class="info-text">Только буквы и пробелы, не более 128 символов</div>
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
                </div>

                <div class="form-group">
                    <label for="birth_date" class="required">Дата рождения</label>
                    <input type="date" 
                           class="form-control <?php echo isset($error_messages['birth_date']) ? 'error' : ''; ?>" 
                           id="birth_date" 
                           name="birth_date" 
                           value="<?php echo htmlspecialchars($old_input['birth_date'] ?? ''); ?>"
                           required>
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
                </div>

                <div class="form-group">
                    <label for="biography">Биография</label>
                    <textarea class="form-control" 
                              id="biography" 
                              name="biography" 
                              rows="5"
                              placeholder="Расскажите о себе..."><?php echo htmlspecialchars($old_input['biography'] ?? ''); ?></textarea>
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
                </div>

                <hr>

                <button type="submit" class="btn-submit">Сохранить</button>
            </form>
        </div>
    </div>
</body>
</html>