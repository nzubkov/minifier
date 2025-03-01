<?php

/**
 * Minify PHP, SQL, JavaScript, Vue, and CSS files by removing comments, unnecessary spaces, and applying file-specific strategies.
 * Supports both single file and recursive directory minification with progress visualization and file combination.
 */
class FileMinifier
{
    private static $supportedExtensions = ['php', 'sql', 'js', 'vue', 'css'];
    private static $totalFiles = 0;
    private static $processedFiles = 0;
    private static $progressBarWidth = 50;
    private static $options = [
        'combine' => false,
        'output' => null,
        'laravel' => false
    ];

    // Laravel-specific patterns
    private static $laravelPatterns = [
        'essential' => [
             '/API\/(?:[A-Za-z0-9\-\_]+\/)*.*Controller\.php$/',  // API Controllers with subdirectories
             '/Controllers\/(?:[A-Za-z0-9\-\_]+\/)*.*Controller\.php$/',       // Deep nested regular Controllers
            '/Services\/.*Service\.php$/',             // Service classes
            '/Events\/.*Event\.php$/',                 // Core events
            '/Listeners\/.*Listener\.php$/',           // Core listeners
            '/Notification\/.*.php$/',                 // Notifications
            '/Dto\/.*.php$/',                 // Dtos
            '/Actions\/.*.php$/',                 // Actions
            '/Enums\/.*.php$/',                 // Dtos
            '/Jobs\/.*.php$/',                 // Jobs
            '/Mail\/.*.php$/',                 // Mail
            '/Rules\/.*.php$/',                 // Rules
            '/TelegramBot\/.*\.php$/',            // TelegramBot
            '/Http\/Requests\/.*Request\.php$/',       // Requests,
            '/Http\/Resources\/.*Resource\.php$/',       // Resources,
             '/Imports\/(?:[A-Za-z0-9\-\_]+\/)*.*\.php$/',        // All PHP files in Imports and subdirectories
            '/Models\/(?:[A-Za-z0-9\-\_]+\/)*.*\.php$/',         // All PHP files in Models and subdirectories
            '/Notifications\/(?:[A-Za-z0-9\-\_]+\/)*.*\.php$/',  // All PHP files in Notifications and subdirectories
            '/Providers\/(?:[A-Za-z0-9\-\_]+\/)*.*\.php$/',      // All PHP files in Providers and subdirectories
            '/Observers\/(?:[A-Za-z0-9\-\_]+\/)*.*\.php$/',      // All PHP files in Observers and subdirectories
            '/Policies\/(?:[A-Za-z0-9\-\_]+\/)*.*\.php$/',       // All PHP files in Policies and subdirectories
        ],
        'exclude' => [
            '/Middleware\/(Authenticate|EncryptCookies|PreventRequestDuringMaintenance|RedirectIfAuthenticated|TrimStrings|TrustHosts|TrustProxies|ValidateSignature|VerifyCsrfToken|TrimStrings)\.php$/',  // Basic middleware
            '/Providers\/(RouteServiceProvider|EventServiceProvider)\.php$/',   // Basic providers
            
        ],
        'content_remove' => [
            '/protected \$fillable=\[.*?\];/',           // Basic fillable arrays
            '/protected \$casts=\[.*?\];/',              // Basic casts
            '/public function __construct\(\).*?}/',     // Empty constructors
            '/public function __construct\(\){}}/',      // Empty constructors
            '/public function toArray\(\).*?}/',         // Simple toArray methods
        ]
    ];

    /**
     * Count total files to be processed in a directory
     */
    private static function countFiles($directory)
    {
        $count = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && in_array(strtolower($file->getExtension()), self::$supportedExtensions)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Draw progress bar in console
     */
    private static function drawProgressBar($message = '')
    {
        if (self::$totalFiles === 0) return;

        $percentage = (self::$processedFiles / self::$totalFiles) * 100;
        $filled = round(($percentage * self::$progressBarWidth) / 100);
        $empty = self::$progressBarWidth - $filled;

        echo "\r";
        echo sprintf(
            'Progress: [%s%s] %.1f%% (%d/%d) %s',
            str_repeat('█', $filled),
            str_repeat('░', $empty),
            $percentage,
            self::$processedFiles,
            self::$totalFiles,
            $message
        );

        if (self::$processedFiles === self::$totalFiles) {
            echo PHP_EOL;
        }
        
        flush();
    }

    /**
     * Format bytes to human readable format
     */
    private static function formatBytes($bytes)
    {
        if ($bytes < 1024) return $bytes . " B";
        if ($bytes < 1048576) return round($bytes / 1024, 2) . " KB";
        return round($bytes / 1048576, 2) . " MB";
    }

    /**
     * Minify PHP content
     */
    private static function minifyPHP($content)
    {
        // Remove comments
        $content = preg_replace('/\/\/[\s\S]+?$|\/\*[\s\S]*?\*\//m', '', $content);
        // Remove whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        // Remove spaces around operators
        $content = preg_replace('/\s*([\(\)\{\}\[\],;:=><])\s*/', '$1', $content);
        return trim($content);
    }

    /**
     * Minify SQL content
     */
    private static function minifySQL($content)
    {
        // Remove comments
        $content = preg_replace('/^--[\s\S+a-zA-Z]+?$/m', '', $content);
        $content = preg_replace('/\/\*[\s\S]*?\*\//', '', $content);
        // Remove whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        return trim($content);
    }

    /**
     * Minify JavaScript content
     */
    private static function minifyJS($content)
    {
        // Remove comments
        $content = preg_replace('/\/\/.*$/m', '', $content);
        $content = preg_replace('/\/\*[\s\S]*?\*\//', '', $content);
        // Remove whitespace
        $content = preg_replace('/^\s+|\s+$/m', '', $content);
        $content = preg_replace('/\n+/', '', $content);
        // Remove spaces around operators
        $content = preg_replace('/\s*([=+\-*\/%&|^!<>{}()\[\];:,.])\s*/', '$1', $content);
        // Ensure space after keywords
        $content = preg_replace('/(if|else|for|while|switch|catch|function)\(/', '$1 (', $content);
        return trim($content);
    }

    /**
     * Minify Vue content
     */
    private static function minifyVue($content)
    {
        // Template section
        $content = preg_replace('/<template>\s*/', '<template>', $content);
        $content = preg_replace('/\s*<\/template>/', '</template>', $content);
        $content = preg_replace('/>\s+</', '><', $content);
        
        // Script section
        $content = preg_replace_callback('/<script>(.*?)<\/script>/s', function($matches) {
            return '<script>' . self::minifyJS($matches[1]) . '</script>';
        }, $content);
        
        // Style section
        $content = preg_replace_callback('/<style[^>]*>(.*?)<\/style>/s', function($matches) {
            return '<style>' . self::minifyCSS($matches[1]) . '</style>';
        }, $content);
        
        return trim($content);
    }

    /**
     * Minify CSS content
     */
    private static function minifyCSS($content)
    {
        // Remove comments
        $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
        // Remove space after colons and before opening braces
        $content = preg_replace('/\s*:\s*/', ':', $content);
        $content = preg_replace('/\s*{\s*/', '{', $content);
        // Remove whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        // Remove space before and after {} ; ,
        $content = preg_replace('/\s*([\{\};,])\s*/', '$1', $content);
        // Remove last semicolon in block
        $content = preg_replace('/;(?=\s*})/', '', $content);
        return trim($content);
    }

    // [Rest of the class methods: process, processDirectory, processFile, etc. remain the same]
    // ... [Previous implementation]

    public static function run($argv)
    {
        $options = self::parseArguments($argv);

        if ($options['source'] === null) {
            echo "Usage:\n";
            echo "Single file   : php minify.php <path_to_file> [options]\n";
            echo "Directory     : php minify.php <source_directory> [options]\n";
            echo "Laravel       : php minify.php <laravel_app_directory> --laravel [options]\n";
            echo "\nOptions:\n";
            echo "-c, --combine    Combine all files into a single output file\n";
            echo "-o, --output     Specify output file/directory path\n";
            echo "-l, --laravel    Enable Laravel-specific minification\n";
            echo "\nSupported file types: " . implode(', ', self::$supportedExtensions) . "\n";
            return;
        }

        self::process(
            $options['source'],
            $options['output'],
            [
                'combine' => $options['combine'],
                'laravel' => $options['laravel']
            ]
        );
    }

    private static function processLaravelDirectory($sourceDir, $outputDir = null)
    {
        if (!is_dir($sourceDir)) {
            echo "Source directory not found: $sourceDir\n";
            return false;
        }

        echo "Scanning Laravel project directory...\n";
        self::$totalFiles = self::countFiles($sourceDir);
        
        if (self::$totalFiles === 0) {
            echo "No PHP files found in directory.\n";
            return false;
        }

        echo "Found " . self::$totalFiles . " PHP files to process.\n\n";

        if ($outputDir === null) {
            $outputDir = dirname($sourceDir) . '/minified';
        }

        if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true)) {
            echo "Failed to create output directory: $outputDir\n";
            return false;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $successCount = 0;
        $skippedCount = 0;
        $totalSaved = 0;

        foreach ($iterator as $item) {
            if ($item->isFile() && $item->getExtension() === 'php') {
                $relativePath = str_replace($sourceDir, '', $item->getPathname());
                $outputPath = $outputDir . $relativePath;
                
                $content = file_get_contents($item->getPathname());
                $minifiedContent = self::minifyLaravelFile($item->getPathname(), $content);

                if ($minifiedContent === false) {
                    $skippedCount++;
                    self::$processedFiles++;
                    continue;
                }

                $outputFileDir = dirname($outputPath);
                if (!is_dir($outputFileDir)) {
                    mkdir($outputFileDir, 0777, true);
                }

                if (file_put_contents($outputPath, $minifiedContent) !== false) {
                    $successCount++;
                    $totalSaved += strlen($content) - strlen($minifiedContent);
                }

                self::$processedFiles++;
                self::drawProgressBar(basename($item->getPathname()));
            }
        }

        echo "\n\nLaravel Minification Summary:\n";
        echo "Total files found: " . self::$totalFiles . "\n";
        echo "Successfully minified: $successCount\n";
        echo "Skipped (excluded): $skippedCount\n";
        echo "Failed: " . (self::$totalFiles - $successCount - $skippedCount) . "\n";
        echo "Total space saved: " . self::formatBytes($totalSaved) . "\n";

        return true;
    }

    private static function processLaravelDirectoryToCombinedFile($sourceDir, $outputFile = null)
    {
        if (!is_dir($sourceDir)) {
            echo "Source directory not found: $sourceDir\n";
            return false;
        }

        echo "Scanning Laravel project directory...\n";
        self::$totalFiles = self::countFiles($sourceDir);
        
        if (self::$totalFiles === 0) {
            echo "No PHP files found in directory.\n";
            return false;
        }

        echo "Found " . self::$totalFiles . " PHP files to process.\n\n";

        // If no output file specified, create default
        if ($outputFile === null) {
            $outputFile = dirname($sourceDir) . '/minified/combined.laravel.php';
        }

        // Create output directory if needed
        $outputDir = dirname($outputFile);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $successCount = 0;
        $skippedCount = 0;
        $totalSaved = 0;
        $combinedContent = [];
        
        // Group files by type for proper ordering
        $fileGroups = [
            'models' => [],
            'services' => [],
            'events' => [],
            'listeners' => [],
            'controllers' => [],
            'other' => []
        ];

        // First pass: categorize and process files
        foreach ($iterator as $item) {
            if ($item->isFile() && $item->getExtension() === 'php') {
                $relativePath = str_replace($sourceDir, '', $item->getPathname());
                $content = file_get_contents($item->getPathname());
                
                $minifiedContent = self::minifyLaravelFile($item->getPathname(), $content);
                
                if ($minifiedContent === false) {
                    $skippedCount++;
                    self::$processedFiles++;
                    continue;
                }

                // Determine file category
                if (preg_match('/Models\/.*\.php$/', $relativePath)) {
                    $fileGroups['models'][$relativePath] = $minifiedContent;
                } elseif (preg_match('/Services\/.*\.php$/', $relativePath)) {
                    $fileGroups['services'][$relativePath] = $minifiedContent;
                } elseif (preg_match('/Events\/.*\.php$/', $relativePath)) {
                    $fileGroups['events'][$relativePath] = $minifiedContent;
                } elseif (preg_match('/Listeners\/.*\.php$/', $relativePath)) {
                    $fileGroups['listeners'][$relativePath] = $minifiedContent;
                } elseif (preg_match('/Controllers\/.*\.php$/', $relativePath)) {
                    $fileGroups['controllers'][$relativePath] = $minifiedContent;
                } else {
                    $fileGroups['other'][$relativePath] = $minifiedContent;
                }

                $successCount++;
                $totalSaved += strlen($content) - strlen($minifiedContent);
                
                self::$processedFiles++;
                self::drawProgressBar(basename($item->getPathname()));
            }
        }

        // Combine content in the correct order
        $combinedContent[] = "<?php\n";
        $combinedContent[] = "/**\n * Combined Laravel files - Generated by FileMinifier\n * Date: " . date('Y-m-d H:i:s') . "\n */\n";

        // Add files in the desired order
        foreach ($fileGroups as $groupName => $files) {
            if (!empty($files)) {
                $combinedContent[] = "\n// {$groupName}\n";
                foreach ($files as $relativePath => $content) {
                    // Remove opening PHP tag if present
                    $content = preg_replace('/^<\?php\s*/', '', $content);
                    $combinedContent[] = "/* File: {$relativePath}*/";
                    $combinedContent[] = $content;
                }
            }
        }

        // Write combined content to output file
        $finalContent = implode("\n", $combinedContent);
        if (file_put_contents($outputFile, $finalContent) === false) {
            echo "\nFailed to write combined file: $outputFile\n";
            return false;
        }

        $finalSize = filesize($outputFile);

        echo "\n\nLaravel Combination Summary:\n";
        echo "Total files found: " . self::$totalFiles . "\n";
        echo "Successfully combined: $successCount\n";
        echo "Skipped (excluded): $skippedCount\n";
        echo "Failed: " . (self::$totalFiles - $successCount - $skippedCount) . "\n";
        echo "Total space saved: " . self::formatBytes($totalSaved) . "\n";
        echo "Combined file size: " . self::formatBytes($finalSize) . "\n";
        echo "Output file: $outputFile\n";

        return true;
    }

    private static function minifyLaravelFile($filePath, $content)
    {
        $relativePath = str_replace(realpath(dirname($filePath) . '/../../'), '', realpath($filePath));
        
        // Check if file should be kept
        $keep = false;
        foreach (self::$laravelPatterns['essential'] as $pattern) {
            if (preg_match($pattern, $relativePath)) {
                $keep = true;
                break;
            }
        }

        // Check if file should be excluded
        foreach (self::$laravelPatterns['exclude'] as $pattern) {
            if (preg_match($pattern, $relativePath)) {
                $keep = false;
                break;
            }
        }

        if (!$keep) {
            return false;
        }

    
        $content = self::minifyPHP($content);

        // Clean up content
        foreach (self::$laravelPatterns['content_remove'] as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }

        return $content;
    }

    private static function parseArguments($argv)
    {
        $options = [
            'combine' => false,
            'output' => null,
            'source' => null,
            'laravel' => false
        ];

        for ($i = 1; $i < count($argv); $i++) {
            switch ($argv[$i]) {
                case '-c':
                case '--combine':
                    $options['combine'] = true;
                    break;
                case '-o':
                case '--output':
                    $options['output'] = $argv[++$i] ?? null;
                    break;
                case '-l':
                case '--laravel':
                    $options['laravel'] = true;
                    break;
                default:
                    if ($options['source'] === null) {
                        $options['source'] = $argv[$i];
                    }
            }
        }

        return $options;
    }


public static function process($sourcePath, $outputPath = null, $options = [])
    {
        self::$options = array_merge(self::$options, $options);
        self::$totalFiles = 0;
        self::$processedFiles = 0;

        if (self::$options['laravel']) {
            return self::$options['combine']
                ? self::processLaravelDirectoryToCombinedFile($sourcePath, $outputPath)
                : self::processLaravelDirectory($sourcePath, $outputPath);
        }

        if (is_dir($sourcePath)) {
            return self::$options['combine']
                ? self::processDirectoryToCombinedFile($sourcePath, $outputPath)
                : self::processDirectory($sourcePath, $outputPath);
        }
        
        return self::processFile($sourcePath, $outputPath);
    }

/**
 * Process a single file
 */
private static function processFile($sourcePath, $outputPath = null, $showIndividualStats = true)
{
    if (!file_exists($sourcePath)) {
        echo "Source file not found: $sourcePath\n";
        return false;
    }

    $content = file_get_contents($sourcePath);
    $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

    if (!in_array($extension, self::$supportedExtensions)) {
        echo "Unsupported file type: $extension\n";
        return false;
    }

    // Minify content based on file type
    switch ($extension) {
        case 'php':
            $content = self::minifyPHP($content);
            break;
        case 'sql':
            $content = self::minifySQL($content);
            break;
        case 'js':
            $content = self::minifyJS($content);
            break;
        case 'vue':
            $content = self::minifyVue($content);
            break;
        case 'css':
            $content = self::minifyCSS($content);
            break;
    }

    // If no output path specified, create one in the 'minified' directory
    if ($outputPath === null) {
        $outputPath = dirname($sourcePath) . '/minified/' . basename($sourcePath);
        $outputDir = dirname($outputPath);
        
    }
    
    if (!is_dir($outputDir ?? $outputPath)) {
        mkdir($outputDir ?? $outputPath, 0777, true);
    }

    // Write minified content
    if (file_put_contents($outputPath . DIRECTORY_SEPARATOR . basename($sourcePath), $content) === false) {
        echo "Failed to write minified file: $outputPath\n";
        return false;
    }

    $originalSize = filesize($sourcePath);
    $minifiedSize = filesize($outputPath . DIRECTORY_SEPARATOR . basename($sourcePath));
    $savedBytes = $originalSize - $minifiedSize;
    $savedPercentage = round(($savedBytes / $originalSize) * 100, 2);

    if ($showIndividualStats) {
        echo "Minified: " . basename($sourcePath) . "\n";
        echo "Original size: " . self::formatBytes($originalSize) . "\n";
        echo "Minified size: " . self::formatBytes($minifiedSize) . "\n";
        echo "Saved: " . self::formatBytes($savedBytes) . " ($savedPercentage%)\n\n";
    }

    return $savedBytes;
}

/**
 * Process an entire directory
 */
private static function processDirectory($sourceDir, $outputDir = null)
{
    if (!is_dir($sourceDir)) {
        echo "Source directory not found: $sourceDir\n";
        return false;
    }

    echo "Scanning directory...\n";
    self::$totalFiles = self::countFiles($sourceDir);
    
    if (self::$totalFiles === 0) {
        echo "No supported files found in directory.\n";
        return false;
    }

    echo "Found " . self::$totalFiles . " files to process.\n\n";

    if ($outputDir === null) {
        $outputDir = dirname($sourceDir) . '/minified';
    }

    if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true)) {
        echo "Failed to create output directory: $outputDir\n";
        return false;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $successCount = 0;
    $totalSaved = 0;

    foreach ($iterator as $item) {
        if ($item->isFile()) {
            $extension = strtolower($item->getExtension());
            if (in_array($extension, self::$supportedExtensions)) {
                $relativePath = str_replace($sourceDir, '', $item->getPathname());
                $outputPath = $outputDir . $relativePath;
                
                $outputFileDir = dirname($outputPath);
                if (!is_dir($outputFileDir)) {
                    mkdir($outputFileDir, 0777, true);
                }

                $result = self::processFile($item->getPathname(), $outputPath, false);
                if ($result !== false) {
                    $successCount++;
                    $totalSaved += $result;
                }
                
                self::$processedFiles++;
                self::drawProgressBar(basename($item->getPathname()));
            }
        }
    }

    echo "\n\nMinification Summary:\n";
    echo "Total files processed: " . self::$totalFiles . "\n";
    echo "Successfully minified: $successCount\n";
    echo "Failed: " . (self::$totalFiles - $successCount) . "\n";
    echo "Total space saved: " . self::formatBytes($totalSaved) . "\n";

    return $successCount === self::$totalFiles;
}

/**
 * Process directory into a single combined file
 */
private static function processDirectoryToCombinedFile($sourceDir, $outputFile)
{
    if (!is_dir($sourceDir)) {
        echo "Source directory not found: $sourceDir\n";
        return false;
    }

    echo "Scanning directory...\n";
    self::$totalFiles = self::countFiles($sourceDir);
    
    if (self::$totalFiles === 0) {
        echo "No supported files found in directory.\n";
        return false;
    }

    echo "Found " . self::$totalFiles . " files to process.\n\n";

    $filesByType = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isFile()) {
            $extension = strtolower($item->getExtension());
            if (in_array($extension, self::$supportedExtensions)) {
                $filesByType[$extension][] = $item->getPathname();
            }
        }
    }

    if ($outputFile === null) {
        $dirName = basename($sourceDir);
        $outputFile = dirname($sourceDir) . '/minified/' . $dirName . '.combined.min.js';
    }

    $outputDir = dirname($outputFile);
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true);
    }

    $combinedContent = [];
    $successCount = 0;

    foreach ($filesByType as $type => $files) {
        foreach ($files as $file) {
            $content = file_get_contents($file);
            
            switch ($type) {
                case 'php':
                    $content = self::minifyPHP($content);
                    break;
                case 'js':
                    $content = self::minifyJS($content);
                    break;
                case 'css':
                    $content = self::minifyCSS($content);
                    break;
            }

            $combinedContent[] = "/* File: " . basename($file) . "*/".$content;
            self::$processedFiles++;
            $successCount++;
            
            self::drawProgressBar(basename($file));
        }
    }

    $finalContent = implode("\n", $combinedContent);
    if (file_put_contents($outputFile, $finalContent) === false) {
        echo "\nFailed to write combined file: $outputFile\n";
        return false;
    }

    $totalSize = filesize($outputFile);
    
    echo "\n\nCombination Summary:\n";
    echo "Total files processed: " . self::$totalFiles . "\n";
    echo "Successfully combined: $successCount\n";
    echo "Failed: " . (self::$totalFiles - $successCount) . "\n";
    echo "Combined file size: " . self::formatBytes($totalSize) . "\n";
    echo "Output file: $outputFile\n";

    return true;
}
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    // Only run when executed directly, not when included in tests
    FileMinifier::run($argv);
}