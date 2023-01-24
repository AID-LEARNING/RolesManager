<?php

namespace SenseiTarzan\RoleManager\Event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class EventChangeNameCustom extends PlayerEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(Player $player, private string $oldNameCustom, private string $newNameCustom)
    {
        $this->player = $player;
    }

    /**
     * @return string
     */
    public function getOldNameCustom(): string
    {
        return $this->oldNameCustom;
    }

    /**
     * @return string
     */
    public function getNewNameCustom(): string
    {
        return $this->newNameCustom;
    }

    /**
     * @param string $newNameCustom
     */
    public function setNewNameCustom(string $newNameCustom): void
    {
        $this->newNameCustom = $newNameCustom;
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

}