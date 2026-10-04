<?php
// PHP built-in server ignores .htaccess. This router supplies equivalent guards.
// Run with: php -S 127.0.0.1:8080 -t . tools/dev-router.php
$path=rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH) ?: '/');
if (str_contains($path,'..') || str_contains($path,'\\') || preg_match('#^/(includes|tools|storage|reportfiles|database|docs|scss)(/|$)#i',$path)
    || preg_match('#(^|/)\\.|\\.(sql|log|zip)$#i',$path)) {
    http_response_code(404); header('Content-Type: text/plain'); echo 'Not found'; return true;
}
return false;
