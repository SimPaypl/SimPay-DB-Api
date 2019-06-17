<?php
class SimPayDB {
	private $error = false;
	private $errorCode = 0;

	private $apiKey = '';

	private $status = '';
	private $value = '';
	private $valueGross = '';
	private $control = '';

	private $transId = '';

	private $valuePartner = '';

	private $userNumber = '';

	protected $debugMode = false;

	public function setDebugMode($value) {
		$this->debugMode = (boolean)$value;
	}

	private function isDebugMode() {
		return !!$this->debugMode;
	}

	private function logDebugMode($err) {
		print_r( $err ); 

		error_log( $err );
	}

	public function parse($data) {

		if (!isset($data[ 'id' ])) {
			$this->setError(true, 1);

			if ($this->isDebugMode()) {
				$this->logDebugMode($this->getErrorText());
			}
			
			return false;
		}

		if (!isset($data['status'])) {
			$this->setError(true, 1);

			if ($this->isDebugMode()) {
				$this->logDebugMode($this->getErrorText());
			}
			
			return false;
		}
		
		if (!isset($data['valuenet'])) {
			$this->setError(true, 1);

			if ($this->isDebugMode()) {
				$this->logDebugMode($this->getErrorText());
			}
			
			return false;
		}

		if (!isset($data['sign'])) {
			$this->setError(true, 1);

			if ($this->isDebugMode()){
				$this->logDebugMode($this->getErrorText());
			}

			return false;
		}

		$this->status = trim($data['status']);
		$this->value =  trim($data['valuenet']);
		$this->valueGross =  trim($data['valuenet_gross']);

		if (isset($data['control'])) {
			$this->control = trim($data['control']);
		}

		if (isset($data['number_from'])) {
			$this->userNumber = trim($data['number_from']);
		}

		$this->transId = trim($data['id']);

		$this->valuePartner = trim($data['valuepartner']);

		if (hash('sha256', $this->transId . $this->status . $this->value . $this->valuePartner . $this->control . $this->apiKey) != $data['sign']) {
			$this->setError(true, 3);

			if ($this->isDebugMode()) {
				$this->logDebugMode($this->getErrorText());
			}
			
			return false;
		}

		$this->value = floatval(str_replace(',', '.', $this->value));

		if ($this->value <= 0.00) {
			$this->setError(true, 4);

			if ($this->isDebugMode()){
				$this->logDebugMode($this->getErrorText());
			}
		}

		$this->valuePartner = floatval(str_replace(',', '.', $this->valuePartner));

		if ($this->valuePartner <= 0.00) {
			$this->setError(true, 4);

			if ($this->isDebugMode()){
				$this->logDebugMode($this->getErrorText());
			}
		}

		return true;
	}
	
	public function isError() {
		return $this->error;
	}
	
	public function getErrorText() {
		switch($this->errorCode) {
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
	
	private function setError($state, $code) {
		$this->error = $state;
		$this->errorCode = $code;
	}

	public function setApiKey($key) {
		$this->apiKey = $key;
	}

	public function getStatus() {
		return $this->status;
	}

	public function getValue() {
		return $this->value;
	}

	public function getValueGross() {
		return $this->valueGross;
	}

	public function getControl() {
		return $this->control;
	}

	public function isTransactionPaid() {
		return ($this->status == 'ORDER_PAYED');
	}

	public function getTransactionId() {
		return $this->transId;
	}

	public function okTransaction() {
		ob_clean();
		echo 'OK';
	}

	public function getValuePartner() {
		return $this->valuePartner;
	}

	public function getUserNumber() {
		return $this->userNumber;
	}

	public function calculateRewardPartner($amount, $provider) {
		/*
		$provider =
		1 - Orange
		2 - Play
		3 - T-mobile
		4 - Plus
		*/

		if ($amount <= 0) {
			return 0.00;
		}

		$arrayCommission = [];

		switch($provider) {
			case 1:{
				$arrayCommission = [0.65, 0.65, 0.65];

				break;
			}
			case 2:{
				$arrayCommission = [0.55, 0.65, 0.70];

				break;
			}
			case 3:{
				$arrayCommission = [0.60, 0.60, 0.60];

				break;
			}
			case 4:{
				$arrayCommission = [0.50, 0.50, 0.50];

				break;
			}
		}

		if ($amount < 9) {
			return number_format($amount * $arrayCommission[0], 2, '.', '');
		} else if( $amount < 25 ) {
			return number_format($amount * $arrayCommission[1], 2 , '.' , '');
		} else {
			return number_format($amount * $arrayCommission[2], 2, '.', '');
		}
	}
}

class SimPayDBTransaction {
	protected $requestOptions = [
		'serviceId' => NULL,
		'amount' => NULL,
		'amount_gross' => NULL,
		'amount_required' => NULL,
		'provider' => NULL,
		'control' => NULL,
		'failure' => NULL,
		'complete' => NULL,
		'sign' => NULL
	];

	protected $apiKey = '';

	protected $resultInstance = NULL;

	protected $debugMode = false;

	public function setDebugMode($value) {
		$this->debugMode = (boolean)$value;
	}

	private function isDebugMode(){
		return !!$this->debugMode;
	}

	private function logDebugMode($err){
		print_r($err);

		error_log($err);
	}

	public function setServiceID($id){
		$this->requestOptions['serviceId'] = $id;
	}

	public function setAmount($amount) {
		$this->requestOptions['amount'] = $amount;

		$this->requestOptions['amount_gross'] = NULL;
		$this->requestOptions['amount_required'] = NULL;
	}

	public function setAmountGross($amount) {
		$this->requestOptions['amount'] = NULL;

		$this->requestOptions['amount_gross'] = $amount;
		$this->requestOptions['amount_required'] = NULL;
	}

	public function setAmountRequired($amount) {
		$this->requestOptions['amount'] = NULL;

		$this->requestOptions['amount_gross'] = NULL;
		$this->requestOptions['amount_required'] = $amount;
	}

	public function setProvider($provider) {
		$this->requestOptions['provider'] = $provider;
	}

	public function setControl($control) {
		$this->requestOptions['control'] = $control;
	}

	public function setFailureLink($link) {
		$this->requestOptions['failure'] = $link;
	}

	public function setCompleteLink($link) {
		$this->requestOptions['complete'] = $link;
	}

	public function setApiKey($key) {
		$this->apiKey = $key;
	}

	protected function generateSign() {
		$hash = '';
		
		if ($this->requestOptions['amount']) {
			$hash = hash('sha256', $this->requestOptions['serviceId'] . $this->requestOptions['amount'] . $this->requestOptions['control'] . $this->apiKey);
		} else if ($this->requestOptions['amount_gross']) {
			$hash = hash('sha256', $this->requestOptions['serviceId'] . $this->requestOptions['amount_gross'] . $this->requestOptions['control'] . $this->apiKey);
		} else if ($this->requestOptions['amount_required']) {
			$hash = hash('sha256', $this->requestOptions['serviceId'] . $this->requestOptions['amount_required'] . $this->requestOptions['control'] . $this->apiKey);
		}

		return $hash;
	}

	public function getTransactionLink() {
		if (!isset($this->resultInstance->link)){
			return '';
		}

		return $this->resultInstance->link;
	}

	public function getResults() {
		return $this->resultInstance;
	}

	public function generateTransaction() {

		$this->requestOptions['sign'] = $this->generateSign();

		$ch = curl_init('https://simpay.pl/db/api');

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestOptions);

		$result = curl_exec($ch);

		curl_close($ch);

		try {
			$resultDecode = json_decode($result);
			
			$this->resultInstance = $resultDecode;
		}
		catch (Exception $err) {
			if( $this->isDebugMode()) {
				$this->logDebugMode($err);
			}
		}

		if ($this->resultInstance != 'success') {
			if ($this->isDebugMode()) {
				$this->logDebugMode($this->resultInstance->message);
			}
		}
	}
}

?>