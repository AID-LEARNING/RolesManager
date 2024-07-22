<?php
namespace SenseiTarzan\RoleManager;

use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\PacketHooker;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\network\mcpe\JwtException;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\types\login\AuthenticationData;
use pocketmine\network\mcpe\protocol\types\login\ClientData;
use pocketmine\network\mcpe\protocol\types\login\JwtChain;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\AsyncTask;
use pocketmine\scheduler\Task;
use pocketmine\utils\Internet;
use pocketmine\utils\SingletonTrait;
use SenseiTarzan\DataBase\Component\DataManager;
use SenseiTarzan\ExtraEvent\Component\EventLoader;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\Middleware\Component\MiddlewareManager;
use SenseiTarzan\Path\PathScanner;
use SenseiTarzan\RoleManager\Class\Middleware\RoleMiddleware;
use SenseiTarzan\RoleManager\Class\Save\JSONSave;
use SenseiTarzan\RoleManager\Class\Save\YAMLSave;
use SenseiTarzan\RoleManager\Commands\RoleCommands;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Component\TextAttributeManager;
use SenseiTarzan\RoleManager\Listener\PlayerListener;
use SenseiTarzan\RoleManager\Task\NameTagTask;
use SOFe\AwaitGenerator\Await;
use SOFe\AwaitStd\AwaitStd;
use Symfony\Component\Filesystem\Path;
use pocketmine\network\mcpe\JwtUtils;

class Main extends PluginBase
{

    use SingletonTrait;

    private AwaitStd $awaitStd;
    public function onLoad(): void
    {
        self::setInstance($this);
        if (!file_exists(Path::join($this->getDataFolder(), "config.yml"))) {
            foreach (PathScanner::scanDirectoryGenerator($search = Path::join(dirname(__DIR__,3) , "resources")) as $file){
                @$this->saveResource(str_replace($search, "", $file));
            }
        }
        new LanguageManager($this);
        new RoleManager($this);
        DataManager::getInstance()->setDataSystem(match (strtolower($this->getConfig()->get("data-type", "json"))) {
            "yml", "yaml" => new YAMLSave($this->getDataFolder()),
            "json" => new JSONSave($this->getDataFolder()),
            default => null
        });
        new TextAttributeManager();
        $this->awaitStd = AwaitStd::init($this);
    }

    protected function onEnable(): void
    {
        if (!PacketHooker::isRegistered()) {
            PacketHooker::register($this);
        }
        LanguageManager::getInstance()->loadCommands("role");

	    $hasMiddleware = $this->getServer()->getPluginManager()->getPlugin("Middleware") !== null;
	    if ($hasMiddleware)
			MiddlewareManager::getInstance()->addMiddleware(new RoleMiddleware());
        EventLoader::loadEventWithClass($this, new PlayerListener($hasMiddleware));

        if ($this->getConfig()->get("nametag-task-tick", 20)) {
            $this->getScheduler()->scheduleRepeatingTask(new NameTagTask(), 20);
        }

        $this->getServer()->getCommandMap()->register("rolemanager", new RoleCommands($this, "role", "Role Command", ["group"]));
    }

    /**
     * @return AwaitStd
     */
    public function getAwaitStd(): AwaitStd
    {
        return $this->awaitStd;
    }
}
