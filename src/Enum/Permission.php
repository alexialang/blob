<?php

namespace App\Enum;

enum Permission: string
{
    case CREATE_QUIZ = 'CREATE_QUIZ';
    case VIEW_RESULTS_ALL = 'VIEW_RESULTS_ALL';
    case MANAGE_USERS = 'MANAGE_USERS';
    case ASSIGN_PERMISSIONS = 'ASSIGN_PERMISSIONS';
}
