# 🚀 Deployment Guide

This guide covers various deployment options for the AI Agent System, from development to production environments.

## 📋 Prerequisites

### System Requirements
- **PHP**: 8.2 or higher
- **Node.js**: 18.0 or higher
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Cache**: Redis 6.0+
- **Web Server**: Nginx or Apache
- **Memory**: 2GB RAM minimum (4GB+ recommended)
- **Storage**: 10GB minimum (depends on knowledge base size)

### Required Extensions
```bash
# PHP Extensions
php-fpm
php-mysql (or php-pgsql)
php-redis
php-mbstring
php-xml
php-curl
php-zip
php-gd
php-intl
php-bcmath
```

## 🐳 Docker Deployment (Recommended)

### Quick Start with Docker Compose

1. **Clone and setup**
```bash
git clone https://github.com/aiens/aiagentsys9.git
cd aiagentsys9
cp .env.example .env
```

2. **Configure environment**
```bash
# Edit .env file with your settings
nano .env
```

3. **Build and run**
```bash
docker-compose up -d
```

4. **Initialize application**
```bash
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

### Docker Compose Configuration

Create `docker-compose.yml`:
```yaml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "8000:8000"
    environment:
      - APP_ENV=production
      - DB_HOST=mysql
      - REDIS_HOST=redis
    volumes:
      - ./storage:/var/www/html/storage
      - ./bootstrap/cache:/var/www/html/bootstrap/cache
    depends_on:
      - mysql
      - redis

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: ai_agent_system
      MYSQL_USER: laravel
      MYSQL_PASSWORD: secret
    volumes:
      - mysql_data:/var/lib/mysql
    ports:
      - "3306:3306"

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
      - ./ssl:/etc/nginx/ssl
    depends_on:
      - app

volumes:
  mysql_data:
  redis_data:
```

### Dockerfile

Create `Dockerfile`:
```dockerfile
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install dependencies
RUN composer install --optimize-autoloader --no-dev
RUN npm install && npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html/storage

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=8000
```

## 🖥️ Traditional Server Deployment

### Ubuntu/Debian Setup

1. **Install dependencies**
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install php8.2 php8.2-fpm php8.2-mysql php8.2-redis php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-intl php8.2-bcmath

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install nodejs

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install MySQL
sudo apt install mysql-server

# Install Redis
sudo apt install redis-server

# Install Nginx
sudo apt install nginx
```

2. **Configure MySQL**
```bash
sudo mysql_secure_installation
sudo mysql -u root -p

CREATE DATABASE ai_agent_system;
CREATE USER 'laravel'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON ai_agent_system.* TO 'laravel'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

3. **Deploy application**
```bash
# Clone repository
cd /var/www
sudo git clone https://github.com/aiens/aiagentsys9.git
cd aiagentsys9

# Install dependencies
sudo composer install --optimize-autoloader --no-dev
sudo npm install && npm run build

# Set permissions
sudo chown -R www-data:www-data /var/www/aiagentsys9
sudo chmod -R 755 /var/www/aiagentsys9/storage
sudo chmod -R 755 /var/www/aiagentsys9/bootstrap/cache

# Configure environment
sudo cp .env.example .env
sudo nano .env

# Generate key and migrate
sudo php artisan key:generate
sudo php artisan migrate
sudo php artisan db:seed
```

4. **Configure Nginx**

Create `/etc/nginx/sites-available/aiagentsys9`:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/aiagentsys9/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

5. **Enable site and restart services**
```bash
sudo ln -s /etc/nginx/sites-available/aiagentsys9 /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
```

## ☁️ Cloud Deployment

### AWS Deployment

#### Using AWS Elastic Beanstalk

1. **Install EB CLI**
```bash
pip install awsebcli
```

2. **Initialize and deploy**
```bash
eb init
eb create production
eb deploy
```

3. **Configure environment variables**
```bash
eb setenv APP_ENV=production DB_HOST=your-rds-endpoint REDIS_HOST=your-elasticache-endpoint
```

#### Using AWS ECS with Fargate

1. **Build and push Docker image**
```bash
aws ecr get-login-password --region us-east-1 | docker login --username AWS --password-stdin your-account.dkr.ecr.us-east-1.amazonaws.com
docker build -t ai-agent-system .
docker tag ai-agent-system:latest your-account.dkr.ecr.us-east-1.amazonaws.com/ai-agent-system:latest
docker push your-account.dkr.ecr.us-east-1.amazonaws.com/ai-agent-system:latest
```

2. **Create ECS task definition and service**
```json
{
  "family": "ai-agent-system",
  "networkMode": "awsvpc",
  "requiresCompatibilities": ["FARGATE"],
  "cpu": "1024",
  "memory": "2048",
  "executionRoleArn": "arn:aws:iam::account:role/ecsTaskExecutionRole",
  "containerDefinitions": [
    {
      "name": "ai-agent-system",
      "image": "your-account.dkr.ecr.us-east-1.amazonaws.com/ai-agent-system:latest",
      "portMappings": [
        {
          "containerPort": 8000,
          "protocol": "tcp"
        }
      ],
      "environment": [
        {
          "name": "APP_ENV",
          "value": "production"
        }
      ]
    }
  ]
}
```

### Google Cloud Platform

#### Using Google Cloud Run

1. **Build and deploy**
```bash
gcloud builds submit --tag gcr.io/your-project/ai-agent-system
gcloud run deploy --image gcr.io/your-project/ai-agent-system --platform managed
```

2. **Set environment variables**
```bash
gcloud run services update ai-agent-system --set-env-vars APP_ENV=production,DB_HOST=your-cloud-sql-ip
```

### DigitalOcean App Platform

1. **Create app spec**
```yaml
name: ai-agent-system
services:
- name: web
  source_dir: /
  github:
    repo: your-username/aiagentsys9
    branch: main
  run_command: php artisan serve --host=0.0.0.0 --port=8080
  environment_slug: php
  instance_count: 1
  instance_size_slug: basic-xxs
  envs:
  - key: APP_ENV
    value: production
  - key: APP_KEY
    value: your-app-key
databases:
- name: db
  engine: PG
  version: "13"
```

## 🔧 Production Optimizations

### Performance Tuning

1. **PHP Optimizations**
```bash
# Enable OPcache
echo "opcache.enable=1" >> /etc/php/8.2/fpm/php.ini
echo "opcache.memory_consumption=256" >> /etc/php/8.2/fpm/php.ini
echo "opcache.max_accelerated_files=20000" >> /etc/php/8.2/fpm/php.ini

# Increase memory limits
echo "memory_limit=512M" >> /etc/php/8.2/fpm/php.ini
echo "max_execution_time=300" >> /etc/php/8.2/fpm/php.ini
```

2. **Laravel Optimizations**
```bash
# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

3. **Database Optimizations**
```sql
-- MySQL optimizations
SET GLOBAL innodb_buffer_pool_size = 1073741824; -- 1GB
SET GLOBAL query_cache_size = 268435456; -- 256MB
SET GLOBAL max_connections = 200;
```

### Security Hardening

1. **SSL/TLS Configuration**
```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Get SSL certificate
sudo certbot --nginx -d your-domain.com
```

2. **Firewall Setup**
```bash
# Configure UFW
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

3. **Application Security**
```bash
# Set secure permissions
sudo chmod 644 .env
sudo chmod -R 755 storage
sudo chmod -R 755 bootstrap/cache

# Disable debug mode
echo "APP_DEBUG=false" >> .env
```

### Monitoring and Logging

1. **Application Monitoring**
```bash
# Install Supervisor for queue workers
sudo apt install supervisor

# Create supervisor config
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

2. **Log Management**
```bash
# Configure log rotation
sudo nano /etc/logrotate.d/laravel
```

3. **Health Checks**
```bash
# Create health check endpoint
php artisan make:controller HealthController
```

## 🔄 CI/CD Pipeline

### GitHub Actions

Create `.github/workflows/deploy.yml`:
```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        
    - name: Install dependencies
      run: composer install --no-dev --optimize-autoloader
      
    - name: Run tests
      run: php artisan test
      
    - name: Deploy to server
      uses: appleboy/ssh-action@v0.1.5
      with:
        host: ${{ secrets.HOST }}
        username: ${{ secrets.USERNAME }}
        key: ${{ secrets.KEY }}
        script: |
          cd /var/www/aiagentsys9
          git pull origin main
          composer install --no-dev --optimize-autoloader
          npm install && npm run build
          php artisan migrate --force
          php artisan config:cache
          php artisan route:cache
          php artisan view:cache
          sudo systemctl reload php8.2-fpm
```

## 🆘 Troubleshooting

### Common Issues

1. **Permission Errors**
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 755 storage bootstrap/cache
```

2. **Database Connection Issues**
```bash
# Check MySQL status
sudo systemctl status mysql

# Test connection
php artisan tinker
DB::connection()->getPdo();
```

3. **Redis Connection Issues**
```bash
# Check Redis status
sudo systemctl status redis

# Test connection
redis-cli ping
```

4. **Queue Worker Issues**
```bash
# Restart queue workers
php artisan queue:restart

# Check failed jobs
php artisan queue:failed
```

### Performance Issues

1. **Slow Database Queries**
```bash
# Enable query logging
php artisan db:monitor

# Analyze slow queries
SHOW PROCESSLIST;
```

2. **High Memory Usage**
```bash
# Monitor memory usage
free -h
top -p $(pgrep php-fpm)
```

3. **Cache Issues**
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

For additional support, please refer to the [main documentation](README_EN.md) or open an issue on GitHub.
