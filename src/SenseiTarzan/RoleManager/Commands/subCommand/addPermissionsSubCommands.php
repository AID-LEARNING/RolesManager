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

use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\RoleManager\Class\Save\ResultUpdate;
use SenseiTarzan\RoleManager\Commands\args\PermissionsArgument;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationFactory;
use SOFe\AwaitGenerator\Await;
use function count;
use function explode;

class addPermissionsSubCommands extends BaseSubCommand
{

	/**
	 * @inheritDoc
	 */
	protected function prepare() : void
	{
		$this->setPermission("rolemanager.command.add-permissions.permission");
		$this->registerArgument(0, new TargetPlayerArgument(name: "target"));
		$this->registerArgument(1, new PermissionsArgument(name: "perm"));

	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void
	{
		if (!$this->testPermissionSilent($sender)) {
			return;
		}
		$target = Server::getInstance()->getPlayerExact($args['target']) ?? $args['target'];
		$perm = explode(";", $args['perm'] ?? "");
		if (count($perm) === 0) {
			return;
		}
		Await::g2c(RoleManager::getInstance()->addPermissionPlayer($target, $perm), function (ResultUpdate $resultUpdate) use ($sender, $target, $perm) {
			$sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::add_permissions_sender($target, $perm)));
			if ($resultUpdate->online) {
				$target->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($target, CustomKnownTranslationFactory::add_permissions_target($perm)));
			}
		}, function () use ($sender, $target, $perm) {

		});
	}
}
