<?php
namespace SenseiTarzan\RoleManager;

use CortexPE\Commando\PacketHooker;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\AsyncTask;
use pocketmine\scheduler\Task;
use pocketmine\utils\Internet;
use pocketmine\utils\SingletonTrait;
use SenseiTarzan\DataBase\Component\DataManager;
use SenseiTarzan\ExtraEvent\Component\EventLoader;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\Path\PathScanner;
use SenseiTarzan\RoleManager\Class\Save\JSONSave;
use SenseiTarzan\RoleManager\Class\Save\YAMLSave;
use SenseiTarzan\RoleManager\Commands\RoleCommands;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Component\TextAttributeManager;
use SenseiTarzan\RoleManager\Listener\PlayerListener;
use SenseiTarzan\RoleManager\Task\NameTagTask;
use SOFe\AwaitGenerator\Await;
use Symfony\Component\Filesystem\Path;

class Main extends PluginBase
{

    use SingletonTrait;
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
    }

    protected function onEnable(): void
    {
        if (!PacketHooker::isRegistered()) {
            PacketHooker::register($this);
        }
        LanguageManager::getInstance()->loadCommands("role");

        EventLoader::loadEventWithClass($this, PlayerListener::class);

        if ($this->getConfig()->get("nametag-task-tick", 20)) {
            $this->getScheduler()->scheduleRepeatingTask(new NameTagTask(), 20);
        }

        $this->getServer()->getCommandMap()->register("senseitarzan", new RoleCommands($this, "role", "Role Command", ["group"]));
    }
}