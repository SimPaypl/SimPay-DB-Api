<?php
class SimPayDB{
	private $error = false;
	private $errorCode = 0;

	private $apiKey = '';

	private $status = '';
	private $value = '';
	private $control = '';

	private $transId = '';

	private $valuePartner = '';

	public function parse( $data ){

		if( !isset( $data[ 'id' ] ) ){
			$this -> setError( true , 1 );
			
			return false;
		}

		if( !isset( $data[ 'status' ] ) ){
			$this -> setError( true , 1 );
			
			return false;
		}
		
		if( !isset( $data[ 'valuenet' ] ) ){
			$this -> setError( true , 1 );
			
			return false;
		}

		if( !isset( $data[ 'sign' ] ) ){
			$this -> setError( true , 1 );
			
			return false;
		}

		$this -> status = trim( $data[ 'status' ] );
		$this -> value =  trim( $data[ 'valuenet' ] );

		if( isset( $data['control'] ) ){
			$this -> control = trim( $data[ 'control' ] );
		}

		$this -> transId = trim( $data[ 'id' ] );

		$this -> valuePartner = trim( $data[ 'valuepartner' ] );

		if( hash( 'sha256' , $this -> transId . $this -> status . $this -> value . $this -> valuePartner . $this -> control . $this -> apiKey ) != $data[ 'sign' ] ){
			$this -> setError( true , 3 );
			
			return false;
		}

		$this -> value = floatval( str_replace( ',' , '.' , $this -> value ) );

		if( $this -> value <= 0.00 ){
			$this -> setError( true , 4 );
		}

		$this -> valuePartner = floatval( str_replace( ',' , '.' , $this -> valuePartner ) );

		if( $this -> valuePartner <= 0.00 ){
			$this -> setError( true , 4 );
		}

		return true;
	}
	
	public function isError(){
		return $this -> error;
	}
	
	public function getErrorText(){
		switch( $this -> errorCode ){
			case 0:
				return 'No Error';
			case 1:
				return 'Missing Parameters';
			case 2:
				return 'No Sign Param';
			case 3:
				return 'Wrong Sign';
			case 4:
				return 'Wrong Amount Value';
		}
		
		return '';
	}
	
	private function setError( $state , $code ){
		$this -> error = $state;
		$this -> errorCode = $code;
	}

	public function setApiKey( $key ){
		$this -> apiKey = $key;
	}

	public function getStatus(){
		return $this -> status;
	}

	public function getValue(){
		return $this -> value;
	}

	public function getControl(){
		return $this -> control;
	}

	public function isTransactionPaid(){
		return ( $this -> status == 'ORDER_PAYED' );
	}

	public function getTransactionId(){
		return $this -> transId;
	}

	public function okTransaction(){
		echo 'OK';
	}

	public function getValuePartner(){
		return $this -> valuePartner;
	}

	public function calculateRewardPartner( $amount , $provider ){
		/*
		$provider =
		1 - Orange
		2 - Play
		3 - T-mobile
		4 - Plus
		*/

		if( $amount <= 0 ){
			return 0.00;
		}

		$arrayComission = [];

		switch( $provider ){
			case 1:{
				$arrayComission = [ 0.55 , 0.65 , 0.70 ];

				break;
			}
			case 2:{
				$arrayComission = [ 0.55 , 0.65 , 0.70 ];

				break;
			}
			case 3:{
				$arrayComission = [ 0.60 , 0.60 , 0.60 ];

				break;
			}
			case 4:{
				$arrayComission = [ 0.50 , 0.50 , 0.50 ];

				break;
			}
		}

		if( $amount < 9 ){
			return number_format( $amount * $arrayComission[ 0 ] , 2 , '.' , '' );
		}
		else if( $amount < 25 ){
			return number_format(  $amount * $arrayComission[ 1 ] , 2 , '.' , '' );
		}
		else{
			return number_format(  $amount * $arrayComission[ 2 ] , 2 , '.' , '' );
		}
	}
}

?>