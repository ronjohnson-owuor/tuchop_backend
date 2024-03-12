<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Create;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class notesController extends Controller
{
    
          /* response message data structure */
    public function responseData($message,$success,$data){

            return response() -> json([
                'message' =>$message,
                'success'=> $success,
                'data'=> $data
            ]);  
    } 
    
    
    //get notes from wikipedia
    public function  getNotes(Request $request){
        $notes_title = $request -> title;
        $client = new Client();
        try{
        $url = "https://en.wikipedia.org/w/api.php?action=query&format=json&prop=extracts&rvprop=content&titles=".$notes_title;
                $wiki_response = $client ->get($url)->getBody();
                $wiki_decoded = json_decode($wiki_response, true);
                // actual notes wrapped  in html tags
                $wiki_extract = "<p>try again </p>";
                foreach ($wiki_decoded['query']['pages'] as $page) {
                    $wiki_extract = $page['extract'];
                    break;
                }
                return $this ->responseData("notes retrieved",true,$wiki_extract);           
        }catch(Throwable $th){
            return $this ->responseData('an error occured,that resource may be missing, try another variation of what you are searching',false,$th->getMessage());
        }
    }    
    
    
        //get topics suggesstions
        public function  getTopic(Request $request){
            $notes_title = $request -> title;
            $client = new Client();
            try{
            $url = "https://en.wikipedia.org/w/api.php?action=query&list=search&format=json&srsearch=".$notes_title;
                    $wiki_response = $client ->get($url)->getBody();
                    $wiki_decoded = json_decode($wiki_response, true);
                    $wiki_extract =[];
                    foreach ($wiki_decoded['query']['search'] as $search) {
                        $wiki_extract[] = $search["title"];
                    }
                    return $this ->responseData("topics retrieved",true,$wiki_extract);           
            }catch(Throwable $th){
                return $this ->responseData('an error occured,that resource may be missing, try another variation of what you are searching',false,$th->getMessage());
            }
        }  
        
    // save user notes to the database
        public function saveNotes(Request $request){
            try{
            $title = $request -> title;
            $notes = $request -> notes;
           $user_id = Auth::user() -> id;
         
                Note::create([
                "creator_id" => $user_id,
                "title" => $title,
                "notes" => $notes
            ]);
            return $this -> responseData("notes saved ",true,null);
           } catch(Throwable $th){
            return $this -> responseData("an error occured try again",false,$th->getMessage());
           }  
        }
        
        public function getSavedNotes(){
            
            try{
                $user_id = Auth::user() ->id;
                $unfiltered_notes = Note::where('creator_id',$user_id) -> get();
                $filtered_notes = [];
                foreach($unfiltered_notes as $unfiltered_note){
                    $create_at = Carbon::parse($unfiltered_note ->created_at) -> format("d/m/yy");
                    $new_note = (Object)[
                        "id" => $unfiltered_note -> id,
                        "title" => $unfiltered_note -> title,
                        "created_at" => $create_at
                    ];
                    $filtered_notes[] = $new_note;
                }
                
                return $this -> responseData("notes retrieved",true,$filtered_notes);                
            } catch(Throwable $th){
                return $this -> responseData("unable to retrieve notes",false,null);
            }

        }
        
        
        public function getEditNotes(Request $request){
            try{
                $notes_id = $request -> id;
                $notes = Note::find($notes_id);
                $notes_body = $notes -> notes;
                return $this -> responseData("notes retrieved",true,$notes_body);                
            }catch(Throwable  $th){
                $message = "<p>" . $th ->getMessage() . "</p>";
                return $this -> responseData("unable to retrieve notes at the moment",false,$message);     
            }

         }
         
         
        //  save edited notes
        public function saveEditedNotes(Request $request){
            try{
                $notes_id = $request -> id;
                $edited_notes = $request -> notes;
                $notes = Note::find($notes_id);
                $notes -> notes = $edited_notes;
                $notes ->save();
                return $this -> responseData("notes saved",true,null);
            }catch(Throwable  $th){
                $message = $th ->getMessage();
                return $this -> responseData("not saved try again",false,$message);     
            }

        }
        
        
        public function deleteNotes(Request $request){
            try {
                $id = $request -> id;
                $note = Note::find($id);
                $note -> delete();
                return $this -> responseData("notes deleted",true,null);
                
            } catch (Throwable $th) {
                return $this -> responseData($th ->getMessage(),true,null);
            }
        }
        
}
