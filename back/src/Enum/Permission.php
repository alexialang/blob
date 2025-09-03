<?php

namespace App\Enum;

enum Permission: string
{
    case CREATE_QUIZ = 'CREATE_QUIZ';
    case MANAGE_USERS = 'MANAGE_USERS';
    case VIEW_RESULTS = 'VIEW_RESULTS';
}
