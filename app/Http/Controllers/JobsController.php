<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\JobApplication;
use App\Models\JobType;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobsController extends Controller
{
    public function index(Request $request) {

        $categories = Category::where('status', 1)->get();
        $jobTypes = JobType::where('status',1)->get();

        $jobs = Job::where('status', 1);
// Search Using Key Word
        if(!empty($request->keyword)) {
            $jobs = $jobs->where(function($query) use ($request){
                $query->orWhere('title','like','%'.$request->keyword.'%');
                $query->orWhere('keywords','like','%'.$request->keyword.'%');
            });
        }

        //Search Using location
        if(!empty($request->location)) {
            $jobs = $jobs->where('location', $request->location);
        }

        //Search Using category
        if(!empty($request->category)) {
            $jobs = $jobs->where('category_id', $request->category);
        }
$jobTypeArray = [];
        //Search Using jobType
        if(!empty($request->jobType)) {
            //1,2,3
            $jobTypeArray = explode(',', $request->jobType);
            $jobs = $jobs->whereIn('job_type_id', $jobTypeArray);
        }

        //Search Using experience
        if(!empty($request->experience)) {
            $jobs = $jobs->where('experience', $request->experience);
        }


        $jobs = $jobs->with(['jobType','category']);
        if($request->sort == '0') {
            $jobs = $jobs->orderBy('created_at', 'ASC');
        } else {
            $jobs = $jobs->orderBy('created_at', 'DESC');
        }
        
        
        $jobs= $jobs->paginate(9);

        return view('front.jobs', [
            'categories' => $categories,
            'jobTypes' => $jobTypes,
            'jobs' => $jobs,
            'jobTypeArray' => $jobTypeArray
        ]);
    }

    //this method will show job in details
    public function detail($id) {

        $job = Job::where([
            'id' => $id,
            'status' => 1
        ])->with(['jobType','category'])->first();

        if($job == null) {
            abort(404);
        }

        return view('front.jobDetail', ['job' => $job]);
    }

    public function applyJob(Request $request) {
        $id = $request->id;

        $job = Job::where('id', $id)->first();

        //if job not found in db
        if ($job == null) {
            //$message = 'Job does not exist';
            session()->flash('error', 'Job does not exist');
            return response()->json([
                'status' => false,
                'message' => 'Job does not exist'
            ]);
        }

        // you can not apply on your Job
        $employer_id = $job->user_id;

         if ($employer_id == Auth::user()->id) {
            //$message = "You can not apply on your own Job";
            session()->flash('error', 'You can not apply on your own Job');
            return response()->json([
                'status' => false,
                'message' => 'You can not apply on your own Job'
            ]);
        }

        //you can not apply job twise
        $jobApplicationCount = JobApplication::where([
            'user_id' => Auth::user()->id,
            'job_id' => $id
        ])->count();

        if($jobApplicationCount > 0 ) {
            // $message = "You already applied on this Job";
            session()->flash('error', 'You already applied on this Job');
            return response()->json([
                'status' => false,
                'message' => 'You already applied on this Job'
            ]);
        }

        $application = new JobApplication();
        $application->job_id = $id;
        $application->user_id = Auth::user()->id;
        $application->employer_id = $employer_id;
        $application->applied_date = now();
        $application->save();

        //$message = "You have successfully applied";
        session()->flash('success', "You have successfully applied");
            return response()->json([
                'status' => true,
                'message' => "You have successfully applied"
            ]);
    }

    
}