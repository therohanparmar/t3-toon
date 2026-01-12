.. _service-reference:

Service API Reference
=====================

Main Service Class: Toon
------------------------

The main service class provides static and instance methods for converting between
PHP arrays/JSON and TOON format.

Class: ``RRP\T3Toon\Service\Toon``

Static Methods
~~~~~~~~~~~~~~

convert()
^^^^^^^^^

Convert arbitrary input into TOON format.

:Signature: ``static string convert(mixed $input)``
:Parameters:
   * ``$input`` (mixed) - JSON string, array, or object
:Returns: ``string`` - TOON representation
:Throws: ``RRP\T3Toon\Exception\ToonEncodeException``

.. code-block:: php

   use RRP\T3Toon\Service\Toon;

   $data = ['key' => 'value'];
   $toon = Toon::convert($data);

encode()
^^^^^^^^

Encode arbitrary input into TOON format (alias for ``convert()``).

:Signature: ``static string encode(mixed $input)``
:Parameters:
   * ``$input`` (mixed) - JSON string, array, or object
:Returns: ``string`` - TOON representation
:Throws: ``RRP\T3Toon\Exception\ToonEncodeException``

.. code-block:: php

   use RRP\T3Toon\Service\Toon;

   $toon = Toon::encode($data);

decode()
^^^^^^^^

Decode a TOON string into an associative PHP array.

:Signature: ``static array decode(string $toon)``
:Parameters:
   * ``$toon`` (string) - TOON-formatted string
:Returns: ``array`` - Decoded PHP array
:Throws: ``RRP\T3Toon\Exception\ToonDecodeException``

.. code-block:: php

   use RRP\T3Toon\Service\Toon;

   $toon = "user: ABC\nactive: true";
   $data = Toon::decode($toon);

estimateTokens()
^^^^^^^^^^^^^^^^

Estimate the number of tokens in a TOON string.

:Signature: ``static array estimateTokens(string $toon)``
:Parameters:
   * ``$toon`` (string) - TOON-formatted string
:Returns: ``array`` - Array with keys:
   * ``words`` (int) - Word count
   * ``chars`` (int) - Character count
   * ``tokens_estimate`` (int) - Estimated token count

.. code-block:: php

   use RRP\T3Toon\Service\Toon;

   $stats = Toon::estimateTokens($toon);
   // Returns: ['words' => 20, 'chars' => 182, 'tokens_estimate' => 19]

Instance Methods
~~~~~~~~~~~~~~~~

The same methods are available as instance methods for dependency injection:

.. code-block:: php

   use RRP\T3Toon\Service\Toon;
   use TYPO3\CMS\Core\Utility\GeneralUtility;

   $toon = GeneralUtility::makeInstance(Toon::class);
   $result = $toon->convert($data);
   $decoded = $toon->decode($result);
   $stats = $toon->estimateTokens($result);

Internal Services
-----------------

ToonEncoder
~~~~~~~~~~~

Class: ``RRP\T3Toon\Service\ToonEncoder``

Handles conversion from PHP arrays/objects/JSON to TOON format.

:Method: ``toToon(mixed $input): string``

ToonDecoder
~~~~~~~~~~~

Class: ``RRP\T3Toon\Service\ToonDecoder``

Handles conversion from TOON format to PHP arrays.

:Method: ``fromToon(string $toon): array``


Utility Classes
---------------

ToonHelper
~~~~~~~~~~

Class: ``RRP\T3Toon\Utility\ToonHelper``

Provides configuration access and utility methods.

:Method: ``static array getConfig()`` - Get extension configuration
