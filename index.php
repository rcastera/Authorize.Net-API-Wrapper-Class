<!DOCTYPE html>
<html>
<head>
<title>Authorize.Net Example.</title>
<meta charset=utf-8 />
<meta http-equiv="keywords" content="" />
<meta http-equiv="description" content="" />
<!-- Le HTML5 shim, for IE6-8 support of HTML elements -->
<!--[if lt IE 9]>
  <script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<style>
  body {
    margin: 0px;
    padding: 0px;
    top: 0px;
    right: 0px;
    bottom: 0px;
    left: 0px;
    font-family: serif;
  }
  header {
    background-color: #000;
  }
    header h1 {
      margin: 0px;
      padding: 10px;
      color: #fff;
    }
  #content {
    margin: 20px 0px 0px 20px;
  }
</style>
</head>
<body>
  <header id="header">
    <h1>Authorize.Net</h1>
  </header>

  <div id="content">
    <?php
      require_once('Class.AuthorizeNet.php');

      /** 
       * Create new Regular Transaction.
       */
      $Auth = new AuthorizeNet('YOUR_LOGIN_ID', 'YOUR_TRANS_KEY');    // Merchant's unique API Login ID and Transaction Key
                  
      $Auth->setEnvironment('test');                                  // 'test' (default) or 'live'
      $Auth->setTransactionType('AUTH_CAPTURE');                      // AUTH_CAPTURE (default), AUTH_ONLY, CAPTURE_ONLY, CREDIT, PRIOR_AUTH_CAPTURE, VOID
      $Auth->setPaymentMethod('CC');                                  // Payment Method - CC (default) or ECHECK

      $Auth->setAmount('160.25', TRUE);                               // Amount
      $Auth->setCCNumber('378282246310005');                          // Credit card number
      $Auth->setCVV('4685');                                          // The three- or four-digit number on the back of a credit card
      $Auth->setExpiration('11/12');                                  // Expiration date - MMYY, MM/YY, MM-YY, MMYYYY, MM/YYYY, MM-YYYY
      $Auth->setPaymentDescription('New Sale on Product 232');        // Description of the Transaction.

      $Auth->setCustomerFirstName('Richard');                         // Customer First Name
      $Auth->setCustomerLastName('Castera');                          // Customer Last Name
      $Auth->setCustomerCompany('Nice Emails');                       // Customer's Company Name
      $Auth->setCustomerAddress('589 8th Ave Suite 10');              // Customer's Billing Address
      $Auth->setCustomerCity('New York');                             // Customer's Billing City
      $Auth->setCustomerState('NY');                                  // Customer's Billing State
      $Auth->setCustomerZip('10018');                                 // Customer's Billing Zip
      $Auth->setCustomerCountry('United States');                     // Customer's Billing Country
      $Auth->setCustomerPhone('212-123-1234');                        // Customer's Billing Phone Number
      $Auth->setCustomerFax('212-123-4567');                          // Customer's Billing Fax Number
      $Auth->setCustomerEmail('email@gmail.com');                     // Customer's Email Address
      $Auth->sendCustomerReceipt(FALSE);                              // Allow Authorize to send it's receipt to the Customer

      $Auth->setShippingFirstName('Richard');                         // Shipping First Name
      $Auth->setShippingLastName('Castera');                          // Shipping Last Name
      $Auth->setShippingCompany('NiceEmails');                        // Shipping Company
      $Auth->setShippingAddress('589 8th Ave. Suite 10');             // Shipping Address
      $Auth->setShippingCity('New York');                             // Shipping City 
      $Auth->setShippingState('NY');                                  // Shipping State
      $Auth->setShippingZip('10018');                                 // Shipping Zip
      $Auth->setShippingCountry('United States');                     // Shipping Country.

      if($Auth->processTransaction()):                                // Process the transaction by sending the values to Authorize.
          echo('Transaction Processed Successfully!');
      else:
          echo('Transaction could not be processed at this time.');
      endif;

      echo('<h2>Name Value Pair String:</h2>');
      echo('<pre>'); 
      print_r($Auth->debugNVP('array'));                              // Output the Name Value Pairs that gets sent to Authorize. Valid values are 'array' or blank for nvp string.
      echo('</pre>');

      echo('<h2>Response From Authorize:</h2>');
      echo('<pre>'); 
      print_r($Auth->getResponse());                                  // Get the response.
      echo('</pre>');

      unset($Auth);                                                   // Destroy the Object.
      ?>    
  </div>

  <footer id="footer">

  </footer>

</body>
</html>