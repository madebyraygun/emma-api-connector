<?php
/**
 * Emma API Connector plugin for Craft CMS 3.x
 *
 * Securely connect a signup form to the Emma API
 *
 * @link      https://madebyraygun.com
 * @copyright Copyright (c) 2018 Raygun Design, LLC
 */

namespace madebyraygun\emmaapiconnector\services;

use madebyraygun\emmaapiconnector\EmmaApiConnector as Plugin;
use MarkRoland\Emma\Client;

use Craft;
use craft\base\Component;

/**
 * EmmaApiConnectorService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Raygun Design, LLC
 * @package   EmmaApiConnector
 * @since     1
 */
class EmmaApiConnectorService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     EmmaApiConnector::$plugin->emmaApiConnectorService->exampleService()
     *
     * @return mixed
     */
    public function subscribe($email)
    {
        $settings = Plugin::$plugin->getSettings();
        
        if ($email === '' || !$this->validateEmail($email)) { // error, invalid email
            return array('status'=>'e');
        }

        $emmaClient = new Client($settings->emmaAccount, $settings->emmaPublicKey, $settings->emmaPrivateKey);
        // $emmaClient->debug = true;

        $response = $emmaClient->import_single_member($email, null, array($settings->emmaGroup), $settings->emmaSignupFormId);

        return $response;
    }

    /**
     * Creates return message object
     *
     * @param        $errorcode
     * @param        $email
     * @param        $vars
     * @param string $message
     * @param bool   $success
     *
     * @return array
     * @author Martin Blackburn
     */
    private function getMessage($errorcode, $email, $vars, $message = '', $success = false)
    {
        return [
            'success' => $success,
            'errorCode' => $errorcode,
            'message' => $message,
            'values' => [
                'email' => $email,
                'vars' => $vars
            ]
        ];
    }


    /**
     * Validate an email address.
     * Provide email address (raw input)
     * Returns true if the email address has the email
     * address format and the domain exists.
     *
     * @param string $email Email to validate
     *
     * @return boolean
     * @author André Elvan
     */
    public function validateEmail($email)
    {
        $isValid = true;
        $atIndex = strrpos($email, "@");
        if (is_bool($atIndex) && !$atIndex) {
            $isValid = false;
        } else {
            $domain = substr($email, $atIndex + 1);
            $local = substr($email, 0, $atIndex);
            $localLen = strlen($local);
            $domainLen = strlen($domain);
            if ($localLen < 1 || $localLen > 64) {
                // local part length exceeded
                $isValid = false;
            } else {
                if ($domainLen < 1 || $domainLen > 255) {
                    // domain part length exceeded
                    $isValid = false;
                } else {
                    if ($local[0] == '.' || $local[$localLen - 1] == '.') {
                        // local part starts or ends with '.'
                        $isValid = false;
                    } else {
                        if (preg_match('/\\.\\./', $local)) {
                            // local part has two consecutive dots
                            $isValid = false;
                        } else {
                            if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
                                // character not valid in domain part
                                $isValid = false;
                            } else {
                                if (preg_match('/\\.\\./', $domain)) {
                                    // domain part has two consecutive dots
                                    $isValid = false;
                                } else {
                                    if
                                    (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                                        str_replace("\\\\", "", $local))
                                    ) {
                                        // character not valid in local part unless
                                        // local part is quoted
                                        if (!preg_match('/^"(\\\\"|[^"])+"$/',
                                            str_replace("\\\\", "", $local))
                                        ) {
                                            $isValid = false;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if ($isValid && !(checkdnsrr($domain, "MX") || checkdnsrr($domain, "A"))) {
                // domain not found in DNS
                $isValid = false;
            }
        }
        return $isValid;
    }
}
