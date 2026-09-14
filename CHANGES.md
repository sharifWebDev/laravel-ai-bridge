# Changes in this update

## ১. Multiple AI Agent Switching (Gemini / DeepSeek / ChatGPT / any custom API)

- **`config/ai-bridge.php`** — নতুন `openai`, `deepseek`, `anthropic` config block + একটি
  `providers` array যেখানে যেকোনো সংখ্যক অতিরিক্ত/custom AI API রেজিস্টার করা যাবে।
  Switch করতে শুধু `.env`-এ পরিবর্তন করুন:

  ```env
  AI_BRIDGE_PROVIDER=gemini      # default
  AI_BRIDGE_PROVIDER=openai      # or "chatgpt"
  AI_BRIDGE_PROVIDER=deepseek
  AI_BRIDGE_PROVIDER=anthropic   # or "claude"
  AI_BRIDGE_PROVIDER=groq        # any custom key you add under `providers`
  ```

- **`src/Providers/OpenAiCompatibleProvider.php`** *(new)* — একটাই generic class যা
  OpenAI-এর `/chat/completions` wire format ব্যবহার করে এমন যেকোনো provider চালাতে পারে:
  OpenAI (ChatGPT), DeepSeek, Groq, OpenRouter, Together AI, এমনকি local/self-hosted
  model (Ollama, vLLM, LM Studio)। নতুন provider add করতে PHP কোড লিখতে হবে না — শুধু
  config-এ `base_url` / `key` / `model` দিলেই হবে (দেখুন `config/ai-bridge.php` এর
  `providers` সেকশনের example)।

- **`src/Providers/AnthropicAiProvider.php`** *(new)* — bonus হিসেবে Claude/Anthropic-ও
  যোগ করা হয়েছে, যাতে বোঝা যায় ভিন্ন wire-format-এর (non-OpenAI-compatible) provider
  কীভাবে যোগ করতে হয়।

- **`src/Providers/AiProviderManager.php`** — পুরোপুরি rewrite করে multi-provider resolve
  করার জন্য। কোনো driver-এর জন্য built-in ক্লাস না থাকলে `providers.{key}` config দেখে
  হয় generic OpenAI-compatible provider বানাবে, নয়তো আপনার নিজের
  `AiProviderInterface`-implement করা class ব্যবহার করবে (`'class' => \App\...::class`)।

- **`src/DTO/ToolDefinition.php`** — Tool declaration এখন canonical, lowercase
  JSON-Schema আকারে বানানো হয় (OpenAI/DeepSeek/Anthropic সবার সাথে সরাসরি কাজ করে)।

- **`src/Providers/GeminiAiProvider.php`** — এখন internally সেই canonical schema-কে
  নিজের uppercase (`OBJECT`/`STRING`/...) ফরম্যাটে রূপান্তর করে, তাই Gemini আগের মতোই
  কাজ করবে — কোনো breaking change নেই।

## ২. Pagination / Query Parameter বাগ ফিক্স + Vector Indexing উন্নতি

### মূল কারণ যা পাওয়া গেছে

1. `CachedControllerToolProvider::extractMethodParameters()` শুধু method **signature**
   এর typed parameter reflect করত। কিন্তু একটা সাধারণ `index(Request $request)` মেথডে
   pagination/filter parameter (`per_page`, `page`, `search`, `sort_by`...) signature-এ
   থাকে না — সেগুলো method-এর **body**-তে `$request->query()`/`->get()`/`->input()`
   দিয়ে read করা হয়। ফলে AI-কে পাঠানো schema **পুরোপুরি খালি** থাকত।
2. `ArgumentValidator` শুধু declared schema-তে থাকা argument-ই allow করে (whitelist)।
   তাই AI যদি `per_page: 20` পাঠাতোও, schema-তে না থাকায় সেটা **silently drop** হয়ে যেত।
3. `LegacyControllerToolAdapter::execute()`-এ AI-এর দেওয়া argument শুধু Request-এর
   **POST/request bag**-এ merge হতো, **query bag**-এ না — তাই যেসব controller
   `$request->query('per_page')` ব্যবহার করে, তারা AI-এর দেওয়া মান কখনোই দেখতে পেত না।

### ফিক্স

- **`src/Services/ControllerRequestParameterAnalyzer.php`** *(new)* — controller
  method-এর source code static ভাবে scan করে (`$request->query/get/input/has/filled/
  boolean/integer/float/string/only(...)` কল এবং `->validate([...])` rule) real
  filter/pagination parameter খুঁজে বের করে — নাম, type, default value ও description সহ।
  Zero dependency (কোনো নতুন composer package লাগেনি), আর কখনো user code execute করে না।
- **`CachedControllerToolProvider::extractMethodParameters()`** — এখন signature থেকে
  পাওয়া parameter-এর সাথে analyzer-এর discover করা parameter merge করে, তাই index/list
  tool-এর schema আর কখনো খালি থাকবে না।
- **`LegacyControllerToolAdapter::execute()`** — AI-এর দেওয়া argument এখন **query এবং
  request** — দুই bag-এই merge করা হয়, তাই controller `query()`/`get()`/`input()`
  যেটাই ব্যবহার করুক না কেন, AI-এর দেওয়া মান কার্যকর হবে।
- **`LegacyControllerToolAdapter::embeddingText()`** — index/list-জাতীয় action-এর জন্য
  আরও বড়, বেশি সমৃদ্ধ vector text তৈরি হয় এখন: resource alias (`"all users"`,
  `"users list"` ইত্যাদি) এবং discovered parameter-এর নাম + সাধারণ query-phrasing
  ("show top N records", "sort the list by a field", ...) সব embedding-এ ঢোকানো হয়।
  ফলে vector search "top 20 users দেখাও"-এর মতো query-কেও সঠিক tool-এর সাথে ভালোভাবে
  match করতে পারবে।

### পরের ধাপ (আপনাকে যা করতে হবে)

Controller/action metadata পরিবর্তনের পর caches invalidate করতে:

```bash
php artisan ai:tools:reindex
```

অথবা নির্দিষ্ট একটা tool-এর জন্য:

```bash
php artisan ai:tools:reindex --tool=sample_controller_index
```

(`ai-bridge.cache` enabled থাকলে `CachedControllerToolProvider`-এর discovered-tools
cache টাও নিজে থেকে TTL অনুযায়ী রিফ্রেশ হবে, বা `AI_BRIDGE_CACHE_ENABLED=false` করে
রেখে দেখতে পারেন।)

## ৩. Create/Update/Delete/Find-by-ID — সব pattern এখন সাপোর্টেড

`ControllerRequestParameterAnalyzer` এখন **তিনটা** data-source, priority অনুযায়ী চেক করে
(যেটা পাওয়া যায় সেটাই ব্যবহার করে):

1. **Controller-এ inline validation** (`$request->validate([...])`) — Controller নিজে
   validate করে, তারপর সেই data একটা Service class-এ পাঠালেও (Controller → Service
   pattern) এটা কাজ করবে, কারণ analyzer শুধু controller method-এর নিজের body scan করে,
   সেই data পরে কোথায় যাচ্ছে সেটা নিয়ে মাথা ঘামায় না।
2. **আলাদা FormRequest ক্লাস** (`store(StoreUserRequest $request)`) — এখন
   `StoreUserRequest::rules()` মেথডও reflect করে scan করা হয় (আগে এটা মিস হতো)।
3. **Model `$fillable` fallback** — যদি কোনো validation-ই না পাওয়া যায় (যেমন
   `User::create($request->all())`), তাহলে resource name থেকে অনুমান করা Model ক্লাস
   (`App\Models\User` বা `App\User`)-এর `$fillable` array থেকে field discover করা হয়
   ($casts থেকে type infer সহ)।

**Route-model-binding-ও এখন কাজ করবে** — অর্থাৎ যদি আপনার controller এভাবে লেখা হয়:

```php
public function show(User $user) { ... }
public function update(User $user, Request $request) { ... }
public function destroy(User $user) { ... }
```

তাহলে AI-কে schema-তে শুধু record-এর `id` (integer) চাওয়া হবে; execute হওয়ার সময়
`LegacyControllerToolAdapter` নিজে `User::find($id)` কল করে আসল model instance
resolve করে controller-কে পাস করে দেয়। ID খুঁজে না পেলে বা না দিলে পরিষ্কার একটা error
(`"User with id [X] was not found."` / `"Missing required identifier..."`) থ্রো হয়,
যেটা AI-কে ফিরিয়ে দেওয়া যায়।

সাধারণ `show(int $id)` / `update(int $id, Request $request)` / `destroy(int $id)`
স্টাইলও আগে থেকেই কাজ করত, এবং এখন schema-তে এদের description-ও পরিষ্কার করে দেওয়া
হয়েছে ("The unique ID of the record to find, update, or delete.")।

সবগুলো pattern manual reflection-based টেস্ট দিয়ে verify করা হয়েছে (FormRequest,
Controller+Service, `$fillable` fallback, এবং route-model-binding find/missing-id/
not-found কেস)।

## ৪. `index()` মেথডে filter (status/category/search ইত্যাদি) discover করা

`ControllerRequestParameterAnalyzer` এখন Laravel-এর filter লেখার তিনটা কমন স্টাইলই
ধরতে পারে:

```php
$request->query('status');        // explicit accessor call - আগে থেকেই সাপোর্টেড
$request->status;                 // "magic property" access - নতুন যোগ হলো
$request->has('search');          // existence-check
```

`$request->status`, `$request->category_id`-এর মতো bare property access (যেটা আসলে
`$request->input('status')`-এরই shortcut, Laravel-এর নিজস্ব magic method দিয়ে কাজ
করে) — এটা খুবই কমন pattern, তাই এখন এটাও schema-তে discover হয়। সাথে
`$request->route()`, `$request->user()`-এর মতো Request-এর নিজস্ব real
method/property গুলো একটা explicit denylist দিয়ে বাদ দেওয়া হয়, যাতে সেগুলো ভুল করে
filter field হিসেবে ধরা না পড়ে।

**এখনো যেটা কাজ করবে না** (static analysis দিয়ে সম্ভব না):
- Dynamic/loop-ভিত্তিক filtering: `foreach ($request->query() as $key => $value) { $query->where($key, $value); }` — এখানে field-এর নাম কোথাও literal হিসেবে লেখা নেই, তাই কিছুই discover করা যাবে না। এমন pattern থাকলে জানাবেন, allowed-filter list config দিয়ে handle করার ব্যবস্থা করা যাবে।
- Nested/bracket-style filter object: `?filter[status]=active&filter[category]=x` (`$request->query('filter')` একটা array রিটার্ন করে) — বর্তমানে এটা `filter` নামে একটাই STRING/ARRAY field হিসেবে ধরা পড়বে, কিন্তু ভেতরের `status`/`category` আলাদা করে বোঝা যাবে না।

## ৫. আজকের দুইটা জরুরি বাগ ফিক্স (production error থেকে ধরা পড়েছে)

### ৫.১. `422 Unprocessable Content` — Gemini "items: missing field" এরর

**কারণ:** আমাদের validation-rule parser যখন `'ids' => 'required|array'`-এর মতো rule
দেখে একটা parameter-কে ARRAY type হিসেবে ধরে, তখন Gemini-র schema-তে সেই
ARRAY-টাইপ property-র জন্য একটা `items` sub-schema **বাধ্যতামূলক** — আমরা সেটা কোথাও
সেট করছিলাম না। ফলাফল: request-এ যতগুলো tool ছিল, ARRAY parameter-ওয়ালা একটা tool
ভুল থাকলেই Gemini **পুরো request-টাই** reject করে দিচ্ছিল (422) — এমনকি আপনার
"packagings export" প্রম্পটের সাথে সম্পর্কহীন একটা fallback tool-এর কারণেও।

**ফিক্স:** এখন pipeline-এর প্রতিটা ধাপে (`ControllerRequestParameterAnalyzer` →
`CachedControllerToolProvider` → `LegacyControllerToolAdapter` → `ToolDefinition` →
`GeminiAiProvider`) যেকোনো ARRAY-টাইপ property-র জন্য সবসময় একটা valid `items`
schema attach করা হয়। Laravel-এর wildcard validation rule-ও এখন বোঝে:
```php
'ids' => 'required|array',
'ids.*' => 'integer',   // <- এখন এটা থেকে items.type = INTEGER ধরা হয়
```
যদি wildcard rule না থাকে, default হিসেবে `items: {type: STRING}` বসে, যাতে Gemini
কখনোই এই কারণে পুরো request reject না করে। পুরো chain (analyzer → declaration →
Gemini payload) test করে `items` ঠিকভাবে propagate হওয়া confirm করা হয়েছে।

### ৫.২. Excel export (`export=excel`) JSON হয়ে ভেঙে যাচ্ছিল

**কারণ:** `AiController`-এ `BinaryFileResponse` চেক করার dead code ছিল, কিন্তু
`AiService::chat()` সবসময় একটা plain array রিটার্ন করত — কখনো raw file response না।
এর উপরে, `ToolResult::fromRaw()` **যেকোনো** `Response` অবজেক্টের উপর
`getContent()` + `json_decode()` চালানোর চেষ্টা করত, যেটা Excel/PDF-এর মতো binary
content-কে silently ভেঙে ফেলত।

**ফিক্স:**
- `ToolResult` এখন `BinaryFileResponse`, `StreamedResponse`, এবং
  `Content-Disposition: attachment` header বা binary content-type (xlsx/csv/pdf/zip)
  থাকা যেকোনো `Response`-কে চিনতে পারে ও অবিকৃত অবস্থায় pass-through করে
  (নতুন `isBinary()` / `binaryResponse` property দিয়ে)।
- `AiService::chat()` — file download হলে এখন raw `Response` object সরাসরি রিটার্ন
  করে, JSON-wrap করে না।
- `AiController::handle()` — এখন যেকোনো `Symfony\Component\HttpFoundation\Response`
  চেক করে (শুধু `BinaryFileResponse` না), তাই `Excel::download()`,
  `response()->streamDownload()`, বা custom attachment header দেওয়া যেকোনো
  download সরাসরি ব্রাউজারে ফাইল হিসেবে চলে যাবে।

Controller-এ `export=excel` চেক করার প্যাটার্ন (`$request->query('export') ===
'excel'`) স্বয়ংক্রিয়ভাবেই schema-তে discover হয়ে যাবে (৪ নং সেকশনের মতোই), তাই
আলাদা কোনো কনফিগারেশন লাগবে না — শুধু controller এভাবে লিখুন:
```php
public function index(Request $request)
{
    $query = Packaging::query();
    // ...filters...

    if ($request->query('export') === 'excel') {
        return Excel::download(new PackagingsExport($query), 'packagings.xlsx');
    }

    return $query->paginate($request->integer('per_page', 15));
}
```

## ৬. FormRequest ব্যবহার করলে "Call to a member function call() on null"

**কারণ (নিশ্চিত করা হয়েছে, exact reproduce করে):** যখন controller method একটা dedicated
FormRequest নেয় (যেমন `update(UpdateCategoryRequest $request, string $id)`), আমাদের
adapter সেটা `new UpdateCategoryRequest()` দিয়ে বানায়, কিন্তু কখনো
`setContainer()` কল করত না। Laravel-এর নিজস্ব `FormRequest::validateResolved()`-এর
প্রথম কাজই হলো `authorize()` মেথড থাকলে সেটা চালানো:
```php
// Laravel-এর নিজস্ব কোড (FormRequest::passesAuthorization())
return $this->container->call([$this, 'authorize']);
```
`$this->container` কখনো সেট না হওয়ায় (`null` থেকে যাওয়ায়), এটাই crash করে —
আক্ষরিক অর্থেই **"Call to a member function call() on null"**। এটা আপনার
`CategoryController`-এর try/catch ব্লকের **আগেই** ঘটে (FormRequest resolve হওয়ার
সময়েই), তাই আপনার নিজের error-handling কোড এটা catch করতে পারছিল না।

**ফিক্স:** `LegacyControllerToolAdapter::execute()`-এ এখন FormRequest বানানোর পর
`setContainer(app())` (এবং safety-র জন্য `setRedirector()`) কল করা হয় —
ঠিক যেভাবে Laravel-এর নিজস্ব router একটা real HTTP request-এ FormRequest resolve
করার সময় করে। আপনার আসল `UpdateCategoryRequest` কোড (যেটা সম্পূর্ণ standard ছিল)
দিয়েই এই বাগ reproduce করে, ফিক্স apply করে, আবার ফিক্স ছাড়া চালিয়ে exact same error
message ("Call to a member function call() on null") পুনরায় পাওয়া গেছে — অর্থাৎ
root cause ১০০% নিশ্চিত।

এখন FormRequest ব্যবহার করা যেকোনো `store`/`update` endpoint (`authorize()` +
`rules()` দুটোই থাকা স্ট্যান্ডার্ড FormRequest, Controller+Service pattern সহ)
সঠিকভাবে কাজ করবে।

