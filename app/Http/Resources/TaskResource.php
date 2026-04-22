<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    private const INDEX_DESCRIPTION_PREVIEW_LIMIT = 100;

    /**
     * Serialize a comment and its nested replies.
     *
     * @return array<string, mixed>
     */
    private function serializeComment($comment): array
    {
        return [
            'id' => $comment->id,
            'body' => $comment->body,
            'parent_id' => $comment->parent_id,
            'created_at' => $comment->created_at?->toIso8601String(),
            'user' => [
                'id' => $comment->user->id,
                'name' => $comment->user->name,
            ],
            'replies' => $comment->relationLoaded('replies')
                ? $comment->replies->map(fn($reply) => $this->serializeComment($reply))->values()
                : [],
        ];
    }

    /**
     * Build a short HTML preview that keeps rich text markup intact.
     */
    private function buildDescriptionPreview(string $html, int $limit): ?string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previousUseInternalErrors = libxml_use_internal_errors(true);

        try {
            $dom->loadHTML(
                '<?xml encoding="utf-8" ?><div id="preview">' . $html . '</div>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );

            $container = $dom->getElementById('preview');

            if (!$container) {
                return null;
            }

            $remaining = $limit;
            $preview = $dom->createElement('div');

            foreach (iterator_to_array($container->childNodes) as $child) {
                if ($remaining <= 0) {
                    break;
                }

                $preview->appendChild($this->clonePreviewNode($dom, $child, $remaining));
            }

            $htmlPreview = '';

            foreach ($preview->childNodes as $child) {
                $htmlPreview .= $dom->saveHTML($child);
            }

            return trim($htmlPreview) !== '' ? $htmlPreview : null;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseInternalErrors);
        }
    }

    /**
     * Clone a node while respecting a text limit.
     */
    private function clonePreviewNode(\DOMDocument $dom, \DOMNode $node, int &$remaining): \DOMNode
    {
        if ($node instanceof \DOMText) {
            $text = $node->wholeText;

            if (mb_strlen($text) <= $remaining) {
                $remaining -= mb_strlen($text);

                return $dom->createTextNode($text);
            }

            $snippet = mb_substr($text, 0, $remaining);
            $remaining = 0;

            return $dom->createTextNode(rtrim($snippet) . '...');
        }

        $clone = $dom->createElement($node->nodeName);

        if ($node->attributes) {
            foreach ($node->attributes as $attribute) {
                $clone->setAttribute($attribute->nodeName, $attribute->nodeValue);
            }
        }

        foreach (iterator_to_array($node->childNodes) as $childNode) {
            if ($remaining <= 0) {
                break;
            }

            $clone->appendChild($this->clonePreviewNode($dom, $childNode, $remaining));
        }

        return $clone;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Calculate historical + active seconds for current column properly
        $currentSeconds = $this->column_updated_at ? (int) abs($this->column_updated_at->diffInSeconds(now())) : 0;
        $history = $this->time_spent_in_columns ?? [];
        $historicalSeconds = $history[$this->column_id] ?? 0;

        $totalSeconds = $historicalSeconds + $currentSeconds;
        $daysInColumn = (int) floor($totalSeconds / 86400);

        $description = $this->description;

        if (
            ($request->routeIs('tasks.index') || $request->routeIs('columns.tasks.index'))
            && is_string($this->description)
        ) {
            $description = $this->buildDescriptionPreview(
                $this->description,
                self::INDEX_DESCRIPTION_PREVIEW_LIMIT
            );
        }

        return [
            'id' => $this->id,
            'team_id' => $this->team_id,
            'column_id' => $this->column_id,
            'order' => $this->order,
            'title' => $this->title,
            'description' => $description,
            'days_in_column' => $daysInColumn,
            'due_date' => $this->due_date,
            'created_by' => $this->created_by,
            'assigned_to' => $this->assigned_to,
            'creator' => $this->whenLoaded('creator', fn() => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'assignee' => $this->whenLoaded('assignee', fn() => [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
            ]),
            'comments' => $this->whenLoaded(
                'comments',
                fn() => $this->comments
                    ->map(fn($comment) => $this->serializeComment($comment))
                    ->values()
            ),
            'events' => $this->whenLoaded(
                'events',
                fn() => $this->events->map(fn($event) => [
                    'id' => $event->id,
                    'type' => $event->type,
                    'created_at' => $event->created_at?->toIso8601String(),
                    'actor' => $event->relationLoaded('actor') && $event->actor
                        ? [
                            'id' => $event->actor->id,
                            'name' => $event->actor->name,
                        ]
                        : null,
                    'metadata' => $event->metadata ?? [],
                ])->values(),
            ),
        ];
    }
}
