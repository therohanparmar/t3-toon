.. _configuration:

===============
Configuration
===============

T3Toon can be configured through the TYPO3 Extension Manager or by setting
extension configuration.

Access Configuration
--------------------

Navigate to :guilabel:`Admin Tools > Settings > Extension Configuration` and
select :guilabel:`T3Toon – Token-Efficient Data Format for TYPO3 AI`.

Configuration Options
----------------------

Escape Style
~~~~~~~~~~~~

:Type: string
:Default: ``backslash``
:Description: The style used for escaping special characters in TOON format.

Available options:
* ``backslash`` - Use backslash escaping (e.g., ``Hello\, world``)

Minimum Rows for Tabular Format
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

:Type: integer
:Default: ``2``
:Description: Minimum number of rows required before converting arrays to tabular format.

When arrays have at least this many items with the same structure, they are
converted to a more compact tabular format:

.. code-block:: text

   items[2]{id,done}:
     1,false
     2,true

Maximum Preview Items
~~~~~~~~~~~~~~~~~~~~~

:Type: integer
:Default: ``200``
:Description: Maximum number of items to preview in nested structures.

This prevents extremely large arrays from generating unreadable output.

Coerce Scalar Types
~~~~~~~~~~~~~~~~~~~

:Type: boolean
:Default: ``1`` (enabled)
:Description: Automatically convert string representations of booleans and numbers
to their proper types during decoding.

When enabled:
* ``"true"`` → ``true`` (boolean)
* ``"false"`` → ``false`` (boolean)
* ``"123"`` → ``123`` (integer)
* ``"45.67"`` → ``45.67`` (float)

Programmatic Configuration
--------------------------

You can also access configuration programmatically:

.. code-block:: php

   use RRP\T3Toon\Utility\ToonHelper;

   $config = ToonHelper::getConfig();
   // Returns:
   // [
   //     'escape_style' => 'backslash',
   //     'min_rows_to_tabular' => 2,
   //     'max_preview_items' => 200,
   //     'coerce_scalar_types' => true,
   // ]