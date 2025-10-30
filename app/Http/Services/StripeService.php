<?php

namespace App\Http\Services;

use App\Models\UserCreditCard;
use App\Models\User;
use Stripe\Error\ApiConnection;
use Stripe\Error\Authentication;
use Stripe\Error\Base;
use Stripe\Error\InvalidRequest;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\Token;
use Stripe\Customer;
use Stripe\Source;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use App\Consts;
use Exception;
use Illuminate\Support\Facades\Auth;
use App\Models\UserStripeCard;
use App\Models\UserStripeConnectAccount;
use Stripe\Account;
use Stripe\Payout;
use Stripe\Transfer;
use Stripe\WebhookEndpoint;
use Stripe\Checkout\Session;
use App\Utils;
use App\Models\Offer;
use App\Exceptions\Reports\PaymentException;

class StripeService
{

    public function __construct()
    {
        self::setApiKey();
    }

    public function getLast4NumberOfCard($paymentMethodId)
    {
        try {
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);

            return $paymentMethod->card->last4;
        } catch (Exception $ex) {
            $this->handleStripeException($ex);
        }
    }

    public function createCustomer($userId, $description = '')
    {
        try {
            $user = User::findOrFail($userId);
            $customer = Customer::create([
                'email' => $user->email,
                'name' => $user->username,
                'description' => $description
            ]);

            return $customer;
        } catch (Exception $ex) {
            $this->handleStripeException($ex);
        }
    }

    public function attactPaymentMethodToCustomer($customerId, $paymentMethodId)
    {
        try {
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach([
              'customer' => $customerId,
            ]);
        } catch (Exception $ex) {
            $this->handleStripeException($ex);
        }
    }

    public function createPaymentIntent($transaction, $customerId, $paymentMethodId) {
        try {
            $paymentIntent = PaymentIntent::create([
              'customer' => $customerId,
              'payment_method' => $paymentMethodId,
              'amount' => $transaction->real_amount * 100,
              'currency' => $transaction->real_currency,
              'confirmation_method' => 'manual',
              'confirm' => true,
              'setup_future_usage' => 'off_session'
            ]);
            if (!$paymentIntent) {
                throw new PaymentException('payment.stripe.create_payment_intent.error');
            }
            return $paymentIntent;
        } catch (Exception $ex) {
            $this->handleStripeException($ex);
        }
    }

    public function executePaymentIntent($paymentIntentId, $paymentMethodId)
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            $paymentIntent->confirm([
              'payment_method' => $paymentMethodId
            ]);

            return $paymentIntent;
        } catch (Exception $ex) {
            $this->handleStripeException($ex);
        }
    }

    private function handleStripeException($ex)
    {
        logger()->error($ex);
        logger()->error('=======Stripe errors======: ', [$ex->getHttpBody()]);
        throw new PaymentException($ex->getStripeCode(), $ex->getMessage());
    }

    public function registerWebhook()
    {
        $this->registerWebhookDeposit();
        // $this->registerWebhookWithdrawal();
    }

    private function registerWebhookDeposit()
    {
        $enabledEvents = [
            Consts::STRIPE_WEBHOOK_EVENT_PAYMENT_INTENT_CREATED,
            Consts::STRIPE_WEBHOOK_EVENT_PAYMENT_INTENT_SUCCEEDED
        ];
        $appUrl = Utils::getSchemeAndHttpHost();
        WebhookEndpoint::create([
            'url'               => "{$appUrl}/api/v1/stripe/web-hook/deposit",
            'enabled_events'    => $enabledEvents
        ]);
    }

    private function registerWebhookWithdrawal()
    {
        $enabledEvents = [
            Consts::STRIPE_WEBHOOK_EVENT_PAYOUT_FAILED,
            Consts::STRIPE_WEBHOOK_EVENT_PAYOUT_PAID,
            Consts::STRIPE_WEBHOOK_EVENT_CHARGE_SUCCEEDED,
            Consts::STRIPE_WEBHOOK_EVENT_CHARGE_FAILED,
        ];
        $appUrl = Utils::getSchemeAndHttpHost();
        WebhookEndpoint::create([
            'url'               => "{$appUrl}/api/v1/stripe/web-hook/withdraw",
            'connect'           => true,
            'enabled_events'    => $enabledEvents
        ]);
    }

    private function setApiKey()
    {
        $apiKey = env('SECRET_STRIPE_API_KEY');
        if (empty($apiKey)) {
            throw new PaymentException('payment.stripe.api_key_invalid');
        }
        Stripe::setApiKey($apiKey);
    }
}
