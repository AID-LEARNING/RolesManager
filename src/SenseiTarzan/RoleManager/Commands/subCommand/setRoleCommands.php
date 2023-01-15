<?php

namespace SenseiTarzan\RoleManager\Commands\subCommand;

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\RoleManager\Class\Role\Role;
use SenseiTarzan\RoleManager\Commands\args\RoleArgument;
use SenseiTarzan\RoleManager\Commands\args\TargetPlayerArgument;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationFactory;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationKeys;

class setRoleCommands extends BaseSubCommand
{


    /**
     * @inheritDoc
     */
    protected function prepare(): void
    {
        $this->setPermission("command.set-role.permission");
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
        $sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::set_role_sender($target,$role)));
        RoleManager::getInstance()->setRolePlayer($target, $role);

    }
}