<?php
// Handle AJAX request first – no output before this
if (isset($_GET['action']) && $_GET['action'] === 'getStructure') {
    header('Content-Type: application/json');
    // Safe root resolution – prevent traversal outside script dir
    $baseDir = realpath('.');
    $requestedRoot = isset($_GET['root']) ? trim($_GET['root'], '/\\') : '.';
    $fullRoot = realpath($requestedRoot === '.' ? $baseDir : $baseDir . '/' . $requestedRoot);
    if ($fullRoot === false || strpos($fullRoot, $baseDir) !== 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid or inaccessible root path']);
        exit;
    }
    function getDirectoryTree($dir, $excludePatterns = [], $maxDepth = 20, $currentDepth = 0) {
        if ($currentDepth > $maxDepth) return [];
        $result = [];
        if (!is_dir($dir) || !is_readable($dir)) return $result;
        $items = @scandir($dir) ?: [];
        natcasesort($items); // Consistent alphabetical order
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            $relative = substr($path, strlen(realpath('.')) + 1);
            $relative = str_replace('\\', '/', $relative); // Normalize slashes
            // Precise exclusion (full segment match)
            foreach ($excludePatterns as $pattern) {
                if (preg_match($pattern, $relative) || preg_match($pattern, $item)) {
                    continue 2; // Skip this item
                }
            }
            if (is_dir($path)) {
                $result[] = $relative . '/';
                $sub = getDirectoryTree($path, $excludePatterns, $maxDepth, $currentDepth + 1);
                $result = array_merge($result, $sub);
            } else {
                $result[] = $relative;
            }
        }
        return $result;
    }
    // Common exclusions – regex for precision
    $excludePatterns = [
        '#(^|/)\.git($|/)#',
        '#(^|/)(node_modules|\.DS_Store|Thumbs\.db)($|/)#',
    ];
    // Dynamically exclude the current script file itself
    $self = basename(__FILE__);
    $excludePatterns[] = '#(^|/)' . preg_quote($self, '#') . '$#';
    $files = getDirectoryTree($fullRoot, $excludePatterns);
    echo json_encode([
        'success' => true,
        'files' => $files,
        'count' => count($files),
        'path' => $fullRoot,
        'root' => $requestedRoot === '.' ? basename($baseDir) : $requestedRoot
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='4' fill='%231c1c1c'/><path d='M6 8 L6 24' stroke='%23ffffff' stroke-width='2' stroke-linecap='square'/><path d='M6 16 L26 16' stroke='%23ffffff' stroke-width='2' stroke-linecap='square'/></svg>">
    <title>DirTree – Folder Structure Generator</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }

        ::selection { background-color: rgb(0 96 255); color: white; }

        body {
            font-family: 'Monaco', 'Menlo', 'Consolas', monospace;
            background:rgb(28 28 28); color:rgb(242 242 242);
            line-height:1.4;
        }

        /* Main responsive container */
        .main-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        /* Right sidebar (controls + info) */
        .sidebar {
            padding: 2rem 2.5rem;
            min-width: 0; 
            width: 100%;
        }

        /* Left container */
        .structure-container {
            background: transparent;
            border-radius: 8px;
            padding: 2.5rem;
            min-width: 0; 
            width: 100%;
            box-sizing: border-box;
        }

        /* ASCII tree output */
        .structure-output {
            font-size: 1rem;
            white-space: pre;
            line-height: 1.6;
            min-height: 200px;
            user-select: text;
            font-family: 'Monaco', 'Menlo', 'Consolas', monospace;
            overflow-x: auto;
            display: block;
            width: fit-content;
            min-width: 100%;
            box-sizing: border-box;
        }

        @media (min-width: 900px) {
            .main-container {
                grid-template-columns: minmax(0, 3fr) minmax(400px, 1fr);
            }
            .sidebar {
                position: sticky;
                top: 0;
                align-self: start;
                height: fit-content;
            }
        }

        /* Mobile styles */
        @media (max-width: 899px) {
            .main-container {
                gap: 1rem;
            }
        
            .sidebar {
                position: static; 
            }
        
            .structure-output {
                font-size: 0.9rem;
            }
        }

        /* Header / Title */
        .header {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .header h1 {
            width: 100%;
            font-size: 1.8rem;
            color: white;
            margin: 0 0 40px 0;
        }

        .controls {
            width: 100%;
            display: flex;
            gap: 0.8rem;
            flex-wrap: wrap;
        }

        .controls button {
            flex: 1 1 auto; 
            min-width: 120px; 
        }
    
        #refreshBtn::before {
            content: '↻';
            font-size: 1.2rem;
            margin-right: 8px;
        }
        #copyBtn::before {
            content: '⎘';
            font-size: 1.2rem;
            margin-right: 8px;
        }

        button {
            background: transparent;
            color: white;
            border: 2px solid white;
            padding: 0.6rem 1.2rem;
            border-radius: 0;
            cursor: pointer;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 600;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            transition: all 0.1s ease;
        }
    
        button.copy-btn { 
            background: white; 
            color: black; 
        }
    
        button:hover, 
        button.copy-btn:hover { 
            background: rgb(0 96 255); 
            color: white; 
            border-color: rgb(0 96 255); 
        }
    
        button.copy-btn.copied {  
            background: rgb(0 96 255); 
            color: white; 
            border-color: rgb(0 96 255);
        }
    
        @keyframes pulse { 
            0%,100% { transform:scale(1); } 
            50% { transform:scale(1.05); } 
        }

        /* Structure output highlight */
        #structureOutput.highlight-copied.flash {
            color: rgb(128 192 255);  
            transition: none;
        }
    
        .current { 
            color: rgb(173 181 189); 
        }
    
        .path-info { 
            color: rgb(255 255 255 / .85); 
            font-size: 1rem; 
            margin-bottom: 1.5rem; 
        }
    
        .stats { 
            color: rgb(173 181 189); 
            margin-top: 1.5rem; 
        }

        /* Info box */
        .info-box {
            color: white;
            background: rgb(0 96 255 / .5);
            padding: 1rem;
            font-size: 0.9rem;
            margin-top: 2.5rem;
        }

        .error {
            color: #ff6b6b;
            background: #3a1d1d;
            padding: 10px;
            border-radius: 4px;
            border-left: 4px solid rgb(255 107 107);
        }
    
        .loading { 
            color: rgb(255 255 255 / .85); 
            font-style: italic; 
        }
    
        a { 
            display: inline-block; 
            margin-top: 30px; 
            color: rgb(173 181 189); 
            text-decoration: none; 
        }
    
        a:hover { 
            color: rgb(255 255 255); 
            text-decoration: none; 
        }

        /* Scrollbar styling for structure output */
        .structure-output::-webkit-scrollbar {
            height: 8px;
            background: transparent;
        }

        .structure-output::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
        }

        .structure-output::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
            transition: background 0.1s;
        }

        .structure-output::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.4);
        }

        /* Firefox scrollbar */
        .structure-output {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.2) rgba(255, 255, 255, 0.05);
        }

        /* Small mobile adjustments */
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
        
            .structure-container, .sidebar {
                padding: 1rem;
            }
        
            .controls button {
                min-width: 100%; 
            }
        
            .header h1 {
                font-size: 1.5rem;
                margin-bottom: 20px;
            }

            /* Smaller scrollbar on mobile */
            .structure-output::-webkit-scrollbar {
                height: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Left: main structure output -->
        <main>
            <div class="structure-container">
                <div class="path-info"><span class="current">Current directory:</span> <span id="currentPath">Loading...</span></div>
                <pre id="structureOutput" class="structure-output">Loading folder structure...</pre>
                <div class="stats" id="stats"></div>
            </div>
        </main>

        <!-- Right: sticky sidebar -->
        <aside class="sidebar">
            <div class="header">
                <h1>├── DirTree</h1>
                <div class="controls">
                    <button id="refreshBtn">Refresh</button>
                    <button id="copyBtn" class="copy-btn">Copy</button>
                </div>
            </div>

            <div class="info-box">
                <strong>Usage:</strong> Click "Copy" to get a clean ASCII tree for READMEs, docs, tickets, AI chats, etc.<br>
                Compatible with Markdown code blocks. Refresh after file changes.
            </div>

            <p class="credit"><a href="https://github.com/jacypress/dirtree" target="_blank">github.com/jacypress/dirtree</a></p>
        </aside>
    </div>

    <script>
        // Your existing JavaScript – unchanged
        class FolderStructure {
            constructor() {
                this.flatList = [];
                this.tree = null;
                this.outputEl = document.getElementById('structureOutput');
                this.statsEl = document.getElementById('stats');
                this.pathEl = document.getElementById('currentPath');
                this.init();
            }
            async init() {
                document.getElementById('refreshBtn').onclick = () => this.loadStructure();
                document.getElementById('copyBtn').onclick = () => this.copyToClipboard();
                await this.loadStructure();
            }
            async loadStructure() {
                this.showLoading();
                try {
                    const url = new URL(window.location);
                    url.searchParams.set('action', 'getStructure');
                    const res = await fetch(url);
                    const data = await res.json();
                    if (!data.success) throw new Error(data.error || 'Failed to load');
                    this.flatList = data.files;
                    this.tree = this.buildTree(data.files);
                    this.render(data.root || 'root');
                    this.updateStats();
                    this.pathEl.textContent = data.path;
                } catch (err) {
                    this.showError('Error: ' + err.message);
                }
            }
            buildTree(paths) {
                const tree = {};
                paths.forEach(path => {
                    if (!path) return;
                    const parts = path.split('/').filter(Boolean);
                    let node = tree;
                    parts.forEach((part, i) => {
                        const isLast = i === parts.length - 1;
                        const isDir = path.endsWith('/') || !isLast;
                        if (!node[part]) {
                            node[part] = { name: part, children: {}, isDir };
                        }
                        if (!isLast) node = node[part].children;
                    });
                });
                return tree;
            }
            generateTree(node, prefix = '', hasSibling = false) {
                let output = '';
                const entries = Object.entries(node).sort(([,a], [,b]) => {
                    if (a.isDir !== b.isDir) return a.isDir ? -1 : 1;
                    return a.name.localeCompare(b.name);
                });

                entries.forEach(([key, item], idx) => {
                    const isLastItem = idx === entries.length - 1;
        
                    // Determine the prefix for THIS line's branch symbol
                    const linePrefix = prefix + (isLastItem ? '└── ' : '├── ');
        
                    // Add the current item with its line prefix
                    output += linePrefix + item.name + (item.isDir ? '/' : '') + '\n';

                    // If this is a directory with children, generate them
                    if (item.isDir && Object.keys(item.children).length > 0) {
                        // For children: 
                        // - If current item is NOT the last sibling, continue the vertical bar
                        // - If current item IS the last sibling, use spaces (no vertical bar)
                        const childPrefix = prefix + (isLastItem ? '    ' : '│   ');
            
                        output += this.generateTree(item.children, childPrefix, !isLastItem);
                    }
                });

                return output;
            }
            render(rootName = 'root') {
                if (!this.tree || !Object.keys(this.tree).length) {
                    this.outputEl.textContent = '(empty directory)';
                    return;
                }
                const treeStr = rootName + '/\n' + this.generateTree(this.tree);
                this.outputEl.textContent = treeStr;
            }
            updateStats() {
                const folders = this.flatList.filter(p => p.endsWith('/')).length;
                const files = this.flatList.length - folders;
                this.statsEl.textContent = `${folders} folders • ${files} files`;
            }
            showLoading() {
                this.outputEl.textContent = 'Loading folder structure...';
                this.outputEl.className = 'structure-output loading';
            }
            showError(msg) {
                this.outputEl.textContent = msg;
                this.outputEl.className = 'structure-output error';
            }
            async copyToClipboard() {
                const text = this.outputEl.textContent.trim();
                const btn = document.getElementById('copyBtn');
                const output = this.outputEl;  // shorthand for #structureOutput

                try {
                    await navigator.clipboard.writeText(text);

                    // Start highlight immediately
                    output.classList.add('highlight-copied', 'flash');

                    // Remove flash after short burst (.5 seconds)
                    setTimeout(() => {
                        output.classList.remove('flash');
                    }, 500);

                    // Button feedback
                   const orig = btn.textContent;
                    btn.textContent = 'Copied!';
                    btn.classList.add('copied');

                    // Reset everything after .5 seconds
                    setTimeout(() => {
                        btn.textContent = orig;
                        btn.classList.remove('copied');
                        // Fade out the highlight (transition handles smooth removal)
                        output.classList.remove('highlight-copied');
                    }, 500);

                } catch (err) {
                    alert('Copy failed: ' + err);
                }
            }
        }
        document.addEventListener('DOMContentLoaded', () => new FolderStructure());
    </script>
</body>

</html>
