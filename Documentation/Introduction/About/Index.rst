.. _about:

About T3Toon
============

What is T3Toon?
---------------

**T3Toon** (Token-Optimized Object Notation) is a compact, human-readable, and token-efficient data format designed specifically for AI and LLM integrations in TYPO3 CMS.

It transforms large JSON or PHP arrays into a format that:

* Reduces token usage by up to 70% compared to JSON
* Maintains human readability (YAML-like structure)
* Preserves complete data structure and key order
* Supports bidirectional conversion (JSON ⇄ TOON)

Use Cases
---------

T3Toon is perfect for:

* **AI Prompt Engineering**: Compress structured data for LLMs (ChatGPT, Gemini, Claude, Mistral)
* **Token Optimization**: Reduce token usage and API costs
* **Data Preprocessing**: Streamline complex structured inputs
* **Logging & Debugging**: Store compact, readable structured logs
* **Database Storage**: Reduce JSON storage size while preserving structure
* **Developer Tools**: Perfect for previews and compact dashboards (e.g. TOON Playground backend module)

Why T3Toon?
-----------

When working with AI APIs, every token counts. Traditional JSON format includes:

* Redundant quotes around keys and string values
* Verbose syntax (braces, brackets, commas)
* Repeated structural markers

T3Toon eliminates this overhead while maintaining:

* Complete data fidelity
* Human readability
* Easy parsing and conversion
* Support for complex nested structures

Example Comparison
------------------

**JSON Format** (7.7 KB, ~1,930 tokens):

.. code-block:: json

   {
     "user": "ABC",
     "message": "Hello, how are you?",
     "tasks": [
       {"id": 1, "done": false},
       {"id": 2, "done": true}
     ]
   }

**TOON Format** (2.5 KB, ~640 tokens):

.. code-block:: text

   user: ABC
   message: Hello\, how are you?
   tasks:
     items[2]{done,id}:
       false,1
       true,2

**Result**: ~67% size reduction and ~66.8% fewer tokens while retaining complete data accuracy.

Format and specification
-------------------------

As of **v4.0.0**, this extension implements the official `TOON Specification v3.3 <https://github.com/toon-format/spec>`_ and passes the complete language-agnostic conformance suite (389/389 encode + decode fixtures). Output is interoperable with other spec-compliant TOON implementations. The spec engine (``RRP\T3Toon\Spec\Encoder`` / ``Decoder``) is dependency-free; the TYPO3 service layer is a thin adapter over it.

Tabular arrays use the real key (``users[2]{id,name}:``), primitive arrays are inline (``tags[3]: a,b,c``), and the encoder supports comma/tab/pipe delimiters and optional key folding.
