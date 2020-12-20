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
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\MainLogger;

#events that are listened

use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;

class EventListener implements Listener
{
  /** @var Main */
  public Main $Main;
  /** @var MainLogger */
  public MainLogger $Logger;
  /** @var Server */
  public Server $Server;

  /**
   * EventListener constructor.
   * @param Main $mn
   */
  public function __construct(Main $mn)
  {
    $this->Main   = $mn;
    $this->Logger = $mn->getServer()->getLogger();
    $this->Server = $mn->getServer();
  }

  /**
   * @param PlayerJoinEvent $event
   */
  public function onJoin(PlayerJoinEvent $event) : void
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

  /**
   * @param PlayerQuitEvent $event
   */
  public function onQuit(PlayerQuitEvent $event) : void
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
      unset($this->Main->Analyzers[$uuid]->Player);
    }
  }

  /**
   * @param PlayerMoveEvent $event
   */
  public function onMove(PlayerMoveEvent $event) : void
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

  /**
   * @param EntityMotionEvent $event
   */
  public function onMotion(EntityMotionEvent $event) : void
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
  
  /**
   * @param EntityShootBowEvent $event
   */
  public function onBowShot(EntityShootBowEvent $event) : void
  {
    $entity = $event->getEntity();
    if($entity instanceof Player)
    {
      $entityuuid = $entity->getRawUniqueID();
      if (array_key_exists($entityuuid , $this->Main->Analyzers))
      {
        if($entity != null and $this->Main->Analyzers[$entityuuid]->Player != null)
        {
          $this->Main->Analyzers[$entityuuid]->onPlayerShootsBow($event);
        }
      }
    }
  }
  
  /**
   * @param EntityRegainHealthEvent $event
   */
  public function onRegeneration(EntityRegainHealthEvent $event) : void
  {
    $entity = $event->getEntity();
    if($entity instanceof Player)
    {
      $entityuuid = $entity->getRawUniqueID();
      if (array_key_exists($entityuuid , $this->Main->Analyzers))
      {
        if($entity != null and $this->Main->Analyzers[$entityuuid]->Player != null)
        {
          #only listen if regeneration is natural
          $reason = $event->getRegainReason();
          if($reason == EntityRegainHealthEvent::CAUSE_REGEN || $reason == EntityRegainHealthEvent::CAUSE_EATING || $reason == EntityRegainHealthEvent::CAUSE_SATURATION)
          {
            $this->Main->Analyzers[$entityuuid]->onPlayerRegainsHealth($event);
          }
        }
      }
    }
  }

  /**
   * @param EntityDamageEvent $event
   */
  public function onDamage(EntityDamageEvent $event) : void
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
