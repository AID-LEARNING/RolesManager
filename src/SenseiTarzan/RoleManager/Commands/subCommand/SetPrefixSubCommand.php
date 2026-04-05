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

namespace SenseiTarzan\RoleManager\Commands\subCommand;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use SenseiTarzan\RoleManager\Main;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationFactory;
use SOFe\AwaitGenerator\Await;

class SetPrefixSubCommand extends BaseSubCommand
{

	/**
	 * @inheritDoc
	 */
	protected function prepare() : void
	{
		$this->setPermission("rolemanager.command.prefix.permission");
		$this->registerArgument(0, new TargetPlayerArgument(name: "target"));
		$this->registerArgument(1, new RawStringArgument(name: "prefix"));

	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void
	{
		if (!$this->testPermissionSilent($sender)) {
			return;
		}
		$target = Server::getInstance()->getPlayerExact($args['target']);
		if ($target === null) {
			$sender->sendMessage(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::error_player_disconnected($args['target'])));
			return;
		}
		$prefix = $args['prefix'];
		Await::g2c(RoleManager::getInstance()->setPrefix($target, $prefix), function (string $prefix) use ($sender, $target) {
			$sender->sendMessage(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::set_prefix_sender($target, $prefix)));
			$target->sendMessage(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($target, CustomKnownTranslationFactory::set_prefix_target($prefix)));
		}, function () use ($sender, $target, $prefix) {
			$sender->sendMessage(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::error_set_prefix_sender($target, $prefix)));
		});
	}
}
