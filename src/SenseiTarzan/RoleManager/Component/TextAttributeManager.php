<?php

namespace SenseiTarzan\RoleManager\Component;

use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use SenseiTarzan\RoleManager\Class\Exception\RolePlayerNotFoundException;
use SenseiTarzan\RoleManager\Class\Text\ChatAttribute;
use SenseiTarzan\RoleManager\Class\Text\CustomChatFormatter;
use SenseiTarzan\RoleManager\Class\Text\NameTagAttribute;
use DaPigGuy\PiggyFactions\PiggyFactions;
use DaPigGuy\PiggyFactions\players\PlayerManager;
use ShockedPlot7560\FactionMaster\API\MainAPI;
use ShockedPlot7560\FactionMaster\Utils\Ids as FactionMasterID;
use SOFe\AwaitGenerator\Await;

class TextAttributeManager
{
    use SingletonTrait;

    /**
     * @var ChatAttribute[]
     */
    private array $chatAttributes = [];

    /**
     * @var NameTagAttribute[]
     */
    private array $nameTagAttribute;


    public function __construct()
    {
        $this->setup();
    }

    public function registerChatAttribute(ChatAttribute $chatAttribute): void
    {
        $this->chatAttributes[$chatAttribute->getName()] = $chatAttribute;
    }

    /**
     *
     * @param NameTagAttribute $nameTagAttribute
     * @return void
     */
    public function registerNameTagAttribute(NameTagAttribute $nameTagAttribute): void
    {
        $this->nameTagAttribute[$nameTagAttribute->getName()] = $nameTagAttribute;
    }

    /**
     * @return ChatAttribute[]
     * @phpstan-return array<string, ChatAttribute>
     */
    public function getChatAttributesAll(): array
    {
        return $this->chatAttributes;
    }

    /**
     * @return NameTagAttribute[]
     * @phpstan-return array<string, NameTagAttribute>
     */
    public function getNameTagAttributesAll(): array
    {
        return $this->nameTagAttribute;
    }

    protected function setup(): void
    {
        $this->registerChatAttribute(new ChatAttribute("playerName",  function (Player $player, string $message, string $search, string &$format): void {
            $format = str_replace($search, $player->getDisplayName(), $format);
        }));
        $this->registerChatAttribute(new ChatAttribute("role" , function (Player $player, string $message, string $search, string &$format): void {
            $format = str_replace($search, RolePlayerManager::getInstance()->getPlayer($player)->getRoleName(), $format);
        }));

        $this->registerChatAttribute(new ChatAttribute("prefix",  function (Player $player, string $message, string $search, string &$format): void {
            $format = str_replace($search, RolePlayerManager::getInstance()->getPlayer($player)->getPrefix(), $format);
        }));

        $this->registerChatAttribute(new ChatAttribute("suffix", function (Player $player, string $message, string $search, string &$format): void {
            $format = str_replace($search, RolePlayerManager::getInstance()->getPlayer($player)->getSuffix(), $format);
        }));

        $this->registerChatAttribute(new ChatAttribute("message",  function (Player $player, string $message, string $search, string &$format): void {
            $format = str_replace($search, $message, $format);
        }));

        $this->registerChatAttribute(new ChatAttribute("factionName", function (Player $player, string $message, string $search, string &$format): void {

            $factionName = null;
            if (class_exists("\\ShockedPlot7560\\FactionMaster\\API\\MainAPI")){
                $factionName = MainAPI::getUser($player->getName())?->getFactionName();
            }elseif (class_exists("\\DaPigGuy\\PiggyFactions\\PiggyFactions")){
                $factionName = PiggyFactions::getInstance()->getPlayerManager()->getPlayer($player)?->getFaction()?->getName();
            }
            $format = str_replace($search, $factionName ?? "", $format);
        }));

        $this->registerChatAttribute(new ChatAttribute("factionRank",  function (Player $player, string $message, string $search, string &$format): void {
            $factionRank = null;
            if (class_exists("\\ShockedPlot7560\\FactionMaster\\API\\MainAPI")){
                $factionRank = match (MainAPI::getUser($player->getName())?->getRank()) {
                    FactionMasterID::MEMBER_ID => "*",
                    FactionMasterID::COOWNER_ID => "**",
                    FactionMasterID::OWNER_ID => "***",
                    default => null,
                };
            }elseif (class_exists("\\DaPigGuy\\PiggyFactions\\PiggyFactions")){
                $factionRank = ($instance = PiggyFactions::getInstance())->getTagManager()->getPlayerRankSymbol($instance->getPlayerManager()->getPlayer($player));
            }
            $format = str_replace($search, $factionRank ?? "", $format);
        }));

        $this->registerNameTagAttribute(new NameTagAttribute("playerName", function (Player $player, string $search, string &$format): void {
            $format = str_replace($search, $player->getDisplayName(), $format);
        }));
        $this->registerNameTagAttribute(new NameTagAttribute("factionName", function (Player $player, string $search, string &$format): void {
            $factionName = null;
            if (class_exists("\\ShockedPlot7560\\FactionMaster\\API\\MainAPI")){
                $factionName = MainAPI::getUser($player->getName())?->getFactionName();
            }elseif (class_exists("\\DaPigGuy\\PiggyFactions\\PiggyFactions")){
                $factionName = PiggyFactions::getInstance()->getPlayerManager()->getPlayer($player)?->getFaction()?->getName();
            }
            $format = str_replace($search, $factionName ?? "", $format);

        }));
        $this->registerNameTagAttribute(new NameTagAttribute("factionRank", function (Player $player, string $search, string &$format): void {
            $factionRank = null;
            if (class_exists("\\ShockedPlot7560\\FactionMaster\\API\\MainAPI")){
                $factionRank = match (MainAPI::getUser($player->getName())?->getRank()) {
                    FactionMasterID::MEMBER_ID => "*",
                    FactionMasterID::COOWNER_ID => "**",
                    FactionMasterID::OWNER_ID => "***",
                    default => null,
                };
            }elseif (class_exists("\\DaPigGuy\\PiggyFactions\\PiggyFactions")){
                $factionRank = ($instance = PiggyFactions::getInstance())->getTagManager()->getPlayerRankSymbol($instance->getPlayerManager()->getPlayer($player));
            }
            $format = str_replace($search, $factionRank ?? "", $format);

        }));

        $this->registerNameTagAttribute(new NameTagAttribute("role",  function (Player $player, string $search, string &$format): void {
            $format = str_replace($search, RolePlayerManager::getInstance()->getPlayer($player)->getRoleName(), $format);
        }));

        $this->registerNameTagAttribute(new NameTagAttribute("prefix",  function (Player $player, string $search, string &$format): void {
            $format = str_replace($search, RolePlayerManager::getInstance()->getPlayer($player)->getPrefix(), $format);
        }));

        $this->registerNameTagAttribute(new NameTagAttribute("suffix", function (Player $player, string $search, string &$format): void {
            $format = str_replace($search, RolePlayerManager::getInstance()->getPlayer($player)->getSuffix(), $format);
        }));
    }

    /**
     * this method allows you to give the final formatting of the message according to what you give or according to the role
     * @param Player $player
     * @param string $message
     * @param string|null $format
     * @return \Generator
     */
    public function formatMessage(Player $player, string $message, string|null $format = null): \Generator
    {
        return Await::promise(function ($resolve, $reject) use($player, $message, $format){


            $rolePlayer = RolePlayerManager::getInstance()->getPlayer($player);
            if ($rolePlayer === null){
                $reject(new RolePlayerNotFoundException());
                return;
            }
            $format ??= $rolePlayer->getRole()->getChatFormat();
            foreach ($this->getChatAttributesAll() as $chatAttribute) {
                $chatAttribute->runChangeChat($player, $message, $format);
            }
            $resolve(new CustomChatFormatter($format));
        });
    }

    /**
     * this method allows you to give the final formatting of the nameTag according to what you give or according to the role
     * @param Player $player
     * @param string|null $format
     * @return void
     */
    public function formatNameTag(Player $player, string|null $format = null): \Generator
    {
        return Await::promise(function ( $resolve, $reject) use($player, $format){

            $rolePlayer = RolePlayerManager::getInstance()->getPlayer($player);
            if ($rolePlayer === null){
                $reject(new RolePlayerNotFoundException());
                return;
            }
            $format ??= $rolePlayer->getRole()->getNameTagFormat();
            foreach ($this->getNameTagAttributesAll() as $nameTagAttribute) {
                $nameTagAttribute->runChangeNameTag($player, $format);
            }
            $resolve($format);
        });

    }
}