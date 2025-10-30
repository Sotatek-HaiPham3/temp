<?php

namespace App\Http\Services;

use App\Consts;
use PayPal\Api\PaymentExecution;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PayoutBatchHeader;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Api\Payout;
use PayPal\Api\PayoutSenderBatchHeader;
use PayPal\Api\PayoutItem;
use PayPal\Api\Currency;
use PayPal\Api\Webhook;
use PayPal\Api\WebhookEventType;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Exception\PayPalInvalidCredentialException;
use PayPal\Exception\PayPalMissingCredentialException;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use App\Utils;
use App\Exceptions\Reports\PaymentException;
use Exception;

class PaypalService
{
    private $apiContext;

    public function __construct()
    {
        self::initApiContext();
    }

    private function initApiContext()
    {
        $clientId           = env('PAYPAL_CLIENT_ID');
        $clientSecret       = env('PAYPAL_CLIENT_SECRET');
        $mode               = env('PAYPAL_MODE');

        if (!$clientId || !$clientSecret || !$mode) {
            throw new PaymentException('payment.paypal.invalid_client');
        }

        $this->apiContext   = new ApiContext(new OAuthTokenCredential($clientId, $clientSecret));
        $this->apiContext->setConfig([
            'mode' => strtoupper($mode)
        ]);
    }

    public function createTransaction($price, $currency, $description)
    {
        $currency = strtoupper($currency);

        $item = new Item();
        $item->setName($description)
             ->setCurrency($currency)
             ->setQuantity(1)
             ->setPrice($price);

        $itemList = new ItemList();
        $itemList->setItems(array($item));

        $details = new Details();
        $details->setShipping(0)
                ->setTax(0)
                ->setSubtotal($price);

        $amount = new Amount();
        $amount->setCurrency($currency)
               ->setTotal($price)
               ->setDetails($details);

        $transaction = new Transaction();
        $transaction->setAmount($amount)
                    ->setItemList($itemList)
                    ->setDescription($description);

        return $transaction;
    }

    public function createOrder($price, $currency, $description, $input)
    {
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $transaction = $this->createTransaction($price, $currency, $description);
        $refererReturnUrl = urlencode(Utils::getRefererReturnUrlForDeposit($input));
        $refererCancelUrl = urlencode($input['referer_url'] . array_get($input, 'hash_url', ''));
        $appUrl = Utils::getSchemeAndHttpHost();
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl("{$appUrl}/payment/paypal/webhook/return?redirectUrl={$refererReturnUrl}")
            ->setCancelUrl("{$appUrl}/payment/paypal/webhook/cancel?redirectUrl={$refererCancelUrl}");

        $payment = new Payment();
        $payment->setIntent(Consts::PAYPAL_CHECKOUT_TYPE)
            ->setPayer($payer)
            ->setTransactions(array($transaction))
            ->setRedirectUrls($redirectUrls);

        $payment->create($this->apiContext);

        $this->validateOrder($payment);

        return $payment;
    }

    public function approveOrder($price, $currency, $description, $paymentId, $payerId)
    {
        $payment = Payment::get($paymentId, $this->apiContext);

        $execution = new PaymentExecution();
        $execution->setPayerId($payerId);

        $transaction = $this->createTransaction($price, $currency, $description);

        $execution->addTransaction($transaction);

        $payment->execute($execution, $this->apiContext);
        $payment = Payment::get($paymentId, $this->apiContext);

        $this->validateOrder($payment);

        return $payment;
    }

    public function payout($transactionId, $paypalReceiverEmail, $currency, $amount)
    {
        $currency = strtoupper($currency);

        try {
            $payouts = new Payout();
            $senderBatchHeader = new PayoutSenderBatchHeader();

            $senderBatchHeader->setSenderBatchId($transactionId);
            $senderItem = new PayoutItem();
            $senderItem->setRecipientType('Email')
                ->setReceiver($paypalReceiverEmail)
                ->setAmount(new Currency([
                    'value' => $amount,
                    'currency' => $currency
                ]));
            $payouts->setSenderBatchHeader($senderBatchHeader)
                ->addItem($senderItem);

            $output = $payouts->create(null, $this->apiContext);
            return $output;
        } catch (Exception $exception) {
            $this->handleExceptionWithLog($exception);
        }
    }

    public function handleExceptionWithLog($exception, $transaction = null)
    {
        if ($transaction) {
            $transaction->status = Consts::TRANSACTION_STATUS_FAILED;
            $transaction->error_detail = $exception;
            $transaction->save();
        }

        $errorKey = null;
        if ($exception instanceof PayPalConnectionException) {
            $errorKey = 'payment.paypal.connection_timeout';
        }
        else if ($exception instanceof PayPalInvalidCredentialException) {
            $errorKey = 'payment.paypal.invalid_credential';
        }
        else if ($exception instanceof PayPalMissingCredentialException) {
            $errorKey = 'payment.paypal.missing_credential';
        }

        throw new PaymentException($errorKey, $exception->getMessage());
    }

    private function validateOrder($order)
    {
        switch ($order->getState()) {
            case Consts::PAYMENT_ORDER_FAILED:
                $msgError = implode(Consts::CHAR_COMMA, $order->getFailedTransactions());
                throw new PaymentException('payment.paypal.order_failed', $msgError);
        }
    }

    public function registerWebhook()
    {
        $this->registerWebhookDeposit();
        $this->registerWebhookWithdrawal();
    }

    private function registerWebhookDeposit()
    {
        $webhook = new Webhook();
        $webhook->setUrl(config('app.url') . '/payment/paypal/webhook/deposit');
        $webhookEventTypes[] = new WebhookEventType(
            '{
                "name": "PAYMENT.SALE.COMPLETED"
            }'
        );
        $webhookEventTypes[] = new WebhookEventType(
            '{
                "name": "PAYMENT.SALE.DENIED"
            }'
        );
        $webhookEventTypes[] = new WebhookEventType(
            '{
                "name": "PAYMENT.SALE.PENDING"
            }'
        );
        $webhook->setEventTypes($webhookEventTypes);

        try {
            $output = $webhook->create($this->apiContext);
        } catch (PayPalConnectionException $exception) {
            $error = json_decode($exception->getData(), true);
            logger()->error('Error when create paypal webhook deposit: ', [$error]);
            throw $exception;
        } catch (Exception $exception) {
            throw $exception;
        }
        return $output;
    }

    private function registerWebhookWithdrawal()
    {
        $webhook = new Webhook();
        $webhook->setUrl(config('app.url') . '/payment/paypal/webhook/withdraw');
        $webhookEventTypes[] = new WebhookEventType(
            '{
                "name": "PAYMENT.PAYOUTSBATCH.DENIED"
            }'
        );
        $webhookEventTypes[] = new WebhookEventType(
            '{
                "name": "PAYMENT.PAYOUTSBATCH.PROCESSING"
            }'
        );
        $webhookEventTypes[] = new WebhookEventType(
            '{
                "name": "PAYMENT.PAYOUTSBATCH.SUCCESS"
            }'
        );
        $webhook->setEventTypes($webhookEventTypes);
        try {
            $output = $webhook->create($this->apiContext);
        } catch (PayPalConnectionException $exception) {
            $error = json_decode($exception->getData(), true);
            logger()->error('Error when create paypal webhook withdraw: ', [$error]);
            throw $exception;
        } catch (Exception $exception) {
            throw $exception;
        }
        return $output;
    }

    public function getPaymentDetailById($paymentId)
    {
        try {
            return Payment::get($paymentId, $this->apiContext);
        } catch (Exception $exception) {
            return $exception;
        }
    }
}
