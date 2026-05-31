<?php
 
declare(strict_types=1);
 
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';
 
session_destroy();
redirect(pageUrl('login.php'));
 
