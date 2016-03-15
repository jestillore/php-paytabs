<?php

namespace jestillore\PayTabs;

class PayTabs {

    const VERSION = 'jestillore/php-paytabs 0.1.0';

    const API_VERSION = 'apiv2';
    const CREATE_PAY_PAGE_URL = 'https://www.paytabs.com/' . self::API_VERSION . '/create_pay_page';
    const VERIFY_PAYMENT_URL = 'https://www.paytabs.com/' . self::API_VERSION . '/verify_payment';

    private $merchantEmail;
    private $secretKey;
    private $siteUrl;
    private $returnUrl;
    private $title;
    private $language = 'English';
    private $currency;
    private $merchantIp;

    private $curl;

    public function __construct($options) {
        foreach ($options as $key => $value) {
            $this->$key = $value;
        }
        $this->curl = new \anlutro\cURL\cURL;
    }

    public function set($key, $value) {
        $this->$key = $value;
    }

    public function get($key) {
        return $this->$key;
    }

    public function createPayPage($options) {

        $params = [
            // config
            'merchant_email' => $this->merchantEmail,
            'secret_key' => $this->secretKey,
            'site_url' => $this->siteUrl,
            'return_url' => $this->returnUrl,
            'title' => $this->title,
            'msg_lang' => $this->language,
            'cms_with_version' => self::VERSION,
            'ip_merchant' => $this->merchantIp,

            // shipping
            'shipping_first_name' => $options['shipping']['firstName'],
            'shipping_last_name' => $options['shipping']['lastName'],
            'address_shipping' => $options['shipping']['address'],
            'city_shipping' => $options['shipping']['city'],
            'state_shipping' => $options['shipping']['state'],
            'postal_code_shipping' => str_pad($options['shipping']['postalCode'], 5, '0', STR_PAD_LEFT),
            'country_shipping' => $options['shipping']['country'],

            // billing
            'cc_first_name' => $options['billing']['firstName'],
            'cc_last_name' => $options['billing']['lastName'],
            'cc_phone_number' => $options['billing']['phoneNumber'],
            'phone_number' => $options['billing']['phoneNumber'],
            'email' => $options['billing']['email'],
            'billing_address' => $options['billing']['address'],
            'state' => $options['billing']['state'],
            'city' => $options['billing']['city'],
            'postal_code' => str_pad($options['billing']['postalCode'], 5, '0', STR_PAD_LEFT),
            'country' => $options['billing']['country'],

            // charges
            'other_charges' => $options['otherCharges'],
            'discount' => $options['discount'],
            'reference_no' => $options['reference'],
            'currency' => $this->currency
        ];

        // products
        $products = [];
        $prices = [];
        $quantities = [];

        $amount = $options['otherCharges'] - $options['discount'];

        foreach ($options['products'] as $product) {
            $products[] = $product['title'];
            $prices[] = $product['price'];
            $quantities[] = $product['quantity'];

            $amount += ($product['price'] * $product['quantity']);
        }

        $params['products_per_title'] = join(' || ', $products);
        $params['unit_price'] = join(' || ', $prices);
        $params['quantity'] = join(' || ', $quantities);
        $params['amount'] = $amount;

        $params['ip_customer'] = isset($options['customerIp']) ? $options['customerIp'] : $_SERVER['REMOTE_ADDR'];

        $res = $this->curl->post(self::CREATE_PAY_PAGE_URL, $params);

        $result = json_decode($res);

        return [
            'result' => $result->result,
            'responseCode' => $result->response_code,
            'paymentUrl' => $result->payment_url,
            'payPageId' => $result->p_id,
            'success' => $result->response_code == 4012
        ];

    }

    public function verifyPayment($options) {

        $params = [
            'merchant_email' => $this->merchantEmail,
            'secret_key' => $this->secretKey,
            'payment_reference' => $options['paymentReference']
        ];

        $res = $this->curl->post(self::VERIFY_PAYMENT_URL, $params);

        $result = json_decode($res);

        return [
            'result' => $result->result,
            'responseCode' => $result->response_code,
            'invoiceId' => $result->pt_invoice_id,
            'amount' => $result->amount,
            'currency' => $result->currency,
            'transactionId' => $result->transaction_id,
            'success' => $result->response_code == 100
        ];
        
    }

}
