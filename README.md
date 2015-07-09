PAYMILL - xtCommerce 4 /Veyton
====================

xtCommerce (Version 4 / Veyton) Plugin for PAYMILL credit card and elv payments

## Your Advantages
* PCI DSS compatibility
* Payment means: Credit Card (Visa, Visa Electron, Mastercard, Maestro, Diners, Discover, JCB, AMEX, China Union Pay), Direct Debit (ELV)
* Optional fast checkout configuration allowing your customers not to enter their payment detail over and over during checkout
* Improved payment form with visual feedback for your customers
* Supported Languages: German, English, Spanish, French, Italian, Portuguese
* Backend Log with custom View accessible from your shop backend

# PayFrame 
 
We’ve introduced a “payment form” option for easier compliance with PCI 
requirements. 
In addition to having a payment form directly integrated in your checkout page, you 
can use our embedded PayFrame solution to ensure that payment data never 
touches your website. 
 
To learn more about the benefits of PayFrame, please visit our [FAQ](https://www.paymill.com/en/faq/how-does-paymills-payframe-solution-work "FAQ").

# Installation

    Download the Zip here: https://github.com/Paymill/Paymill-xtCommerce-4/archive/master.zip

# Configuration

Afterwards perform the following steps in your store backend:

* Install the plugin
* Activate the plugin
* Configure the PAYMILL payment method by inserting your public and private test or live keys
* Activate the PAYMILL payment method

# Refund
To refund the order you simply have to change the order status to "Refund / Chargeback (PAYMILL)" and a online refund will be processed.

# Preauth / Capture
If you select the Preauth / Capture feature, you simply have to change the status to "Capture (PAYMILL)" and a online capture will be processed.

# In case of errors

In case of any errors check the PAYMILL log entry in the plugin config and 
contact the PAYMILL support (support@paymill.de).

# Notes about the payment process

The payment is processed when an order is placed in the shop frontend.

Fast Checkout: Fast checkout can be enabled by selecting the option in the PAYMILL Basic Settings. If any customer completes a purchase while the option is active this customer will not be asked for data again. Instead a reference to the customer data will be saved allowing comfort during checkout.
