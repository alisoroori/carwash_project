<?php
require_once 'db.php';
require_once __DIR__ . '/../../vendor/iyzico/iyzipay-php/IyzipayBootstrap.php';

use Iyzipay\Options;

class PaymentGateway
{
    private $options;
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->options = new \Iyzipay\Options();
        $this->options->setApiKey('your-api-key');
        $this->options->setSecretKey('your-secret-key');
        $this->options->setBaseUrl('https://sandbox-api.iyzipay.com'); // Sandbox URL
    }

    public function createPayment($orderData)
    {
        $request = new \Iyzipay\Request\CreatePaymentRequest();

        // Set payment details
        $request->setLocale(\Iyzipay\Model\Locale::TR);
        $request->setConversationId('CARWASH_' . $orderData['order_id']);
        $request->setPrice($orderData['total']);
        $request->setPaidPrice($orderData['total']);
        $request->setCurrency(\Iyzipay\Model\Currency::TL);
        $request->setInstallment(1);
        $request->setBasketId($orderData['order_id']);
        $request->setPaymentChannel(\Iyzipay\Model\PaymentChannel::WEB);
        $request->setPaymentGroup(\Iyzipay\Model\PaymentGroup::PRODUCT);

        // Set buyer details
        $buyer = $this->createBuyer($orderData['user']);
        $request->setBuyer($buyer);

        // Set shipping/billing address
        $address = $this->createAddress($orderData['user']);
        $request->setShippingAddress($address);
        $request->setBillingAddress($address);

        // Set basket items
        $request->setBasketItems($this->createBasketItems($orderData['items']));

        return \Iyzipay\Model\Payment::create($request, $this->options);
    }

    private function createBuyer($user)
    {
        $buyer = new \Iyzipay\Model\Buyer();
        $buyer->setId($user['id']);
        $buyer->setName($user['name']);
        $buyer->setSurname('');  // Split name if available
        $buyer->setEmail($user['email']);
        $buyer->setIdentityNumber("11111111111");  // Required for Turkish payment system
        $buyer->setRegistrationAddress($user['address'] ?? "Test Address");
        $buyer->setCity($user['city'] ?? "Istanbul");
        $buyer->setCountry("Turkey");
        return $buyer;
    }

    private function createAddress($user)
    {
        $address = new \Iyzipay\Model\Address();
        $address->setContactName($user['name']);
        $address->setCity($user['city'] ?? "Istanbul");
        $address->setCountry("Turkey");
        $address->setAddress($user['address'] ?? "Test Address");
        return $address;
    }

    private function createBasketItems($items)
    {
        $basketItems = [];
        foreach ($items as $item) {
            $basketItem = new \Iyzipay\Model\BasketItem();
            $basketItem->setId($item['id']);
            $basketItem->setName($item['service_name']);
            $basketItem->setCategory1("CarWash");
            $basketItem->setItemType(\Iyzipay\Model\BasketItemType::VIRTUAL);
            $basketItem->setPrice($item['price']);
            $basketItems[] = $basketItem;
        }
        return $basketItems;
    }

    public function processPayment($token, $orderId)
    {
        // Retrieve and process payment result
        $request = new \Iyzipay\Request\RetrieveCheckoutFormRequest();
        $request->setLocale(\Iyzipay\Model\Locale::TR);
        $request->setConversationId('CARWASH_' . $orderId);
        $request->setToken($token);

        return \Iyzipay\Model\CheckoutForm::retrieve($request, $this->options);
    }
}
