<?php

namespace App\Http\Controllers;

use App\Models\Refferall;
use App\Models\Token;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class userController extends Controller
{
    
            /* response message data structure */
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
    
    
    
    public function normalSignin(Request $request){
        try {
            $request->validate([
                'name' =>'required',
                'password'=>'required',
                'email' =>'required|email|unique:users',
                'type' =>'required|integer'
            ]);
            
            $refferallId = $request -> refferall;
            $name = $request['name'];
            $email =$request["email"];
            $type = $request["type"];
            
            /* zero for notmal sign in and  1 for socialite sign in */
            if($type == 0){
            $password = bcrypt($request['password']);
            $user = User::create([
                "name"=>$name,
                "password" =>$password,
                "email" =>$email,
                "email_verified" =>false
            ]);              
            }else{
                $picture = $request ->picture;
                $user = User::create([
                    "name"=>$name,
                    "picture" =>$picture,
                    "email" =>$email,
                    "email_verified" =>true
                ]);  
            }
            // give the user free tier token of 500
            Token::create([
                "user_id"  => $user ->id,
                "tokens" => 500, //give 500 free tokens at first signup
                "expiry" => now() ->addMonth()
            ]);
            /* generate token for user to store in the backend*/
            $token = $user -> createToken('user') ->plainTextToken;
            if($refferallId != null){
            $refferSection = Refferall::where('refferer',$refferallId) -> first();
            if($refferSection == null){
                Refferall::create([
                    'refferer' => $refferallId,
                    'reffered' => 1,
                    'award' => 20
                ]);
            }  
            $refferSection -> reffered +=1;
            $refferSection -> award +=20;
            $refferSection->save();
           $refferSection -> reffered +=1;
           $user_tokens = Token::where('user_id',$refferallId) -> first();
           if($user_tokens != null){
            $user_tokens->tokens += 20;
            $user_tokens ->save();
           }
        }         
            
            return $this->responseMessage('sign up success',true,$token,null);        
        } catch (ValidationException $valExe) {
            return $this ->responseMessage($valExe ->getMessage(),false,null,$valExe->getMessage()); 
        }
    }
    
    
    
    public function normalLogin(Request $request){
        try {
            $request->validate([
                'password'=>'required',
                'email' =>'required|email',
                'type' =>'required'
            ]);
            
            $type = $request ->type;
            $email =$request["email"];
            $password = $request["password"];
            $get_user = User::where('email',$email) ->first();
            if(!$get_user){
                return $this->responseMessage('user not found',false,null,'you dont have an account');
            }
            /* generate token for user to store in the backend*/
            $token = $get_user -> createToken('user') ->plainTextToken;
            if($type == 1){
                return $this ->responseMessage("login successfull",true,$token,null);
            }
            $password_correct = Hash::check($password,$get_user->password);
            if(!$password_correct){
                return $this->responseMessage('password mismatch',false,null,'password mismatch');
            }
            return $this->responseMessage('login success',true,$token,null);           
        } catch (ValidationException $valExe) {
            return $this->responseMessage($valExe->getMessage(),false,null,$valExe->getMessage());
        }
    }
    
    
    public function getUser(){
        $user = Auth::user();
        $userTokenData = Token::where("user_id",$user -> id)->first();
        $token = $userTokenData ->tokens;
        $data = (object)[
            'id' => $user ->id,
            'email' =>$user ->email,
            'name' =>$user ->name,
            'picture' =>$user ->picture,
            'token_remaining' => $token,
            'plan_type' => $user -> planType
        ];
        
        return  $this -> responseData(null,true,$data);
    }
    
    
    
    // edit user information
    public function EditUser(Request $request){
        try{
            $user = Auth::user();
            $image = $request ->file('image');
            $name = $request -> name;
            
            if($image != null){
                $newFilename = time() .'tuchopai-profiles'.'.'.$image->getClientOriginalExtension();
                $filePath = "tuchopai/profiles/".$newFilename;
                $storage_disk = Storage::disk('s3');
                try{
                    $storage_disk->put($filePath,file_get_contents($image),'public');    
                }catch(Throwable $th){
                    return $this ->responseData('check your internet',false,$th->getMessage());
                }
                $path = $storage_disk ->url($filePath);
                $user ->picture = $path;
            }
            
            if($name != null){
                $user -> name = $name;
            }
            $user -> save();
            return $this->responseData('User updated successfully', true, null);            
        }catch(Throwable $th){
            return $this ->responseData('error in updating the user data',false,$th->getMessage());
        }

    }
    
    
    
    
    
}
