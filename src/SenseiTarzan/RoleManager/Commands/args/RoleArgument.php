<?php

/*
 *
 *            _____ _____         _      ______          _____  _   _ _____ _   _  _____
 *      /\   |_   _|  __ \       | |    |  ____|   /\   |  __ \| \ | |_   _| \ | |/ ____|
 *     /  \    | | | |  | |______| |    | |__     /  \  | |__) |  \| | | | |  \| | |  __
 *    / /\ \   | | | |  | |______| |    |  __|   / /\ \ |  _  /| . ` | | | | . ` | | |_ |
 *   / ____ \ _| |_| |__| |      | |____| |____ / ____ \| | \ \| |\  |_| |_| |\  | |__| |
 *  /_/    \_\_____|_____/       |______|______/_/    \_\_|  \_\_| \_|_____|_| \_|\_____|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author AID-LEARNING
 * @link https://github.com/AID-LEARNING
 *
 */

declare(strict_types=1);

namespace SenseiTarzan\RoleManager\Commands\args;

use CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;
use SenseiTarzan\RoleManager\Class\Role\Role;
use SenseiTarzan\RoleManager\Component\RoleManager;
use function array_keys;
use function strtolower;

class RoleArgument extends StringEnumArgument
{

	public static array $VALUES = [];

	public function getValue(string $string) {
		return self::$VALUES[strtolower($string)];
	}

	public function getEnumValues() : array {
		return array_keys(self::$VALUES);
	}

	public function parse(string $argument, CommandSender $sender) : Role|string
	{
		return RoleManager::getInstance()->getRoleNullable($this->getValue($argument)) ?? $this->getValue($argument);
	}

	public function getTypeName() : string
	{
		return "role";
	}

	public function getEnumName() : string
	{
		return "role";
	}
}
