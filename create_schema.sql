# Таблица пользователей. Разбивается по id.
CREATE TABLE users (
  id       INT UNSIGNED            NOT NULL PRIMARY KEY,
  name     VARCHAR(20)             NOT NULL UNIQUE,
  password CHAR(60)                NOT NULL,
  role     TINYINT UNSIGNED        NOT NULL,
  balance  DECIMAL(15, 2) UNSIGNED NOT NULL DEFAULT 0.00
)
  CHARACTER SET = utf8
  ENGINE = InnoDB;

# Таблица, используемая для генерации нового user_id. Она нужна, т.к. users может быть разбита.
CREATE TABLE sequences (
  user_id INT UNSIGNED NOT NULL DEFAULT 0
)
  CHARACTER SET = utf8,
  ENGINE = InnoDB;

INSERT INTO sequences VALUES ();

# Таблица ожидающих заказов. Разбивается по customer_id. order_id в этой таблице является уникальным, но вообще
# заказ однозначно идентифицируется парой (customer_id, order_id)
CREATE TABLE waiting_orders (
  order_id    INT UNSIGNED            NOT NULL AUTO_INCREMENT PRIMARY KEY,
  customer_id INT UNSIGNED            NOT NULL,
  description VARCHAR(300)            NOT NULL,
  price       DECIMAL(10, 2) UNSIGNED NOT NULL,
  time        BIGINT UNSIGNED         NOT NULL,
  INDEX (order_id, customer_id),
  INDEX (customer_id, time),
  INDEX (customer_id),
  INDEX (time)
)
  CHARACTER SET = utf8
  ENGINE = InnoDB;

# Табица используется заказчиком для просмотра своих уже исполненных заказов. Разбивается по customer_id.
CREATE TABLE done_orders_for_customer (
  order_id    INT UNSIGNED            NOT NULL,
  customer_id INT UNSIGNED            NOT NULL,
  description VARCHAR(300)            NOT NULL,
  price       DECIMAL(10, 2) UNSIGNED NOT NULL,
  time        BIGINT UNSIGNED         NOT NULL,
  executor_id INT UNSIGNED            NOT NULL,
  done_time   BIGINT UNSIGNED         NOT NULL,
  PRIMARY KEY (order_id, customer_id),
  INDEX (customer_id, time),
  INDEX (customer_id)
)
  CHARACTER SET = utf8
  ENGINE = InnoDB;

# Дублирующая таблица исполненных заказов, которая используется исполнителем. Хранит profit вместо price.
# Разбивается по executor_id.
CREATE TABLE done_orders_for_executor (
  order_id    INT UNSIGNED            NOT NULL,
  customer_id INT UNSIGNED            NOT NULL,
  description VARCHAR(300)            NOT NULL,
  profit      DECIMAL(10, 2) UNSIGNED NOT NULL,
  time        BIGINT UNSIGNED         NOT NULL,
  executor_id INT UNSIGNED            NOT NULL,
  done_time   BIGINT UNSIGNED         NOT NULL,
  PRIMARY KEY (order_id, customer_id),
  INDEX (executor_id, time),
  INDEX (executor_id)
)
  CHARACTER SET = utf8
  ENGINE = InnoDB;

# Лог недавно удаленных и исполненных заказов. Используется для быстрого обновления ленты исполнителя.
# Очищается раз в несколько минут cron-скриптом clean.php. Разбивается по customer_id, чем балансируется нагрузка на запись.
# Для чтения - кэш
CREATE TABLE done_or_canceled_log (
  order_id    INT UNSIGNED    NOT NULL,
  customer_id INT UNSIGNED    NOT NULL,
  time        BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (order_id, customer_id),
  INDEX (time)
)
  CHARACTER SET = utf8
  ENGINE = InnoDB;

# Таблица для хранения сессий.
CREATE TABLE sessions (
  id         VARCHAR(255) NOT NULL PRIMARY KEY,
  touch_time BIGINT       NOT NULL,
  data       TEXT         NOT NULL
)
  CHARACTER SET = utf8
  ENGINE = InnoDB;


# Времена устаревания кэшей
CREATE TABLE expiration_times (
  id   TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  time BIGINT UNSIGNED  NOT NULL
)
  CHARACTER SET = utf8
  ENGINE = MEMORY;

# Кэш блока последних заказов в очереди
CREATE TABLE feed_cache (
  order_id    INT UNSIGNED            NOT NULL,
  customer_id INT UNSIGNED            NOT NULL,
  description VARCHAR(300)            NOT NULL,
  price       DECIMAL(10, 2) UNSIGNED NOT NULL,
  time        BIGINT UNSIGNED         NOT NULL,
  PRIMARY KEY (order_id, customer_id)
)
  CHARACTER SET = utf8
  ENGINE = MEMORY;

# Кэш лога удаленных и исполненных заказов. Содержит в себе лол полностью.
CREATE TABLE done_or_canceled_log_cache (
  order_id    INT UNSIGNED    NOT NULL,
  customer_id INT UNSIGNED    NOT NULL,
  time        BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (order_id, customer_id),
  INDEX (time)
)
  CHARACTER SET = utf8
  ENGINE = MEMORY;