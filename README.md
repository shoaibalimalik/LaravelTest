Here I will highlight some code from BookingController that needs improvements.

app/Http/Controllers/BookingController.php

    public function index(Request $request)
    {
        if($user_id = $request->get('user_id')) { .... }
        
        -- there is no validation here where $request->get('user_id') contains an ID,String,Boolean or         anything.

        elseif($request->__authenticatedUser->user_type == env('ADMIN_ROLE_ID') || $request->__authenticatedUser->user_type == env('SUPERADMIN_ROLE_ID'))
        {
            $response = $this->repository->getAll($request);
        }

        -- very wrong approach. environment variables should always be used from the config files and/
        not using the env files directly in controllers/services. They sometimes don't return the values
        due to caching or anything on production if php artisan config:cache is run.

    }    

    public function show($id){
        $job = $this->repository->with('translatorJobRel.user')->find($id);
        
        -- id parameter could be validated and findorFail method should be used instead of find to
           return 404 page incase the requested resource doesn't exist.
    }   


    public function store(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->store($request->__authenticatedUser, $data);

        -- no validation in place here and $request->all(); should be replaced with $request->except
        or request only.

        -- store method in the repo is written in a very non laravel and poor way. It should be consized
        and validation should be handled by laravel. 

        return response($response);

    } 


    public function update($id, Request $request)
    {
        $data = $request->all();
        $cuser = $request->__authenticatedUser;
        $response = $this->repository->updateJob($id, array_except($data, ['_token', 'submit']), $cuser);

        -- again request all should be avoided, I will also do a little refactor here

        $data = $request->except(['_token', 'submit']);
        $response = $this->repository->updateJob($id, $data, $cuser);

        return response($response);
    }

    public function immediateJobEmail(Request $request)
    {
        $adminSenderEmail = config('app.adminemail');
        $data = $request->all();  -- bad approach and validation missing

        $response = $this->repository->storeJobEmail($data);

        return response($response);
    }

    public function getHistory(Request $request)
    {
        -- validation missing
        if($user_id = $request->get('user_id')) {

            $response = $this->repository->getUsersJobsHistory($user_id, $request);
            return response($response);
        }

        return null;
    }

    public function acceptJob(Request $request)
    {
        $data = $request->all();    --bad approach and no validation
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJob($data, $user);

        return response($response);
    }

    public function acceptJobWithId(Request $request)
    {
        $data = $request->get('job_id');
        $user = $request->__authenticatedUser;

        -- validation missing

        $response = $this->repository->acceptJobWithId($data, $user);

        return response($response);
    }


    public function cancelJob(Request $request)
    {
        $data = $request->all();   -- bad approach and validation missing
        $user = $request->__authenticatedUser;
        $response = $this->repository->cancelJobAjax($data, $user);
        return response($response);
    }

    public function endJob(Request $request)
    {
        $data = $request->all();   -- bad approach and validation missing

        $response = $this->repository->endJob($data);

        return response($response);

    }


    public function customerNotCall(Request $request)
    {
        $data = $request->all();   -- bad approach validation missing

        $response = $this->repository->customerNotCall($data);

        return response($response);

    }

    public function distanceFeed(Request $request)
    {

        -- code may be optimized using ternary operators
        -- queries should be moved to repository
    }



    -- one major issue is there is no policy or roles implementation, any user can perform the action or some specific user? UserPolicy could be used to perform authorization.


    app/Repository/BookingRepository.php


    -- extra code that should be handled by laravel validation.
    -- find to be replaced with findorfail
    -- too much native php code