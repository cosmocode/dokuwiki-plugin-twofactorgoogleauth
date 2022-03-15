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
        return $this->settings->get('verified');
    }

    /** @inheritdoc */
    public function getLabel()
    {
        return 'Google Authenticator (TOTP)';
    }

    /** @inheritdoc */
    public function renderProfileForm(Form $form)
    {
        global $conf;
        global $USERINFO;

        if (!$this->settings->get('verified')) {
            // Show the QR code so the user can add other devices.
            $secret = $this->getSecret();
            $name = $USERINFO['name'].'@'.$conf['title'];
            $url = 'otpauth://totp/'.rawurlencode($name).'?secret='.$secret;
            $svg = \dokuwiki\plugin\twofactorgoogleauth\QRCode::svg($url);

            $form->addHTML('<figure><figcaption>'.$this->getLang('directions').'</figcaption>');
            $form->addHTML($svg);
            $form->addHTML('</figure>');
            $form->addHTML('<p>'.$this->getLang('verifynotice').'</p>');
            $form->addTextInput('googleauth_verify', $this->getLang('verifymodule'));
        } else {
            $form->addHTML('<p>' . $this->getLang('passedsetup') . '</p>');
        }
        return $form;
    }

    /** @inheritdoc */
    public function handleProfileForm()
    {
        global $INPUT;

        $otp = $INPUT->str('googleauth_verify');
        if(!$otp) return;

        if($this->checkCode($otp)) {
            $this->settings->set('verified', true);
        }
    }

    /**
     * @inheritdoc
     * auto generates a new secret if none has been saved
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
