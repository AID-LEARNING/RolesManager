<?php

namespace SenseiTarzan\RoleManager\Commands\subCommand;

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\RoleManager\Commands\args\PermissionsArgument;
use SenseiTarzan\RoleManager\Commands\args\TargetPlayerArgument;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationFactory;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationKeys;

class subPermissionsCommands extends BaseSubCommand
{


    /**
     * @inheritDoc
     */
    protected function prepare(): void
    {
        $this->setPermission("command.sub-permissions.permission");
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
        $sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::remove_permissions_sender($target,$perm)));
        RoleManager::getInstance()->removePermissionPlayer($target, $perm);

    }
}