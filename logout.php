<?php

require_once dirname(__FILE__) . '/includes/auth.php';

logout_user();

header('Location: /login.php');
exit;
