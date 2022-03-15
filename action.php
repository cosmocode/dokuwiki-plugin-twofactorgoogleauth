<?php

use dokuwiki\plugin\twofactor\Provider;
use dokuwiki\Form\Form;

/**
 * If we turn this into a helper class, it can have its own language and settings files.
 * Until then, we can only use per-user settings.
 */
class action_plugin_twofactorgoogleauth extends Provider
{

    /** @inheritDoc */
    public function isConfigured()
    {
        return $this->settings->get('secret') &&
            $this->settings->get('verified');
    }

    /** @inheritdoc */
    public function getLabel()
    {
        return 'Google Authenticator (TOTP)';
    }

    /**
     * This user will need to interact with the QR code in order to configure GA.
     */
    public function renderProfileForm(Form $form)
    {
        global $conf;
        global $USERINFO;

        if (!$this->settings->get('verified')) {
            // Show the QR code so the user can add other devices.
            $secret = $this->getSecret();
            $this->settings->set('secret', $secret);
            $data = $this->generateQRCodeData($USERINFO['name'].'@'.$conf['title'], $secret);
            $form->addHTML('<figure><figcaption>'.$this->getLang('directions').'</figcaption>');
            $form->addHTML('<img src="'.$data.'" alt="'.$this->getLang('directions').'" />');
            $form->addHTML('</figure>');
            $form->addHTML('<span>'.$this->getLang('verifynotice').'</span><br>');
            $form->addTextInput(
                'googleauth_verify',
                $this->getLang('verifymodule')
            );
        } else {
            $form->addHTML('<span>' . $this->getLang('passedsetup') . '</span>');
        }
        return $form;
    }

    /**
     * Process any user configuration.
     */
    public function handleProfileForm()
    {
        global $INPUT;

        $otp = $INPUT->str('googleauth_verify');
        if ($otp && $this->processLogin($otp)) {
            $this->settings->set('verified', true);
        }
    }

    /**
     *  This module authenticates against a time-based code.
     */
    public function processLogin($code)
    {
        $ga = new dokuwiki\plugin\twofactor\GoogleAuthenticator();
        $twofactor = plugin_load('action', 'twofactor_profile');
        $expiry = $twofactor->getConf('generatorexpiry');
        $secret = $this->settings->get('secret');
        return $ga->verifyCode($secret, $code, $expiry);
    }

    /**
     * If there is
     * @return string
     * @throws Exception
     */
    public function getSecret()
    {
        $secret = $this->settings->get('secret');
        if (!$secret) {
            $ga = new dokuwiki\plugin\twofactor\GoogleAuthenticator();
            $secret = $ga->createSecret();
            $this->settings->set('secret', $secret);
        }
        return $secret;
    }

    /**
     * @inheritDoc
     */
    public function transmitMessage($code)
    {
        return $this->getLang('verifymodule');
    }
}
