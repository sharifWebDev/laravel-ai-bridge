**`packages/AiBridge/README.md`**

```markdown
# Laravel AI Bridge

A professional, robust, and modular AI-driven integration for Laravel, enabling Large Language Models (like Gemini) to dynamically execute application controller methods as tools. Built strictly following SOLID principles, PSR-4 standards, and featuring built-in caching, semantic tool filtering, and Spatie-compatible role/permission authorization.

## 🚀 Features

* **Dynamic Controller Discovery** - Automatically scans public controller methods via PHP Reflection and maps them into LLM-compatible tool declarations.
* **Custom Tool Overrides** - Implement `ToolProviderInterface` inside any controller to manually define custom tools, descriptions, and parameters.
* **Performance Caching** - Caches generated tool schemas to prevent heavy disk and reflection overhead in production environments.
* **Semantic Tool Selection** - Intelligently filters and selects relevant tools based on user prompts.
* **Built-in Authorization** - Automatically checks logged-in user permissions before exposing tools to the AI model.
* **SOLID Architecture** - Decoupled contracts and services for maximum maintainability and extensibility.
* **Laravel 8, 9, 10, 11 & 12 Support** - Fully compatible with modern Laravel versions.

---

## ⚙️ Requirements

* **PHP:** `^8.0`
* **Laravel:** `^8.0 | ^9.0 | ^10.0 | ^11.0 | ^12.0`

---

## 📦 Installation

### Step 1: Local Package Integration
Add the package repository path to your main project's `composer.json` file:

```json
"repositories": [
    {
        "type": "path",
        "url": "packages/AiBridge"
    }
]

```

### Step 2: Require the Package

Run the following command in your terminal to install the package:

```bash
composer require sharifuddin/laravel-ai-bridge:@dev

```

### Step 3: Configure API Key

Add your Gemini API key to your project's `.env` file:

```env
GEMINI_API_KEY=your_gemini_api_key_here

```

Ensure your `config/services.php` includes the Gemini configuration:

```php
'gemini' => [
    'key' => env('GEMINI_API_KEY'),
],

```

---

## 🛠 Usage & Setup

### 1. Basic Controller Tool Discovery

By default, the package scans designated controllers and registers public methods as AI tools. You can customize permissions or map methods easily.

### 2. Advanced: Implementing Custom Tools in Controllers

If you want a controller to manage its own custom tools or override default parameters, implement `ToolProviderInterface`:

```php
namespace App\Http\Controllers;

use Sharifuddin\LaravelAiBridge\Contracts\ToolProviderInterface;
use Illuminate\Http\Request;

class UserController extends Controller implements ToolProviderInterface
{
    public function getTools(): array
    {
        return [
            'exportUsers' => [
                "name" => "exportUsers",
                "description" => "Export user list in CSV or Excel format.",
                "permission" => "view-records",
                "parameters" => [
                    "type" => "OBJECT",
                    "properties" => [
                        "format" => ["type" => "STRING", "description" => "csv or excel"]
                    ]
                ]
            ]
        ];
    }

    public function exportUsers(Request $request)
    {
        return response()->json(['message' => 'Exporting users...']);
    }
}

```

### 3. Handling AI Prompts via Route

Register the AI controller handle method in your `routes/api.php` file:

```php
use Sharifuddin\LaravelAiBridge\Http\Controllers\AiController;

Route::post('/ai/prompt', [AiController::class, 'handle']);

```

### 4. Testing the Endpoint

Send a `POST` request to `/api/ai/prompt` with a JSON payload:

```json
{
    "prompt": "Can you export all users into csv format?"
}

```

**Successful Function Call Response:**

```json
{
    "status": "success",
    "action": "exportUsers",
    "arguments": {
        "format": "csv"
    }
}

```

**Unauthorized Response (Missing Permissions):**

```json
{
    "status": "error",
    "message": "You do not have the necessary permissions to perform this action."
}

```

---

## 🧪 Testing

Run package tests using PHPUnit (if configured in your development environment):

```bash
vendor/bin/phpunit

```

---

## 📜 License

This package is open-sourced software licensed under the [MIT license](https://www.google.com/search?q=LICENSE).

## 👨‍💻 Author

**Sharif Uddin**

* Email: sharif.webpro@gmail.com
* Website: [https://sharifwebdev.github.io/](https://sharifwebdev.github.io/)

```

```

