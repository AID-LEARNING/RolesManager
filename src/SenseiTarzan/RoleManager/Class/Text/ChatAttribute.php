<?php

namespace SenseiTarzan\RoleManager\Class\Text;

use Closure;
use pocketmine\player\Player;
use pocketmine\utils\Utils;

class ChatAttribute
{
    /**
     * @param string $name
     * @param string $search
     * @param Closure $changeChat <code>
     * function (Player $player, string $message, string $search, string &$format): string {
     *   return $finaleString;
     * }
     * </code>
     */
    public function __construct(private string $name, private Closure $changeChat){
        Utils::validateCallableSignature(function (Player $player, string $message, string $search, string &$format): void{}, $this->changeChat);
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
    public function getChangeChat(): Closure
    {
        return $this->changeChat;
    }

    public function runChangeChat(Player $player, string $message, string &$format): void
    {

        ($this->getChangeChat())($player, $message, "{&{$this->getName()}}", $format);
    }
}