<?php

use App\QodeModules\RedirectDestination;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('qodes')->orderBy('id')->get();

        foreach ($rows as $row) {
            $settings = json_decode((string) $row->settings, true);
            if (! is_array($settings)) {
                $settings = [];
            }

            $wasRedirectType = $row->type === 'redirect';
            $legacyDestination = $settings['destination'] ?? ($settings['redirect']['to'] ?? null);
            $legacyUrl = $settings['redirect']['url']
                ?? $settings['url']
                ?? 'https://example.com';
            $legacyTarget = $settings['redirect']['target_qode_id']
                ?? $settings['target_qode_id']
                ?? null;

            $to = RedirectDestination::MODE_NONE;

            if (isset($settings['redirect']['to']) && is_string($settings['redirect']['to'])) {
                $to = $settings['redirect']['to'];
            } elseif ($wasRedirectType) {
                $to = $legacyDestination === RedirectDestination::MODE_QODE
                    ? RedirectDestination::MODE_QODE
                    : RedirectDestination::MODE_URL;
            }

            if (! in_array($to, [
                RedirectDestination::MODE_NONE,
                RedirectDestination::MODE_URL,
                RedirectDestination::MODE_QODE,
            ], true)) {
                $to = RedirectDestination::MODE_NONE;
            }

            $settings['redirect'] = [
                'to' => $to,
                'url' => is_string($legacyUrl) && $legacyUrl !== '' ? $legacyUrl : 'https://example.com',
                'target_qode_id' => is_numeric($legacyTarget) ? (int) $legacyTarget : null,
            ];

            $settings['title'] ??= 'Untitled';
            $settings['body'] ??= '';

            unset(
                $settings['destination'],
                $settings['status_code'],
                $settings['url'],
                $settings['target_qode_id'],
            );

            DB::table('qodes')->where('id', $row->id)->update([
                'type' => $wasRedirectType ? 'content' : $row->type,
                'settings' => json_encode($settings),
            ]);
        }
    }

    public function down(): void
    {
        // Irreversible data reshape; left intentionally empty.
    }
};
