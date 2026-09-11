-- =====================================================================
-- Albsale Vlora — Custom ERP · Krijimi i databazës + përdoruesit të dedikuar
-- ---------------------------------------------------------------------
-- Ekzekuto NJË HERË si root (jep DDL + krijon user-in e aplikacionit):
--   "C:\xampp\mysql\bin\mysql.exe" -u root -p < 00_database_and_user.sql
-- (ose ngjite gjithë përmbajtjen te phpMyAdmin → SQL)
--
-- Përdoruesi i aplikacionit ka VETËM DML (SELECT/INSERT/UPDATE/DELETE) — jo DDL,
-- jo root. Fjalëkalimi këtu DUHET të përputhet me DB_PASS te erp/.env.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `albsale-vlora`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Nëse ekziston nga një provë e mëparshme, rikrijoje me të njëjtin fjalëkalim.
CREATE USER IF NOT EXISTS 'albsale_app'@'localhost'
  IDENTIFIED BY 'Albsale-xxxxxxxxxxxxxxxxxx';
ALTER USER 'albsale_app'@'localhost'
  IDENTIFIED BY 'Albsale-xxxxxxxxxxxxxxxxxx';

GRANT SELECT, INSERT, UPDATE, DELETE ON `albsale-vlora`.*
  TO 'albsale_app'@'localhost';

FLUSH PRIVILEGES;
