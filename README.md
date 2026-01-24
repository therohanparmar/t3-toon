# TOON for TYPO3

### Token-Optimized Object Notation for AI & LLM Workflows

<p align="center">
  <img src="https://img.shields.io/badge/Version-1.1.0-blue" alt="Version 1.1.0">
  <img src="https://img.shields.io/github/license/sbsaga/toon" alt="License">
  <img src="https://img.shields.io/badge/TYPO3-11,12,13,14-orange" alt="TYPO3 11-14">
  <img src="https://img.shields.io/badge/PHP-7.4%2B-red" alt="PHP 7.4+">
</p>

---

## ✨ What is TOON?

**TOON (Token-Optimized Object Notation)** is a **TYPO3-native data format** that transforms large JSON or PHP arrays into a **compact, human-readable, and token-efficient structure**, purpose-built for **AI prompts and LLM contexts**.

It helps you:

- 🔻 Reduce token usage (up to 60–75%)
- 💰 Lower AI API costs
- 🧠 Improve prompt clarity and context understanding
- 🔁 Convert data seamlessly between **JSON ⇄ TOON**

---

## 🚀 Key Features

- 🔁 Bidirectional conversion (JSON ⇄ TOON)
- 🧩 Compact, YAML-like and human-readable format
- 💰 Significant token and size reduction
- 📊 Built-in analytics and token estimation
- 🧠 Optimized for ChatGPT, Gemini, Claude, and Mistral
- 🆕 Supports deeply nested and complex data structures
- 🔒 Preserves key order and data integrity

---

## 📦 Installation

### ➤ TYPO3 Extension Repository (TER)

Install via the TYPO3 backend or directly from TER:

🔗 https://extensions.typo3.org/extension/rrp_t3toon

---

### ➤ Composer (Packagist)

Recommended for Composer-based TYPO3 projects: 🔗 https://packagist.org/packages/rrp/t3-toon

```bash
composer require rrp/t3-toon
```

## 🧠 Quick Usage Example

    use RRP\T3Toon\Service\Toon;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $data = [
        'user' => 'ABC',
        'tasks' => [
            ['id' => 1, 'done' => false],
            ['id' => 2, 'done' => true],
        ],
    ];

    echo GeneralUtility::makeInstance(Toon::class)->convert($data);

**Output (TOON):**

    user: ABC
    tasks:
      items[2]{id,done}:
        1,false
        2,true

---

## 📚 Documentation

Full documentation, configuration, and advanced usage are available here:

🔗 https://docs.typo3.org/p/rrp/t3-toon/main/en-us/

---

## 🧩 Use Cases

- 🤖 AI prompt engineering
- 📉 Token and cost optimization
- 🧠 Structured data preprocessing
- 🧾 Compact logging and debugging
- 🗄️ Optimized JSON storage
- 🔍 Developer tooling and previews

---

## 🧰 Compatibility

| TYPO3       | PHP   | Extension Version |
| ----------- | ----- | ----------------- |
| 11.x – 14.x | ≥ 7.4 | v1.2.0            |

---

## 👨‍💻 Authors

- **[Rohan Parmar](https://www.linkedin.com/in/rohanrparmar)**
- **[Himanshu Ramavat](https://www.linkedin.com/in/himanshu-ramavat/)**

---

## 💡 Contributing

Contributions are welcome and appreciated ❤️

- Fork the repository
- Create a feature branch
- Commit your changes
- Submit a Pull Request

---

## 📜 License

Licensed under the MIT License — free for personal and commercial use.

---

<p align="center">
  <b>Made with 🧡 for the TYPO3 Developer</b>
</p>
