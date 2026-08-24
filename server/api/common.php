<?php
declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-ArtForDogs-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/*
 * ArtForDogs API
 * Положи этот файл и остальные API-файлы в:
 * C:\OpenServer\domains\artfordogs2\api\
 *
 * Если захочешь закрыть API ключом, впиши ключ ниже и такой же ключ
 * укажи в iOS-приложении.
 */
const ARTFORDOGS_API_KEY = '';

function apiAuth(): void
{
    if (ARTFORDOGS_API_KEY === '') {
        return;
    }

    $key = (string)($_SERVER['HTTP_X_ARTFORDOGS_KEY'] ?? '');
    if (!hash_equals(ARTFORDOGS_API_KEY, $key)) {
        apiJson(['success' => false, 'message' => 'Неверный API ключ.'], 401);
    }
}

function apiJson(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function requestData(): array
{
    $contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));

    if (strpos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '{}', true);
        return is_array($data) ? $data : [];
    }

    return $_POST;
}

function normalizePhone(string $phone): string
{
    $phone = trim($phone);
    $phone = preg_replace('/[^\d+]/u', '', $phone) ?? '';

    if (preg_match('/^8\d{10}$/', $phone)) {
        $phone = '+7' . substr($phone, 1);
    }

    return $phone;
}
