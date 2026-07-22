<?php

declare(strict_types=1);

$data['my_text'] = strtoupper((string)($data['my_text'] ?? ''));
$data['headless_processed'] = true;

return $data;
