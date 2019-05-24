<?php
declare(strict_types=1);

namespace DarkWav\SAC;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2019 DarkWav
 */

use AttachableThreadedLogger;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Server;

class EventListener implements Listener
{
    /**
     * @var Main $Main
     */
    public $Main;

    /**
     * @var AttachableThreadedLogger $Logger
     */
    public $Logger;

    /**
     * @var Server $Server
     */
    public $Server;

    /**
     * EventListener constructor.
     * @param Main $mn
     */
    public function __construct(Main $mn)
    {
        $this->Main = $mn;
        $this->Logger = $mn->getServer()->getLogger();
        $this->Server = $mn->getServer();
    }

    /**
     * @priority normal
     * @param PlayerJoinEvent $event
     */
    public function onJoin(PlayerJoinEvent $event)
    {
        $plr = $event->getPlayer();
        $hash = spl_object_hash($plr);
        $name = $plr->getName();
        $oldHash = null;
        $analyzer = null;
        foreach ($this->Main->Analyzers as $key => $ana) {
            if ($ana->PlayerName == $name) {
                $oldHash = $key;
                $analyzer = $ana;
                $analyzer->Player = $plr;
            }
        }
        if ($oldHash != null) {
            unset($this->Main->Analyzers[$oldHash]);
            $this->Main->Analyzers[$hash] = $analyzer;
            $this->Main->Analyzers[$hash]->onPlayerRejoin();
        } else {
            $analyzer = new Analyzer($plr, $this->Main);
            $this->Main->Analyzers[$hash] = $analyzer;
            $this->Main->Analyzers[$hash]->onPlayerJoin();
        }
    }

    /**
     * @priority normal
     * @param PlayerQuitEvent $event
     */
    public function onQuit(PlayerQuitEvent $event)
    {
        $plr = $event->getPlayer();
        $hash = spl_object_hash($plr);
        if (!empty($plr) and !empty($hash) and array_key_exists($hash, $this->Main->Analyzers)) {
            $analyzer = $this->Main->Analyzers[$hash];
            if (!empty($analyzer)) {
                $analyzer->onPlayerQuit();
            }
            $this->Main->Analyzers[$hash]->Player = null;
        }
    }
}