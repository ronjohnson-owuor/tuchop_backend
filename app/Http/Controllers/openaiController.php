<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
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
                    ['role' => 'system', 'content' =>'return a valid array of  subtopics in  the topic given by the user.the topics must be separated by a  comma in the array.Dont return anything before or after th array.make sure also to give a very extensive list.'],
                    ['role' => 'user', 'content' =>$user_request],
                ]
            ]);
            $message = $result->choices[0]->message->content;
            return $this -> responseMessage($message,true,true,null);
        } catch(\Throwable $th){
            return $this -> responseMessage('there was an error',false,null,$th ->getMessage());
        }

    }
    

    /* this are normal questions asked by the user that has no image or pdf in it just chatbot */
    public function normalChat(Request $request){
        $apiKey = env("PERPLEXITY_AI_API_KEY");
        $question = $request ->message;
        $body = json_encode([
            "model" => "mistral-7b-instruct",
            "messages" => [
                [
                    "role" => "system",
                    "content" => "Be precise and concise. Give your answer in the format {question:question the user asked,answer:your answer,follow_up_questions:array of follow up question maximum 5}. Give a valid JSON format for all your responses, nothing to come before or after the JSON format. If it's a math question, make sure you explain your steps."
                ],
                [
                    "role" => "user",
                    "content" => $question
                ]
            ]
        ]);
        
        try {
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
        $messageContent = json_decode($messageContent);
        return $this -> responseMessage($messageContent,true,true,null);
        } catch(Exception $e){
            return $this ->responseMessage("there was an error wait then try again",false,false,$e);
        }
        
    }    
    
    
    // ask questions relatd to an image a user has give
    public function askImage(Request $request){
        $apiKey = env("PERPLEXITY_AI_API_KEY");
        $question = $request ->question;
        $image_url = $request -> url;
        $body = json_encode([
            "model" => "mistral-7b-instruct",
            "messages" => [
                [
                    "role" => "system",
                    "content" => "Be precise and concise. Give your answer in the format {question:question the user asked in string,image:url of the image from  which the question was asked string,answer:your answer in string,follow_up_questions:array of follow up question maximum 5}.  Give a valid JSON format for all your responses, nothing to come before or after the JSON format.For math questions make sure to show your workings for the question within the answer section.use the image link to answer questions from or that are in the image"
                ],
                [
                    "role" => "user",
                    "content" => $question
                ],
                [
                    "role" => "user",
                    "content" => $image_url
                ]
            ]
        ]);
        
        try {
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
        $messageContent = json_decode($messageContent);
        return $this -> responseMessage($messageContent,true,true,null);
        } catch(Exception $e){
            return $this ->responseMessage("there was an error wait then try again",false,false,$e->getMessage());
        }
    }
        
    /* ask question relatd to the pdf */
    public function  askPDF(Request $request){
        $pdfurl = $request ->url;
        $pdfId =  $request -> id;
        $question = $request -> question;
        
        /* check if the pdf has a source id */
        $pdf = Media::find($pdfId);    
           if($pdf == null){
            return $this -> responseMessage("pdf not found",false,null,"pdf not found");
           }
           
           $source_id = $pdf ->sourceId;
            $apiKey = env("CHAT_PDF_API_KEY");
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
