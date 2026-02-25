.. _configuration:

===============
Configuration 
===============

T3Toon can be configured through the TYPO3 Extension Manager (Install Tool) or by passing per-call options via :ref:`EncodeOptions <options-reference>` and :ref:`DecodeOptions <options-reference>`.

Access configuration
--------------------

Navigate to :guilabel:`Admin Tools > Settings > Extension Configuration` and
select :guilabel:`T3Toon – Token-Efficient Data Format for TYPO3 AI`.

Extension configuration options
--------------------------------

Escape style
~~~~~~~~~~~~

:Type: string
:Default: ``backslash``
:Description: The style used for escaping special characters in TOON format.

Available options:
* ``backslash`` — Use backslash escaping (e.g. ``Hello\, world``)

Minimum rows for tabular format
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

:Type: integer
:Default: ``2``
:Description: Minimum number of rows required before converting arrays to tabular format.

When arrays have at least this many items with the same structure, they are
converted to a more compact tabular format:

.. code-block:: text

   items[2]{id,done}:
     1,false
     2,true

Maximum preview items
~~~~~~~~~~~~~~~~~~~~

:Type: integer
:Default: ``200``
:Description: Maximum number of items to preview in nested structures.

This prevents extremely large arrays from generating unreadable output.

Coerce scalar types
~~~~~~~~~~~~~~~~~~

:Type: boolean
:Default: ``1`` (enabled)
:Description: Automatically convert string representations of booleans and numbers
to their proper types during decoding.

When enabled:
* ``"true"`` → ``true`` (boolean)
* ``"false"`` → ``false`` (boolean)
* ``"123"`` → ``123`` (integer)
* ``"45.67"`` → ``45.67`` (float)

Programmatic defaults (code)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The following options are used by the service with defaults in code when not set in Extension Configuration:

* **indent** (int, default ``2``) — Spaces per indentation level
* **delimiter** (string, default ``','``) — Field delimiter for tabular rows (comma or tab)
* **primitive_array_header** (bool, default ``false``) — Emit primitive arrays as ``[N]: v1,v2,v3`` (spec-style)

Per-call options
----------------

You can override encoding and decoding behavior per call using :ref:`EncodeOptions <options-reference>` and :ref:`DecodeOptions <options-reference>` instead of changing global configuration. See :ref:`Options (EncodeOptions & DecodeOptions) <options-quick>`.

Programmatic access
-------------------

.. code-block:: php

   use RRP\T3Toon\Utility\ToonHelper;

   $config = ToonHelper::getConfig();
   // Returns full config including defaults:
   // [
   //     'indent' => 2,
   //     'delimiter' => ',',
   //     'escape_style' => 'backslash',
   //     'min_rows_to_tabular' => 2,
   //     'max_preview_items' => 200,
   //     'coerce_scalar_types' => true,
   //     'primitive_array_header' => false,
   // ]

   $merged = ToonHelper::getConfigMerged(['indent' => 4]);
   // Merges overrides with extension config (e.g. for one-off encoding).
