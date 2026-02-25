.. _quickUsage:

==========
Quick usage
==========

This section covers the main ways to use T3Toon: the Toon service (static and instance), per-call options, token estimation, and error handling. For global helpers, Fluid ViewHelpers, and the backend module, see the dedicated sections below.

Basic encoding (JSON → TOON)
-----------------------------

Convert PHP arrays or JSON strings to TOON format.

**Instance API (recommended in TYPO3 for dependency injection):**

.. code-block:: php

   use RRP\T3Toon\Service\Toon;
   use TYPO3\CMS\Core\Utility\GeneralUtility;

   $data = [
       'user' => 'ABC',
       'message' => 'Hello, how are you?',
       'tasks' => [
           ['id' => 1, 'done' => false],
           ['id' => 2, 'done' => true],
       ],
   ];

   $toon = GeneralUtility::makeInstance(Toon::class)->convert($data);
   echo $toon;

**Static API (convenience, no DI):**

.. code-block:: php

   use RRP\T3Toon\Service\Toon;

   $toon = Toon::convertStatic($data);
   // or
   $toon = Toon::encodeStatic($data);

**Output:**

.. code-block:: text

   user: ABC
   message: Hello\, how are you?
   tasks:
     items[2]{done,id}:
       false,1
       true,2

Basic decoding (TOON → PHP)
----------------------------

Convert TOON format back to PHP arrays.

.. code-block:: php

   use RRP\T3Toon\Service\Toon;
   use TYPO3\CMS\Core\Utility\GeneralUtility;

   $toon = <<<TOON
   user: ABC
   tasks:
     items[2]{id,done}:
       1,false
       2,true
   TOON;

   $data = GeneralUtility::makeInstance(Toon::class)->decode($toon);
   // or: $data = Toon::decodeStatic($toon);
   print_r($data);

**Output:**

.. code-block:: php

   Array
   (
       [user] => ABC
       [tasks] => Array
           (
               [0] => Array ( [id] => 1, [done] => false )
               [1] => Array ( [id] => 2, [done] => true )
           )
   )

Per-call options (EncodeOptions / DecodeOptions)
------------------------------------------------

Override encoding or decoding behavior for a single call without changing extension configuration.

**Encoding presets:**

.. code-block:: php

   use RRP\T3Toon\Domain\Model\EncodeOptions;
   use RRP\T3Toon\Service\Toon;

   $compact = Toon::encodeStatic($data, EncodeOptions::compact());   // indent 0
   $readable = Toon::encodeStatic($data, EncodeOptions::readable()); // indent 4
   $tabular = Toon::encodeStatic($data, EncodeOptions::tabular());    // tab delimiter

**Decoding (lenient = no scalar coercion):**

.. code-block:: php

   use RRP\T3Toon\Domain\Model\DecodeOptions;
   use RRP\T3Toon\Service\Toon;

   $data = Toon::decodeStatic($toon);                              // use extension config
   $strings = Toon::decodeStatic($toon, DecodeOptions::lenient());  // keep "true", "42" as strings

See :ref:`Options (EncodeOptions & DecodeOptions) <options-quick>` for full details.

Token estimation
-----------------

Estimate the number of tokens in a TOON string (heuristic based on words and characters).

.. code-block:: php

   use RRP\T3Toon\Service\Toon;

   $toonService = GeneralUtility::makeInstance(Toon::class);
   $toon = $toonService->convert($data);
   $stats = $toonService->estimateTokens($toon);
   // or: $stats = Toon::estimateTokensStatic($toon);

   print_r($stats);

**Output:**

.. code-block:: php

   Array
   (
       [words] => 20
       [chars] => 182
       [tokens_estimate] => 19
   )

Error handling
---------------

Decoding malformed TOON throws ``RRP\T3Toon\Exception\ToonDecodeException`` with line number and snippet. See :ref:`Error handling <error-handling-quick>`.

.. code-block:: php

   use RRP\T3Toon\Exception\ToonDecodeException;
   use RRP\T3Toon\Service\Toon;

   try {
       $data = Toon::decodeStatic($input);
   } catch (ToonDecodeException $e) {
       $line = $e->getLineNumber();
       $snippet = $e->getSnippet();
       // Handle error
   }

Working with JSON strings
-------------------------

The encoder accepts JSON strings; they are decoded to arrays and then converted to TOON.

.. code-block:: php

   $json = '{"user":"ABC","active":true}';
   $toon = Toon::encodeStatic($json);
   echo $toon;

**Output:**

.. code-block:: text

   user: ABC
   active: true

Complex nested structures
--------------------------

T3Toon handles deeply nested structures. The output remains human-readable, reversible, and compact.

.. code-block:: php

   $data = [
       'user' => [
           'id' => 101,
           'roles' => ['admin', 'editor'],
           'profile' => [
               'location' => ['city' => 'ABC', 'country' => 'India'],
           ],
       ],
       'orders' => [ ['order_id' => 'ORD-1001', 'amount' => 1998] ],
   ];
   $toon = Toon::encodeStatic($data);

Further reading
---------------

* :ref:`Options (EncodeOptions & DecodeOptions) <options-quick>`
* :ref:`Error handling <error-handling-quick>`
* :ref:`Global helpers <global-helpers-quick>`
* :ref:`Fluid ViewHelpers <fluid-viewhelpers-quick>`
* :ref:`Backend module (TOON Playground) <backend-module-quick>`
