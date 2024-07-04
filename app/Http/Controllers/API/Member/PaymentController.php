<?php

namespace App\Http\Controllers\API\Member;

use App\Http\Controllers\Controller;
use App\Models\Product\Transaction;
use App\Models\YukPaymentToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    protected $url;
    protected $mid;
    protected $grant_type;
    protected $client_id;
    protected $client_secret;

    public function __construct(){
        $this->url = config("payment.url");
        $this->mid = config("payment.mid");
        $this->grant_type = config("payment.grant_type");
        $this->client_id = config("payment.client_id");
        $this->client_secret = config("payment.client_secret");
    }
    public function paymentChannels(Request $request)
    {
        $auth = $this->checkToken();
        $mid = $this->mid;
        // dd($response->result->access_token);
        $res = Http::retry(2,60)->withHeaders(
        ["MID"=>$mid,
        "User-device"=>"DESKTOP"
    ])->withToken($auth)->get($this->url."/api/payment-channels",
    [
        "grouping" => $request->grouping
    ]);

    // dd($res->object());

    return $res->json();

    }

    private function login()
    {
        
        $auth = Http::post(
            $this->url."/api/oauth/token",
            ["grant_type"=>$this->grant_type,
            "client_id"=>$this->client_id,
            "client_secret"=>$this->client_secret,
            ]

        );

        $response = $auth->object();
        $token = $response->result;

        if($response->result){
            $getToken = YukPaymentToken::upsert(
                [
                "token" => $token->access_token,
                "expires_in"=>$token->expires_in,
                "id" => "1"
            ],
            [
                "id"
            ]);
            return $token->access_token;
        }


// dd( $this->url."/api/oauth/token");
        // return $response;
    }

    private function checkToken(){
        $getToken = YukPaymentToken::first();
        if($getToken && $getToken->expires_in > time()){
            return $getToken->token;
    }else{
        return $this->login();
    }
}

public function requestPayments(Request $request)
{    
    $body = [
        'request_time'=> time(),
        
        'payment'=> [
            'pmt_channel_code'=>$request->payment['pmt_channel_code']
        ],
        'va'=> [
            'account_id'=> $request->user()->id,
            'expires_in'=> 600000
        ],

        'order_details'=> [
            'order_id'=> $request->order_details['order_id'],
            'amount'=> $request->order_details['amount']
        ],

        'customer'=> [
            'name'=> $request->user()->name,
            'email'=> $request->user()->email,
            'phone'=> $request->user()->phone ?? 0,
        ],
        'billing'=> [
            'name'=> $request->user()->name,
            'phone'=> $request->user()->phone ?? 0,
        ],
        'session_timeout'=> 1000300,
        'notification_url' => 'http://localhost',
        'callback_url' => 'http://localhost:8000'
    ];
    
    $auth = $this->checkToken();
        $mid = $this->mid;
        // dd($response->result->access_token);
        $res = Http::retry(2,60)->withHeaders(
        ["MID"=>$mid,
        "Accept"=> "application/json",
        "User-device"=>"DESKTOP"
    ])->withToken($auth)->post($this->url."/api/transactions/request-payment", $body );



    return $res->json();
 
}

public function requestVa(Request $request){
    $auth = $this->checkToken();
    $mid = $this->mid;
    $payment_token = $request->payment_token;
    // dd($response->result->access_token);
    $res = Http::retry(2,60)->withHeaders(
    ["MID"=>$mid,
    "Accept"=> "application/json",
    "User-device"=>"DESKTOP"
])->withToken($auth)->post($this->url."/api/transactions/".$payment_token."/pay");


// $response = new JsonResponse($transaction);

return $res->json();

}

public function requestCheckPaymentStatus(Request $request)
{
    $auth = $this->checkToken();
    $mid = $this->mid;
    $order_id = $request->order_id;
    // dd($response->result->access_token);
    $res = Http::retry(2,60)->withHeaders(
    ["MID"=>$mid,
    "Accept"=> "application/json",
    "User-device"=>"DESKTOP"
])->withToken($auth)->get($this->url."/api/transactions/".$order_id);

// $headers = $res->headers();

// error_log(json_encode($headers));


$response = $this->updateStatusTransaction($res, $order_id);




return $response;
} 

public function updateStatusTransaction($res , $order_id) 
{


    if(gettype($res) === 'object')
    {
        if($res['status_code'] === 200)
        {
            $status = $res['result']['status'];
            // error_log($status);
            $transaction = Transaction::find($order_id);
            $transaction->update(['status'=>$status]);

        }

    }
    

return $res->json();
}

public function testRequestPayments(Request $request, $id){

    $query = Transaction::where('id',$id)->first();

    if($query){
        $amount = $query['grandtotal'];
        return response()->json(['message' => $amount]);

    }else {
        $failed = 'data tidak ditemukan';
        return response()->json(['message' => $failed]);
    }


}

public function test2RequestPayments(Request $requests){



    $order_id = $requests->order_id;
    $pmt_channel_code = $requests->pmt_channel_code;
    $query = Transaction::where('id',$order_id)->first();

    if($query){
        $payload = array(
            'request_time'=> time(),
            'payment' => array(
                'pmt_channel_code'=> $pmt_channel_code
            ),
            'va' => array(
                'account_id'=> "12345678",
                "expires_in"=> 600
            ),
            'order_details' => array(
                'order_id'=>$query['id'],
                'amount' => $query['grandtotal'],
                'shipping_fee' => 10000
            ),

            'customer' => array(
                'name' => $requests->user()->name,
                'phone' => $requests->user()->phone ?? 0,
                'email' => $requests->user()->email
            ),
            'billing' => array(
                'name' => $requests->user()->name,
                'phone' => $requests->user()->phone ?? 0,
            ),
            'session_time_out' => '1800',
            'notification_url'=> "http://localhost",
            'callback_url' => "http://localhost:8000"


        );

        $auth = $this->checkToken();
        $mid = $this->mid;
        // dd($response->result->access_token);
        $res = Http::retry(2,60)->withHeaders(
        ["MID"=>$mid,
        "Accept"=> "application/json",
        "User-device"=>"DESKTOP"
    ])->withToken($auth)->post($this->url."/api/transactions/request-payment", $payload );

        // return response()->json(['message' => 'success', 'data'=> $payload]);
        return $res->json();


    }else {
        $failed = 'data kosong';
        return response()->json(['message' => $failed, 'data' => null]);
    }
}
public function yukkPaymentNotification(Request $requests){

    $headers = $requests->headers->all();
    $signature = $requests->header('signature');

    // error_log($signature);


    $client_secrets = "RzzS8LX0OLf1Nyc09sUHPXP5Lv4JleLUiZxxSkI7";
    $order_id = 2;
    $pmt_channel_code = "VA_BCA";
    $amount = 15000;
    $status = "SUCCESS";
    $sha = "sha512";

    $string_to_hash = $client_secrets . "+" . $order_id . "+" . $pmt_channel_code . "+" . $amount . "+" . $status;

    $hash = hash($sha, $string_to_hash);

    if($signature == $hash){
        $status = $requests->status;
        error_log($status);
    }
    else {
        error_log('failed');

    }
    


    return response()->json(['message'=> 'ok', 'data' => $hash],200);

}


}
