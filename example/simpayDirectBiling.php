<?php
include( 'SimPayDB.php' );
include( 'database.php' );

$simPay = new SimPayDB();

//Ustawianie API Key z panelu usługi partnera
$simPay -> setApiKey( 'lNEEDQPfPKHleZdd' );

//Parsowanie informacji pobranych z POST
if( $simPay -> parse( $_POST ) ){
	
	//Sprawdzenie czy parsowanie przebiegło pomyslnie
	if( $simPay -> isError() ){
		//Pobranie textowej wersji błędu
		$errorText = $simPay -> getErrorText();
		
		//Wyświetlenie błędu
		echo $errorText;
		
		//Zapisanie błędu do error logów
		error_log( $errorText , 0 );

		return;
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

			return;
		}

		$stmt = $pdoObject -> prepare('INSERT INTO `recharg_history`( `user_id`, `amount`, `type` ) VALUES ( :user_id , :amount , 5 )');
		
		$stmt -> bindValue( ':user_id' , $detailsUser[ 0 ][ 'id' ] , PDO::PARAM_INT );
		$stmt -> bindValue( ':amount' , $simPay -> getValuePartner() , PDO::PARAM_STR );

		$stmt -> execute();

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
$simPay -> okTransaction();
?>
