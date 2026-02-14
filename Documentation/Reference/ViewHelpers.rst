.. _viewhelpers-reference:

Fluid ViewHelper Reference
===========================

T3Toon registers the Fluid namespace **toon** (``RRP\T3Toon\ViewHelpers``). Use these ViewHelpers in Fluid templates for encoding, decoding, and token estimation.

toon:encode
-----------

Encodes a value to TOON format.

**Arguments:**

* **value** (required, mixed) — Data to encode (array, object, scalar)
* **options** (optional, string) — Preset: ``default``, ``compact``, ``readable``, ``tabular``; default: ``default``

**Returns:** TOON string (no child content).

**Example:**

.. code-block:: html

   <toon:encode value="{data}" options="readable" />

toon:decode
-----------

Decodes a TOON string to a PHP array.

**Arguments:**

* **toon** (required, string) — TOON-formatted string to decode
* **as** (optional, string) — Variable name to assign the decoded array to; if set, child content is rendered and the variable is available inside
* **lenient** (optional, bool) — If true, do not coerce scalar types; default: false

**Returns:** If ``as`` is set, the rendered child content; otherwise JSON-encoded decoded array.

**Examples:**

.. code-block:: html

   <toon:decode toon="{toonString}" />
   <toon:decode toon="{toonString}" as="decoded">
       <f:for each="{decoded}" as="item">…</f:for>
   </toon:decode>
   <toon:decode toon="{toonString}" as="decoded" lenient="true">…</toon:decode>

toon:estimateTokens
-------------------

Estimates token count for a TOON string.

**Arguments:**

* **toon** (required, string) — TOON string to estimate
* **as** (optional, string) — Variable name to assign the result (keys: ``words``, ``chars``, ``tokens_estimate``); if set, child content is rendered

**Returns:** If ``as`` is set, the rendered child content; otherwise the string value of ``tokens_estimate``.

**Examples:**

.. code-block:: html

   <toon:estimateTokens toon="{toonString}" />
   <toon:estimateTokens toon="{toonString}" as="stats">{stats.tokens_estimate}</toon:estimateTokens>

Namespace registration
----------------------

The namespace is registered in ``ext_localconf.php``:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['toon'] = ['RRP\T3Toon\ViewHelpers'];

In Fluid, use ``xmlns:toon="http://typo3.org/ns/RRP/T3Toon/ViewHelpers"`` or the short name ``toon`` if your TYPO3 version resolves it.
