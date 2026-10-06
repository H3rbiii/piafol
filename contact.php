<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html#DlaFirm', true, 303);
    exit;
}

$configFile = getenv('CONTACT_CONFIG_PATH') ?: dirname(__DIR__) . '/private/piafol-contact-config.php';

if (!is_file($configFile)) {
    http_response_code(400);
    exit('Formularz jest chwilowo niedostepny.');
}

$config = require $configFile;

if (!is_array($config) || array_diff(['smtp_host', 'smtp_port', 'smtp_user', 'smtp_password', 'from_email', 'to_email'], array_keys($config))) {
    http_response_code(500);
    exit('Formularz jest chwilowo niedostepny.');
}

if (!empty($_POST['website'])) {
    header('Location: index.html?sent=1#DlaFirm');
    exit;
}

$email = filter_var(trim($_POST['E-mail'] ?? ''), FILTER_VALIDATE_EMAIL);
$company = trim($_POST['Firma'] ?? '');
$product = trim($_POST['Produkt'] ?? '');
$dimensions = trim($_POST['Rozmiar lub wymiary'] ?? '');
$quantity = trim($_POST['Ilość'] ?? '');
$message = trim($_POST['Wiadomość'] ?? '');

if (!$email || $product === '' || $message === '') {
    http_response_code(422);
    exit('Uzupelnij wymagane pola formularza.');
}

$subject = 'Zapytanie ze strony Piafol: ' . $product;
$body = implode("\r\n", [
    'Nowe zapytanie ze strony piafol.pl',
    '',
    'Firma: ' . ($company !== '' ? $company : 'Nie podano'),
    'E-mail: ' . $email,
    'Produkt: ' . $product,
    'Rozmiar lub wymiary: ' . ($dimensions !== '' ? $dimensions : 'Nie podano'),
    'Potrzebna ilosc: ' . ($quantity !== '' ? $quantity : 'Nie podano'),
    '',
    'Wiadomosc:',
    $message,
]);

$success = sendSmtpMessage($config, $subject, $body, $email);

if (!$success) {
    $success = sendNativeMail($config, $subject, $body, $email);
}

if (!$success) {
    http_response_code(500);
    exit('Nie udalo sie wyslac wiadomosci. Sprobuj ponownie pozniej.');
}

header('Location: index.html?sent=1#DlaFirm');
exit;

function sendSmtpMessage(array $config, string $subject, string $body, string $replyTo): bool
{
    $socket = @fsockopen('ssl://' . $config['smtp_host'], (int) $config['smtp_port'], $errorCode, $errorMessage, 15);
    if (!$socket || !expectSmtp($socket, 220)) {
        return false;
    }

    if (!smtpCommand($socket, 'EHLO piafol.pl', 250)) return false;
    if (!smtpCommand($socket, 'AUTH LOGIN', 334)) return false;
    if (!smtpCommand($socket, base64_encode($config['smtp_user']), 334)) return false;
    if (!smtpCommand($socket, base64_encode($config['smtp_password']), 235)) return false;
    if (!smtpCommand($socket, 'MAIL FROM:<' . $config['from_email'] . '>', 250)) return false;
    if (!smtpCommand($socket, 'RCPT TO:<' . $config['to_email'] . '>', 250)) return false;
    if (!smtpCommand($socket, 'DATA', 354)) return false;

    $headers = [
        'From: Piafol <' . $config['from_email'] . '>',
        'To: ' . $config['to_email'],
        'Reply-To: ' . $replyTo,
        'Subject: ' . encodeHeader($subject),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];
    fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n" . dotStuff($body) . "\r\n.\r\n");
    $sent = expectSmtp($socket, 250);
    fwrite($socket, "QUIT\r\n");
    fclose($socket);

    return $sent;
}

function sendNativeMail(array $config, string $subject, string $body, string $replyTo): bool
{
    $headers = [
        'From: Piafol <' . $config['from_email'] . '>',
        'Reply-To: ' . $replyTo,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];

    return mail(
        $config['to_email'],
        encodeHeader($subject),
        $body,
        implode("\r\n", $headers),
        '-f' . $config['from_email']
    );
}

function smtpCommand($socket, string $command, int $expectedCode): bool
{
    fwrite($socket, $command . "\r\n");
    return expectSmtp($socket, $expectedCode);
}

function expectSmtp($socket, int $expectedCode): bool
{
    $response = fgets($socket, 512);
    return $response !== false && (int) substr($response, 0, 3) === $expectedCode;
}

function encodeHeader(string $value): string
{
    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function dotStuff(string $value): string
{
    return preg_replace('/^\./m', '..', $value);
}
