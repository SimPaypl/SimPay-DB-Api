<?php
$cfg = array(
    'mysql' => array(
        'host' => 'localhost',
        'username' => 'username',
        'password' => 'password',
        'database' => 'database'
    ),
    /*
    * Klucz API z panelu
    */
    'apiKey' => 'yjhy45ffgbxv',
    /*
    * ID Usługi z panelu simpay
    */
    'serviceId' => 1111,
    /*
    * Kwota jaką miała kosztować usługa
    */
    'amount' => 22.50
);

function getRemoteAddr()
{
    return getenv('HTTP_CLIENT_IP') ?: getenv('HTTP_X_FORWARDED_FOR'[0]) ?: getenv('HTTP_X_FORWARDED') ?: getenv('HTTP_FORWARDED_FOR') ?: getenv('HTTP_FORWARDED') ?: getenv('REMOTE_ADDR');
}

function checkIp($ip)
{
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://simpay.pl/api/get_ip');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FAILONERROR, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $response = json_decode(curl_exec($curl));
    curl_close($curl);

    if (in_array($ip, $response->respond->ips)) {
        return true;
    } else {
        return false;
    }
}

if (!checkIp(getRemoteAddr())) {
    exit('OK');
}

if (!isset($_POST['id'], $_POST['status'], $_POST['valuenet_gross'], $_POST['valuenet'], $_POST['valuepartner'], $_POST['control'], $_POST['sign'])) {
    exit('OK');
}

$mysqli = new mysqli($cfg['mysql']['host'], $cfg['mysql']['username'], $cfg['mysql']['password'], $cfg['password']['database']);
if ($mysqli->connect_error) {
    exit('Connection error: ' . $mysqli->connect_error);
}

$json = json_encode($_POST);

/*

* ID Trsanakcji
$json->id;

* Status transakcji
$json->status;

* Kwota transakcji brutto
$json->valuenet_gross;

* Kwota transakcji netto
$json->valuenet;

* Kwota z tranakcji jaką otrzymał partner
$json->valuepartner;

* Pole do wykorzystania przez parnera, np do przechowywania informacji o płatności
$json->control;

* Pole, które pozwala zweryfikować poprawność transakcji, a także pochodzenie notyfikacji
$json->sign;

*/

if ($json->status != "ORDER_PAYED") {
    exit('OK');
}

if ($json->sign != hash('sha256', $json->id . $json->status . $json->valuenet_netto . $json->valuepartner . $json->control . $cfg['apiKey'])) {
    exit('OK');
}

if ($json->valuenet_gross != $cfg['amount']) {
    exit('OK');
}

$stmt = $mysqli->prepare("SELECT * FROM `dcb` WHERE `control` = ? AND `status` = 'new';");
    
$stmt->bind_param($json->control);
$stmt->execute();
$detailsUser = $stmt->fetch();

if (count($detailsUser) == 0) {
    /*
    * Setowanie statusu transakcji jako completed w przypadku poprawnego zakończenia transakcji i jeżeli dana transakcja po polu control została znaleziona w bazie danych
    */
    $stmt = $mysqli->prepare("UPDATE `dcb` SET `status` = 'completed', `amount` = ? WHERE `control` = ?;");
    $stmt->bind_param($json->valuepartner, $json->control);
    $stmt->execute();
    $mysqli->close();
}

ob_clean();
exit('OK');
