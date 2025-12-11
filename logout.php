<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

auth_logout();
header('Location: /FozGym/');
exit;