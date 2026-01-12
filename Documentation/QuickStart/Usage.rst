.. _quickUsage:

==========
Quick usage
==========

Basic Encoding (JSON → TOON)
-----------------------------

Convert PHP arrays or JSON strings to TOON format:

.. code-block:: php

   use RRP\T3Toon\Service\Toon;

   $data = [
       'user' => 'ABC',
       'message' => 'Hello, how are you?',
       'tasks' => [
           ['id' => 1, 'done' => false],
           ['id' => 2, 'done' => true],
       ],
   ];

   $toon = Toon::convert($data);
   echo $toon;

**Output:**

.. code-block:: text

   user: ABC
   message: Hello\, how are you?
   tasks:
     items[2]{done,id}:
       false,1
       true,2

Basic Decoding (TOON → JSON)
-----------------------------

Convert TOON format back to PHP arrays:

.. code-block:: php

   use RRP\T3Toon\Service\Toon;

   $toon = <<<TOON
   user: ABC
   tasks:
     items[2]{id,done}:
       1,false
       2,true
   TOON;

   $data = Toon::decode($toon);
   print_r($data);

**Output:**

.. code-block:: php

   Array
   (
       [user] => ABC
       [tasks] => Array
           (
               [0] => Array
                   (
                       [id] => 1
                       [done] => false
                   )
               [1] => Array
                   (
                       [id] => 2
                       [done] => true
                   )
           )
   )

Token Estimation
----------------

Estimate the number of tokens in a TOON string:

.. code-block:: php

   use RRP\T3Toon\Service\Toon;

   $toon = Toon::convert($data);
   $stats = Toon::estimateTokens($toon);
   print_r($stats);

**Output:**

.. code-block:: php

   Array
   (
       [words] => 20
       [chars] => 182
       [tokens_estimate] => 19
   )

Using Dependency Injection
---------------------------

For better testability and TYPO3 integration, use dependency injection:

.. code-block:: php

   use RRP\T3Toon\Service\Toon;
   use TYPO3\CMS\Core\Utility\GeneralUtility;

   $toonService = GeneralUtility::makeInstance(Toon::class);
   $toon = $toonService->convert($data);
   $decoded = $toonService->decode($toon);

Working with JSON Strings
--------------------------

T3Toon automatically detects and handles JSON strings:

.. code-block:: php

   use RRP\T3Toon\Service\Toon;

   $json = '{"user":"ABC","active":true}';
   $toon = Toon::convert($json);
   echo $toon;

**Output:**

.. code-block:: text

   user: ABC
   active: true

Complex Nested Structures
--------------------------

T3Toon handles deeply nested structures:

.. code-block:: php

   use RRP\T3Toon\Service\Toon;

   $data = [
       'user' => [
           'id' => 101,
           'active' => true,
           'roles' => ['admin', 'editor'],
           'profile' => [
               'age' => 32,
               'location' => [
                   'city' => 'ABC',
                   'country' => 'India',
               ],
           ],
       ],
       'orders' => [
           [
               'order_id' => 'ORD-1001',
               'amount' => 1998,
               'status' => 'paid',
           ],
       ],
   ];

   $toon = Toon::convert($data);
   echo $toon;

This structure remains **human-readable, reversible, and compact**, even with deep nesting.
