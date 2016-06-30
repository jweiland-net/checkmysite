<?php

namespace JWeiland\Checkmysite\Configuration;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Stefan Froemken <projects@jweiland.net>, jweiland.net
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ExtConf implements SingletonInterface
{
    /**
     * @var string
     */
    protected $emailTo = '';

    /**
     * @var string
     */
    protected $emailFrom = '';

    /**
     * @var string
     */
    protected $contentText = '';

    /**
     * @var string
     */
    protected $redirectUrl = '';

    /**
     * @var int
     */
    protected $emailWaitTime = 1800;


    /**
     * constructor of this class
     * This method reads the global configuration and calls the setter methods.
     */
    public function __construct()
    {
        // get global configuration
        $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['checkmysite']);
        if (is_array($extConf)) {
            // call setter method foreach configuration entry
            foreach ($extConf as $key => $value) {
                $methodName = 'set' . GeneralUtility::underscoredToUpperCamelCase($key);
                if (method_exists($this, $methodName)) {
                    $this->$methodName($value);
                }
            }
        }
    }

    /**
     * Returns the emailTo
     *
     * @return string $emailTo
     */
    public function getEmailTo()
    {
        return $this->emailTo;
    }

    /**
     * Sets the emailTo
     *
     * @param string $emailTo
     * @return void
     */
    public function setEmailTo($emailTo)
    {
        $this->emailTo = (string)$emailTo;
    }

    /**
     * Returns the emailFrom
     *
     * @return string $emailFrom
     */
    public function getEmailFrom()
    {
        return $this->emailFrom;
    }

    /**
     * Sets the emailFrom
     *
     * @param string $emailFrom
     * @return void
     */
    public function setEmailFrom($emailFrom)
    {
        $this->emailFrom = (string)$emailFrom;
    }

    /**
     * Returns the contentText
     *
     * @return string $contentText
     */
    public function getContentText()
    {
        if (empty($this->contentText)) {
            return 'Sorry, our website is down for maintenance. Please try again later!';
        }
        return $this->contentText;
    }

    /**
     * Sets the contentText
     *
     * @param string $contentText
     * @return void
     */
    public function setContentText($contentText)
    {
        $this->contentText = trim((string)$contentText);
    }

    /**
     * Returns the redirectUrl
     *
     * @return string $redirectUrl
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * Sets the redirectUrl
     *
     * @param string $redirectUrl
     * @return void
     */
    public function setRedirectUrl($redirectUrl)
    {
        $this->redirectUrl = (string)$redirectUrl;
    }

    /**
     * Returns the emailWaitTime
     *
     * @return int $emailWaitTime
     */
    public function getEmailWaitTime()
    {
        if (empty($this->emailWaitTime)) {
            return 1800;
        }
        return $this->emailWaitTime;
    }

    /**
     * Sets the emailWaitTime
     *
     * @param int $emailWaitTime
     * @return void
     */
    public function setEmailWaitTime($emailWaitTime)
    {
        $this->emailWaitTime = (int)$emailWaitTime;
    }
}
