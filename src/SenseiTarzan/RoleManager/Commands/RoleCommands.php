<?php

namespace SenseiTarzan\RoleManager\Commands;

use CortexPE\Commando\BaseCommand;
use pocketmine\command\CommandSender;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\RoleManager\Commands\subCommand\createRoleSubCommand;
use SenseiTarzan\RoleManager\Commands\subCommand\modifiedRoleSubCommand;
use SenseiTarzan\RoleManager\Commands\subCommand\reloadRoleSubCommand;
use SenseiTarzan\RoleManager\Commands\subCommand\setNameRoleCustomSubCommand;
use SenseiTarzan\RoleManager\Commands\subCommand\setPermissionsSubCommand;
use SenseiTarzan\RoleManager\Commands\subCommand\setPrefixSubCommand;
use SenseiTarzan\RoleManager\Commands\subCommand\setRoleSubCommand;
use SenseiTarzan\RoleManager\Commands\subCommand\addPermissionsSubCommands;
use SenseiTarzan\RoleManager\Commands\subCommand\setSuffixSubCommand;
use SenseiTarzan\RoleManager\Commands\subCommand\subPermissionsSubCommand;

class RoleCommands extends BaseCommand
{


    protected function prepare(): void
    {
        $this->setPermission("command.role.permission");
        $this->registerSubCommand(new createRoleSubCommand($this->plugin, "create"));
        $this->registerSubCommand(new modifiedRoleSubCommand($this->plugin, "modify"));
        $this->registerSubCommand(new reloadRoleSubCommand($this->plugin, "relaod"));
        $this->registerSubCommand(new setRoleSubCommand($this->plugin, "setrole"));
        $this->registerSubCommand(new setNameRoleCustomSubCommand($this->plugin, "setnamecustom"));
        $this->registerSubCommand(new setPrefixSubCommand($this->plugin, "setprefix"));
        $this->registerSubCommand(new setSuffixSubCommand($this->plugin, "setsuffix"));
        $this->registerSubCommand(new setPermissionsSubCommand($this->plugin, "setperm"));
        $this->registerSubCommand(new addPermissionsSubCommands($this->plugin, "addperm"));
        $this->registerSubCommand(new subPermissionsSubCommand($this->plugin, "subperm"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage(LanguageManager::getInstance()->getTranslate($sender, ""));    }
}