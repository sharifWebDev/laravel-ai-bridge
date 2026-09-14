<?php

namespace Sharifuddin\LaravelAiBridge\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Plain ReflectionMethod::getParameters() only sees a method's *typed
 * signature* (e.g. `index(Request $request)`), never the query/filter/body
 * parameters the method actually reads out of that Request - whether
 * directly in its own body, via a dedicated FormRequest's rules(), or via
 * mass-assignment against an Eloquent model's $fillable. For typical
 * Laravel endpoints this means the AI-facing tool schema can end up
 * completely empty, so:
 *
 *  - the AI is never told a `per_page`/`page`/`search`/`sort_by`/`name`/
 *    `email`/... field exists at all, so it can't fill one in even when
 *    the user explicitly asks for e.g. "top 20" or "create a user named X";
 *  - ArgumentValidator whitelists arguments strictly against the declared
 *    schema, so even if the AI guessed a parameter name, it gets silently
 *    dropped before execution.
 *
 * This analyzer statically scans - in priority order - three common
 * Laravel data-entry sources so all of them are covered regardless of
 * which convention a given controller/service follows:
 *
 *  1) inline `$request->query()/get()/input()/has()/filled()/boolean()/
 *     integer()/float()/string()/only()/validate([...])` calls in the
 *     controller method's own body (works whether the validated/only()
 *     data is then used directly or forwarded into a Service class -
 *     "Controller + Service" pattern);
 *  2) a dedicated FormRequest's `rules()` method, for controllers that
 *     type-hint a custom FormRequest instead of validating inline (there
 *     is no `->validate(...)` call to find in the controller itself, since
 *     Laravel resolves and validates the FormRequest automatically);
 *  3) an Eloquent model's `$fillable` array, as a last-resort fallback for
 *     controllers that just do `Model::create($request->all())` with no
 *     validation at all.
 *
 * It never executes user code (Eloquent models are instantiated only to
 * read their $fillable/$casts, never persisted) and is best-effort: a
 * pattern it doesn't recognize is simply skipped, it never throws.
 */
final class ControllerRequestParameterAnalyzer
{
    /**
     * Request's own real methods/properties - deliberately excluded from
     * discoverMagicPropertyAccess() so e.g. `$request->route()` or
     * `$request->user()` never get mistaken for a filter/input field.
     */
    private const RESERVED_REQUEST_MEMBERS = [
        'query', 'request', 'attributes', 'cookies', 'files', 'server', 'headers',
        'json', 'all', 'only', 'except', 'input', 'get', 'has', 'hasAny', 'filled',
        'missing', 'boolean', 'integer', 'float', 'string', 'date', 'enum',
        'validate', 'validateWithBag', 'safe', 'collect', 'session', 'user',
        'route', 'path', 'decodedPath', 'url', 'fullUrl', 'fullUrlWithQuery',
        'fullUrlWithoutQuery', 'is', 'fullUrlIs', 'method', 'isMethod', 'ip', 'ips',
        'userAgent', 'header', 'hasHeader', 'bearerToken', 'ajax', 'pjax',
        'prefetch', 'secure', 'wantsJson', 'expectsJson', 'acceptsJson', 'accepts',
        'format', 'file', 'hasFile', 'allFiles', 'merge', 'mergeIfMissing',
        'replace', 'flash', 'flashOnly', 'flashExcept', 'old', 'flush',
        'setUserResolver', 'getUserResolver', 'authorize', 'rules', 'messages',
        'withValidator', 'validated', 'validateResolved', 'toArray',
    ];

    /**
     * @param string|null $modelClassHint fully-qualified Eloquent model class to fall back to
     *        (via $fillable) when nothing else was discovered - see priority (3) above.
     * @return array<string, array{type: string, description: string, required?: bool, default?: mixed}>
     */
    public function discover(ReflectionMethod $method, ?string $modelClassHint = null): array
    {
        $discovered = [];

        $source = $this->methodSource($method);
        $requestVar = $this->requestVariableName($method);

        if ($source !== null && $requestVar !== null) {
            $this->discoverAccessorCalls($source, $requestVar, $discovered);
            $this->discoverOnlyCalls($source, $requestVar, $discovered);
            $this->discoverMagicPropertyAccess($source, $requestVar, $discovered);
            $this->discoverValidateRules($source, $requestVar, $discovered);
        }

        // FormRequest rules are authoritative for whatever fields they
        // cover, so let them override any same-named heuristic guess above.
        foreach ($this->discoverFromFormRequestRules($method) as $name => $entry) {
            $discovered[$name] = $entry;
        }

        // Last resort: nothing found anywhere above, but this endpoint
        // clearly accepts a Request/FormRequest body - fall back to the
        // target model's mass-assignable fields.
        if (empty($discovered) && $requestVar !== null && $modelClassHint !== null) {
            $discovered = $this->discoverFromModelFillable($modelClassHint);
        }

        return $discovered;
    }

    /**
     * Returns the raw PHP source text spanning the method's body, or null
     * if it can't be read (e.g. no file, a native/compiled method).
     */
    private function methodSource(ReflectionMethod $method): ?string
    {
        $file = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        if (!$file || !$startLine || !$endLine || !is_readable($file)) {
            return null;
        }

        $lines = @file($file);
        if ($lines === false) {
            return null;
        }

        $slice = array_slice($lines, $startLine - 1, max(1, $endLine - $startLine + 1));

        return implode('', $slice);
    }

    /**
     * Finds the variable name of the Request/FormRequest-typed parameter
     * on the method signature (usually "request"), which is what we
     * anchor all the accessor-call regexes to.
     */
    private function requestVariableName(ReflectionMethod $method): ?string
    {
        foreach ($method->getParameters() as $param) {
            $type = $param->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $typeName = $type->getName();
                if ($typeName === Request::class || is_subclass_of($typeName, Request::class)) {
                    return $param->getName();
                }
            }
        }

        return null;
    }

    /**
     * Matches `$request->query('name', default)`, `->get(...)`,
     * `->input(...)`, `->has(...)`, `->filled(...)`, `->boolean(...)`,
     * `->integer(...)`, `->float(...)`, `->string(...)`.
     *
     * @param array<string, array<string, mixed>> $discovered
     */
    private function discoverAccessorCalls(string $source, string $requestVar, array &$discovered): void
    {
        $accessors = ['query', 'get', 'input', 'has', 'filled', 'boolean', 'integer', 'float', 'string'];
        $accessorPattern = implode('|', $accessors);

        $pattern = '/\$' . preg_quote($requestVar, '/') . '->(' . $accessorPattern . ')\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]\s*(?:,\s*([^)]+?))?\s*\)/';

        if (!preg_match_all($pattern, $source, $matches, PREG_SET_ORDER)) {
            return;
        }

        foreach ($matches as $match) {
            $accessor = $match[1];
            $name = $match[2];
            $defaultExpr = isset($match[3]) ? trim($match[3]) : null;

            if (isset($discovered[$name])) {
                continue;
            }

            $type = $this->typeFromAccessor($accessor, $name);
            $entry = [
                'type' => $type,
                'description' => $this->describeParameter($name, $type),
            ];

            $default = $this->literalFromExpression($defaultExpr);
            if ($default !== null) {
                $entry['default'] = $default;
            }

            $discovered[$name] = $entry;
        }
    }

    /**
     * Matches `$request->only(['a', 'b', 'c'])` / `$request->only('a', 'b')`.
     *
     * @param array<string, array<string, mixed>> $discovered
     */
    private function discoverOnlyCalls(string $source, string $requestVar, array &$discovered): void
    {
        $pattern = '/\$' . preg_quote($requestVar, '/') . '->only\(([^)]*)\)/';

        if (!preg_match_all($pattern, $source, $matches)) {
            return;
        }

        foreach ($matches[1] as $argsExpr) {
            preg_match_all('/[\'"]([a-zA-Z0-9_]+)[\'"]/', $argsExpr, $nameMatches);

            foreach ($nameMatches[1] as $name) {
                if (isset($discovered[$name])) {
                    continue;
                }

                $discovered[$name] = [
                    'type' => 'STRING',
                    'description' => $this->describeParameter($name, 'STRING'),
                ];
            }
        }
    }

    /**
     * Matches Laravel's "magic property" access on a Request - e.g.
     * `$request->status`, `$request->search` - which is a very common,
     * even more casual alternative to `$request->input('status')` and
     * behaves identically at runtime (Request::__get() delegates to
     * input()). Excludes Request's own real methods/properties (query,
     * validate, user, route, ...) via an explicit denylist, since those
     * are framework internals rather than filter/input fields.
     *
     * Uses PREG_OFFSET_CAPTURE and a manual "is the next char '('?" check
     * (rather than a regex negative lookahead) to decide whether this is a
     * property access or a method call - a lookahead here would let the
     * engine backtrack the identifier itself to satisfy it, e.g. matching
     * "ha" out of "has(" instead of correctly skipping the whole call.
     *
     * @param array<string, array<string, mixed>> $discovered
     */
    private function discoverMagicPropertyAccess(string $source, string $requestVar, array &$discovered): void
    {
        $pattern = '/\$' . preg_quote($requestVar, '/') . '->([a-zA-Z_][a-zA-Z0-9_]*)/';

        if (!preg_match_all($pattern, $source, $matches, PREG_OFFSET_CAPTURE)) {
            return;
        }

        foreach ($matches[1] as [$name, $offset]) {
            $afterOffset = $offset + strlen($name);
            if (($source[$afterOffset] ?? null) === '(') {
                continue; // it's a method call, e.g. $request->has(...), not a property
            }

            if (isset($discovered[$name]) || in_array($name, self::RESERVED_REQUEST_MEMBERS, true)) {
                continue;
            }

            $type = $this->typeFromAccessor('string', $name);
            $discovered[$name] = [
                'type' => $type,
                'description' => $this->describeParameter($name, $type),
            ];
        }
    }

    /**
     * Matches `$request->validate([...])` calls in the controller method's
     * own body.
     *
     * @param array<string, array<string, mixed>> $discovered
     */
    private function discoverValidateRules(string $source, string $requestVar, array &$discovered): void
    {
        $offset = 0;
        while (preg_match('/->validate\(/', $source, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $callStart = $m[0][1] + strlen($m[0][0]);
            $block = $this->extractBalanced($source, $callStart - 1, '(', ')');
            $offset = $callStart;

            if ($block === null) {
                continue;
            }

            foreach ($this->parseRuleAssignments($block) as $name => $entry) {
                $discovered[$name] = $entry;
            }
        }
    }

    /**
     * For a method that type-hints a dedicated FormRequest (a Request
     * subclass other than the base Illuminate\Http\Request), reflects that
     * class's own `rules()` method and parses its returned array literal -
     * there is no `->validate(...)` call to find anywhere, since Laravel
     * validates FormRequests automatically before the controller method
     * body ever runs.
     *
     * Only the common `return [ ... ];` shape is handled; a computed or
     * conditional rule set is left alone rather than risking an incorrect
     * guess.
     *
     * @return array<string, array{type: string, description: string, required?: bool}>
     */
    private function discoverFromFormRequestRules(ReflectionMethod $method): array
    {
        foreach ($method->getParameters() as $param) {
            $type = $param->getType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $typeName = $type->getName();
            if ($typeName === Request::class || !is_subclass_of($typeName, Request::class)) {
                continue; // only a dedicated FormRequest subclass, not the base Request
            }

            if (!method_exists($typeName, 'rules')) {
                continue;
            }

            try {
                $rulesMethod = new ReflectionMethod($typeName, 'rules');
            } catch (\ReflectionException) {
                continue;
            }

            $rulesSource = $this->methodSource($rulesMethod);
            if ($rulesSource === null || !preg_match('/return\s*\[/', $rulesSource, $m, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            $bracketOffset = $m[0][1] + strlen($m[0][0]) - 1; // position of the '['
            $block = $this->extractBalanced($rulesSource, $bracketOffset, '[', ']');

            if ($block === null) {
                continue;
            }

            return $this->parseRuleAssignments($block);
        }

        return [];
    }

    /**
     * Last-resort fallback: no validation of any kind was found, but the
     * endpoint accepts a Request body - reflect the target Eloquent
     * model's $fillable (and $casts, for better type inference) instead.
     * The model is only ever instantiated in memory, never queried or
     * persisted.
     *
     * @return array<string, array{type: string, description: string}>
     */
    private function discoverFromModelFillable(string $modelClass): array
    {
        if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
            return [];
        }

        try {
            $model = new $modelClass();
        } catch (\Throwable) {
            return [];
        }

        $fillable = method_exists($model, 'getFillable') ? $model->getFillable() : [];
        if (empty($fillable)) {
            return [];
        }

        $casts = method_exists($model, 'getCasts') ? $model->getCasts() : [];
        $shortName = class_basename($modelClass);

        $result = [];
        foreach ($fillable as $field) {
            $cast = strtolower((string) ($casts[$field] ?? ''));
            $type = match (true) {
                str_contains($cast, 'int') => 'INTEGER',
                str_contains($cast, 'float') || str_contains($cast, 'double') || str_contains($cast, 'decimal') => 'NUMBER',
                str_contains($cast, 'bool') => 'BOOLEAN',
                str_contains($cast, 'array') || str_contains($cast, 'json') || str_contains($cast, 'collection') => 'ARRAY',
                default => $this->typeFromAccessor('string', $field),
            };

            $result[$field] = [
                'type' => $type,
                'description' => "Field \"{$field}\" of {$shortName} (auto-discovered from its \$fillable), type: " . strtolower($type) . '.',
            ];

            if ($type === 'ARRAY') {
                $result[$field]['items'] = ['type' => 'STRING'];
            }
        }

        return $result;
    }

    /**
     * Parses a shallow 'field' => 'rules' (or ['rules']) map out of a
     * validate()/rules() array-literal block, inferring type + required
     * from the rule string(s).
     *
     * Handles Laravel's array-validation convention specially: a rule key
     * like 'ids.*' describes the TYPE OF EACH ITEM in the 'ids' array
     * (e.g. 'ids' => 'required|array', 'ids.*' => 'integer'), not a
     * separate field - so it's folded into 'ids' own 'items' sub-schema
     * rather than overwriting 'ids' itself. Every ARRAY-typed field is
     * guaranteed to end up with an 'items' entry (defaulting to STRING
     * when no wildcard rule was present) since some providers (Gemini)
     * reject an array-type schema property that has no 'items' at all.
     *
     * @return array<string, array{type: string, description: string, required?: bool, items?: array{type: string}}>
     */
    private function parseRuleAssignments(string $block): array
    {
        $result = [];
        $itemTypes = [];

        preg_match_all(
            '/[\'"]([a-zA-Z0-9_.\*]+)[\'"]\s*=>\s*(\[[^\[\]]*\]|[\'"][^\'"]*[\'"])/',
            $block,
            $ruleMatches,
            PREG_SET_ORDER
        );

        foreach ($ruleMatches as $ruleMatch) {
            $rawName = $ruleMatch[1];
            $rulesText = strtolower($ruleMatch[2]);
            $ruleType = $this->typeFromRuleText($rulesText);

            if (preg_match('/^(.+)\.\*$/', $rawName, $wildcardMatch)) {
                // e.g. 'ids.*' => 'integer' - describes each ITEM of the
                // 'ids' array, not a field of its own.
                $itemTypes[$wildcardMatch[1]] = $ruleType;
                continue;
            }

            $name = strtok($rawName, '.'); // "filters.status" -> "filters"

            $entry = $result[$name] ?? [
                'type' => $ruleType,
                'description' => $this->describeParameter($name, $ruleType),
            ];
            $entry['type'] = $ruleType;

            if (str_contains($rulesText, 'required') && !str_contains($rulesText, 'required_if') && !str_contains($rulesText, 'required_with')) {
                $entry['required'] = true;
            }

            $result[$name] = $entry;
        }

        foreach ($result as $name => $entry) {
            if ($entry['type'] === 'ARRAY') {
                $result[$name]['items'] = ['type' => $itemTypes[$name] ?? 'STRING'];
            }
        }

        // A wildcard rule ('ids.*' => ...) always implies its base field is
        // an array, even if no explicit 'ids' => 'array' rule was present.
        foreach ($itemTypes as $name => $itemType) {
            if (!isset($result[$name])) {
                $result[$name] = [
                    'type' => 'ARRAY',
                    'description' => $this->describeParameter($name, 'ARRAY'),
                    'items' => ['type' => $itemType],
                ];
            } elseif ($result[$name]['type'] !== 'ARRAY') {
                $result[$name]['type'] = 'ARRAY';
                $result[$name]['items'] = ['type' => $itemType];
            }
        }

        return $result;
    }

    /**
     * Given the offset of an opening bracket (default "(") returns the
     * substring up to (but excluding) its matching closing bracket, or
     * null if unbalanced.
     */
    private function extractBalanced(string $source, int $openOffset, string $open = '(', string $close = ')'): ?string
    {
        if (($source[$openOffset] ?? null) !== $open) {
            return null;
        }

        $depth = 0;
        $length = strlen($source);

        for ($i = $openOffset; $i < $length; $i++) {
            if ($source[$i] === $open) {
                $depth++;
            } elseif ($source[$i] === $close) {
                $depth--;
                if ($depth === 0) {
                    return substr($source, $openOffset + 1, $i - $openOffset - 1);
                }
            }
        }

        return null;
    }

    private function typeFromRuleText(string $rulesText): string
    {
        return match (true) {
            str_contains($rulesText, 'integer') => 'INTEGER',
            str_contains($rulesText, 'numeric') => 'NUMBER',
            str_contains($rulesText, 'boolean') => 'BOOLEAN',
            str_contains($rulesText, 'array') => 'ARRAY',
            default => 'STRING',
        };
    }

    private function typeFromAccessor(string $accessor, string $name): string
    {
        $byAccessor = match ($accessor) {
            'boolean' => 'BOOLEAN',
            'integer' => 'INTEGER',
            'float' => 'NUMBER',
            default => null,
        };

        if ($byAccessor !== null) {
            return $byAccessor;
        }

        // Heuristics from common Laravel pagination/filter naming when the
        // accessor itself (query/get/input/string) doesn't imply a type.
        if (preg_match('/^(per_page|perpage|page_size|pagesize|limit|top|count)$/i', $name)) {
            return 'INTEGER';
        }

        if (preg_match('/^page$/i', $name)) {
            return 'INTEGER';
        }

        // Foreign-key-style filter names (from_location_id, category_id, ...)
        // are numeric IDs in the overwhelming majority of Laravel apps.
        if (preg_match('/_ids?$/i', $name)) {
            return 'INTEGER';
        }

        return 'STRING';
    }

    private function literalFromExpression(?string $expr): mixed
    {
        if ($expr === null || $expr === '') {
            return null;
        }

        if (preg_match('/^-?\d+$/', $expr)) {
            return (int) $expr;
        }

        if (preg_match('/^-?\d+\.\d+$/', $expr)) {
            return (float) $expr;
        }

        if (in_array(strtolower($expr), ['true', 'false'], true)) {
            return strtolower($expr) === 'true';
        }

        if (preg_match('/^[\'"](.*)[\'"]$/', $expr, $m)) {
            return $m[1];
        }

        // Anything else (a variable, a constant, a method call, null, ...)
        // is not a safe literal to surface as a schema default.
        return null;
    }

    private function describeParameter(string $name, string $type): string
    {
        $normalized = strtolower($name);

        return match (true) {
            in_array($normalized, ['per_page', 'perpage', 'page_size', 'pagesize', 'limit', 'top', 'count'], true) =>
                "Number of records to return per page (pagination size). Use this to satisfy requests like \"show top 20\" or \"list 50 records\".",
            $normalized === 'page' => 'Page number for pagination (1-based).',
            in_array($normalized, ['sort_by', 'sortby', 'order_by', 'orderby', 'sort'], true) => 'Field name to sort the results by.',
            in_array($normalized, ['sort_direction', 'sortdirection', 'order', 'direction'], true) => 'Sort direction, e.g. "asc" or "desc".',
            in_array($normalized, ['search', 'q', 'keyword', 'query', 'term'], true) => 'Free-text search term used to filter the results.',
            $normalized === 'status' => 'Filter results by status value.',
            in_array($normalized, ['export', 'format', 'download'], true) =>
                'Set to "excel" (or another supported format such as "pdf"/"csv") to download the results as a file instead of returning a JSON list.',
            preg_match('/^(from|start)[_-]?date$/', $normalized) === 1 => 'Start date (inclusive) to filter results from, format YYYY-MM-DD.',
            preg_match('/^(to|end)[_-]?date$/', $normalized) === 1 => 'End date (inclusive) to filter results up to, format YYYY-MM-DD.',
            str_ends_with($normalized, '_id') || str_ends_with($normalized, '_ids') =>
                'Filter by the related record\'s ID ("' . $name . '").',
            default => "Query/filter parameter \"{$name}\" (auto-discovered from the controller method, type: " . strtolower($type) . ').',
        };
    }
}
