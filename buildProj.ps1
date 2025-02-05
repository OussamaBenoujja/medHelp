# Define the base project path
$projectPath = "C:\Users\Youcode\Desktop\medHelp"

# Define folder structure
$folders = @(
    "app",
    "app\Config",
    "app\Controllers",
    "app\Models",
    "app\Repositories",
    "app\Services",
    "app\Views",
    "app\Core",
    "public",
    "public\assets",
    "migrations",
    "docker",
    "docker\php",
    "docker\postgres"
)

# Create folders
foreach ($folder in $folders) {
    $fullPath = Join-Path $projectPath $folder
    if (!(Test-Path $fullPath)) {
        New-Item -Path $fullPath -ItemType Directory | Out-Null
    }
}

# Define files and initial content
$files = @{
    "docker-compose.yml" = @"
version: '3.8'
services:
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    ports:
      - '8080:80'
    volumes:
      - .:/var/www/html
  db:
    image: postgres:latest
    environment:
      POSTGRES_USER: admin
      POSTGRES_PASSWORD: admin
      POSTGRES_DB: medical_db
    ports:
      - '5432:5432'
"@
    ".htaccess" = @"
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
"@
    "app\Config\config.php" = @"
<?php
return [
    'db_host' => 'db',
    'db_name' => 'medical_db',
    'db_user' => 'admin',
    'db_password' => 'admin'
];
"@
    "app\Core\Router.php" = @"
<?php
class Router {
    public function route() {
        echo 'Router initialized';
    }
}
"@
    "docker\php\Dockerfile" = @"
FROM php:8.1-apache
RUN docker-php-ext-install pdo pdo_pgsql
COPY . /var/www/html
"@
    "docker\postgres\init.sql" = @"
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE NOT NULL
);
"@
}

# Create files with initial content
foreach ($file in $files.Keys) {
    $filePath = Join-Path $projectPath $file
    $directory = Split-Path $filePath

    # Ensure the directory exists
    if (!(Test-Path $directory)) {
        New-Item -Path $directory -ItemType Directory | Out-Null
    }

    # Create the file and add content
    Set-Content -Path $filePath -Value $files[$file]
}

Write-Host "Project structure and files created successfully at $projectPath"
