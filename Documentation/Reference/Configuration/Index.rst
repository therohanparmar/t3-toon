.. _configuration-reference:

Configuration Reference
=======================

Extension configuration
-----------------------

The extension configuration is stored in the TYPO3 Extension Configuration
(Install Tool) and read by the service. Defaults are applied in code when a
value is not set. Access the full, normalized config programmatically via
``ToonHelper::getConfig()``.

Settings
--------

==================== ============================ ============ ====================================
Key                  Values                       Default      Applies to
==================== ============================ ============ ====================================
enabled              boolean                      ``1``        encode (passthrough when off)
indent               int (>= 1)                   ``2``        encode
delimiter            comma / tab / pipe           ``comma``    encode
key_folding          off / safe                   ``off``      encode (spec §13.4)
flatten_depth        int (-1 = unbounded)         ``-1``       encode (key folding)
strict               boolean                      ``1``        decode (spec §14); also the
                                                               Playground's lenient-decode default
expand_paths         off / safe                   ``off``      decode (spec §13.4)
show_default_example boolean                      ``1``        Playground (prefill on first load)
json_baseline        minified / pretty2 /         ``minified`` Playground (size-reduction baseline)
                     pretty4 / tabs
==================== ============================ ============ ====================================

.. note::

   ``json_baseline`` only affects the size-reduction figure shown in the
   Playground. The optimization %% stored in usage logs is always measured
   against **minified** JSON so logged values stay comparable over time.

Normalized configuration array
------------------------------

``ToonHelper::getConfig()`` returns the values mapped for the spec engine:

.. code-block:: php

   [
       'enabled' => true,        // bool
       'indent' => 2,            // int (>= 1)
       'delimiter' => ',',       // ',' | "\t" | '|'
       'key_folding' => 'off',   // 'off' | 'safe'
       'flatten_depth' => null,  // int | null (null = unbounded)
       'strict' => true,         // bool
       'expand_paths' => 'off',  // 'off' | 'safe'
       'show_default_example' => true,  // bool (Playground prefill)
       'json_baseline' => 'minified',   // 'minified' | 'pretty2' | 'pretty4' | 'tabs'
   ]

Accessing configuration
-----------------------

.. code-block:: php

   use RRP\T3Toon\Utility\ToonHelper;

   $config = ToonHelper::getConfig();
   $indent = $config['indent'];
   $merged = ToonHelper::getConfigMerged(['indent' => 4]);

Configuration via Extension Manager
-----------------------------------

Navigate to :guilabel:`Admin Tools > Settings > Extension Configuration` and
select :guilabel:`T3Toon – Token-Efficient Data Format for TYPO3 AI`.

EncodeOptions and DecodeOptions (per-call)
------------------------------------------

See :ref:`Options (EncodeOptions & DecodeOptions) <options-reference>` for
overriding encoding/decoding per call instead of changing global configuration.
