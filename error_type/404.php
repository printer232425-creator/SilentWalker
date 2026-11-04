<?php
// errors/404.php — text-only fallback, HTTP 404 status
http_response_code(404);
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store, max-age=0');

echo "Page not found\n";