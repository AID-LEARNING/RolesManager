<?php

namespace SenseiTarzan\RoleManager\Commands\subCommand;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use Exception;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use CortexPE\Commando\args\TargetPlayerArgument;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationFactory;
use SOFe\AwaitGenerator\Await;

class setPrefixSubCommand extends BaseSubCommand
{


    /**
     * @inheritDoc
     */
    protected function prepare(): void
    {
        $this->setPermission("rolemanager.command.prefix.permission");
        $this->registerArgument(0, new TargetPlayerArgument(name: "target"));
        $this->registerArgument(1, new RawStringArgument(name: "prefix"));

    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$this->testPermissionSilent($sender)) {
            return;
        }
        $target = Server::getInstance()->getPlayerExact($args['target']);
        if ($target === null) {
            $sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::error_player_disconnected($args['target'])));
            return;
        }
        $prefix = $args['prefix'];
        Await::g2c(RoleManager::getInstance()->setPrefix($target, $prefix), function (string $prefix) use ($sender, $target) {
            $sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::set_prefix_sender($target, $prefix)));
            $target->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($target, CustomKnownTranslationFactory::set_prefix_target($prefix)));
        }, function () use ($sender, $target, $prefix) {
            $sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::error_set_prefix_sender($target, $prefix)));
        });
    }
}