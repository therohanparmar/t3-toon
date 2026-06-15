.. _options-quick:
.. _options-reference:

===========================================
Options (EncodeOptions & DecodeOptions)
===========================================

You can override encoding and decoding behavior **per call** without changing
extension configuration. Pass ``EncodeOptions`` to ``encode()`` / ``convert()`` /
``encodeStatic()`` / ``convertStatic()`` and ``DecodeOptions`` to ``decode()`` /
``decodeStatic()``. Use ``null`` to fall back to extension config.

EncodeOptions
-------------

Class: ``RRP\T3Toon\Domain\Model\EncodeOptions``

Constructor parameters (all optional; ``null`` = use extension config):

* **indent** (int|null) — Spaces per indentation level (spec default 2; must be >= 1)
* **delimiter** (string|null) — Document delimiter: ``EncodeOptions::DELIMITER_COMMA`` (``','``), ``EncodeOptions::DELIMITER_TAB`` (``"\t"``), or ``EncodeOptions::DELIMITER_PIPE`` (``'|'``)
* **keyFolding** (string|null) — ``'off'`` (default) or ``'safe'`` (collapse single-key object chains into dotted paths, spec §13.4)
* **flattenDepth** (int|null) — Max segments to fold when ``keyFolding`` is ``'safe'``; ``null`` = unbounded

Presets
~~~~~~~

.. code-block:: php

   use RRP\T3Toon\Domain\Model\EncodeOptions;
   use RRP\T3Toon\Service\Toon;

   // Default: use extension config
   $toon = Toon::encodeStatic($data, EncodeOptions::default());

   // Compact: indent 2, comma delimiter (canonical)
   $toon = Toon::encodeStatic($data, EncodeOptions::compact());

   // Readable: indent 4
   $toon = Toon::encodeStatic($data, EncodeOptions::readable());

   // Tabular: tab delimiter (spreadsheet-friendly)
   $toon = Toon::encodeStatic($data, EncodeOptions::tabular());

   // Folded: safe key folding (a.b.c: 1)
   $toon = Toon::encodeStatic($data, EncodeOptions::folded());

   // Custom: pipe delimiter + safe folding to depth 2
   $options = new EncodeOptions(delimiter: '|', keyFolding: 'safe', flattenDepth: 2);
   $toon = Toon::encodeStatic($data, $options);

DecodeOptions
-------------

Class: ``RRP\T3Toon\Domain\Model\DecodeOptions``

Constructor parameters (all optional; ``null`` = use extension config):

* **strict** (bool|null) — Enforce strict-mode validation (array length and row-width counts, indentation multiples, escape and delimiter consistency, blank lines inside arrays). Default ``true`` (spec §14). Set ``false`` for lenient parsing.
* **expandPaths** (string|null) — ``'off'`` (default) keeps dotted keys literal; ``'safe'`` expands unquoted dotted keys into nested objects (spec §13.4)

.. note::

   Type coercion of unquoted tokens (``true``/``false``/``null``/numbers) always
   follows the specification in both strict and lenient modes. "Lenient" relaxes
   structural validation only; it does **not** disable type coercion.

Presets
~~~~~~~

.. code-block:: php

   use RRP\T3Toon\Domain\Model\DecodeOptions;
   use RRP\T3Toon\Service\Toon;

   // Default: strict decoding
   $data = Toon::decodeStatic($toon, DecodeOptions::default());

   // Lenient: relax strict-mode validation
   $data = Toon::decodeStatic($toon, DecodeOptions::lenient());

   // Expanded: split dotted keys into nested objects
   $data = Toon::decodeStatic($toon, DecodeOptions::expanded());

Merging with extension config
-----------------------------

Internally, non-null option properties are merged with extension configuration
for that single call. Use ``ToonHelper::getConfigMerged()`` if you need the
merged config array (e.g. for custom logic).
