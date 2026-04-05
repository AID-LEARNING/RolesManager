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

namespace SenseiTarzan\RoleManager\Commands;

use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use pocketmine\command\CommandSender;
use SenseiTarzan\RoleManager\Main;
use SenseiTarzan\RoleManager\Commands\subCommand\AddPermissionsSubCommands;
use SenseiTarzan\RoleManager\Commands\subCommand\AddsubRoleSubCommand;
use SenseiTarzan\RoleManager\Commands\subCommand\CreateRoleSubCommand;
use SenseiTarzan\RoleManager\Commands\subCommand\ModifiedRoleSubCommand;
use SenseiTarzan\RoleManager\Commands\subCommand\ReloadRoleSubCommand;
use SenseiTarzan\RoleManager\Commands\subCommand\RemovePermissionsSubCommand;
use SenseiTarzan\RoleManager\Commands\subCommand\RemovesubRoleSubCommand;
use SenseiTarzan\RoleManager\Commands\subCommand\SetNameRoleCustomSubCommand;
use SenseiTarzan\RoleManager\Commands\subCommand\SetPermissionsSubCommand;
use SenseiTarzan\RoleManager\Commands\subCommand\SetPrefixSubCommand;
use SenseiTarzan\RoleManager\Commands\subCommand\SetRoleSubCommand;
use SenseiTarzan\RoleManager\Commands\subCommand\SetsubRoleSubCommand;
use SenseiTarzan\RoleManager\Commands\subCommand\SetSuffixSubCommand;
use SenseiTarzan\RoleManager\Component\RolePlayerManager;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationFactory;

class RoleCommands extends BaseCommand
{

	protected function prepare() : void
	{

		$this->setPermission("rolemanager.command.role.permission");
		$this->registerSubCommand(new CreateRoleSubCommand($this->plugin, "create"));
		$this->registerSubCommand(new ModifiedRoleSubCommand($this->plugin, "modify"));
		$this->registerSubCommand(new ReloadRoleSubCommand($this->plugin, "reload"));
		$this->registerSubCommand(new SetRoleSubCommand($this->plugin, "setrole"));
		$this->registerSubCommand(new SetNameRoleCustomSubCommand($this->plugin, "setnamecustom"));
		$this->registerSubCommand(new SetPrefixSubCommand($this->plugin, "setprefix"));
		$this->registerSubCommand(new SetSuffixSubCommand($this->plugin, "setsuffix"));
		$this->registerSubCommand(new SetPermissionsSubCommand($this->plugin, "setperm"));
		$this->registerSubCommand(new AddPermissionsSubCommands($this->plugin, "addperm"));
		$this->registerSubCommand(new RemovePermissionsSubCommand($this->plugin, "subperm"));
		$this->registerSubCommand(new AddsubRoleSubCommand($this->plugin, "addsubrole"));
		$this->registerSubCommand(new RemovesubRoleSubCommand($this->plugin, "removesubrole"));
		$this->registerSubCommand(new SetsubRoleSubCommand($this->plugin, "setsubrole"));
		$this->addConstraint(new InGameRequiredConstraint($this));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void
	{
		$sender->sendMessage(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::get_information_role(RolePlayerManager::getInstance()->getPlayer($sender->getName())->getRoleName())));
	}

	public function getPermission() : string
	{

		return "rolemanager.command.role.permission";
	}
}
