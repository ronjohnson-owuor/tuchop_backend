<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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
            /* generate token for user to store in the backend*/
            $token = $user -> createToken('user') ->plainTextToken;
            return $this->responseMessage('sign up success',true,$token,null);        
        } catch (ValidationException $valExe) {
            return $this ->responseMessage('input fields error',false,null,$valExe->getMessage()); 
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
            return $this->responseMessage('input field errors',false,null,$valExe->getMessage());
        }
    }
    
    
    public function getUser(){
        $user = Auth::user();
        $data = (object)[
            'id' => $user ->id,
            'email' =>$user ->email,
            'name' =>$user ->name,
            'picture' =>$user ->picture
        ];
        
        return  $this -> responseData(null,true,$data);
    }
    
    
    
    
    
}
