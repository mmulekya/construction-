<?php
// Database & general config
define("DB_HOST", "localhost");
define("DB_USER", "your_db_user");
define("DB_PASS", "your_db_pass");
define("DB_NAME", "your_db_name");

// Load environment variables (OpenAI key)
if(file_exists(__DIR__ . '/../.env')){
    $env = parse_ini_file(__DIR__ . '/../.env');
    define("OPENAI_API_KEY", $env['OPENAI_API_KEY']);
} else {
    define("OPENAI_API_KEY", "");
}