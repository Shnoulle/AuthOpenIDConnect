<?php
    require_once(__DIR__."/vendor/autoload.php");
    use Jumbojett\OpenIDConnectClient;

    class AuthOpenIDConnect extends AuthPluginBase {
        protected $storage = 'DbStorage';
        protected $settings = [
            'providerURL' => [
                'type' => 'string',
                'label' => 'Provider URL',
                'default' => ''
            ],
            'client' => [
                'type' => 'string',
                'label' => 'Client',
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
                'default' => '',
            ]
        ];
        static protected $description = 'OpenID Connect Authenticaton Plugin for LimeSurvey.';
        static protected $name = 'AuthOpenIDConnect';

        public function init(){
            $this->subscribe('beforeLogin');
            $this->subscribe('newUserSession');
        }

        public function beforeLogin(){
            $providerURL = $this->get('providerURL', null, null, false);
            $clientID = $this->get('clientID', null, null, false);
            $clientSecret = $this->get('clientSecret', null, null, false);
            $redirectURL = $this->get('redirectURL', null, null, false);

            $oidc = new OpenIDConnectClient($providerURL, $clientID, $clientSecret);
            $oidc->setRedirectURL($redirectURL);
            
            if(isset($_REQUEST['error'])){
                $this->log('Authentication failed - received error from ID Provider.');
                return;
            }

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
                    $user->lang = 'en';
                    $user->email = $email;
    
                    if(!$user->save()){
                        $this->log('User could not be created.');
                        return;
                    }
    
                    $this->log('User was successfully created.');
                }

                $this->setUsername($user->users_name);
                $this->setAuthPlugin();
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
    }
?>