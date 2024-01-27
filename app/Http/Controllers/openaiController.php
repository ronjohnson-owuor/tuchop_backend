<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenAI;

class openaiController extends Controller
{
       /* response message data structure */
       public function responseMessage($message,$success,$token,$error){
        if($token){
            return response() -> json([
                'message' =>$message,
                'success'=> $success,
                'token'=>$token
            ]);   
        }else{
            return response() -> json([
                'message' =>$message,
                'success'=> $success,
                'errors'=> $error
            ]);  
        }
    }
    
    
    /* this is for asking basic chat from to and from the model its the one used for the free version */
    public function promptModel(Request $request){
        
        /* ask a  genral question */
        try{
            $user_request = $request -> message;
            $api_key = env("OPENAI_KEY");
            $client = OpenAI::client($api_key);
            $result = $client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' =>'please output valid json'],
                    ['role' => 'user', 'content' =>$user_request],
                ]
            ]);
            $message = $result->choices[0]->message->content;
            return $this -> responseMessage($message,true,true,null);
        } catch(\Throwable $th){
            return $this -> responseMessage('there was an error',false,null,$th ->getMessage());
        }

    }
    
    /* ask question relatd to the pdf */
    public function  askPDF(){
        
    }
    
    
    
    
    /* ask question from the image */
    public function promtImage(){
        
    }    
    
    
    
    /* get topic that someone is learning */
    
    
}
