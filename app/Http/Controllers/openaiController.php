<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Subscriptionmanager\Express;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class openaiController extends Controller
{
    
    public function responseData($message,$success,$data){

        return response() -> json([
            'message' =>$message,
            'success'=> $success,
            'data'=> $data
        ]);
      }
        
        
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
    
    
        /* this is used to generate lesson sub topic from open-ai get the topics that the user want to learn  */
        public function getTopic(Request $request){
            try{
                $apiKey = env("PERPLEXITY_AI_API_KEY");
                $client = new Client();
                $user_request = $request -> message;
                $body = json_encode([
                    "model" => "mistral-7b-instruct",
                    'messages' => [
                        [
                            'role' => 'system', 
                            'content' =>'the user has provided a topic he wants to study down below.your main task is to return a javascript array of the subtopic in the topic given.follow these rule.
                            1.RETURN A VALID JAVASCRIPT ARRAY. dont return anything before or after the array.this rule must be followed very strictly.
                            2.nothing should come before or after the array because the array will be parsed and mapped over
                            3.the array should be extensive and cover all the concept.'
                        ],
                        ['role' => 'user', 'content' =>$user_request],
                    ]
                ]);
                $response = $client-> request('POST', 'https://api.perplexity.ai/chat/completions', [
                    'body' => $body,
                    'headers' => [
                        'accept' => 'application/json',
                        'content-type' => 'application/json',
                        'Authorization' => 'Bearer ' . $apiKey,
                ],
                ]);
                $responseData = json_decode($response->getBody(), true);
                // Extract message content
                $message = $responseData['choices'][0]['message']['content'];
                $startPosition = strpos($message,'[');
                $endPosition = strpos($message,']');
                if($startPosition != false && $endPosition != false){
                    $subArrayString = substr($message, $startPosition, $endPosition - $startPosition + 1);
                    $message = $subArrayString;
                }
                return $this -> responseMessage($message,true,true,null);
            } catch(\Throwable $th){
                return $this -> responseMessage('there was an error',false,null,$th ->getMessage());
            }

        }
    
    
    
    // get follow up
    public function getFollowUps ($question){
        $question = "give me the follow up questions that i can use  to anderstand this question deeply" . $question;
        try{
            $api_key = env("PERPLEXITY_AI_API_KEY");
            $client = new Client();
            $body = json_encode([
                "model" => "mistral-7b-instruct",
                'messages' => [
                    [
                        'role' => 'system', 
                        'content' =>'the user has provided a question down below.your main task is to return a javascript array of related questions that can help understand the question given.give a maximum of five questions.'
                    ],
                    ['role' => 'user', 'content' =>$question],
                ]
            ]);
            
            $response = $client-> request('POST', 'https://api.perplexity.ai/chat/completions', [
                'body' => $body,
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    'Authorization' => 'Bearer ' . $api_key,
            ],
            ]);
            $responseData = json_decode($response->getBody(), true);
            // Extract message content
            $message = $responseData['choices'][0]['message']['content'];
            $startPosition = strpos($message,'[');
            $endPosition = strpos($message,']');
            if($startPosition != false && $endPosition != false){
                $subArrayString = substr($message, $startPosition, $endPosition - $startPosition + 1);
                $message = $subArrayString;
            }
            return json_decode($message);
        } catch(\Throwable $th){
            return [];
        }
    }

    /* this are normal questions asked by the user that has no image or pdf in it just chatbot */
    public function normalChat(Request $request){
        $user = Auth::user();
        $userId = $user ->id;
        $requestFilter = new Express();        

        $apiKey = env("PERPLEXITY_AI_API_KEY");
        $question = $request ->message;
        $body = json_encode([
            "model" => "mistral-7b-instruct",
            "messages" => [
                [
                    "role" => "system",
                    "content" => "Return accurate answer to the question given by the user below.Return your answer in the html formart.For easier styling."
                ],
                [
                    "role" => "user",
                    "content" => $question
                ]
            ]
        ]);
        
        try {
            
            if(!$requestFilter ->filterRequest($userId)){
                // if the token are over and user need to renew the token
                return $this ->responseMessage("Your wallet does not allow the request top up",false,false,null);   
            }
              $client = new Client();
        $response = $client-> request('POST', 'https://api.perplexity.ai/chat/completions', [
          'body' => $body,
          'headers' => [
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'Authorization' => 'Bearer ' . $apiKey,
          ],
        ]);
        $responseData = json_decode($response->getBody(), true);
        // Extract message content
        $messageContent = $responseData['choices'][0]['message']['content'];
        $followups = $this ->getFollowUps($question);
        $data = (object)[
            "question" => $question,
            "answer" => $messageContent,
            "follow_up_questions" => $followups ? $followups : ['follow up questions will appear here if there is any 😎']
        ];
        return $this -> responseMessage($data,true,true,null);  
        } catch(Exception $e){
            return $this ->responseMessage("there was an error wait then try again or check your internet just incase",false,false,$e);
        }
        
    }    
    
    

    
    // extract text from image
    public function extractTextfromImage($image_url){
        try {
            $response = Http::withHeaders([
                'apikey' => env("APILAYER_KEY")
            ])->get('https://api.apilayer.com/image_to_text/url?url=' . $image_url);
            $text_json = json_decode($response,true);
            $text = $text_json["all_text"];
            // this is the text extracted from the image
            return $text;
        } catch (Exception $exception) {
            return 500; //there was an error
        }
        
    }
    
    
    //CREATE YOUR OWN OCR PACKAGE AND USE IT IN YOUR PROJECT
    public function originalTextExtract(Request $request){
       return "ocr will be here";
    } 
    
    
    
    
    
    // ask image  question to get answer
    public function getAnswerForTheImge($text_extract){
        try{
            $apiKey = env("PERPLEXITY_AI_API_KEY");
                $client = new Client();
                $body = json_encode([
                    "model" => "mistral-7b-instruct",
                    'messages' => [
                        ['role' => 'system', 
                            'content' =>"Return accurate answer to the question given by the user below.Return your answer in the html formart.For easier styling.
                        "],
                        ['role' => 'user', 'content' =>$text_extract],
                    ]
                ]);
                $response = $client-> request('POST', 'https://api.perplexity.ai/chat/completions', [
                    'body' => $body,
                    'headers' => [
                        'accept' => 'application/json',
                        'content-type' => 'application/json',
                        'Authorization' => 'Bearer ' . $apiKey,
                ],
                ]);
                $responseData = json_decode($response->getBody(), true);
                // Extract message content
                $message = $responseData['choices'][0]['message']['content'];
                return $message;
        } catch(\Throwable $th){
            return 500;
        }
    }
    
    public function askImage(Request $request) {
        $image_url = $request->url;
        $question = $request->question;
        
        $user = Auth::user();
        $userId = $user -> id;
        
        $requestFilter = new Express();
     
     if(!$requestFilter ->filterRequest($userId)){
        // if user money is over
        return $this ->responseMessage("your wallet is empty",false,false,null);   
    }
       $text = $this ->extractTextfromImage($image_url);
        if($text == 500){
            $error_data =[
                "question" => $question,
                "answer" => "there was an error or the image has no text.Our image model is still under development and might not be that good with working or recognising images fully but we are working on it.ON THE OTHER SIDE TRY CHECKING YOUR INTERNET CONNECTION ,YOU MAY BE DISCONNECTED",
                "follow_up_questions" => ["try another image with text in it","wait  moment then try again"]
            ];
            return $this ->responseMessage($error_data,true,false,"no text found");
        }else{
            //ask the i now to return the answers to the question
            $text = $question." after the semi collon : ".$text;
            $text_answer = $this -> getAnswerForTheImge($text);
            
            if($text == 500){
                $error_data =[
                    "question" => $question,
                    "answer" => "there was an error or the image has no text.Our image model is still under development and might not be that good with working or recognising images fully but we are working on it.ON THE OTHER SIDE TRY CHECKING YOUR INTERNET CONNECTION ,YOU MAY BE DISCONNECTED",
                    "follow_up_questions" => ["error on our side,wait a moment we re on it","wait  moment then try again or refresh the page"]
                ];
                return $this ->responseMessage($error_data,true,false,"no text found");  
            }
            $data = [
                "question" => $question,
                "answer" => $text_answer,
                "follow_up_questions" => ["image question suggestion not available"]
            ];

            return $this ->responseMessage($data,true,true,null);
        }   
    } 
    
    
    
        
    /* ask question relatd to the pdf */
    public function  askPDF(Request $request){
        $pdfurl = $request ->url;
        $pdfId =  $request -> id;
        $question = $request -> question;
        $user = Auth::user();
        $userId = $user -> id;
        $requestFilter = new Express();
     
        /* check if the pdf has a source id */
        $pdf = Media::find($pdfId);    
           if($pdf == null){
            return $this -> responseMessage("pdf not found",false,null,"pdf not found");
           }
           
           $source_id = $pdf ->sourceId;
            $apiKey = env("CHAT_PDF_API_KEY");
            if(!$requestFilter ->filterRequest($userId)){
                // if the token are over and user need to renew the token
                return $this ->responseMessage("check your wallet and top up",false,false,null);   
            }
            $client = new Client();
           if($source_id == null){
            /* get source id from chat PDF api and save it to the database */
            $body =  json_encode([
                "url" => $pdfurl
            ]);
            
            try{
                $response = $client-> request('POST', 'https://api.chatpdf.com/v1/sources/add-file', [
                    'body' => $body,
                    'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    'x-api-key' => $apiKey,
                    ],
                ]);
                // get response data 
                $responseData = json_decode($response->getBody(), true);
                $source_id = $responseData["sourceId"];
                $pdf -> sourceId = $source_id;
                $pdf -> save();
            } catch (Exception $exe){
                return $this  -> responseMessage($exe->getMessage(),true,false,"source ID not assigned");  
            }  
           }
            /* PART 2 - ASK QUESTIONS FROM THE ALREADY UPLOADED PDF */
            $body = json_encode([
                "referenceSources" => true,   //show the page in which the source was found\\'/}+
                "sourceId" => $source_id,
                "messages" => [
                [
                "role" => "user",
                "content" => $question
                ]
                ]
            ]);
           try{
            $response = $client-> request('POST', 'https://api.chatpdf.com/v1/chats/message', [
                'body' => $body,
                'headers' => [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'x-api-key' => $apiKey,
                ],
            ]);
            // get response data 
            $responseData = json_decode($response->getBody(), true);
            $answer = $responseData["content"];
            
            $data = [
                "message" =>"answer retrieved from pdf",
                "question" => $question,
                "answer" => $answer,
                "follow_up_questions" => [] //no followup questions
            ];
            
            return $this  ->responseMessage($data,true,true,null);
        } catch (Exception $exe){
            return $this  -> responseMessage("there was an error check your wifi or refresh",true,false,"source ID not assigned");  
        }  
    }
    
    
}
