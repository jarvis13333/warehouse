<?php
session_start();
require_once dirname(__DIR__) . '/lib/auth.php';
require_once dirname(__DIR__) . '/lib/utils.php';
auth_logout();
redirect('/login.php');


