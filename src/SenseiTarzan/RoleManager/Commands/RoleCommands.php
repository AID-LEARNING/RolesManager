<?php

namespace SenseiTarzan\RoleManager\Commands;

use CortexPE\Commando\BaseCommand;
use pocketmine\command\CommandSender;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\RoleManager\Commands\subCommand\setNameRoleCustomCommands;
use SenseiTarzan\RoleManager\Commands\subCommand\setPermissionsCommands;
use SenseiTarzan\RoleManager\Commands\subCommand\setPrefixCommands;
use SenseiTarzan\RoleManager\Commands\subCommand\setRoleCommands;
use SenseiTarzan\RoleManager\Commands\subCommand\addPermissionsCommands;
use SenseiTarzan\RoleManager\Commands\subCommand\setSuffixCommands;
use SenseiTarzan\RoleManager\Commands\subCommand\subPermissionsCommands;

class RoleCommands extends BaseCommand
{


    protected function prepare(): void
    {
        $this->setPermission("command.role.permission");
        $this->registerSubCommand(new setRoleCommands($this->plugin, "setrole"));
        $this->registerSubCommand(new setNameRoleCustomCommands($this->plugin, "setnamecustom"));
        $this->registerSubCommand(new setPrefixCommands($this->plugin, "setprefix"));
        $this->registerSubCommand(new setSuffixCommands($this->plugin, "setsuffix"));
        $this->registerSubCommand(new setPermissionsCommands($this->plugin, "setperm"));
        $this->registerSubCommand(new addPermissionsCommands($this->plugin, "addperm"));
        $this->registerSubCommand(new subPermissionsCommands($this->plugin, "subperm"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage(LanguageManager::getInstance()->getTranslate($sender, ""));    }
}