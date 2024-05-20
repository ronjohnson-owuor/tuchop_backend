<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Comment;
use App\Models\Like;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            $likes = Like::where("blogid",$blog->id)->count();
            $comments = Comment::where("blogid",$blog->id)->count();
            $data = [
                "date" => $createDate,
                "id" => $blog->id,
                "heading" => $blog->heading,
                "thumbnail" => $blog->thumbnail,
                "likes" => $likes,
                "comment" => $comments
            ];
            $newBlog[] = $data;
        }
        return $this ->responseData("blog retrieved",true,$newBlog);
    }
    // edit blog
    public function likeBlog(Request $request){
        try{
            $userId = Auth::user() -> id;
            $blogId = $request -> blogId;
            $userHasLiked = Like::where("userid",$userId)->where("blogid",$blogId)->first();
            if($userHasLiked){
                return $this -> responseData("you already liked the post",false,null);
            }else{
                $createLike = Like::create([
                    "userid" => $userId,
                    "blogid"=> $blogId
                ]);
                $createLike ->save();
                return $this -> responseData("post liked",true,null);
            }
        }catch(Throwable $e){
            return $this -> responseData($e->getMessage(),false,null); 
        }


    }



    // comment on the blog
    public function commentPost(Request $request){
       try{
        $userId = Auth::user() -> id;
        $comment = $request -> comment;
        $blogId = $request -> blogId;
        $saveComment = Comment::create([
            "userid"=> $userId,
            "blogid"=> $blogId,
            "comment"=> $comment
        ]);
        $saveComment ->save();
        return $this -> responseData("comment added",true,null);

       }catch(Throwable $th){
        return $this -> responseData($th->getMessage(),false,null);
       } 
    }







    // readblog data
    public function readBlog (Request $request){
        $heading = $request -> heading;
        $blog = Blog::where("heading",$heading)->first();
        // add the likes and the comments before returning the array
        $likes = Like::where("blogid",$blog->id)->count();
        $comments = Comment::where("blogid",$blog->id)->get();
        $refinedComments = [];
        foreach($comments as $comment){
            $userData = User::where("id", $comment -> userid) ->first();
            $date = Carbon::parse($comment -> created_at) -> format('d/m/Y');
            $data = [
                "picture" => $userData->picture,
                "name" => $userData ->name,
                "date" => $date,
                "comment" => $comment -> comment
                ];
                $refinedComments[] = $data;
        }




        if($blog){
            $newBlog = [
                "content" => $blog->content,
                "thumbnail" => $blog->thumbnail,
                "likes" => $likes,
                "id" => $blog->id,
                "comment" => $refinedComments
            ];
            return $this -> responseData("blog retrieved",true,$newBlog);
        }else{
            return $this -> responseData("unable to retrieve blog at the moment",false,null);  
        }
    }
}
