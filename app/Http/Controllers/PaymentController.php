<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use PayPal\Api\Amount;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Rest\ApiContext;

class PaymentController extends Controller
{

    private $_api_context;
    private $paypal_config;

    public function __construct()
    {
        $this->paypal_config = \Config::get('paypal');

        $this->_api_context = new ApiContext(new OAuthTokenCredential($this->paypal_config['client_id'],$this->paypal_config['secret']));

        $this->_api_context->setConfig($this->paypal_config['settings']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('dashboard.payment.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function postPayment()
    {
        $payer = new Payer();

        $payer->setPaymentMethod('paypal');

        $item1 = new Item();
        $item1->setName('Template')
            ->setCurrency('USD')
            ->setQuantity(1)
            ->setPrice('10');

        $itemList = new ItemList();
        $itemList->setItems([$item1]);

        $amount = new Amount();
        $amount->setCurrency('USD')
            ->setTotal(10);

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setDescription('Template');

        $redirects_urls = new RedirectUrls();
        $redirects_urls->setReturnUrl(\URL::route('payment.status'))
            ->setCancelUrl(\URL::route('payment.status'));

        $payment = new Payment();
        $payment->setIntent('Sale')
            ->setPayer($payer)
            ->setRedirectUrls($redirects_urls)
            ->setTransactions([$transaction]);

        try {
            $payment->create($this->_api_context);
        } catch(PayPalConnectionException $ex){
            echo "Exception: " . $ex->getMessage() . PHP_EOL;
            $err_data = json_decode($ex->getData(), true);
            exit;
        }

        foreach($payment->getLinks() as $link){
            if($link->getRel() == 'approval_url'){
                $redirect_url = $link->getHref();
                break;
            }
        }

        \Session::put('paypal_payment_id',$payment->getId());

        if(isset($redirect_url)){
            return \Redirect::away($redirect_url);
        }

    }

    public function paymentStatus(Request $request)
    {
        $payment_id = \Session::get('paypal_payment_id');
        dd($request->all());
//        \Session::forget('paypal_payment_id');
    }
}
