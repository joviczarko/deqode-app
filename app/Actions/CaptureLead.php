<?php

namespace App\Actions;

use App\Enums\QodeStatus;
use App\Enums\QodeType;
use App\Models\Lead;
use App\Models\Qode;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CaptureLead
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(Qode $qode, array $input): Lead
    {
        if ($qode->status !== QodeStatus::Active || $qode->type !== QodeType::Form) {
            abort(404);
        }

        $fields = $this->fields($qode);
        $rules = $this->rules($fields);
        $attributes = $this->attributes($fields);

        $validator = Validator::make($input, $rules, [], $attributes);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array<string, mixed> $validated */
        $validated = $validator->validated();

        $payload = [];

        foreach ($fields as $field) {
            $key = $field['key'];
            $payload[$key] = $validated[$key] ?? null;
        }

        return Lead::withoutGlobalScopes()->create([
            'tenant_id' => $qode->tenant_id,
            'qode_id' => $qode->id,
            'payload' => $payload,
        ]);
    }

    /**
     * @return list<array{key: string, label: string, type: string, required: bool}>
     */
    public function fields(Qode $qode): array
    {
        $raw = $qode->settings['fields'] ?? [];

        if (! is_array($raw)) {
            return [];
        }

        $fields = [];

        foreach ($raw as $field) {
            if (! is_array($field)) {
                continue;
            }

            $key = trim((string) ($field['key'] ?? ''));
            $label = trim((string) ($field['label'] ?? ''));
            $type = (string) ($field['type'] ?? 'text');

            if ($key === '' || $label === '') {
                continue;
            }

            if (! in_array($type, ['text', 'email', 'textarea'], true)) {
                $type = 'text';
            }

            $fields[] = [
                'key' => $key,
                'label' => $label,
                'type' => $type,
                'required' => (bool) ($field['required'] ?? false),
            ];
        }

        return $fields;
    }

    /**
     * @param  list<array{key: string, label: string, type: string, required: bool}>  $fields
     * @return array<string, list<string>>
     */
    private function rules(array $fields): array
    {
        $rules = [];

        foreach ($fields as $field) {
            $fieldRules = [$field['required'] ? 'required' : 'nullable', 'string', 'max:5000'];

            if ($field['type'] === 'email') {
                $fieldRules[] = 'email';
                $fieldRules[] = 'max:255';
            }

            if ($field['type'] === 'text') {
                $fieldRules[] = 'max:255';
            }

            $rules[$field['key']] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @param  list<array{key: string, label: string, type: string, required: bool}>  $fields
     * @return array<string, string>
     */
    private function attributes(array $fields): array
    {
        $attributes = [];

        foreach ($fields as $field) {
            $attributes[$field['key']] = $field['label'];
        }

        return $attributes;
    }
}
