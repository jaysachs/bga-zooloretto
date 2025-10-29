<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * babylonia implementation : © Jay Sachs <vagabond@covariant.org>
 *
 * Copyright 2025 Jay Sachs <vagabond@covariant.org>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 */

declare(strict_types=1);

namespace Bga\Games\zooloretto\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;


/*
    8 => array(
    		"name" => "Swap",
    		"description" => clienttranslate('${actplayer} must swap two sets on animals.'),
    		"descriptionmyturn" => clienttranslate('${you} must swap two sets on animals.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "Swap", "Back" ),
    		"transitions" => array( "Back" => 2, "NextPlayer" => 4 )
    ),
*/

class Swap extends GameState
{
    function __construct(private Game $game)
    {
        parent::__construct(
            game: $game,
            id: 8,
            type: StateType::ACTIVE_PLAYER,
    		description: clienttranslate('${actplayer} must swap two sets on animals.'),
    		descriptionMyTurn: clienttranslate('${you} must swap two sets on animals.'),
        );
    }

    public function getArgs(int $active_player_id): array
    {
        return [];
    }

    #[PossibleAction]
    public function actSwap(
        string $enc1,
        string $enc2,
        string $anid
    ): mixed {
		$player_id = $this->game->getCurrentPlayerId();
		$player_no = $this->game->getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );
		$tiles1 = "";
		$tiles2 = "";
		if ($enc1!="0")
		{
			$tiles1 = $this->game->getObjectListFromDB( "SELECT id,val,x,y from animals where player_id ='$player_id' and status = 'PLAYED' and x = $enc1" );
			$count = 0;
			foreach( $tiles1 as $index => $tile1)
			{
				$count = $count + 1;
				$sql = "update animals set x = $enc2, y = $count, status='PLAYED2' where id = '".$tile1['id']."'";
				$this->game->DbQuery( $sql );
			}
		}
		else
		{
			$tiles1 = $this->game->getObjectListFromDB( "SELECT id,val,x,y from animals where player_id ='$player_id' and status = 'STALL' and left(val,1) = '$anid'" );
			$count = 0;
			foreach( $tiles1 as $index => $tile1)
			{
				$count = $count + 1;
				$sql = "update animals set x = $enc2, y = $count, status='PLAYED2' where id = '".$tile1['id']."'";
				$this->game->DbQuery( $sql );
			}
		}
		if ($enc1!="0")
		{
			$tiles2 = $this->game->getObjectListFromDB( "SELECT id,val,x,y from animals where player_id ='$player_id' and status = 'PLAYED' and x = $enc2" );
			$count = 0;
			foreach( $tiles2 as $index => $tile2)
			{
				$count = $count + 1;
				$sql = "update animals set x = $enc1, y = $count, status='PLAYED2' where id = '".$tile2['id']."'";
				$this->game->DbQuery( $sql );
			}
		}
		else
		{
			$tiles2 = $this->game->getObjectListFromDB( "SELECT id,val,x,y from animals where player_id ='$player_id' and status = 'PLAYED' and x = $enc2" );
			$count = 0;
			foreach( $tiles2 as $index => $tile2)
			{
				$count = $count + 1;
				$sql = "update animals set x = 0, y = 0, status='STALL' where id = '".$tile2['id']."'";
				$this->game->DbQuery( $sql );
			}
		}

		$sql = "update player set money = money - 1 where player_id = '$player_id'";
		$this->game->DbQuery( $sql );

		$this->game->incStat( 1, "coinsspent", $player_id);

		$sql = "update animals set status='PLAYED' where status='PLAYED2'";
		$this->game->DbQuery( $sql );

		if ($enc1!="0")
		{
			$this->game->notifyAllPlayers( "SwapTiles", clienttranslate( '${player_name} swapped the tiles of his ${pos1} and ${pos2} enclosures.'),
			array(
				'player_id' => $player_id,
				'player_no' => $player_no,
				'enc1' => $enc1,
				'enc2' => $enc2,
				'tiles1' => $tiles1,
				'tiles2' => $tiles2,
				'pos1' => $this->DecodePos($enc1),
				'pos2' => $this->DecodePos($enc2),
				'anid' => $anid,
				'player_name' => $this->game->getCurrentPlayerName(),
				'i18n' => array( 'pos1','pos2' )
			) );
		}
		else
		{
			$this->game->notifyAllPlayers( "SwapTiles", clienttranslate( '${player_name} swapped the tiles of his Barn and ${pos2} enclosures.'),
			array(
				'player_id' => $player_id,
				'player_no' => $player_no,
				'enc1' => $enc1,
				'enc2' => $enc2,
				'tiles1' => $tiles1,
				'tiles2' => $tiles2,
				'pos1' => $this->DecodePos($enc1),
				'pos2' => $this->DecodePos($enc2),
				'anid' => $anid,
				'player_name' => $this->game->getCurrentPlayerName(),
				'i18n' => array( 'pos1','pos2' )
			) );
		}


		if ($enc1=="0")
		{
			////////////////////////////
			// KIDS
			////////////////////////////

			$enclosures = $this->game->getObjectListFromDB( "SELECT distinct x as x from animals where x = $enc2" );
			foreach( $enclosures as $index => $enclosure)
			{
				$males = intval($this->game->getUniqueValueFromDB("select count(*) from animals where id < 300 and val like '_M' and player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
				$females = intval($this->game->getUniqueValueFromDB("select count(*) from animals where id < 300 and val like '_F' and player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
				$kids = intval($this->game->getUniqueValueFromDB("select count(*) from animals where val like '_K' and player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
				$parents = min($males,$females);
				$maxid = intval($this->game->getUniqueValueFromDB("select MAX(id) from animals where id < 300" ));
				$spacesoccupied = intval($this->game->getUniqueValueFromDB("select count(*) from animals where player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
				$animal = $this->game->getUniqueValueFromDB("select distinct LEFT(val , 1) from animals where player_id='$player_id' and x = '" . $enclosure['x']. "'");
				$spacesleft = 0;
				if ($enclosure['x']=="1") $spacesleft = 5 - $spacesoccupied;
				else if ($enclosure['x']=="2") $spacesleft = 4 - $spacesoccupied;
				else if ($enclosure['x']=="3") $spacesleft = 6 - $spacesoccupied;
				else if ($enclosure['x']=="4") $spacesleft = 5 - $spacesoccupied;
				else if ($enclosure['x']=="5") $spacesleft = 5 - $spacesoccupied;
				$totalkids = $parents; //-$kids;

				$sql = "update animals set id = id + 300 where id < 300 and val like '_M' and player_id='$player_id' and x = '" . $enclosure['x']. "' limit $totalkids";
				$this->game->DbQuery( $sql );
				$sql = "update animals set id = id + 300 where id < 300 and val like '_F' and player_id='$player_id' and x = '" . $enclosure['x']. "' limit $totalkids";
				$this->game->DbQuery( $sql );

				$newparents = $this->game->getObjectListFromDB( "SELECT concat('tile_',b.player_no,'_',a.id-300,'_',a.val,'_',a.x,'_',a.y) oldparenttile, concat('tile_',b.player_no,'_',a.id,'_',a.val,'_',a.x,'_',a.y) parenttile, a.id, a.val,a.x,a.y, b.player_no FROM `animals` a, player b WHERE a.player_id = b.player_id and a.id > 300");

				for( $x=1; $x<= $totalkids; $x++ )
				{
					if ($spacesleft>0)
					{
						$maxid = $maxid + 1;
						$spacesleft = $spacesleft - 1;
						$spacesoccupied = $spacesoccupied + 1;

						$found = false;
						$spaceid = 0;
						while (!$found)
						{
							$spaceid = $spaceid + 1;
							$check = intval($this->game->getUniqueValueFromDB("select count(*) from animals where player_id='$player_id' and x = '" . $enclosure['x']. "' and y='$spaceid'" ));
							if ($check==0)
							{
								$found = true;
							}
						}

						$sql = "insert into animals (id, idsel, idorder, player_id, val, status,x,y) values ('" . $maxid . "',null,0,'$player_id','".$animal."K','THIKINGKID',".$enclosure['x'].", $spaceid)";
						$this->game->DbQuery( $sql );
						$kids = $this->game->getObjectListFromDB( "SELECT concat('tile_',$player_no,'_',id,'_',val,'_',x,'_',y) kidtile, id, val,x,y FROM `animals` WHERE status = 'THIKINGKID'");
						$kidsstall = "";

						$this->game->notifyAllPlayers( "Babies", clienttranslate( '${player_name} received a newborn ${translatedval} that goes into his enclosure.'),
						array(
							'player_id' => $player_id,
							'player_no' => $player_no,
							'kids' => $kids,
							'kidsstall' => $kidsstall,
							'player_name' => $this->game->getCurrentPlayerName(),
							'translatedval' => $this->DecodeAnimal($animal."K"),
							'newparents'=>$newparents,
							'i18n' => array( 'translatedval' )
						) );

						$sql = "update animals set status = 'PLAYED' where status = 'THIKINGKID'";
						$this->game->DbQuery( $sql );
						$sql = "update animals set status = 'STALL' where status = 'THIKINGKIDSTALL'";
						$this->game->DbQuery( $sql );
					}
					else
					{
						$maxid = $maxid + 1;
						$sql = "insert into animals (id, idsel, idorder, player_id, val, status,x,y) values ('" . $maxid . "',null,0,'$player_id','".$animal."K','THIKINGKIDSTALL',0,0)";
						$this->game->DbQuery( $sql );
						$kids = "";
						$kidsstall = $this->game->getObjectListFromDB( "SELECT concat('tile_',".$player_no.",'_',id,'_',val,'_',x,'_',y) as kidtile, id, val,x,y FROM `animals` WHERE status = 'THIKINGKIDSTALL'");

						$this->game->notifyAllPlayers( "Babies", clienttranslate( '${player_name} received a newborn ${translatedval} that goes into his Barn.'),
						array(
							'player_id' => $player_id,
							'player_no' => $player_no,
							'kids' => $kids,
							'kidsstall' => $kidsstall,
							'player_name' => $this->game->getCurrentPlayerName(),
							'translatedval' => $this->DecodeAnimal($animal."K"),
							'newparents'=>$newparents,
							'i18n' => array( 'translatedval' )
						) );

						$sql = "update animals set status = 'PLAYED' where status = 'THIKINGKID'";
						$this->game->DbQuery( $sql );
						$sql = "update animals set status = 'STALL' where status = 'THIKINGKIDSTALL'";
						$this->game->DbQuery( $sql );
					}
				}
			}
		}
        return NextPlayer::class;
    }

    #[PossibleAction]
    public function actBack(): mixed {
        return PlayerTurn::class;
    }

    function zombie(int $playerId): mixed {
        // FIXME
        return "";
    }
}
