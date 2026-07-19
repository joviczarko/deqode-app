<?php

namespace App\Enums;

enum PackageStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Legacy = 'legacy';
    case UpgradeOnly = 'upgrade_only';
    case Hidden = 'hidden';

    public function label(): string
    {
        return config('packages.statuses.'.$this->value, $this->value);
    }

    public function isPurchasable(): bool
    {
        return $this === self::Active || $this === self::UpgradeOnly;
    }

    public function appearsInCheckout(): bool
    {
        return $this === self::Active;
    }
}
