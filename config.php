<?php

require_once (INCLUDE_DIR.'/class.plugin.php');
require_once (INCLUDE_DIR.'/class.forms.php');
require_once ('jsonRPC.php');

class LimeSurveyConfig extends PluginConfig {

    // Provide compatibility function for versions of osTicket prior to
    // translation support (v1.9.4)
    function translate() {
        if (!method_exists('Plugin', 'translate')) {
            return array(
                function($x) { return $x; },
                function($x, $y, $n) { return $n != 1 ? $y : $x; },
            );
        }
        return Plugin::translate('lime-survey');
    }

    public function getServer() {
        return $this->get('domain');
    }

    public function getSurveyID() {
        return $this->get('surveyid');
    }

    public function getUser() {
        return $this->get('user');
    }

    public function getPasswd() {
        return $this->get('passwd');
    }

    public function getEvent() {
        return $this->get('event');
    }

    public function getKey() {
        return $this->get('KEY');
    }

    public function getServerSettings() {
        $settings = [
            'domain'   => $this->getServer(),
            'surveyid'   => $this->getSurveyID(),
            'user'   => $this->getUser(),
            'passwd'    => $this->getPasswd(),
            'event'    => $this->getEvent(),
            'scopes' => $scopes,
        ];

        return $settings;
    }

    function getAllOptions() {
        list($__, $_N) = self::translate();
        return array(
            'lss' => new SectionBreakField(array(
                'label' => 'LimeSurvey® Settings',
                'hint' => $__('This section should be all that is required for LimeSurvey'),
            )),
            'domain' => new TextboxField(array(
                'label' => $__('Domain Name'),
                'hint' => $__('FQDN of the LimeSurvey server'),
                'configuration' => array('size'=>40, 'length'=>60),
                'validators' => array(
                function($self, $val) use ($__) {
                    if (strpos($val, '.') === false)
                        $self->addError(
                            $__('Domain name is expected'));
                }),
            )),
            'surveyid' => new TextboxField(array(
                'label' => $__('Survey ID'),
                'hint' => $__('ID of the Active Survey'),
                'configuration' => array('size'=>40, 'length'=>120),
            )),
            'user' => new TextboxField(array(
                'label' => $__('Username'),
                'hint' => $__('Username for the authentication'),
                'configuration' => array('size'=>40, 'length'=>120),
            )),
            'passwd' => new TextboxField(array(
                'widget' => 'PasswordWidget',
                'label' => $__('Password'),
                'validator' => 'noop',
                'hint' => $__("Password associated with the user account"),
                'configuration' => array('size'=>40),
            )),
            'event' => new ChoiceField(array(
                'label' => $__('Trigger Event'),
                'hint' => $__("Event to trigger the Survey Enrollment process"),
                'configuration' => array('size'=>40, 'length'=>120),
                'default' => 'ticket.closed',
                'choices' => array(
                    'ticket.created' => 'Ticket Created',
                    'ticket.closed' => 'Ticket Closed',
                ),
            )),
            'KEY' => new TextboxField(array(
                'configuration' => array('size'=>40, 'length'=>120),
                'visibility' => new VisibilityConstraint(
                    new Q(),
                    VisibilityConstraint::HIDDEN),
                'default' => 'tWDUcstQspwxMTD9ebhpk3',
            ))
        );
    }

    function getOptions() {
        return  $this->getAllOptions();
    }

    function pre_save(&$config, &$errors) {
        list($__, $_N) = self::translate();
        $connection_error = false;
        $missing_settings = false;

        if ($config['user'] and !$config['passwd']){
            $config['passwd'] = Crypto::decrypt($this->getPasswd(), SECRET_SALT, $config['KEY']); 
        }

        if (!$config['surveyid'])
            $this->getForm()->getField('surveyid')->addError(
                $__("No Survey ID specified."));

        if ($config['domain'] && $config['user'] && $config['passwd'] && $config['surveyid']) {
            $testJSONRPCClient = new JsonRPCClient( 'https://'.$config['domain'].'/index.php/admin/remotecontrol' );
            $sessionKey = $testJSONRPCClient->get_session_key( $config['user'], $config['passwd'] );
            if (is_array($sessionKey)){
                if ($sessionKey['status']){
                    $connection_error = $sessionKey['status'];
                }
            }else{
                $testJSONRPCClient->release_session_key( $sessionKey );
            }
        }
        else {
            if (!$config['domain']){
                $this->getForm()->getField('domain')->addError(
                    $__("No servers specified."));
                $missing_settings = true;
                }
            if (!$config['user']){
                $this->getForm()->getField('user')->addError(
                    $__("No user specified."));
                $missing_settings = true;
                }
            if (!$config['passwd']){
                $this->getForm()->getField('passwd')->addError(
                    $__("No password specified."));
                $missing_settings = true;
            }
        }

        if ($missing_settings) {
            $errors['err'] = $__('Missing settings in the configuration');
        }

        if ($connection_error) {
            $this->getForm()->getField('domain')->addError($connection_error);
            $errors['err'] = $__('Unable to connect to the LimeSurvey servers');
        }

        global $msg;
        if (!$errors)
            $config['passwd'] = Crypto::encrypt($config['passwd'],SECRET_SALT, $config['KEY']);
            $msg = $__('Lime Survey configuration updated successfully');

        return !$errors;
    }

}

?>

