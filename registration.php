public function signup(){
        try{
            $validator = Validator::make($this->request->all(), [ 
                'email' => 'required|string|email|unique:users', 
                'password' => 'required|min:8',
                'company_website' => 'required',
                'company_name' => 'required',
                'company_industry' => 'required'
            ]);

            if ($validator->fails()) {
                foreach ($validator->messages()->getMessages() as $field_name => $messages){
                    throw new Exception($messages[0], 1);
                }
            }

            //$username = $this->request['firstname'].$this->request['lastname'];
            
            $user = User::create([
                'email' => $this->request['email'],
                'password' => Hash::make($this->request['password']),
                'role_id' => $this->roleId,
                'status' => $this->userStatus
            ]);  

            $userId = $user->id;

            $company = UserEmployer::create([
                'user_id' => $userId,
                'company_name' => $this->request['company_name'], 
                'company_website' => $this->request['company_website'], 
                'privacy_policy' => $this->request['privacy_policy'], 
                'newsletter' => $this->request['newsletter'] 
            ]);

            $companyId = $company->id;
            $companyIndustrues = $this->request['company_industry'];  
             
            foreach($companyIndustrues as $key => $value){
                EmployerCompanyIndustry::create([
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'industry_id' => $value
                ]);
            }      
            return response()->json([
                    'status'=>'success', 
                    'message' => 'Signup Successful'
            ],200);
                  

        }catch (\Exception $ex){
            return response()->json([
                'status' => 'error',
                'code' => $ex->getCode(),
                'message' => $ex->getMessage(),
                'error_details' => 'on line : '.$ex->getLine().' on file : '.$ex->getFile()
            ], 200);
        }
    }