<?php
/**
 * @uses        Submits payment transactions to Authorize.NET using the AIM API
 * @author      Richard Castera
 * @link        http://www.richardcastera.com/projects/authorize-net-api-wrapper-class-in-php
 * @date        3/19/2010
 * @version     0.04
 * @copyright   Richard Castera 2010 © Copyright 
 * @access      Public
 * @comments    This class can submit transactions to Authoirze.NET. We can send single payments or recurring.
 * @see         http://developer.authorize.net/
 * @license     GNU LESSER GENERAL PUBLIC LICENSE
 **/

class AuthNet {


  /**
   * @uses	  Sets the environment of the transaction.
   * @access	private
   * @var		  String 
   **/ 
  private $environment = 'test'; // default value.


  /**
   * @uses	  Contains the URLS for submitting a transaction.
   * @access	private
   * @var		  Array 
   **/ 
  private $gatewayURL = array (
    'live'=>'https://secure.authorize.net/gateway/transact.dll',
    'test'=>'https://test.authorize.net/gateway/transact.dll', 
  );


  /**
   * @uses	  Contains the keys for submitting a transaction type to Authorize.
   * @access	private
   * @var		  Array 
   **/ 
  private $NVP = '';


  /**
   * @uses	  Contains an array of values returned from processing the transaction.
   * @access	private
   * @var		  Array 
   **/ 
  private $response = '';


  /**
   * @uses	  The delimeter to seperate the values returned from the transaction.
   * @access	private
   * @var		  String 
   **/ 
  private $responseDelimeter = '|';





  /**
   * @uses		    Constructor - The API Login ID and Transaction Key together provide the merchant authentication required for access to the payment gateway.
   * @access		  Public
   * @required 	  Required
   * @param   	  String $apiLoginID - The merchant's unique API Login ID.
   * @param   	  String $apiTransKey - The merchant's unique Transaction Key. 
   * @return  	  None.
   * @example		  $Auth = new AuthNet('API_LOGIN_ID', 'API_TRANSACTION_KEY');
   **/ 
  public function __construct($apiLoginID = '', $apiTransKey = '') {
    $this->NVP = array (
      'x_login'=>$this->truncateChars($apiLoginID, 20),
      'x_tran_key'=>$this->truncateChars($apiTransKey, 16),
    );
  
    // Setup some default values.
    $this->setupDefaults();
  }


  /**
   * @uses	      Destructor.
   * @access	    Public
   * @param       None. 
   * @return      None.
   * @example	    unset($obj);
   **/ 
  public function __destruct() {
    unset($this);
  }


  /**
   * @uses	      Sets up default transaction information.
   * @access	    Private
   * @param       None. 
   * @return      None.
   * @example	    $this->setupDefaults();
   **/ 
  private function setupDefaults() {
    $defaults = array(
      // Indicates to the system the set of fields that will be included in the response: 3.0 is the default version. 3.1 
      // allows the merchant to utilize the Card Code feature, and is the current standard version.
      'x_version'=>'3.1', 
  
      // In order to receive a delimited response from the payment gateway, this field must be submitted with a value
      // of TRUE or the merchant has to configure a delimited response through the Merchant Interface. 
      'x_delim_data'=>'TRUE',
  
      // The character that is used to separate fields in the transaction response. The payment gateway will use the
      // character passed in this field or the value stored in the Merchant Interface if no value is passed. If this field is passed,
      // and the value is null, it will override the value stored in the Merchant Interface and there is no delimiting character in the transaction response.
      // A single symbol Ex. , (comma) | (pipe) " (double quotes) ' (single quote) : (colon) ; (semicolon) / (forward slash) \ (back slash) - (dash) * (star)
      'x_delim_char'=>$this->responseDelimeter,
      
      // SIM applications use relay response. Set this to false if you are using AIM.
      'x_relay_response'=>'FALSE',
      
      // IP address of the customer initiating the transaction. If this value is not passed, it will default to 255.255.255.255.
      'x_customer_ip'=>$_SERVER['REMOTE_ADDR'],
    );
  
    $this->NVP = array_merge($this->NVP, $defaults); 
  }


  /**
   * @uses	      Sets the Environment of the transaction.
   * @access	    Public
   * @param       String $environment - Possible values: ('test', 'live'). 
   * @return      None.
   * @example	    $Auth->setEnvironment('test');
   **/
  public function setEnvironment($environment = 'test') {
    if(strtolower($environment) == 'test') {
      $this->environment = $this->gatewayURL['test'];  
    }
    else {
      $this->environment = $this->gatewayURL['live'];  
    }
  }


  /**
   * @uses	      Returns the Environment of the transaction.
   * @access	    Public
   * @param       None. 
   * @return      String - The environment set.
   * @example	    $Auth->getEnvironment();
   **/
  public function getEnvironment() {
    return $this->environment;  
  }


  /**
   * @uses	      Sets the window of time after the submission of a transaction that a duplicate transaction can not be submitted.
   * @access	    Public
   * @required    Optional
   * @param       Integer $seconds - Any value between 0 and 28800 (no comma). 
   * @return      None.
   * @example	    $Auth->submissionWindow(60); // 1 minute
   **/
  public function submissionWindow($seconds = 28800) {
    $window = array(
      'x_duplicate_window'=>(int)$seconds,
    );
  
    $this->NVP = array_merge($this->NVP, $window);         
  }


  /**
   * @uses	      Sets the type of Credit Card transaction.
   * @access	    Public
   * @required    Required
   * @param       String $transactionType - AUTH_CAPTURE (default), AUTH_ONLY, CAPTURE_ONLY, CREDIT, PRIOR_AUTH_CAPTURE, VOID. 
   * @return      None.
   * @example	    $Auth->setTransactionType('AUTH_CAPTURE');
   **/
  public function setTransactionType($transactionType = 'AUTH_CAPTURE') {
    $type = array(
      'x_type'=>strtoupper($transactionType),
    );
  
    $this->NVP = array_merge($this->NVP, $type); 
  }


  /**
   * @uses	      Sets the Payment Method.
   * @access	    Public
   * @required    Optional
   * @param       String $paymentMethod - CC or ECHECK. 
   * @return      None.
   * @example	    $Auth->setPaymentMethod('CC');
   **/
  public function setPaymentMethod($paymentMethod = 'CC') {
    $method = array(
      'x_method'=>strtoupper($paymentMethod),
    );
  
    $this->NVP = array_merge($this->NVP, $method); 
  }


  /**
   * @uses	      Sets the Amount of the transaction. Up to 15 digits with a decimal point (no dollar symbol)
   * @access	    Public
   * @required    Required
   * @param       String/Integer/Float - $amount - 150.00.
   * @param       Boolean - $wholeAmt - True to remove cents false, to keep it. 
   * @return      None.
   * @example	    $Auth->setAmount(150.00);
   **/
  public function setAmount($amount = 0, $wholeAmt) {
    $amt = array(
      'x_amount'=>$this->cleanAmt($amount, $wholeAmt),
    );
  
    $this->NVP = array_merge($this->NVP, $amt);  
  }


  /**
   * @uses	      Sets the Customer's Credit Card Number. Between 13 and 16 digits without spaces.
   * @access	    Public
   * @required    Required
   * @param       String $number - The Credit Card Number. Dashes will be striped. 
   * @return      None.
   * @example	    $Auth->setCCNumber('1234-1234-1234-1234');
   **/
  public function setCCNumber($number = '') {
    $cc = array(
      'x_card_num'=>$this->cleanCCNumber($number),
    );
  
    $this->NVP = array_merge($this->NVP, $cc);   
  }


  /**
   * @uses	      Sets the Customer's Credit Card Expiration Date. MMYY, MM/YY, MM-YY, MMYYYY, MM/YYYY, MM-YYYY
   * @access	    Public
   * @required    Required
   * @param       String $expiration - The Customer's Credit Card Expiration Date
   * @return      None.
   * @example	    $Auth->setExpiration('03/12');
   **/
  public function setExpiration($expiration = '0000') {
    $exp = array(
      'x_exp_date'=>$this->cleanExpDate($expiration),
    );
  
    $this->NVP = array_merge($this->NVP, $exp); 
  }


  /**
   * @uses	      Sets the Customer's card code. The three- or four-digit number on the back of a credit card (on the front for American Express).
   * @access	    Public
   * @required    Optional
   * @param       String $cvv - The Customer's Credit Card Security Code
   * @return      None.
   * @example	    $Auth->setCVV('0000');
   **/
  public function setCVV($cvv = '') {
    $security = array(
      'x_card_code'=>$cvv,
    );
  
    $this->NVP = array_merge($this->NVP, $security);   
  }


  /**
   * @uses	      Sets the Payment Description. This is just a commment regarding the transaction for your reference.
   * @access	    Public
   * @required    Optional
   * @param       String $description - The Payment description.
   * @return      None.
   * @example	    $Auth->setPaymentDescription('Purchased product number 34324');
   **/
  public function setPaymentDescription($description = '') {
    $desc = array(
      'x_description'=>$this->truncateChars($description, 255),
    );
  
    $this->NVP = array_merge($this->NVP, $desc); 
  }


  /**
   * @uses	      Sets the First Name associated with the Customer's Billing Address. Up to 50 characters (no symbols)
   * @access	    Public
   * @required    Optional
   * @param       String $firstName - The First Name associated with the Customer's Billing Address. 
   * @return      None.
   * @example	    $Auth->setCustomerFirstName('Richard');
   **/
  public function setCustomerFirstName($firstName = '') {
    $first = array(
      'x_first_name'=>$this->truncateChars($firstName, 50),
    );
  
    $this->NVP = array_merge($this->NVP, $first); 
  }


  /**
   * @uses	      Sets the Last Name associated with the Customer's Billing Address. Up to 50 characters (no symbols)
   * @access	    Public
   * @required    Optional
   * @param       String $lastName - The Last Name associated with the Customer's Billing Address. 
   * @return      None.
   * @example	    $Auth->setCustomerLastName('Castera');
   **/
  public function setCustomerLastName($lastName = '') {
    $last = array(
      'x_last_name'=>$this->truncateChars($lastName, 50),
    );
  
    $this->NVP = array_merge($this->NVP, $last); 
  }


  /**
   * @uses	      Sets the Company Name associated with the Customer's Billing. Up to 50 characters (no symbols)
   * @access	    Public
   * @required    Optional
   * @param       String $companyName - The company name associated with the Customer's Billing Address. 
   * @return      None.
   * @example	    $Auth->setCustomerCompany('SankyNet');
   **/
  public function setCustomerCompany($companyName = '') {
    $company = array(
      'x_company'=>$this->truncateChars($companyName, 50),
    );
  
    $this->NVP = array_merge($this->NVP, $company); 
  }


  /**
   * @uses	      Sets the Customer's Billing address. Up to 60 characters (no symbols)
   * @access	    Public
   * @required    Optional
   * @param       String $customerAddress - The Customer's Billing address. 
   * @return      None.
   * @example	    $Auth->setCustomerAddress('589 8th Ave. Suite 10');
   **/
  public function setCustomerAddress($customerAddress = '') {
    $address = array(
      'x_address'=>$this->truncateChars($customerAddress, 60),
    );
  
    $this->NVP = array_merge($this->NVP, $address); 
  }


  /**
   * @uses	      Sets the Customer's Billing City. Up to 40 characters (no symbols)
   * @access	    Public
   * @required    Optional
   * @param       String $customerCity - The Customer's Billing City. 
   * @return      None.
   * @example	    $Auth->setCustomerCity('New York');
   **/
  public function setCustomerCity($customerCity = '') {
    $city = array(
      'x_city'=>$this->truncateChars($customerCity, 40),
    );
  
    $this->NVP = array_merge($this->NVP, $city); 
  }


  /**
   * @uses	      Sets the Customer's Billing State. Up to 40 characters (no symbols) or a valid two-character state code.
   * @access	    Public
   * @required    Optional
   * @param       String $customerState - The Customer's Billing State. 
   * @return      None.
   * @example	    $Auth->setCustomerState('NY');
   **/
  public function setCustomerState($customerState = '') {
    $state = array(
      'x_state'=>$this->truncateChars($customerState, 40),
    );
  
    $this->NVP = array_merge($this->NVP, $state); 
  }


  /**
   * @uses	      Sets the Customer's Billing Zip. Up to 20 characters (no symbols).
   * @access	    Public
   * @required    Optional
   * @param       String $customerZip - The Customer's Billing Zip. 
   * @return      None.
   * @example	    $Auth->setCustomerZip('10018');
   **/
  public function setCustomerZip($customerZip = '') {
    $zip = array(
      'x_zip'=>$this->truncateChars($customerZip, 20),
    );
  
    $this->NVP = array_merge($this->NVP, $zip); 
  }


  /**
   * @uses	      Sets the Customer's Billing Country. Up to 60 characters (no symbols).
   * @access	    Public
   * @required    Optional
   * @param       String $customerCountry - The Customer's Billing Country. 
   * @return      None.
   * @example	    $Auth->setCustomerCountry('United States');
   **/
  public function setCustomerCountry($customerCountry = '') {
    $country = array(
      'x_country'=>$this->truncateChars($customerCountry, 60),
    );
  
    $this->NVP = array_merge($this->NVP, $country); 
  }


  /**
   * @uses	      Sets the Customer's Billing Phone. Up to 25 digits (no letters) Ex. 123-123-1234.
   * @access	    Public
   * @required    Optional
   * @param       String $customerPhone - The Customer's Billing Phone. 
   * @return      None.
   * @example	    $Auth->setCustomerPhone('212-123-4567');
   **/
  public function setCustomerPhone($customerPhone = '000-000-0000') {
    $phone = array(
      'x_phone'=>$this->truncateChars($this->cleanPhoneNumber($customerPhone), 25),
    );
  
    $this->NVP = array_merge($this->NVP, $phone); 
  }


  /**
   * @uses	      Sets the Customer's Billing Fax. Up to 25 digits (no letters) Ex. 123-123-1234.
   * @access	    Public
   * @required    Optional
   * @param       String $customerFax - The Customer's Billing Fax. 
   * @return      None.
   * @example	    $Auth->setCustomerFax('212-123-4567');
   **/
  public function setCustomerFax($customerFax = '000-000-0000') {
    $fax = array(
      'x_fax'=>$this->truncateChars($this->cleanPhoneNumber($customerFax), 25),
    );
  
    $this->NVP = array_merge($this->NVP, $fax); 
  }


  /**
   * @uses	      Sets the Customer's Email Address. Up to 255 characters.
   * @access	    Public
   * @required    Optional
   * @param       String $customerEmail - The Customer's Email Address. 
   * @return      None.
   * @example	    $Auth->setCustomerEmail('richard.castera@gmail.com');
   **/
  public function setCustomerEmail($customerEmail = '') {
    $email = array(
      'x_email'=>$this->truncateChars($customerEmail, 255),
    );
  
    $this->NVP = array_merge($this->NVP, $email); 
  }


  /**
   * @uses	      Sets the First Name associated with the Customer's Shipping Address. Up to 50 characters (no symbols)
   * @access	    Public
   * @required    Optional
   * @param       String $firstName - The First Name associated with the Customer's Shipping Address. 
   * @return      None.
   * @example	    $Auth->setShippingFirstName('Richard');
   **/
  public function setShippingFirstName($firstName = '') {
    $first = array(
      'x_ship_to_first_name'=>$this->truncateChars($firstName, 50),
    );
  
    $this->NVP = array_merge($this->NVP, $first); 
  }


  /**
   * @uses	      Sets the Last Name associated with the Customer's Shipping Address. Up to 50 characters (no symbols)
   * @access	    Public
   * @required    Optional
   * @param       String $lastName - The Last Name associated with the Customer's Shipping Address. 
   * @return      None.
   * @example	    $Auth->setShippingLastName('Castera');
   **/
  public function setShippingLastName($lastName = '') {
    $last = array(
      'x_ship_to_last_name'=>$this->truncateChars($lastName, 50),
    );
  
    $this->NVP = array_merge($this->NVP, $last); 
  }


  /**
   * @uses	      Sets the Company Name associated with the Customer's Shipping. Up to 50 characters (no symbols)
   * @access	    Public
   * @required    Optional
   * @param       String $companyName - The Company name associated with the Customer's Shipping Address. 
   * @return      None.
   * @example	    $Auth->setShippingCompany('SankyNet');
   **/
  public function setShippingCompany($companyName = '') {
    $company = array(
      'x_ship_to_company'=>$this->truncateChars($companyName, 50),
    );
  
    $this->NVP = array_merge($this->NVP, $company); 
  }


  /**
   * @uses	      Sets the Customer's Shipping address. Up to 60 characters (no symbols)
   * @access	    Public
   * @required    Optional
   * @param       String $shippingAddress - The Customer's Shipping address. 
   * @return      None.
   * @example	    $Auth->setShippingAddress('589 8th Ave. Suite 10');
   **/
  public function setShippingAddress($shippingAddress = '') {
    $address = array(
      'x_ship_to_address'=>$this->truncateChars($shippingAddress, 60),
    );
  
    $this->NVP = array_merge($this->NVP, $address); 
  }


  /**
   * @uses	      Sets the Customer's Shipping City. Up to 40 characters (no symbols)
   * @access	    Public
   * @required    Optional
   * @param       String $shippingCity - The Customer's Shipping City. 
   * @return      None.
   * @example	    $Auth->setShippingCity('New York');
   **/
  public function setShippingCity($shippingCity = '') {
    $city = array(
      'x_ship_to_city'=>$this->truncateChars($shippingCity, 40),
    );
  
    $this->NVP = array_merge($this->NVP, $city); 
  }


  /**
   * @uses	      Sets the Customer's Shipping State. Up to 40 characters (no symbols) or a valid two-character state code.
   * @access	    Public
   * @required    Optional
   * @param       String $shippingState - The Customer's Shipping State. 
   * @return      None.
   * @example	    $Auth->setShippingState('NY');
   **/
  public function setShippingState($shippingState = '') {
    $state = array(
      'x_ship_to_state'=>$this->truncateChars($shippingState, 40),
    );
  
    $this->NVP = array_merge($this->NVP, $state); 
  }


  /**
   * @uses	      Sets the Customer's Shipping Zip. Up to 20 characters (no symbols).
   * @access	    Public
   * @required    Optional
   * @param       String $shippingZip - The Customer's Shipping Zip. 
   * @return      None.
   * @example	    $Auth->setShippingZip('10018');
   **/
  public function setShippingZip($shippingZip = '') {
    $zip = array(
      'x_ship_to_zip'=>$this->truncateChars($shippingZip, 20),
    );
  
    $this->NVP = array_merge($this->NVP, $zip); 
  }


  /**
   * @uses	      Sets the Customer's Shipping Country. Up to 60 characters (no symbols).
   * @access	    Public
   * @required    Optional
   * @param       String $shippingCountry - The Customer's Shipping Country. 
   * @return      None.
   * @example	    $Auth->setShippingCountry('United States');
   **/
  public function setShippingCountry($shippingCountry = '') {
    $country = array(
      'x_ship_to_country'=>$this->truncateChars($shippingCountry, 60),
    );
  
    $this->NVP = array_merge($this->NVP, $country); 
  }


  /**
   * @uses	      If set to TRUE, an email will be sent to the customer after the transaction is processed. If FALSE, no email is sent to the customer.
   * @access	    Public
   * @required    Optional
   * @param       String $sendReceipt - Indicate whether an email receipt should be sent to the customer.
   * @return      None.
   * @example	    $Auth->sendCustomerReceipt(TRUE);
   **/
  public function sendCustomerReceipt($sendReceipt = TRUE) {
    $receipt = array(
      'x_email_customer'=>(int)$sendReceipt,
    );
  
    $this->NVP = array_merge($this->NVP, $receipt); 
  }


  /**
   * @uses	      Sets a Merchant-defined field to submit to Authorize.
   * @access	    Public
   * @required    Optional
   * @param       String $name - The name of the custom field.
   * @param       String $value - The value of the custom field.
   * @return      None.
   * @example	    $Auth->setCustomField('origin_code', 'hpdon');
   **/
  public function setCustomField($name = '', $value = '') {
    $custom = array(
      $name=>(string)$value,
    );
  
    $this->NVP = array_merge($this->NVP, $custom); 
  }


  /**
   * @uses	      This get the NVP's that will be sent to Authorize.
   * @access	    Private
   * @param       None. 
   * @return      String - A string of NVP's.
   * @example	    $this->getNVP();
   **/
  private function getNVP() {
    $post = '';
    foreach($this->NVP as $key=>$value) { 
      $post .= "$key=" . urlencode($value) . "&";
    }
    return (string)rtrim($post, "& ");
  }


  /**
   * @uses	      Sends the request to Authorize for processing.
   * @access	    Public
   * @param       None.
   * @return      Boolean - True if the transaction was successful False, if not.
   * @example	    $Auth->processTransaction();
   **/
  public function processTransaction() {
    // Uses the CURL library for php to establish a connection,
    // submit the post, and record the response.
    if(function_exists('curl_init') && extension_loaded('curl')) {
      $request = curl_init($this->getEnvironment());              // Initiate curl object
      curl_setopt($request, CURLOPT_HEADER, 0);                   // Set to 0 to eliminate header info from response
      curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);           // Returns response data instead of TRUE(1)
      curl_setopt($request, CURLOPT_POSTFIELDS, $this->getNVP()); // Use HTTP POST to send the data
      curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);       // Uncomment this line if you get no gateway response.
      $postResponse = curl_exec($request);                        // Execute curl post and store results in $post_response
  
      // Additional options may be required depending upon your server configuration
      // you can find documentation on curl options at http://www.php.net/curl_setopt
      curl_close($request); // close curl object
  
      // Get the response.
      $this->response = $postResponse;    
      return TRUE;
    }
    else {
      return FALSE;
    }
  }


  /**
   * @uses	      Gets the response from Authorize.
   * @access	    Public
   * @param       None.
   * @return      Array/String - Returns an array of Authorize's response or empty string if not return.
   * @example	    $Auth->getResponse();
   **/
  public function getResponse() {
    if($this->response) {
      $response = explode($this->responseDelimeter, $this->response);
  
      if(is_array($response)) {
        return $response;
      }
      else {
        return '';
      }     
    }
    else {
      return '';
    }
  }


  /**
   * @uses	      Formats the monetary amount sent to Authorize.
   * @access	    Private
   * @param       String/Integer/Float $amount - The amount to clean.
   * @param       Boolean $wholeAmt - True to remove cents false, to keep it. 
   * @return      Integer/Float - Returns the monetary amount formatted based on the $wholeAmt parameter.
   * @example	    $this->cleanAmt();
   **/
  private function cleanAmt($amount = 0, $wholeAmt = FALSE) {
    if($wholeAmt) {
      $amount = preg_replace('/[^0-9.]/', '', trim($amount));
      return (int)$amount;    
    }
    else {
      $amount = preg_replace('/[^0-9.]/', '', trim($amount));
      return (float)$amount;
    }
  }    


  /**
   * @uses	      Removes all characters from the credit card number except for numbers.
   * @access	    Private
   * @param       String $cc - The crdeit card number. 
   * @return      String - Returns the credit card number with only numeric characters.
   * @example	    $this->cleanCCNumber('5412-2232-2323-3443');
   **/
  private function cleanCCNumber($cc = '') {
    $cc = preg_replace('/[^0-9]/', '', trim($cc));
    return (string)$cc;
  }


  /**
   * @uses	      Removes all characters from the telephone number except for numbers and dashes.
   * @access	    Private
   * @param       String $phone - The phone number. 
   * @return      String - Returns the phone number with dashes.
   * @example	    $this->cleanPhoneNumber('718-232-2323');
   **/
  private function cleanPhoneNumber($phone = '') {
    $phone = preg_replace('/[^0-9-]/', '', trim($phone));
    return (string)$phone;
  }


  /**
   * @uses	      Removes all characters from the Expiration date except for numbers, slashes and dashes.
   * @access	    Private
   * @param       String $exp - The expiration date. 
   * @return      String - Returns the expiration date formatted for authorize.
   * @example	    $this->cleanExpDate('718-232-2323');
   **/
  private function cleanExpDate($exp = '') {
    $exp = preg_replace('/[^0-9]-\//', '', trim($exp));
    return (string)$exp;
  }


  /**
   * @uses	      Used to debug values that will be sent to Authorize.
   * @access	    Private
   * @param       String $string - The string to truncate.
   * @param       Integer $limit - The amount to truncate. 
   * @return      Returns the string truncated.
   * @example	    $this->truncateChars('Richard Castera', 10);
   **/
  private function truncateChars($string = '', $limit = 0) {
    for($i = 0; $i <= $limit AND $i < strlen($string); $i++){
      $output .= $string[$i];
    }
    return (string)trim($output);
  }


  /**
   * @uses	      Used to debug values that will be sent to Authorize.
   * @access	    Public
   * @param       String $type - Valid values are 'array' or 'string'.
   * @return      This returns either and array of the NVP's or a string based on the parameter chosen.
   * @example	    $Auth->debugNVP('array');
   **/
  public function debugNVP($type = 'array') {
    if($type == 'array') {
      return $this->NVP;   
    }
    else {
      return $this->getNVP();
    }
  }    
}
?>