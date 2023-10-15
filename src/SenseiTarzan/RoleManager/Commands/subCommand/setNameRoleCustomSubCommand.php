<?php

namespace SenseiTarzan\RoleManager\Commands\subCommand;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use Exception;
use pocketmine\command\CommandSender;
use pocketmine\entity\projectile\Throwable;
use pocketmine\Server;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use CortexPE\Commando\args\TargetPlayerArgument;
use SenseiTarzan\RoleManager\Class\Exception\CancelEventException;
use SenseiTarzan\RoleManager\Class\Exception\RoleFilteredNameCustomException;
use SenseiTarzan\RoleManager\Class\Exception\RoleNoNameCustomException;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationFactory;
use SOFe\AwaitGenerator\Await;

class setNameRoleCustomSubCommand extends BaseSubCommand
{


    /**
     * @inheritDoc
     */
    protected function prepare(): void
    {
        $this->setPermission("rolemanager.command.nameCustom.permission");
        $this->registerArgument(0, new TargetPlayerArgument(name: "target"));
        $this->registerArgument(1, new RawStringArgument(name: "nameCustom"));

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
        $nameCustom = $args['nameCustom'];
        Await::g2c(RoleManager::getInstance()->setNameRoleCustom($target, $nameCustom), function (string $nameCustom) use ($sender, $target) {
            $sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::set_name_role_sender($target, $nameCustom)));
        },[
        RoleNoNameCustomException::class => function () use ($sender, $target, $nameCustom) {
            $sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::error_set_name_role_sender($target, $nameCustom)));
        },
        CancelEventException::class => function () use ($sender, $target, $nameCustom) {
            $sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::error_set_name_role_sender($target, $nameCustom)));
        },
        RoleFilteredNameCustomException::class =>  function () use ($sender, $target, $nameCustom) {
            $sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::error_set_name_role_is_filtered_sender($target, $nameCustom)));
        }
        ]);


    }
}