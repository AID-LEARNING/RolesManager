<?php

namespace SenseiTarzan\RoleManager\Class\Middleware;

use Generator;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\SetLocalPlayerAsInitializedPacket;
use SenseiTarzan\DataBase\Component\DataManager;
use SenseiTarzan\Middleware\Class\IMiddleWare;

class RoleMiddleware implements IMiddleWare
{

	public function getName(): string
	{
		return "Role Middleware";
	}

	/**
	 * @inheritDoc
	 */
	public function onDetectPacket(): string
	{
		return SetLocalPlayerAsInitializedPacket::class;
	}

	public function getPromise(DataPacketReceiveEvent $event): Generator
	{
		return DataManager::getInstance()->getDataSystem()->loadDataPlayerByMiddleware($event->getOrigin()->getPlayer());
	}
}