.. _options-quick:
.. _options-reference:

===========================================
Options (EncodeOptions & DecodeOptions)
===========================================

You can override encoding and decoding behavior **per call** without changing extension configuration. Pass ``EncodeOptions`` to ``encode()`` / ``convert()`` / ``encodeStatic()`` / ``convertStatic()`` and ``DecodeOptions`` to ``decode()`` / ``decodeStatic()``. Use ``null`` to fall back to extension config.

EncodeOptions
-------------

Class: ``RRP\T3Toon\Domain\Model\EncodeOptions``

Constructor parameters (all optional; ``null`` = use extension config):

* **indent** (int|null) — Spaces per indentation level (default from config: 2)
* **delimiter** (string|null) — Field delimiter for tabular rows: ``EncodeOptions::DELIMITER_COMMA`` (``','``) or ``EncodeOptions::DELIMITER_TAB`` (``"\t"``)
* **maxPreviewItems** (int|null) — Maximum rows to emit in tabular blocks
* **escapeStyle** (string|null) — Escape style (e.g. ``'backslash'``)
* **minRowsToTabular** (int|null) — Minimum rows before using tabular format
* **primitiveArrayHeader** (bool|null) — If true, emit primitive sequential arrays as single line ``[N]: v1,v2,v3`` (spec-style)

Presets
~~~~~~~

.. code-block:: php

   use RRP\T3Toon\Domain\Model\EncodeOptions;
   use RRP\T3Toon\Service\Toon;

   // Default: use extension config
   $toon = Toon::encodeStatic($data, EncodeOptions::default());
   // or pass null
   $toon = Toon::encodeStatic($data, null);

   // Compact: indent 0, comma delimiter
   $toon = Toon::encodeStatic($data, EncodeOptions::compact());

   // Readable: indent 4
   $toon = Toon::encodeStatic($data, EncodeOptions::readable());

   // Tabular: tab delimiter (spreadsheet-friendly)
   $toon = Toon::encodeStatic($data, EncodeOptions::tabular());

   // Custom: e.g. primitive array header
   $options = new EncodeOptions(primitiveArrayHeader: true);
   $toon = Toon::encodeStatic($data, $options);

DecodeOptions
-------------

Class: ``RRP\T3Toon\Domain\Model\DecodeOptions``

Constructor parameters:

* **coerceScalarTypes** (bool|null) — If true, convert ``"true"``/``"false"``/``"123"`` to PHP types; if false, keep as strings (default from config: true)

Presets
~~~~~~~

.. code-block:: php

   use RRP\T3Toon\Domain\Model\DecodeOptions;
   use RRP\T3Toon\Service\Toon;

   // Default: use extension config (coerce scalar types)
   $data = Toon::decodeStatic($toon, DecodeOptions::default());

   // Lenient: do not coerce; keep "true", "42" as strings
   $data = Toon::decodeStatic($toon, DecodeOptions::lenient());

Merging with extension config
-----------------------------

Internally, non-null option properties are merged with extension configuration for that single call. Use ``ToonHelper::getConfigMerged()`` if you need the merged config array (e.g. for custom logic).
