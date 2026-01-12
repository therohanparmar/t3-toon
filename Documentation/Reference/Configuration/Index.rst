.. _configuration-reference:

Configuration Reference
=======================

Extension Configuration
----------------------

The extension configuration is stored in the TYPO3 Extension Manager and can be
accessed programmatically via ``ToonHelper::getConfig()``.

Configuration Array Structure
-----------------------------

.. code-block:: php

   [
       'escape_style' => 'backslash',        // string
       'min_rows_to_tabular' => 2,            // int
       'max_preview_items' => 200,            // int
       'coerce_scalar_types' => true,         // bool
   ]

Accessing Configuration
-----------------------

.. code-block:: php

   use RRP\T3Toon\Utility\ToonHelper;

   $config = ToonHelper::getConfig();
   $escapeStyle = $config['escape_style'];

Configuration via Extension Manager
-----------------------------------

Navigate to :guilabel:`Admin Tools > Settings > Extension Configuration` and
select :guilabel:`T3Toon – Token-Efficient Data Format for TYPO3 AI`.

All configuration options are available through the Extension Manager interface.

Service Configuration
---------------------

The extension uses TYPO3's dependency injection container. Services are configured
in ``Configuration/Services.yaml``.

Default Service Configuration
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: yaml

   services:
     _defaults:
       autowire: true
       autoconfigure: true
       public: false

     RRP\T3Toon\:
       resource: '../Classes/*'
       exclude: '../Classes/Domain/Model/*'

Custom Service Configuration
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

You can override service configuration in your site package:

.. code-block:: yaml

   services:
     RRP\T3Toon\Service\ToonEncoder:
       arguments:
         $config:
           escape_style: 'custom'
           min_rows_to_tabular: 3
