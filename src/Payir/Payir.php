<?php
namespace Hosseinizadeh\Gateway\Payir;

use Hosseinizadeh\Gateway\Enum;
use Hosseinizadeh\Gateway\PortAbstract;
use Hosseinizadeh\Gateway\PortInterface;

class Payir extends PortAbstract implements PortInterface
{
    /**
     * Address of main CURL server
     *
     * @var string
     */
    protected $serverUrl = 'https://pay.ir/pg/send';

    /**
     * Address of CURL server for verify payment
     *
     * @var string
     */
    protected $serverVerifyUrl = 'https://pay.ir/pg/verify';
    /**
     * Address of gate for redirect
     *
     * @var string
     */
    protected $gateUrl = 'https://pay.ir/pg/';

    /**
     * factor number
     *
     * @var string
     */
    protected $factorNumber;

    /**
     * mobile number
     *
     * @var string
     */
    protected $mobile;

    /**
     * description
     *
     * @var string
     */
    protected $description;

    /**
     * {@inheritdoc}
     */
    public function set($amount)
    {
        $this->amount = $amount * 10;
        return $this;
    }

    /**
     * تعیین شماره فاکتور (اختیاری)
     *
     * @param $factorNumber
     *
     * @return $this
     */
    public function setFactorNumber($factorNumber)
    {
        $this->factorNumber = $factorNumber;
        return $this;
    }

    /**
     * تعیین موبایل (اختیاری)
     *
     * @param $mobile
     *
     * @return $this
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;
        return $this;
    }

    /**
     * تعیین توضیحات (اختیاری)
     *
     * @param $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function ready()
    {
        $this->sendPayRequest();
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function redirect()
    {
        return redirect()->to($this->gateUrl . $this->refId);
    }

    /**
     * {@inheritdoc}
     */
    public function verify($transaction)
    {
        parent::verify($transaction);
        $this->userPayment();
        $this->verifyPayment();
        return $this;
    }

    /**
     * Sets callback url
     *
     * @param $url
     */
    function setCallback($url)
    {
        $this->callbackUrl = $url;
        return $this;
    }

    /**
     * Gets callback url
     * @return string
     */
    function getCallback()
    {
        if (!$this->callbackUrl)
            $this->callbackUrl = $this->config->get('gateway.payir.callback-url');
        return urlencode($this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]));
    }

    /**
     * Send pay request to server
     *
     * @return void
     *
     * @throws PayirSendException
     */
    protected function sendPayRequest()
    {
        $this->newTransaction();
        $fields = [
            'api'      => $this->config->get('gateway.payir.api'),
            'amount'   => $this->amount,
            'redirect' => $this->getCallback(),
        ];

        if (isset($this->factorNumber))
            $fields['factorNumber'] = $this->factorNumber;

        if (isset($this->mobile))
            $fields['mobile'] = $this->mobile;

        if (isset($this->description))
            $fields['description'] = $this->description;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->serverUrl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $response = json_decode($response, true);
        curl_close($ch);
        if (is_numeric($response['status']) && $response['status'] > 0) {
            $this->refId = $response['token'];
            $this->transactionSetRefId();
            return true;
        }
        $this->transactionFailed();
        $this->newLog($response['errorCode'], PayirSendException::$errors[ $response['errorCode'] ]);
        throw new PayirSendException($response['errorCode']);
    }

    /**
     * Check user payment with GET data
     *
     * @return bool
     *
     * @throws PayirReceiveException
     */
    protected function userPayment()
    {
        $status = Request('status');
        $transId = Request('transId');
        $this->cardNumber = Request('cardNumber');
        $message = Request('message');
        if (is_numeric($status) && $status > 0) {
            $this->trackingCode = $transId;
            return true;
        }
        $this->transactionFailed();
        $this->newLog(-5, $message);
        throw new PayirReceiveException(-5);
    }

    /**
     * Verify user payment from zarinpal server
     *
     * @return bool
     *
     * @throws PayirReceiveException
     */
    protected function verifyPayment()
    {
        $fields = [
            'api'     => $this->config->get('gateway.payir.api'),
            'token' => $this->refId(),
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->serverVerifyUrl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $response = json_decode($response, true);
        curl_close($ch);
        if ($response['status'] == 1) {
            $this->transactionSucceed();
            $this->newLog(1, Enum::TRANSACTION_SUCCEED_TEXT);
            return true;
        }

        $this->transactionFailed();
        $this->newLog($response['errorCode'], PayirReceiveException::$errors[ $response['errorCode'] ]);
        throw new PayirReceiveException($response['errorCode']);
    }
}
