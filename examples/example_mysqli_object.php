<?php
include( 'SimPayDB.php' );

//Ustawienia MYSQL
$host = 'localhost';
$port = '3306'; 
$username = 'username';
$password = 'password';
$database = 'database';

$conn = new mysqli( $host, $username, $password, $database );

// Check connection
if ($conn->connect_error) {
    die( 'Blad polaczenia: ' . $conn->connect_error );
} 

$simPay = new SimPayDB();

//Ustawianie API Key z panelu usługi partnera
$simPay -> setApiKey( 'lNEEDQPfPKHleZdd' );

//Parsowanie informacji pobranych z POST
if( $simPay -> parse( $_POST ) ){
	
	//Sprawdzenie czy parsowanie przebiegło pomyslnie
	if( $simPay -> isError() ){
		//Zwrócenie że transakcja została pomyślnie odebrana przez partnera
		$simPay -> okTransaction();

		$conn ->close();

		exit();
	}
	
	//Dodanie informacji o transakcji do bazy danych
	//$simPay -> getStatus() - Obecny status transakcji
	//$simPay -> getValuePartner() - Ile partner rzeczywiście uzyskał prowizji
	//$simPay -> getControl() - Wartość control wysłana przy starcie transakcji
	$stmt = $conn->prepare("INSERT INTO `direct_billing`( `status`, `value`, `control` ) VALUES ( ? , ? , ? )");
	
	$stmt->bind_param(  $simPay -> getStatus() , $simPay -> getValuePartner() , $simPay -> getControl() );

	$stmt->execute();
	
	//Sprawdzenie czy transakcja została opłacona
	if( $simPay -> isTransactionPaid() ){
		$stmt = $conn->prepare("SELECT * FROM `users` WHERE `id` = ?");
	
		$stmt->bind_param(  $simPay -> getControl() );

		$stmt->execute();

		$detailsUser = $stmt->fetch(); 

		if( count( $detailsUser ) == 0 ){
			//Zwrócenie że transakcja została pomyślnie odebrana przez partnera
			$simPay -> okTransaction();

			$conn ->close();

			exit();
		}

		//$simPay -> getValuePartner() - Ile partner rzeczywiście uzyskał prowizji
		$stmt = $conn->prepare("UPDATE `users` SET `money`= `money` + ? WHERE `id` = ?");
	
		$stmt->bind_param(  $simPay -> getValuePartner() , $detailsUser[ 0 ][ 'id' ] );

		$stmt->execute();
	}
}
else{
	//Sprawdzenie typu błedu
	error_log( $simPay -> getErrorText() );
}

//Zwrócenie że transakcja została pomyślnie odebrana przez partnera
//Wartość zwracana przez partnera powinna zawierać tylko "OK". System SimPay uzna wtedy, że transakcja została poprawnie obsłużona i nie będzie ponawiał zapytań do serwisu partnera.
$simPay -> okTransaction();

$conn ->close();
?>
