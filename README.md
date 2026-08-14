# Remember to do when pulling (not first time)
```powershell
cd .\smart-library-system
composer install
npm ci --include=optional
php artisan migrate
composer run dev
```


# Smart Library System — Setup with GitHub Desktop

# Preparing same environment
## 1. Install PHP

应该都有php了吧。。

## 2. Configure `php.ini`

```powershell
Copy-Item C:\php\php.ini-development C:\php\php.ini
```

Open:

```text
C:\php\php.ini
```

Keep:

```ini
extension_dir = "ext"
```

Enable the required extensions by removing the semicolon `;`:

```ini
extension=curl
extension=fileinfo
extension=mbstring
extension=openssl
extension=pdo_sqlite
extension=sqlite3
extension=zip
```

Save the file and open a new terminal.

Verify:

```powershell
php -m | findstr /I "curl fileinfo mbstring openssl pdo_sqlite sqlite3 zip"
```

## 3. Install Composer

Install Composer using the Windows installer.
[link to composer](https://getcomposer.org/Composer-Setup.exe)

When asked for the PHP executable, select:

```text
C:\php\php.exe
```

Close and reopen the terminal, then verify:

```powershell
composer -V
where.exe composer
```

## 4. Install Node.js

Install the Windows x64 version of:
[link to node](https://nodejs.org/dist/v24.18.0/node-v24.18.0-x64.msi)

```text
Node.js 24 LTS
```

Keep these options enabled:

```text
Node.js runtime
npm package manager
Add to PATH
```

Close and reopen the terminal, then verify:

```powershell
node -v
npm -v
where.exe node
```

## 5. Complete the Local Laravel Setup

Open a terminal inside the cloned project folder.

The terminal path should end with:

```text
C:\Projects\smart-library-system
```

## 6. Install PHP dependencies

Run:

```powershell
composer install
```

Use `composer install`, not `composer update`, because `composer install` uses the package versions recorded
 in:

```text
composer.lock
```

## 7. Install frontend dependencies

When `package-lock.json` exists, run:

```powershell
npm ci --include=optional
```

If there is no `package-lock.json`, run:

```powershell
npm install --include=optional
```

Build the frontend:

```powershell
npm run build
```

## 8. Create the local `.env`

Create `.env` from the safe template:

```powershell
Copy-Item .env.example .env
```

Open `.env` and confirm:

```env
APP_NAME="Smart Library System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=sqlite
```

Do not place passwords or secret keys inside `.env.example`.

Generate the local application key:

```powershell
php artisan key:generate
```

Each teammate may have a different `APP_KEY` in their local `.env`.

## 9. Create the local SQLite database

Run:

```powershell
if (!(Test-Path .\database\database.sqlite)) {
    New-Item .\database\database.sqlite -ItemType File
}
```

Verify:

```powershell
Test-Path .\database\database.sqlite
```

Expected result:

```text
True
```

The SQLite file is local and should not appear in GitHub Desktop.

## 10. Clear cached configuration

```powershell
php artisan optimize:clear
```

## 11. Run migrations

```powershell
php artisan migrate
```

The following output is normal when every migration has already run:

```text
INFO  Nothing to migrate.
```

Check the migration status:

```powershell
php artisan migrate:status
```

## 12. Start the project

```powershell
composer run dev
```

Open:

```text
http://127.0.0.1:8000
```

Stop the project with:

```text
Ctrl + C
```

---

# IMPORTANT
put .gitignore file in BMIT3173_SMARTLIBRARYSYSTEM
BMIT3173_SMARTLIBRARYSYSTEM
  -- smart-library-system
  -- .git
  -- README.md
  -- .gitignore
