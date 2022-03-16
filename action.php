<?php

use dokuwiki\Form\Form;
use dokuwiki\plugin\twofactor\OtpField;
use dokuwiki\plugin\twofactor\Provider;
use dokuwiki\plugin\twofactorgoogleauth\QRCode;

/**
 * Twofactor Provider for TOTP aka Google Authenticator
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

        $secret = $this->getSecret();
        $name = $USERINFO['name'] . '@' . $conf['title'];
        $url = 'otpauth://totp/' . rawurlencode($name) . '?secret=' . $secret;
        $svg = QRCode::svg($url);

        $form->addHTML('<figure><p>' . $this->getLang('directions') . '</p>');
        $form->addHTML($svg);
        $form->addHTML('<figcaption><code>'.$secret.'</code></figcaption>');
        $form->addHTML('</figure>');

        $form->addHTML('<p>' . $this->getLang('verifynotice') . '</p>');
        $form->addElement(new OtpField('googleauth_verify'));

        return $form;
    }

    /** @inheritdoc */
    public function handleProfileForm()
    {
        global $INPUT;

        // create secret when setup is initialized
        if ($INPUT->bool('init')) {
            $this->initSecret();
        }

        $otp = $INPUT->str('googleauth_verify');
        if (!$otp) return;

        if ($this->checkCode($otp)) {
            $this->settings->set('verified', true);
        }
    }

    /**
     * @inheritDoc
     */
    public function transmitMessage($code)
    {
        return $this->getLang('verifymodule');
    }
}
