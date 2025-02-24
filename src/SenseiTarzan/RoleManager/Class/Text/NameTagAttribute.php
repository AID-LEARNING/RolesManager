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

namespace SenseiTarzan\RoleManager\Class\Text;

use Closure;
use pocketmine\player\Player;
use pocketmine\utils\Utils;

readonly class NameTagAttribute
{
	/**
	 * @param Closure $changeNameTag <code>
	 *                               function (Player $player, string $search, string &$format): string {
	 *                               return $finaleString;
	 *                               }
	 *                               </code>
	 */
	public function __construct(private string $name, private Closure $changeNameTag){
		Utils::validateCallableSignature(function (Player $player, string $search, string &$format) : void{}, $this->changeNameTag);
	}

	public function getName() : string
	{
		return $this->name;
	}
	public function getChangeNameTag() : Closure
	{
		return $this->changeNameTag;
	}

	public function runChangeNameTag(Player $player, string &$format) : void
	{
		($this->getChangeNameTag())($player, "{&{$this->getName()}}", $format);
	}
}
