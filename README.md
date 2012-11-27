Paymill-xtCommerce-4
====================

xtCommerce (Version 4 / Veyton) Plugin for Paymill credit card payments

# Installation

Important: Use the following command to clone the complete repository including the submodules:
    
    git clone --recursive https://github.com/Paymill/Paymill-xtCommerce-4.git

# Configuration

Afterwards perform the following steps in your store backend:

* Install the plugin
* Activate the plugin
* Configure the Paymill payment method by inserting your public and private test or live keys
* Activate the Paymill payment method


# In case of errors

Make sure the logfile (plugins/xt_paymill/classes/paymill/log.txt) is writable. In case of any errors check this files contents and contact the Paymill support (support@paymill.de).

# Notes about the payment process

The payment is processed when an order is placed in the shop frontend.