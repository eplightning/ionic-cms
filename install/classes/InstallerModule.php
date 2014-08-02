<?php

/**
 * IonicCMS installation framework module
 *
 * @package    ionic
 * @subpackage install
 * @copyright  2009-2013 (c) Wrex
 */
abstract class InstallerModule {

    /**
     * @var int
     */
    protected $currentStep = 1;

    /**
     * @var Installer
     */
    protected $installer = NULL;

    /**
     * Set current step
     *
     * @param integer $new
     */
    public function currentStep($new)
    {
        $this->currentStep = $new;
    }

    /**
     * Handle current step
     */
    abstract public function handle();

    /**
     * Set installer instance
     *
     * @param Installer $installer
     */
    public function installer(Installer $installer)
    {
        $this->installer = $installer;
    }

}
