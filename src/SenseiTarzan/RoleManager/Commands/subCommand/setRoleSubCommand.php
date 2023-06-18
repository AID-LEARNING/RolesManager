<?php

namespace SenseiTarzan\RoleManager\Commands\subCommand;

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\RoleManager\Class\Exception\CancelEventException;
use SenseiTarzan\RoleManager\Class\Role\Role;
use SenseiTarzan\RoleManager\Class\Save\ResultUpdate;
use SenseiTarzan\RoleManager\Commands\args\RoleArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationFactory;
use SOFe\AwaitGenerator\Await;

class setRoleSubCommand extends BaseSubCommand
{


    /**
     * @inheritDoc
     */
    protected function prepare(): void
    {
        $this->setPermission("rolemanager.command.set-role.permission");
        $this->registerArgument(0, new TargetPlayerArgument(name: "target"));
        $this->registerArgument(1, new RoleArgument(name: "name"));

    }

    /**
     * @throws CancelEventException
     */
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
        Await::g2c(RoleManager::getInstance()->setRolePlayer($target,$role), function (ResultUpdate $resultUpdate) use ($sender, $target){
            $sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::set_role_sender($target,$role = $resultUpdate->data)));
            if ($resultUpdate->online){
                $target->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($target, CustomKnownTranslationFactory::set_role_target($role)));
            }
        }, function (){

        });

    }
}