<?php
/**
 * IonicCMS installation framework
 *
 * @package    ionic
 * @subpackage install
 * @copyright  2009-2013 (c) Wrex
 */
session_start();

// Pathes
define('INSTALLER_PATH', dirname(__FILE__).'/');
define('IONIC_PATH'    , substr(INSTALLER_PATH, 0, strlen(INSTALLER_PATH) - 8));
define('APPPATH'       , IONIC_PATH.'private/application/');
define('MODULE'        , 'install');

// Load file
require_once INSTALLER_PATH.'classes/Installer.php';
require_once INSTALLER_PATH.'classes/InstallerModule.php';
require_once INSTALLER_PATH.'classes/InstallerModuleInstall.php';

// Installer
$installer = new Installer;

// Handle
$installer->handleInstallation(new InstallerModuleInstall);
