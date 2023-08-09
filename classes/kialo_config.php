<?php

namespace mod_kialo;

use moodle_url;
use OAT\Library\Lti1p3Core\Platform\Platform;
use OAT\Library\Lti1p3Core\Registration\Registration;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainFactory;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;
use OAT\Library\Lti1p3Core\Tool\Tool;

class kialo_config {
    private static $instance = null;

    public function get_tool_url() {
        $target_url_from_env = getenv('TARGET_KIALO_URL');
        if (!empty($target_url_from_env)) {
            return $target_url_from_env;
        } else {
            return "https://www.kialo-edu.com";
        }
    }

    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new kialo_config();
        }

        return self::$instance;
    }

    /**
     * The privatekey and kid are generated once when the plugin is installed, see upgradelib.php.
     *
     * @return KeyChainInterface
     * @throws \dml_exception
     */
    public function get_platform_keychain(): KeyChainInterface {
        $kid = get_config("mod_kialo", "kid");
        $privatekey_str = get_config("mod_kialo", "privatekey");
        $publickey_str = openssl_pkey_get_details(openssl_pkey_get_private($privatekey_str))['key'];

        return (new KeyChainFactory)->create(
                $kid,                            // [required] identifier (used for JWT kid header)
                'kialo',             // [required] key set name (for grouping)
                $publickey_str,                 // [required] public key (file or content)
                $privatekey_str,                // [optional] private key (file or content)
                '',          // [optional] private key passphrase (if existing)
                KeyInterface::ALG_RS256            // [optional] algorithm (default: RS256)
        );
    }

    /**
     * @return Platform
     */
    public function get_platform(): Platform {
        return new Platform(
                'kialo-moodle-plguin',                          // [required] identifier
                'Kialo Moodle Plugin',                            // [required] name
                (new moodle_url('/mod/kialo'))->out(),              // [required] audience
                (new moodle_url('/mod/kialo/lti_auth.php'))->out(), // [optional] OIDC authentication url
        );
    }

    public function get_tool(): Tool {
        $tool_url = $this->get_tool_url();
        return new Tool(
                'kialo-edu',
                'Kialo Edu',
                $tool_url,
                $tool_url . "/lti/login",
                $tool_url . '/lti/launch',
                $tool_url . '/lti/deep-linking'
        );
    }

    public function create_registration(?string $deployment_id = null): Registration {
        $tool = $this->get_tool();
        $platformJwksUrl = (new moodle_url('/mod/kialo/lti_jwks.php'))->out();
        $toolJwksUrl = $this->get_tool_url() . "/lti/jwks.json";
        $deploymentIds = $deployment_id ? [$deployment_id] : [];

        return new Registration(
                'kialo-moodle-registration',
                'kialo-moodle-client',  # TODO: change this to something more unique?
                $this->get_platform(),
                $tool,
                $deploymentIds,
                $this->get_platform_keychain(),
                null,
                $platformJwksUrl,
                $toolJwksUrl,
        );
    }
}
