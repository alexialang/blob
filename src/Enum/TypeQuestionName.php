<?php

namespace App\Enum;

enum TypeQuestionName: string
{
    case QCM = 'QCM';
    case TEXTE_LIBRE = 'Texte Libre';
    case VRAI_FAUX = 'Vrai/Faux';
}
