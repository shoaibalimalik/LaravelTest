<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        if($user_id = $request->get('user_id')) {
            if(!is_int($user_id)){
                $response = ['error' => 'bad input'];     
            }else{
                $response = $this->repository->getUsersJobs($user_id);
            }
        }
        elseif($request->__authenticatedUser->user_type == config('app.ADMIN_ROLE_ID') || $request->__authenticatedUser->user_type == config('app.SUPERADMIN_ROLE_ID'))
        {
            // config(app.) means config/app.php and keys defined in that file that read from .env file
            $response = $this->repository->getAll($request);
        }

        return response($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $job = $this->repository->with('translatorJobRel.user')->findOrFail($id);

        return response($job);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            "from_language_id" => "required|integer",
            "immediate" => "required",
            "due_date" => "required_if:immediate,no",
            "due_time" => "required_if:immediate,no",
            "customer_phone_type" => "required_if:immediate,no",
            "duration" => "required",
        ]);

        // some fields are only required if immediate is no

        /*
          all of the remaining fields should be added here that are being used in the store method 
          else they will be null.. I'm actually having a hardtime with my keyboard, some keys are not working
          so not typing everthing but just making slight improvements.

          for none required field we may use "nullable" instead of "required"
        */

        $response = $this->repository->store($request->__authenticatedUser, $data);

        return response($response);

    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        $cuser = $request->__authenticatedUser;
        $data = $request->except(['_token', 'submit']);
        $response = $this->repository->updateJob($id,$data, $cuser);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        $adminSenderEmail = config('app.adminemail');
        $data = $request->validate([
            "user_email_job_id" => "required|integer",
            "user_type" => "required"
            // all the none required fields with "nullable".
        ]);

        $response = $this->repository->storeJobEmail($data);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        if($user_id = $request->get('user_id')) {

            $response = $this->repository->getUsersJobsHistory($user_id, $request);
            return response($response);
        }

        return null;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $user = $request->__authenticatedUser;
        $data = $request->validate([
            "job_id" => "required|integer",
            // all the none required fields with "nullable".
        ]);
        
        $response = $this->repository->acceptJob($data, $user);

        return response($response);
    }

    public function acceptJobWithId(Request $request)
    {
        $user = $request->__authenticatedUser;
        $data = $request->validate([
            "job_id" => "required|integer",
            // all the none required fields with "nullable".
        ]);
        
        $response = $this->repository->acceptJobWithId($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $user = $request->__authenticatedUser;
        $data = $request->validate([
            "job_id" => "required|integer",
            // all the none required fields with "nullable".
        ]);

        $response = $this->repository->cancelJobAjax($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $data = $request->validate([
            "job_id" => "required|integer",
            // all the none required fields with "nullable".
        ]);

        $response = $this->repository->endJob($data);

        return response($response);

    }

    public function customerNotCall(Request $request)
    {
        $data = $request->validate([
            "job_id" => "required|integer",
            // all the none required fields with "nullable".
        ]);

        $response = $this->repository->customerNotCall($data);

        return response($response);

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->getPotentialJobs($user);

        return response($response);
    }

    public function distanceFeed(Request $request)
    {
        $data = $request->all();

        $distance = $request->input('distance',"");
        $time = $request->input('time',"");
        $session = $request->input('session_time',"");

        if (isset($data['jobid']) && $data['jobid'] != "") {
            $jobid = $data['jobid'];
        }

        if ($data['flagged'] == 'true') {
            if($data['admincomment'] == '') return "Please, add comment"; 
            // should be added in request validate using required_if rule 
            $flagged = 'yes';
        } else {
            $flagged = 'no';
        }

        $manually_handled = ($data['manually_handled'] == 'true') ? 'yes' : 'no';
        $by_admin = ($data['by_admin'] == 'true') ? 'yes' : 'no';
        $admincomment = (isset($data['admincomment']) && $data['admincomment'] != "") ? $data['admincomment'] : '';
        
        if ($time || $distance) {
            // should be moved to repository with required parameters
            $affectedRows = Distance::where('job_id', '=', $jobid)->update(array('distance' => $distance, 'time' => $time));
        }

        if ($admincomment || $session || $flagged || $manually_handled || $by_admin) {

            $affectedRows1 = Job::where('id', '=', $jobid)->update(array('admin_comments' => $admincomment, 'flagged' => $flagged, 'session_time' => $session, 'manually_handled' => $manually_handled, 'by_admin' => $by_admin));

        }

        return response('Record updated!');
    }

    public function reopen(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->reopen($data);

        return response($response);
    }

    public function resendNotifications(Request $request)
    {
        $data = $request->validate([
            "jobid" => "required|integer"
        ]);
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);
        $this->repository->sendNotificationTranslator($job, $job_data, '*');

        return response(['success' => 'Push sent']);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $data = $request->validate([
            "jobid" => "required|integer"
        ]);
        
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }

}
