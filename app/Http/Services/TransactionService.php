<?php

namespace App\Http\Services;

use App\Consts;
use App\Models\UserCreditCard;
use App\Models\User;
use App\Models\Tip;
use App\Models\UserStripeCustomer;
use App\Models\UserStripeCustomerWithoutLogged;
use App\Models\SessionSystemMessage;
use App\Models\Channel;
use App\Models\UserBalance;
use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Transaction;
use App\Models\Offer;
use App\Models\ExchangeOffer;
use App\Http\Services\StripeService;
use App\Http\Services\PaypalService;
use App\Http\Services\UserService;
use App\Http\Services\ChatService;
use App\Utils;
use App\Utils\BigNumber;
use App\Utils\CurrencyExchange;
use Exception;
use App\Events\UserUpdated;
use App\Events\TransactionUpdated;
use App\Events\DepositSuccess;
use App\Events\TipUpdated;
use Mail;
use App\Mails\RejectedWithdrawMail;
use App\Mails\ApprovedWithdrawMail;
use App\Mails\FailedWithdrawMail;
use App\Jobs\WithdrawalExecutingJob;
use App\Exceptions\Reports\PaymentException;
use App\Exceptions\Reports\InsufficientBalanceException;
use App\Exceptions\Reports\NotEnoughBalancesException;
use App\Mails\ExchangeCoinsMail;
use App\Mails\DepositPaymentMail;
use Aws;
use App\Traits\SessionTrait;
use App\Traits\NotificationTrait;

class TransactionService
{
    use SessionTrait, NotificationTrait;

    private $stripeService;

    public function __construct()
    {
        $this->userService      = new UserService();
        $this->stripeService    = new StripeService();
        $this->paypalService    = new PaypalService();
        $this->chatService = new ChatService();
    }

    public function deposit($input)
    {
        $input['type'] = Consts::TRANSACTION_TYPE_DEPOSIT;
        $input['currency'] = Consts::CURRENCY_COIN;
        $input['real_currency'] = Consts::CURRENCY_USD;

        $userId = Auth::id();
        if (!empty($input['username'])) {
            $userId = User::where('username', $input['username'])->value('id');
        }

        $transaction = $this->createTransaction($userId, $input);

        $transaction->memo = sprintf(Consts::TRANSATION_MEMO_DEPOSIT, $transaction->amount);
        $input['transaction_id'] = $transaction->id;
        $input['user_id'] = $transaction->user_id;

        switch ($input['payment_type']) {
            case Consts::PAYMENT_TYPE_PAYPAL:
                logger()->info('=====Transaction deposit with paypal creating=====');
                return $this->makeDepositPaypal($transaction, $input);
            case Consts::PAYMENT_TYPE_CREDIT_CARD:
                logger()->info('=====Transaction deposit with stripe creating=====');
                return $this->depositStripe($transaction, $input);
            default:
                throw new PaymentException('payment.invalid_type');
        }
    }

    private function makeDepositPaypal($transaction, $input)
    {
        if ($transaction->real_amount >= Consts::PAYMENT_MAX_DEPOSIT_PAYPAL) {
            throw new PaymentException('payment.paypal.max_value_deposit');
        }

        try {
            $order = $this->paypalService->createOrder(
                $transaction->real_amount,
                $transaction->real_currency,
                Consts::PAYMENT_DEPOSIT_MESSAGE,
                $input
            );

            logger()->info('=====Transaction deposit with paypal created=====');
            $transaction->transaction_id = $order->getId();
            $transaction->paypal_token = $order->getToken();
            $transaction->status = Consts::TRANSACTION_STATUS_CREATED;
            $transaction->message_key = Consts::MESSAGE_TRANSACTION_DEPOSIT;
            $transaction->message_props = [
                'usd' => Utils::formatPropsValue($transaction->real_amount),
                'coins' => Utils::formatPropsValue($transaction->amount)
            ];
            $transaction->save();

            event(new TransactionUpdated($transaction));

            $data = [
                'when' => 'Create deposit by paypal',
                'data' => [
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => $transaction->real_amount
                ]
            ];
            Aws::performFirehosePut($data, $this->getUser($transaction->user_id));

            return [
                'approvalUrl' => $order->getApprovalLink()
            ];
        } catch (Exception $exception) {
            $this->paypalService->handleExceptionWithLog($exception, $transaction);
        }
    }

    public function convertBalances($exchangeOfferId)
    {
        $userId = Auth::id();
        $exchangeOffer = ExchangeOffer::find($exchangeOfferId);
        $this->validateUserBalance($userId, $exchangeOffer->bars, Consts::CURRENCY_BAR);

        $input['type'] = Consts::TRANSACTION_TYPE_CONVERT;
        $input['payment_type'] = Consts::PAYMENT_SERVICE_TYPE_GLBAR;
        $input['status'] = Consts::TRANSACTION_STATUS_SUCCESS;
        $input['memo'] = Consts::TRANSATION_MEMO_CONVERT;
        $input['amount'] = $exchangeOffer->coins;
        $input['real_amount'] = $exchangeOffer->bars;
        $input['currency'] = Consts::CURRENCY_COIN;
        $input['real_currency'] = Consts::CURRENCY_BAR;
        $input['message_key'] = Consts::MESSAGE_TRANSACTION_CONVERT;
        $input['message_props'] = [
            'rewards' => Utils::formatPropsValue($exchangeOffer->bars),
            'coins' => Utils::formatPropsValue($exchangeOffer->coins)
        ];

        $transaction = $this->createTransaction($userId, $input);

        $this->userService->addMoreBalance($userId, $transaction->amount, $transaction->currency);
        $this->userService->subtractBalance($userId, $transaction->real_amount, $transaction->real_currency);

        // event(new TransactionUpdated($transaction));
        Mail::queue(new ExchangeCoinsMail($transaction));

        $data = [
            'when' => 'Convert rewards to coins',
            'data' => [
                'transaction_id' => $transaction->transaction_id,
                'rewards' => $transaction->real_amount,
                'coins' => $transaction->amount
            ]
        ];
        Aws::performFirehosePut($data);

        $notificationParams = [
            'user_id' => $userId,
            'type' => Consts::NOTIFY_TYPE_WALLET_COINS,
            'message' => Consts::NOTIFY_WALLET_EXCHANGE,
            'props' => [
                'rewards' => Utils::formatPropsValue($exchangeOffer->bars),
                'coins' => Utils::formatPropsValue($exchangeOffer->coins)
            ],
            'data' => ['user' => (object) ['id' => $userId]]
        ];
        $this->fireNotification(Consts::NOTIFY_TYPE_OTHER, $notificationParams);

        return $transaction;
    }

    private function depositStripe($transaction, $input)
    {
        if($transaction->real_amount >= Consts::PAYMENT_MAX_DEPOSIT_STRIPE){
            throw new PaymentException('payment.stripe.max_value_charge');
        }

        $paymentMethodId = array_get($input, 'payment_method_id', null);
        $savePaymentMethod = array_get($input, 'save_payment_method', Consts::FALSE);

        if (Auth::check()) {
            $userStripeCustomer = $this->createUserStripeCustomer($input, $paymentMethodId, $savePaymentMethod);
        } else {
            $userStripeCustomer = $this->createUserStripeCustomerWithoutLogged($input);
        }

        $customerId = $userStripeCustomer->customer_id;
        $paymentMethodId = $paymentMethodId ?? $userStripeCustomer->payment_method_id;

        $this->stripeService->attactPaymentMethodToCustomer($customerId, $paymentMethodId);

        $paymentIntent = $this->stripeService->createPaymentIntent($transaction, $customerId, $paymentMethodId);

        $transaction->transaction_id = $paymentIntent->id;
        $transaction->status = Consts::TRANSACTION_STATUS_CREATED;
        $transaction->message_key = Consts::MESSAGE_TRANSACTION_DEPOSIT;
        $transaction->message_props = [
            'usd' => Utils::formatPropsValue($transaction->real_amount),
            'coins' => Utils::formatPropsValue($transaction->amount)
        ];
        $transaction->save();

        event(new UserUpdated($transaction->user_id));
        event(new TransactionUpdated($transaction));

        logger('==========paymentIntent==========: ', [$paymentIntent]);

        if ($paymentIntent->status === Consts::STRIPE_TRANSACTION_STATUS_SUCCESS) {
            $transaction->status = Consts::TRANSACTION_STATUS_EXECUTING;
            $transaction->save();

            $this->sendNotifyDepositExecuting($transaction);

            $data = [
                'when' => 'Deposit by credit card',
                'data' => [
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => $transaction->real_amount
                ]
            ];
            Aws::performFirehosePut($data, $this->getUser($transaction->user_id));

            return $transaction;
        }

        return [
            'payment_secret' => $paymentIntent->client_secret
        ];
    }

    private function createUserStripeCustomer($input, $paymentMethodId, $savePaymentMethod)
    {
        $userId = Auth::id();
        $userStripeCustomer = UserStripeCustomer::where('user_id', $userId)->first();

        if (!$userStripeCustomer) {
            $customer = $this->stripeService->createCustomer($userId);
            $userStripeCustomer = UserStripeCustomer::create([
                'user_id' => $userId,
                'customer_id' => $customer->id
            ]);
        }

        if ($paymentMethodId && $savePaymentMethod) {
            $userStripeCustomer->payment_method_id = $paymentMethodId;
            $userStripeCustomer->save();
        }

        return $userStripeCustomer;
    }

    private function createUserStripeCustomerWithoutLogged($input)
    {
        $customerId = UserStripeCustomerWithoutLogged::where('user_id', $input['user_id'])->value('customer_id');

        if (!$customerId) {
            $customer = $this->stripeService->createCustomer($input['user_id'], Consts::STRIPE_DESCRIPTION_DEPOSIT_WITHOUT_LOGGED);
            $customerId = $customer->id;
        }

        $userStripeCustomerWithoutLogged = UserStripeCustomerWithoutLogged::create([
            'user_id' => $input['user_id'],
            'customer_id' => $customerId,
            'payment_method_id' => $input['payment_method_id']
        ]);

        return $userStripeCustomerWithoutLogged;
    }

    public function handlePaymentIntent($input)
    {
        if (array_key_exists('error', $input)) {
            $transaction = Transaction::where('transaction_id', $input['error']['payment_intent']['id'])
                ->where('payment_type', Consts::PAYMENT_TYPE_CREDIT_CARD)
                ->where('type', Consts::TRANSACTION_TYPE_DEPOSIT)
                ->where('status', Consts::TRANSACTION_STATUS_CREATED)
                ->first();

            $transaction->status = Consts::TRANSACTION_STATUS_CANCEL;
        }

        if (array_key_exists('paymentIntent', $input) && $input['paymentIntent']['status'] === 'requires_confirmation') {
            $paymentIntentId = $input['paymentIntent']['id'];
            $paymentMethodId = $input['paymentIntent']['payment_method'];
            $this->stripeService->executePaymentIntent($paymentIntentId, $paymentMethodId);

            $transaction = Transaction::where('transaction_id', $paymentIntentId)
                ->where('payment_type', Consts::PAYMENT_TYPE_CREDIT_CARD)
                ->where('type', Consts::TRANSACTION_TYPE_DEPOSIT)
                ->where('status', Consts::TRANSACTION_STATUS_CREATED)
                ->first();

            $transaction->status = Consts::TRANSACTION_STATUS_EXECUTING;
            $this->sendNotifyDepositExecuting($transaction);
        }

        $transaction->save();
        event(new TransactionUpdated($transaction));

        $data = [
            'when' => 'Deposit by credit card required 3D secure',
            'data' => [
                'transaction_id' => $transaction->transaction_id,
                'amount' => $transaction->real_amount
            ]
        ];
        Aws::performFirehosePut($data, $this->getUser($transaction->user_id));

        return $transaction;
    }

    public function depositStripeWebHook($payment)
    {
        logger()->info('=====Information payment deposit from stripe webhook=====: ' . json_encode($payment));
        $paymentMethodId = '';
        if (!empty($payment['data']['object']['payment_method'])) {
            $paymentMethodId = $payment['data']['object']['payment_method'];
        }
        $this->handleStripeWebhook($payment['data']['object']['id'], $payment['type'], Consts::TRANSACTION_TYPE_DEPOSIT, $paymentMethodId, json_encode($payment));
    }

    // public function withdrawStripeWebHook($payment)
    // {
    //     logger()->info('=====Information payment withdraw from stripe webhook=====: ' . json_encode($payment));
    //     $this->handleStripeWebhook($payment['data']['object']['id'], $payment['type'], Consts::TRANSACTION_TYPE_WITHDRAW, json_encode($payment));
    // }

    private function handleStripeWebhook($transactionId, $transactionStatus, $type, $paymentMethodId, $errorMsg = null)
    {
        DB::beginTransaction();
        try {
            $transaction = Transaction::where('transaction_id', $transactionId)
                ->where('payment_type', Consts::PAYMENT_TYPE_CREDIT_CARD)
                ->where('type', $type)
                ->whereIn('status', [
                    Consts::TRANSACTION_STATUS_EXECUTING,
                    Consts::TRANSACTION_STATUS_CREATED
                ])
                ->first();
            if (!$transaction) {
                throw new Exception(__('payment.paypal.success.transaction_invalid', [ 'id' => $transactionId ]));
            }
            switch ($transactionStatus) {
                case Consts::STRIPE_WEBHOOK_EVENT_PAYOUT_FAILED:
                case Consts::STRIPE_WEBHOOK_EVENT_CHARGE_FAILED:
                    $transaction->status = Consts::TRANSACTION_STATUS_FAILED;
                    $transaction->error_detail = $errorMsg;
                    break;
                case Consts::STRIPE_WEBHOOK_EVENT_PAYOUT_PAID:
                case Consts::STRIPE_WEBHOOK_EVENT_CHARGE_SUCCEEDED:
                    $transaction->status = Consts::TRANSACTION_STATUS_SUCCESS;
                    break;
                case Consts::STRIPE_WEBHOOK_EVENT_PAYMENT_INTENT_CREATED:
                    $transaction->status = Consts::TRANSACTION_STATUS_EXECUTING;
                    break;
                case Consts::STRIPE_WEBHOOK_EVENT_PAYMENT_INTENT_SUCCEEDED:
                    $transaction->status = Consts::TRANSACTION_STATUS_SUCCESS;
                    $this->userService->addMoreBalance($transaction->user_id, $transaction->amount, $transaction->currency);
                    $this->sendEmailAndNotifyDepositSuccess($transaction, $paymentMethodId);
                    break;
                default:
                    // Unexpected event type
                    logger('Unexpected event type stripe: ' . $event);
                    break;
            }
            $transaction->save();
            event(new TransactionUpdated($transaction));

            $data = [
                'when' => 'Recevied webhook from Stripe',
                'data' => [
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => $transaction->real_amount
                ]
            ];
            Aws::performFirehosePut($data, $this->getUser($transaction->user_id));

            DB::commit();
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            if ($transaction) {
                $transaction->error_detail = $ex;
                $transaction->save();
            }
        }
    }

    public function createInternalTransaction($userId, $data, $transactionId = null, $memo = null)
    {
        return $this->createTransaction($userId, $data, $transactionId, $memo);
    }

    public function createTransaction($userId, $input, $transactionId = null, $memo = null)
    {
        $currentMillis                      = Utils::currentMilliseconds();

        $transaction                        = new Transaction();
        $transaction->user_id               = $userId;
        $transaction->transaction_id        = $transactionId ? $transactionId : Utils::generateRandomString(36);
        $transaction->paypal_token          = $input['token'] ?? null;
        $transaction->currency              = $input['currency'];
        $transaction->payment_type          = $input['payment_type'];
        $transaction->type                  = $input['type'];
        $transaction->status                = $input['status'] ?? Consts::TRANSACTION_STATUS_CREATING;
        $transaction->paypal_receiver_email = $input['paypal_receiver_email'] ?? null;
        $transaction->memo                  = $memo ?? array_get($input, 'memo', null);
        $transaction->message_key           = array_get($input, 'message_key', Consts::MESSAGE_TRANSACTION_DEPOSIT);
        $transaction->message_props         = array_get($input, 'message_props', []);
        $transaction->internal_type         = array_get($input, 'internal_type', null);
        $transaction->internal_type_id      = array_get($input, 'internal_type_id', null);
        $transaction->created_at            = $currentMillis;
        $transaction->updated_at            = $currentMillis;

        if (array_key_exists('real_amount', $input)) {
            $transaction->real_amount = $input['real_amount'];
        }
        if (array_key_exists('real_currency', $input)) {
            $transaction->real_currency = $input['real_currency'];
        }
        if (array_key_exists('amount', $input)) {
            $transaction->amount = $input['amount'];
        }
        if (array_key_exists('offer_id', $input)) {
            $transaction->offer_id = $input['offer_id'];
            $offer = Offer::find($input['offer_id']);
            if (!$offer) {
                throw new PaymentException('payment.offer_not_exists');
            }
            $transaction->real_amount = $offer->price;
            $transaction->amount = $offer->coin;
        }
        if (!Auth::check()) {
            $transaction->without_logged = Consts::TRUE;
        }

        $transaction->save();

        return $transaction;
    }

    public function depositPaypalExecute($paymentId, $payerId, $token)
    {
        $remainTimeExpire = Utils::currentMilliseconds() - Consts::PAYPAL_TRANSACTION_EXPIRED_TIME;

        if (BigNumber::new($remainTimeExpire)->comp(0) <= 0) {
            throw new Exception(__('payment.paypal.deposit.time_expired'));
        }

        $transaction = Transaction::where('transaction_id', $paymentId)
            ->where('paypal_token', $token)
            ->where('payment_type', Consts::PAYMENT_SERVICE_TYPE_PAYPAL)
            ->where('type', Consts::TRANSACTION_TYPE_DEPOSIT)
            ->where('status', Consts::TRANSACTION_STATUS_CREATED)
            ->where('created_at', '>=', $remainTimeExpire)
            ->first();

        if (!$transaction) {
            $msgError = __('payment.paypal.success.transaction_invalid', [ 'id' => $paymentId ]);

            $transaction->status = Consts::TRANSACTION_STATUS_FAILED;
            $transaction->memo = Consts::TRANSACTION_STATUS_FAILED;
            $transaction->error_detail = $msgError;
            $transaction->save();

            event(new TransactionUpdated($transaction));

            logger()->error('======depositPaypalExecute::Error - ', [$msgError]);

            return;
        }

        $order = $this->paypalService->approveOrder(
            $transaction->real_amount,
            $transaction->real_currency,
            Consts::PAYMENT_DEPOSIT_MESSAGE,
            $paymentId,
            $payerId
        );

        logger()->info('=====Transaction deposit with paypal executing=====');
        $transaction->status = Consts::TRANSACTION_STATUS_EXECUTING;
        $transaction->save();

        $this->sendNotifyDepositExecuting($transaction);
        event(new TransactionUpdated($transaction));

        $data = [
            'when' => 'Executing deposit with Paypal',
            'data' => [
                'transaction_id' => $transaction->transaction_id,
                'amount' => $transaction->real_amount
            ]
        ];
        Aws::performFirehosePut($data, $this->getUser($transaction->user_id));
    }

    public function depositPaypalCancel($token)
    {
        logger()->info('=====Transaction deposit with paypal cancel=====');
        $transaction = Transaction::where('paypal_token', $token)
            ->where('payment_type', Consts::PAYMENT_SERVICE_TYPE_PAYPAL)
            ->where('type', Consts::TRANSACTION_TYPE_DEPOSIT)
            ->where('status', Consts::TRANSACTION_STATUS_CREATED)
            ->first();

        if (!$transaction) {
            throw new Exception('Token invalid ' . $token);
        }

        $transaction->status = Consts::TRANSACTION_STATUS_CANCEL;
        $transaction->save();
        event(new TransactionUpdated($transaction));

        $data = [
            'when' => 'Cancel deposit with Paypal',
            'data' => [
                'transaction_id' => $transaction->transaction_id,
                'amount' => $transaction->real_amount
            ]
        ];
        Aws::performFirehosePut($data, $this->getUser($transaction->user_id));
    }

    public function depositPaypalWebHook($payment)
    {
        logger()->info('=====Information payment deposit from paypal webhook=====: ' . json_encode($payment));
        $msgError = '';
        if (!empty($payment['resource']['errors'])) {
            $msgError = $payment['resource']['errors']['message'];
        }
        $this->handlePaypalWebhook($payment['resource']['parent_payment'], $payment['event_type'], Consts::TRANSACTION_TYPE_DEPOSIT, $msgError);
    }

    public function withdrawPaypalWebHook($payment)
    {
        logger()->info('=====Information payment withdrawal from  paypal webhook=====: ' . json_encode($payment));
        $msgError = '';
        if (!empty($payment['resource']['errors'])) {
            $msgError = $payment['resource']['errors']['message'];
        }
        $this->handlePaypalWebhook($payment['resource']['batch_header']['payout_batch_id'], $payment['event_type'], Consts::TRANSACTION_TYPE_WITHDRAW, $msgError);
    }

    private function handlePaypalWebhook($transactionId, $transactionStatus, $type, $errorMsg = null)
    {
        $transaction = Transaction::where('transaction_id', $transactionId)
            ->where('payment_type', Consts::PAYMENT_SERVICE_TYPE_PAYPAL)
            ->where('type', $type)
            ->whereIn('status', [Consts::TRANSACTION_STATUS_PENDING, Consts::TRANSACTION_STATUS_EXECUTING])
            ->first();
        if (!$transaction) {
            throw new Exception(__('payment.paypal.success.transaction_invalid', [ 'id' => $transactionId ]));
        }
        switch ($transactionStatus) {
            case Consts::PAYMENT_SALE_COMPLETED:
                $transaction->status = Consts::TRANSACTION_STATUS_SUCCESS;
                if ($type === Consts::TRANSACTION_TYPE_DEPOSIT) {
                    $this->userService->addMoreBalance($transaction->user_id, $transaction->amount, $transaction->currency);
                    $this->sendEmailAndNotifyDepositSuccess($transaction);
                }
                break;
            case Consts::PAYMENT_PAYOUTSBATCH_SUCCESS:
                $transaction->status = Consts::TRANSACTION_STATUS_SUCCESS;
                $notificationParams = [
                    'user_id' => $transaction->user_id,
                    'type' => Consts::NOTIFY_TYPE_WALLET_USD,
                    'message' => Consts::NOTIFY_WALLET_CASH_OUT_SUCCESS,
                    'props' => [
                        'rewards' => Utils::formatPropsValue($transaction->amount),
                        'usd' => Utils::formatPropsValue($transaction->real_amount)
                    ],
                    'data' => []
                ];
                $this->fireNotification(Consts::NOTIFY_TYPE_OTHER, $notificationParams);
                break;
            case Consts::PAYMENT_SALE_PENDING:
                $transaction->status = Consts::TRANSACTION_STATUS_PENDING;
                break;
            case Consts::PAYMENT_PAYOUTSBATCH_PROCESSING:
                $transaction->status = Consts::TRANSACTION_STATUS_EXECUTING;
                break;
            case Consts::PAYMENT_SALE_DENIED:
                $transaction->status = Consts::TRANSACTION_STATUS_DENIED;
                $transaction->error_detail = $errorMsg;
                // TO DO failedDepositMail
                break;
            case Consts::PAYMENT_PAYOUTSBATCH_DENIED:
                $transaction->status = Consts::TRANSACTION_STATUS_DENIED;
                $transaction->error_detail = $errorMsg;
                Mail::queue(new FailedWithdrawMail($transaction));

                $notificationParams = [
                    'user_id' => $transaction->user_id,
                    'type' => Consts::NOTIFY_TYPE_WALLET_USD,
                    'message' => Consts::NOTIFY_WALLET_CASH_OUT_FAILED,
                    'props' => [
                        'rewards' => Utils::formatPropsValue($transaction->amount),
                        'usd' => Utils::formatPropsValue($transaction->real_amount)
                    ],
                    'data' => []
                ];
                $this->fireNotification(Consts::NOTIFY_TYPE_OTHER, $notificationParams);
                break;
            default:
                // TODO handling
                break;
        }
        // if ($transactionStatus === Consts::PAYMENT_SALE_COMPLETED) {
        //     $transaction->amount = $this->calculateAmount($transaction);
        //     $this->userService->addMoreBalance($transaction->user_id, $transaction->amount, $transaction->currency);
        // }
        $transaction->save();
        event(new TransactionUpdated($transaction));

        $data = [
            'when' => 'Recevied webhook from Paypal',
            'data' => [
                'transaction_id' => $transaction->transaction_id,
                'amount' => $transaction->real_amount
            ]
        ];
        Aws::performFirehosePut($data, $this->getUser($transaction->user_id));
    }

    private function calculateAmount($transaction)
    {
        if ($transaction->offer_id) {
            $offer = Offer::findOrFail($transaction->offer_id);
            if ($offer) {
                $amount = $offer->coin;
                $user = Auth::user() ?? User::find($transaction->user_id);
                if ($offer->always_bonus || $this->userService->isFirstTimePurchase($user)) {
                    $bonusAmount = BigNumber::new($amount)->mul($offer->bonus);
                    $amount = $bonusAmount->add($amount)->toString();

                    $user->purchase++;
                    $user->save();
                }
            }
            return $amount;
        }

        switch ($transaction->real_currency) {
            // case Consts::CURRENCY_USD:
            //     return CurrencyExchange::usdToCoin($transaction->real_amount);
            //     break;
            case Consts::CURRENCY_COIN:
                return CurrencyExchange::coinToBar($transaction->real_amount);
            case Consts::CURRENCY_BAR:
                return CurrencyExchange::barToCoin($transaction->real_amount);
            default:
                throw new Exception("The {$transaction->real_currency} currency is not found.");
        }
    }

    public function withdraw($input)
    {
        $amountBars = CurrencyExchange::usdToBar($input['real_amount']);
        $this->validateUserBalance(Auth::id(), $amountBars, Consts::CURRENCY_BAR);

        $input['real_currency'] = Consts::CURRENCY_USD;
        $input['currency'] = Consts::CURRENCY_BAR;
        $input['type'] = Consts::TRANSACTION_TYPE_WITHDRAW;
        $input['payment_type'] = Consts::PAYMENT_TYPE_PAYPAL;
        $input['status'] = Consts::TRANSACTION_STATUS_CREATED;
        $input['amount'] = $amountBars;
        $input['message_key'] = Consts::MESSAGE_TRANSACTION_WITHDRAW;
        $input['message_props'] = [
            'usd' => Utils::formatPropsValue($input['real_amount']),
            'rewards' => Utils::formatPropsValue($amountBars)
        ];

        logger()->info('=====Transaction withdraw with paypal creating=====');
        $transaction = $this->createTransaction(Auth::id(), $input, null, Consts::TRANSATION_MEMO_WITHDRAW);

        logger()->info('=====Subtract balance of user before created transaction=====');
        $this->userService->subtractBalance($transaction->user_id, $transaction->amount, $transaction->currency);

        $notificationParams = [
            'user_id' => $transaction->user_id,
            'type' => Consts::NOTIFY_TYPE_WALLET_USD,
            'message' => Consts::NOTIFY_WALLET_CASH_OUT,
            'props' => [
                'rewards' => Utils::formatPropsValue($transaction->amount),
                'usd' => Utils::formatPropsValue($transaction->real_amount)
            ],
            'data' => ['user' => (object) ['id' => $transaction->user_id]]
        ];
        $this->fireNotification(Consts::NOTIFY_TYPE_OTHER, $notificationParams);

        event(new TransactionUpdated($transaction));

        $data = [
            'when' => 'Create cash out with Paypal',
            'data' => [
                'transaction_id' => $transaction->transaction_id,
                'amount' => $transaction->real_amount
            ]
        ];
        Aws::performFirehosePut($data);

        return $transaction;
    }

    private function validateUserBalance($userId, $amount, $currency = Consts::CURRENCY_COIN)
    {
        $userBalance = $this->userService->getUserBalanceAndLock($userId);
        if (BigNumber::new($userBalance->{$currency})->comp($amount) < 0) {
            throw new InsufficientBalanceException();
        };
    }

    public function withdrawPaypal($transaction)
    {
        $order = $this->paypalService->payout(
            $transaction->transaction_id,
            $transaction->paypal_receiver_email,
            $transaction->real_currency,
            $transaction->real_amount
        );

        if (!$order) {
            throw new Exception('Creating withdrawal transaction failer.');
        }

        logger()->info('=====Transaction withdraw with paypal pending=====');
        $transaction->transaction_id = $order->getBatchHeader()->getPayoutBatchId();
        $transaction->status = Consts::TRANSACTION_STATUS_PENDING;
        $transaction->memo = Consts::TRANSACTION_MEMO_WITHDRAW_EXECUTING;
        $transaction->save();

        Mail::queue(new ApprovedWithdrawMail($transaction));
        event(new TransactionUpdated($transaction));

        $data = [
            'when' => 'Executing cash out with Paypal',
            'data' => [
                'transaction_id' => $transaction->transaction_id,
                'amount' => $transaction->real_amount
            ]
        ];
        Aws::performFirehosePut($data, $this->getUser($transaction->user_id));

        return $transaction;
    }

    public function saveErrorDetail($transactionId, $errorDetail)
    {
        $transaction = Transaction::where('transaction_id', $transactionId)->first();
        $transaction->status = Consts::TRANSACTION_STATUS_FAILED;
        $transaction->error_detail = $errorDetail;
        $transaction->save();

        return $transaction;
    }

    public function getHistory($input)
    {
        $paginator = Transaction::where('user_id', Auth::id())
            ->select('status', 'real_currency', 'real_amount', 'amount', 'currency', 'type',
                'payment_type', 'memo', 'message_key', 'message_props', 'created_at', 'updated_at')
            ->where(function ($query) {
                $query->whereIn('status', [
                    Consts::TRANSACTION_STATUS_SUCCESS,
                    Consts::TRANSACTION_STATUS_EXECUTING,
                    Consts::TRANSACTION_STATUS_REJECTED,
                    Consts::TRANSACTION_STATUS_DENIED,
                    Consts::TRANSACTION_STATUS_PENDING,
                    Consts::TRANSACTION_STATUS_FAILED
                ])
                ->orWhere(function ($query2) {
                    $query2->where('status', Consts::TRANSACTION_STATUS_CREATED)
                        ->where('type', Consts::TRANSACTION_TYPE_WITHDRAW);
                });
            })
            ->when(
                !empty($input['sort'])&& !empty($input['sort_type']),
                function ($query) use ($input) {
                    $query->orderBy($input['sort'], $input['sort_type']);
                },
                function ($query) {
                    $query->orderBy('updated_at', 'desc');
                }
            )
            ->paginate(array_get($input, 'limit', Consts::DEFAULT_PER_PAGE));

        $userIds = $paginator->getCollection()->pluck('message_props.user_id');
        $users = User::withoutAppends()->whereIn('id', $userIds)->get()->mapWithKeys(function ($user) {
            return [$user['id'] => $user];
        })->all();

        $paginator->getCollection()->transform(function ($transaction) use ($users) {
            if (empty($transaction->message_props->user_id)) {
                return $transaction;
            }

            $userId = $transaction->message_props->user_id;
            if (!empty($userId) && !empty($users[$userId])) {
                $user = $users[$userId];

                $cloneProps = $transaction->message_props ? (object) cloneDeep($transaction->message_props) : (object) [];
                $cloneProps->username = $user->username;

                $transaction->message_props = $cloneProps;
            }
            return $transaction;
        });

        return $paginator;
    }

    public function getInternalTransaction($input)
    {
        return Transaction::where('user_id', Auth::id())
            ->where('payment_type', Consts::PAYMENT_SERVICE_TYPE_INTERNAL)
            ->when(
                !empty($input['sort'])&& !empty($input['sort_type']),
                function ($query) use ($input) {
                    $query->orderBy($input['sort'], $input['sort_type']);
                },
                function ($query) {
                    $query->orderBy('updated_at', 'desc');
                }
            )
            ->when(
                empty($input['limit']),
                function ($query) {
                    return $query->get();
                },
                function ($query) use ($input) {
                    return $query->paginate($input['limit']);
                }
            );
    }

    public function getDetail($input)
    {
        $isInvalidType = empty($input['type'])
            || !in_array($input['type'], [Consts::TRANSACTION_TYPE_DEPOSIT, Consts::TRANSACTION_TYPE_WITHDRAW]);
        if ($isInvalidType) {
            throw new Exception('Transaction type is invalid.');
        }

        $isInvalidType = empty($input['id']) || is_int($input['id']);
        if ($isInvalidType) {
            throw new Exception('Transaction is invalid.');
        }

        return Transaction::where('user_id', Auth::id())
            ->where('type', $input['type'])
            ->where('id', $input['id'])
            ->select('transaction_id', 'real_currency', 'real_amount', 'amount', 'currency', 'type',
                'payment_type', 'memo', 'created_at', 'updated_at')
            ->when(
                !empty($input['sort'])&& !empty($input['sort_type']),
                function ($query) use ($input) {
                    $query->orderBy($input['sort'], $input['sort_type']);
                },
                function ($query) {
                    $query->orderBy('updated_at', 'desc');
                }
            )
            ->when(
                empty($input['limit']),
                function ($query) {
                    return $query->get();
                },
                function ($query) use ($input) {
                    return $query->paginate($input['limit']);
                }
            );
    }

    private function withdrawInternal($senderId, $receiverId, $amount, $currency, $isTipGamelancer = false)
    {
        $this->userService->subtractBalance($senderId, $amount, $currency);
        if ($isTipGamelancer) {
            $this->userService->addMoreBalance($receiverId, CurrencyExchange::coinToBar($amount), Consts::CURRENCY_BAR);
        } else {
            $this->userService->addMoreBalance($receiverId, $amount, $currency);
        }
    }

    public function getOffers($input)
    {
        return Offer::orderBy('id')->paginate(array_get($input, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    // It is only updated by admin
    public function updateExcuteTransaction($input)
    {
        $transaction = Transaction::findOrFail($input['id']);

         if ($input['is_approved'] == Consts::TRUE) {
            WithdrawalExecutingJob::dispatch($transaction);

            $notificationParams = [
                'user_id' => $transaction->user_id,
                'type' => Consts::NOTIFY_TYPE_WALLET_USD,
                'message' => Consts::NOTIFY_WALLET_CASH_OUT_APPROVED,
                'props' => [
                    'rewards' => Utils::formatPropsValue($transaction->amount),
                    'usd' => Utils::formatPropsValue($transaction->real_amount)
                ],
                'data' => []
            ];
            $this->fireNotification(Consts::NOTIFY_TYPE_OTHER, $notificationParams);

            return $transaction;
         }

        $transaction->status = Consts::TRANSACTION_STATUS_REJECTED;
        $transaction->memo = Consts::TRANSACTION_MEMO_WITHDRAW_REJECT;
        $transaction->save();

        logger()->info('=====Refund balance for user when admin reject transaction=====');
        $this->userService->addMoreBalance($transaction->user_id, $transaction->amount, $transaction->currency);

        $notificationParams = [
            'user_id' => $transaction->user_id,
            'type' => Consts::NOTIFY_TYPE_WALLET_USD,
            'message' => Consts::NOTIFY_WALLET_CASH_OUT_REJECTED,
            'props' => [
                'rewards' => Utils::formatPropsValue($transaction->amount),
                'usd' => Utils::formatPropsValue($transaction->real_amount)
            ],
            'data' => []
        ];
        $this->fireNotification(Consts::NOTIFY_TYPE_OTHER, $notificationParams);

        Mail::queue(new RejectedWithdrawMail($transaction));

        return $transaction;
    }

    public function getTransactionDetail($transactionId)
    {
        return Transaction::find($transactionId);
    }

    public function tip($params, $action = null)
    {
        $senderId = Auth::id();
        $tipAmount = array_get($params, 'tip');

        if (!UserBalance::isEnoughBalance($senderId, $tipAmount)) {
            throw new NotEnoughBalancesException();
        }

        $this->userService->subtractBalance($senderId, $tipAmount);

        $receiverId = array_get($params, 'receiver_id');
        $barReceived = CurrencyExchange::coinToBar($tipAmount);
        $this->userService->addMoreBalance($receiverId, $barReceived, Consts::CURRENCY_BAR);

        switch ($action) {
            case Consts::TIP_VIA_REVIEW:
            case Consts::TIP_VIA_SESSION:
                $memo = Consts::TIP_MEMO_SESSION;
                break;
            case Consts::TIP_VIA_VIDEO:
                $memo = Consts::TIP_MEMO_VIDEO;
                break;
            default:
                $memo = Consts::TIP_MEMO_FREE;
                break;
        }

        $tip = Tip::create([
            'object_id'     => array_get($params, 'object_id'),
            'sender_id'     => $senderId,
            'receiver_id'   => $receiverId,
            'type'          => array_get($params, 'type'),
            'tip'           => $tipAmount,
            'memo'          => $memo,
        ]);

        $this->createTipTransaction($tip, $barReceived);

        $actionParams = [
            'rewards' => $barReceived
        ];
        $this->tipActionHandle($tip, $actionParams, $action);

        return $tip;
    }

    private function tipActionHandle($tip, $params, $action)
    {
        switch ($action) {
            case Consts::TIP_VIA_REVIEW:
                break;

            case Consts::TIP_VIA_SESSION:
                $session = Session::where('id', $tip->object_id)->first();
                if ($session) {
                    event(new TipUpdated($tip->receiver_id, $session));
                }
                $this->createReceiverTipNotification($tip);
                $this->createSenderTipNotification($tip);
                $this->createTipMessage($tip, $params);
                break;

            case Consts::TIP_VIA_VIDEO:
                $this->createReceiverTipNotification($tip, Consts::TRUE);
                $this->createSenderTipNotification($tip, Consts::TRUE);
                break;

            case Consts::TIP_VIA_CHAT:
                $this->createReceiverTipNotification($tip);
                $this->createSenderTipNotification($tip);
                $this->createTipMessage($tip, $params);
                break;
            
            default:
                break;
        }
    }

    private function createTipTransaction($tip, $bars)
    {
        $this->createTipWithdrawTransaction($tip);
        $this->createTipDepositTransaction($tip, $bars);
    }

    private function createTipWithdrawTransaction($tip)
    {
        $message = $tip->type === Consts::OBJECT_TYPE_VIDEO
            ? Consts::MESSAGE_TRANSACTION_TIP_VIDEO_WITHDRAW
            : Consts::MESSAGE_TRANSACTION_TIP_WITHDRAW;
        $props = [
            'amount' => Utils::formatPropsValue($tip->tip),
            'user_id' => $tip->receiver_id
        ];
        $data = [
            'currency'          => Consts::CURRENCY_COIN,
            'amount'            => $tip->tip,
            'payment_type'      => Consts::PAYMENT_SERVICE_TYPE_INTERNAL,
            'type'              => Consts::TRANSACTION_TYPE_WITHDRAW,
            'status'            => Consts::TRANSACTION_STATUS_SUCCESS,
            'message_key'       => $message,
            'message_props'     => $props,
            'internal_type'     => Consts::TRANSACTION_TYPE_TIP,
            'internal_type_id'  => $tip->id
        ];
        $this->createTransaction($tip->sender_id, $data);
    }

    private function createTipDepositTransaction($tip, $bars)
    {
        $message = $tip->type === Consts::OBJECT_TYPE_VIDEO
            ? Consts::MESSAGE_TRANSACTION_TIP_VIDEO_DEPOSIT
            : Consts::MESSAGE_TRANSACTION_TIP_DEPOSIT;
        $props = [
            'amount' => Utils::formatPropsValue($bars),
            'user_id' => $tip->sender_id
        ];
        $data = [
            'currency'          => Consts::CURRENCY_BAR,
            'amount'            => $bars,
            'payment_type'      => Consts::PAYMENT_SERVICE_TYPE_INTERNAL,
            'type'              => Consts::TRANSACTION_TYPE_DEPOSIT,
            'status'            => Consts::TRANSACTION_STATUS_SUCCESS,
            'message_key'       => $message,
            'message_props'     => $props,
            'internal_type'     => Consts::TRANSACTION_TYPE_TIP,
            'internal_type_id'  => $tip->id
        ];
        $this->createTransaction($tip->receiver_id, $data);
    }

    private function createTipMessage($tip, $params)
    {
        $channel = Channel::getChannelBySenderIdAndReceiverId($tip->sender_id, $tip->receiver_id);
        if (!$channel) {
            return;
        }

        $props = [
            'coins' => Utils::formatPropsValue($tip->tip),
            'rewards' => Utils::formatPropsValue(array_get($params, 'rewards'))
        ];
        $systemMessage = SessionSystemMessage::create([
            'channel_id' => $channel->mattermost_channel_id,
            'sender_id' => $tip->sender_id,
            'object_id' => $tip->id,
            'object_type' => Consts::OBJECT_TYPE_TIP,
            'message_key' => Consts::MESSAGE_SESSION_TIP,
            'message_props' => $props,
            'message_type' => Consts::MESSAGE_TYPE_TIP,
            'data' => $tip,
            'is_processed' => Consts::TRUE,
            'started_event' => Utils::currentMilliseconds()
        ]);

        $userIds = [$tip->sender_id, $tip->receiver_id];
        $this->eventSessionMessageUpdated($systemMessage->id, $userIds);

        $message = [
            'key' => Consts::MESSAGE_SESSION_TIP,
            'sender' => $tip->sender_id
        ];
        $this->createPostMessage($channel->mattermost_channel_id, $message, $systemMessage);
    }

    private function createReceiverTipNotification($tip, $isTipVideo = Consts::FALSE)
    {
        $notificationParams = [
            'user_id' => $tip->receiver_id,
            'type' => $isTipVideo ? Consts::NOTIFY_TYPE_VIDEO : Consts::NOTIFY_TYPE_TIP,
            'message' => $isTipVideo ? Consts::NOTIFY_VIDEO_TIP : Consts::NOTIFY_TIP,
            'props' => [
                'rewards' => Utils::formatPropsValue($tip->tip),
                'usd' => Utils::formatPropsValue(CurrencyExchange::barToUsd($tip->tip))
            ],
            'data' => [
                'video_id' => $isTipVideo ? $tip->object_id : null,
                'user' => ['id' => $tip->sender_id]
            ]
        ];
        $this->fireNotification(Consts::NOTIFY_TYPE_OTHER, $notificationParams);
    }

    private function createSenderTipNotification($tip, $isTipVideo = Consts::FALSE)
    {
        $notificationParams = [
            'user_id' => $tip->sender_id,
            'type' => $isTipVideo ? Consts::NOTIFY_TYPE_VIDEO_SEND_TIP : Consts::NOTIFY_TYPE_SEND_TIP,
            'message' => $isTipVideo ? Consts::NOTIFY_VIDEO_SEND_TIP : Consts::NOTIFY_SEND_TIP,
            'props' => ['coins' => Utils::formatPropsValue($tip->tip)],
            'data' => [
                'video_id' => $isTipVideo ? $tip->object_id : null,
                'user' => ['id' => $tip->receiver_id]
            ]
        ];
        $this->fireNotification(Consts::NOTIFY_TYPE_OTHER, $notificationParams);
    }

    private function sendEmailAndNotifyDepositSuccess($transaction, $paymentMethodId = null)
    {
        Mail::queue(new DepositPaymentMail($transaction, $paymentMethodId));

        $notificationParams = [
            'user_id' => $transaction->user_id,
            'type' => Consts::NOTIFY_TYPE_WALLET_COINS,
            'message' => $transaction->without_logged ? Consts::NOTIFY_WALLET_PURCHASE_WITHOUT_LOGGED : Consts::NOTIFY_WALLET_PURCHASE,
            'props' => [
                'coins' => Utils::formatPropsValue($transaction->amount),
                'usd' => Utils::formatPropsValue($transaction->real_amount)
            ],
            'data' => ['user' => ['id' => $transaction->user_id]]
        ];
        $this->fireNotification(Consts::NOTIFY_TYPE_OTHER, $notificationParams);

        event(new DepositSuccess($transaction));
    }

    private function sendNotifyDepositExecuting($transaction)
    {
        $notificationParams = [
            'user_id' => $transaction->user_id,
            'type' => Consts::NOTIFY_TYPE_WALLET_COINS,
            'message' => Consts::NOTIFY_WALLET_PURCHASE_EXECUTING,
            'props' => [
                'coins' => Utils::formatPropsValue($transaction->amount),
                'usd' => Utils::formatPropsValue($transaction->real_amount)
            ],
            'data' => ['user' => ['id' => $transaction->user_id]]
        ];
        $this->fireNotification(Consts::NOTIFY_TYPE_OTHER, $notificationParams);
    }

    private function getUser($userId)
    {
        return User::select('id', 'username', 'email', 'sex', 'avatar')
            ->where('id', $userId)
            ->first();
    }
}
