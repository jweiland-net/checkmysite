<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/checkmysite.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Checkmysite\Configuration;

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class will streamline the values from extension manager configuration
 */
class ExtConf implements SingletonInterface
{
    protected string $emailTo = '';

    protected string$emailFrom = '';

    protected string $contentText = '';

    protected string $redirectUrl = '';

    protected int $emailWaitTime = 1800;

    protected string $templateOutputAlternative = '';

    public function __construct(ExtensionConfiguration $extensionConfiguration)
    {
        try {
            $extConf = $extensionConfiguration->get('checkmysite');
            if (!is_array($extConf)) {
                return;
            }

            if (empty($extConf)) {
                return;
            }

            // call setter method foreach configuration entry
            foreach ($extConf as $key => $value) {
                $methodName = 'set' . GeneralUtility::underscoredToUpperCamelCase($key);
                if (method_exists($this, $methodName)) {
                    $this->$methodName($value);
                }
            }
        } catch (ExtensionConfigurationExtensionNotConfiguredException | ExtensionConfigurationPathDoesNotExistException $e) {
        }
    }

    public function getEmailTo(): string
    {
        return $this->emailTo;
    }

    public function setEmailTo(string $emailTo): void
    {
        $this->emailTo = $emailTo;
    }

    public function getEmailFrom(): string
    {
        return $this->emailFrom;
    }

    public function setEmailFrom(string $emailFrom): void
    {
        $this->emailFrom = $emailFrom;
    }

    public function getContentText(): string
    {
        if (empty($this->contentText)) {
            return 'Sorry, our website is down for maintenance. Please try again later!';
        }

        return $this->contentText;
    }

    public function setContentText(string $contentText): void
    {
        $this->contentText = trim($contentText);
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    public function setRedirectUrl(string $redirectUrl): void
    {
        $this->redirectUrl = $redirectUrl;
    }

    public function getEmailWaitTime(): int
    {
        if (empty($this->emailWaitTime)) {
            return 1800;
        }

        return $this->emailWaitTime;
    }

    public function setEmailWaitTime(string $emailWaitTime): void
    {
        $this->emailWaitTime = (int)$emailWaitTime;
    }

    public function getTemplateOutputAlternative(): string
    {
        return $this->templateOutputAlternative;
    }

    public function setTemplateOutputAlternative(string $templateOutputAlternative): void
    {
        $this->templateOutputAlternative = $templateOutputAlternative;
    }
}
