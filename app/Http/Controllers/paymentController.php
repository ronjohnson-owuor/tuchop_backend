<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Models\Token;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Unicodeveloper\Paystack\Facades\Paystack;

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


    /* card payment and also mobile payment too using paystack  */
    public function cardPayment(Request $request){
        $user = Auth::user();
        $plan_number = $request ->plan_number;
        $planArray = [
            1 =>1605,
            2 =>3048,
            3 =>35309   
        ];
        
        if(!$plan_number){
            return $this -> responseData('payment not successfull refresh and try again',false,null);
        }
        $amount = ($planArray[$plan_number]) *100;
        $now = Carbon::now();
        $reference = $now->format('YmdHisv');
        $hashedReference = hash('sha256', $reference);
        $user_id = $user -> id;
        $data = array(
            "amount" => $amount,
            "email" =>env("MERCHANT_EMAIL"),
            "reference" => $hashedReference,
            "currency" => "KES"
        );
        
        /* save the reffrence to the database and the type of subscription someone is subscribing to so that when the payment is over we will use it to update his plan. */
        Receipt::create([
            "receipt" =>$hashedReference,
            "userid" =>$user_id,
            "completed" =>false,
            "plan" => $plan_number
        ]);
        
        try{
            $redirectData = Paystack::getAuthorizationUrl($data);
            return $this -> responseData("transaction initiated redirecting ....",true,$redirectData);
        }catch(Exception $e) {
            return $this -> responseData("transaction  not successfull refresh and try again",false,null);
        }        
    }
    
    
    
    
    public function cardCallback(){
        $paymentDetails = Paystack::getPaymentData();
        $reference = $paymentDetails['data']['reference'];  /* the payment details is an array not object so 
        ignore the error */
        $paymentStatus = $paymentDetails['status'];
        
        if ( $paymentStatus == 1) {
            $payment_receipt = Receipt::where('receipt',$reference) ->where('completed',false) -> first();
            $user_id = $payment_receipt ->userid;
            $plan_number = $payment_receipt ->plan;
           
            $user = User::find($user_id);
            if ($user) {
                $expiry_date = now();
                if ($plan_number == 3) {
                    $expiry_date->addYear();
                } else {
                    $expiry_date->addMonth();
                }
                $user->update([
                    'planType' => $plan_number,
                    'expiry_date' => $expiry_date,
                ]);
                $userToken = Token::where("user_id",$user_id)->first();
                $userToken -> tokens = $plan_number == 1 ? 20000:1000000000; //just give unlimited token to the users above plantype 1;
                $userToken -> expiry = $expiry_date;
                //new expiry and new token given to user.
                $userToken ->save();
            }
            $payment_receipt->update(['completed' => true]);
            echo "
            <!DOCTYPE html>
            <html lang='en'>
            
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Thank You for Subscribing</title>
            </head>
            
            <body style='font-family: Verdana, sans-serif; background-color: #f8f8f8; text-align: center; padding: 50px;'>
                <div style='max-width: 600px; margin: 0 auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);'>
                    <h1 style='font-weight: 400; color: #ff8c00;'>Thank You for Subscribing to TUCHOP AI</h1>
                    <p style='color: #555;'>Your payment was successful. We appreciate your support!</p>
                    <p style='color: #555;'>Click the button below to go back home:</p>
                    <a style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #ff8c00; color: #fff; text-decoration: none; border-radius: 5px; transition: background-color 0.3s ease;'
                        href='http://localhost:5000'>Back Home</a>
                    <p style='color: #555;'>Thanks for the transaction, by the way. 🤗</p>
                </div>
            </body>
            
            </html>
        ";
                   
        }else{
            echo "
            <!DOCTYPE html>
            <html lang='en'>
            
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Payment Unsuccessful</title>
            </head>
            
            <body style='font-family: Verdana, sans-serif; background-color: #f8f8f8; text-align: center; padding: 50px;'>
                <div style='max-width: 600px; margin: 0 auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);'>
                    <h1 style='font-weight: 400; color: #dc3545;'>Payment Unsuccessful</h1>
                    <p style='color: #555;'>We apologize for the inconvenience. It seems there was an issue with your payment.</p>
                    <p style='color: #555;'>Click the button below to retry:</p>
                    <a style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #dc3545; color: #fff; text-decoration: none; border-radius: 5px; transition: background-color 0.3s ease;'
                        href='http://localhost:5000/pricing'>Retry</a>
                    <p style='color: #555;'>We appreciate your patience and understanding. Please try again.</p>
                </div>
            </body>
            
            </html>
        ";
        }
        
    }
}
