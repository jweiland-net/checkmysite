<?php
namespace JWeiland\Checkmysite\Checker;

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
use JWeiland\Checkmysite\Configuration\ExtConf;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * check the index.php for hacking
 */
class IndexPhpChecker
{
    /**
     * @var ExtConf
     */
    protected $extConf;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $hackingIssue = '';

    /**
     * @var array
     */
    protected $pattern = array(
        /*
         * stop words
         */
        '/java/i',
        '/text/i',
        '/iframe/i',
        '/type/i',
        /*
         * super globals
         */
        '/\$_GET/i',
        '/\$HTTP_GET_VARS/i',
        '/\$_POST/i',
        '/\$HTTP_POST_VARS/i',
        '/\$_COOKIE/i',
        '/\$HTTP_COOKIE_VARS/i',
        '/\$_REQUEST/i',
        '/\$_FILES/i',
        '/\$HTTP_POST_FILES/i',
        '/\$_SERVER\[\'REQUEST_METHOD\'\]/i',
        '/\$_SERVER\[\'QUERY_STRING\'\]/i',
        '/\$_SERVER\[\'REQUEST_URI\'\]/i',
        '/\$_SERVER\[\'HTTP_ACCEPT\'\]/i',
        '/\$_SERVER\[\'HTTP_ACCEPT_CHARSET\'\]/i',
        '/\$_SERVER\[\'HTTP_ACCEPT_ENCODING\'\]/i',
        '/\$_SERVER\[\'HTTP_ACCEPT_LANGUAGE\'\]/i',
        '/\$_SERVER\[\'HTTP_CONNECTION\'\]/i',
        '/\$_SERVER\[\'HTTP_HOST\'\]/i',
        '/\$_SERVER\[\'HTTP_REFERER\'\]/i',
        '/\$_SERVER\[\'HTTP_USER_AGENT\'\]/i',
        '/\$_SERVER\[\'HTTP_X_FORWARDED_FOR\'\]/i',
        '/\$_SERVER\[\'PHP_SELF\'\]/i',
        /*
         * check for execution methods
         */
        '/`/i',
        '/fopen/i',
        '/readfile/i',
        // '/file/i',
        '/fpassthru/i',
        '/gzfile/i',
        '/gzopen/i',
        '/gzpassthru/i',
        '/readgzfile/i',
        '/file_get_contents/i',
        '/file_put_contents/i',
        '/copy/i',
        '/rename/i',
        '/rmdir/i',
        '/mkdir/i',
        '/unlink/i',
        '/parse_ini_file/i',
        '/eval/i',
        /*
         * include functions
         */
        '/include/i',
        '/include_once/i',
        '/require_once/i',
        '/virtual/i',
        /*
         * socket functions
         */
        '/fsockopen/i',
        '/pfsockopen/i',
        '/socket_create/i',
        '/socket_connect/i',
        '/socket_write/i',
        '/socket_send/i',
        '/socket_recv/i',
        '/pfsockopen/i',
        /*
         * header functions
         */
        '/header/i',
        '/http_redirect/i',
        '/httpmessage::setheaders/i',
        '/httpmessage::setresponsecode/i',
        /*
         * sql functions
         */
        '/mysql_query/i',
        '/mssql_query/i',
        '/pg_query/i',
        /*
         * url functions
         */
        '/urlencode/i',
        '/urldecode/i',
        /*
         * hack used functions
         */
        '/base64_decode/i',
    );

    /**
     * initialize this object
     *
     * @return void
     */
    public function initializeObject()
    {
        $this->extConf = GeneralUtility::makeInstance('JWeiland\\Checkmysite\\Configuration\\ExtConf');
        $this->registry = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
        // @ToDo: SF: Why do we need this?
        date_default_timezone_set('Europe/Berlin');
    }

    /**
     * read content of index.php and check content for hacking attacks
     *
     * @return void
     */
    public function checkIndexPhp()
    {
        $this->initializeObject();
        $content = @file_get_contents(PATH_site . 'index.php');
        if (!empty($content)) {
            // removing all comments
            $content = preg_replace('~(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|(//.*)~', '', $content);
            if ($this->searchForHack($content)) {
                // hacking detected! send mail
                if (
                    $this->extConf->getEmailTo()
                    && (int)$this->registry->get('checkmysite', 'timestampOfLastSendEmail') + $this->extConf->getEmailWaitTime() <= time()
                ) {
                    $this->sendHackingNotice();
                }
                die($this->getOutput());
            }
        } else {
            // panic no index.php or not readable!
            if ($this->extConf->getEmailTo()) {
                $this->sendMissingIndexNotice();
            }
            exit;
        }
    }

    /**
     * Parse the content
     * If a modification was detected return true
     *
     * @param string $content
     * @return bool
     */
    protected function searchForHack($content)
    {
        foreach ($this->pattern as $pattern) {
            $this->hackingIssue = $pattern;
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        // no hacking. return
        return false;
    }
    
    /**
     * Generate an alternative output on hacking detection
     * or an output for redirect
     *
     * @return string
     */
    protected function getOutput()
    {
        // redirect to url, if set and is valid
        if (
            !empty(parse_url($this->extConf->getRedirectUrl(), PHP_URL_HOST)) &&
            GeneralUtility::isValidUrl($this->extConf->getRedirectUrl())
        ) {
            // because of the attack we may have an output.
            // That's why we use the META Refresh instead of the better header Location method
            $output = $this->renderTemplate(
                $this->extConf->getTemplateOutputRedirect(),
                array(
                    'redirectUrl' => $this->extConf->getRedirectUrl()
                )
            );
        } else {
            $output = $this->renderTemplate(
                $this->extConf->getTemplateOutputAlternative(),
                array(
                    'title' => 'Sorry',
                    'content' => $this->extConf->getContentText()
                )
            );
        }
        
        return $output;
    }
    
    /**
     * send hacking notice via email
     *
     * @return void
     */
    protected function sendHackingNotice()
    {
        $this->sendMail(
            sprintf(
                'TYPO3-CheckMySite hacking attempt @ site: %s',
                $_SERVER['HTTP_HOST']
            ),
            $this->renderTemplate(
                $this->extConf->getEmailTemplateForHacking(),
                array(
                    'hackingIssue' => $this->hackingIssue
                )
            )
        );
    }
    
    /**
     * send missing or not readable index.php notice via email
     *
     * @return void
     */
    protected function sendMissingIndexNotice()
    {
        $this->sendMail(
            'TYPO3-CheckMySite panic, no index.php!',
            $this->renderTemplate(
                $this->extConf->getEmailTemplateForMissingIndex()
            )
        );
    }
    
    /**
     * send the email, using the MailMessage class
     *
     * @param string $subject
     * @param string $body
     *
     * @return void
     */
    protected function sendMail($subject, $body)
    {
        /** @var MailMessage $mail */
        $mail = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Mail\\MailMessage');
        $mail->setFrom(array($this->extConf->getEmailFrom() => 'TYPO3-CheckMySite'));
        $mail->setTo(GeneralUtility::trimExplode(',', $this->extConf->getEmailTo()));
        $mail->setSubject($subject);
        $mail->setBody(nl2br(strip_tags($body)));
        $mail->addPart($body, 'text/html');
        if ($mail->send()) {
            $this->registry->set('checkmysite', 'timestampOfLastSendEmail', time());
        }
    }
    
    /**
     * Render template
     *
     * @param string $file A filename which can also be started with EXT:
     * @param array $assign Add some variables you want to assign to template
     *
     * @return string
     */
    protected function renderTemplate($file, array $assign = array())
    {
        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance('TYPO3\\CMS\\Fluid\\View\\StandaloneView');
        $view->setTemplatePathAndFilename(
            GeneralUtility::getFileAbsFileName($file)
        );
        $view->assignMultiple($assign);
        
        return $view->render();
    }
}
