<?php
/**
 * DokuWiki Plugin logindelay (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Anna Dabrowska <dokuwiki@cosmocode.de>
 */

class action_plugin_logindelay_login extends DokuWiki_Action_Plugin
{

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     *
     * @return void
     */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('AUTH_LOGIN_CHECK', 'BEFORE', $this, 'checkDelay');
        $controller->register_hook('AUTH_LOGIN_CHECK', 'AFTER', $this, 'processLoginAttempt');
    }

    /**
     * Check if login attempt should be allowed or delayed
     *
     * @param Doku_Event $event
     */
    public function checkDelay(Doku_Event $event)
    {
        $user = $event->data['user'];

        // no user to check
        if (empty($user)) return;

        $logHelper = new \helper_plugin_logindelay_log($user);

        // should the login attempt be delayed?
        $delay = $logHelper->calculateDelay();

        if ($delay > 0) {
            $event->preventDefault();
            $this->displayMessage($delay);
        }
    }

    /**
     * Check status of login attempt on the result of auth_login_wrapper()
     *
     * @param Doku_Event $event
     */
    public function processLoginAttempt(Doku_Event $event)
    {
        global $ACT;
        if ($ACT !== 'login') return;

        $authenticatedUser = $_SERVER['REMOTE_USER'];
        $loginUser = $event->data['user'];

        $logHelper = new \helper_plugin_logindelay_log($loginUser);

        // exit early if this attempt should be delayed
        $delay = $logHelper->calculateDelay();
        if ($delay > 0) {
            $this->displayMessage($delay);
            return;
        }

        // failed login attempt, intervene
        if ($loginUser && $loginUser !== $authenticatedUser) {
            if ($logHelper->putFailStrike() > $this->getConf('maxFailures')) {
                $delay = $logHelper->calculateDelay();
                $this->displayMessage($delay);
            }
            return;
        }

        // successful login, clear any previous failures
        $logHelper->clearFailStrikes();
    }

    /**
     * Display error message with hint when a retry will be allowed
     *
     * @param $allowedRetry
     */
    protected function displayMessage($allowedRetry)
    {
        msg(sprintf($this->getLang('errorMessage'), $allowedRetry), -1);
    }
}

