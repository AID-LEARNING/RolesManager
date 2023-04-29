<?php

namespace SenseiTarzan\RoleManager\Commands\subCommand;

use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\RoleManager\Class\Role\Role;
use SenseiTarzan\RoleManager\Commands\args\RoleArgument;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationFactory;

class setsubRoleSubCommand extends BaseSubCommand
{


    /**
     * @inheritDoc
     */
    protected function prepare(): void
    {
        $this->setPermission("rolemanager.command.set-sub-role.permission");
        $this->registerArgument(0, new TargetPlayerArgument(name: "target"));
        $this->registerArgument(1, new RoleArgument(name: "name"));

    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$this->testPermissionSilent($sender)){
            return;
        }
        $target = Server::getInstance()->getPlayerExact($args['target']) ?? $args['target'];
        $role = $args['name'];
        if (!$role instanceof Role){
            $sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender,CustomKnownTranslationFactory::role_not_found($role)));
            return;
        }
        RoleManager::getInstance()->setSubRolesPlayer($target, $role);
        $sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender,CustomKnownTranslationFactory::set_sub_roles_sender($target, $role)));

    }
    public function getPermission(): string
    {
       return "rolemanager.command.set-sub-role.permission";
    }
}