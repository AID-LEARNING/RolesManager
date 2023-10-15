<?php

namespace SenseiTarzan\RoleManager\Listener;

use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\chat\ChatFormatter;
use SenseiTarzan\DataBase\Component\DataManager;
use SenseiTarzan\ExtraEvent\Class\EventAttribute;
use SenseiTarzan\RoleManager\Component\RolePlayerManager;
use SenseiTarzan\RoleManager\Component\TextAttributeManager;
use SOFe\AwaitGenerator\Await;

class PlayerListener
{

    #[EventAttribute(EventPriority::LOWEST)]
    public function onJoin(PlayerJoinEvent $event): void
    {
        DataManager::getInstance()->getDataSystem()->loadDataPlayer($event->getPlayer());
    }

    #[EventAttribute(EventPriority::LOWEST)]
    public function onQuit(PlayerQuitEvent $event): void
    {
        RolePlayerManager::getInstance()->removePlayer($event->getPlayer());
    }

    #[EventAttribute(EventPriority::MONITOR)]
    public function onChat(PlayerChatEvent $event): void
    {

        Await::g2c(TextAttributeManager::getInstance()->formatMessage($event->getPlayer(), $event->getMessage()), function (?ChatFormatter $chatFormatter = null) use ($event) {
            if($chatFormatter === null) return;
            $event->setFormatter($chatFormatter);
        }, function () use ($event) {
            $event->cancel();
            $event->getPlayer()->sendMessage("Loading... Format message");
        });
    }

}