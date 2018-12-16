<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use DB;
use Mail;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|max:255',
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->all();
        $validator = $this->validator($data);

        if ($validator->passes()) {
            $user = $this->create($data)->toArray();
            $user['link'] = str_random(30);

            DB::table('user_activations')->insert(['id_user' => $user['id'], 'token' => $user['link']]);

            Mail::send('emails.activation', $user, function ($message) use ($user) {
                $message->to($user['email']);
                $message->subject(' Your Activation code');
            });
            return redirect()->to('login')->with('success', "Welcome! We have sent your activation code. Please click on the link");
        }
        return back()->with('errors', $validator->errors());
    }

    public function userActivation($token){
        $check = DB::table('user_activations')->where('token',$token)->first();
        //dd ($check);
        if(!is_null($check)){
            //  dd ($user);
            $user = User::find($check->id_user);
            // dd ($user);
            if ($user->is_activated ==1){
                // dd ($user);
                return redirect()->to('login')->with('success',"Account Activated");

            }
            $user->update(['is_activated' => 1]);
            DB::table('user_activations')->where('token',$token)->delete();
            return redirect()->to('login')->with('success',"Successful Registration");
        }
        return redirect()->to('login')->with('Warning',"Invalid Token");
    }
}