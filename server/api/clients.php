<?php
require_once __DIR__ . '/common.php';
require_once dirname(__DIR__) . '/dbConfig.php';
require_once dirname(__DIR__) . '/functions.php';

apiAuth();

try {
    $clients = get_client();
    $result = [];

    foreach ((array)$clients as $client) {
        $result[] = [
            'name' => (string)($client['name'] ?? ''),
            'telefon' => (string)($client['telefon'] ?? ''),
            'password' => (string)($client['password'] ?? '')
        ];
    }

    apiJson([
        'success' => true,
        'clients' => $result
    ]);
} catch (Throwable $e) {
    apiJson([
        'success' => false,
        'message' => 'Не удалось получить клиентов: ' . $e->getMessage()
    ], 500);
}
