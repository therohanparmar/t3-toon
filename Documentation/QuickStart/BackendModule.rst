.. _backend-module-quick:

================
Backend modules
================

The extension ships with a single backend module under :guilabel:`Tools`:

* **TOON Playground** — interactive encode/decode in the browser.

It has two screens. The **TOON Logs** screen — a list of every recorded
``encode`` / ``convert`` call, with filters and bulk delete — is reached from a
:guilabel:`View Logs` button inside the Playground (and a :guilabel:`Back to
Playground` button returns from it). There is no separate menu entry for the logs.

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
* **Encode options** — Delimiter (comma / tab / pipe), indent, key folding, flatten depth, and JSON baseline (aligned with the `official TOON playground <https://toonformat.dev/playground.html>`_). Defaults come from the extension configuration.
* **JSON baseline** — The JSON formatting the size-reduction figure is measured against, mirroring the official playground: Pretty (2 spaces), Pretty (4 spaces), Pretty (tabs), or Compact (default — the fairest comparison). The default is configurable via the ``json_baseline`` extension setting.
* **Load example** — **Hikes** (official mixed-structure demo) or **TYPO3** (headless site sample).
* **Encode to TOON** — Converts pasted JSON using the selected encode options.
* **Decode from TOON** — Converts pasted TOON to pretty-printed JSON (strict by default).
* **Lenient decode** — Optional checkbox to relax strict-mode validation (counts, indentation, blank lines). Pre-checked when the ``strict`` extension setting is disabled.
* **Encode (compact)** — Same as encode with compact preset (indent 2, comma).
* **Output area** — Result plus character counts; after encode, the size reduction vs the selected JSON baseline is shown when applicable.
* **Estimated tokens** — Approximate token count for the result when available.
* **Error messages** — Invalid JSON or malformed TOON surfaces a clear error.
* **Default example** — On first load the **Hikes** sample and its TOON output are pre-filled. Disable via ``show_default_example``; **Clear all** empties both fields.
* **Official playground** — Footer link to `toonformat.dev <https://toonformat.dev/playground.html>`_.

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

From **Tools → TOON Playground**, click the :guilabel:`View Logs` button in the
header. Use :guilabel:`Back to Playground` to return.

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
