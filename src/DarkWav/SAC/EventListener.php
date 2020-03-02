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
    $this->Main   = $mn;
    $this->Logger = $mn->getServer()->getLogger();
    $this->Server = $mn->getServer();
  }
  
  public function onJoin(PlayerJoinEvent $event)
  {
    $plr      = $event->getPlayer();
    $hash     = spl_object_hash($plr);
    $name     = $plr->getName();
    $oldhash  = null;
    $analyzer = null;
    foreach ($this->Main->Analyzers as $key=>$ana)
    {
      if ($ana->PlayerName == $name)
      {
        $oldhash  = $key;
        $analyzer = $ana;
        $analyzer->Player = $plr;
      }
    }
    if ($oldhash != null)
    {
      unset($this->Main->Analyzers[$oldhash]);
      $this->Main->Analyzers[$hash] = $analyzer;
      $this->Main->Analyzers[$hash]->onPlayerRejoin();
    }
    else
    {
      $analyzer = new Analyzer($plr, $this->Main);
      $this->Main->Analyzers[$hash] = $analyzer;
      $this->Main->Analyzers[$hash]->onPlayerJoin();
    }
  }

  public function onQuit(PlayerQuitEvent $event)
  {
    $plr      = $event->getPlayer();
    $hash     = spl_object_hash($plr);
    if (!empty($plr) and !empty($hash) and array_key_exists($hash , $this->Main->Analyzers))
    {
      $analyzer = $this->Main->Analyzers[$hash];
      if (!empty($analyzer))
      {
        $analyzer->onPlayerQuit();
      }
      $this->Main->Analyzers[$hash]->Player = null;
    }
  }

  public function onMove(PlayerMoveEvent $event)
  {
    $plr      = $event->getPlayer();
    $hash     = spl_object_hash($plr);
    if (array_key_exists($hash , $this->Main->Analyzers))
    {
      if($plr != null and $this->Main->Analyzers[$hash]->Player != null)
      {
        $this->Main->Analyzers[$hash]->onPlayerMoveEvent($event);
      }
    }
  }
  
  public function onDamage(EntityDamageEvent $event)
  {
    $entity       = $event->getEntity();
    $entityHash   = spl_object_hash($entity);
    if (array_key_exists($hash , $this->Main->Analyzers))
    {
      if($entity instanceof Player and $entity != null and $this->Main->Analyzers[$entityHash]->Player != null)
      {
        $this->Main->Analyzers[$entityHash]->onPlayerGetsHit($event);
      }
    }
    if($event instanceof EntityDamageByEntityEvent)
    {
      $damager      =  $event->getDamager();
      $damagerHash  = spl_object_hash($entity);
      if($damager instanceof Player and $damager != null and $this->Main->Analyzers[$damagerHash]->Player != null)
      {
        if ($event->getCause() == EntityDamageEvent::CAUSE_ENTITY_ATTACK)
        {
          $this->Main->Analyzers[$damagerHash]->onPlayerPerformsHit($event);
        }
      }
    }
  }

}
