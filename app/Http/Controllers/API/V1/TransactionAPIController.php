<?php

namespace App\Http\Controllers\API\V1;

use App\CardUtils;
use App\Http\Requests\AddStripeCardFormRequest;
use App\Http\Requests\EmailPaypalRequest;
use App\Http\Requests\IbanRequest;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Consts;
use App\Utils;
use App\Http\Controllers\AppBaseController;
use App\Http\Services\TransactionService;
use App\Http\Requests\PaypalDepositFormRequest;
use App\Http\Requests\StripeDepositFormRequest;
use App\Http\Requests\StripeDepositWithoutLoggedFormRequest;
use App\Http\Requests\PaypalDepositWithoutLoggedFormRequest;
use App\Http\Requests\WithdrawalFormRequest;
use App\Http\Requests\PaypalSuccessFormRequest;
use App\Http\Requests\TipRequest;
use App\Http\Requests\ConvertBalanceFormRequest;
use PayPal\Exception\PayPalConnectionException;
use Exception;
use DB;
use Auth;
use Stripe\Error\SignatureVerification;
use Stripe\Event;
use Stripe\Stripe;
use Stripe\Webhook;
use App\Events\TransactionUpdated;
use Illuminate\Validation\ValidationException;

class TransactionAPIController extends AppBaseController
{

    private $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * @SWG\Post(
     *   path="/v1/payment/deposit/paypal",
     *   summary="Deposit paypal",
     *   tags={"V1.Payment"},
     *   security={
     *     {"passport": {}},
     *   },
     *  @SWG\Parameter(
     *       name="offer_id",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="hash_url",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function depositPaypal(PaypalDepositFormRequest $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();

            $input['payment_type'] = Consts::PAYMENT_TYPE_PAYPAL;
            $input['referer_url'] = $request->headers->get('referer');

            $data = $this->transactionService->deposit($input);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v1/payment/deposit/stripe",
     *   summary="Deposit stripe",
     *   tags={"V1.Payment"},
     *   security={
     *     {"passport": {}},
     *   },
     *  @SWG\Parameter(
     *       name="offer_id",
     *       in="formData",
     *       required=true,
     *       type="integer"
     *   ),
     *  @SWG\Parameter(
     *       name="payment_method_id",
     *       in="formData",
     *       required=false,
     *       type="string",
     *       description="if the owner doesn't exists credit card, it should be required.."
     *   ),
     *  @SWG\Parameter(
     *       name="save_payment_method",
     *       in="formData",
     *       required=false,
     *       type="integer",
     *       enum={0,1}
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function depositStripe(StripeDepositFormRequest $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $input['payment_type'] = Consts::PAYMENT_TYPE_CREDIT_CARD;
            $data = $this->transactionService->deposit($input);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v1/payment/deposit-without-logged/paypal",
     *   summary="Deposit paypal without logged",
     *   tags={"V1.Payment"},
     *   security={},
     *  @SWG\Parameter(
     *       name="offer_id",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="username",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function depositPaypalWithoutLogged(PaypalDepositWithoutLoggedFormRequest $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $input['payment_type'] = Consts::PAYMENT_TYPE_PAYPAL;
            $input['referer_url'] = $request->headers->get('referer');
            $data = $this->transactionService->deposit($input);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v1/payment/deposit-without-logged/stripe",
     *   summary="Deposit stripe without logged",
     *   tags={"V1.Payment"},
     *   security={},
     *  @SWG\Parameter(
     *       name="offer_id",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="payment_method_id",
     *       in="formData",
     *       required=false,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="username",
     *       in="formData",
     *       required=false,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function depositStripeWithoutLogged(StripeDepositWithoutLoggedFormRequest $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $input['payment_type'] = Consts::PAYMENT_TYPE_CREDIT_CARD;
            $data = $this->transactionService->deposit($input);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function handlePaymentIntent(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->transactionService->handlePaymentIntent($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    /**
     * @SWG\Get(
     *   path="/v1/payment/transaction-detail",
     *   summary="Get transaction detail by Id",
     *   tags={"V1.Payment"},
     *   security={
     *     {"passport": {}},
     *   },
     *  @SWG\Parameter(
     *       name="transaction_id",
     *       in="query",
     *       required=true,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getTransactionDetail(Request $request)
    {
        $data = $this->transactionService->getTransactionDetail($request->input('transaction_id'));
        return $this->sendResponse($data);
    }

    /**
     * @SWG\Post(
     *   path="/v1/payment/withdraw/paypal",
     *   summary="Withdrawal paypal",
     *   tags={"V1.Payment"},
     *   security={
     *     {"passport": {}},
     *   },
     *  @SWG\Parameter(
     *       name="real_amount",
     *       in="formData",
     *       required=true,
     *       type="number"
     *   ),
     *  @SWG\Parameter(
     *       name="paypal_receiver_email",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function withdraw(WithdrawalFormRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->transactionService->withdraw($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;

        }
    }

    /**
     * @SWG\Post(
     *   path="/v1/payment/convert-balance",
     *   summary="Convert balances",
     *   tags={"V1.Payment"},
     *   security={
     *     {"passport": {}},
     *   },
     *  @SWG\Parameter(
     *       name="exchange_offer_id",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function convertBalances(ConvertBalanceFormRequest $request) {
        DB::beginTransaction();
        try {
            $data = $this->transactionService->convertBalances($request->exchange_offer_id);
            DB::commit();
            event(new TransactionUpdated($data));
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    /**
     * @SWG\Get(
     *   path="/v1/payment/transactions/history",
     *   summary="Get transactions by user_id",
     *   tags={"V1.Payment"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getHistory(Request $request) {
        $data = $this->transactionService->getHistory($request->all());
        return $this->sendResponse($data);
    }

    public function getDetail(Request $request) {
        try {
            $data = $this->transactionService->getDetail($request->all());
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            logger()->error($ex);
            return $this->sendError($ex->getMessage());
        }
    }

    /**
     * @SWG\Get(
     *   path="/v1/offers",
     *   summary="Get offers for deposit",
     *   tags={"V1.Payment"},
     *   security={
     *   },
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getOffers(Request $request)
    {
        $params = $request->all();
        $data = $this->transactionService->getOffers($params);
        return $this->sendResponse($data);
    }

    public function getInternalTransaction(Request $request) {
        try {
            $data = $this->transactionService->getInternalTransaction($request->all());
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            logger()->error($ex);
            return $this->sendError($ex->getMessage());
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/user/tip",
    *   summary="Tip User",
    *   tags={"V1.Transactions"},
    *   security={{"passport": {}},},
    *   @SWG\Parameter(name="object_id", in="formData", required=false, type="integer"),
    *   @SWG\Parameter(name="receiver_id", in="formData", required=true, type="integer"),
    *   @SWG\Parameter(name="tip", in="formData", required=true, type="number"),
    *   @SWG\Parameter(name="type", in="formData", required=false, type="string", enum={"session", "bounty", "video"}),
    *   @SWG\Parameter(name="create_message", in="formData", required=false, type="integer", enum={1, 0}),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function tip(TipRequest $request)
    {
        DB::beginTransaction();
        try {
            switch ($request->type) {
                case Consts::OBJECT_TYPE_SESSION:
                    $action = Consts::TIP_VIA_SESSION;
                    break;
                case Consts::OBJECT_TYPE_VIDEO:
                    $action = Consts::TIP_VIA_VIDEO;
                    break;
                case Consts::OBJECT_TYPE_TIP:
                    $action = Consts::TIP_VIA_CHAT;
                    break;
                default:
                    $action = null;
                    break;
            }
            $data = $this->transactionService->tip($request->all(), $action);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
