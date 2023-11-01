<?php

namespace SenseiTarzan\RoleManager\Utils;

use pocketmine\utils\TextFormat;

class Utils
{
    public static function roleStringToId(string $name): string{
        return str_replace(" ", "_", strtolower(Utils::removeColorInRole($name)));
    }
    public static function rolesStringToIdArray(array $roles): array{
        return array_map(fn (string $role) => Utils::roleStringToId($role), $roles);
    }

    public static function removeColorInRole(string $name): string
    {
            return TextFormat::clean($name);
    }

}