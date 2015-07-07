CREATE TABLE users (
  id       INT UNSIGNED            NOT NULL AUTO_INCREMENT,
  name     VARCHAR(20)             NOT NULL UNIQUE,
  password CHAR(60)                NOT NULL,
  role     TINYINT UNSIGNED        NOT NULL,
  balance  DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT 0.00,
  PRIMARY KEY (id)
)
  CHARACTER SET = utf8
  ENGINE = InnoDB;

CREATE TABLE orders (
  id          INT UNSIGNED            NOT NULL AUTO_INCREMENT,
  description VARCHAR(200)            NOT NULL,
  price       DECIMAL(10, 2) UNSIGNED NOT NULL,
  time        INT UNSIGNED            NOT NULL,
  customer_id INT UNSIGNED            NOT NULL,
  executor_id INT UNSIGNED            NOT NULL DEFAULT 0,
  done        BOOL                    NOT NULL DEFAULT FALSE,
  done_time   BIGINT UNSIGNED         NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  INDEX (executor_id),
  INDEX (customer_id, done)
)
  CHARACTER SET = utf8
  ENGINE = InnoDB;

CREATE TABLE waiting_orders (
  id          INT UNSIGNED            NOT NULL AUTO_INCREMENT,
  order_id    INT UNSIGNED            NOT NULL UNIQUE,
  description VARCHAR(200)            NOT NULL,
  price       DECIMAL(10, 2) UNSIGNED NOT NULL,
  time        BIGINT UNSIGNED         NOT NULL,
  PRIMARY KEY (id)
)
  CHARACTER SET = utf8
  ENGINE InnoDB;