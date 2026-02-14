.. _quickInstallation:

==================
Quick installation
==================

In a :ref:`composer-based TYPO3 installation <t3start:install>` you can install
the extension EXT:rrp_t3toon via composer:

.. code-block:: bash

   composer require rrp/t3-toon

In TYPO3 installations above version 11.5 the extension will be automatically
installed. You do not have to activate it manually.

Requirements
------------

* TYPO3 CMS 11.0.0 – 14.9.99
* PHP 7.4 or higher

Update the database scheme
--------------------------

Open your TYPO3 backend with :ref:`system maintainer <t3coreapi:system-maintainer>`
permissions.

In the module menu to the left navigate to :guilabel:`Admin Tools > Maintenance`,
then click on :guilabel:`Analyze database` and create all.

Clear all caches
----------------

In the same module :guilabel:`Admin Tools > Maintenance` you can also
conveniently clear all caches by clicking the button :guilabel:`Flush cache`.

Verify installation
-------------------

After installation, you can verify that the extension is loaded by checking
the extension manager or by using the service in your code:

**Instance API (recommended in TYPO3):**

.. code-block:: php

   use RRP\T3Toon\Service\Toon;
   use TYPO3\CMS\Core\Utility\GeneralUtility;

   $toon = GeneralUtility::makeInstance(Toon::class);
   $result = $toon->convert(['test' => 'value']);
   echo $result; // Should output: test: value

**Static API (convenience):**

.. code-block:: php

   use RRP\T3Toon\Service\Toon;

   $result = Toon::convertStatic(['test' => 'value']);
   echo $result; // test: value

**Backend module:** In the TYPO3 backend, go to :guilabel:`Tools > TOON Playground` to encode and decode TOON in the browser.
