<?php
    require_once(__DIR__."/vendor/autoload.php");
    use Jumbojett\OpenIDConnectClient;

    class AuthOpenIDConnect extends AuthPluginBase {
        protected $storage = 'DbStorage';
        protected $settings = [
            'info' => [
                'type' => 'info',
                'content' => '<h1>OpenID Connect</h1><p>Please provide the following settings.</br>If necessary settings are missing, the default authdb login will be shown.</p>'
            ],
            'providerURL' => [
                'type' => 'string',
                'label' => 'Provider URL',
                'default' => ''
            ],
            'clientID' => [
                'type' => 'string',
                'label' => 'Client ID',
                'default' => ''
            ],
            'clientSecret' => [
                'type' => 'string',
                'label' => 'Client Secret',
                'default' => ''
            ],
            'redirectURL' => [
                'type' => 'string',
                'label' => 'Redirect URL',
                'help' => 'This URL is automatically set on plugin activation. Hand it to your ID Provider if needed.',
                'default' => '',
            ]
        ];
        static protected $description = 'OpenID Connect Authenticaton Plugin for LimeSurvey.';
        static protected $name = 'AuthOpenIDConnect';

        public function init(){
            $this->subscribe('beforeLogin');
            $this->subscribe('newUserSession');
            $this->subscribe('afterLogout');
        }

        public function beforeLogin(){
            $providerURL = $this->get('providerURL', null, null, false);
            $clientID = $this->get('clientID', null, null, false);
            $clientSecret = $this->get('clientSecret', null, null, false);
            $redirectURL = $this->get('redirectURL', null, null, false);

            if(!$providerURL || !$clientSecret || !$clientID || !$redirectURL){
                // Display authdb login if necessary plugin settings are missing.
                return;
            }

            $oidc = new OpenIDConnectClient($providerURL, $clientID, $clientSecret);
            $oidc->setRedirectURL($redirectURL);
            
            if(isset($_REQUEST['error'])){
                return;
            }

            try {
                if($oidc->authenticate()){
                    $username = $oidc->requestUserInfo('preferred_username');
                    $email = $oidc->requestUserInfo('email');
                    $givenName = $oidc->requestUserInfo('given_name');
                    $familyName = $oidc->requestUserInfo('family_name');
    
                    $user = $this->api->getUserByName($username);
    
                    if(empty($user)){
                        $user = new User;
                        $user->users_name = $username;
                        $user->setPassword(createPassword());
                        $user->full_name = $givenName.' '.$familyName;
                        $user->parent_id = 1;
                        $user->lang = $this->api->getConfigKey('defaultlang', 'en');
                        $user->email = $email;
        
                        if(!$user->save()){
                            // Couldn't create user, navigate to authdb login.
                            return;
                        }
                        // User successfully created.
                    }
    
                    $this->setUsername($user->users_name);
                    $this->setAuthPlugin();
                    return;
                }
            } catch (\Throwable $error) {
                // Error occurred during authentication process, redirect to authdb login.
                return;
            }
            
        }
        
        public function newUserSession(){
            $identity = $this->getEvent()->get('identity');
            if ($identity->plugin != 'AuthOpenIDConnect') {
                return;
            }

            $user = $this->api->getUserByName($this->getUsername());

            // Shouldn't happen, but just to be sure.
            if(empty($user)){
                $this->setAuthFailure(self::ERROR_UNKNOWN_IDENTITY, gT('User not found.'));
            } else {
                $this->setAuthSuccess($user);
            }
        }

        public function afterLogout(){
            Yii::app()->getRequest()->redirect('/', true, 302);
        }
    }
?>