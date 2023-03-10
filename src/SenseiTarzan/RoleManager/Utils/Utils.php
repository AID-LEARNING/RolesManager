<?php

namespace SenseiTarzan\RoleManager\Utils;

use pocketmine\utils\TextFormat;

class Utils
{
    public static function roleStringToId(string $name): string{
        return str_replace(" ", "_", strtolower(Utils::removeColorInRole($name)));
    }
    public static function rolesStringToIdArray(array $heritages): array{
        return array_map(fn (string $role) => Utils::roleStringToId($role), $heritages);
    }

    public static function removeColorInRole(string $name): string{
        return str_replace(array_values(TextFormat::COLORS), "", $name);
    }

}