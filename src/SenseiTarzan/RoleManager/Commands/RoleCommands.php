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
        $this->registerSubCommand(new setRoleCommands("setrole"));
        $this->registerSubCommand(new setNameRoleCustomCommands("setnamecustom"));
        $this->registerSubCommand(new setPrefixCommands("setprefix"));
        $this->registerSubCommand(new setSuffixCommands("setsuffix"));
        $this->registerSubCommand(new setPermissionsCommands("setperm"));
        $this->registerSubCommand(new addPermissionsCommands("addperm"));
        $this->registerSubCommand(new subPermissionsCommands("subperm"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage(LanguageManager::getInstance()->getTranslate($sender, ""));    }
}