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

namespace SenseiTarzan\RoleManager\Event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;
use SenseiTarzan\RoleManager\Class\Role\Role;

	class EventChangeRole extends PlayerEvent implements Cancellable
{
	use CancellableTrait;

	public function __construct( Player $player, private Role $oldRole, private Role $newRole)
	{
		$this->player = $player;
	}

	/**
	 * @return ?Role
	 */
	public function getOldRole() : ?Role
	{
		return $this->oldRole;
	}

	public function getNewRole() : Role
	{
		return $this->newRole;
	}

	public function setNewRole(Role $newRole) : void
	{
		$this->newRole = $newRole;
	}

}
