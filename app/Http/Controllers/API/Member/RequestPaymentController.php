<?php

namespace App\Http\Controllers\API\Member;

use App\Http\Controllers\Controller;
use App\Models\YukPaymentToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
    
    
    
    // public function paymentChannel(Request $request)
    // {
    // $url = config('payment.url');

    //     return ApiRequestor::post(
    //         $url + 'api/payment-channels',
    //         Config::$serverKey,
    //         $request
    //     );
    // }
}
