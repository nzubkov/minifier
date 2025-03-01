# minifier: Streamline Your Code, Optimize Your Tokens
A powerful PHP-based minification utility that intelligently reduces file sizes by eliminating unnecessary elements while preserving functionality. Designed with precision for developers who value efficiency and optimization.
The utility is for reducing file sizes by removing comments, whitespace, and unnecessary characters from PHP, SQL, JavaScript, Vue, and CSS files. Especially useful for Claude AI projects to reduce token count and optimize usage of API pricing plans.

# Requirements:

#### PHP 8.2+ to run the minifier

## Features

- **Multi-language support**: Minifies PHP, SQL, JavaScript, Vue, and CSS files
- **Flexible processing modes**:
    - Single file minification
    - Recursive directory processing
    - File combination into a single output
- **Laravel project optimization**: Special mode with smart file filtering for Laravel applications
- **Visual feedback**: Console progress bar and detailed statistics
- **Significant size reduction**: Effectively reduces file sizes while preserving functionality

## Why Use FileMinifier?

- **Reduce API costs**: When sharing code with AI models like Claude, smaller files mean fewer tokens and lower costs
- **Improve load times**: Minified files load faster in browsers and applications
- **Laravel optimization**: Intelligently processes Laravel applications with customized rules
- **Preserve core functionality**: Removes only non-essential elements while keeping code working

## Installation

Simply place somewhere the minify.php file

## Usage

### Basic Usage
```bash
# Minify a single file
php minify.php path/to/file.js

# Process an entire directory (recursive)
php minify.php path/to/directory

# Process a Laravel project
php minify.php path/to/laravel-app --laravel
```

### Advanced Options

```bash
# Combine all files into a single output file
php minify.php path/to/directory --combine

# Specify custom output location
php minify.php path/to/file.js --output path/to/output.js

# Process Laravel project and combine into single file
php minify.php path/to/laravel-app --laravel --combine --output path/to/combined.php
```

## Examples

### Minifying a JavaScript File

```php
php minify.php assets/js/app.js
```
#### Output:
```bash
Minified: app.js
Original size: 124.53 KB
Minified size: 68.21 KB
Saved: 56.32 KB (45.2%)
```

### Processing a Directory of CSS Files

```bash
php minify.php public/css
```
#### Output:

```bash
Scanning directory...
Found 8 files to process.

Progress: [████████████████████████████████████████████████] 100.0% (8/8) style.css

Minification Summary:
Total files processed: 8
Successfully minified: 8
Failed: 0
Total space saved: 247.85 KB
```

### Minifying a Laravel Project

```bash
php minify.php app --laravel
```

#### Output:

```bash
Scanning Laravel project directory...
Found 87 PHP files to process.

Progress: [████████████████████████████████████████████████] 100.0% (87/87) UserController.php

Laravel Minification Summary:
Total files found: 87
Successfully minified: 78
Skipped (excluded): 9
Failed: 0
Total space saved: 398.64 KB
```

### Combining Multiple Files

```bash
php minify.php src/js --combine --output dist/bundle.min.js
```

#### Output:

```bash
Scanning directory...
Found 12 files to process.

Progress: [████████████████████████████████████████████████] 100.0% (12/12) utilities.js

Combination Summary:
Total files processed: 12
Successfully combined: 12
Failed: 0
Combined file size: 86.4 KB
Output file: dist/bundle.min.js
```

## Use Case: Optimizing for Claude API

When working with AI assistants like Claude that charge by token count, reducing file size becomes essential for cost management. For example:

1. Before minification: A PHP project with 20,000 tokens might cost $0.20 at $0.01/1K tokens
2. After minification: The same project reduced to 12,000 tokens costs only $0.12

FileMinifier helps optimize your code sharing with AI assistants while maintaining all functional aspects of your code.

## Laravel Optimization

The Laravel mode intelligently processes files based on their importance:

- **Keeps and optimizes**: Controllers, Models, Services, Events, Listeners, etc.
- **Excludes**: Basic middleware, default providers, and other boilerplate files
- **Removes boilerplate code**: Empty constructors, basic property definitions, etc.

## Contributing

Contributions are welcome! Feel free to submit pull requests with improvements or additional features.

## License

MIT License
