<?php
namespace JWeiland\Checkmysite\Configuration;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
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
     * @var string
     */
    protected $emailTemplateForHacking = '';
    
    /**
     * @var string
     */
    protected $emailTemplateForNotReadableIndex = '';
    
    /**
     * @var string
     */
    protected $templateOutputRedirect = '';
    
    /**
     * @var string
     */
    protected $templateOutputAlternative = '';
    
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
    
    /**
     * Returns the emailTemplateForHacking
     *
     * @return string $emailTemplateForHacking
     */
    public function getEmailTemplateForHacking()
    {
        return $this->emailTemplateForHacking;
    }
    
    /**
     * Sets the emailTemplateForHacking
     *
     * @param string $emailTemplateForHacking
     * @return void
     */
    public function setEmailTemplateForHacking($emailTemplateForHacking)
    {
        $this->emailTemplateForHacking = (string)$emailTemplateForHacking;
    }
    
    /**
     * Returns the emailTemplateForNotReadableIndex
     *
     * @return string $emailTemplateForNotReadableIndex
     */
    public function getEmailTemplateForNotReadableIndex()
    {
        return $this->emailTemplateForNotReadableIndex;
    }
    
    /**
     * Sets the emailTemplateForNotReadableIndex
     *
     * @param string $emailTemplateForNotReadableIndex
     * @return void
     */
    public function setEmailTemplateForNotReadableIndex($emailTemplateForNotReadableIndex)
    {
        $this->emailTemplateForNotReadableIndex = (string)$emailTemplateForNotReadableIndex;
    }
    
    /**
     * Returns the templateOutputRedirect
     *
     * @return string $templateOutputRedirect
     */
    public function getTemplateOutputRedirect()
    {
        return $this->templateOutputRedirect;
    }
    
    /**
     * Sets the templateOutputRedirect
     *
     * @param string $templateOutputRedirect
     * @return void
     */
    public function setTemplateOutputRedirect($templateOutputRedirect)
    {
        $this->templateOutputRedirect = (string)$templateOutputRedirect;
    }
    
    /**
     * Returns the templateOutputAlternative
     *
     * @return string $templateOutputAlternative
     */
    public function getTemplateOutputAlternative()
    {
        return $this->templateOutputAlternative;
    }
    
    /**
     * Sets the templateOutputAlternative
     *
     * @param string $templateOutputAlternative
     * @return void
     */
    public function setTemplateOutputAlternative($templateOutputAlternative)
    {
        $this->templateOutputAlternative = (string)$templateOutputAlternative;
    }
}
