<?php

namespace SenseiTarzan\RoleManager\Class\Text;

use pocketmine\lang\Translatable;
use pocketmine\player\chat\ChatFormatter;
use pocketmine\player\Player;
use SenseiTarzan\RoleManager\Component\TextAttributeManager;

class CustomChatFormatter implements ChatFormatter
{
    public function __construct(private string $message)
    {
    }

    public function format(string $username, string $message): Translatable|string
    {

        return $this->message;
    }
}