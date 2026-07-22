<?php

namespace App\QodeModules;

use App\Enums\QodeType;
use App\QodeModules\Contracts\QodeModule;
use App\QodeModules\Modules\ContentModule;
use App\QodeModules\Modules\FileDownloadModule;
use App\QodeModules\Modules\FormModule;
use App\QodeModules\Modules\LinkHubModule;
use InvalidArgumentException;

class ModuleRegistry
{
    /**
     * @var array<string, class-string<QodeModule>>
     */
    private array $modules = [
        'content' => ContentModule::class,
        'link_hub' => LinkHubModule::class,
        'form' => FormModule::class,
        'file_download' => FileDownloadModule::class,
    ];

    public function get(QodeType|string $type): QodeModule
    {
        $key = $type instanceof QodeType ? $type->value : $type;

        if (! isset($this->modules[$key])) {
            throw new InvalidArgumentException("Unknown Qode module type [{$key}].");
        }

        return app($this->modules[$key]);
    }

    /**
     * @return array<string, string>
     */
    public function options(): array
    {
        $options = [];

        foreach ($this->modules as $key => $class) {
            $options[$key] = app($class)->label();
        }

        return $options;
    }

    public function defaultType(): QodeType
    {
        return QodeType::Content;
    }
}
