<?php
require_once 'payment_config.php';
require_once __DIR__ . '/../../vendor/iyzico/iyzipay-php/IyzipayBootstrap.php';
require_once __DIR__ . '/../../vendor/iyzico/iyzipay-php/autoload.php';

use Iyzipay\Options;
use Iyzipay\Model\PaymentCard;
use Iyzipay\Model\Payment;

class PaymentHelper
{
    private $options;

    public function __construct()
    {
        $this->options = new Options();
        $this->options->setApiKey(IYZICO_API_KEY);
        $this->options->setSecretKey(IYZICO_SECRET_KEY);
        $this->options->setBaseUrl(IYZICO_BASE_URL);
    }

    public function createPayment($orderData, $cardData)
    {
        $request = new \Iyzipay\Request\CreatePaymentRequest();

        // Set payment details
        $request->setLocale(\Iyzipay\Model\Locale::TR);
        $request->setConversationId('CarWash_' . $orderData['order_id']);
        $request->setPrice($orderData['total']);
        $request->setPaidPrice($orderData['total']);
        $request->setCurrency(\Iyzipay\Model\Currency::TL);
        $request->setInstallment(1);
        $request->setBasketId($orderData['order_id']);
        $request->setPaymentChannel(\Iyzipay\Model\PaymentChannel::WEB);
        $request->setPaymentGroup(\Iyzipay\Model\PaymentGroup::PRODUCT);

        // Set card details
        $paymentCard = new \Iyzipay\Model\PaymentCard();
        $paymentCard->setCardHolderName($cardData['cardName']);
        $paymentCard->setCardNumber($cardData['cardNumber']);
        $paymentCard->setExpireMonth($cardData['expMonth']);
        $paymentCard->setExpireYear($cardData['expYear']);
        $paymentCard->setCvc($cardData['cvv']);
        $paymentCard->setRegisterCard(0);
        $request->setPaymentCard($paymentCard);

        // Set buyer details
        $buyer = new \Iyzipay\Model\Buyer();
        $buyer->setId($_SESSION['user_id']);
        $buyer->setName($orderData['user']['name']);
        $buyer->setSurname($orderData['user']['surname']);
        $buyer->setEmail($orderData['user']['email']);
        $buyer->setIdentityNumber("74300864791");
        $buyer->setRegistrationAddress("Istanbul");
        $buyer->setCity("Istanbul");
        $buyer->setCountry("Turkey");
        $request->setBuyer($buyer);

        // Set shipping address
        $shippingAddress = new \Iyzipay\Model\Address();
        $shippingAddress->setContactName($orderData['user']['name']);
        $shippingAddress->setCity("Istanbul");
        $shippingAddress->setCountry("Turkey");
        $shippingAddress->setAddress("Istanbul");
        $request->setShippingAddress($shippingAddress);

        // Set billing address
        $billingAddress = new \Iyzipay\Model\Address();
        $billingAddress->setContactName($orderData['user']['name']);
        $billingAddress->setCity("Istanbul");
        $billingAddress->setCountry("Turkey");
        $billingAddress->setAddress("Istanbul");
        $request->setBillingAddress($billingAddress);

        // Set basket items
        $basketItems = [];
        foreach ($orderData['items'] as $item) {
            $basketItem = new \Iyzipay\Model\BasketItem();
            $basketItem->setId($item['id']);
            $basketItem->setName($item['service_name']);
            $basketItem->setCategory1("CarWash");
            $basketItem->setItemType(\Iyzipay\Model\BasketItemType::VIRTUAL);
            $basketItem->setPrice($item['price']);
            $basketItems[] = $basketItem;
        }
        $request->setBasketItems($basketItems);

        // Make payment request
        return \Iyzipay\Model\Payment::create($request, $this->options);
    }
}
