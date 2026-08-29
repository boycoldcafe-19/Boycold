<?php
require_once dirname(__DIR__) . '/db_config.php';
require_once dirname(__DIR__) . '/payments.php';

boycold_ensure_payment_schema($connect);
echo "Payment schema is ready.\n";
