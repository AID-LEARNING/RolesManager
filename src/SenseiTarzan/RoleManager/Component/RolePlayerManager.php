<?php

namespace SenseiTarzan\RoleManager\Component;

use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use SenseiTarzan\ExtraEvent\Class\EventAttribute;
use SenseiTarzan\RoleManager\Class\Role\RolePlayer;

class RolePlayerManager
{
    use SingletonTrait;

    /**
     * @var RolePlayer[]
     */
    private array $players = [];


    public function loadPlayer(Player $player, RolePlayer $rolePlayer): void{
        $this->players[$rolePlayer->getId()] = $rolePlayer;
        $this->loadPermissions($player,$rolePlayer);
    }

    public function getPlayer(Player|string $player): ?RolePlayer{
        return $this->players[strtolower(is_string($player) ? $player : $player->getName())]?? null;
    }

    public function removePlayer(Player|string  $player): void{
        unset($this->players[strtolower(is_string($player) ? $player : $player->getName())]);
    }

    public function loadPermissions(Player $player, RolePlayer $rolePlayer): void
    {
        $roleManager = RoleManager::getInstance();
        $perm_update = $rolePlayer->getPermissions();
        $permsAll = $perm_update +  $rolePlayer->getRole()->getAllPermissions();
        $perms = [];
        foreach ($permsAll as $perm) {
            $perms[$perm] = true;
        }
        $roleManager->addPermissions($player, $perms);
    }

}