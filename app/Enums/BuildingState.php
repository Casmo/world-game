<?php

namespace App\Enums;

enum BuildingState: string
{
    case UnderConstruction = 'under_construction';
    case Built = 'built';
}
