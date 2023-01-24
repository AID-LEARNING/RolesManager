<?php

namespace SenseiTarzan\RoleManager\Event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class EventChangeSuffix extends PlayerEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(Player $player, private string $oldSuffix, private string $newSuffix)
    {
        $this->player = $player;
    }

    /**
     * @return string
     */
    public function getOldSuffix(): string
    {
        return $this->oldSuffix;
    }

    /**
     * @return string
     */
    public function getNewSuffix(): string
    {
        return $this->newSuffix;
    }

    /**
     * @param string $newSuffix
     */
    public function setNewSuffix(string $newSuffix): void
    {
        $this->newSuffix = $newSuffix;
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

}