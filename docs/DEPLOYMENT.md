# Deployment Guide

## Prerequisites

- PHP 7.4+
- MySQL 5.7+
- Apache 2.4+
- SSL Certificate
- Composer (optional)

## Production Server Setup

1. **Server Configuration**

```apache
# Apache virtual host configuration
<VirtualHost *:80>
    ServerName carwash.example.com
    DocumentRoot /var/www/carwash

    <Directory /var/www/carwash>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/carwash_error.log
    CustomLog ${APACHE_LOG_DIR}/carwash_access.log combined
</Directory>
</VirtualHost>
```

2. **Database Setup**

```sql
CREATE DATABASE carwash_prod;
CREATE USER 'carwash_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON carwash_prod.* TO 'carwash_user'@'localhost';
FLUSH PRIVILEGES;
```

3. **Environment Configuration**

```env
# Production .env
APP_ENV=production
APP_DEBUG=false
DB_HOST=localhost
DB_NAME=carwash_prod
DB_USER=carwash_user
DB_PASS=strong_password
```

4. **Security Checklist**

- [ ] Enable HTTPS
- [ ] Set secure file permissions
- [ ] Configure PHP settings
- [ ] Enable error logging
- [ ] Disable development features

## Deployment Steps

1. **Code Deployment**

```bash
# On production server
cd /var/www
git clone https://github.com/your-repo/carwash_project.git carwash
cd carwash
```

2. **File Permissions**

```bash
chown -R www-data:www-data /var/www/carwash
chmod -R 755 /var/www/carwash
chmod -R 777 /var/www/carwash/uploads
```

3. **Database Migration**

```bash
mysql -u root -p carwash_prod < database/schema.sql
mysql -u root -p carwash_prod < database/initial_data.sql
```
