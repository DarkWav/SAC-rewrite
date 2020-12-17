<?php
declare(strict_types=1);
namespace DarkWav\SAC;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2021 DarkWav and others.
 */

#basic imports

use pocketmine\event\Listener;
use pocketmine\event\Cancellable;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;
use DarkWav\SAC\Main;
use DarkWav\SAC\Analyzer;

#events that are listened

use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityMotionEvent;


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
    $uuid     = $plr->getRawUniqueID();
    $name     = $plr->getName();
    $olduuid  = null;
    $analyzer = null;
    foreach ($this->Main->Analyzers as $key=>$ana)
    {
      if ($ana->PlayerName == $name)
      {
        $olduuid  = $key;
        $analyzer = $ana;
        $analyzer->Player = $plr;
      }
    }
    if ($olduuid != null)
    {
      unset($this->Main->Analyzers[$olduuid]);
      $this->Main->Analyzers[$uuid] = $analyzer;
      $this->Main->Analyzers[$uuid]->onPlayerRejoin();
    }
    else
    {
      $analyzer = new Analyzer($plr, $this->Main);
      $this->Main->Analyzers[$uuid] = $analyzer;
      $this->Main->Analyzers[$uuid]->onPlayerJoin();
    }
  }

  public function onQuit(PlayerQuitEvent $event)
  {
    $plr      = $event->getPlayer();
    $uuid     = $plr->getRawUniqueID();
    if (!empty($plr) and !empty($uuid) and array_key_exists($uuid , $this->Main->Analyzers))
    {
      $analyzer = $this->Main->Analyzers[$uuid];
      if (!empty($analyzer))
      {
        $analyzer->onPlayerQuit();
      }
      $this->Main->Analyzers[$uuid]->Player = null;
    }
  }

  public function onMove(PlayerMoveEvent $event)
  {
    $plr      = $event->getPlayer();
    $uuid     = $plr->getRawUniqueID();
    if (array_key_exists($uuid , $this->Main->Analyzers))
    {
      if($plr != null and $this->Main->Analyzers[$uuid]->Player != null)
      {
        $this->Main->Analyzers[$uuid]->onPlayerMoveEvent($event);
      }
    }
  }
  
  public function onMotion(EntityMotionEvent $event)
  {
    $entity = $event->getEntity();
    if($entity instanceof Player)
    {
      $entityuuid = $entity->getRawUniqueID();
      if (array_key_exists($entityuuid , $this->Main->Analyzers))
      {
        if($entity != null and $this->Main->Analyzers[$entityuuid]->Player != null)
        {
          $this->Main->Analyzers[$entityuuid]->onPlayerReceivesMotion($event);
        }
      }
    }
  }
  
  public function onDamage(EntityDamageEvent $event)
  {
    $entity       = $event->getEntity();
    if($entity instanceof Player)
    {
      $entityuuid   = $entity->getRawUniqueID();
      if (array_key_exists($entityuuid , $this->Main->Analyzers))
      {
        if($entity != null and $this->Main->Analyzers[$entityuuid]->Player != null)
        {
          $this->Main->Analyzers[$entityuuid]->onPlayerGetsHit($event);
        }
      }
    }
    if($event instanceof EntityDamageByEntityEvent)
    {
      $damager      =  $event->getDamager();
      if($damager instanceof Player)
      {
        $damageruuid  =  $damager->getRawUniqueID();
        if (array_key_exists($damageruuid , $this->Main->Analyzers))
        {
          if($damager != null and $this->Main->Analyzers[$damageruuid]->Player != null)
          {
            if ($event->getCause() == EntityDamageEvent::CAUSE_ENTITY_ATTACK)
            {
              $this->Main->Analyzers[$damageruuid]->onPlayerPerformsHit($event);
            }
          }
        }
      }
    }
  }

}
