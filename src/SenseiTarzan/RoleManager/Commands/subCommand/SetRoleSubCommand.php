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
use SenseiTarzan\RoleManager\Main;
use SenseiTarzan\RoleManager\Class\Exception\CancelEventException;
use SenseiTarzan\RoleManager\Class\Role\Role;
use SenseiTarzan\RoleManager\Class\Save\ResultUpdate;
use SenseiTarzan\RoleManager\Commands\args\RoleArgument;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationFactory;
use SOFe\AwaitGenerator\Await;

class SetRoleSubCommand extends BaseSubCommand
{

	/**
	 * @inheritDoc
	 */
	protected function prepare() : void
	{
		$this->setPermission("rolemanager.command.set-role.permission");
		$this->registerArgument(0, new TargetPlayerArgument(name: "target"));
		$this->registerArgument(1, new RoleArgument(name: "role"));

	}

	/**
	 * @throws CancelEventException
	 */
	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void
	{
		if (!$this->testPermissionSilent($sender)){
			return;
		}
		$target = Server::getInstance()->getPlayerExact($args['target']) ?? $args['target'];
		$role = $args['role'];
		if (!$role instanceof Role){
			$sender->sendMessage(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($sender,CustomKnownTranslationFactory::role_not_found($role)));
			return;
		}
		Await::g2c(RoleManager::getInstance()->setRolePlayer($target,$role), function (ResultUpdate $resultUpdate) use ($sender, $target){
			$sender->sendMessage(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::set_role_sender($target,$role = $resultUpdate->data)));
			if ($resultUpdate->online){
			   $target->sendMessage(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($target, CustomKnownTranslationFactory::set_role_target($role)));
			}
		}, function (){

		});

	}
}
