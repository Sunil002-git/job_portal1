<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\JobType;
use App\Models\Job;
use Illuminate\Http\Request;

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
            $jobs = $jobs->where('category', $request->category);
        }
        $jobs = $jobs->with('jobType')->orderBy('created_at', 'DESC')->paginate(9);

        return view('front.jobs', [
            'categories' => $categories,
            'jobTypes' => $jobTypes,
            'jobs' => $jobs,
        ]);
    }
}
