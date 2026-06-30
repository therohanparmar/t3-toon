.. _fluid-viewhelpers-quick:

=====================
Fluid ViewHelpers
=====================

T3Toon registers the Fluid namespace **toon** so you can encode, decode, and estimate tokens directly in Fluid templates. Add the namespace and use the ViewHelpers as shown below.

Namespace
---------

In your Fluid template, add:

.. code-block:: html

   xmlns:toon="http://typo3.org/ns/RRP/T3Toon/ViewHelpers"

Or ensure the namespace is available (registered in ext_localconf as ``toon``).

toon:encode
-----------

Encodes a value to TOON format.

**Arguments:**

* **value** (required) — Data to encode (array, object, scalar)
* **options** (optional) — Preset: ``default``, ``compact``, ``readable``, ``tabular``; default: ``default``

**Examples:**

.. code-block:: html

   <toon:encode value="{data}" />
   <toon:encode value="{data}" options="compact" />
   <toon:encode value="{data}" options="readable" />
   <toon:encode value="{data}" options="tabular" />

toon:decode
-----------

Decodes a TOON string to a PHP array. You can assign the result to a variable and use it in child content (e.g. ``f:for``), or output JSON.

**Arguments:**

* **toon** (required) — TOON-formatted string to decode
* **as** (optional) — Variable name to assign the decoded array to; if set, child content is rendered and the variable is available inside
* **lenient** (optional) — If ``true``, decode in lenient (non-strict) mode; default: ``false``

**Examples:**

.. code-block:: html

   <!-- Output decoded as JSON -->
   <toon:decode toon="{toonString}" />

   <!-- Assign to variable and use in loop -->
   <toon:decode toon="{toonString}" as="decoded">
       <f:for each="{decoded}" as="item">
           {item.name}
       </f:for>
   </toon:decode>

   <!-- Lenient decode (relaxed strict-mode validation) -->
   <toon:decode toon="{toonString}" as="decoded" lenient="true">
       ...
   </toon:decode>

toon:estimateTokens
-------------------

Estimates token count for a TOON string. Outputs ``tokens_estimate`` by default, or assigns the full stats array to a variable.

**Arguments:**

* **toon** (required) — TOON string to estimate
* **as** (optional) — Variable name to assign the result (keys: ``words``, ``chars``, ``tokens_estimate``); if set, child content is rendered

**Examples:**

.. code-block:: html

   <!-- Output estimated token count -->
   <toon:estimateTokens toon="{toonString}" />

   <!-- Use full stats in child content -->
   <toon:estimateTokens toon="{toonString}" as="stats">
       Words: {stats.words}, Chars: {stats.chars}, Tokens: {stats.tokens_estimate}
   </toon:estimateTokens>

Full template example
----------------------

.. code-block:: html

   <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
         xmlns:toon="http://typo3.org/ns/RRP/T3Toon/ViewHelpers"
         data-namespace-typo3-fluid="true">
     <toon:encode value="{myData}" options="readable" />
     <toon:decode toon="{toonText}" as="decoded">
       <f:for each="{decoded}" as="item">…</f:for>
     </toon:decode>
     <toon:estimateTokens toon="{toonText}" as="stats">{stats.tokens_estimate}</toon:estimateTokens>
   </html>
