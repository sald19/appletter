<?php

namespace App\Http\Controllers\Auth;

use App\User;
use Illuminate\Support\Facades\Redirect;
use Validator;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Auth;
use Laravel\Socialite\Facades\Socialite;
class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    //protected $redirectTo = '/payment';
    protected $redirectTo = '/dashboard';

    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|confirmed|min:6',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
    }

    /*
     * Redirect the user to the facebook authentication page
     *
     * @return Response
     */
    public function redirectToProvider()
    {
        return Socialite::driver('facebook')->redirect();
    }

    public function handleProviderCallback()
    {
        try {
            $user = Socialite::driver('facebook')->user();
        } catch (\Exception $e) {
            return redirect('auth/facebook');
        }

        $authUser = $this->findOrCreateUser($user);

        Auth::login($authUser,true);

        if ( Auth::user()->active == 1) {

            return Redirect::to('/dashboard')->with('status-active', 1);
        }

        return \Redirect::to('/payment');
    }

    private function findOrCreateUser ($facebookUser)
    {
        if($authUser = User::where('facebook_id',$facebookUser->id)->first()){
            return $authUser;
        } elseif($authUser = User::where('email',$facebookUser->email)->first()) {
            $authUser->facebook_id = $facebookUser->id;
            $authUser->avatar = $facebookUser->avatar;
            $authUser->save();

            return $authUser;
        }

        return User::create([
            'name'  => $facebookUser->name,
            'email' => $facebookUser->email,
            'facebook_id' => $facebookUser->id,
            'avatar' => $facebookUser->avatar
        ]);
    }
}
