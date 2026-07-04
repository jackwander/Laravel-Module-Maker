<?php

namespace Jackwander\ModuleMaker\AI\Support;

enum Depth: string
{
    case Full = 'full';
    case Compressed = 'compressed';
    case Summary = 'summary';

    public static function fromString($value): self
    {
        return self::tryFrom(strtolower((string) $value)) ?? self::Full;
    }
}
