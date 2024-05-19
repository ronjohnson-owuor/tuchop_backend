<?php
namespace App\Subscriptionmanager;
use App\Http\Controllers\Controller;
use App\Models\User;

class Express extends Controller{
	public function filterRequest($userId){
		$amountRemaining = User::where("id",$userId) ->first()->planType;
		// if the amount cannot support request
		if($amountRemaining < 0.15){
			return false;
		}
		$user = User::where("id",$userId) ->first();
		$user ->planType = ($user ->planType) - 0.15;
		$user ->save();
		return  true;
	}
}