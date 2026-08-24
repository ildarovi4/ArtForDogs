<?php
require_once __DIR__ . '/common.php';
apiAuth();

$data = requestData();

$name = trim((string)($data['name'] ?? ''));
$phone = trim((string)($data['telefon'] ?? $data['phone'] ?? ''));
$password = trim((string)($data['password'] ?? ''));
$option = filter_var($data['option'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
$option = $option === null ? true : $option;
$rezervDate = trim((string)($data['rezervDate'] ?? $data['date'] ?? ''));

if ($name === '') apiJson(['success'=>false,'message'=>'Не выбран клиент.'],400);
if ($rezervDate === '') apiJson(['success'=>false,'message'=>'Не указана дата и время.'],400);
if ($password === '') apiJson(['success'=>false,'message'=>'Не указан пароль.'],400);

$timestamp = strtotime($rezervDate);
if ($timestamp === false) apiJson(['success'=>false,'message'=>'Некорректная дата и время.'],400);

$hour = (int)date('H', $timestamp);
if ($hour >= 4) $hello = 'Доброе утро';
if ($hour >= 10) $hello = 'Добрый день';
if ($hour >= 16) $hello = 'Добрый вечер';
if ($hour >= 22 || $hour < 4) $hello = 'Доброй ночи';

$displayName = ucfirst($name);
$date = date('d.m.Y', $timestamp);
$time = date('H:i', $timestamp);
$timeFrom = date('H:i', $timestamp - 900);
$timeTo = date('H:i', $timestamp + 3600);

$body = "$hello, $displayName!\n\n"
      . "Вы записались на посещение зала на $date в $time\n"
      . "Пароль для входа в зал:\n{$password}#\n"
      . "Будет действовать $date с $timeFrom до $timeTo\n\n"
      . "Арендованный зал находиться по адресу:\n"
      . "г. Уфа ул. Пархоменко 106 вход со двора, слева от 1 подъезда серая дверь на ней кодовый замок.";

if ($option) {
    $body .= "\n\nЕсли вас не затруднит оставьте пожалуйста отзыв на:\nhttps://clck.ru/3QwWyG";
}

apiJson([
    'success' => true,
    'message' => $body,
    'phone' => normalizePhone($phone),
    'name' => $name,
    'date' => $date,
    'time' => $time
]);
