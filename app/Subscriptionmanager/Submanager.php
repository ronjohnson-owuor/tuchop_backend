<?php
namespace App\Subscriptionmanager;
use App\Http\Controllers\Controller;
use App\Models\Token;
use App\Models\Topic;
use App\Models\User;
use Carbon\Carbon;

class Submanager extends Controller{
	
	
	// if the user has a free tier then he can only create 10 topics ,if he is in the
	// starter stage he can create only 50 topics  from there he can create as many topics as he want 
	// this function is response for checking the user subscription an then regulating his topic creation effectively
	public function canCreateTopic($userid){
		// user id that we want to allow to create topic
		$userId = $userid;
		$currentTopicNumber = Topic::where("topic_creator",$userId) ->count();
		$planType = User::where("id",$userId) ->first()->planType; // user is in free tier and
		
		
		if ($planType == 0) {
			if($currentTopicNumber >= 10){
				return (object)[
					"cancreate" => false,
					"message" => "UPGRADE: upgrade to continue...have reached maximum topic for your free plan"
			];	
			}
		} else if($planType == 1){
			if($currentTopicNumber >= 50){
				return (object)[
					"cancreate" => false,
					"message" => "UPGRADE: upgrade to continue...have reached maximum topic for your plan"
				];	
			}	
		}

		return (object)[
			"cancreate" => true,
			"message" => "maximum not reached" 
		];
	}
	
	
	
	
	// this function is responsible for checking if the user has eceeded his/her  monthly limit  
	public function requestRegulator($id){
		$userId = $id;
		// for unlimited and yearly users their token are unlimited.while starters their tokens are limited 
		/* 
		free users limit per month = 500 tokens per month;
		 */
		$planType = User::where("id",$userId) ->first()->planType;
		$userToken = Token::where("user_id",$userId)->first();
		$expiry = $userToken ->expiry;
		$hasExpired = Carbon::parse($expiry) ->isPast();
		$imageQuestion = false;
		$fileQuestion = false;
		if($hasExpired){
			return (object)[
				"valid" => false,
				"message" => "👋 UPGRADE: your subscription has expired,please renew to get more token."
			];	
		}else{
		// check if the user token is over or he can still make request
		$tokenNumber = $userToken -> tokens;
		if ($tokenNumber == 0 && $planType < 2){
			return (object)[
				"valid" => false,
				"message" => "UPGRADE: sorry your token is depleted please upgrade to get more tokens."
			];
		}
		
		if($planType < 2){
			$userToken -> tokens = $userToken->tokens-1;
			$userToken->save();
		}
		
		if($planType == 1){
			$fileQuestion = true;
		}else if($planType >= 2){
			$imageQuestion = true;
			$fileQuestion = true;
		}
		
		return (object)[
			"valid" => true,
			"imageQuestion" => $imageQuestion,
			"fileQuestion" => $fileQuestion,
			"message" => "token not depleted"
		];	
	}
	}
	}