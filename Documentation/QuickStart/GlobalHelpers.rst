.. _global-helpers-quick:

=================
Global helpers
=================

After the extension is loaded, the following global functions are available. They wrap the static Toon service methods and require no ``use`` statement. Use them only when TYPO3 bootstrap is available (e.g. in TYPO3 context).

Available functions
-------------------

toon()
~~~~~~

Encode a value to TOON format.

:Signature: ``toon(mixed $value, ?EncodeOptions $options = null): string``
:Parameters:
   * ``$value`` — Data to encode (array, object, scalar, JSON string)
   * ``$options`` — Optional; ``null`` = extension config
:Returns: TOON string

.. code-block:: php

   $toon = toon(['name' => 'TOON']);
   $compact = toon($data, \RRP\T3Toon\Domain\Model\EncodeOptions::compact());

toon_decode()
~~~~~~~~~~~~~

Decode a TOON string to a PHP array.

:Signature: ``toon_decode(string $toon, ?DecodeOptions $options = null): array``
:Throws: ``RRP\T3Toon\Exception\ToonDecodeException`` when input is malformed

.. code-block:: php

   $data = toon_decode($toonString);

toon_compact()
~~~~~~~~~~~~~~

Encode to TOON with compact options (indent 0, comma delimiter).

:Signature: ``toon_compact(mixed $value): string``

.. code-block:: php

   $toon = toon_compact($data);

toon_readable()
~~~~~~~~~~~~~~~

Encode to TOON with readable options (indent 4).

:Signature: ``toon_readable(mixed $value): string``

.. code-block:: php

   $toon = toon_readable($data);

toon_decode_lenient()
~~~~~~~~~~~~~~~~~~~~~

Decode TOON without coercing scalar types (keep ``"true"``, ``"42"`` as strings).

:Signature: ``toon_decode_lenient(string $toon): array``
:Throws: ``RRP\T3Toon\Exception\ToonDecodeException`` when input is malformed

.. code-block:: php

   $data = toon_decode_lenient($toonString);

toon_estimate_tokens()
~~~~~~~~~~~~~~~~~~~~~~

Estimate token count for a TOON string (words/chars heuristic).

:Signature: ``toon_estimate_tokens(string $toon): array``
:Returns: Array with keys ``words`` (int), ``chars`` (int), ``tokens_estimate`` (int)

.. code-block:: php

   $stats = toon_estimate_tokens($toonString);
   echo $stats['tokens_estimate'];

When to use
-----------

* **Quick scripts or hooks** where you prefer not to inject the Toon service.
* **Legacy or non-Extbase code** where global functions are acceptable.

For dependency injection and testability, prefer the **Toon service** (instance or static) in PHP classes.
