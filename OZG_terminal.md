# Terminal 1
cd .\smart-library-system
php artisan serve

# Terminal 2
cd .\smart-library-system
dir package.json
npm run dev

# Terminal 3
cd .\smart-library-system
php artisan migrate:rollback --step=1
php artisan migrate
php artisan migrate:status