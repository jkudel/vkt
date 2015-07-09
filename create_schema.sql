CREATE TABLE users (
  id       INT UNSIGNED            NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name     VARCHAR(20)             NOT NULL UNIQUE,
  password CHAR(60)                NOT NULL,
  role     TINYINT UNSIGNED        NOT NULL,
  balance  DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT 0.00
)
  CHARACTER SET = utf8
  ENGINE = InnoDB;

CREATE TABLE orders (
  id          INT UNSIGNED            NOT NULL AUTO_INCREMENT PRIMARY KEY,
  description VARCHAR(200)            NOT NULL,
  price       DECIMAL(10, 2) UNSIGNED NOT NULL,
  time        INT UNSIGNED            NOT NULL,
  customer_id INT UNSIGNED            NOT NULL,
  executor_id INT UNSIGNED            NOT NULL DEFAULT 0,
  done        BOOL                    NOT NULL DEFAULT FALSE,
  done_time   BIGINT UNSIGNED         NOT NULL DEFAULT 0,
  INDEX (executor_id),
  INDEX (customer_id, done)
)
  CHARACTER SET = utf8
  ENGINE = InnoDB;

CREATE TABLE waiting_orders (
  id          INT UNSIGNED            NOT NULL PRIMARY KEY,
  description VARCHAR(200)            NOT NULL,
  price       DECIMAL(10, 2) UNSIGNED NOT NULL,
  time        BIGINT UNSIGNED         NOT NULL
)
  CHARACTER SET = utf8
  ENGINE InnoDB;