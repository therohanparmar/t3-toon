.. _error-handling-quick:

===============
Error handling
===============

When decoding malformed TOON input, T3Toon throws ``RRP\T3Toon\Exception\ToonDecodeException`` (a subclass of ``RRP\T3Toon\Exception\ToonException``). This exception includes the line number and a snippet of the problematic line to simplify debugging.

Exception hierarchy
-------------------

* **ToonException** — Base exception for all TOON-related errors
* **ToonDecodeException** — Thrown when TOON input cannot be parsed (invalid key:value format, malformed structure, etc.)

ToonDecodeException API
------------------------

* **getMessage()** — Full message, including ``"Line N: ..."`` and snippet when available
* **getLineNumber(): int** — One-based line number where the error occurred (0 if unknown)
* **getSnippet(): ?string** — The line content that caused the error (or null)

Example
-------

.. code-block:: php

   use RRP\T3Toon\Exception\ToonDecodeException;
   use RRP\T3Toon\Service\Toon;

   $input = "user: ABC\ninvalid-line-without-colon\nkey: value";

   try {
       $data = Toon::decodeStatic($input);
   } catch (ToonDecodeException $e) {
       echo $e->getMessage();       // e.g. "Line 2: Invalid key:value format"
       echo $e->getLineNumber();    // 2
       echo $e->getSnippet();        // "invalid-line-without-colon"
   }

Best practices
--------------

* Always catch ``ToonDecodeException`` when decoding user-provided or external TOON input.
* Use ``getLineNumber()`` and ``getSnippet()`` in logs or user-facing error messages to help fix invalid data.
* For other TOON-related errors (e.g. invalid options), catch the base ``ToonException`` if you need a single handler.
