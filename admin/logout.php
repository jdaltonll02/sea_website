<?php
require_once __DIR__ . '/../public/includes/helpers.php';
require_once __DIR__ . '/../public/includes/auth.php';

logout();
redirect('/admin/login.php');
