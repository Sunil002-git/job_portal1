<?php

namespace App\Http\Controllers;

//use Dotenv\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Category;
use App\Models\JobType;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\File;


class AccountController extends Controller
{
    public function registration() {
        return view("front.account.registration");
    }

    public  function processRegistration(Request $request) {
       $validator = Validator::make($request->all(),[
        'name' => 'required',
        'email'=> 'required|email|unique:users,email',
        'password' => 'required|min:5|same:confirm_password',
        'confirm_password' => 'required',
       ]);
if( $validator->passes()) {
$user = new User ();
$user->name = $request->name;
$user->email = $request->email;
$user->password = Hash::make($request->password);

$user->save();

session()->flash('success','You have registered successfully');

    return response()->json([
        'status' => true,
        'errors' => []
    ]);
} else {
    return response()->json([
        'status' => false,
        'errors' => $validator->errors()
    ]);
}

    }
    public function login() {
        return view('front.account.login');
    }

    public function authenticate(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if($validator->passes()) {

            if(Auth::attempt(['email' => $request->email, 'password'=> $request->password])) {
                return redirect()->route('account.profile');
            } else {
                return redirect()->route('account.login')->with('error', 'Either Email or Password is Incorrect');            
            }

        } else {
           return redirect()->route('account.login')
           ->withErrors($validator)
           ->withInput($request->only('email'));

        }
    }

    public function profile() {
        $id = Auth::user()->id;
        $user = User::where('id',$id)->first();
       

        return view('front.account.profile', [
            'user' => $user
        ]);
    }

    public function updateProfile(Request $request) {
        $id = Auth::user()->id;

        $validator = Validator::make($request->all(), [
            'name' => 'required|min:5|max:20',
            'email' => 'required|email|unique:users,email,'.$id.',id'

        ]);

        if($validator->passes()){

            $user = User::find($id);
            $user->name = $request->name;
            $user->email = $request->email;
            $user->mobile = $request->mobile;
            $user->designation = $request->designation;
            $user->save();

            session()->flash('success', 'Profile Updated Successfully.');

            return response()->json([
                'status' => true,
                'errors' => []
            ]);

        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }
    public function logout() {
        Auth::logout();
        return redirect()->route('account.login');
    }


    public function updateProfilePic(Request $request) {
        // dd($request->all());
        $id = Auth::user()->id;
        
        
        $validator = Validator::make($request->all(),[
            'image' => 'required|image'
        ]);

        if($validator->passes()) {
            $image = $request->image;
            $ext = $image->getClientOriginalExtension();
            $imageName = $id.'-'.time().'.'.$ext;
            $image->move(public_path('/profile_pic/'), $imageName);

            $sourcePath = public_path('/profile_pic/'.$imageName);
            $manager = new ImageManager(Driver::class);
            $image = $manager->read($sourcePath);
            
            // crop the best fitting 5:3 (600x360) ratio and resize to 600x360 pixel
            $image->cover(150, 150);
            $image->toPng()->save(public_path('/profile_pic/thumb/'.$imageName));

            //delete old profile pic

            File::delete(public_path('/profile_pic/thumb/'.Auth::user()->image));
            File::delete(public_path('/profile_pic/'.Auth::user()->image));
            User::where('id', $id)->update(['image' => $imageName]);

            session()->flash('success', 'Profile Picture Updated Successfully');
        
            return response()->json([
                'status' => true,
                'errors' => []
            ]);

        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }
    
    public function createJob() {
       $categories = Category::orderBy('name','ASC')->where('status',1)->get();
       $jobTypes = JobType::orderBy('name','ASC')->where('status',1)->get(); 
       return view('front.account.job.create', [
            'categories' => $categories,
            'jobTypes' => $jobTypes,
        ]);
    }

    public function saveJob(Request $request) {

        $rules = [
            'title' => 'required|min:5|max:200',
            'category' => 'required',
            'jobType' => 'required',
            'vacancy' => 'required|integer',
            'location' => 'required|max:50',
            'description' => 'required',
            'company_name' => 'required|min:3|max:75',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes()) {

        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }
}
