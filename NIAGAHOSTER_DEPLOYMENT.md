# Laravel Deployment Checklist for Niagahoster

## 1. Update your .env file for production
- Set APP_ENV=production
- Set APP_DEBUG=false
- Update APP_URL to your domain
- Configure your database settings

## 2. Optimize your application
```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 3. Upload your files
- Upload all files to your hosting account using FTP or SSH

## 4. Set proper permissions
- Run the set-permissions.sh script or manually set permissions
- Make sure storage and bootstrap/cache directories are writable

## 5. Configure your domain
- Point your domain to the public directory if possible
- If not possible, use the root .htaccess and index.php redirects

## 6. Run migrations (if needed)
```bash
php artisan migrate --force
```

## 7. Verify installation
- Check your site is working correctly
- Check error logs if you encounter issues
