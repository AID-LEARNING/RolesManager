<?php

namespace SenseiTarzan\RoleManager\Class\Text;

use Closure;
use pocketmine\player\Player;
use pocketmine\utils\Utils;

class NameTagAttribute
{
    /**
     * @param string $name
     * @param string $search
     * @param Closure $changeNameTag <code>
     * function (Player $player, string $search, string &$format): string {
     *   return $finaleString;
     * }
     * </code>
     */
    public function __construct(private string $name,  private Closure $changeNameTag){
        Utils::validateCallableSignature(function (Player $player, string $search, string &$format): void{}, $this->changeNameTag);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    /**
     * @return Closure
     */
    public function getChangeNameTag(): Closure
    {
        return $this->changeNameTag;
    }

    public function runChangeNameTag(Player $player, string &$format): void
    {

        ($this->getChangeNameTag())($player, "{&{$this->getName()}}", $format);
    }
}