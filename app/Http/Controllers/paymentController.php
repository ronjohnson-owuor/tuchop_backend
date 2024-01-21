<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class paymentController extends Controller
{
     /* response message data structure */
     public function responseData($message,$success,$data){

        return response() -> json([
            'message' =>$message,
            'success'=> $success,
            'data'=> $data
        ]);  
}

    // determine which plan the user wants to pay for and return the price in dollars
    public function whichPlan($plan_number){
        $plan_number = intval($plan_number);
        
        $planArray = array(
            0 =>['name'=>"Basic", "price"=>10.99],
            1 =>["name"=>"Pro","price"=>20.99],
            2 =>["name"=>"yearly","price"=>210.99]   
        );
        /* ensure to apply coversion rate here before returning the price */
        return intVal($planArray[ $plan_number]['price']);
        
    }

    /* generate token for the stk push */
    public function generate_access_token()
    {
        $consumer_key = env("MPESA_CONSUMER_KEY");
        $consumer_secret = env("MPESA_CONSUMER_SECRET");
        $credentials = base64_encode($consumer_key . ":" . $consumer_secret);
        $url = env("MPESA_TOKEN_GENERATION_URL");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . $credentials));
        //setting a custom header
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($curl);
        $access_token = json_decode($curl_response);
        return $access_token->access_token;
    }
    
    
    
    public function mobilePayment(Request $request){
        try{
            $request -> validate([
                'phone_number' => 'required',
                'payment_type' => 'required'
            ]);
        } catch (ValidationException $th){
            return $this -> responseData('error in processing the message',false,null);
        }
        
        
        $BusinessShortCode = env("MPESA_BUSSINESS_SHORTCODE");
        $access_token = $this->generate_access_token();
        $passkey =env("MPESA_PASS_KEY");
        $timestamp= Carbon::rawParse('now')->format('YmdHms');
        /* password is the combination of bussiness shortcode pass key and timestamp to base64 */
        $password = base64_encode($BusinessShortCode.$passkey.$timestamp);
        $Amount= $this ->whichPlan($request->payment_type);
        $PartyA =intval($request ->phone_number);
        $PartyB = env("MPESA_PARTY_B"); /* partyB is the same as the bussiness shortcode */
        $url = env('MPESA_REQUEST_PROCESSING_URL');
  
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization:Bearer ' . $access_token)); //setting custom header and getting the token from the pregenerated access token
        
        
        $curl_post_data = array(
          //Fill in the request parameters with valid values
          'BusinessShortCode' => $BusinessShortCode,
          'Password' => $password,
          'Timestamp' => $timestamp,
          'TransactionType' => 'CustomerPayBillOnline',
          'Amount' => $Amount,
          'PartyA' => $PartyA,
          'PartyB' => $PartyB,
          'PhoneNumber' =>$PartyA,
          'CallBackURL' =>env("MPESA_CALLBACK_URL"),
          'AccountReference' => env("MPESA_ACC_REF"),
          'TransactionDesc' => 'subscribe to pro plan on tuchopAI'
        );
        
        $data_string = json_encode($curl_post_data);
        
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//this prevent ssl request from being sent
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        
        $curl_response = curl_exec($curl);
        $returning_response =json_decode($curl_response);
        return $this -> responseData('payment initiated check your phone to continue',true,$returning_response);
    }
    
    
    
    
    
    
     public function callBackFunction(Request $request) {
        try{
            /* retrieve the request from the callback url */
            $request_data = $request->getContent();
            // Decode the JSON string
            $decodedData = json_decode($request_data);
            $filename ='callback_data.json';
            $now = Carbon::now();
            Storage::disk('public/'.$now)->put($filename,$request_data);
            // // response data for processing
            // $stk_callback = $decodedData ->Body->stkCallback;
            
        
            
            // // Extract required information
            // $merchantRequestId = $stk_callback ->MerchantRequestID;
            // $checkoutRequestId = $stk_callback ->CheckoutRequestID;
            // $resultCode = $stk_callback->ResultCode;

            
            // if ($resultCode == 0) {
            //     // If the transaction is successfull then formart the data and save it in the database
            //     $response_data = $decodedData -> Body ->stkCallback->CallbackMetadata;
            //     $mpesaReceiptNumber = $response_data->Item[1]->Value;
            //     $mpesaAmount = $response_data->Item[0]->Value;
            //     $phoneNumber = $response_data->Item[4]->Value;
                
                
                
            //     // /* insert the data to the database first after receiving the callback */
            //     // Mpesacallbacksprocessing::create([
            //     //     "transaction_receipt" =>$mpesaReceiptNumber,
            //     //     "merchant_Id" =>$merchantRequestId,
            //     //     "checkoutrequest_Id" =>$checkoutRequestId,
            //     //     "processed" =>false,
            //     //     "phone" =>$phoneNumber,
            //     //     "amount" => $mpesaAmount
            //     // ]);
                
                
            //     /* create a json success file in amazon s3 storage */
            //     $formattedData = (Object) [
            //         'MerchantRequestID' => $merchantRequestId,
            //         'CheckoutRequestID' => $checkoutRequestId,
            //         'ResultCode' => $resultCode,
            //         'MpesaReceiptNumber' => $mpesaReceiptNumber,
            //         'PhoneNumber' => $phoneNumber,
            //         'MpesaAmount' => $mpesaAmount
            //     ];
            //     $filename =time().'callback_success.json';
            //     $disk = Storage::disk('s3');
            //     $filepath ="callbacks/".$filename;
            //     $disk->put($filepath,json_encode($formattedData),'private'); 
            // }
        }catch(Exception $exe){
            Log::info("there was an error in storing callback.json in the json ERROR =>".$exe ->getMessage());
        }
    }
    
    function confirmReceipt(Request $request){
        $user = Auth::user();
        $receipt = $request -> receipt;
        $plan_type = $request ->plan;
        
        $receipt_exist = Receipt::where('receipt',$receipt) -> where("confirmed",false) ->first();
        if($receipt_exist){
            $receipt_exist ->confirmed = true;
            $receipt_exist ->save();
            /* TODO: update user membership plan here */
            return $this ->responseData('confirmed payment successfull and plan upgraded',true,null);
        }else{
            return $this ->responseData('payment unsucessfull if there is an error please contact us.',false,null); 
        }
    }
    
    
}
