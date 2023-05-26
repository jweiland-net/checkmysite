<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/checkmysite.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Checkmysite\Checker;

use JWeiland\Checkmysite\Configuration\ExtConf;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Check the index.php for hacking attacks
 */
class IndexPhpChecker
{
    protected ExtConf $extConf;

    protected Registry $registry;

    protected string $hackingIssue = '';

    protected array $pattern = [
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
    ];

    public function __construct(ExtConf $extConf, Registry $registry)
    {
        $this->extConf = $extConf;
        $this->registry = $registry;
    }

    /**
     * Read content of index.php and check content for hacking attacks
     */
    public function checkIndexPhp(): void
    {
        // No need to check against is_file, because without an index.php this script will not be started
        if (is_readable(Environment::getPublicPath() . '/index.php')) {
            $content = @file_get_contents(Environment::getPublicPath() . '/index.php');
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
            // panic index.php is not readable
            if ($this->extConf->getEmailTo()) {
                $this->sendNotReadableNotice();
            }
            exit;
        }
    }

    /**
     * Parse the content.
     * If a modification/hack was detected, return true.
     */
    protected function searchForHack(string $content): bool
    {
        foreach ($this->pattern as $pattern) {
            $this->hackingIssue = $pattern;
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate an alternative output on hacking detection
     * or an output for redirect
     */
    protected function getOutput(): string
    {
        // Redirect to url, if set and is valid
        if (
            !empty(parse_url($this->extConf->getRedirectUrl(), PHP_URL_HOST))
            && GeneralUtility::isValidUrl($this->extConf->getRedirectUrl())
        ) {
            // because of the attack we may have an output.
            // That's why we use the META Refresh instead of the better header Location method
            $output = $this->renderTemplate(
                $this->extConf->getTemplateOutputRedirect(),
                [
                    'redirectUrl' => $this->extConf->getRedirectUrl(),
                ]
            );
        } else {
            $output = $this->renderTemplate(
                $this->extConf->getTemplateOutputAlternative(),
                [
                    'title' => 'Sorry',
                    'content' => $this->extConf->getContentText(),
                ]
            );
        }

        return $output;
    }

    /**
     * Send hacking notice via email
     */
    protected function sendHackingNotice(): void
    {
        $this->sendMail(
            sprintf(
                'TYPO3-CheckMySite hacking attempt @ site: %s',
                $_SERVER['HTTP_HOST']
            ),
            $this->renderTemplate(
                $this->extConf->getEmailTemplateForHacking(),
                [
                    'hackingIssue' => $this->hackingIssue,
                ]
            )
        );
    }

    /**
     * Send not readable index.php notice via email
     */
    protected function sendNotReadableNotice(): void
    {
        $this->sendMail(
            'TYPO3-CheckMySite panic. index.php not readable!',
            $this->renderTemplate(
                $this->extConf->getEmailTemplateForNotReadableIndex()
            )
        );
    }

    /**
     * Send the email, using the MailMessage class
     */
    protected function sendMail(string $subject, string $body): void
    {
        $recipients = [];
        foreach (GeneralUtility::trimExplode(',', $this->extConf->getEmailTo()) as $email) {
            $recipients[] = new Address($email);
        }

        $fluidEmail = GeneralUtility::makeInstance(FluidEmail::class);
        $fluidEmail->addFrom(new Address($this->extConf->getEmailFrom(), 'TYPO3-CheckMySite'));
        $fluidEmail->addTo(...$recipients);
        $fluidEmail->subject($subject);
        $fluidEmail->assignMultiple([
            'headline' => $subject,
            'content' => $body,
        ]);

        try {
            GeneralUtility::makeInstance(Mailer::class)->send($fluidEmail);
            $this->registry->set('checkmysite', 'timestampOfLastSendEmail', time());
        } catch (TransportExceptionInterface $e) {
        }
    }

    /**
     * Render template
     */
    protected function renderTemplate(string $file, array $assign = []): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(
            GeneralUtility::getFileAbsFileName($file)
        );
        $view->assignMultiple($assign);

        return $view->render();
    }
}
