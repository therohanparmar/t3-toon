.. _configuration-reference:

Configuration Reference
=======================

Extension configuration
----------------------

The extension configuration is stored in the TYPO3 Extension Manager (Install Tool) and is read by the service. Additional defaults (indent, delimiter, primitive_array_header) are applied in code when not set. Access the full config programmatically via ``ToonHelper::getConfig()``.

Configuration array structure
-----------------------------

.. code-block:: php

   [
       'indent' => 2,                    // int, spaces per level (code default)
       'delimiter' => ',',               // string, ',' or "\t" (code default)
       'escape_style' => 'backslash',    // string, from Install Tool
       'min_rows_to_tabular' => 2,       // int, from Install Tool
       'max_preview_items' => 200,       // int, from Install Tool
       'coerce_scalar_types' => true,    // bool, from Install Tool
       'primitive_array_header' => false, // bool, code default
   ]

Install Tool (Extension Configuration) provides: ``escape_style``, ``min_rows_to_tabular``, ``max_preview_items``, ``coerce_scalar_types``. The service adds defaults for ``indent``, ``delimiter``, and ``primitive_array_header`` when not present.

Accessing configuration
-----------------------

.. code-block:: php

   use RRP\T3Toon\Utility\ToonHelper;

   $config = ToonHelper::getConfig();
   $indent = $config['indent'];
   $merged = ToonHelper::getConfigMerged(['indent' => 4]);

Configuration via Extension Manager
-----------------------------------

Navigate to :guilabel:`Admin Tools > Settings > Extension Configuration` and select :guilabel:`T3Toon – Token-Efficient Data Format for TYPO3 AI`.

EncodeOptions and DecodeOptions (per-call)
------------------------------------------

See :ref:`Options (EncodeOptions & DecodeOptions) <options-reference>` for overriding encoding/decoding per call instead of changing global configuration.
