# Terminal 1
cd .\smart-library-system
php artisan serve --host=127.0.0.1 --port=8000
Build:
npm run build
VS Code:
PORTS → Forwarded Port 8000 → Public → Copy Forwarded Address

# Terminal 2
cd .\smart-library-system
dir package.json
npm run dev

# Terminal 3
cd .\smart-library-system
php artisan migrate:rollback --step=1
php artisan migrate
php artisan migrate:status