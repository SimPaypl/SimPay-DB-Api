<?php
include( 'SimPayDB.php' );

//Ustawienia MYSQL
$host = 'localhost';
$port = '3306'; 
$username = 'username';
$password = 'password';
$database = 'database';

$pdoObject = null;

try{
	$pdoObject = new PDO('mysql:host='.$host.';dbname='.$database.';port='.$port, $username, $password , array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'"));
	$pdoObject->query('SET NAMES utf8mb4');
	$pdoObject->query('SET CHARACTER SET utf8mb4');
	$pdoObject->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch( PDOException $e ){
	exit();
}

$simPay = new SimPayDB();

//Ustawianie API Key z panelu usługi partnera
$simPay -> setApiKey( 'lNEEDQPfPKHleZdd' );

//Parsowanie informacji pobranych z POST
if( $simPay -> parse( $_POST ) ){
	
	//Sprawdzenie czy parsowanie przebiegło pomyslnie
	if( $simPay -> isError() ){
		$simPay -> okTransaction();

		exit();
	}
	
	//Dodanie informacji o transakcji do bazy danych
	//$simPay -> getStatus() - Obecny status transakcji
	//$simPay -> getValuePartner() - Ile partner rzeczywiście uzyskał prowizji
	//$simPay -> getControl() - Wartość control wysłana przy starcie transakcji
	$stmt = $pdoObject -> prepare( 'INSERT INTO `direct_billing`( `status`, `value`, `control`) VALUES ( :status , :value , :control )' );

	$stmt -> bindValue( ':status' , $simPay -> getStatus(), PDO::PARAM_STR );
	$stmt -> bindValue( ':value' , $simPay -> getValuePartner(), PDO::PARAM_STR );
	$stmt -> bindValue( ':control' , $simPay -> getControl(), PDO::PARAM_STR );
		 
	$stmt -> execute();
	
	//Sprawdzenie czy transakcja została opłacona
	if( $simPay -> isTransactionPaid() ){
		$stmt = $pdoObject -> prepare('SELECT * FROM `users` WHERE `id` = :user_id');
	
		$stmt -> bindValue( ':user_id' , $simPay -> getControl() , PDO::PARAM_INT );

		$stmt -> execute();

		$detailsUser = $stmt -> fetchAll();

		if( count( $detailsUser ) == 0 ){
			//Zwrócenie że transakcja została pomyślnie odebrana przez partnera
			$simPay -> okTransaction();

			exit();
		}

		//$simPay -> getValuePartner() - Ile partner rzeczywiście uzyskał prowizji
		$stmt = $pdoObject -> prepare('UPDATE `users` SET `money`= `money` + :amount WHERE `id` = :user_id');
		
		$stmt -> bindValue( ':user_id' , $detailsUser[ 0 ][ 'id' ] , PDO::PARAM_INT );
		$stmt -> bindValue( ':amount' , $simPay -> getValuePartner() , PDO::PARAM_STR );

		$stmt -> execute();
	}
}
else{
	//Sprawdzenie typu błedu
	error_log( $simPay -> getErrorText() );
}

//Zwrócenie że transakcja została pomyślnie odebrana przez partnera
//Wartość zwracana przez partnera powinna zawierać tylko "OK". System SimPay uzna wtedy, że transakcja została poprawnie obsłużona i nie będzie ponawiał zapytań do serwisu partnera.
$simPay -> okTransaction();
?>
