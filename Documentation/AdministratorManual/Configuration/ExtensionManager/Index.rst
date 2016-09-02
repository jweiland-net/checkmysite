.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../_IncludedDirectives.rst

.. _extensionManager:

Extension Manager
-----------------

Some general settings can be configured in the Extension Manager.
If you need to configure these, switch to the module "Extension Manager", select the extension "**checkmysite**" and press on the configure-icon!

The settings are divided into several tabs and described here in detail:

Properties
^^^^^^^^^^

.. container:: ts-properties

  ====================================== ========== ====================================================================
  Property                                Tab        Default
  ====================================== ========== ====================================================================
  redirect_url_                           basic      ``https://``
  content_text_                           basic      Sorry, our website is down for maintenance. Please try again later!
  email_to_                               email
  email_from_                             email
  email_wait_time_                        email      1800
  email_template_for_hacking_             template   EXT:checkmysite/Resources/Private/Email/HackingNotice.html
  email_template_for_not_readable_index_  template   EXT:checkmysite/Resources/Private/Email/NotReadableIndex.html
  template_output_redirect_               template   EXT:checkmysite/Resources/Private/Output/Redirect.html
  template_output_alternative_            template   EXT:checkmysite/Resources/Private/Output/Alternative.html
  ====================================== ========== ====================================================================

Property details
^^^^^^^^^^^^^^^^

.. only:: html

   .. contents::
        :local:
        :depth: 1

.. _extensionManager_redirect_url:

redirect_url
""""""""""""

If index.php was modified, you can redirect to another page. But please define a page on another server or domain
to prevent endless redirect loops.

.. _extensionManager_content_text:

content_text
""""""""""""

If you haven't configured a redirect URL, you can enter a fixed text here, which will be used for output.

.. _extensionManager_email_to:

email_to
""""""""

Email address for notifications (separate multiple addresses with comma)

.. _extensionManager_email_from:

email_from
""""""""""

If we send emails, from whose email address we should send them? We prefer to insert addresses from your domain. Else they may be declared as spam.

.. _extensionManager_email_wait_time:

email_wait_time
"""""""""""""""

set the waiting time (in seconds) between the notification e-mails.

.. _extensionManager_email_template_for_hacking:

email_template_for_hacking
""""""""""""""""""""""""""

Define an email template path, which should be used to send the hacking notification.

.. _extensionManager_email_template_for_not_readable_index:

email_template_for_not_readable_index
"""""""""""""""""""""""""""""""""""""

Define an email template path, which should be used to send a notification, if index.php is not readable.

.. _extensionManager_template_output_redirect:

template_output_redirect
""""""""""""""""""""""""

Define a template for redirecting to another location via META refresh.

.. _extensionManager_template_output_alternative:

template_output_alternative
"""""""""""""""""""""""""""

Define a template, which should be shown, if an index.php modification was detected.
