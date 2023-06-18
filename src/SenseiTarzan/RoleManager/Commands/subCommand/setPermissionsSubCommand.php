<?php

namespace SenseiTarzan\RoleManager\Commands\subCommand;

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\RoleManager\Class\Save\ResultUpdate;
use SenseiTarzan\RoleManager\Commands\args\PermissionsArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationFactory;
use SOFe\AwaitGenerator\Await;

class setPermissionsSubCommand extends BaseSubCommand
{


    /**
     * @inheritDoc
     */
    protected function prepare(): void
    {
        $this->setPermission("rolemanager.command.set-permissions.permission");
        $this->registerArgument(0, new TargetPlayerArgument(name: "target"));
        $this->registerArgument(1, new PermissionsArgument(name: "perm"));

    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$this->testPermissionSilent($sender)){
            return;
        }
        $target = Server::getInstance()->getPlayerExact($args['target']) ?? $args['target'];
        $perm = explode(";", $args['perm'] ?? "");
        if (count($perm) === 0) {
            return;
        }
        Await::g2c(RoleManager::getInstance()->setPermissionPlayer($target, $perm), function (ResultUpdate $resultUpdate) use ($sender, $target) {
            $sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::set_permissions_sender($target, $perm = $resultUpdate->data)));
            if ($resultUpdate->online) {
                $target->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($target, CustomKnownTranslationFactory::set_permissions_target($perm)));
            }
        }, function () use ($sender, $target, $perm) {

        });

    }
}