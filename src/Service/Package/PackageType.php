<?php
declare(strict_types=1);

namespace App\Service\Package;

enum PackageType: string
{
    case Cms = 'cms';
    case Addon = 'addon';
    case Theme = 'theme';
}
