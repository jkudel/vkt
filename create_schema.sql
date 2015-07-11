CREATE TABLE users (
  id       INT UNSIGNED            NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name     VARCHAR(20)             NOT NULL UNIQUE,
  password CHAR(60)                NOT NULL,
  role     TINYINT UNSIGNED        NOT NULL,
  balance  DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT 0.00
)
  CHARACTER SET = utf8
  ENGINE = InnoDB;

CREATE TABLE waiting_orders (
  id          INT UNSIGNED            NOT NULL AUTO_INCREMENT PRIMARY KEY,
  customer_id INT UNSIGNED            NOT NULL,
  description VARCHAR(200)            NOT NULL,
  price       DECIMAL(10, 2) UNSIGNED NOT NULL,
  time        BIGINT UNSIGNED         NOT NULL,
  INDEX (id, customer_id),
  INDEX (customer_id, time),
  INDEX (customer_id),
  INDEX (time)
)
  CHARACTER SET = utf8
  ENGINE = InnoDB;

CREATE TABLE done_orders_for_customer (
  id          INT UNSIGNED            NOT NULL,
  customer_id INT UNSIGNED            NOT NULL,
  description VARCHAR(200)            NOT NULL,
  price       DECIMAL(10, 2) UNSIGNED NOT NULL,
  time        BIGINT UNSIGNED         NOT NULL,
  executor_id INT UNSIGNED            NOT NULL,
  done_time   BIGINT UNSIGNED         NOT NULL,
  PRIMARY KEY (id, customer_id),
  INDEX (customer_id, time),
  INDEX (customer_id)
)
  CHARACTER SET = utf8
  ENGINE = InnoDB;

CREATE TABLE done_orders_for_executor (
  id          INT UNSIGNED            NOT NULL,
  customer_id INT UNSIGNED            NOT NULL,
  description VARCHAR(200)            NOT NULL,
  profit      DECIMAL(10, 2) UNSIGNED NOT NULL,
  time        BIGINT UNSIGNED         NOT NULL,
  executor_id INT UNSIGNED            NOT NULL,
  done_time   BIGINT UNSIGNED         NOT NULL,
  PRIMARY KEY (id, customer_id),
  INDEX (executor_id, time),
  INDEX (executor_id)
)
  CHARACTER SET = utf8
  ENGINE = InnoDB;

CREATE TABLE changes_log (
  order_id INT UNSIGNED     NOT NULL PRIMARY KEY,
  type     TINYINT UNSIGNED NOT NULL,
  time     INT UNSIGNED     NOT NULL,
  INDEX (time)
)
  CHARACTER SET = utf8
  ENGINE = InnoDB;