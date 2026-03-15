-- Основная таблица для заявок
CREATE TABLE application (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(128) NOT NULL DEFAULT '',
  year int(10) NOT NULL DEFAULT 0,
  ability_god int(1) NOT NULL DEFAULT 0,
  ability_fly int(1) NOT NULL DEFAULT 0,
  ability_idclip int(1) NOT NULL DEFAULT 0,
  ability_fireball int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Для языков программирования создадим отдельную таблицу (так как в задании требуют множественный выбор)
CREATE TABLE programming_languages (
    id int(10) unsigned NOT NULL AUTO_INCREMENT,
    name varchar(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Заполняем таблицу языков
INSERT INTO programming_languages (name) VALUES 
('Pascal'), ('C'), ('C++'), ('JavaScript'), ('PHP'), 
('Python'), ('Java'), ('Haskell'), ('Clojure'), 
('Prolog'), ('Scala'), ('Go');

-- Таблица связи заявок с языками программирования
CREATE TABLE application_languages (
    application_id int(10) unsigned NOT NULL,
    language_id int(10) unsigned NOT NULL,
    PRIMARY KEY (application_id, language_id),
    FOREIGN KEY (application_id) REFERENCES application(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES programming_languages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;