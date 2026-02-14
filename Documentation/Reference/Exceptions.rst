.. _exceptions-reference:

Exception Reference
===================

ToonException
-------------

Class: ``RRP\T3Toon\Exception\ToonException``

Base exception for all TOON-related errors. Extends ``\Exception``.

Use this when catching any T3Toon exception in a single handler.

ToonDecodeException
------------------

Class: ``RRP\T3Toon\Exception\ToonDecodeException``

Thrown when TOON input cannot be decoded (malformed key:value format, invalid structure, etc.). Extends ``ToonException``.

Methods
~~~~~~~

getMessage(): string
   Full message; includes ``"Line N: ..."`` and snippet when available.

getLineNumber(): int
   One-based line number where the error occurred (0 if unknown).

getSnippet(): ?string
   The line content that caused the error, or null.

Example
~~~~~~~

.. code-block:: php

   use RRP\T3Toon\Exception\ToonDecodeException;

   try {
       $data = Toon::decodeStatic($input);
   } catch (ToonDecodeException $e) {
       $line = $e->getLineNumber();
       $snippet = $e->getSnippet();
       // Log or display to user
   }
