.. _backend-module-quick:

=============================
Backend module (TOON Playground)
=============================

The extension provides a backend module **TOON Playground** under :guilabel:`Tools` in the TYPO3 backend. Use it to encode and decode TOON in the browser without writing code.

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

Use cases
---------

* **Quick testing** — Verify how your data looks in TOON format.
* **Token estimation** — Compare token counts for JSON vs TOON for the same data.
* **Debugging** — Decode TOON strings from logs or API responses to inspect structure.
* **Documentation and demos** — Show TOON format to colleagues or in training.

Requirements
-----------

* Backend user must have access to the **Tools** module group (standard for editors and admins).
* Extension EXT:rrp_t3toon must be installed and active.
