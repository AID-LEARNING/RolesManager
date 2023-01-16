<?php

namespace SenseiTarzan\RoleManager\Commands\args;

use CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;
use pocketmine\permission\PermissionManager;
use SenseiTarzan\RoleManager\Class\Role\Role;
use SenseiTarzan\RoleManager\Component\RoleManager;

class PermissionsArgument extends StringEnumArgument
{

    public static array $VALUES = [];
    public function __construct(string $name, bool $optional = false)
    {
        foreach (PermissionManager::getInstance()->getPermissions() as $permission){
            self::$VALUES[strtolower($permission->getName())] = $permission->getName();
        }
        parent::__construct($name, $optional);
    }


    public function getValue(string $string) {
        return self::$VALUES[strtolower($string)] ?? $string;
    }

    public function getEnumValues(): array {
        return array_keys(self::$VALUES);
    }


    public function canParse(string $testString, CommandSender $sender): bool {
        return true;
    }

    public function parse(string $argument, CommandSender $sender): string
    {
        return $this->getValue($argument);
    }

    public function getTypeName(): string
    {
        return "stringpermission";
    }
    public function getEnumName(): string
    {
        return "stringpermission";
    }
}