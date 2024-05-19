<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class Blogcontroller extends Controller
{
    public function responseData($message,$success,$data){

        return response() -> json([
            'message' =>$message,
            'success'=> $success,
            'data'=> $data
        ]);  
}
    public function createBlog(Request $request){
        try{
            $request -> validate([
                'heading' => 'required',
                'thumbnail'=> 'required',
                'content'=> 'required',
            ]);               
        }catch(ValidationException $e){
            return $this -> responseData($e->getMessage(),false,null);
        }


        $heading = $request -> heading;
        $thumbnail = $request -> thumbnail;
        $content = $request -> content;
        $blog = new Blog();
        $blog -> heading = $heading;
        $blog -> thumbnail = $thumbnail;
        $blog -> content = $content;
        $blog -> save();
        return $this -> responseData('blog saved',true,null); 
    }

    // retrieve blog
    public function getBlog(Request $request){
        $amount = $request->amount ?? 10; 
        $blogs = Blog::take($amount)->orderBy('id', 'DESC')->get();
        $carbon = new Carbon();
        $newBlog = [];
        foreach ($blogs as $blog) {
            $createDate = $carbon->parse($blog->created_at)->format('d:m:y');
            $data = [
                "date" => $createDate,
                "heading" => $blog->heading,
                "thumbnail" => $blog->thumbnail,
                "content" => $blog->content,
                "likes" => 0,
                "comment" => []
            ];
            $newBlog[] = $data;
        }
        return $this ->responseData("blog retrieved",true,$newBlog);
    }
    // edit blog
}
