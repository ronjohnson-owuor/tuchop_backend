<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Subscriptionmanager\Submanager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class uploadController extends Controller
{
    
        /* authenticate user */
        function Authuser(){
        $user = Auth::user();
        return $user;
    }
        
     /* response message data structure */
     public function responseData($message,$success,$data){

        return response() -> json([
            'message' =>$message,
            'success'=> $success,
            'data'=> $data
        ]);  
}



    
    /* upload image */
    public function uploadImage(Request $request){
        $userId = Auth::user() ->id;
        $submanager = new Submanager();
        $requestRegulator = $submanager ->requestRegulator($userId);
        try{
            try{
                $request ->validate([
                    'image' => 'required|mimes:jpg,png,jpeg|file',
                    'topic_id' =>'required',
                    'subtopic_id' => 'required',
                ]);
            } catch(ValidationException $valExe){
                return $this -> responseData('unable to upload maybe check your image type',false,$valExe ->errors());
            }
            
            $user = $this -> Authuser();
            $user_id = $user -> id;
            /* when the validation is a success */
            $image_to_be_uploaded = $request->file('image');
            $newFilename = time() .'tuchopai'.'.'.$image_to_be_uploaded->getClientOriginalExtension();
            $filePath = "tuchopai/images/".$newFilename;
            $storage_disk = Storage::disk('s3');
            
            // can the user upload image
            if(!$requestRegulator ->imageQuestion){
                return $this -> responseData('UPGRADE: upgrade to answer question from images',false,null);  
            }
            
            
            try{
                $storage_disk->put($filePath,file_get_contents($image_to_be_uploaded),'public');    
            }catch(Throwable $th){
                return $this ->responseData('check your internet',false,$th->getMessage());
            }
            /* end of image processing and storage of image*/
            /* start of image processing for database storage */
            $path = $storage_disk ->url($filePath);
            $image_saved = Media::create([
                'media_name' =>$path,
                'user_id' => $user_id,
                'topic_id' => $request->topic_id,
                'subtopic_id'=> $request->subtopic_id,
                'media_url' => $path,
                'media_type' =>0 /* 0 for images and 1 for files */
            ]);
            
            if($image_saved){
                return $this ->responseData('image uploaded you can use it for prompting',true,$image_saved);
            }else{
                return $this ->responseData('image not uploaded',false,null); 
            }    
        }catch(Throwable $th){
            return $this -> responseData('error on our side try later',false,$th ->getMessage());
        }
        
    }
    
    
    
    
    
    
    
        /* upload file */
        public function uploadFile(Request $request){
        
            try{
                try{
                    $request ->validate([
                        'file' => 'required|mimes:pdf,docx,doc,txt|file',
                        'topic_id' =>'required',
                        'subtopic_id' => 'required',
                    ]);
                } catch(ValidationException $valExe){
                    return $this -> responseData('unable to upload maybe check your file type',false,$valExe ->errors());
                }
                $submanager = new Submanager();
                $user = $this -> Authuser();
                $user_id = $user -> id;
                $requestRegulator = $submanager ->requestRegulator($user_id);
                /* when the validation is a success */
                $file_to_be_uploaded = $request->file('file');
                $newFilename = time() .'tuchopai'.'.'.$file_to_be_uploaded->getClientOriginalExtension();
                $filePath = "tuchopai/files/".$newFilename;
                $storage_disk = Storage::disk('s3');
                if(!$requestRegulator ->fileQuestion){
                    return $this -> responseData('UPGRADE: upgrade to answer question from files',false,null);  
                }
                try{
                    $storage_disk->put($filePath,file_get_contents($file_to_be_uploaded),'public');    
                }catch(Throwable $th){
                    return $this ->responseData('check your internet',false,$th->getMessage());
                }
                /* end of file processing and storage of file*/
                /* start of file processing for database storage */
                $path = $storage_disk ->url($filePath);
                $file_saved = Media::create([
                    'media_name' =>$path,
                    'topic_id' => $request->topic_id,
                    'user_id' => $user_id,
                    'subtopic_id'=> $request->subtopic_id,
                    'media_url' => $path,
                    'media_type' =>1 /* 0 for files and 1 for files */
                ]);
                
                if($file_saved){
                    return $this ->responseData('file uploaded you can use it for prompting',true,$file_saved);
                }else{
                    return $this ->responseData('file not uploaded',false,null); 
                }    
            }catch(Throwable $th){
                return $this -> responseData('error on our side try later',false,$th ->getMessage());
            }
            
        }
        
        
        public function getMedia(Request $request){
            $user = $this -> Authuser();
            $user_id = $user -> id;
            $media_data = Media::where('user_id',$user_id) ->where('topic_id',$request->topic_id) ->orderBy('id','DESC')-> get();
            return $this ->responseData('file retrieved',true,$media_data);
        }
        
        
        public function deleteMedia(Request $request){
            $user = $this -> Authuser();
            $user_id = $user -> id;
            $media_id = $request ->media_id;
            $media_to_be_deleted = Media::where('id',$media_id) -> first();
            $disk = Storage::disk('s3');
            if($media_to_be_deleted){  
                if ($disk->exists($media_to_be_deleted ->media_url)) {
                    // deletes image from the folder in s3
                    $disk ->delete($media_to_be_deleted ->media_url);
                }
                $media_to_be_deleted ->delete();
                return $this -> responseData('media deleted',true,null);
            }else{
                return $this -> responseData('media not found',false,null); 
            }
        }
        
        
        /* function to rename the media name  */
        public function renameMedia(Request $request){
            $user = $this -> Authuser();
            $user_id = $user -> id;
            $media_id = $request ->media_id;
            $new_name = $request -> new_name;
            $topic_id = $request->topic_id;
            $media_to_be_renamed = Media::where('id',$media_id) -> first();
            if($media_to_be_renamed){  
                $media_to_be_renamed ->media_name = $new_name;
                $media_to_be_renamed ->save();
                return $this -> responseData('media renamed',true,null);
            }else{
                return $this -> responseData('media not found',false,null); 
            }
        }
        
        
}
