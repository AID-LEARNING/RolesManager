<?php

namespace SenseiTarzan\RoleManager\Commands\subCommand;

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\RoleManager\Commands\args\PermissionsArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationFactory;

class setPermissionsSubCommand extends BaseSubCommand
{


    /**
     * @inheritDoc
     */
    protected function prepare(): void
    {
        $this->setPermission("command.set-permissions.permission");
        $this->registerArgument(0, new TargetPlayerArgument(name: "target"));
        $this->registerArgument(1, new PermissionsArgument(name: "perm"));

    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$this->testPermissionSilent($sender)){
            return;
        }
        $target = Server::getInstance()->getPlayerExact($args['target']) ?? $args['target'];
        $perm =  explode(";", $args['perm']);
        $sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::set_permissions_sender($target, $perm)));
        RoleManager::getInstance()->setPermissionPlayer($target, $perm);

    }
}