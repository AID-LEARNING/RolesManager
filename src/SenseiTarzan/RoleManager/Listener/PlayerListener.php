<?php

namespace SenseiTarzan\RoleManager\Listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use SenseiTarzan\RoleManager\Component\DataManager;
use SenseiTarzan\RoleManager\Component\RolePlayerManager;
use SenseiTarzan\RoleManager\Component\TextAttributeManager;

class PlayerListener implements Listener
{

    /**
     * @allowHandle
     * @priority HIGHEST
     */
    public function onJoin(PlayerJoinEvent $event): void{
        DataManager::getInstance()->getDataSystem()->loadDataPlayer($event->getPlayer());
    }


    /**
     * @allowHandle
     * @priority HIGHEST
     */
    public function onQuit(PlayerQuitEvent $event): void{
        RolePlayerManager::getInstance()->removePlayer($event->getPlayer());
    }

    /**
     * @allowHandle
     * @priority HIGHEST
     */
    public function onChat(PlayerChatEvent $event): void{
        TextAttributeManager::getInstance()->formatMessage($event->getPlayer(), $event->getMessage(), $format);
        $event->setFormat($format);
    }

}