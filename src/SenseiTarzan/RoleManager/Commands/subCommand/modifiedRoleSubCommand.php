<?php

namespace SenseiTarzan\RoleManager\Commands\subCommand;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use pocketmine\command\CommandSender;
use SenseiTarzan\RoleManager\Component\RoleManager;

class modifiedRoleSubCommand extends BaseSubCommand
{


    /**
     * @inheritDoc
     */
    protected function prepare(): void
    {
        $this->setPermission("rolemanager.command.modified-role.permission");
        $this->addConstraint(new InGameRequiredConstraint($this));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$this->testPermissionSilent($sender)) {
            return;
        }
        RoleManager::getInstance()->modifiedRoleSelectUI($sender);

    }

    public function getPermission()
    {
        return "rolemanager.command.modified-role.permission";
    }
}