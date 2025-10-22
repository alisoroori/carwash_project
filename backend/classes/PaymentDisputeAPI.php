<?php
declare(strict_types=1);

namespace App\Classes;

class PaymentDisputeAPI
{
    private $apiKey;
    private $secretKey;
    private $baseUrl;

    public function __construct()
    {
        $this->apiKey = getenv('IYZICO_API_KEY');
        $this->secretKey = getenv('IYZICO_SECRET_KEY');
        $this->baseUrl = 'https://api.iyzipay.com';
    }

    public function createDisputeCase($transactionId, $reason, $evidence)
    {
        $options = new \Iyzipay\Options();
        $options->setApiKey($this->apiKey);
        $options->setSecretKey($this->secretKey);
        $options->setBaseUrl($this->baseUrl);

        $request = new \Iyzipay\Request\CreateDisputeRequest();
        $request->setLocale(\Iyzipay\Model\Locale::TR);
        $request->setPaymentTransactionId($transactionId);
        $request->setReason($reason);

        if ($evidence) {
            $request->setEvidenceFile(base64_encode(file_get_contents($evidence)));
        }

        return \Iyzipay\Model\Dispute::create($request, $options);
    }

    public function checkDisputeStatus($disputeId)
    {
        $options = new \Iyzipay\Options();
        $options->setApiKey($this->apiKey);
        $options->setSecretKey($this->secretKey);
        $options->setBaseUrl($this->baseUrl);

        $request = new \Iyzipay\Request\RetrieveDisputeRequest();
        $request->setPaymentTransactionId($disputeId);

        return \Iyzipay\Model\Dispute::retrieve($request, $options);
    }
}

