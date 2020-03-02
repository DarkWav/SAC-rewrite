<?php
declare(strict_types=1);

namespace DarkWav\SAC;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2020 DarkWav and others.
 */

#basic imports

use pocketmine\event\Listener;
use pocketmine\event\Cancellable;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;
use DarkWav\SAC\Main;
use DarkWav\SAC\Analyzer;

#event that are listened

use pocketmine\event\player\PlayerMoveEvent;

class EventListener implements Listener
{
    public $Main;
    public $Logger;
    public $Server;

    public function __construct(Main $mn)
    {
        $this->Main = $mn;
        $this->Logger = $mn->getServer()->getLogger();
        $this->Server = $mn->getServer();
    }

    public function onJoin(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();
        $uuid = $player->getRawUniqueId();

        $this->Main->Analyzers[$uuid] = new Analyzer($player, $this->Main);
        $this->Main->Analyzers[$uuid]->onPlayerJoin();
    }

    public function onQuit(PlayerQuitEvent $event)
    {
        $player = $event->getPlayer();
        $uuid = $player->getRawUniqueId();

        unset($this->Main->Analyzers[$uuid]);
    }

    public function onMove(PlayerMoveEvent $event)
    {
        $player = $event->getPlayer();
        $analyzer = $this->Main->Analyzers[$player->getRawUniqueId()];

        if ($analyzer !== null) {
            $analyzer->onPlayerMoveEvent($event);
        }
    }

    public function onDamage(EntityDamageEvent $event)
    {
        $entity = $event->getEntity();

        if ($entity instanceof Player) {
            $analyzer = $this->Main->Analyzers[$entity->getRawUniqueId()];

            if ($analyzer !== null) {
                $analyzer->onPlayerGetsHit();
            }
        }

        if ($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();

            if ($damager instanceof Player) {
                $analyzer = $this->Main->Analyzers[$damager->getRawUniqueId()];

                if ($analyzer !== null) {
                    $analyzer->onPlayerPerformsHit($event);
                }
            }
        }
    }
}
