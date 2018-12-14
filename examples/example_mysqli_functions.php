<?php
include( 'SimPayDB.php' );

//Ustawienia MYSQL
$host = 'localhost';
$port = '3306'; 
$username = 'username';
$password = 'password';
$database = 'database';

$conn = mysqli_connect( $host, $username, $password, $database );

// Check connection
if ( !$conn ) {
    die( 'Blad polaczenia: ' . mysqli_connect_error() );
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

		mysqli_stmt_close( $conn );

		exit();
	}
	
	//Dodanie informacji o transakcji do bazy danych
	//$simPay -> getStatus() - Obecny status transakcji
	//$simPay -> getValuePartner() - Ile partner rzeczywiście uzyskał prowizji
	//$simPay -> getControl() - Wartość control wysłana przy starcie transakcji
	$stmt = mysqli_prepare( $conn , "INSERT INTO `direct_billing`( `status`, `value`, `control` ) VALUES ( ? , ? , ? )");
	
	mysqli_stmt_bind_param( $stmt , $simPay -> getStatus() , $simPay -> getValuePartner() , $simPay -> getControl() );

	mysqli_stmt_execute( $stmt );
	
	//Sprawdzenie czy transakcja została opłacona
	if( $simPay -> isTransactionPaid() ){
		$stmt = mysqli_prepare( $conn , "SELECT * FROM `users` WHERE `id` = ?");
	
		mysqli_stmt_bind_param( $stmt , $simPay -> getControl() );

		mysqli_stmt_execute( $stmt );

		$detailsUser = mysqli_stmt_fetch( $stmt );

		if( count( $detailsUser ) == 0 ){
			//Zwrócenie że transakcja została pomyślnie odebrana przez partnera
			$simPay -> okTransaction();

			mysqli_stmt_close( $conn );

			exit();
		}

		//$simPay -> getValuePartner() - Ile partner rzeczywiście uzyskał prowizji
		$stmt = mysqli_prepare( $conn , "UPDATE `users` SET `money`= `money` + ? WHERE `id` = ?");
	
		mysqli_stmt_bind_param( $stmt , $simPay -> getValuePartner() , $detailsUser[ 0 ][ 'id' ] );

		mysqli_stmt_execute( $stmt );
	}
}
else{
	//Sprawdzenie typu błedu
	error_log( $simPay -> getErrorText() );
}

//Zwrócenie że transakcja została pomyślnie odebrana przez partnera
//Wartość zwracana przez partnera powinna zawierać tylko "OK". System SimPay uzna wtedy, że transakcja została poprawnie obsłużona i nie będzie ponawiał zapytań do serwisu partnera.
$simPay -> okTransaction();

mysqli_stmt_close( $conn );
?>
