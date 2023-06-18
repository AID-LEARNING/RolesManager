<?php

namespace SenseiTarzan\RoleManager\Commands\args;

use CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;
use SenseiTarzan\RoleManager\Class\Role\Role;
use SenseiTarzan\RoleManager\Component\RoleManager;

class RoleArgument extends StringEnumArgument
{

    public static array $VALUES = [];


    public function getValue(string $string) {
        return self::$VALUES[strtolower($string)];
    }

    public function getEnumValues(): array {
        return array_keys(self::$VALUES);
    }

    public function parse(string $argument, CommandSender $sender): ?Role
    {
        return RoleManager::getInstance()->getRoleNullable($this->getValue($argument)) ?? $this->getValue($argument);
    }

    public function getTypeName(): string
    {
        return "role";
    }

    public function getEnumName(): string
    {
        return "role";
    }
}