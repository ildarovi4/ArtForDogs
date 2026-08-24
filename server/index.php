<?php
session_start(); 
include_once 'dbConfig.php';

include_once 'functions.php';

/* ST-906: автоматическая отправка SMS через Android-модем по ADB. */
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
    $phone = trim($phone);
    $message = trim($message);

    if ($phone === '') throw new RuntimeException('Номер получателя не указан.');
    if ($message === '') throw new RuntimeException('Текст SMS пустой.');

    $normalizedPhone = preg_replace('/[^\d+]/u', '', $phone);
    if ($normalizedPhone === null || $normalizedPhone === '') {
        throw new RuntimeException('Номер получателя указан некорректно.');
    }

    if (preg_match('/^8\d{10}$/', $normalizedPhone)) {
        $normalizedPhone = '+7' . substr($normalizedPhone, 1);
    }

    if (!preg_match('/^\+\d{10,15}$/', $normalizedPhone) &&
        !preg_match('/^\d{10,15}$/', $normalizedPhone)) {
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

    $numberB64 = rtrim(strtr(base64_encode($normalizedPhone), '+/', '-_'), '=');
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

/* AJAX-запрос от кнопки «Отправить в SMS через Модем». */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_modem_sms') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $message = urldecode((string)($_POST['message'] ?? ''));
        sendSmsViaModem((string)($_POST['phone'] ?? ''), $message);

        echo json_encode([
            'success' => true,
            'message' => '✓ SMS успешно отправлено.'
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'SMS не отправлено: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

//include_once 'dikidi-to-bd.php';
//print_r($_POST);
$time = strtotime($_POST[rezervDate]);





//print($time);
//print_r($_POST); 


$morning = "Доброе утро";
$day = "Добрый день";
$evening = "Добрый вечер";
$night = "Доброй ночи";
$minyt = date("i");
$chasov = date("H");
if($chasov >= 04) {$hello = $morning;}
if($chasov >= 10) {$hello = $day;}
if($chasov >= 16) {$hello = $evening;}
if($chasov >= 22 or $chasov < 04) {$hello = $night;}
//echo "Время: $chasov:$minyt, $hello";


?>

<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<script
  src="https://code.jquery.com/jquery-3.6.3.js"
  integrity="sha256-nQLuAZGRRcILA+6dMBOvcRh5Pe310sBpanc6+QBmyVM="
  crossorigin="anonymous">
	
</script>
<style>
:root{--blue:#0a84ff;--text:#1c1c1e;--line:rgba(60,60,67,.18);--shadow:0 14px 40px rgba(30,80,160,.12)}
*{box-sizing:border-box}html{-webkit-text-size-adjust:100%;scroll-behavior:smooth}
body{margin:0;min-height:100vh;color:var(--text);font-family:-apple-system,BlinkMacSystemFont,"SF Pro Display","SF Pro Text",Arial,sans-serif;background:radial-gradient(circle at 8% 0%,rgba(10,132,255,.20),transparent 32%),radial-gradient(circle at 95% 10%,rgba(175,82,222,.18),transparent 30%),linear-gradient(135deg,#eef6ff 0%,#f7f5ff 52%,#eef1ff 100%);overflow-x:hidden}
a{color:inherit}.page-shell{width:min(980px,calc(100% - 32px));margin:34px auto 60px}
.top-sms-manager{display:flex;align-items:center;justify-content:center;width:min(980px,100%);min-height:78px;margin:0 auto 12px;padding:16px 24px;border:1px solid rgba(255,255,255,.9);border-radius:24px;background:rgba(255,255,255,.82);box-shadow:var(--shadow);color:#1266d6;text-decoration:none;font-size:28px;font-weight:700;backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px)}
.top-sms-manager .manager-icon{width:28px;height:28px;margin-right:12px;flex:0 0 28px}
.modem-sms-status,.copy-status{width:min(980px,100%);margin:0 auto 10px;padding:0 6px;font-size:16px;font-weight:700;line-height:1.35}.modem-sms-status{display:none}.modem-sms-status.success,.modem-sms-status.error{display:block}.modem-sms-status.success,.copy-status.success{color:#14833b}.modem-sms-status.error,.copy-status.error{color:#d70015}.copy-status{min-height:0}
form{width:min(980px,100%);margin:0 auto;padding:30px 34px 32px;border:1px solid rgba(255,255,255,.95);border-radius:28px;background:rgba(255,255,255,.76);box-shadow:var(--shadow);backdrop-filter:blur(22px);-webkit-backdrop-filter:blur(22px)}
form p{margin:0 0 22px}form p>b{display:block;margin-bottom:10px;font-size:16px;letter-spacing:.01em}
form select,form input[type=text],form input[type=number],form input[type=datetime-local]{width:100%;height:52px;padding:0 15px;border:1px solid var(--line);border-radius:15px;background:rgba(255,255,255,.9);color:var(--text);font:inherit;font-size:17px;outline:none;box-shadow:0 5px 16px rgba(0,0,0,.04);-webkit-appearance:none;appearance:none}
form select:focus,form input:focus{border-color:rgba(10,132,255,.55);box-shadow:0 0 0 4px rgba(10,132,255,.12)}
.phone-row{display:flex;align-items:center;gap:10px;width:100%}.phone-row .phone-plus{font-size:22px;font-weight:500;flex:0 0 auto}.phone-row input{flex:1;min-width:0}
#extra-input{display:none;margin-top:-10px;margin-bottom:22px}#extra-input input{width:100%}
.form-actions{display:flex;justify-content:center;align-items:center;gap:12px;margin-top:10px;text-align:center}.form-actions input{border:0;border-radius:18px;min-height:48px;padding:0 24px;font:700 16px -apple-system,BlinkMacSystemFont,"SF Pro Text",Arial,sans-serif;cursor:pointer}.form-actions input[type=submit]{background:linear-gradient(180deg,#1b8cff,#007aff);color:#fff;box-shadow:0 8px 20px rgba(0,122,255,.25)}.form-actions input[type=reset]{background:rgba(255,255,255,.9);color:#1c1c1e;box-shadow:0 5px 15px rgba(0,0,0,.08)}
.brd{width:min(980px,100%);margin:24px auto 0;padding:22px 24px;border:0!important;border-radius:22px;background:linear-gradient(135deg,#ffd84a,#ffc83d)!important;box-shadow:0 12px 30px rgba(180,120,0,.18);overflow:hidden}#text1 p{margin:0;font-size:17px;line-height:1.48;overflow-wrap:anywhere;word-break:break-word}
.generated-actions{width:min(980px,100%);margin:14px auto 0;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:10px}.generated-actions .action-button,.generated-actions a.button,.random-sms-action{width:100%;min-width:0;box-sizing:border-box;display:flex;align-items:center;justify-content:center;gap:9px;min-height:46px;padding:0 14px;margin:0;border:1px solid rgba(0,0,0,.08);border-radius:15px;background:rgba(255,255,255,.92);color:#1c1c1e;text-decoration:none;font:600 16px -apple-system,BlinkMacSystemFont,"SF Pro Text",Arial,sans-serif;cursor:pointer;box-shadow:0 7px 18px rgba(0,0,0,.08);transition:.18s ease}.generated-actions .action-button:hover{transform:translateY(-1px);background:#fff}.generated-actions .action-button:nth-child(n+3){grid-column:1/-1}.button-icon{width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;flex:0 0 20px;line-height:1}.button-icon svg{width:20px;height:20px;display:block}.button-icon svg *{vector-effect:non-scaling-stroke}.generated-actions .action-button{white-space:nowrap}.generated-actions .action-button:disabled{opacity:.55;cursor:wait;transform:none}#sendModemSms{width:100%;background:linear-gradient(180deg,#1b8cff,#007aff);color:#fff;border-color:transparent;font-weight:700}.random-sms-action{width:100%;background:rgba(255,255,255,.94);grid-column:1/-1!important}
@media(max-width:700px){body{font-size:17px}.page-shell{width:100%;margin:14px auto 36px;padding:0 14px}.top-sms-manager{width:100%;min-height:70px;margin:0 0 10px;border-radius:20px;font-size:23px}.modem-sms-status,.copy-status{width:100%;padding:0 4px;margin-bottom:10px;font-size:16px}form{width:100%;padding:22px 18px 24px;border-radius:24px}form p{margin-bottom:20px}form p>b{font-size:17px;margin-bottom:9px}form select,form input[type=text],form input[type=number],form input[type=datetime-local]{width:100%;height:50px;font-size:17px;border-radius:14px}.phone-row{gap:8px}.phone-row .phone-plus{font-size:21px}.form-actions{gap:10px}.form-actions input{min-height:46px;padding:0 20px;font-size:16px}.brd{width:100%;margin-top:18px;padding:18px;border-radius:20px}#text1 p{font-size:17px;line-height:1.48}.generated-actions{width:100%;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:8px;margin-top:12px}.generated-actions .action-button,.generated-actions a.button,.random-sms-action{width:100%;min-width:0;min-height:48px;padding:0 8px;font-size:15px;border-radius:15px}.generated-actions .action-button:nth-child(n+3),.generated-actions .random-sms-action{grid-column:1/-1!important}a,p,div,span{max-width:100%}}

/* === iOS bottom pickers: restored === */
.ios-picker-backdrop{
    display:none;
}
.ios-picker{
    display:none;
}
@media(max-width:700px){
    /* Keep the native fields as data sources, but use an iOS bottom sheet UI. */
    #reason, #party{
        color:#1c1c1e !important;
        -webkit-text-fill-color:#1c1c1e !important;
        caret-color:transparent;
        cursor:pointer;
        opacity:1 !important;
    }

    .ios-picker-backdrop{
        position:fixed;
        inset:0;
        z-index:9998;
        background:rgba(0,0,0,.22);
        backdrop-filter:blur(5px);
        -webkit-backdrop-filter:blur(5px);
        opacity:0;
        pointer-events:none;
        transition:opacity .22s ease;
        display:block;
    }
    .ios-picker-backdrop.open{
        opacity:1;
        pointer-events:auto;
    }

    .ios-picker{
        position:fixed;
        left:0;
        right:0;
        bottom:0;
        z-index:9999;
        display:block;
        padding:10px 12px calc(12px + env(safe-area-inset-bottom));
        border-radius:24px 24px 0 0;
        background:rgba(250,250,252,.96);
        box-shadow:0 -15px 45px rgba(0,0,0,.20);
        backdrop-filter:blur(25px);
        -webkit-backdrop-filter:blur(25px);
        transform:translateY(105%);
        transition:transform .26s cubic-bezier(.22,.8,.25,1);
    }
    .ios-picker.open{
        transform:translateY(0);
    }

    .ios-picker-head{
        display:flex;
        align-items:center;
        justify-content:space-between;
        min-height:42px;
        padding:0 4px 8px;
    }
    .ios-picker-title{
        font-size:16px;
        font-weight:700;
        color:#1c1c1e;
    }
    .ios-picker-btn{
        border:0;
        background:transparent;
        color:#007aff;
        font:600 16px -apple-system,BlinkMacSystemFont,"SF Pro Text",Arial,sans-serif;
        padding:8px 6px;
    }

    .ios-client-list{
        max-height:42vh;
        overflow-y:auto;
        -webkit-overflow-scrolling:touch;
        padding:4px 0 8px;
    }
    .ios-client-option{
        width:100%;
        min-height:52px;
        border:0;
        border-radius:14px;
        background:transparent;
        text-align:left;
        padding:0 16px;
        font:500 17px -apple-system,BlinkMacSystemFont,"SF Pro Text",Arial,sans-serif;
        color:#1c1c1e;
    }
    .ios-client-option.selected{
        background:#e7f1ff;
        color:#007aff;
        font-weight:700;
    }

    .ios-datetime{
        position:relative;
        display:grid;
        grid-template-columns:1.5fr 1fr 1.2fr 1fr 1fr;
        gap:5px;
        height:230px;
        overflow:hidden;
        padding:5px 0;
    }
    .ios-wheel{
        position:relative;
        height:220px;
        overflow-y:auto;
        scroll-snap-type:y mandatory;
        -webkit-overflow-scrolling:touch;
        padding:85px 0;
        scrollbar-width:none;
    }
    .ios-wheel::-webkit-scrollbar{display:none}
    .ios-wheel-item{
        height:50px;
        display:flex;
        align-items:center;
        justify-content:center;
        scroll-snap-align:center;
        font:500 18px -apple-system,BlinkMacSystemFont,"SF Pro Text",Arial,sans-serif;
        color:#8e8e93;
    }
    .ios-wheel-item.active{
        color:#1c1c1e;
        font-weight:700;
    }
    .ios-wheel-highlight{
        position:absolute;
        left:0;
        right:0;
        top:90px;
        height:50px;
        border-radius:12px;
        background:rgba(118,118,128,.12);
        pointer-events:none;
    }

    .ios-picker-hint{
        text-align:center;
        color:#8e8e93;
        font-size:12px;
        margin-top:4px;
    }
}


@media(max-width:700px){
    #reason,#party,#reason option,#party option{
        color:#1c1c1e !important;
        -webkit-text-fill-color:#1c1c1e !important;
        opacity:1 !important;
    }
}

/* iPhone Safari: prevent automatic zoom when controls receive focus */
@media (max-width: 700px) {
    input,
    select,
    textarea,
    button {
        font-size: 16px !important;
    }

    /* Keep the compact visual size without triggering Safari focus zoom. */
    .generated-actions .action-button,
    .generated-actions a.button,
    .random-sms-action {
        font-size: 16px !important;
    }

    #reason,
    #party {
        font-size: 16px !important;
    }
}


@media (max-width:700px){
    html{
        scroll-behavior:auto !important;
    }
    button,
    .copy-button{
        -webkit-tap-highlight-color:transparent;
    }
}

</style>
	
<title>ArtForDogs2</title>
</head>

<body>

<div id="iosPickerBackdrop" class="ios-picker-backdrop" onclick="closeIosPicker()"></div>

<div id="iosClientPicker" class="ios-picker" aria-hidden="true">
    <div class="ios-picker-head">
        <button type="button" class="ios-picker-btn" onclick="closeIosPicker()">Отмена</button>
        <div class="ios-picker-title">Выберите клиента</div>
        <button type="button" class="ios-picker-btn" onclick="confirmClientPicker()">Готово</button>
    </div>
    <div id="iosClientList" class="ios-client-list"></div>
</div>

<div id="iosDatePicker" class="ios-picker" aria-hidden="true">
    <div class="ios-picker-head">
        <button type="button" class="ios-picker-btn" onclick="closeIosPicker()">Отмена</button>
        <div class="ios-picker-title">Дата и время</div>
        <button type="button" class="ios-picker-btn" onclick="confirmDatePicker()">Готово</button>
    </div>
    <div class="ios-datetime">
        <div class="ios-wheel-highlight"></div>
        <div id="wheelYear" class="ios-wheel"></div>
        <div id="wheelMonth" class="ios-wheel"></div>
        <div id="wheelDay" class="ios-wheel"></div>
        <div id="wheelHour" class="ios-wheel"></div>
        <div id="wheelMinute" class="ios-wheel"></div>
    </div>
    <div class="ios-picker-hint">Проведите пальцем вверх или вниз</div>
</div>

	
<style>
   .brd {
    border: 4px double black; /* Параметры границы */
    background: #fc3; /* Цвет фона */
    padding: 10px; /* Поля вокруг текста */
   }
  </style>	
	<a href="/PHP_SMS_Manager/index.php" class="action-button button top-sms-manager"><span class="manager-icon" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><rect x="4" y="6" width="24" height="17" rx="5" stroke="currentColor" stroke-width="2.2"/><path d="M10 23l-1.5 4.5L14 23" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="11" cy="14.5" r="1.2" fill="currentColor"/><circle cx="16" cy="14.5" r="1.2" fill="currentColor"/><circle cx="21" cy="14.5" r="1.2" fill="currentColor"/></svg></span><span>SMS Manager</span></a>
<div id="copyStatus" class="copy-status" aria-live="polite"></div>
<div id="modemSmsStatus" class="modem-sms-status" aria-live="polite"></div>
<script>
async function copytext(selector){
 var el=document.querySelector(selector),status=document.getElementById("copyStatus");
 if(!el)return;
 var text=el.innerText||el.textContent||"";
 try{
   if(navigator.clipboard&&window.isSecureContext){
     await navigator.clipboard.writeText(text);
   }else{
     /*
      * iPhone Safari: do NOT create/focus a textarea.
      * Focusing a temporary textarea makes Safari scroll the page.
      * Instead select the existing message element and execute Copy.
      */
     var selection=window.getSelection();
     var range=document.createRange();
     range.selectNodeContents(el);
     selection.removeAllRanges();
     selection.addRange(range);
     var copied=document.execCommand("copy");
     selection.removeAllRanges();
     if(!copied) throw new Error("copy failed");
   }
   status.textContent="✓ Текст успешно скопирован.";
   status.className="copy-status success";
 }catch(e){
   status.textContent="Не удалось скопировать текст.";
   status.className="copy-status error";
 }
}
</script>
<?php 
	if(!empty($_POST['new_name'])){
		
	add_client();
		 if(isset($_SESSION['reg']['res'])){echo $_SESSION['reg']['res'];} 
	$_POST['name'] = $_POST['new_name'];
	if(empty($_POST['password'])){
		$_POST['password'] = mb_substr($_POST['telefon'], 4);}
		
	}
	
$aname= ucfirst($_POST[name]);
	$adate=date('d.m.Y', $time);
	$atime= date('H:i', $time);
	$atime15= date('H:i', $time-900);
	$atime2= date('H:i', $time+3600);
	$tel= $_POST['telefon'];
	//print $tel;
	$otziv="
%0A%0A
Если вас не затруднит оставьте пожалуйста отзыв на:%0A
https://clck.ru/3QwWyG";
	
	if($_POST['option']){$otziv2=$otziv;}else{$otziv2="";}
	
	 $body = "$hello, $aname!\n\nВы записались на посещение зала на $adate в $atime\nПароль для входа в зал:\n$_POST[password]#\nБудет действовать $adate с $atime15 до $atime2\n\nАрендованный зал находиться по адресу:\nг. Уфа ул. Пархоменко 106 вход со двора, слева от 1 подъезда серая дверь на ней кодовый замок.";
	 if($_POST['option']){$body .= "\n\nЕсли вас не затруднит оставьте пожалуйста отзыв на:\nhttps://clck.ru/3QwWyG";}

	
	$body2 = trim(strip_tags($body));
	if($_POST){
	
	$otziv3="<br><br>
Если вас не затруднит оставьте пожалуйста отзыв на:<br>
https://clck.ru/3QwWyG
";
		if($_POST['option']){$otziv4=$otziv3;}else{$otziv4="";}
echo ' <div class="brd" id="text1">
	
	<p >	
	
	
	
	'.$hello.', '.ucfirst($_POST[name]).'!<br> 
Вы записались на посещение зала на '.date('d.m.Y', $time).' в '.date('H:i', $time).'<br> 
Пароль для входа в зал:
'.$_POST[password].'#<br>
Будет действовать '.date('d.m.Y', $time).' с '.date('H:i', $time-900).' до '.date('H:i', $time+3600).'<br>
Арендованный зал находиться по адресу:
г. Уфа ул. Пархоменко 106 вход со двора , слева от 1 подъезда серая дверь на ней кодовый замок. <br>


'.$otziv4.'
</p>
	
	</div>
	
	';
		


	
	
	$encodedBody2 = urlencode($body2);
	$htmlContent = <<<HTML
<div class="generated-actions">
  <button type="button" class="action-button" onclick="copytext('#text1')"><span class="button-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><rect x="8" y="8" width="11" height="12" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M16 8V6.5A1.5 1.5 0 0 0 14.5 5h-8A1.5 1.5 0 0 0 5 6.5v10A1.5 1.5 0 0 0 6.5 18H8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span><span>Скопировать</span></button>
  <button type="button" class="action-button" onclick="document.location='/index.php'"><span class="button-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span><span>Новая запись</span></button>
  <a href="https://wa.me/$tel/?text=$encodedBody2" class="action-button button"><span class="button-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 3.5a8.5 8.5 0 0 0-7.2 13l-1 3.5 3.6-1A8.5 8.5 0 1 0 12 3.5Z" stroke="#25D366" stroke-width="1.8"/><path d="M9.2 8.4c.2-.4.4-.5.7-.5h.6c.2 0 .4.1.5.4l.7 1.7c.1.2 0 .4-.1.6l-.5.6c.6 1.1 1.4 1.8 2.6 2.4l.5-.6c.2-.2.4-.2.7-.1l1.6.7c.3.1.4.3.3.6-.2.8-.8 1.4-1.5 1.5-1.2.2-3.3-.8-4.8-2.1-1.4-1.2-2.3-2.8-2.3-3.9 0-.5.2-.9.5-1.3Z" fill="#25D366"/></svg></span><span>Отправить на Whats App</span></a>
  <a href="sms:$tel?body=$encodedBody2" class="action-button button"><span class="button-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3h11A2.5 2.5 0 0 1 20 5.5v8a2.5 2.5 0 0 1-2.5 2.5H11l-4.8 4v-4.2A2.5 2.5 0 0 1 4 13.5v-8Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M8 9h8M8 12h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></span><span>Отправить в SMS</span></a>
  <button type="button" id="sendModemSms" class="action-button button"><span class="button-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><rect x="6" y="4" width="12" height="16" rx="3" stroke="currentColor" stroke-width="1.8"/><path d="M9 8h6M9 12h6M9 16h3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span><span>Отправить в SMS через Модем</span></button>
</div>

  <script>
  document.getElementById('sendModemSms').addEventListener('click', async function () {
      const button = this;
      const status = document.getElementById('modemSmsStatus');

      button.disabled = true;
      status.textContent = 'Отправка...';
      status.className = 'modem-sms-status';

      const formData = new FormData();
      formData.append('action', 'send_modem_sms');
      formData.append('phone', <?= json_encode((string)$tel, JSON_UNESCAPED_UNICODE) ?>);
      formData.append('message', <?= json_encode((string)$body2, JSON_UNESCAPED_UNICODE) ?>);

      try {
          const response = await fetch('index.php', {
              method: 'POST',
              body: formData
          });
          const result = await response.json();

          if (!response.ok || !result.success) {
              throw new Error(result.message || 'Не удалось отправить SMS.');
          }

          status.textContent = '✓ SMS успешно отправлено.';
          status.className = 'modem-sms-status success';
      } catch (error) {
          status.textContent = error.message || 'SMS не отправлено.';
          status.className = 'modem-sms-status error';
      } finally {
          button.disabled = false;
      }
  });
  </script>
	
HTML;
	echo $htmlContent;
	} 

	if($_POST['name'])
	{
		echo '<div class="generated-actions"><a href="/send-sms-st906.php?number='. urlencode($_POST['telefon']). '" class="action-button button random-sms-action"><span class="button-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5v-11Z" stroke="currentColor" stroke-width="1.8"/><path d="m6.5 7.5 5.5 4 5.5-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span>Отправить произвольное SMS клиенту</span></a></div>';
	}
	
	?>
	

	
	<?php  if(!$_POST){
$contact = get_client();	//print_r($contact);	
	//echo json_encode($contact);?>

 <form name="test" method="post" action="index.php">
 <p><b>Имя клиента:</b><br>
	 <select id="reason" onchange="checkSelection(); fillFormFields(this)" name="name"  >
		 <option value="">-- Выберите клиента --</option>
	 <?php 
	//print_r(get_client());
					   
		
	foreach($contact as $key => $value):
				  ?>
                	<option <?php if($key == $contact) echo "selected";?> value="<?=$value['name']?>" data-fields='{"telefon":"<?=$value['telefon']?>","password":"<?=$value['password']?>"}' ><?=$value['name']?></option>
                <?php endforeach; ?>
<option value="other">Добавить нового клиента</option>
	 </select></p>
	<!-- <p><b>Введите имя клиента:</b><br>
   <input name="name" type="text" size="30">
  </p>-->
<!-- Поле, которое будет появляться -->
<div id="extra-input">
    <input type="text" name="new_name" size="30" placeholder="Введите имя нового клиента">
</div>

<script>
    function checkSelection() {
        const select = document.getElementById('reason');
        const inputDiv = document.getElementById('extra-input');
        
        // Показываем блок, если выбрано "other", иначе скрываем
        if (select.value === 'other') {
            inputDiv.style.display = 'block';
        } else {
            inputDiv.style.display = 'none';
        }
    }
</script>	 
	 
	 
	 <p><b>Введите телефон клиента:</b><br>
   <span class="phone-row"><span class="phone-plus">+</span><input type="number" name="telefon" pattern="[0-9]*" inputmode="decimal" oninput="this.value = this.value.slice(0, 11)" required placeholder="79874879999"></span>
  </p>
	 
	 <?php
	//$today = getdate();
	?>
	
	<p><b>Выберите дату посещения зала:</b><br>
	 <input  id="party"  type="datetime-local"  name="rezervDate"  value="<?=date("Y-m-d")?>T12:00" />
	 </p> 
	 
	 
	 <?php // print(); ?>
	 
	
	 
   <p><b>Введите пароль для входа:</b><br>
	   
	   <input type="number" name="password" pattern="[0-9]*" inputmode="decimal"  oninput="this.value = this.value.slice(0, 7)">
  
  </p>
	<input type="checkbox" name="option" value="a2" checked>Запрос отзыва<Br> 
  <p class="form-actions"><input type="submit" value="Сформировать">
   <input type="reset" value="Очистить"></p>
 </form>
	<script>
// функция для заполнения формы при выборе нового пункта
function fillFormFields(select){
    if(select.selectedIndex > -1){
        var option = select.options[select.selectedIndex];
        var form = select.form;
        if(option.dataset.fields){
            var fields = JSON.parse(option.dataset.fields);
            // теперь в fields у нас есть данные для заполнения полей
            // Заполняем поле название
            form.password.value = fields.password;
			form.telefon.value = fields.telefon;
        }else{
            form.password.value = '';
        }
    }
}
</script>
	<?php }
	if(isset($_SESSION['reg']['res'])){unset($_SESSION['reg']);} 
	?>
	

          
</div>

<script>
(function(){
    var activePicker = null;
    var pendingClientValue = '';
    var pendingDate = null;

    function isMobilePicker(){
        return window.matchMedia('(max-width:700px)').matches;
    }

    function openSheet(which){
        if(!isMobilePicker()) return;
        closeIosPicker();
        activePicker = which;
        document.getElementById('iosPickerBackdrop').classList.add('open');
        var el = document.getElementById(which === 'client' ? 'iosClientPicker' : 'iosDatePicker');
        el.classList.add('open');
        el.setAttribute('aria-hidden','false');
        document.body.style.overflow = 'hidden';
    }

    window.closeIosPicker = function(){
        document.getElementById('iosPickerBackdrop').classList.remove('open');
        document.getElementById('iosClientPicker').classList.remove('open');
        document.getElementById('iosDatePicker').classList.remove('open');
        document.getElementById('iosClientPicker').setAttribute('aria-hidden','true');
        document.getElementById('iosDatePicker').setAttribute('aria-hidden','true');
        document.body.style.overflow = '';
        activePicker = null;
    };

    function buildClientPicker(){
        var select = document.getElementById('reason');
        var list = document.getElementById('iosClientList');
        if(!select || !list) return;
        list.innerHTML = '';
        Array.prototype.forEach.call(select.options, function(opt, idx){
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'ios-client-option' + (opt.selected ? ' selected' : '');
            b.textContent = opt.textContent;
            b.dataset.value = opt.value;
            b.onclick = function(){
                pendingClientValue = opt.value;
                Array.prototype.forEach.call(list.children, function(x){x.classList.remove('selected');});
                b.classList.add('selected');
                // Visual selection is immediate, but form value is committed on "Готово".
            };
            list.appendChild(b);
        });
    }

    function showClient(){
        var select = document.getElementById('reason');
        if(!select) return;
        pendingClientValue = select.value;
        buildClientPicker();
        openSheet('client');
    }

    window.confirmClientPicker = function(){
        var select = document.getElementById('reason');
        if(select){
            var value = pendingClientValue;
            var option = Array.prototype.find.call(select.options, function(o){
                return String(o.value) === String(value);
            });
            if(option){
                select.selectedIndex = option.index;
                option.selected = true;
                select.value = option.value;
                try {
                    select.dispatchEvent(new Event('input', {bubbles:true}));
                    select.dispatchEvent(new Event('change', {bubbles:true}));
                } catch(e) {
                    var ev = document.createEvent('HTMLEvents');
                    ev.initEvent('change', true, false);
                    select.dispatchEvent(ev);
                }
            }
        }
        closeIosPicker();
    };

    function pad2(n){ return String(n).padStart(2,'0'); }

    function parseDateValue(){
        var input = document.getElementById('party');
        var raw = input && input.value ? input.value : '';
        var d = raw ? new Date(raw) : new Date();
        if(isNaN(d.getTime())) d = new Date();
        return {
            y:d.getFullYear(),
            m:d.getMonth()+1,
            d:d.getDate(),
            h:d.getHours(),
            min:0
        };
    }

    function makeWheel(id, values, current, formatter){
        var wheel = document.getElementById(id);
        wheel.innerHTML = '';
        values.forEach(function(v){
            var item=document.createElement('div');
            item.className='ios-wheel-item';
            item.dataset.value=v;
            item.textContent=formatter ? formatter(v) : v;
            if(String(v)===String(current)) item.classList.add('active');
            wheel.appendChild(item);
        });
        requestAnimationFrame(function(){
            var target = Array.prototype.find.call(wheel.children, function(x){
                return String(x.dataset.value)===String(current);
            });
            if(target) wheel.scrollTop = target.offsetTop - 85;
        });
        wheel.onscroll = function(){
            clearTimeout(wheel._t);
            wheel._t=setTimeout(function(){
                var center=wheel.scrollTop+110;
                var best=null,dist=1e9;
                Array.prototype.forEach.call(wheel.children,function(x){
                    var c=x.offsetTop+x.offsetHeight/2;
                    var d=Math.abs(c-center);
                    if(d<dist){dist=d;best=x;}
                    x.classList.remove('active');
                });
                if(best) best.classList.add('active');
            },60);
        };
    }

    function getWheelValue(id, fallback){
        var wheel=document.getElementById(id);
        var center=wheel.scrollTop+110, best=null, dist=1e9;
        Array.prototype.forEach.call(wheel.children,function(x){
            var c=x.offsetTop+x.offsetHeight/2, d=Math.abs(c-center);
            if(d<dist){dist=d;best=x;}
        });
        return best ? Number(best.dataset.value) : fallback;
    }

    function showDate(){
        pendingDate=parseDateValue();

        var years=[];
        for(var y=pendingDate.y-2;y<=pendingDate.y+5;y++) years.push(y);
        var months=[];
        for(var m=1;m<=12;m++) months.push(m);
        var days=[];
        var maxDay=new Date(pendingDate.y,pendingDate.m,0).getDate();
        for(var d=1;d<=maxDay;d++) days.push(d);
        var hours=[];
        for(var h=0;h<=23;h++) hours.push(h);
        // User requested minutes to start at 00; keep minute at 00.
        var mins=[0];

        makeWheel('wheelYear',years,pendingDate.y);
        makeWheel('wheelMonth',months,pendingDate.m,function(v){return pad2(v);});
        makeWheel('wheelDay',days,pendingDate.d,function(v){return pad2(v);});
        makeWheel('wheelHour',hours,pendingDate.h,function(v){return pad2(v);});
        makeWheel('wheelMinute',mins,0,function(){return '00';});

        openSheet('date');
    }

    window.confirmDatePicker=function(){
        var y=getWheelValue('wheelYear',pendingDate.y);
        var m=getWheelValue('wheelMonth',pendingDate.m);
        var d=getWheelValue('wheelDay',pendingDate.d);
        var h=getWheelValue('wheelHour',pendingDate.h);
        var input=document.getElementById('party');
        if(input){
            var value=y+'-'+pad2(m)+'-'+pad2(d)+'T'+pad2(h)+':00';
            input.value=value;
            input.setAttribute('value',value);
            try {
                input.dispatchEvent(new Event('input',{bubbles:true}));
                input.dispatchEvent(new Event('change',{bubbles:true}));
            } catch(e) {
                var ev=document.createEvent('HTMLEvents');
                ev.initEvent('change',true,false);
                input.dispatchEvent(ev);
            }
            input.style.color='#1c1c1e';
            input.style.webkitTextFillColor='#1c1c1e';
        }
        closeIosPicker();
    };

    function attach(){
        var client=document.getElementById('reason');
        var party=document.getElementById('party');

        if(client){
            client.addEventListener('mousedown',function(e){
                if(isMobilePicker()){e.preventDefault();showClient();}
            });
            client.addEventListener('touchstart',function(e){
                if(isMobilePicker()){e.preventDefault();showClient();}
            },{passive:false});
        }

        if(party){
            party.addEventListener('mousedown',function(e){
                if(isMobilePicker()){e.preventDefault();showDate();}
            });
            party.addEventListener('touchstart',function(e){
                if(isMobilePicker()){e.preventDefault();showDate();}
            },{passive:false});
        }
    }

    if(document.readyState==='loading'){
        document.addEventListener('DOMContentLoaded',attach);
    }else{
        attach();
    }

    window.addEventListener('resize',function(){
        if(!isMobilePicker()) closeIosPicker();
    });
})();
</script>





</body>
</html>
