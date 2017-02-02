<?php
	namespace PayMayaPaymentVault;
	
	use Curl\Curl;
	
	require_once __DIR__ . "/Curl/Curl.php";
	require_once __DIR__ . "/Curl/ArrayUtil.php";
	require_once __DIR__ . "/Curl/CaseInsensitiveArray.php";
	require_once __DIR__ . "/Curl/MultiCurl.php";
	
	class CardDetails {
		public $cardNumber;
		public $cardExpiryMonth;
		public $cardExpiryYear;
		public $cardCVC;
		public $cardType;
		public $cardId;
		
		function __construct() {
			
		}
		
		function __destruct() {
			$this->cardNumber = null;
			$this->cardExpiryMonth = null;
			$this->cardExpiryYear = null;
			$this->cardCVC = null;
			$this->cardType = null;
			$this->cardId = null;
		}
		
		function __get( $name ) {
			return $this->$name;
		}
		
		function  __set( $name, $value ) {
			$this->$name = str_replace(chr(32), '', trim($value));
		}
	}
	
	class CustomersDetails {
		public $firstName;
		public $middleName;
		public $lastName;
		public $phone;
		public $email;
		public $sex;
		public $birthday;
		public $line1;
		public $line2;
		public $city;
		public $state;
		public $zipCode;
		public $countryCode;
		
		function __construct() {
			
		}
		
		function __destruct() {
			$this->firstName = null;
			$this->middleName = null;
			$this->lastName = null;
			$this->phone = null;
			$this->email = null;
			$this->sex = null;
			$this->birthday = null;
			$this->line1 = null;
			$this->line2 = null;
			$this->city = null;
			$this->state = null;
			$this->zipCode = null;
			$this->countryCode = null;
		}
		
		function __get( $name ) {
			return $this->$name;
		}
		
		function  __set( $name, $value ) {
			$this->$name = trim($value);
		}
	}
	
	class PaymentVault {
		
		private $paymentId;
		private $customerId;
		private $tokenId;
		private $tokenState;
		private $pk_key;
		private $sk_key;
		private $url;
		private $paymayaUrl = array(
			"sandbox" => 'https://pg-sandbox.paymaya.com/payments',
			"production" => ''
		);
		private $urlPath = array(
			'createToken' => '/v1/payment-tokens',
			'payments' => '/v1/payments'
		);
		
		public $totalAmount;
		public $currency;
		public $CustomerDetails;
		public $CardDetails;
		
		function __construct($publicKey, $secretKey, $env) {
			$this->pk_key = trim($publicKey);
			$this->sk_key = trim($secretKey);
			$this->url = $this->paymayaUrl[($env == "yes"? "sandbox" : "production")];
			
			$this->CustomerDetails = new CustomersDetails();
			$this->CardDetails = new CardDetails();
		}
		
		function __destruct() {
			$this->CustomerDetails = null;
			$this->CardDetails = null;
		}
		
		function __get( $name ) {
			return $this->$name;
		}
		
		function  __set( $name, $value ) {
			$this->$name = $value;
		}
		
		public function hasToken(){
			return (strlen($this->tokenId) > 0? true : false);
		}
		
		public function hasPaymentId(){
			return (strlen($this->paymentId) > 0? true : false);
		}
		
		public function getToken(){
			$ch = new Curl();
			$ch->setHeader('Content-Type', 'application/json');
			$ch->setHeader('Authorization', "Basic " . base64_encode($this->pk_key . ":"));
			$ch->post($this->url . $this->urlPath['createToken'], json_encode($this->generateReqCreateToken()));
			
			$retval = $ch->response;
			$ch->close();
			$this->tokenId = $retval->paymentTokenId;
			$this->tokenState = $retval->state;
			
			return $retval;
		}
		
		public function createPayment(){
			$this->getToken();
			
			if ($this->hasToken() == false){
				return false;
			}
			
			$ch = new Curl();
			$ch->setHeader('Content-Type', 'application/json');
			$ch->setHeader('Authorization', "Basic " . base64_encode($this->sk_key . ":"));
			$ch->post($this->url . $this->urlPath['payments'], json_encode($this->generateReqCreatePayment()));
			
			$retval = $ch->response;
			$ch->close();
			
			$this->paymentId = $retval->id;
			
			return $retval;
		}
		
		public function getPayment(){
			if($this->hasPaymentId() == false){
				return false;
			}
			
			$ch = new Curl();
			$ch->setHeader('Content-Type', 'application/json');
			$ch->setHeader('Authorization', "Basic " . base64_encode($this->sk_key . ":"));
			$ch->get($this->url . $this->urlPath['payments'] . chr(47) . $this->paymentId);
			
			$retval = $ch->response;
			$ch->close();
			
			return $retval;
		}
		
		private function generateReqCreateToken(){
			return array(
				'card' => array(
					'number' => (isset($this->CardDetails->cardNumber)? $this->CardDetails->cardNumber : ""),
					'expMonth' => (isset($this->CardDetails->cardExpiryMonth)? $this->CardDetails->cardExpiryMonth : ""),
					'expYear' => (isset($this->CardDetails->cardExpiryYear)? $this->CardDetails->cardExpiryYear : ""),
					'cvc' => (isset($this->CardDetails->cardCVC)? $this->CardDetails->cardCVC : ""),
				)
			);
		}
		
		private function generateReqCreatePayment(){
			return array(
				'paymentTokenId' => $this->tokenId,
				'totalAmount' => array(
					"amount" => (isset($this->totalAmount)? $this->totalAmount : 0),
                    "currency" => (isset($this->currency)? $this->currency : "")
				),
				'buyer' => array(
					"firstName" => (isset($this->CustomerDetails->firstName)? $this->CustomerDetails->firstName : ""),
				    "middleName" => (isset($this->CustomerDetails->middleName)? $this->CustomerDetails->middleName : ""),
				    "lastName" => (isset($this->CustomerDetails->lastName)? $this->CustomerDetails->lastName : ""),
				    "contact" => array(
					    "phone" => (isset($this->CustomerDetails->phone)? $this->CustomerDetails->phone : ""),
				        "email" => (isset($this->CustomerDetails->email)? $this->CustomerDetails->email : "")
				    ),
				    "billingAddress" => array(
					    "line1" => (isset($this->CustomerDetails->line1)? $this->CustomerDetails->line1 : ""),
				        "line2" => (isset($this->CustomerDetails->line2)? $this->CustomerDetails->line2 : ""),
				        "city" => (isset($this->CustomerDetails->city)? $this->CustomerDetails->city : ""),
				        "state" => (isset($this->CustomerDetails->state)? $this->CustomerDetails->state : ""),
				        "zipCode" => (isset($this->CustomerDetails->zipCode)? $this->CustomerDetails->zipCode : ""),
				        "countryCode" => (isset($this->CustomerDetails->countryCode)? $this->CustomerDetails->countryCode : "")
				    )
				)
			);
		}
		
	}