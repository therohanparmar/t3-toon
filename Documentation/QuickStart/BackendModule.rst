.. _backend-module-quick:

================
Backend modules
================

The extension ships with two backend modules under :guilabel:`Tools`:

* **TOON Playground** — interactive encode/decode in the browser.
* **TOON Logs** — list of every recorded ``encode`` / ``convert`` call, with filters and bulk delete.

.. _backend-module-playground:

TOON Playground
===============

Use it to encode and decode TOON in the browser without writing code.

Access
------

In the TYPO3 backend menu, go to **Tools → TOON Playground**.

Features
--------

* **Input area** — Paste JSON (for encoding) or TOON text (for decoding).
* **Encode to TOON** — Converts the pasted JSON to TOON format using default encoding options.
* **Decode from TOON** — Converts the pasted TOON text to JSON (pretty-printed).
* **Encode (compact)** — Converts the pasted JSON to TOON using compact options (indent 0).
* **Output area** — Shows the result (TOON or JSON) after an action.
* **Estimated tokens** — Displays an approximate token count for the result when available.
* **Error messages** — If input is invalid (e.g. invalid JSON for encode, malformed TOON for decode), an error message is shown.

Workflow
--------

#. Paste **JSON** into the input field and click **Encode to TOON** or **Encode (compact)** to get TOON output.
#. Or paste **TOON** into the input field and click **Decode from TOON** to get JSON.
#. Check the output area and the estimated tokens. Fix any errors reported if the input was invalid.

.. _backend-module-logs:

TOON Logs
=========

Every successful ``encode`` / ``convert`` call (from anywhere — Playground, ViewHelpers,
global helpers, programmatic API, scheduler tasks, CLI commands) is recorded in the
``tx_rrpt3toon_log`` table. The Logs module surfaces that data for auditing and debugging.

Access
------

In the TYPO3 backend menu, go to **Tools → TOON Logs**.

What gets recorded
------------------

Each row stores:

* ``uid`` and ``crdate`` (timestamp of the call)
* ``input_size`` and ``output_size`` (bytes)
* ``optimization_pct`` (compression ratio, computed once on insert)
* ``settings_enabled`` (snapshot of the ``enabled`` extension config flag at call time)

Failures are not logged — the exception propagates unchanged so callers behave identically
to before. The log writer is wrapped in ``try/catch`` so a missing table or DB hiccup
never breaks the Toon service.

Features
--------

* **Filters** — date range, optimization status (enabled / disabled / all), minimum
  optimization %, page size (25 / 50 / 100).
* **Per-row delete** — trash icon in each row → confirmation modal → delete one entry.
* **Bulk delete** — header checkbox or individual checkboxes select rows; a bar shows
  the count and a "Delete selected (N)" action.
* **Optimization badge** — green when bytes were saved, neutral when the call ran in
  passthrough mode (extension setting ``enabled = 0``).
* **Sliding-window pagination** — newest entries first, filter state preserved across
  page jumps.

Update database scheme
----------------------

After installing or updating the extension, run the database compare so the
``tx_rrpt3toon_log`` table is created:

.. code-block:: bash

   vendor/bin/typo3 database:updateschema "*.add,*.change"

Or via :guilabel:`Admin Tools > Maintenance > Analyze database > Create all`.

Development seed command
------------------------

For local development and demos, insert random log entries via the bundled CLI command:

.. code-block:: bash

   vendor/bin/typo3 t3toon:seed-logs 500       # 500 random rows over the last 30 days
   vendor/bin/typo3 t3toon:seed-logs 100 7     # 100 rows over the last 7 days

The seeder generates a realistic mix: ~80 % of rows with ``settings_enabled = 1`` and
25–75 % compression; ~20 % passthrough rows with ``optimization_pct = 0``.

Requirements
------------

* Backend user must have access to the **Tools** module group (standard for editors and admins).
* Extension EXT:rrp_t3toon must be installed and active.
* The ``tx_rrpt3toon_log`` table must exist — run the schema migration after install.
