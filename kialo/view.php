<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Prints an instance of mod_kialo.
 *
 * @package     mod_kialo
 * @copyright   2023 Kialo Inc. <support@kialo-edu.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once('vendor/autoload.php');

/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$k = optional_param('k', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('kialo', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('kialo', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('kialo', array('id' => $k), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('kialo', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

//$event = \mod_kialo\event\course_module_viewed::create(array(
//    'objectid' => $moduleinstance->id,
//    'context' => $modulecontext
//));
//$event->add_record_snapshot('course', $course);
//$event->add_record_snapshot('kialo', $moduleinstance);
//$event->trigger();

//$PAGE->set_url('/mod/kialo/view.php', array('id' => $cm->id));
//$PAGE->set_title(format_string($moduleinstance->name));
//$PAGE->set_heading(format_string($course->fullname));
//$PAGE->set_context($modulecontext);

//echo $OUTPUT->header();

// TODO: How to properly check if this is a student or teacher
if (false && has_capability('mod/kialo:addinstance', $modulecontext)) {
    redirect($moduleinstance->discussion_url, 'redirecting to Kialo...');
} else {
    //echo "STUDENT";
}


use OAT\Library\Lti1p3Core\Security\Key\KeyChainFactory;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;
use OAT\Library\Lti1p3Core\Platform\Platform;
use OAT\Library\Lti1p3Core\Registration\Registration;
use OAT\Library\Lti1p3Core\Tool\Tool;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use OAT\Library\Lti1p3Core\Security\User\Result\UserAuthenticationResultInterface;
use OAT\Library\Lti1p3Core\User\UserIdentityInterface;

use OAT\Library\Lti1p3Core\Message\Launch\Builder\PlatformOriginatingLaunchBuilder;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;


$kid = get_config("mod_kialo", "kid");
$privatekey_str = get_config("mod_kialo", "privatekey");
$publickey_str = bin2hex(openssl_pkey_get_details(openssl_pkey_get_private($privatekey_str))['rsa']['n']);

$platformKeyChain = (new KeyChainFactory)->create(
        $kid,                                // [required] identifier (used for JWT kid header)
        'kialo',                        // [required] key set name (for grouping)
        $publickey_str, // [required] public key (file or content)
        $privatekey_str,     // [optional] private key (file or content)
        '',                             // [optional] private key passphrase (if existing)
        KeyInterface::ALG_RS256            // [optional] algorithm (default: RS256)
);

$platform = new Platform(
        'kialo-xzy-42',                       // [required] identifier
        'kialo-moodle-plugin',                             // [required] name
        (new moodle_url('/mod/kialo'))->out(),                     // [required] audience
        (new moodle_url('/mod/kialo/lti_auth.php'))->out(),           // [optional] OIDC authentication url
        (new moodle_url('/mod/kialo/lti_token.php'))->out()  // [optional] OAuth2 access token url
);


$tool = new Tool(
        'kialo-edu',               // [required] identifier
        'Kialo Edu',                     // [required] name
        'http://localhost:5000',            # TODO PM-41849: replace with real url
        'http://localhost:5000/lti/login',   # TODO PM-41849: replace with real url
        'http://localhost:5000/lti/launch',      # TODO PM-41849: replace with real url
        'http://localhost:5000/lti/deep-linking' # TODO PM-41849: replace with real url
);
$platformJwksUrl = (new moodle_url('/mod/kialo/lti_jwks.php'))->out();
$toolJwksUrl = "http://localhost:5000/lti/jwks.json";  # TODO PM-41849: replace with real url

$deploymentIds = [$id];

$registration = new Registration(
        'registrationIdentifier',  // [required] identifier
        'kialo-xzy-42',    // [required] client id
        $platform,                 // [required] (PlatformInterface) platform
        $tool,                     // [required] (ToolInterface) tool
        $deploymentIds,            // [required] (array) deployments ids
        $platformKeyChain,         // [optional] (KeyChainInterface) key chain of the platform
        null,             // [optional] (KeyChainInterface) key chain of the tool
        $platformJwksUrl,          // [optional] JWKS url of the platform
        $toolJwksUrl,              // [optional] JWKS url of the tool
);

class RegistrationRepository implements RegistrationRepositoryInterface
{
    public function find(string $identifier): ?RegistrationInterface
    {
        global $registration;
        return $registration;
    }

    public function findAll(): array
    {
        global $registration;
        return [$registration];
    }

    public function findByClientId(string $clientId) : ?RegistrationInterface
    {
        global $registration;
        return $registration;
    }

    public function findByPlatformIssuer(string $issuer, string $clientId = null): ?RegistrationInterface
    {
        global $registration;
        return $registration;
    }

    public function findByToolIssuer(string $issuer, string $clientId = null): ?RegistrationInterface
    {
        global $registration;
        return $registration;
    }
};

class UserAuthenticationResult implements UserAuthenticationResultInterface
{
    /** @var bool */
    private $success;

    /** @var UserIdentityInterface|null */
    private $userIdentity;

    public function __construct(bool $success, ?UserIdentityInterface $userIdentity = null)
    {
        $this->success = $success;
        $this->userIdentity = $userIdentity;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isAnonymous(): bool
    {
        return null === $this->userIdentity;
    }

    public function getUserIdentity(): ?UserIdentityInterface
    {
        return $this->userIdentity;
    }
}


class UserAuthenticator implements UserAuthenticatorInterface
{
    public function authenticate(
            RegistrationInterface $registration,
            string $loginHint
    ): UserAuthenticationResultInterface {
        // TODO: Implement authenticate() method to perform user authentication (ex: session, LDAP, etc)
        // TODO: Check if Moodle user is logged in
        return new UserAuthenticationResult(true);
    }
};


// Create a builder instance
$builder = new PlatformOriginatingLaunchBuilder();

// Get related registration of the launch
/** @var RegistrationRepositoryInterface $registrationRepository */
$registration_instance = $registration; //$registrationRepository->find("WHATEVER");

// Build a launch message
$message = $builder->buildPlatformOriginatingLaunch(
        $registration_instance,                                               // related registration
        LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST, // message type of the launch, as an example: 'LtiDeepLinkingResponse'
        'http://localhost:5000/lti/launch',                                    // target link uri of the launch (final destination after OIDC flow)
        'loginHint42',                                                 // login hint that will be used afterwards by the platform to perform authentication
        $id,                                                        // will use the registration default deployment id, but you can pass a specific one
        [
                'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner' // role
        ],
        [
                new ContextClaim('contextIdentifier')  // LTI claim representing the context of the launch
        ]
);
echo $message->toHtmlRedirectForm();

//echo $OUTPUT->footer();
