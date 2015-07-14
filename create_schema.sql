CREATE TABLE users (
  id       INT UNSIGNED            NOT NULL PRIMARY KEY,
  name     VARCHAR(20)             NOT NULL UNIQUE,
  password CHAR(60)                NOT NULL,
  role     TINYINT UNSIGNED        NOT NULL,
  balance  DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT 0.00
)
  CHARACTER SET = utf8
  ENGINE = InnoDB;

CREATE TABLE sequences (
  user_id INT UNSIGNED NOT NULL DEFAULT 0
)
  CHARACTER SET = utf8,
  ENGINE = InnoDB;

INSERT INTO sequences VALUES ();

CREATE TABLE waiting_orders (
  order_id    INT UNSIGNED            NOT NULL AUTO_INCREMENT PRIMARY KEY,
  customer_id INT UNSIGNED            NOT NULL,
  description VARCHAR(200)            NOT NULL,
  price       DECIMAL(10, 2) UNSIGNED NOT NULL,
  time        BIGINT UNSIGNED         NOT NULL,
  INDEX (order_id, customer_id),
  INDEX (customer_id, time),
  INDEX (customer_id),
  INDEX (time)
)
  CHARACTER SET = utf8
  ENGINE = InnoDB;

CREATE TABLE done_orders_for_customer (
  order_id    INT UNSIGNED            NOT NULL,
  customer_id INT UNSIGNED            NOT NULL,
  description VARCHAR(200)            NOT NULL,
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

CREATE TABLE done_orders_for_executor (
  order_id    INT UNSIGNED            NOT NULL,
  customer_id INT UNSIGNED            NOT NULL,
  description VARCHAR(200)            NOT NULL,
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

CREATE TABLE done_or_canceled_log (
  order_id    INT UNSIGNED    NOT NULL,
  customer_id INT UNSIGNED    NOT NULL,
  time        BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (order_id, customer_id),
  INDEX (time)
)
  CHARACTER SET = utf8
  ENGINE = InnoDB;