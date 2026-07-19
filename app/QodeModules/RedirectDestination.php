<?php

namespace App\QodeModules;

use App\Models\Qode;
use App\Support\QodeUrlBuilder;
use Illuminate\Validation\ValidationException;

class RedirectDestination
{
    public const MODE_NONE = 'none';

    public const MODE_URL = 'url';

    public const MODE_QODE = 'qode';

    public function __construct(
        private QodeUrlBuilder $urls,
    ) {}

    /**
     * @return array{redirect: array{to: string, url: string|null, target_qode_id: int|null}}
     */
    public function defaults(): array
    {
        return [
            'redirect' => [
                'to' => self::MODE_NONE,
                'url' => 'https://example.com',
                'target_qode_id' => null,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function modeOptions(): array
    {
        return [
            self::MODE_NONE => "Don't redirect",
            self::MODE_URL => 'External URL',
            self::MODE_QODE => 'Another Qode',
        ];
    }

    public function isRedirecting(Qode $qode): bool
    {
        return $this->mode($qode) !== self::MODE_NONE;
    }

    public function mode(Qode $qode): string
    {
        $to = $qode->settings['redirect']['to'] ?? self::MODE_NONE;

        return in_array($to, [self::MODE_NONE, self::MODE_URL, self::MODE_QODE], true)
            ? $to
            : self::MODE_NONE;
    }

    /**
     * Absolute URL when redirect is enabled; null when "Don't redirect".
     */
    public function urlOrNull(Qode $qode): ?string
    {
        $mode = $this->mode($qode);

        if ($mode === self::MODE_NONE) {
            return null;
        }

        if ($mode === self::MODE_QODE) {
            $target = $this->findAllowedTarget(
                tenantId: (int) $qode->tenant_id,
                targetId: isset($qode->settings['redirect']['target_qode_id'])
                    ? (int) $qode->settings['redirect']['target_qode_id']
                    : null,
                excludeId: (int) $qode->id,
            );

            if ($target === null) {
                abort(404);
            }

            return $this->urls->forQode($target);
        }

        $url = trim((string) ($qode->settings['redirect']['url'] ?? ''));

        if ($url === '') {
            abort(404);
        }

        return $url;
    }

    /**
     * @param  array<string, mixed>  $redirect
     * @return array{to: string, url: string|null, target_qode_id: int|null}
     */
    public function validateForSave(Qode|int|null $source, array $redirect): array
    {
        $mode = (string) ($redirect['to'] ?? self::MODE_NONE);
        $sourceId = $source instanceof Qode ? (int) $source->id : (is_int($source) ? $source : null);
        $tenantId = $source instanceof Qode
            ? (int) $source->tenant_id
            : (int) (auth()->user()?->tenant_id ?? 0);

        $url = isset($redirect['url']) ? trim((string) $redirect['url']) : null;
        $targetId = isset($redirect['target_qode_id']) && $redirect['target_qode_id'] !== ''
            ? (int) $redirect['target_qode_id']
            : null;

        if ($mode === self::MODE_NONE) {
            return [
                'to' => self::MODE_NONE,
                'url' => $url !== '' ? $url : 'https://example.com',
                'target_qode_id' => $targetId,
            ];
        }

        if ($mode === self::MODE_QODE) {
            if ($targetId === null || $targetId === 0) {
                throw ValidationException::withMessages([
                    'settings.redirect.target_qode_id' => 'Select a Qode to redirect to.',
                ]);
            }

            if ($sourceId !== null && $targetId === $sourceId) {
                throw ValidationException::withMessages([
                    'settings.redirect.target_qode_id' => 'A Qode cannot redirect to itself.',
                ]);
            }

            $target = $this->findAllowedTarget($tenantId, $targetId, $sourceId);

            if ($target === null) {
                throw ValidationException::withMessages([
                    'settings.redirect.target_qode_id' => 'Choose an active Qode that is not itself redirecting. Cascades and loops are not allowed.',
                ]);
            }

            return [
                'to' => self::MODE_QODE,
                'url' => $url !== '' && $url !== null ? $url : 'https://example.com',
                'target_qode_id' => $target->id,
            ];
        }

        if ($mode !== self::MODE_URL) {
            throw ValidationException::withMessages([
                'settings.redirect.to' => 'Invalid redirect option.',
            ]);
        }

        if ($url === null || $url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw ValidationException::withMessages([
                'settings.redirect.url' => 'Enter a valid destination URL.',
            ]);
        }

        return [
            'to' => self::MODE_URL,
            'url' => $url,
            'target_qode_id' => $targetId,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function searchableOptions(int $tenantId, ?int $excludeId, string $search = ''): array
    {
        $query = Qode::query()
            ->where('tenant_id', $tenantId)
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
            ->filter(fn (Qode $qode): bool => ! $this->isRedirecting($qode))
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

        if ($target === null || ! $target->isActive()) {
            return null;
        }

        // Target must not redirect (blocks cascades and loops).
        if ($this->isRedirecting($target)) {
            return null;
        }

        return $target;
    }
}
