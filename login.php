public function login(){
        try{
            $validator = Validator::make($this->request->all(), [ 
                'password' => 'required',
                'email' => 'required',
            ]);

            if ($validator->fails()) {
                foreach ($validator->messages()->getMessages() as $field_name => $messages){
                    throw new Exception($messages[0], 1);
                }
            } 

            if(Auth::attempt(['email'=>$this->request->email, 'password'=> $this->request->password])){

                $userDetails = $this->userRepository->getData(['email'=> $this->request->email],'first',[],1);

                if($userDetails['role_id']==$this->roleId){
                    $user = $this->request->user();
                    $token =  $user->createToken($this->secret)->accessToken;

                    return response()->json([
                        'status'=>'success',
                        'auth'=>$token,
                        'authToken' => $token,
                        'data' => $user,
                        'message' => 'Login Successful'
                    ],200);
                }else{
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid Username or Password'
                    ], 200);
                }
            }else{
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid Username or Password'
                ], 200);
            }

        }catch (\Exception $ex){
            return response()->json([
                'status' => 'error',
                'code' => $ex->getCode(),
                'message' => $ex->getMessage(),
                'error_details' => 'on line : '.$ex->getLine().' on file : '.$ex->getFile()
            ], 200);
        }
    }