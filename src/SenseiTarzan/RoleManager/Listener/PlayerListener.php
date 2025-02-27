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

namespace SenseiTarzan\RoleManager\Listener;

use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\chat\ChatFormatter;
use SenseiTarzan\DataBase\Component\DataManager;
use SenseiTarzan\ExtraEvent\Class\EventAttribute;
use SenseiTarzan\RoleManager\Component\RolePlayerManager;
use SenseiTarzan\RoleManager\Component\TextAttributeManager;
use SOFe\AwaitGenerator\Await;

class PlayerListener
{

	public function __construct(
        private readonly bool $hasMiddleware,
        private readonly DataManager $dataManager)
	{
	}

	#[EventAttribute(EventPriority::LOWEST)]
	public function onJoin(PlayerJoinEvent $event) : void
	{
		if (!$this->hasMiddleware)
			$this->dataManager->getDataSystem()->loadDataPlayer($event->getPlayer());
	}

	#[EventAttribute(EventPriority::LOWEST)]
	public function onQuit(PlayerQuitEvent $event) : void
	{
		RolePlayerManager::getInstance()->removePlayer($event->getPlayer());
	}

	#[EventAttribute(EventPriority::MONITOR)]
	public function onChat(PlayerChatEvent $event) : void
	{

		Await::g2c(TextAttributeManager::getInstance()->formatMessage($event->getPlayer(), $event->getMessage()), function (?ChatFormatter $chatFormatter = null) use ($event) {
			if($chatFormatter === null) return;
			$event->setFormatter($chatFormatter);
		}, function () use ($event) {
			$event->cancel();
			$event->getPlayer()->sendMessage("Loading... Format message");
		});
	}

}
