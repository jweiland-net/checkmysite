<?php

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

/**
 * check the index.php for hacking
 */
class CheckFile
{
    private $arr_conf = array();
    private $str_emailcheckFile;
    private $bol_first = false;
    private $int_emailSendWaitTime;
    private $str_hackingIssue = '';
    private $flt_compatVersion = 0.0;

    /**
     * pattern
     */
    private $arr_pattern = array(
        /*
         * stop words
         */
        '/java/i',
        '/text/i',
        '/iframe/i',
        '/type/i',
        '/<[^<?php]/',
        '/>[^?>]/',
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
         * check for execution mehtods
         */
        '/`/i',
        '/fopen/i',
        '/readfile/i',
//		'/file/i',
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

    // every 30 min, better we use a ts conf var, but if there is no setting
    const EMAIL_WAIT = 1800;
    const CONTENT_TEXT = 'Ups, the server made a boo-boo!';

    public function __construct($arr_conf = null)
    {
        global $TYPO3_CONF_VARS;

        $this->arr_conf = $arr_conf!=null?$arr_conf:$this->arr_conf;
        $this->str_emailcheckFile = realpath(dirname(__FILE__)).'/check.db';
        date_default_timezone_set('Europe/Berlin');

        $this->flt_compatVersion = (float)$TYPO3_CONF_VARS['SYS']['compat_version'];
    }

    public function setConfig($arr_conf)
    {
        $this->arr_conf = $arr_conf;
        $this->int_emailSendWaitTime = $arr_conf['email_wait_time'];
        if ((int)$this->int_emailSendWaitTime == 0) {
            $this->int_emailSendWaitTime = self::EMAIL_WAIT;
        }
        if (strlen($this->arr_conf['content_text']) < 1) {
            $this->arr_conf['content_text'] = self::CONTENT_TEXT;
        }
    }

    public function checkIndexPhp($arr_conf)
    {
        $this->setConfig($arr_conf);
        // filetimes for email send
        $this->getEmailWaitingTime();
        $str_indexPhpContent = @file_get_contents(PATH_site.'index.php');
        if (strlen($str_indexPhpContent) > 0) {
            // removing all comments
            $str_cleanedIndexPhp = preg_replace('~(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|(//.*)~', '', $str_indexPhpContent);
            if ($this->searchForHack($str_cleanedIndexPhp)) {
                // hacking!
                if ($arr_conf['email_to'] && ($this->bol_first || ($this->int_lastmail+(int)$this->int_emailSendWaitTime <= time()))) {
                    $this->sendMail(
                    $arr_conf['email_from'],
                    'TYPO3-CheckMySite',
                    $arr_conf['email_to'],
                    'TYPO3-CheckMySite hacking attempt @ site:'.$_SERVER['HTTP_HOST'],
                    'Hello Administrator,'.PHP_EOL.'please check your TYPO3 installation, it seem to be hacking at the index.php of TYPO3!'.PHP_EOL.PHP_EOL.'Pattern match: '.PHP_EOL.$this->str_hackingIssue);
                }
                // standard redirect url
                $str_redirectUrl = 'http://'.$_SERVER['HTTP_HOST'].'/typo3conf/ext/checkmysite/out.php?body='.urlencode($arr_conf['content_text']).'&title=Sorry';
                if (strlen($arr_conf['redirect_url']) > 7) {
                    $str_redirectUrl = $arr_conf['redirect_url'];
                    // we have to set the bad http reaload, couse if there will be output we can not change the header-location
                    $str_out = '<html>
                                    <head>
                                        <meta name="robots" content="noindex,nofollow">
                                        <meta http-equiv="refresh" content="1; URL='.$str_redirectUrl.'">
                                        <title>redirect</title>
                                    </head>
                                    <body>redirect to <a href="'.$str_redirectUrl.'">'.$str_redirectUrl.'</a></body>
                                </html>';
                } else {
                    $str_out = '<html>
                                    <head>
                                        <meta name="robots" content="noindex,nofollow">
                                        <title>Sorry</title>
                                    </head>
                                    <body>
                                        '.$arr_conf['content_text'].'
                                    </body>
                                </html>';
                }
                die(print $str_out);
            }
            return;
        } else {
            // panic no index.php or not readable!
            if ($arr_conf['email_to'] && ($this->bol_first || ($this->int_lastmail+$this->int_emailSendWaitTime >= time()))) {
                $this->sendMail($arr_conf['email_from'], 'TYPO3-CheckMySite', $arr_conf['email_to'], 'TYPO3-CheckMySite panic, no index.php!', 'Hello Administratior, please check your TYPO3 installation, your index.php is not readable!');
            }
            exit;
        }
    }

    private function searchForHack($str_cleanedIndexPhp)
    {
        $bol_hack = false;
        foreach($this->arr_pattern as $str_pattern) {
            $this->str_hackingIssue = $str_pattern;
            if ($this->flt_compatVersion > 4.7 && $str_pattern == '/>[^?>]/') {
                continue;
            }
            $int_found = preg_match($str_pattern, $str_cleanedIndexPhp);
            if ((int) $int_found > 0) {
                return true;
            }
        }
        // no hacking return
        return $bol_hack;
    }

    /**
     * fetch the email send waiting time, and set it so this
     */
    private function getEmailWaitingTime()
    {
        if (is_file($this->str_emailcheckFile)) {
            $this->int_lastmail = filectime($this->str_emailcheckFile);
        } else {
            $this->bol_first = true;
        }
    }

    /**
     * touch the send file to remeber the last send email
     *
     * @return boolean true|false
     */
    private function setLastEmailSend()
    {
        return touch($this->str_emailcheckFile);
    }

    /**
     * send the email, using the t3lib message function
     * or failsave with php mailfunction!
     *
     * @param string $email_from
     * @param string $name_from
     * @param string $email_to
     * @param string $subject
     * @param string $body
     */
    private function sendMail($email_from, $name_from, $email_to, $subject, $body)
    {
        if ($this->flt_compatVersion < 4.5) {
            $headers = 'From: '.$email_from;
            $bol_send = t3lib_div::plainMailEncoded($email_to,$subject,$body, $headers);
            // OR: t3lib_utility_Mail::mail($email, $subject, $message, $headers, $additionalheaders = '-fexample@test.de');
        } else {
            if ($this->flt_compatVersion > 4.7) {
                $mail = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('t3lib_mail_Message');
            } else {
                $mail = t3lib_div::makeInstance('t3lib_mail_Message');
            }

            if (is_object($mail)) {
                $mail->setFrom(array($email_from => $name_from));
                if (strpos($email_to, ',') !== false) {
                    $email_to = implode(',', $email_to);
                }
                $mail->setTo($email_to);
                $mail->setSubject($subject);
                $mail->setBody($body);
                $bol_send = $mail->send();
            } else {
                // failsave php mail function
                $bol_send = mail($email_to, $subject, $body);
            }
        }
        if ($bol_send) {
            $this->setLastEmailSend();
        }
    }
}
