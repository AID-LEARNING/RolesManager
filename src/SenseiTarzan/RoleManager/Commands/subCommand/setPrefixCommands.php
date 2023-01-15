<?php

namespace SenseiTarzan\RoleManager\Commands\subCommand;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\RoleManager\Commands\args\PermissionsArgument;
use SenseiTarzan\RoleManager\Commands\args\TargetPlayerArgument;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Component\RolePlayerManager;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationFactory;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationKeys;

class setPrefixCommands extends BaseSubCommand
{


    /**
     * @inheritDoc
     */
    protected function prepare(): void
    {
        $this->setPermission("command.prefix.permission");
        $this->registerArgument(0, new TargetPlayerArgument(name: "target"));
        $this->registerArgument(1, new RawStringArgument(name: "prefix"));

    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$this->testPermissionSilent($sender)){
            return;
        }
        $target = Server::getInstance()->getPlayerExact($args['target']);
        if ($target === null){
            $sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::error_player_disconnected($args['target'])));
            return;
        }
        $prefix = $args['prefix'];
        $sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::set_prefix_sender($target, $prefix)));
        RoleManager::getInstance()->setPrefix($target, $prefix);

    }
}