-- Создаем основную таблицу для заявок
CREATE TABLE application (
    id int(10) unsigned NOT NULL AUTO_INCREMENT,
    full_name varchar(150) NOT NULL,
    phone varchar(20) NOT NULL,
    email varchar(100) NOT NULL,
    birth_date date NOT NULL,
    gender enum('male', 'female', 'other') NOT NULL,
    biography text,
    contract_accepted tinyint(1) NOT NULL DEFAULT 0,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Создаем таблицу для языков программирования (справочник)
CREATE TABLE programming_languages (
    id int(10) unsigned NOT NULL AUTO_INCREMENT,
    name varchar(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Заполняем таблицу языков программирования
INSERT INTO programming_languages (name) VALUES 
('Pascal'), 
('C'), 
('C++'), 
('JavaScript'), 
('PHP'), 
('Python'), 
('Java'), 
('Haskell'), 
('Clojure'), 
('Prolog'), 
('Scala'), 
('Go');

-- Создаем таблицу связи между заявками и языками (связь многие-ко-многим)
CREATE TABLE application_languages (
    application_id int(10) unsigned NOT NULL,
    language_id int(10) unsigned NOT NULL,
    PRIMARY KEY (application_id, language_id),
    FOREIGN KEY (application_id) REFERENCES application(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES programming_languages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
