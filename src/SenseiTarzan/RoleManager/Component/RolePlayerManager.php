<?php

namespace SenseiTarzan\RoleManager\Component;

use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use SenseiTarzan\ExtraEvent\Class\EventAttribute;
use SenseiTarzan\RoleManager\Class\Role\RolePlayer;
use SenseiTarzan\RoleManager\Main;
use SOFe\AwaitGenerator\Await;

class RolePlayerManager
{
    use SingletonTrait;

    /**
     * @var RolePlayer[]
     */
    private array $players = [];


    public function loadPlayer(Player $player, RolePlayer $rolePlayer): void
    {
        $rolePlayer->setAttachment($player->addAttachment(Main::getInstance()));
        $this->players[$rolePlayer->getId()] = $rolePlayer;
        $this->loadPermissions($rolePlayer);
    }

    public function getPlayer(Player|string $player): ?RolePlayer
    {
        return $this->players[strtolower(is_string($player) ? $player : $player->getName())] ?? null;
    }

    public function removePlayer(Player|string $player): void
    {
        unset($this->players[strtolower(is_string($player) ? $player : $player->getName())]);
    }

    public function loadPermissions(RolePlayer $rolePlayer): void
    {
        RoleManager::getInstance()->addPermissions($rolePlayer, self::combinePermissionsAndSetTrue($rolePlayer->getPermissions(), $rolePlayer->getRole()->getAllPermissions(), $rolePlayer->getPermissionsSubRoles()));
    }

    private function combinePermissionsAndSetTrue(...$perms): array
    {
        return array_fill_keys(array_merge(...$perms), true);
    }

    public function reloadPermissions(): void
    {
        foreach ($this->players as $rolePlayer) {
            $this->loadPermissions($rolePlayer);
        }
    }

}