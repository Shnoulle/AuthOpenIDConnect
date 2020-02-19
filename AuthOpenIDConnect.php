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
                'default' => ''
            ]
        ];
        static protected $description = 'OpenID Connect Authenticaton Plugin for LimeSurvey.';
        static protected $name = 'AuthOpenIDConnect';

        public function init(){ }
    }
?>