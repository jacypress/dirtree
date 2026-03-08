# ├── DirTree

A single-file PHP + JavaScript utility that generates clean ASCII folder structure trees directly in your browser. Perfect for documentation, bug reports, and AI prompts.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)](https://php.net)

## Features
- **Safe by design** – Path traversal protection, only shows current directory contents
- **Smart filtering** – Auto-excludes `.git`, `node_modules`, `.DS_Store`, and itself
- **Clean output** – Folders first, alphabetical sorting, trailing slashes on directories
- **One-click copy** – Copies formatted tree with visual feedback
- **Dark theme** – Easy on the eyes, monospace output for precision
- **Zero config** – Just drop and run

## Usage
1. Drop `dirtree.php` into any project folder
2. Open it in your browser:  
   `http://localhost/your-project/dirtree.php`
3. Click **"Copy"** → paste the tree anywhere

## Example Output
```
my-project/
├── src/
│   ├── components/
│   │   └── Button.js
│   ├── utils/
│   │   ├── helpers.js
│   │   └── constants.js
│   └── index.js
├── public/
│   ├── index.html
│   └── favicon.ico
├── package.json
└── README.md
```

Perfect for:
- READMEs & documentation
- Bug reports & tickets
- AI prompts & chats
- Team communication
- Project planning

## How It Works
- PHP scans the directory (secure, read-only)
- Returns JSON to the frontend
- JavaScript renders the ASCII tree

## File Structure
```
dirtree.php
├── PHP backend (directory scanner)
└── HTML/JS frontend (renderer + copy)
```

## License
MIT © [J](https://jacypress.com) – free for any use, private or commercial.

---
*Built for developers who love clean documentation.*
