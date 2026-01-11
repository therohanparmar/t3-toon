# TOON for TYPO3

### Compact · Token-Efficient · Human-Readable Data Format for AI Prompts & LLM Contexts

<p align="center">
  <img src="https://img.shields.io/badge/Version-1.0.0-blue" alt="Version 1.0.0">
  <img src="https://img.shields.io/github/license/sbsaga/toon" alt="License">
  <img src="https://img.shields.io/badge/TYPO3-12,13-orange" alt="TYPO3 12,13">
  <img src="https://img.shields.io/badge/PHP-8.0%2B-red" alt="PHP 8.0+">
  <img src="https://img.shields.io/badge/AI-Ready-success" alt="AI Ready">
</p>

---

## 📚 Table of Contents

1. [Overview](#-overview)
2. [Key Features](#-key-features)
3. [Benchmark & Analytics](#-real-world-benchmark)
4. [Installation](#-installation)
5. [Configuration](#-configuration)
6. [Usage](#-usage)
7. [Quick Benchmark Route](#-quick-benchmark-route)
8. [Analytics & Visualization](#-analytics--visualization)
10. [Integration Use Cases](#-integration-use-cases)
11. [Compatibility](#-compatibility)
12. [Compression Visualization](#-example-compression-visualization)
14. [License](#-license)

---

## ✨ Overview

**TOON for TYPO3** — also known as **Token-Optimized Object Notation** — is a **TYPO3-native AI data optimization library** that transforms large JSON or PHP arrays into a **compact, readable, and token-efficient format**.

It’s crafted for developers working with **ChatGPT, Gemini, Claude, Mistral, or OpenAI APIs**, helping you:
✅ Save tokens and reduce API costs
✅ Simplify complex prompt structures
✅ Improve AI response quality and context understanding
✅ Maintain human readability and reversibility

---

## 🚀 Key Features

| Feature                             | Description                                                 |
| ----------------------------------- | ----------------------------------------------------------- |
| 🔁 **Bidirectional Conversion**     | Convert JSON ⇄ TOON with ease                               |
| 🧩 **Readable & Compact**           | YAML-like structure, human-friendly format                  |
| 💰 **Token-Efficient**              | Save up to 70% tokens on every AI prompt                    |
| 🔒 **Preserves Key Order**          | Ensures deterministic data output                           |
| 📊 **Built-in Analytics**           | Measure token, byte, and compression performance            |
| 🌍 **AI & LLM Ready**               | Optimized for ChatGPT, Gemini, Claude, and Mistral models   |
| 🆕 **Complex Nested Array Support** | Fully supports deeply nested associative and indexed arrays |

---

## Complex & Nested Array Support

TOON now supports **deeply nested and mixed data structures**, including:

- Multi-level associative arrays
- Indexed collections inside objects
- Complex real-world structures like users, profiles, orders, metadata, and logs

This enhancement ensures that **no structural information is lost**, while still benefiting from TOON’s compact, token-efficient format.

### Example: Nested Data Conversion

```php
$data = [
    'user' => [
        'id' => 101,
        'active' => true,
        'roles' => ['admin', 'editor'],
        'profile' => [
            'age' => 32,
            'location' => [
                'city' => 'ABC',
                'country' => 'India',
            ],
        ],
    ],
    'orders' => [
        [
            'order_id' => 'ORD-1001',
            'amount' => 1998,
            'status' => 'paid',
        ],
    ],
];

echo \RRP\T3Toon\Service\Toon::convert($data);
```

This structure remains **human-readable, reversible, and compact**, even with deep nesting.

---

## 🧪 Real-World Benchmark

| Metric        | JSON  | TOON  | Reduction               |
| ------------- | ----- | ----- | ----------------------- |
| Size (bytes)  | 7,718 | 2,538 | **67.12% smaller**      |
| Tokens (est.) | 1,930 | 640   | **~66.8% fewer tokens** |

### 📈 Visual Comparison

```
JSON (7.7 KB)
██████████████████████████████████████████████████████████████████████████

TOON (2.5 KB)
█████████████████
```

💡 **TOON** reduces token load by _60–75%_, improving **AI efficiency**, **cost**, and **response quality**.

---

## ⚙️ Installation

```bash
composer require rrp/t3-toon
```

---

## ⚙️ Configuration


---

## 🧠 Usage

### ➤ Convert JSON → TOON

```php
$data = [
    'user' => 'ABC',
    'message' => 'Hello, how are you?',
    'tasks' => [
        ['id' => 1, 'done' => false],
        ['id' => 2, 'done' => true],
    ],
];

$converted = \RRP\T3Toon\Service\Toon::convert($data);
echo $converted;
```

**Output:**

```
user: ABC
message: Hello\, how are you?
tasks:
  items[2]{done,id}:
    false,1
    true,2
```

---

### ➤ Convert TOON → JSON

```php
$toon = <<<TOON
user: ABC
tasks:
  items[2]{id,done}:
    1,false
    2,true
TOON;

$json = \RRP\T3Toon\Service\Toon::decode($toon);
print_r($json);
```

---

### ➤ Estimate Tokens

```php
$stats = Toon::estimateTokens($converted);
print_r($stats);
```

**Output:**

```json
{
  "words": 20,
  "chars": 182,
  "tokens_estimate": 19
}
```

---

## 📊 Analytics & Visualization

| Metric              | Description           | Example |
| ------------------- | --------------------- | ------- |
| `json_size_bytes`   | Original JSON size    | 7,718   |
| `toon_size_bytes`   | Optimized TOON size   | 2,538   |
| `saving_percent`    | Space saved           | 67.12%  |
| `tokens_estimate`   | Estimated token count | 640     |
| `compression_ratio` | Ratio (TOON/JSON)     | 0.33    |

🧠 **Visual Graph (Efficiency Comparison)**

```
| JSON: ██████████████████████████████████████████████████████████ 100%
| TOON: ████████████ 33%
```

---

## 🧩 Integration Use Cases

| Use Case                             | Benefit                                             |
| ------------------------------------ | --------------------------------------------------- |
| 🤖 **AI Prompt Engineering**         | Compress structured data for LLMs                   |
| 📉 **Token Optimization**            | Reduce token usage and API costs                    |
| 🧠 **Data Preprocessing**            | Streamline complex structured inputs                |
| 🧾 **Logging & Debugging**           | Store compact, readable structured logs             |
| 🗄️ **Database Storage Optimization** | Reduce JSON storage size while preserving structure |
| 🔍 **Developer Tools**               | Perfect for previews and compact dashboards         |

---

## 🧰 Compatibility

| TYPO3       | PHP | Package Version |
| ----------- | --- | --------------- |
| 12.x – 13.x | ≥ 8 | v1.0.0          |

---

## 📉 Example Compression Visualization

```
JSON (7.7 KB)
██████████████████████████████████████████████████████████████████████████

TOON (2.5 KB)
█████████████████
```

🧠 **~67% size reduction** while retaining complete data accuracy.

---

## 💡 Contribution

Contributions are highly encouraged!

- Fork the repository
- Create a new feature branch
- Commit & push improvements
- Submit a Pull Request 🎉

---

## 📜 License

Licensed under the **MIT License** — free for both commercial and personal use.

---

<p align="center">
  <b>Made with 🧡 for TYPO3 community</b>
</p>
