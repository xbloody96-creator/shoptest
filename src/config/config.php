<?php
return [
    'db_host' => getenv('DB_HOST') ?: 'localhost',
    'db_name' => getenv('DB_NAME') ?: 'gamestore_db',
    'db_user' => getenv('DB_USER') ?: 'root',
    'db_pass' => getenv('DB_PASS') ?: '',
    'db_charset' => 'utf8mb4',
    
    'app_name' => 'GameStore',
    'app_url' => 'http://localhost',
    'admin_email' => 'admin@gamestore.local',
    
    'upload_dir' => __DIR__ . '/../public/assets/images/uploads/',
    'max_upload_size' => 5 * 1024 * 1024, // 5MB
    
    'items_per_page' => 12,
    'session_lifetime' => 3600 * 24, // 24 часа
];
