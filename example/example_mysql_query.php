<?php
include( 'SimPayDB.php' );

//Ustawienia MYSQL
$host = 'localhost';
$port = '3306'; 
$username = 'username';
$password = 'password';
$database = 'database';

$conn = mysql_connect( $host, $username, $password, $database );

// Check connection
if ( !$conn ) {
    die( 'Blad polaczenia: ' . mysql_error() );
}

$selected = mysql_select_db( $database , $conn );

if ( !$selected ) {
    die( 'Blad polaczenia: ' . mysql_error() );
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

		mysql_close( $conn );

		return;
	}
	
	//Dodanie informacji o transakcji do bazy danych
	//$simPay -> getStatus() - Obecny status transakcji
	//$simPay -> getValuePartner() - Ile partner rzeczywiście uzyskał prowizji
	//$simPay -> getControl() - Wartość control wysłana przy starcie transakcji

	mysql_query( 'INSERT INTO `direct_billing`( `status`, `value`, `control` ) VALUES ( ' . mysql_escape_string( $simPay -> getStatus() ). ' , ' . mysql_escape_string( $simPay -> getValuePartner() ). ' , ' . mysql_escape_string( $simPay -> getControl() ). ' )' , $conn );
	
	//Sprawdzenie czy transakcja została opłacona
	if( $simPay -> isTransactionPaid() ){
		$retval = mysql_query( 'INSERT INTO `SELECT * FROM `users` WHERE `id` = ' . mysql_escape_string( $simPay -> getControl() ) , $conn );

		$detailsUser = mysql_fetch_assoc( $retval );

		mysql_free_result( $retval );

		if( count( $detailsUser ) == 0 ){
			//Zwrócenie że transakcja została pomyślnie odebrana przez partnera
			$simPay -> okTransaction();

			mysql_close( $conn );

			return;
		}

		//$simPay -> getValuePartner() - Ile partner rzeczywiście uzyskał prowizji
		mysql_query( $conn , 'UPDATE `users` SET `money`= `money` + ' . mysql_escape_string( $simPay -> getValuePartner() ). ' WHERE `id` = ' . mysql_escape_string( $detailsUser[ 0 ][ 'id' ] ) );
	}
}
else{
	//Sprawdzenie typu błedu
	error_log( $simPay -> getErrorText() );
}

//Zwrócenie że transakcja została pomyślnie odebrana przez partnera
//Wartość zwracana przez partnera powinna zawierać tylko "OK". System SimPay uzna wtedy, że transakcja została poprawnie obsłużona i nie będzie ponawiał zapytań do serwisu partnera.
$simPay -> okTransaction();

mysql_close( $conn );
?>
