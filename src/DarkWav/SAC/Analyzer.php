<?php
declare(strict_types=1);
namespace DarkWav\SAC;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2019 DarkWav
 */

use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;

use DarkWav\SAC\Main;
use DarkWav\SAC\KickTask;

#import checks

use DarkWav\SAC\checks\AngleCheck;
use DarkWav\SAC\checks\AutoClickerCheck;
use DarkWav\SAC\checks\CombatHeuristics;
use DarkWav\SAC\checks\CriticalsCheck;
use DarkWav\SAC\checks\FastBowCheck;
use DarkWav\SAC\checks\FastBreakCheck;
use DarkWav\SAC\checks\FastPlaceCheck;
use DarkWav\SAC\checks\FlyCheck;
use DarkWav\SAC\checks\GlideCheck;
use DarkWav\SAC\checks\NoClipCheck;
use DarkWav\SAC\checks\ReachCheck;
use DarkWav\SAC\checks\RegenCheck;
use DarkWav\SAC\checks\SpeedCheck;
use DarkWav\SAC\checks\SpiderCheck;
use DarkWav\SAC\checks\VClipCheck;

class Analyzer
{
  #golobal variables
  public $Main;
  public $Player;
  public $PlayerName;
  public $Server;
  public $Logger;
  public $Colorized;

  #checks

  public $AngleCheck;
  public $AutoClickerCheck;
  public $CombatHeuristics;
  public $CriticalsCheck;
  public $FastBowCheck;
  public $FastBreakCheck;
  public $FastPlaceCheck;
  public $FlyCheck;
  public $GlideCheck;
  public $NoClipCheck;
  public $ReachCheck;
  public $RegenCheck;
  public $SpeedCheck;
  public $SpiderCheck;
  public $VClipCheck;

  #data

  public $FromXZPos;
  public $ToXZPos;
  public $XZDistance;
  public $PreviousTick;
  public $XZTimeRingBuffer;
  public $XZDistanceRingBuffer;
  public $YTimeRingBuffer;
  public $YDistanceRingBuffer;
  public $XZRingBufferSize;
  public $YRingBufferSize;
  public $XZRingBufferIndex;
  public $YRingBufferIndex;
  public $XZTimeSum;
  public $XZDistanceSum;
  public $YTimeSum;
  public $YDistanceSum;
  public $XZSpeed;
  public $YSpeed;

  public function __construct($plr, Main $sac)
  {

    #initialize basic variables

    $this->Main       = $sac;
    $this->Player     = $plr;
    $this->PlayerName = $this->Player->getName();
    $this->Server     = $this->Main->server;
    $this->Logger     = $this->Main->logger;
    $this->Colorized  = $this->Main->Colorized;

    #initialize checks

    $this->AngleCheck       = new AngleCheck($this);
    $this->AutoClickerCheck = new AutoClickerCheck($this);
    $this->CombatHeuristics = new CombatHeuristics($this);
    $this->CriticalsCheck   = new CriticalsCheck($this);
    $this->FastBowCheck     = new FastBowCheck($this);
    $this->FastBreakCheck   = new FastBreakCheck($this);
    $this->FastPlaceCheck   = new FastPlaceCheck($this);
    $this->FlyCheck         = new FlyCheck($this);
    $this->GlideCheck       = new GlideCheck($this);
    $this->NoClipCheck      = new NoClipCheck($this);
    $this->ReachCheck       = new ReachCheck($this);
    $this->RegenCheck       = new RegenCheck($this);
    $this->SpeedCheck       = new SpeedCheck($this);
    $this->SpiderCheck      = new SpiderCheck($this);
    $this->VClipCheck       = new VClipCheck($this);

    #initialize data variables

    #processPlayerMoveEvent() data

    $this->FromXZPos            = new Vector3(0.0, 0.0, 0.0);
    $this->ToXZPos              = new Vector3(0.0, 0.0, 0.0);
    $this->FromYPos             = new Vector3(0.0, 0.0, 0.0);
    $this->ToYPos               = new Vector3(0.0, 0.0, 0.0);
    $this->XZDistance           = 0.0;
    $this->YDistance            = 0.0;
    $this->PreviousTick         = -1.0;
    $this->XZRingBufferSize     = 8;
    $this->YRingBufferSize      = 8;
    $this->XZRingBufferIndex    = 0;
    $this->YRingBufferIndex     = 0;
    $this->XZTimeRingBuffer     = array_fill(0, $this->XZRingBufferSize, 0.0);
    $this->XZDistanceRingBuffer = array_fill(0, $this->XZRingBufferSize, 0.0);
    $this->YTimeRingBuffer      = array_fill(0, $this->YRingBufferSize , 0.0);
    $this->YDistanceRingBuffer  = array_fill(0, $this->YRingBufferSize , 0.0);
    $this->XZTimeSum            = 0.0;
    $this->XZDistanceSum        = 0.0;
    $this->YTimeSum             = 0.0;
    $this->YDistanceSum         = 0.0;
    $this->XZSpeed              = 0.0;
    $this->YSpeed               = 0.0;

  }

  # Event handlers
  # these will work with events redirected from EventListener
  
  public function onPlayerJoin() : void
  {
    $this->Player->sendMessage(TextFormat::ESCAPE.$this->Colorized."[SAC] > $this->PlayerName, I am watching you ...");
    $this->Logger->info(TextFormat::ESCAPE.$this->Colorized."[SAC] > $this->PlayerName, I am watching you ...");
  }

  public function onPlayerRejoin() : void
  {
    $this->Player->sendMessage(TextFormat::ESCAPE.$this->Colorized."[SAC] > $this->PlayerName, I am still watching you ...");
    $this->Logger->info(TextFormat::ESCAPE.$this->Colorized."[SAC] > $this->PlayerName, I am still watching you ...");
  }

  public function onPlayerQuit() : void
  {
    $this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "[SAC] > $this->PlayerName is no longer watched...");
  }

  public function onPlayerMoveEvent($event) : void
  {
    #process event first
    $this->processPlayerMoveEvent($event);
    #then run checks
    $this->SpeedCheck->run($event);
  }
  
  public function onPlayerGetsHit($event): void
  {
  
  }

  public function onPlayerPerformsHit($event): void
  {
    #process data
    $this->processPlayerPerformsHit($event);
    #run regular checks
    $this->AngleCheck->run($event);
    $this->AutoClickerCheck->run($event);
    $this->CriticalsCheck->run($event);
    $this->ReachCheck->run($event);
    #run heuristics
    $this->CombatHeuristics->run($event);
  }

  # processing functions
  # these will process data retrieved from events
  
  public function processPlayerMoveEvent($event) : void
  {
    #calculate distance travelled in the event itself in XZ and Y axis.
    $this->FromXZPos    = new Vector3($event->getFrom()->getX(), 0.0, $event->getFrom()->getZ());
    $this->ToXZPos      = new Vector3($event->getTo()->getX()  , 0.0, $event->getTo()->getZ()  );
    $this->XZDistance   = $this->FromXZPos->distance($this->ToXZPos);
    $this->FromYPos     = new Vector3(0.0, $event->getFrom()->getY(), 0.0);
    $this->ToYPos       = new Vector3(0.0, $event->getTo()->getY()  , 0.0);
    $this->YDistance    = $this->FromYPos->distance($this->ToYPos);

    $Tick               = (double)$this->Server->getTick();
    $TPS                = (double)$this->Server->getTicksPerSecond();
    $TickCount          = (double)($Tick - $this->PreviousTick);
    $TimeDiff           = (double)($TickCount) / (double)$TPS;
    if ($TPS > 0.0 and $this->PreviousTick != -1.0)
    {
      if($TimeDiff < 2.0) #if move events are divided too far apart each other, ignore the move
      {
        #write distances and times into a a ringbuffer
        $this->XZTimeSum                                      = $this->XZTimeSum     - $this->XZTimeRingBuffer    [$this->XZRingBufferIndex] + $TimeDiff; #ringbuffer time sum (remove oldest, add new)
        $this->XZDistanceSum                                  = $this->XZDistanceSum - $this->XZDistanceRingBuffer[$this->XZRingBufferIndex] + $this->XZDistance; #ringbuffer distance sum (remove oldest, add new) 
        $this->XZTimeRingBuffer    [$this->XZRingBufferIndex] = $TimeDiff; #overwrite oldest delta_t  with the new one
        $this->XZDistanceRingBuffer[$this->XZRingBufferIndex] = $this->XZDistance; #overwrite oldest distance with the new one          
        $this->XZRingBufferIndex++; #Update ringbuffer position
        if ($this->XZRingBufferIndex >= $this->XZRingBufferSize)
        {
          $this->XZRingBufferIndex = 0; #make ringbuffer index reset once its at the end of the ringbuffer
        }
        $this->YTimeSum                                      = $this->YTimeSum     - $this->YTimeRingBuffer    [$this->YRingBufferIndex] + $TimeDiff; #ringbuffer time sum (remove oldest, add new)
        $this->YDistanceSum                                  = $this->YDistanceSum - $this->YDistanceRingBuffer[$this->YRingBufferIndex] + $this->YDistance; #ringbuffer distance sum (remove oldest, add new) 
        $this->YTimeRingBuffer    [$this->YRingBufferIndex]  = $TimeDiff; #overwrite oldest delta_t  with the new one
        $this->YDistanceRingBuffer[$this->YRingBufferIndex]  = $this->YDistance; #overwrite oldest distance with the new one          
        $this->YRingBufferIndex++; #Update ringbuffer position
        if ($this->YRingBufferIndex >= $this->YRingBufferSize)
        {
          $this->YRingBufferIndex = 0; #make ringbuffer index reset once its at the end of the ringbuffer
        }
      }
      #calculate actual average movement speed
      if ($this->XZTimeSum > 0)
      {
        $this->XZSpeed = (double)$this->XZDistanceSum / (double)$this->XZTimeSum; #speed = distance / time difference:
      }
      else
      {
        $this->XZSpeed = 0.0;
      }
      if ($this->YTimeSum > 0)
      {
        $this->YSpeed = (double)$this->YDistanceSum  / (double)$this->YTimeSum; #speed = distance / time difference:
      }
      else
      {
        $this->YSpeed = 0.0;
      }
    }
    $this->PreviousTick = $Tick;
  }

  public function processPlayerPerformsHit($event) : void
  {
  }
  
  #util functions

  public function kickPlayer($message) : void
  {
    $this->Main->getScheduler()->scheduleDelayedTask(new KickTask($this->Main, $this->Player, $message), 1);
  }

}
