# Albsale Vlora — Custom ERP (i rinovuar)

Kanali **B2B/B2C** i _Digital Enterprise Landscape_. Ky modul është versioni i
forcuar dhe i ristrukturuar i projektit të vjetër PHP/MySQL (`htdocs/salt`),
i përgatitur për integrim **Order-to-Cash** me SAP S/4HANA përmes SAP Integration Suite.

## Struktura
```
erp/
├── public/                 # I VETMI web root i ekspozuar
│   ├── index.php
│   ├── myorders.php        # Kundenportal (O2C, self-service)
│   ├── assets/{css,img}/
│   └── api/
│       ├── article/  · order/  · user/            # CRUD REST + login/register/logout
│       └── integration/{send_order,receive_event} # ura me SAP CI
├── src/
│   ├── bootstrap.php       # ngarkon .env, gabimet, seksionin, helper-at
│   ├── Config/{env,Database,integration}.php
│   ├── Models/{Article,Order,User}.php
│   ├── Security/auth.php
│   └── Lib/canonical.php   # XML kanonik O2C -> SAP CI
├── sql/{01_schema,02_integration}.sql
├── .env.example  ·  .gitignore  ·  composer.json
```

## Nisja (lokale, XAMPP)
1. `cp .env.example .env` dhe plotëso vlerat (DB, CPI, tokenet).
2. Krijo bazën dhe ngarko skemat:
   ```sql
   SOURCE sql/01_schema.sql;
   SOURCE sql/02_integration.sql;
   ```
3. Konfiguro web-server-in që `DocumentRoot` të jetë dosja `public/`.
4. Regjistro një përdorues: `POST public/api/user/register.php` `{ "username","password",... }`.
