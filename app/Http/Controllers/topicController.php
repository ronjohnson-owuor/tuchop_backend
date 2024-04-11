<?php

namespace App\Http\Controllers;

use App\Subscriptionmanager\Submanager;
use Carbon\Carbon;
use App\Models\Topic;
use App\Models\Promptdata;
use Illuminate\Http\Request;
use Alaouy\Youtube\Facades\Youtube;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Throwable;

class topicController extends Controller
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
        
        
        
        /* response message data structure */
        public function responseData($message,$success,$data){

            return response() -> json([
                'message' =>$message,
                'success'=> $success,
                'data'=> $data
            ]);  
    }
        
        

        
        /* authenticate user */
        function Authuser(){
            $user = Auth::user();
            return $user;
        }
        
        
        
        
    public function saveSubtopic(Request $request){
        
       $subscriptionManager = new Submanager();
        
        try{
            $request -> validate([
              'topic_name' =>'required',
              'topics_choosen' =>'required' 
            ]);
        } catch(ValidationException $err){
          return $this ->responseMessage("unable to save the topic wait a minute then try again",false,false,$err->getMessage());  
        }
        
        $user = $this ->Authuser();
        $canCreate = $subscriptionManager -> canCreateTopic($user->id);
        $canCreateTopic = $canCreate ->cancreate;
        $cancreateMessage = $canCreate -> message;
        
        if($canCreateTopic){
            $new_topic = Topic::create([
                'topic_creator' => $user ->id,
                'topic_name' => $request ->topic_name,
                'topics_choosen' => json_encode($request->topics_choosen) //encode the array for storage as a string array
            ]);
            
          return $this -> responseMessage("topic saved start learning",true,true,false);   
        }
        return $this -> responseMessage($cancreateMessage,false,true,false);
 
    }
    
    
    
    public function returnSavedTopics(){
        $user = $this ->Authuser();
        $planType = User::where("id",$user -> id) ->first()->planType;
        $saved_topics = Topic::where('topic_creator',$user ->id) ->orderBy('id','DESC') -> get();
        $topics_remaining = "infinite ♾";
        
        if($planType == 0){
            $topics_remaining = 20 - ($saved_topics -> count());
        }elseif($planType == 1){
            $topics_remaining = 50 - ($saved_topics -> count());  
        }
        
        
        
        
        $formatted_topics = [];
        foreach($saved_topics as  $saved_topic){
           $date_created = Carbon::parse($saved_topic ->created_at) ->format('d-m-Y');
           $topic_name = $saved_topic ->topic_name;
           $topics_choosen = $saved_topic ->topics_choosen;
           $topic_id = $saved_topic ->id;
           
           $new_response = (Object)[
            'topic_id' =>$topic_id,
            'topic_name' => $topic_name,
            'topics_choosen' => $topics_choosen,
            'date_created' => $date_created,
           ];
           $formatted_topics[] = $new_response;
           
        }
        return response() -> json([
            'message' =>null,
            'success'=> true,
            'data'=> $formatted_topics,
            'topics_remaining' =>$topics_remaining
        ]);
    }
    
    
    
    /* get list of topics */
    public function getTopicList(Request $request){
        $user = $this ->Authuser();
        $topic_id = $request ->id;
        $saved_topics = Topic::where('topic_creator',$user ->id) ->where('id',$topic_id)->value('topics_choosen');
        $formatted_topics =json_decode($saved_topics);
        return $this ->responseData(null,true,$formatted_topics);
    }  
    
    /* delete a topic */
    public function deleteTopic(Request $request){
        $topic_id = $request ->id;
        $to_be_deleted = Topic::where('id',$topic_id) ->first();
        if($to_be_deleted){
         $to_be_deleted ->delete();
         return $this ->responseData('topic deleted',true,null);
        }else{
            return $this ->responseData('topic not found',false,null);
        }
    }
    
    
    /* save user topic in the form of question and answer */
    public function savePrompt(Request $request){
        try{
            $request ->validate([
               'module_id'  =>'required',
               'submodule_id' => 'required',
               'question' => 'required',
               'answer' =>'required',
               'follow_up_questions' =>'required'
            ]);
            
        }catch(ValidationException $exe){
           return $this -> responseData('error saving data',false,$exe->getMessage()); 
        }
        
        $user = auth() ->user();
        $module_owner_id = $user ->id;
        
        
        /* save data to the database */
        $module_id = $request ->module_id;
        $submodule_id = $request ->submodule_id;
        $question = $request ->question;
        $answer = $request ->answer;
        $follow_up_question = $request ->follow_up_questions;
        $data = Promptdata::create([
            'module_id' =>$module_id,
            'module_owner_id' =>$module_owner_id,
            'submodule_id' =>$submodule_id,
            'question' => $question,
            'answer' =>$answer,
            'follow_up_question' =>$follow_up_question,
            'videos' => '[]'
        ]);
        return $this ->responseData('data saved',true,$data);
    }
    
    
    public function savedChat(Request $request){
        $user = $this ->Authuser();
        $chats = Promptdata::where('module_id',$request->module_id) -> where('module_owner_id',$user->id) -> get();
        foreach ($chats as $chat) {
            $chat ->saved = true;
            if (json_decode($chat ->videos) == null){
                $chat ->videosId = []; 
            }else{
              $chat ->videosId = json_decode($chat ->videos);  
            }
            
            $chat -> follow_up_question = json_decode($chat -> follow_up_question);
        }
        return $this ->responseData('retrieved',true,$chats);
    }
    
    
        /* delete a topic */
        public function deleteChats(Request $request){
            $topic_id = $request ->id;
            $to_be_deleted = Promptdata::where('id',$topic_id) ->first();
            if($to_be_deleted){
             $to_be_deleted ->delete();
             return $this ->responseData(' chat deleted',true,null);
            }else{
                return $this ->responseData('chat not found',false,null);
            }
        }
        
        public function getAvideo(Request $request){
            $userId = Auth::user() ->id;
            $phrase = $request ->phrase;
            $index = $request -> index;
           $submanager = new Submanager();
         $requestRegulator = $submanager ->requestRegulator($userId);
            try{
                if(!$requestRegulator ->fileQuestion){
                    return $this ->responseMessage('UPGRADE: upgrade to starter plan to request video answers',false,false,"plan not applicable for video");
                }
                

                $results = Youtube::searchVideos($phrase);
                $videoIds =[];
                foreach ($results as $result) {
                     $videoData = Youtube::getVideoInfo($result ->id ->videoId);   
                  $videoId  = (Object)[
                    'index' =>intval($index),
                    'video_id' =>$result ->id ->videoId,
                    'thumbnail' => "https://img.youtube.com/vi/". $result ->id ->videoId . "/hqdefault.jpg",
                    "title" => $videoData -> snippet -> title
                  ];
                  
                  $videoIds[] = $videoId;
                }
                return $this ->responseData('video retrieved',true,$videoIds);
            } catch(Throwable $th){
                return $this ->responseMessage('error in getting videos',false,false,$th->getMessage());
            }

        }
        
        
 public function saveVideo(Request $request) {
    $question_id = $request->question_id;
    $video_id = $request->video_id;

    $question_location = Promptdata::find($question_id);

    if (!$question_location) {
        // Handle case where the prompt data with the given ID is not found
        return $this->responseData('cannot save video', false,[]);
    }

    $videos = json_decode($question_location->videos) ?? [];
    
    foreach($videos as $video){
        if($video -> video_id == $video_id){
            return $this -> responseData('video already saved',false,null);
        }
    }

    $new_video = [
        'index' => intval($question_id),
        'video_id' => $video_id
    ];

    $videos[] = $new_video;

    $question_location->videos = json_encode($videos);
    $question_location->save();

    return $this->responseData('Video saved', true, $videos);
}



// edit subtopic of a topic
public function updateSubTopics(Request $request){
    $user = Auth::user();
    $user_id = $user -> id;
    $topic_id = $request -> topic_id;
    $new_array = $request -> new_topic;
    $topic = Topic::find($topic_id);
    // make sure the person editing topic is the owner
    if (!$topic || $user_id != $topic ->topic_creator){
        return $this ->responseData("topics not edited make sure you are the owner or the topic cannot be found at the moment",false,null);
    }
    
    // find the topics
    try{
        
    } catch (Throwable $th){
        return $this ->responseData("topics not edited",false,null);
    }
    $topic ->topics_choosen = $new_array;
    $topic ->save();
    return $this ->responseData("topics successfully edited",true,null);
}

    
    
}
