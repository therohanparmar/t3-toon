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

Enabled
~~~~~~~

:Type: boolean
:Default: ``1`` (enabled)
:Description: Master switch for TOON optimization.

When this flag is on, ``Toon::encode()`` / ``Toon::convert()`` (and their static aliases
and global helpers) run the encoder normally.

When it is off, those methods short-circuit and return the input as-is — verbatim for
strings, ``json_encode($input)`` for arrays/objects. This lets you disable optimization
globally without code changes (e.g. while debugging an integration). Calls are still
recorded in the :ref:`TOON Logs module <backend-module-logs>` with ``settings_enabled = 0``
and ``optimization_pct = 0``, so you can audit which requests ran with optimization on
vs off.

``decode()`` is not affected by this flag.

Indent
~~~~~~

:Type: integer
:Default: ``2``
:Description: Spaces per indentation level when encoding. Must be at least 1 (spec default 2).

Delimiter
~~~~~~~~~

:Type: options (comma / tab / pipe)
:Default: ``comma``
:Description: Document delimiter used for inline arrays and tabular rows when encoding.

Key folding
~~~~~~~~~~~

:Type: options (off / safe)
:Default: ``off``
:Description: When ``safe``, chains of single-key objects are collapsed into dotted
   paths to reduce verbosity (spec §13.4):

.. code-block:: text

   a.b.c: 1

Flatten depth
~~~~~~~~~~~~~

:Type: integer
:Default: ``-1`` (unbounded)
:Description: Maximum number of segments to fold when key folding is ``safe``. Use
   ``-1`` for unbounded; values below 2 have no practical folding effect.

Strict decoding
~~~~~~~~~~~~~~~

:Type: boolean
:Default: ``1`` (enabled)
:Description: Enforce strict-mode validation when decoding — array length and
   row-width counts, indentation multiples, escape sequences, delimiter
   consistency, and blank lines inside arrays (spec §14). Disable for lenient
   parsing. Type coercion of unquoted tokens follows the spec in either mode.

Expand paths
~~~~~~~~~~~~

:Type: options (off / safe)
:Default: ``off``
:Description: When ``safe``, unquoted dotted keys are expanded into nested objects
   on decode, e.g. ``a.b.c`` becomes ``{a:{b:{c:...}}}`` (spec §13.4). ``off`` keeps
   dotted keys literal.

Show default example
~~~~~~~~~~~~~~~~~~~~

:Type: boolean
:Default: ``1`` (enabled)
:Description: When enabled, the :ref:`TOON Playground <backend-module-playground>` pre-fills a
   sample JSON input and its encoded TOON output on first load. Disable to open the
   Playground with empty fields.

JSON baseline
~~~~~~~~~~~~~

:Type: options (minified / pretty2 / pretty4 / tabs)
:Default: ``minified``
:Description: Default JSON formatting the :ref:`TOON Playground <backend-module-playground>`
   compares TOON output against when computing the size-reduction percentage.
   The options mirror the official TOON playground: Compact (``minified``),
   Pretty with 2 spaces, 4 spaces, or tabs. Compact is the fairest comparison
   (what you would actually send to an LLM); the pretty variants include
   indentation whitespace and therefore show larger savings. The optimization
   %% stored in usage logs always uses the compact baseline so logged values
   stay comparable over time.

Per-call options
----------------

You can override encoding and decoding behavior per call using :ref:`EncodeOptions <options-reference>` and :ref:`DecodeOptions <options-reference>` instead of changing global configuration. See :ref:`Options (EncodeOptions & DecodeOptions) <options-quick>`.

Programmatic access
-------------------

.. code-block:: php

   use RRP\T3Toon\Utility\ToonHelper;

   $config = ToonHelper::getConfig();
   // Returns the normalized config used by the spec engine:
   // [
   //     'enabled' => true,
   //     'indent' => 2,
   //     'delimiter' => ',',
   //     'key_folding' => 'off',
   //     'flatten_depth' => null,
   //     'strict' => true,
   //     'expand_paths' => 'off',
   //     'show_default_example' => true,
   //     'json_baseline' => 'minified', // or 'pretty2' | 'pretty4' | 'tabs'
   // ]

   $merged = ToonHelper::getConfigMerged(['indent' => 4]);
   // Merges overrides with extension config (e.g. for one-off encoding).
