<?php
/**
 * Submits payment transactions to Authorize.NET using the AIM API
 * @author      Richard Castera
 * @link        http://www.richardcastera.com/projects/authorizenet-api-wrapper-class
 * @see         http://developer.authorize.net/
 * @license     GNU LESSER GENERAL PUBLIC LICENSE
 */

class AuthorizeNet {
  /**
   * Environment test/live.
   * @var String 
   */ 
  private $environment = 'test';

  /**
   * Contains the URLS for submitting a transaction.
   * @var Array
   */ 
  private $gatewayURL = array (
    'live'=>'https://secure.authorize.net/gateway/transact.dll',
    'test'=>'https://test.authorize.net/gateway/transact.dll', 
  );

  /**
   * Contains the keys for submitting a transaction type to Authorize.
   * @var Array
   */ 
  private $NVP = '';

  /**
   * Contains an array of values returned from processing the transaction.
   * @var Array
   */ 
  private $response = '';

  /**
   * The delimeter to seperate the values returned from the transaction.
   * @var String
   */ 
  private $responseDelimeter = '|';

  /**
   * Constructor - The API Login ID and Transaction Key together provide the merchant authentication required for access to the payment gateway.
   * @param String $apiLoginID - The merchant's unique API Login ID.
   * @param String $apiTransKey - The merchant's unique Transaction Key.
   */
  public function __construct($apiLoginID = '', $apiTransKey = '') {
    $this->NVP = array (
      'x_login'=>$this->truncateChars($apiLoginID, 20),
      'x_tran_key'=>$this->truncateChars($apiTransKey, 16),
    );
    $this->setupDefaults();
  }

  /**
   * Destructor.
   */ 
  public function __destruct() {
    unset($this);
  }

  /**
   * Sets up default transaction information.
   */ 
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
   * Sets the Environment of the transaction.
   * @param String $environment - Possible values: ('test', 'live'). 
   */
  public function setEnvironment($environment = 'test') {
    if(strtolower($environment) == 'test') {
      $this->environment = $this->gatewayURL['test'];  
    }
    else {
      $this->environment = $this->gatewayURL['live'];  
    }
  }

  /**
   * Returns the Environment of the transaction.
   * @return String - The environment set.
   */
  public function getEnvironment() {
    return $this->environment;  
  }

  /**
   * Sets the window of time after the submission of a transaction that a duplicate transaction can not be submitted.
   * @param Integer $seconds - Any value between 0 and 28800 (no comma).
   */
  public function submissionWindow($seconds = 28800) {
    $window = array(
      'x_duplicate_window'=>(int)$seconds,
    );
    $this->NVP = array_merge($this->NVP, $window);         
  }

  /**
   * Sets the type of Credit Card transaction.
   * @param String $transactionType - AUTH_CAPTURE (default), AUTH_ONLY, CAPTURE_ONLY, CREDIT, PRIOR_AUTH_CAPTURE, VOID.
   */
  public function setTransactionType($transactionType = 'AUTH_CAPTURE') {
    $type = array(
      'x_type'=>strtoupper($transactionType),
    );
    $this->NVP = array_merge($this->NVP, $type); 
  }

  /**
   * Sets the Payment Method.
   * @param String $paymentMethod - CC or ECHECK. 
   */
  public function setPaymentMethod($paymentMethod = 'CC') {
    $method = array(
      'x_method'=>strtoupper($paymentMethod),
    );
    $this->NVP = array_merge($this->NVP, $method); 
  }

  /**
   * Sets the Amount of the transaction. Up to 15 digits with a decimal point (no dollar symbol)
   * @param String/Integer/Float - $amount - 150.00.
   * @param Boolean - $wholeAmt - True to remove cents false, to keep it. 
   */
  public function setAmount($amount = 0, $wholeAmt) {
    $amt = array(
      'x_amount'=>$this->cleanAmt($amount, $wholeAmt),
    );
    $this->NVP = array_merge($this->NVP, $amt);  
  }

  /**
   * Sets the Customer's Credit Card Number. Between 13 and 16 digits without spaces.
   * @param String $number - The Credit Card Number. Dashes will be striped. 
   */
  public function setCCNumber($number = '') {
    $cc = array(
      'x_card_num'=>$this->cleanCCNumber($number),
    );
    $this->NVP = array_merge($this->NVP, $cc);   
  }

  /**
   * Sets the Customer's Credit Card Expiration Date. MMYY, MM/YY, MM-YY, MMYYYY, MM/YYYY, MM-YYYY
   * @param String $expiration - The Customer's Credit Card Expiration Date
   */
  public function setExpiration($expiration = '0000') {
    $exp = array(
      'x_exp_date'=>$this->cleanExpDate($expiration),
    );
    $this->NVP = array_merge($this->NVP, $exp); 
  }

  /**
   * Sets the Customer's card code. The three- or four-digit number on the back of a credit card (on the front for American Express).
   * @param String $cvv - The Customer's Credit Card Security Code
   */
  public function setCVV($cvv = '') {
    $security = array(
      'x_card_code'=>$cvv,
    );
    $this->NVP = array_merge($this->NVP, $security);   
  }

  /**
   * Sets the Payment Description. This is just a commment regarding the transaction for your reference.
   * @param String $description - The Payment description.
   */
  public function setPaymentDescription($description = '') {
    $desc = array(
      'x_description'=>$this->truncateChars($description, 255),
    );
    $this->NVP = array_merge($this->NVP, $desc); 
  }

  /**
   * Sets the First Name associated with the Customer's Billing Address. Up to 50 characters (no symbols)
   * @param String $firstName - The First Name associated with the Customer's Billing Address. 
   */
  public function setCustomerFirstName($firstName = '') {
    $first = array(
      'x_first_name'=>$this->truncateChars($firstName, 50),
    );
    $this->NVP = array_merge($this->NVP, $first); 
  }

  /**
   * Sets the Last Name associated with the Customer's Billing Address. Up to 50 characters (no symbols)
   * @param String $lastName - The Last Name associated with the Customer's Billing Address.
   * @example $Auth->setCustomerLastName('Castera');
   */
  public function setCustomerLastName($lastName = '') {
    $last = array(
      'x_last_name'=>$this->truncateChars($lastName, 50),
    );
    $this->NVP = array_merge($this->NVP, $last); 
  }

  /**
   * Sets the Company Name associated with the Customer's Billing. Up to 50 characters (no symbols)
   * @param String $companyName - The company name associated with the Customer's Billing Address. 
   */
  public function setCustomerCompany($companyName = '') {
    $company = array(
      'x_company'=>$this->truncateChars($companyName, 50),
    );
    $this->NVP = array_merge($this->NVP, $company); 
  }

  /**
   * Sets the Customer's Billing address. Up to 60 characters (no symbols)
   * @param String $customerAddress - The Customer's Billing address.
   */
  public function setCustomerAddress($customerAddress = '') {
    $address = array(
      'x_address'=>$this->truncateChars($customerAddress, 60),
    );
    $this->NVP = array_merge($this->NVP, $address); 
  }

  /**
   * Sets the Customer's Billing City. Up to 40 characters (no symbols)
   * @param String $customerCity - The Customer's Billing City. 
   */
  public function setCustomerCity($customerCity = '') {
    $city = array(
      'x_city'=>$this->truncateChars($customerCity, 40),
    );
    $this->NVP = array_merge($this->NVP, $city); 
  }

  /**
   * Sets the Customer's Billing State. Up to 40 characters (no symbols) or a valid two-character state code.
   * @param String $customerState - The Customer's Billing State.
   */
  public function setCustomerState($customerState = '') {
    $state = array(
      'x_state'=>$this->truncateChars($customerState, 40),
    );
    $this->NVP = array_merge($this->NVP, $state); 
  }

  /**
   * Sets the Customer's Billing Zip. Up to 20 characters (no symbols).
   * @param String $customerZip - The Customer's Billing Zip.
   */
  public function setCustomerZip($customerZip = '') {
    $zip = array(
      'x_zip'=>$this->truncateChars($customerZip, 20),
    );
    $this->NVP = array_merge($this->NVP, $zip); 
  }

  /**
   * Sets the Customer's Billing Country. Up to 60 characters (no symbols).
   * @param String $customerCountry - The Customer's Billing Country.
   * @example $Auth->setCustomerCountry('United States');
   */
  public function setCustomerCountry($customerCountry = '') {
    $country = array(
      'x_country'=>$this->truncateChars($customerCountry, 60),
    );
    $this->NVP = array_merge($this->NVP, $country); 
  }

  /**
   * Sets the Customer's Billing Phone. Up to 25 digits (no letters) Ex. 123-123-1234.
   * @param String $customerPhone - The Customer's Billing Phone.
   */
  public function setCustomerPhone($customerPhone = '000-000-0000') {
    $phone = array(
      'x_phone'=>$this->truncateChars($this->cleanPhoneNumber($customerPhone), 25),
    );
    $this->NVP = array_merge($this->NVP, $phone); 
  }

  /**
   * Sets the Customer's Billing Fax. Up to 25 digits (no letters) Ex. 123-123-1234.
   * @param String $customerFax - The Customer's Billing Fax.
   */
  public function setCustomerFax($customerFax = '000-000-0000') {
    $fax = array(
      'x_fax'=>$this->truncateChars($this->cleanPhoneNumber($customerFax), 25),
    );
    $this->NVP = array_merge($this->NVP, $fax); 
  }

  /**
   * Sets the Customer's Email Address. Up to 255 characters.
   * @param String $customerEmail - The Customer's Email Address.
   */
  public function setCustomerEmail($customerEmail = '') {
    $email = array(
      'x_email'=>$this->truncateChars($customerEmail, 255),
    );
    $this->NVP = array_merge($this->NVP, $email); 
  }

  /**
   * Sets the First Name associated with the Customer's Shipping Address. Up to 50 characters (no symbols)
   * @param String $firstName - The First Name associated with the Customer's Shipping Address.
   */
  public function setShippingFirstName($firstName = '') {
    $first = array(
      'x_ship_to_first_name'=>$this->truncateChars($firstName, 50),
    );
    $this->NVP = array_merge($this->NVP, $first); 
  }

  /**
   * Sets the Last Name associated with the Customer's Shipping Address. Up to 50 characters (no symbols)
   * @param String $lastName - The Last Name associated with the Customer's Shipping Address. 
   */
  public function setShippingLastName($lastName = '') {
    $last = array(
      'x_ship_to_last_name'=>$this->truncateChars($lastName, 50),
    );
    $this->NVP = array_merge($this->NVP, $last); 
  }

  /**
   * Sets the Company Name associated with the Customer's Shipping. Up to 50 characters (no symbols)
   * @param String $companyName - The Company name associated with the Customer's Shipping Address.
   */
  public function setShippingCompany($companyName = '') {
    $company = array(
      'x_ship_to_company'=>$this->truncateChars($companyName, 50),
    );
    $this->NVP = array_merge($this->NVP, $company); 
  }

  /**
   * Sets the Customer's Shipping address. Up to 60 characters (no symbols)
   * @param String $shippingAddress - The Customer's Shipping address.
   */
  public function setShippingAddress($shippingAddress = '') {
    $address = array(
      'x_ship_to_address'=>$this->truncateChars($shippingAddress, 60),
    );
    $this->NVP = array_merge($this->NVP, $address); 
  }

  /**
   * Sets the Customer's Shipping City. Up to 40 characters (no symbols)
   * @param String $shippingCity - The Customer's Shipping City.
   */
  public function setShippingCity($shippingCity = '') {
    $city = array(
      'x_ship_to_city'=>$this->truncateChars($shippingCity, 40),
    );
    $this->NVP = array_merge($this->NVP, $city); 
  }

  /**
   * Sets the Customer's Shipping State. Up to 40 characters (no symbols) or a valid two-character state code.
   * @param String $shippingState - The Customer's Shipping State.
   */
  public function setShippingState($shippingState = '') {
    $state = array(
      'x_ship_to_state'=>$this->truncateChars($shippingState, 40),
    );
    $this->NVP = array_merge($this->NVP, $state); 
  }

  /**
   * Sets the Customer's Shipping Zip. Up to 20 characters (no symbols).
   * @param String $shippingZip - The Customer's Shipping Zip.
   */
  public function setShippingZip($shippingZip = '') {
    $zip = array(
      'x_ship_to_zip'=>$this->truncateChars($shippingZip, 20),
    );
    $this->NVP = array_merge($this->NVP, $zip); 
  }

  /**
   * Sets the Customer's Shipping Country. Up to 60 characters (no symbols).
   * @param String $shippingCountry - The Customer's Shipping Country.
   */
  public function setShippingCountry($shippingCountry = '') {
    $country = array(
      'x_ship_to_country'=>$this->truncateChars($shippingCountry, 60),
    );
    $this->NVP = array_merge($this->NVP, $country); 
  }

  /**
   * If set to TRUE, an email will be sent to the customer after the transaction is processed. If FALSE, no email is sent to the customer.
   * @param String $sendReceipt - Indicate whether an email receipt should be sent to the customer.
   */
  public function sendCustomerReceipt($sendReceipt = TRUE) {
    $receipt = array(
      'x_email_customer'=>(int)$sendReceipt,
    );
    $this->NVP = array_merge($this->NVP, $receipt); 
  }

  /**
   * Sets a Merchant-defined field to submit to Authorize.
   * @param String $name - The name of the custom field.
   * @param String $value - The value of the custom field.
   */
  public function setCustomField($name = '', $value = '') {
    $custom = array(
      $name=>(string)$value,
    );
    $this->NVP = array_merge($this->NVP, $custom); 
  }

  /**
   * This get the NVP's that will be sent to Authorize.
   * @return String - A string of NVP's.
   */
  private function getNVP() {
    $post = '';
    foreach($this->NVP as $key=>$value) { 
      $post .= "$key=" . urlencode($value) . "&";
    }
    return (string)rtrim($post, "& ");
  }

  /**
   * Sends the request to Authorize for processing.
   * @return Boolean - True if the transaction was successful False, if not.
   */
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
   * Gets the response from Authorize.
   * @return Array/String - Returns an array of Authorize's response or empty string if not return.
   */
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
   * Formats the monetary amount sent to Authorize.
   * @param String/Integer/Float $amount - The amount to clean.
   * @param Boolean $wholeAmt - True to remove cents false, to keep it. 
   * @return Integer/Float - Returns the monetary amount formatted based on the $wholeAmt parameter.
   */
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
   * Removes all characters from the credit card number except for numbers.
   * @param String $cc - The crdeit card number. 
   * @return String - Returns the credit card number with only numeric characters.
   */
  private function cleanCCNumber($cc = '') {
    $cc = preg_replace('/[^0-9]/', '', trim($cc));
    return (string)$cc;
  }

  /**
   * Removes all characters from the telephone number except for numbers and dashes.
   * @param String $phone - The phone number.
   * @return String - Returns the phone number with dashes.
   */
  private function cleanPhoneNumber($phone = '') {
    $phone = preg_replace('/[^0-9-]/', '', trim($phone));
    return (string)$phone;
  }

  /**
   * Removes all characters from the Expiration date except for numbers, slashes and dashes.
   * @param String $exp - The expiration date.
   * @return String - Returns the expiration date formatted for authorize.
   */
  private function cleanExpDate($exp = '') {
    $exp = preg_replace('/[^0-9]-\//', '', trim($exp));
    return (string)$exp;
  }

  /**
   * Used to debug values that will be sent to Authorize.
   * @param String $string - The string to truncate.
   * @param Integer $limit - The amount to truncate. 
   * @return Returns the string truncated.
   */
  private function truncateChars($string = '', $limit = 0) {
    for($i = 0; $i <= $limit AND $i < strlen($string); $i++){
      $output .= $string[$i];
    }
    return (string)trim($output);
  }

  /**
   * Used to debug values that will be sent to Authorize.
   * @param String $type - Valid values are 'array' or 'string'.
   * @return This returns either and array of the NVP's or a string based on the parameter chosen.
   */
  public function debugNVP($type = 'array') {
    if($type == 'array') {
      return $this->NVP;   
    }
    else {
      return $this->getNVP();
    }
  }    
}
