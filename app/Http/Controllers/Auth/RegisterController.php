<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SumsubService;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{

    protected $sumsubService;
    private $baseUrl;
    private $appToken;
    private $secretKey;
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
    public function __construct(SumsubService $sumsubService)
    {
        $this->middleware('guest');
        $this->sumsubService = $sumsubService;
        $this->baseUrl = config('services.sumsub.base_url', env('SUMSUB_BASE_URL'));
        $this->appToken = env('SUMSUB_APP_TOKEN');
        $this->secretKey = env('SUMSUB_SECRET_KEY');
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    
        if (!$user instanceof \App\Models\User) {
            dd('User creation failed', $user);
        }
    
        try {
            $user->assignRole('user');
        } catch (\Exception $e) {
            return back()->withErrors("Role assignment failed: " . $e->getMessage());
        }
    
        $user->update([
            'external_id' => 'user_' . time(),
        ]);
    
        $sumsubResponse = $this->sumsubService->createApplicant($user->external_id);
        
        if (!isset($sumsubResponse['id'])) {
            return back()->withErrors("Error: Could not create applicant.");
        }
    
        $user->update([
            'applicant_id' => $sumsubResponse['id'],
        ]);
        
        return $user;
    }
}
