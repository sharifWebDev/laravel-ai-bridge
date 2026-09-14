<?php

namespace Sharifuddin\LaravelAiBridge\DTO;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Normalized, token-efficient result of a tool execution. Large result
 * sets are truncated to $maxRecords before ever reaching the AI model or
 * the HTTP response, with pagination metadata preserved so the model can
 * still reason about totals.
 *
 * File/binary downloads (Excel/PDF/CSV exports, e.g. `Excel::download()`,
 * `response()->streamDownload()`, `Storage::download()`) are a special
 * case: their raw Response object is captured as-is in $binaryResponse
 * (see isBinary()) rather than being read/decoded, since attempting to
 * treat binary content as JSON - as a naive "read every Response's
 * content" implementation would - silently corrupts the file.
 */
final class ToolResult
{
    /**
     * @param array<int|string, mixed> $data
     * @param array<string, mixed> $meta
     */
    private function __construct(
        public readonly bool $success,
        public readonly array $data,
        public readonly array $meta,
        public readonly ?string $error = null,
        public readonly ?Response $binaryResponse = null,
    ) {
    }

    public static function fromRaw(mixed $raw, int $maxRecords = 50): self
    {
        if ($raw instanceof self) {
            return $raw;
        }

        if ($raw instanceof BinaryFileResponse || $raw instanceof StreamedResponse || self::looksLikeFileDownload($raw)) {
            // Never call getContent() on these: a BinaryFileResponse reads
            // from a file path (and may throw if that temp file is already
            // gone by the time this runs), and a StreamedResponse's
            // "content" is a callback, not a string - either way, the
            // actual bytes belong to the HTTP layer, not to a JSON payload.
            return new self(true, [], [], null, $raw);
        }

        if ($raw instanceof Arrayable) {
            $raw = $raw->toArray();
        }

        if ($raw instanceof Collection) {
            $raw = $raw->all();
        }

        if ($raw instanceof \Illuminate\Http\JsonResponse) {
            $raw = $raw->getData(true);
        } elseif ($raw instanceof Response) {
            $content = $raw->getContent();
            if (is_string($content) && $content !== '') {
                $decoded = json_decode($content, true);
                $raw = json_last_error() === JSON_ERROR_NONE ? $decoded : $content;
            }
        }

        if ($raw instanceof \JsonSerializable) {
            $raw = $raw->jsonSerialize();
        }

        // Laravel paginators expose ->items()/->total() etc. via toArray()
        // already handled above; plain arrays/scalars fall through here.
        if (is_array($raw) && array_is_list($raw)) {
            $total = count($raw);
            $truncated = array_slice($raw, 0, $maxRecords);

            return new self(true, $truncated, [
                'total' => $total,
                'returned' => count($truncated),
                'truncated' => $total > count($truncated),
            ]);
        }

        if (is_array($raw)) {
            // Associative array: treat 'data'/'meta' keys specially when present
            // (e.g. already-paginated results), otherwise wrap as a single record.
            if (array_key_exists('data', $raw)) {
                $items = is_array($raw['data']) ? $raw['data'] : [$raw['data']];
                $meta = is_array($raw['meta'] ?? null) ? $raw['meta'] : [];
                $total = $meta['total'] ?? count($items);
                $truncated = array_slice(array_values($items), 0, $maxRecords);

                return new self(true, $truncated, $meta + [
                    'total' => $total,
                    'returned' => count($truncated),
                    'truncated' => $total > count($truncated),
                ]);
            }

            return new self(true, [$raw], ['total' => 1, 'returned' => 1, 'truncated' => false]);
        }

        return new self(true, [$raw], ['total' => 1, 'returned' => 1, 'truncated' => false]);
    }

    public static function failure(string $message): self
    {
        return new self(false, [], [], $message);
    }

    /**
     * True when this result is a file download that must be returned to
     * the HTTP layer untouched (see binaryResponse) instead of being
     * JSON-encoded via toArray().
     */
    public function isBinary(): bool
    {
        return $this->binaryResponse !== null;
    }

    /**
     * A plain Response (not BinaryFileResponse/StreamedResponse) is a file
     * download when it carries the tell-tale signs of one: a
     * Content-Disposition: attachment header (set by every one of
     * Laravel's own download helpers), or a binary/export Content-Type
     * (xlsx/xls/csv/pdf/zip/octet-stream) - checked instead of assuming
     * "any Content-Type other than JSON must be plain text".
     */
    private static function looksLikeFileDownload(mixed $raw): bool
    {
        if (!$raw instanceof Response) {
            return false;
        }

        $disposition = (string) $raw->headers->get('Content-Disposition', '');
        if (str_contains($disposition, 'attachment')) {
            return true;
        }

        $contentType = strtolower((string) $raw->headers->get('Content-Type', ''));

        return $contentType !== '' && (
            str_contains($contentType, 'spreadsheet')
            || str_contains($contentType, 'ms-excel')
            || str_contains($contentType, 'csv')
            || str_contains($contentType, 'pdf')
            || str_contains($contentType, 'zip')
            || str_contains($contentType, 'octet-stream')
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        if (!$this->success) {
            return ['success' => false, 'message' => $this->error];
        }

        return [
            'success' => true,
            'data' => $this->data,
            'meta' => $this->meta,
        ];
    }
}
