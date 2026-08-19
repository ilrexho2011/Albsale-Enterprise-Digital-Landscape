-- =====================================================================
-- Albsale Vlora — Custom ERP · Skema bazë (MySQL / MariaDB)
-- Rifreskuar nga salt.sql me përmirësime sigurie/integriteti:
--   * charset utf8mb4
--   * user.password VARCHAR(255)  -> mban hash-in (password_hash), JO VARCHAR(20)
--   * user.username UNIQUE
--   * çelësa parësorë/të huaj eksplicitë
-- DB: albsale-vlora
-- =====================================================================
SET NAMES utf8mb4;
SET time_zone = "+00:00";

-- -------------------- Tabela: salt (artikuj) --------------------------
CREATE TABLE IF NOT EXISTS `salt` (
  `saltcode`     INT(11)      NOT NULL AUTO_INCREMENT,
  `title`        VARCHAR(50)  NOT NULL,
  `producer`     VARCHAR(50)  NOT NULL,
  `stock`        INT(11)      NOT NULL DEFAULT 0,
  `unit`         VARCHAR(10)  NOT NULL DEFAULT 'Ton',
  `priceperunit` INT(11)      NOT NULL DEFAULT 0,
  `currency`     VARCHAR(10)  NOT NULL DEFAULT 'EU',
  PRIMARY KEY (`saltcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------- Tabela: user (klientë/përdorues) ----------------
CREATE TABLE IF NOT EXISTS `user` (
  `id`       INT(11)       NOT NULL AUTO_INCREMENT,
  `name`     VARCHAR(50)   NOT NULL,
  `surname`  VARCHAR(50)   NOT NULL,
  `username` VARCHAR(50)   NULL,
  `password` VARCHAR(255)  NULL,          -- hash (bcrypt/argon2), kurrë tekst i thjeshtë
  `ZINN`     VARCHAR(30)   NOT NULL,
  `email`    VARCHAR(100)  NOT NULL,
  `tel`      VARCHAR(20)   NULL,
  `created`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_username` (`username`),
  KEY `ix_user_zinn` (`ZINN`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------- Tabela: salesorder (porosi) ---------------------
CREATE TABLE IF NOT EXISTS `salesorder` (
  `idso`     INT(11)      NOT NULL AUTO_INCREMENT,
  `ZINN`     VARCHAR(30)  NOT NULL,
  `saltcode` INT(11)      NOT NULL,
  `title`    VARCHAR(50)  NOT NULL,
  `quantity` INT(11)      NOT NULL,
  `unit`     VARCHAR(10)  NOT NULL DEFAULT 'Ton',
  `value`    INT(11)      NOT NULL,
  `currency` VARCHAR(10)  NOT NULL DEFAULT 'EU',
  `created`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idso`),
  KEY `ix_so_zinn` (`ZINN`),
  KEY `ix_so_saltcode` (`saltcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------- Të dhëna shembull (katalogu) --------------------
INSERT INTO `salt` (`saltcode`, `title`, `producer`, `stock`, `unit`, `priceperunit`, `currency`) VALUES
(13455,  'Jodiertes_Speisesalz',      'Albsale',    200,   'Ton', 955,  'EU'),
(35345,  'Rohes_Meersalz',            'Albsale',    1200,  'Ton', 654,  'EU'),
(44353,  'Strassen_Salz',             'Dhrovjan',   5200,  'Ton', 265,  'EU'),
(587564, 'Gemahlenes_Steinsalz',      'Dhrovjan',   18900, 'Ton', 192,  'EU'),
(456124, 'Hochwertiges_Korallensalz', 'Albsale',    150,   'Ton', 1270, 'EU')
ON DUPLICATE KEY UPDATE stock = VALUES(stock);

-- Shënim: krijoji përdoruesit me kredenciale VETËM përmes endpoint-it
-- api/user/register.php (password_hash). Mos fut fjalëkalime direkt në SQL.
