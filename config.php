<?php
// config.php
define('UPLOAD_DIR', 'uploads/'); // Folder where uploaded files are saved
define('PROCESSED_FILES_DIR', 'processed_files/'); // Folder where processed files are stored
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // Max file size (10MB)
define('ALLOWED_EXTENSIONS', ['xlsx']); // Allowed file types
define('AMAZON_API_URL', 'https://www.amazon.de/acp/buffet-mobile-card/buffet-mobile-card-3e67eb5a-92a5-4eae-9a4d-c1d3082690fb-1734571386882/getRspManufacturerContent?page-type=DetailAW&stamp=1734623286402'); // Amazon API endpoint (example)
