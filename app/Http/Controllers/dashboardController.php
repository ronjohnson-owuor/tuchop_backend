<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Note;
use App\Models\Promptdata;
use App\Models\Refferall;
use App\Models\Topic;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Throwable;

class dashboardController extends Controller
{
              /* response message data structure */
        public function responseData($message,$success,$data){
            return response() -> json([
                'message' =>$message,
                'success'=> $success,
                'data'=> $data
            ]);  
        }
        
    //manages the  general part of the dashboard. 
    public function general(){
      $userId = Auth::user() -> id;
      $totalTopics = Topic::where('topic_creator', $userId) -> count();
      $totalQueries = Promptdata::where('module_owner_id', $userId) -> count();
      $totalUploads = Media::where('user_id',$userId) -> count();  
       
      $data = [
        'topics' => $totalTopics,
        'queries' => $totalQueries,
        'uploads' => $totalUploads
      ];
      return $this -> responseData("success",true,$data);
    }
    
    
    public function accountOverview(){
        try{
            $userId = Auth::user() -> id;
            $notes = Note::where('creator_id',$userId) -> count(); 
            $refferallData = Refferall::where('refferer',$userId) -> first();
            $accountCreatedOn = Auth::user() -> created_at;
            $daysOnsite = Carbon::now() -> diffInDays($accountCreatedOn);
            $data = [
                "refferals" => $refferallData ? $refferallData -> reffered : 0,
                "awards" => $refferallData ? $refferallData -> award: 0,
                "notes" => $notes,
                "days" => $daysOnsite
            ];
            
            
            
            return $this ->  responseData('success',true,$data);  
        }catch(Throwable $th){
            return $this -> responseData($th->getMessage(),false,[]);
        }

    }
}
