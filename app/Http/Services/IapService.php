<?php

namespace App\Http\Services;

use App\Models\IapItem;
use App\Models\UserIosPurchased;
use App\Models\UserAndroidPurchased;
use App\Http\Services\UserService;
use App\Consts;
use App\Utils;
use App\Utils\BigNumber;
use ReceiptValidator\iTunes\Validator as iTunesValidator;
use ReceiptValidator\GooglePlay\Validator as PlayValidator;
use ReceiptValidator\GooglePlay\PurchaseResponse as PlayPurchaseResponse;
use Google_Client;
use Google_Service_AndroidPublisher;
use Exception;
use App\Models\Transaction;

class IapService {

    protected $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function getItem($platform)
    {
        return IapItem::where('is_actived', Consts::TRUE)
            ->where('platform', $platform)
            ->get();
    }

    public function purchaseItemIos($userId, $transactionId, $receiptData)
    {
        logger()->info('======iOS Purchased: ', ['transactionId' => $transactionId, 'receiptData' => $receiptData]);

        $validator = new iTunesValidator($this->getEndPointIos());

        $response = $validator->setReceiptData($receiptData)->validate();

        if (! $response->isValid()) {
            throw new Exception("Invalid transaction with code = {$response->getResultCode()}");
        }

        $purchase = collect($response->getPurchases())->first(function ($purchase) use ($transactionId) {
          return $purchase->getTransactionId() === $transactionId;
        });

        if (!$purchase) {
          throw new Exception("Invalid transaction id = {$transactionId}");
        }

        $iapItem = IapItem::where('product_id', $purchase->getProductId())
            ->where('platform', Consts::IAP_PLATFORM_IOS)
            ->first();
        if (!$iapItem) {
          throw new Exception("Invalid product id = {$purchase->getProductId()}");
        }

        UserIosPurchased::create([
          'user_id' => $userId,
          'transaction_id' => $purchase->getTransactionId(),
          'original_transaction_id' => $purchase->getOriginalTransactionId(),
          'product_id' => $purchase->getProductId(),
          'quantity' => $purchase->getQuantity(),
          'purchased_at' => $purchase->getPurchaseDate()->timestamp * 1000,
        ]);

        $this->createTransaction($userId, $iapItem);

        logger()->info("======IAP for iOS:: Purchased {$purchase->getQuantity()} x {$iapItem->product_id}");
        $amount = BigNumber::new($iapItem->coin)->mul($purchase->getQuantity())->toString();
        $this->addCoins($userId, $amount);

        return true;
    }

     public function purchaseItemAndroid($userId, $params)
     {
        logger()->info('======Android Purchased: ', ['params' => $params]);

        $productId = $params['product_id'];
        $token = $params['token'];

        $client = new Google_Client();
        $client->setApplicationName('Gamelancer Backend');
        $client->setScopes([Google_Service_AndroidPublisher::ANDROIDPUBLISHER]);
        $client->setAuthConfig(storage_path('credentials/gamelancer-service-account.json'));

        $validator = new PlayValidator(new Google_Service_AndroidPublisher($client));

        $packageName = env('ANDROID_PACKAGE_NAME');
        $response = $validator->setPackageName($packageName)
            ->setProductId($productId)
            ->setPurchaseToken($token)
            ->validatePurchase();

        logger()->info('======Android Purchased: ', ['purchase' => $response->getRawResponse()]);

        $this->validateAndroidPurchasedTransaction($params, $response);

        $iapItem = IapItem::where('product_id', $productId)
            ->where('platform', Consts::IAP_PLATFORM_ANDROID)
            ->first();
        if (!$iapItem) {
          throw new Exception("Invalid product id = {$productId}");
        }

        logger()->info("======IAP for android:: Purchased {$iapItem->product_id}");

        UserAndroidPurchased::create([
            'user_id' => $userId,
            'package_name' => $packageName,
            'product_id' => $productId,
            'purchase_token' => $token,
            'quantity' => 1,
            'developer_payload' => $response->getDeveloperPayload(),
            'purchase_time_millis' => $response->getPurchaseTimeMillis(),
        ]);

        $this->createTransaction($userId, $iapItem);

        $this->addCoins($userId, $iapItem->coin);
     }

    private function getEndPointIos()
    {
        // if (Utils::isProduction()) {
        //     return iTunesValidator::ENDPOINT_PRODUCTION;
        // }
        return iTunesValidator::ENDPOINT_SANDBOX;
    }

    private function addCoins($userId, $amount)
    {
        $this->userService->addMoreBalance($userId, $amount, Consts::CURRENCY_COIN);
    }

    private function validateAndroidPurchasedTransaction($params, $purchase)
    {
        $productId = $params['product_id'];
        $token = $params['token'];
        $orderId = $params['order_id'];

        if (!$purchase->isAcknowledged() || $purchase->getPurchaseState() !== PlayPurchaseResponse::PURCHASE_STATE_PURCHASED
            || $purchase->getConsumptionState() !== PlayPurchaseResponse::CONSUMPTION_STATE_CONSUMED
            || $purchase->getRawResponse()->orderId !== $orderId) {
            throw new Exception("Invalid transaction with token {$token}");
        }

        $packageName = env('ANDROID_PACKAGE_NAME');
        $exists = UserAndroidPurchased::where('package_name', $packageName)
            ->where('product_id', $productId)
            ->where('purchase_token', $token)
            ->exists();

        if ($exists) {
            throw new Exception('Transaction was already recorded');
        }

        return true;
    }

    private function createTransaction($userId, IapItem $iapItem)
    {
        $currentMillis = Utils::currentMilliseconds();

        Transaction::create([
            'user_id' => $userId,
            'transaction_id' => Utils::generateRandomString(36),
            'real_currency' => Consts::CURRENCY_USD,
            'real_amount' => $iapItem->price,
            'currency' => Consts::CURRENCY_COIN,
            'amount' => $iapItem->coin,
            'payment_type' => Consts::PAYMENT_SERVICE_TYPE_IAP,
            'type' => Consts::TRANSACTION_TYPE_DEPOSIT,
            'status' => Consts::TRANSACTION_STATUS_SUCCESS,
            'memo' => Consts::TRANSACTION_MEMO_DEPOSIT_SUCCESS,
            'created_at' => $currentMillis,
            'updated_at' => $currentMillis
        ]);
    }

}
