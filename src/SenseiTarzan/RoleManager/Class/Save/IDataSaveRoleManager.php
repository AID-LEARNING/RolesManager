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

namespace SenseiTarzan\RoleManager\Class\Save;

use Generator;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SenseiTarzan\DataBase\Class\IDataSave;
use SenseiTarzan\RoleManager\Class\Role\RolePlayer;
use SenseiTarzan\RoleManager\Component\RolePlayerManager;
use SenseiTarzan\RoleManager\Event\EventLoadRolePlayer;
use SOFe\AwaitGenerator\Await;
use Throwable;
use function assert;
use function mb_strtolower;

abstract class IDataSaveRoleManager implements IDataSave
{

	abstract protected function createPromiseLoadDataPlayer(Player|string $player) : Generator;

	final public function loadDataPlayer(Player|string $player) : void
	{
		assert($player instanceof Player);
		Await::g2c($this->createPromiseLoadDataPlayer($player), function (RolePlayer $rolePlayer) use ($player) {
			RolePlayerManager::getInstance()->loadPlayer($player, $rolePlayer);
			if (EventLoadRolePlayer::hasHandlers()) {
				$event = new EventLoadRolePlayer($player, $rolePlayer);
				$event->call();
			}
		}, function (Throwable $exception) use ($player) {
			$player->kick(TextFormat::DARK_RED . "Error: " . $exception->getMessage());
		});
	}

	final public function loadDataPlayerByMiddleware(Player|string $player) : Generator
	{
		assert($player instanceof Player);
		return Await::promise(function($resolve) use ($player) {
			Await::g2c($this->createPromiseLoadDataPlayer($player), function (RolePlayer $rolePlayer) use ($player, $resolve) {
				RolePlayerManager::getInstance()->loadPlayer($player, $rolePlayer);
				if (EventLoadRolePlayer::hasHandlers()) {
					$event = new EventLoadRolePlayer($player, $rolePlayer);
					$event->call();
				}
				$resolve();
			}, function (Throwable $exception) use ($player, $resolve) {
				$resolve($exception);
			});
		});
	}

	abstract protected function createPromiseSaveDataPlayer(RolePlayer $rolePlayer) : Generator;
	abstract public function createPromiseUpdateOnline(string $id, string $type, mixed $data) : Generator;

	abstract public function createPromiseUpdateOffline(string $id, string $type, mixed $data) : Generator;

	final public function updateOnline(string $id, string $type, mixed $data) : Generator
	{
		return $this->createPromiseUpdateOnline(mb_strtolower($id), $type, $data);
	}

	final public function updateOffline(string $id, string $type, mixed $data) : Generator
	{
		return $this->createPromiseUpdateOffline(mb_strtolower($id), $type, $data);
	}
}
