<?php
require_once __DIR__ . '/common.php';
apiAuth();

apiJson([
    'success' => true,
    'app' => 'ArtForDogs',
    'service' => 'SMS Manager API',
    'version' => '1.0'
]);
