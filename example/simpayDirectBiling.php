<?php
include( 'SimPayDB.php' );
include( '../database.php' );

$simPay = new SimPayDB();

$simPay -> setApiKey( 'lNEEDQPfPKHleZdd' );

if( $simPay -> parse( $_POST ) ){

	if( $simPay -> isError() ){
		$simPay -> okTransaction();

		return;
	}

	$stmt = $pdoObject -> prepare( 'INSERT INTO `direct_billing`( `status`, `value`, `control`) VALUES ( :status , :value , :control )' );

	$stmt -> bindValue( ':status' , $simPay -> getStatus(), PDO::PARAM_STR );
	$stmt -> bindValue( ':value' , $simPay -> getValue(), PDO::PARAM_STR );
	$stmt -> bindValue( ':control' , $simPay -> getControl(), PDO::PARAM_STR );
		 
	$stmt -> execute();

	if( $simPay -> isTransactionPaid() ){
		$stmt = $pdoObject -> prepare('SELECT * FROM `users` WHERE `id` = :user_id');
	
		$stmt -> bindValue( ':user_id' , $simPay -> getControl() , PDO::PARAM_INT );

		$stmt -> execute();

		$detailsUser = $stmt -> fetchAll();

		if( count( $detailsUser ) == 0 ){
			$simPay -> okTransaction();

			return;
		}

		$stmt = $pdoObject -> prepare('INSERT INTO `recharg_history`( `user_id`, `amount`, `type` ) VALUES ( :user_id , :amount , 5 )');
		
		$stmt -> bindValue( ':user_id' , $detailsUser[ 0 ][ 'id' ] , PDO::PARAM_INT );
		$stmt -> bindValue( ':amount' , $simPay -> getValue() , PDO::PARAM_STR );

		$stmt -> execute();

		$stmt = $pdoObject -> prepare('UPDATE `users` SET `money`= `money` + :amount WHERE `id` = :user_id');
		
		$stmt -> bindValue( ':user_id' , $detailsUser[ 0 ][ 'id' ] , PDO::PARAM_INT );
		$stmt -> bindValue( ':amount' , $simPay -> getValue() , PDO::PARAM_STR );

		$stmt -> execute();
	}
}
else{
	error_log( $simPay -> getErrorText() );
}

$simPay -> okTransaction();
?>