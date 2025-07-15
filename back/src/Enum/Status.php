<?php

namespace App\Enum;

enum Status: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    public function getName(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::PUBLISHED => 'En ligne',
            self::ARCHIVED => 'Archivé',
        };
    }
}
