<?php

namespace App\Enum;

enum Status: string
{
    case BROUILLON = 'brouillon';
    case EN_LIGNE = 'en_ligne';
    case ARCHIVE = 'archive';
}
