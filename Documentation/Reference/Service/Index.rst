.. _service-reference:

Service API Reference
=====================

Main service class: Toon
-----------------------

Class: ``RRP\T3Toon\Service\Toon``

The main service provides **instance** and **static** methods for converting between PHP arrays/JSON and TOON format, and for token estimation. Pass ``null`` for options to use extension configuration.

Instance methods (recommended for TYPO3 DI)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

convert($input, ?EncodeOptions $options = null): string
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Convert arbitrary input into TOON format.

:Parameters:
   * ``$input`` (mixed) — JSON string, array, or object
   * ``$options`` (EncodeOptions|null) — Optional; null = extension config
:Returns: TOON string

encode($input, ?EncodeOptions $options = null): string
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Encode arbitrary input into TOON format (alias for convert).

decode(string $toon, ?DecodeOptions $options = null): mixed
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Decode a TOON string into an associative PHP array.

:Parameters:
   * ``$toon`` (string) — TOON-formatted string
   * ``$options`` (DecodeOptions|null) — Optional; null = extension config
:Returns: array
:Throws: ``RRP\T3Toon\Exception\ToonDecodeException`` when input is malformed

estimateTokens(string $toon): array
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Estimate the number of tokens in a TOON string (words/chars heuristic).

:Parameters: ``$toon`` (string) — TOON string
:Returns: array with keys ``words`` (int), ``chars`` (int), ``tokens_estimate`` (int)

Static methods (convenience, no DI)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

convertStatic($input, ?EncodeOptions $options = null): string
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Static equivalent of convert().

encodeStatic($input, ?EncodeOptions $options = null): string
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Static equivalent of encode().

decodeStatic(string $toon, ?DecodeOptions $options = null): mixed
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Static equivalent of decode(). Throws ``ToonDecodeException`` on malformed input.

estimateTokensStatic(string $toon): array
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Static equivalent of estimateTokens().

Example
~~~~~~~

.. code-block:: php

   use RRP\T3Toon\Service\Toon;
   use RRP\T3Toon\Domain\Model\EncodeOptions;
   use TYPO3\CMS\Core\Utility\GeneralUtility;

   $toon = GeneralUtility::makeInstance(Toon::class);
   $result = $toon->convert($data);
   $decoded = $toon->decode($result);
   $stats = $toon->estimateTokens($result);

   $compact = Toon::encodeStatic($data, EncodeOptions::compact());

Internal services
-----------------

ToonEncoder
~~~~~~~~~~~

Class: ``RRP\T3Toon\Service\ToonEncoder``

Handles conversion from PHP arrays/objects/JSON to TOON format.

:Method: ``toToon(mixed $input, ?EncodeOptions $options = null): string``

ToonDecoder
~~~~~~~~~~~

Class: ``RRP\T3Toon\Service\ToonDecoder``

Handles conversion from TOON format to PHP arrays.

:Method: ``fromToon(string $toon, ?DecodeOptions $options = null): mixed``
:Throws: ``RRP\T3Toon\Exception\ToonDecodeException`` when input is malformed

Utility classes
---------------

ToonHelper
~~~~~~~~~~

Class: ``RRP\T3Toon\Utility\ToonHelper``

:Method: ``static getConfig(): array`` — Get full extension configuration (including code defaults)
:Method: ``static getConfigMerged(array $overrides): array`` — Merge overrides with extension config

Domain models (options)
-----------------------

EncodeOptions
~~~~~~~~~~~~~

Class: ``RRP\T3Toon\Domain\Model\EncodeOptions``

Constructor: ``(?int $indent = null, ?string $delimiter = null, ?string $keyFolding = null, ?int $flattenDepth = null)``

Presets: ``default()``, ``compact()``, ``readable()``, ``tabular()``, ``folded()``. See :ref:`Options (EncodeOptions & DecodeOptions) <options-reference>`.

DecodeOptions
~~~~~~~~~~~~~

Class: ``RRP\T3Toon\Domain\Model\DecodeOptions``

Constructor: ``(?bool $strict = null, ?string $expandPaths = null)``

Presets: ``default()``, ``lenient()``, ``expanded()``. See :ref:`Options (EncodeOptions & DecodeOptions) <options-reference>`.
