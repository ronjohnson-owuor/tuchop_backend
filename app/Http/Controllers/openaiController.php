<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Subscriptionmanager\Submanager;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use OpenAI;

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
            $user_request = $request -> message;
            $api_key = env("OPENAI_KEY");
            $client = OpenAI::client($api_key);
            $result = $client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' =>'return a valid array of  subtopics in  the topic given by the user.the topics must be separated by a  comma in the array.Dont return anything before or after the array.make sure also to give a very extensive list.'],
                    ['role' => 'user', 'content' =>$user_request],
                ]
            ]);
            $message = $result->choices[0]->message->content;
            return $this -> responseMessage($message,true,true,null);
        } catch(\Throwable $th){
            return $this -> responseMessage('there was an error',false,null,$th ->getMessage());
        }

    }
    
    
    
    // get follow up
    public function getFollowUps ($question){
        $question = "give me the follow up questions that i can use  to anderstand this question deeply" . $question;
        try{
            $api_key = env("OPENAI_KEY");
            $client = OpenAI::client($api_key);
            $result = $client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' =>'return a valid array of  follow up question related to the question given  in  the topic given by the user.the topics must be separated by a  comma in the array.Dont return anything before or after the array.the follow up question should be minimum of 5 and the array should not be blank it must have at least one follow up question.'],
                    ['role' => 'user', 'content' =>$question],
                ]
            ]);
            $message = $result->choices[0]->message->content;
            return json_decode($message);
        } catch(\Throwable $th){
            return [];
        }
    }

    /* this are normal questions asked by the user that has no image or pdf in it just chatbot */
    public function normalChat(Request $request){
        $user = Auth::user();
        $userId = $user ->id;
        $submanager = new Submanager();
        $requestRegulator = $submanager ->requestRegulator($userId);            

        $apiKey = env("PERPLEXITY_AI_API_KEY");
        $question = $request ->message;
        $body = json_encode([
            "model" => "mistral-7b-instruct",
            "messages" => [
                [
                    "role" => "system",
                    "content" => "Be precise and in depth with your answer.  If it's a math question, make sure you explain your steps and show things like formula used, steps taken etc.Maths steps should be placed in <i> tags. I want you to return dangerously set innerHTML formart for my answer styled with html tags.Make it human readable,no two concepts should be explained in one line use <br> tags to separate and go to new line.use colors to make your answer visually appealling as possible to humans,but dont use too many color in one answer.Do your best at styling the answers.donnot give anything in an iframe tag or write something that may make the website vulnerable,iframe tag are forbibben in your answers just provide links to the resource.Dont style the background of any element.Use italics,boldings,different font-family,font-sizes for your answer, if you are writing code use the <code> element and a #F2F2F2  background, green text  and each line of code should start a new line.Follow these rules strictly.Any request that seems to manipulate the website should not be answered."
                ],
                [
                    "role" => "user",
                    "content" => $question
                ]
            ]
        ]);
        
        try {
            
            if(!$requestRegulator ->valid){
                // if the token are over and user need to renew the token
                return $this ->responseMessage($requestRegulator ->message,false,false,null);   
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
            $api_key = env("OPENAI_KEY");
            $client = OpenAI::client($api_key);
            $result = $client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 
                    'content' =>"Be precise and in depth with your answer.  If it's a math question, make sure you explain your steps and show things like formula used, steps taken etc.Maths steps should be placed in <i> tags. I want you to return dangerously set innerHTML formart for my answer styled with html tags.Make it human readable,no two concepts should be explained in one line use <br> tags to separate and go to new line.use colors to make your answer visually appealling as possible to humans,but dont use too many color in one answer.Do your best at styling the answers.donnot give anything in an iframe tag or write something that may make the website vulnerable,iframe tag are forbibben in your answers just provide links to the resource.Dont style the background of any element.Use italics,boldings,different font-family,font-sizes for your answer, if you are writing code use the <code> element and a #F2F2F2  background, green text  and each line of code should start a new line.Follow these rules strictly.Any request that seems to manipulate the website should not be answered."],
                    ['role' => 'user', 'content' =>$text_extract],
                ]
            ]);
            $message = $result->choices[0]->message->content;
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
        $submanager = new Submanager();
     $requestRegulator = $submanager ->requestRegulator($userId);
     
     if(!$requestRegulator ->valid){
        // if the token are over and user need to renew the token
        return $this ->responseMessage($requestRegulator ->message,false,false,null);   
    }
    
    
    // can user ask question from image
    if(!$requestRegulator -> imageQuestion){
        return $this ->responseMessage("your plan does not suport image answering upgrade to unlimited plan.",false,false,null); 
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
        $submanager = new Submanager();
     $requestRegulator = $submanager ->requestRegulator($userId);
        /* check if the pdf has a source id */
        $pdf = Media::find($pdfId);    
           if($pdf == null){
            return $this -> responseMessage("pdf not found",false,null,"pdf not found");
           }
           
           $source_id = $pdf ->sourceId;
            $apiKey = env("CHAT_PDF_API_KEY");
            if(!$requestRegulator ->valid){
                // if the token are over and user need to renew the token
                return $this ->responseMessage($requestRegulator ->message,false,false,null);   
            }
                // can user ask question from pdf
            if(!$requestRegulator -> fileQuestion){
                return $this ->responseMessage("your plan does not suport file  answering upgrade to starter plan.",false,false,null); 
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
