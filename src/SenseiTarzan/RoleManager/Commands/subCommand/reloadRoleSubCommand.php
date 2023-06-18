<?php

namespace SenseiTarzan\RoleManager\Commands\subCommand;

use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\RoleManager\Commands\args\PermissionsArgument;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Component\RolePlayerManager;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationFactory;

class reloadRoleSubCommand extends BaseSubCommand
{


    /**
     * @inheritDoc
     */
    protected function prepare(): void
    {
        $this->setPermission("rolemanager.command.reload-role.permission");

    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$this->testPermissionSilent($sender)){
            return;
        }
        RoleManager::getInstance()->loadRoles();
        RolePlayerManager::getInstance()->reloadPermissions();

    }
}