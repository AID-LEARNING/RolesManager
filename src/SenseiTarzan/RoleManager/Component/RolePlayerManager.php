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

namespace SenseiTarzan\RoleManager\Component;

use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use SenseiTarzan\RoleManager\Class\Role\RolePlayer;
use SenseiTarzan\RoleManager\Main;
use function array_fill_keys;
use function array_merge;
use function is_string;
use function strtolower;

class RolePlayerManager
{
	use SingletonTrait;

	/** @var RolePlayer[] */
	private array $players = [];

	public function loadPlayer(Player $player, RolePlayer $rolePlayer) : void
	{
		$rolePlayer->setAttachment($player->addAttachment(Main::getInstance()));
		$this->players[$rolePlayer->getId()] = $rolePlayer;
		$this->loadPermissions($rolePlayer);
	}

	public function getPlayer(Player|string $player) : ?RolePlayer
	{
		return $this->players[strtolower(is_string($player) ? $player : $player->getName())] ?? null;
	}

	public function removePlayer(Player|string $player) : void
	{
		unset($this->players[strtolower(is_string($player) ? $player : $player->getName())]);
	}

	public function loadPermissions(RolePlayer $rolePlayer) : void
	{
		RoleManager::getInstance()->addPermissions($rolePlayer, self::combinePermissionsAndSetTrue($rolePlayer->getPermissions(), $rolePlayer->getRole()->getAllPermissions(), $rolePlayer->getPermissionsSubRoles()));
	}

	private function combinePermissionsAndSetTrue(...$perms) : array
	{
		return array_fill_keys(array_merge(...$perms), true);
	}

	public function reloadPermissions() : void
	{
		foreach ($this->players as $rolePlayer) {
			$this->loadPermissions($rolePlayer);
		}
	}

}
