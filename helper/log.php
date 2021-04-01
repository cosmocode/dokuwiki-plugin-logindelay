<?php

/**
 * Class helper_plugin_logindelay_log
 */
class helper_plugin_logindelay_log extends DokuWiki_Plugin
{
    /**
     * @var string Absolute path to a user's stat file
     */
    protected $statFile;

    /**
     * helper_plugin_logindelay_log constructor
     * @param string $user
     */
    public function __construct($user)
    {
        global $conf;
        $this->statFile = $conf['cachedir'] . '/logindelay_' . $user . '.log';
    }

    /**
     * Remove user's fail stats
     */
    public function clearFailStrikes()
    {
        @unlink($this->statFile);
    }

    /**
     * Increment user's fail stats
     * @return int
     */
    public function putFailStrike()
    {
        $strikes = $this->readStrikes() + 1;
        file_put_contents($this->statFile, $strikes);
        return $strikes;
    }

    /**
     * Return the number of failed logins as recorded in user's
     * stat file, or 0 if the file does not exist.
     *
     * @return int
     */
    public function readStrikes()
    {
        return (int) file_get_contents($this->statFile);
    }

    /**
     * Calculates in how many minutes a login retry will be allowed,
     * based on configuration and the stat file's timestamp
     *
     * @return int
     */
    public function calculateDelay()
    {
        if (!is_file($this->statFile)) return 0;

        $strikes = $this->readStrikes();
        if ($strikes < $this->getConf('maxFailures')) return 0;

        $delay = $this->getConf('initialDelay') * pow(2, ($strikes - $this->getConf('maxFailures')));
        $remainingDelay = $delay - (time() - filemtime($this->statFile)) / 60;
        return (int) $remainingDelay >= 0 ? ceil($remainingDelay) : 0;
    }
}
