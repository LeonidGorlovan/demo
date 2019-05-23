<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Str;
use Validator;

class UserController extends Controller
{
    public function registration(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users',
            'password' => 'required|min:' . env('MIN_PASSW') . '|max:' . env('MAX_PASSW')
        ]);

        if ($validator->fails()) {
            return $this->responseFail($validator->messages());
        }

        $user = User::create([
            'email' => $request->input('email'),
            'password' => \Hash::make($request->input('password')),
            'api_token' => Str::random(60),
        ]);

        return $this->responseSuccess([
            'unique access token' => data_get($user, 'api_token')
        ]);
    }

    public function details(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);
        

        if ($validator->fails()) {
            return $this->responseFail($validator->messages());
        }
        
        $user = User::where('api_token', $request->input('token'))->first();
        
        if (empty($user)) {
            return $this->responseFail([
                'db' => 'Record Not Found'
            ]);
        }
        
        return $this->responseSuccess([
            'user email' => data_get($user, 'email')
        ]);
    }
    
    
}
