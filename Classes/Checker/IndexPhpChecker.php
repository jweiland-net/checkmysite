<?php

namespace JWeiland\Checkmysite\Checker;

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
use JWeiland\Checkmysite\Configuration\ExtConf;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * check the index.php for hacking
 */
class IndexPhpChecker
{
    /**
     * @var ExtConf
     */
    private $extConf;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var string
     */
    private $hackingIssue = '';

    /**
     * @var array
     */
    private $pattern = array(
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
     * IndexPhpChecker constructor.
     */
    public function __construct()
    {
        $this->extConf = GeneralUtility::makeInstance('JWeiland\\Checkmysite\\Configuration\\ExtConf');
        $this->registry = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
        date_default_timezone_set('Europe/Berlin');
    }

    /**
     * read content of index.php and check content for hacking attacks
     *
     * @return void
     */
    public function checkIndexPhp()
    {
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
                    $this->sendMail(
                        $this->extConf->getEmailFrom(),
                        'TYPO3-CheckMySite',
                        $this->extConf->getEmailTo(),
                        'TYPO3-CheckMySite hacking attempt @ site:' . $_SERVER['HTTP_HOST'],
                        'Hello Administrator,'.PHP_EOL.'please check your TYPO3 installation, it seems to be hacking at the index.php of TYPO3!'.PHP_EOL.PHP_EOL.'Pattern match: '.PHP_EOL.$this->hackingIssue
                    );
                }
                // standard redirect url
                if (strlen($this->extConf->getRedirectUrl()) > 7) {
                    // we have to set the bad http reload, because if there will be output we can not change the header-location
                    $output = sprintf('
                        <html>
                            <head><meta name="robots" content="noindex,nofollow"><meta http-equiv="refresh" content="1; URL=%s"><title>redirect</title></head>
                            <body>redirect to <a href="%s">%s</a></body>
                        </html>',
                        $this->extConf->getRedirectUrl(),
                        'redirect',
                        $this->extConf->getRedirectUrl(),
                        $this->extConf->getRedirectUrl()
                    );
                } else {
                    $output = sprintf('
                        <html>
                            <head><meta name="robots" content="noindex,nofollow"><title>%s</title></head>
                            <body>%s</body>
                        </html>',
                        'Sorry',
                        $this->extConf->getContentText()
                    );
                }
                die($output);
            }
        } else {
            // panic no index.php or not readable!
            if ($this->extConf->getEmailTo()) {
                $this->sendMail(
                    $this->extConf->getEmailFrom(),
                    'TYPO3-CheckMySite',
                    $this->extConf->getEmailTo(),
                    'TYPO3-CheckMySite panic, no index.php!',
                    'Hello Administrator, please check your TYPO3 installation, your index.php is not readable!'
                );
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
    private function searchForHack($content)
    {
        foreach($this->pattern as $pattern) {
            $this->hackingIssue = $pattern;
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        // no hacking return
        return false;
    }

    /**
     * send the email, using the t3lib message function
     * or failsave with php mail function!
     *
     * @param string $emailFrom
     * @param string $nameFrom
     * @param string $emailTo
     * @param string $subject
     * @param string $body
     */
    private function sendMail($emailFrom, $nameFrom, $emailTo, $subject, $body)
    {
        /** @var MailMessage $mail */
        $mail = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Mail\\MailMessage');
        $mail->setFrom(array($emailFrom => $nameFrom));
        if (strpos($emailTo, ',') !== false) {
            $emailTo = implode(',', $emailTo);
        }
        $mail->setTo($emailTo);
        $mail->setSubject($subject);
        $mail->setBody($body);
        if ($mail->send()) {
            $this->registry->set('checkmysite', 'timestampOfLastSendEmail', time());
        }
    }
}
