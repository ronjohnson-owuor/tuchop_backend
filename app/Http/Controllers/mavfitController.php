<?php

namespace App\Http\Controllers;

use App\Models\Mavfit;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class mavfitController extends Controller
{
    public function submitData(Request $request){
        try{
           $request -> validate([
            "firstname" => "required",
            "lastname" => "required",
            "email" => "required | unique:mavfits,email",
            "phone" => "required",
            "info" => "required",
             "traffic" => "required",
             "question" => "required"
           ]);
        } catch(ValidationException $valExe){
            return response() -> json([
                "message" => "check your input fields and try again",
                "success" => false,
                "data" => $valExe -> getMessage()
            ]);
        }
        
        $firstName = $request -> firstname;
        $lastName = $request -> lastname;
        $email = $request -> email;
        $phone = $request -> phone;
        $info = $request -> info;
        $traffic = $request -> traffic;
        $question = $request -> question;
        $promo = $request -> promo;
        
        
        $data = [
            "firstname" => $firstName ,
            "lastname" => $lastName ,
            "email" => $email,
            "phone" => $phone ,
            "info" => $info ,
             "traffic" => $traffic,
             "question" => $question
        ];
        
        if($promo != null){
            $data['promo'] = $promo;
        }
        
        Mavfit::create($data);
        return  response() -> json([
            "message" => "form submitted successfully",
            "success" => true,  
        ]);   
    }
}
