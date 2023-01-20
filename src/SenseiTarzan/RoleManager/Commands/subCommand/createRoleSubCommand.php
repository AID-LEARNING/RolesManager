<?php

namespace SenseiTarzan\RoleManager\Commands\subCommand;

use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\RoleManager\Commands\args\PermissionsArgument;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationFactory;

class createRoleSubCommand extends BaseSubCommand
{


    /**
     * @inheritDoc
     */
    protected function prepare(): void
    {
        $this->setPermission("command.create-role.permission");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$this->testPermissionSilent($sender)){
            return;
        }
        RoleManager::getInstance()->createRoleUI($sender);

    }
}