<?php

namespace App\QodeModules;

use App\Enums\QodeType;
use App\Models\Qode;
use App\Support\QodeUrlBuilder;
use Illuminate\Validation\ValidationException;

class RedirectDestination
{
    public const MODE_URL = 'url';

    public const MODE_QODE = 'qode';

    public function __construct(
        private QodeUrlBuilder $urls,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'destination' => self::MODE_URL,
            'url' => 'https://example.com',
            'target_qode_id' => null,
        ];
    }

    /**
     * Resolve the final absolute URL for a Redirect Qode. Always one hop.
     * Target Qodes must not themselves be Redirect (no cascade / loops).
     */
    public function urlFor(Qode $qode): string
    {
        $settings = $qode->settings ?? [];
        $mode = (string) ($settings['destination'] ?? self::MODE_URL);

        if ($mode === self::MODE_QODE) {
            $target = $this->findAllowedTarget(
                tenantId: (int) $qode->tenant_id,
                targetId: isset($settings['target_qode_id']) ? (int) $settings['target_qode_id'] : null,
                excludeId: (int) $qode->id,
            );

            if ($target === null) {
                abort(404);
            }

            return $this->urls->forQode($target);
        }

        $url = trim((string) ($settings['url'] ?? ''));

        if ($url === '') {
            abort(404);
        }

        return $url;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function validateForSave(Qode|int|null $source, array $settings): array
    {
        $mode = (string) ($settings['destination'] ?? self::MODE_URL);
        $sourceId = $source instanceof Qode ? (int) $source->id : (is_int($source) ? $source : null);
        $tenantId = $source instanceof Qode
            ? (int) $source->tenant_id
            : (int) (auth()->user()?->tenant_id ?? 0);

        if ($mode === self::MODE_QODE) {
            $targetId = isset($settings['target_qode_id']) ? (int) $settings['target_qode_id'] : null;

            if ($targetId === null || $targetId === 0) {
                throw ValidationException::withMessages([
                    'settings.target_qode_id' => 'Select a Qode to redirect to.',
                ]);
            }

            if ($sourceId !== null && $targetId === $sourceId) {
                throw ValidationException::withMessages([
                    'settings.target_qode_id' => 'A Qode cannot redirect to itself.',
                ]);
            }

            $target = $this->findAllowedTarget($tenantId, $targetId, $sourceId);

            if ($target === null) {
                throw ValidationException::withMessages([
                    'settings.target_qode_id' => 'Choose an active non-redirect Qode from your account. Redirect-to-redirect is not allowed.',
                ]);
            }

            $settings['target_qode_id'] = $target->id;
            $settings['destination'] = self::MODE_QODE;

            return $settings;
        }

        $url = trim((string) ($settings['url'] ?? ''));

        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw ValidationException::withMessages([
                'settings.url' => 'Enter a valid destination URL.',
            ]);
        }

        $settings['destination'] = self::MODE_URL;
        $settings['url'] = $url;

        return $settings;
    }

    /**
     * Options for the Filament searchable select (non-redirect Qodes only).
     *
     * @return array<int, string>
     */
    public function searchableOptions(int $tenantId, ?int $excludeId, string $search = ''): array
    {
        $query = Qode::query()
            ->where('tenant_id', $tenantId)
            ->where('type', '!=', QodeType::Redirect->value)
            ->when($excludeId !== null, fn ($q) => $q->whereKeyNot($excludeId))
            ->orderByDesc('id')
            ->limit(50);

        $search = trim($search);

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%');
            });
        }

        return $query->get()
            ->mapWithKeys(fn (Qode $qode): array => [
                $qode->id => $qode->name.' ('.$qode->slug.')',
            ])
            ->all();
    }

    public function optionLabel(int $tenantId, ?int $excludeId, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $target = $this->findAllowedTarget($tenantId, (int) $value, $excludeId);

        if ($target === null) {
            return null;
        }

        return $target->name.' ('.$target->slug.')';
    }

    private function findAllowedTarget(int $tenantId, ?int $targetId, ?int $excludeId): ?Qode
    {
        if ($targetId === null || $targetId === 0 || $tenantId === 0) {
            return null;
        }

        if ($excludeId !== null && $targetId === $excludeId) {
            return null;
        }

        $target = Qode::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($targetId)
            ->first();

        if ($target === null) {
            return null;
        }

        // No redirect→redirect (blocks cascades and loops).
        if ($target->type === QodeType::Redirect) {
            return null;
        }

        if (! $target->isActive()) {
            return null;
        }

        return $target;
    }
}
