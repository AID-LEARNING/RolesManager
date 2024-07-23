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

namespace SenseiTarzan\RoleManager\Task;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use SenseiTarzan\RoleManager\Component\TextAttributeManager;
use SOFe\AwaitGenerator\Await;

class NameTagTask extends Task
{

	/**
	 * @inheritDoc
	 */
	public function onRun() : void
	{
		foreach (Server::getInstance()->getOnlinePlayers() as $player){
			if (!$player->isConnected()) continue;
			Await::g2c(TextAttributeManager::getInstance()->formatNameTag($player), function (string $format) use ($player) {
				$player->setNameTag($format);
			}, function () use ($player) {
				$player->setNameTag("Loading...");
			});
		}
	}
}
