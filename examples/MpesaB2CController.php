<?php

namespace App\Http\Controllers;

use App\User;
use App\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

use Osen\Mpesa\B2C;

class MpesaController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

        B2C::init(
            array(
                'env'               => 'sandbox',
                'type'              => 4,
                'shortcode'         => '173527',
                'headoffice'          => '173527',
                'key'               => 'Your Consumer Key',
                'secret'            => 'Your Consumer Secret',
                'username'          => '',
                'passkey'           => 'Your Online Passkey',
                'validation_url'    => url('mpesa/validate'),
                'confirmation_url'  => url('mpesa/confirm'),
                'callback_url'      => url('mpesa/reconcile'),
                'results_url'       => url('mpesa/timeout'),
            )
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function pay(Request $request)
    {
        $data = $request->all();

        exit(var_dump(B2C));

        try {
            $res = B2C($request->phone, $request->amount, $request->reference);

            if(!isset($res['errorCode'])){
                $data['ref']            = $res->MerchantRequestID;
                $payment                = Payment::create($data);
        
                if($payment){
                    return array('msg' => 'saved' );
                } else {
                    return array('msg' => 'failed' );
                }

                return Redirect::back();
            }
        } catch (\Exception $e) {
            return array('msg' => $e->getMessage );
            return Redirect::back();
        }
    }

    public function reconcile(Request $request, $method = 'mpesa')
    {
        if ($method == 'mpesa') {
            $response = B2C::reconcile($request->getContent(), function ($data)
            {
                $response = json_decode( true );
                return isset( $response['Body']['stkCallback'] ) ? $response['Body']['stkCallback'] : null;
            });
            
            $payment = Payment::where('mpesa', $response['MerchantRequestID'])->first();
            $payment->status = 'Paid';
            if ($payment->save()) {
                return array('status' => 0);
            }
        }
    }

    public function validation()
    {
        return B2C::validate();
    }

    public function confirmation()
    {
        return B2C::confirm();
    }

    public function results()
    {
        return B2C::results();
    }
}