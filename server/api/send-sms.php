<?php
require_once __DIR__ . '/common.php';
apiAuth();

const SMS_ST906_ADB = 'C:\\platform-tools\\adb.exe';
const SMS_ST906_PACKAGE = 'com.st906.sms';
const SMS_ST906_SERIAL = '';

function st906RunCommand(array $args): array
{
    if (!is_file(SMS_ST906_ADB)) {
        throw new RuntimeException('adb.exe не найден: ' . SMS_ST906_ADB);
    }

    $command = '"' . SMS_ST906_ADB . '"';
    foreach ($args as $arg) {
        $command .= ' ' . escapeshellarg((string)$arg);
    }
    $command .= ' 2>&1';

    $output = [];
    $exitCode = -1;
    exec($command, $output, $exitCode);

    return ['exit_code' => $exitCode, 'output' => implode(PHP_EOL, $output)];
}

function st906DetectSerial(): string
{
    if (SMS_ST906_SERIAL !== '') return SMS_ST906_SERIAL;

    st906RunCommand(['start-server']);
    $result = st906RunCommand(['devices']);

    if ($result['exit_code'] !== 0) {
        throw new RuntimeException('Не удалось выполнить adb devices: ' . $result['output']);
    }

    foreach (preg_split('/\R/', trim($result['output'])) as $line) {
        $parts = preg_split('/\s+/', trim($line));
        if (count($parts) >= 2 && $parts[1] === 'device') return $parts[0];
    }

    throw new RuntimeException('ST-906 не найден через ADB.');
}

function sendSmsViaModem(string $phone, string $message): void
{
    $phone = normalizePhone($phone);
    $message = trim($message);

    if ($phone === '') throw new RuntimeException('Номер получателя не указан.');
    if ($message === '') throw new RuntimeException('Текст SMS пустой.');

    if (!preg_match('/^\+\d{10,15}$/', $phone) &&
        !preg_match('/^\d{10,15}$/', $phone)) {
        throw new RuntimeException('Проверьте номер получателя.');
    }

    st906RunCommand(['start-server']);
    $serial = st906DetectSerial();

    $package = st906RunCommand([
        '-s', $serial, 'shell', 'pm', 'list', 'packages'
    ]);

    if ($package['exit_code'] !== 0 ||
        strpos($package['output'], SMS_ST906_PACKAGE) === false) {
        throw new RuntimeException(
            'На ST-906 не найдено приложение ' . SMS_ST906_PACKAGE
        );
    }

    $numberB64 = rtrim(strtr(base64_encode($phone), '+/', '-_'), '=');
    $textB64 = rtrim(strtr(base64_encode($message), '+/', '-_'), '=');

    $broadcast = st906RunCommand([
        '-s', $serial,
        'shell', 'am', 'broadcast',
        '-n', SMS_ST906_PACKAGE . '/.SmsReceiver',
        '-a', SMS_ST906_PACKAGE . '.SEND',
        '--es', 'number_b64', $numberB64,
        '--es', 'text_b64', $textB64
    ]);

    if ($broadcast['exit_code'] !== 0 ||
        stripos($broadcast['output'], 'Broadcast completed') === false) {
        throw new RuntimeException(
            'ST-906 не подтвердил отправку SMS. ' . $broadcast['output']
        );
    }
}

$data = requestData();
$phone = (string)($data['phone'] ?? $data['telefon'] ?? '');
$message = (string)($data['message'] ?? '');

try {
    sendSmsViaModem($phone, $message);
    apiJson(['success'=>true,'message'=>'✓ SMS успешно отправлено.']);
} catch (Throwable $e) {
    apiJson(['success'=>false,'message'=>'SMS не отправлено: '.$e->getMessage()],500);
}
