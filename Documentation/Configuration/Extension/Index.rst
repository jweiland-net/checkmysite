.. include:: ../Includes.rst.txt


.. _extensionSettings:

==================
Extension Settings
==================

Some general settings for `checkmysite` can be configured
in *Admin Tools -> Settings*.

Properties
==========

redirect_url
------------

..  confval:: redirect_url

    :Required: false
    :type: string
    :Default: `https://`
    :Path: Extension Settings

    If `index.php` was modified, you can redirect to another page. But please
    define a page on another server or domain to prevent endless
    redirect loops.

content_text
------------

..  confval:: content_text

    :Required: false
    :type: string
    :Default: Sorry, our website is down for maintenance. Please try again later!
    :Path: Extension Settings

    If you haven't configured a redirect URL, you can enter a fixed text here,
    which will be used for output.

email_to
--------

..  confval:: email_to

    :Required: false
    :type: string
    :Default: [EMPTY]
    :Path: Extension Settings

    Email address for notifications (separate multiple addresses with comma)

email_from
----------

..  confval:: email_from

    :Required: false
    :type: string
    :Default: [EMPTY]
    :Path: Extension Settings

    If we send emails, from whose email address we should send them? We prefer
    to insert addresses from your domain. Else they may be declared as spam.

email_wait_time
---------------

..  confval:: email_wait_time

    :Required: true
    :type: integer
    :Default: 1800
    :Path: Extension Settings

    Set the waiting time (in seconds) between the notification e-mails.

template_output_alternative
---------------------------

..  confval:: template_output_alternative

    :Required: true
    :type: string
    :Default: EXT:checkmysite/Resources/Private/Output/Alternative.html
    :Path: Extension Settings

    Define a template, which should be shown, if an index.php modification
    was detected.
