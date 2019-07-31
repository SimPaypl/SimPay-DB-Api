<?php
require_once('SimPayDB.php');

$cfg = array(
	'simpay' => array(
		/*
			Klucz API usługi
			Typ pola string
		*/
		'apiKey' => 'lNEEDQPfPKHleZdd',
	)
);

$simPay = new SimPayDB();

$simPay->setApiKey($cfg['simpay']['apiKey']);

if (!in_array($simPay->getRemoteAddr(), $simPay->getIp()['respond']['ips'])) {
	$simPay->okTransaction();
	$mysqli->close();
	exit();
}

//Parsowanie informacji pobranych z POST
if ($simPay->parse($_POST)) {
	
	//Sprawdzenie czy parsowanie przebiegło pomyslnie
	if ($simPay->isError()) {
		//Zwrócenie że transakcja została pomyślnie odebrana przez partnera
		$simPay->okTransaction();
		exit();
	}
	
	
	//Sprawdzenie czy transakcja została opłacona
	if (!$simPay->isTransactionPaid()) {
		error_log($simPay->getErrorText());
	}
} else {
	//Sprawdzenie typu błedu
	error_log($simPay->getErrorText());
}

//$simPay->getStatus() - Obecny status transakcji
//$simPay->getValuePartner() - Ile partner rzeczywiście uzyskał prowizji
//$simPay->getControl() - Wartość control wysłana przy starcie transakcji

/*
* Tutaj można wykonywać zapytanie do bazy MySQL ze statusem iż transakcja jest prawidłowa
*/

$simPay->okTransaction();
exit();
?>
