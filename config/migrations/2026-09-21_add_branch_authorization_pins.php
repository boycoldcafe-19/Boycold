<?php
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../branch_authorization_pin.php';

boycold_ensure_branch_authorization_pin_schema($connect);
echo "Branch authorization PIN migration completed.\n";
