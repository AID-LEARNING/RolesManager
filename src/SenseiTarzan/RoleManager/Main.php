<?php
namespace SenseiTarzan\RoleManager;

use CortexPE\Commando\PacketHooker;
use pocketmine\plugin\PluginBase;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\Path\PathScanner;
use SenseiTarzan\RoleManager\Commands\RoleCommands;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Component\TextAttributeManager;
use SenseiTarzan\RoleManager\Listener\PlayerListener;
use SenseiTarzan\RoleManager\Task\NameTagTask;
use Webmozart\PathUtil\Path;

class Main extends PluginBase
{

    public function onLoad(): void
    {
        if (!file_exists(Path::join($this->getDataFolder(), "config.yml"))) {
            foreach (PathScanner::scanDirectoryGenerator($search = Path::join(dirname(__DIR__,3) , "resources")) as $file){
                @$this->saveResource(str_replace($search, "", $file));
            }
        }
        new LanguageManager($this);
        new RoleManager($this);
        new TextAttributeManager();
    }

    protected function onEnable(): void
    {
        if (!PacketHooker::isRegistered()) {
            PacketHooker::register($this);
        }

        $this->getServer()->getPluginManager()->registerEvents(new PlayerListener(), $this);
        $this->getScheduler()->scheduleRepeatingTask(new NameTagTask(), 20);
        $this->getServer()->getCommandMap()->register("senseitarzan", new RoleCommands($this, "role", "Role Command", ["group"]));
    }
}