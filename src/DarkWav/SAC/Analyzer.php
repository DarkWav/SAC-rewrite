<?php
declare(strict_types=1);
namespace DarkWav\SAC;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2020 DarkWav and others.
 */

use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;

use DarkWav\SAC\Main;
use DarkWav\SAC\KickTask;
use DarkWav\SAC\CheckRegister;

class Analyzer
{
  #golobal variables
  public $Main;
  public $Player;
  public $PlayerName;
  public $Server;
  public $Logger;
  public $Colorized;
  public $CheckRegister;

  #data

  public $isPvp;
  public $damagedEntityPosition;
  public $damagedEntityPositionXZ;
  public $playerPosition;
  public $playerPositionXZ;
  public $playerFacingDirection;
  public $playerFacingDirectionXZ;
  public $directionToTarget;
  public $directionToTargetXZ;
  public $hitDistance;
  public $hitDistanceXZ;
  public $directionDotProduct;
  public $directionDotProductXZ;
  public $hitAngle;
  public $hitAngleXZ;
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

    $this->Main              = $sac;
    $this->Player            = $plr;
    $this->PlayerName        = $this->Player->getName();
    $this->Server            = $this->Main->server;
    $this->Logger            = $this->Main->logger;
    $this->Colorized         = $this->Main->Colorized;
    $this->CheckRegister     = new CheckRegister($this);
    
    #initialize data variables

    #processPlayerPerformsHit() data

    $this->isPvp                   = false;
    $this->damagedEntityPosition   = new Vector3(0.0, 0.0, 0.0);
    $this->damagedEntityPositionXZ = new Vector3(0.0, 0.0, 0.0);
    $this->playerPosition          = new Vector3(0.0, 0.0, 0.0);
    $this->playerPositionXZ        = new Vector3(0.0, 0.0, 0.0);
    $this->playerFacingDirection   = new Vector3(0.0, 0.0, 0.0);
    $this->playerFacingDirectionXZ = new Vector3(0.0, 0.0, 0.0);
    $this->directionToTarget       = new Vector3(0.0, 0.0, 0.0);
    $this->directionToTargetXZ     = new Vector3(0.0, 0.0, 0.0);
    $this->hitDistance             = new Vector3(0.0, 0.0, 0.0); #Reach Distance
    $this->hitDistanceXZ           = new Vector3(0.0, 0.0, 0.0); #Reach Distance (only X and Z axis)
    $this->directionDotProduct     = 0;
    $this->directionDotProductXZ   = 0;
    $this->hitAngle                = 0; #Hit Angle
    $this->hitAngleXZ              = 0; #Hit Angle (only X and Z axis)
    
    #processPlayerMoveEvent() data
    
    $this->FromXZPos               = new Vector3(0.0, 0.0, 0.0);
    $this->ToXZPos                 = new Vector3(0.0, 0.0, 0.0);
    $this->FromYPos                = new Vector3(0.0, 0.0, 0.0);
    $this->ToYPos                  = new Vector3(0.0, 0.0, 0.0);
    $this->XZDistance              = 0.0;
    $this->YDistance               = 0.0;
    $this->PreviousTick            = -1.0;
    $this->XZRingBufferSize        = 8;
    $this->YRingBufferSize         = 8;
    $this->XZRingBufferIndex       = 0;
    $this->YRingBufferIndex        = 0;
    $this->XZTimeRingBuffer        = array_fill(0, $this->XZRingBufferSize, 0.0);
    $this->XZDistanceRingBuffer    = array_fill(0, $this->XZRingBufferSize, 0.0);
    $this->YTimeRingBuffer         = array_fill(0, $this->YRingBufferSize , 0.0);
    $this->YDistanceRingBuffer     = array_fill(0, $this->YRingBufferSize , 0.0);
    $this->XZTimeSum               = 0.0;
    $this->XZDistanceSum           = 0.0;
    $this->YTimeSum                = 0.0;
    $this->YDistanceSum            = 0.0;
    $this->XZSpeed                 = 0.0;
    $this->YSpeed                  = 0.0;
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
    $this->CheckRegister->runChecksOnPlayerMoveEvent($event);
  }
  
  public function onPlayerGetsHit($event): void
  {
  
  }

  public function onPlayerPerformsHit($event): void
  {
    #process data
    $this->processPlayerPerformsHit($event);
    #then run checks
    $this->CheckRegister->runChecksOnPlayerPerformsHit($event);
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
    $damagedEntity = $event->getEntity();
    if ($damagedEntity instanceof Player)
    {
      $this->isPvp = true;
    }
    else
    {
      $this->isPvp = false;
    }
    $this->damagedEntityPosition      = new Vector3($damagedEntity->getX(), $damagedEntity->getY(), $damagedEntity->getZ());
    $this->damagedEntityPositionXZ    = new Vector3($damagedEntity->getX(), 0                     , $damagedEntity->getZ());
    $this->playerPosition             = new Vector3($this->Player->getX() , $this->Player->getY() , $this->Player->getZ() );
    $this->playerPositionXZ           = new Vector3($this->Player->getX() , 0                     , $this->Player->getZ() );
    $this->playerFacingDirection      = $this->Player->getDirectionVector()->normalize();
    $this->playerFacingDirectionXZ    = $this->Player->getDirectionVector();
    $this->playerFacingDirectionXZ->y = 0;
    $this->playerFacingDirectionXZ    = $this->playerFacingDirectionXZ->normalize();
    $this->directionToTarget          = $this->damagedEntityPosition->subsract($this->playerPosition)->normalize();
    $this->directionToTargetXZ        = $this->damagedEntityPositionXZ->subsract($this->playerPositionXZ)->normalize();
    $this->hitDistance                = $this->playerPosition->distance($this->damagedEntityPosition);
    $this->hitDistanceXZ              = $this->playerPositionXZ->distance($this->damagedEntityPositionXZ);
    #here comes the maths bois!
    $this->directionDotProduct        = $this->playerPosition->dot($this->damagedEntityPosition);
    $this->directionDotProductXZ      = $this->playerPositionXZ->dot($this->damagedEntityPositionXZ);
    $this->hitAngle                   = rad2deg(acos($this->directionDotProduct));
    $this->hitAngleXZ                 = rad2deg(acos($this->directionDotProductXZ));
  }
  
  #util functions

  public function kickPlayer($message) : void
  {
    $this->Main->getScheduler()->scheduleDelayedTask(new KickTask($this->Main, $this->Player, $message), 1);
  }

}
