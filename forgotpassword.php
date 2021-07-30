/**
     * Create token password reset
     *
     * @param  [string] email
     * @return [string] message
     */
    public function create(Request $request){
        $request->validate([
            'email' => 'required|string|email',
        ]);
        $user = User::where(['email' => $request->email,'role_id' => $this->roleId])->first();

        // getting email subject and body start 
        $emailTemplateContent = EmailTemplateContent::where('template_id',5)->first();
        $emailSubject = $emailTemplateContent->email_subject;
        $emailBody = $emailTemplateContent->email_content;
        /// getting email subject and body end

        $frontend_app_url = env('FRONTEND_APP_URL'); 

        if (!$user)
            return response()->json([
                'status' => 'error',
                'message' => 'We can\'t find a user with that e-mail address.'
            ], 200);

        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => str_random(60)
             ]
        );

        $url = $frontend_app_url.'/reset-password/employer/'.$passwordReset->token;

        $resetPasswordBtn = '<a href="'.$url.'" target="_blank" class="button button-primary">Reset Password</a>';

        $filteredSubject = str_replace(
            [
                '[your_name]',
                '[email]', 
                '[days_left]',
                '[expiration_date]',
                '[activation_link]',
                '[website_name]',
                '[website_link]',
                '[pricing_page]',
                '[recovery_password_link]'

            ],
            [
                $user->firstname,
                $user->email,
                '',
                '',
                '',
                env('APP_NAME'),
                $frontend_app_url,
                '',
                '',
            ],
            $emailSubject
        );

        $filteredContent = str_replace(
            [
                '[your_name]',
                '[email]', 
                '[days_left]',
                '[expiration_date]',
                '[activation_link]',
                '[website_name]',
                '[website_link]',
                '[pricing_page]',
                '[recovery_password_link]'

            ],
            [
                $user->firstname,
                $user->email,
                '',
                '',
                '',
                env('APP_NAME'),
                $frontend_app_url,
                '',
                $resetPasswordBtn,
            ],
            $emailBody
        );

        if ($user && $passwordReset)
            $user->notify(
                new PasswordResetRequest($filteredContent,$filteredSubject,$user->role)
            );
        return response()->json([
            'status' => 'success',
            'message' => 'We have e-mailed your password reset link!'
        ]);
    }

    /**
     * Find token password reset
     *
     * @param  [string] $token
     * @return [string] message
     * @return [json] passwordReset object
     */
    public function find($token){

        $passwordReset = PasswordReset::where('token', $token)
            ->first();
        if (!$passwordReset)
            return response()->json([
                'status' => 'error',
                'message' => 'This password reset token is invalid.'
            ], 404);
        if (Carbon::parse($passwordReset->updated_at)->addMinutes(720)->isPast()) {
            $passwordReset->delete();
            return response()->json([
                'status' => 'error',
                'message' => 'This password reset token is invalid.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $passwordReset
        ],200);
    }

    /**
     * Reset password
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @param  [string] token
     * @return [string] message
     * @return [json] user object
     */
    public function reset(Request $request){
        $request->validate([
            'password' => 'required|min:8|string',
            'token' => 'required|string'
        ]);

        $passwordReset = PasswordReset::where('token', $request->token)->first();

        if (!$passwordReset)
            return response()->json([
                'message' => 'This password reset token is invalid.'
            ], 404);
        $user = User::where('email', $passwordReset->email)->first();
        if (!$user)
            return response()->json([
                'message' => 'We can\'t find a user with that e-mail address.'
            ], 404);
        $user->password = bcrypt($request->password);
        $user->save();
        $passwordReset->delete();
        // $user->notify(new PasswordResetSuccess($passwordReset));
        
        return response()->json([
            'status'    => 'success',
            'message'   => 'Password changed successfuly, please login',
            'data'      => $user
        ],200);
    }