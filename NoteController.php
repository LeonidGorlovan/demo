<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Note;
use App\User;
use Str;
use Validator;

class NoteController extends Controller
{
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'title' => 'required|max:255',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->responseFail($validator->messages());
        }

        $user = User::where('api_token', $request->input('token'))->first();

        if (empty($user)) {
            return $this->responseFail([
                'db' => 'User Not Found'
            ]);
        }

        $note = Note::create([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'api_token' => $request->input('token'),
        ]);

        return $this->responseSuccess([
            'note id' => data_get($note, 'id')
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'note_id' => 'required|numeric',
            'title' => 'required|max:255',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->responseFail($validator->messages());
        }

        $user = User::where('api_token', $request->input('token'))->first();

        if (empty($user)) {
            return $this->responseFail([
                'db' => 'User Not Found'
            ]);
        }

        $note = Note::where('id', $request->input('note_id'))->where('api_token', $request->input('token'))->update([
            'title' => $request->input('title'),
            'description' => $request->input('description')
        ]);

        if (empty($note)) {
            return $this->responseFail([
                'db' => 'Record Not Found'
            ]);
        }

        return $this->responseSuccess([
            'note id' => $request->input('note_id')
        ]);
    }

    public function list(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->responseFail($validator->messages());
        }

        $notes = Note::where('api_token', $request->input('token'))->get();

        if(empty($notes) || count($notes) == 0) 
        {
            return $this->responseFail([
                'db' => 'Records Not Found'
            ]);
        }

        return $this->responseSuccess([
            'notes' => $notes
        ]);
    }
}
