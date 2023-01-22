<?php

namespace SenseiTarzan\RoleManager\Listener;

use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use SenseiTarzan\ExtraEvent\Class\EventAttribute;
use SenseiTarzan\RoleManager\Component\DataManager;
use SenseiTarzan\RoleManager\Component\RolePlayerManager;
use SenseiTarzan\RoleManager\Component\TextAttributeManager;

class PlayerListener
{

    #[EventAttribute(EventPriority::HIGHEST)]
    public function onJoin(PlayerJoinEvent $event): void{
        DataManager::getInstance()->getDataSystem()->loadDataPlayer($event->getPlayer());
    }

    #[EventAttribute(EventPriority::HIGHEST)]
    public function onQuit(PlayerQuitEvent $event): void{
        RolePlayerManager::getInstance()->removePlayer($event->getPlayer());
    }

    #[EventAttribute(EventPriority::HIGHEST)]
    public function onChat(PlayerChatEvent $event): void{
        TextAttributeManager::getInstance()->formatMessage($event->getPlayer(), $event->getMessage(), $format);
        $event->setFormat($format);
    }

}