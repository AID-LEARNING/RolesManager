<?php

namespace SenseiTarzan\RoleManager\Component;

use pocketmine\utils\SingletonTrait;
use SenseiTarzan\RoleManager\Class\Save\IDataSave;

class DataManager
{
    use SingletonTrait;

    public IDataSave|null $dataSystem;
    public function __construct()
    {
    }

    public function setSaveDataSystem(?IDataSave $saveData): void{
        $this->dataSystem = $saveData;
    }

    /**
     * @return IDataSave|null
     */
    public function getDataSystem(): ?IDataSave
    {
        return $this->dataSystem;
    }





}