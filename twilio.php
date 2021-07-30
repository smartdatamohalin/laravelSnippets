/**
    * API Function to send message on registration.
    * @param 
    * @return status, message, data
    * Created By: Pankaj Joshi
    */
    public function sendMessageOnRegistration()
    {
        $validator_phone = Validator::make($this->request->all(),[
            'mobile_number' => 'required|unique:users,mobile_number,'.$this->request->id,
            'via'           => 'required'
        ]);
        $user_data = $this->userRepository->getData(['id'=>$this->request->id],'first',[],0);
        if ($validator_phone->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'The mobile number has already been taken.',
                'data' => $user_data
            ], 200);
        }
        $user = $this->userRepository->getData(['id'=>$this->request->id],'first',[],0);
        $code = $this->request->country_code;
        $phone_number = preg_replace('/[^0-9]/', '', $this->request->mobile_number);
        $phone_number = $code.''.$phone_number;

        $new_user = $this->userRepository->createUpdateData(['id'=> $this->request->id],$this->request->all());
    	$sid    = $this->twilioSid;
        $token  = $this->twilioToken;
        $client = new Client( $sid, $token );
        if($this->request->via=='msg'){
            $token = rand(1001, 9999);
            $newTime = date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")." +10 minutes"));
            $mobile_verified = MobileVerification::updateOrCreate(['user_id' => $this->request->id],['mobile' => $phone_number, 'token' => $token, 'valid_till' => $newTime]);
            $msg= $client->messages->create(
                $phone_number,
                [
                    'from' => $this->twilioServiceNumber,
                    'body' => 'OTP is '.$token. ' for Project. Valid till '.$newTime.'. Do not share OTP for security reasons.',
                ]
            );
            //$phone_number
            return response()->json([
                'status' => 'success',
                'message' => 'Mobile number updated.',
                'data'  => $new_user,
            ], 200);
        }else{
            $token = rand(1001, 9999);
            $newTime = date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")." +10 minutes"));
            $mobile_verified = MobileVerification::updateOrCreate(['user_id' => $this->request->id],['mobile' => $phone_number, 'token' => $token, 'valid_till' => $newTime]);
            $response = new VoiceResponse;
            $response->say("Thank You for the verification.");
            $response->pause(['length' => 1]);
            $response->say("Your one time password is".$token);
            $response->pause(['length' => 1]);
            $response->say("Good Bye");
            $msg= $client->calls->create(
                $phone_number,
                $this->twilioServiceNumber,
                [
                    "record"=>true,
                    "twiml" => $response
                ]
            );
    
          
            //$phone_number
            return response()->json([
                'status' => 'success',
                'message' => 'Mobile number verified.',
                'data'  => $new_user,
            ], 200);
        }
      
    }

