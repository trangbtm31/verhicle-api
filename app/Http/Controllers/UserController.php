<?php

namespace App\Http\Controllers;

use App\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    /*public function __construct()
    {
        $this->middleware('Authorize:' . __CLASS__, ['except' => ['index', 'show']]);
        $this->middleware('oauth', ['except' => ['index', 'show']]);
    }*/

    public function index()
    {

        $users = User::all();
        return $this->success($users, 200);
    }

    public function register(Request $request)
    {
        $this->validateRequest($request);
        User::create([
            'phone' => $request->get('phone'),
            'name' => $request->get('name'),
            'gender' => $request->get('gender'),
            'password' => Hash::make($request->get('password'))
        ]);

/*        return $this->success("The user with with id {$user->id} has been created", 201);*/
        return $this->success([
            "status" => 1,
            "message"=> "Success"
            ], 201);
    }

    public function show($id)
    {

        $user = User::find($id);

        if (!$user) {
            return $this->error("The user with {$id} doesn't exist", 404);
        }

        return $this->success($user, 200);
    }

    public function update(Request $request, $id)
    {

        $user = User::find($id);

        if (!$user) {
            return $this->error("The user with {$id} doesn't exist", 404);
        }

        $this->validateRequest($request);

        $user->email = $request->get('email');
        $user->password = Hash::make($request->get('password'));

        $user->save();

        return $this->success("The user with with id {$user->id} has been updated", 200);
    }

    public function destroy($id)
    {

        $user = User::find($id);

        if (!$user) {
            return $this->error("The user with {$id} doesn't exist", 404);
        }

        $user->delete();

        return $this->success("The user with with id {$id} has been deleted", 200);
    }

    public function validateRequest(Request $request)
    {

        $rules = [
            'phone' => 'required|digits_between:9,11',
            'password' => 'required|min:6',
            'name' => 'required',
            'gender' => 'required|digits_between:0,1'
        ];

        $this->validate($request, $rules);
    }

    public function isAuthorized(Request $request)
    {

        $resource = "users";
        // $user     = User::find($this->getArgs($request)["user_id"]);

        return $this->authorizeUser($request, $resource);
    }
}