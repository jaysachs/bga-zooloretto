<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Zooloretto implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * zooloretto.action.php
 *
 * Zooloretto main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/zooloretto/zooloretto/myAction.html", ...)
 *
 */
  
  
  class action_zooloretto extends APP_GameAction
  { 
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "zooloretto_zooloretto";
            self::trace( "Complete reinitialization of board game" );
      }
  	} 
  	
  	// TODO: defines your action entry points there


	public function DrawTile()
    {
        self::setAjaxMode();     
        $result = $this->game->gDrawTile();
        self::ajaxResponse( );
    }

	public function PlaceTile()
    {
        self::setAjaxMode();     
        $x = self::getArg( "x", AT_alphanum, true );
		$y = self::getArg( "y", AT_alphanum, true );

        $result = $this->game->gPlaceTile($x, $y);
        self::ajaxResponse( );
    }
	
	public function TakeWagon()
    {
        self::setAjaxMode();     
        $x = self::getArg( "x", AT_alphanum, true );

        $result = $this->game->gTakeWagon($x);
        self::ajaxResponse( );
    }
	
	public function ConfirmDiscard()
    {
        self::setAjaxMode();     
        $tileid = self::getArg( "tileid", AT_alphanum, true );

        $result = $this->game->gConfirmDiscard($tileid);
        self::ajaxResponse( );
    }
	
	public function SwapTiles()
    {
        self::setAjaxMode();     
        $enc1 = self::getArg( "enc1", AT_alphanum, true );
        $enc2 = self::getArg( "enc2", AT_alphanum, true );
        $anid = self::getArg( "anid", AT_alphanum, true );
		

        $result = $this->game->gSwapTiles($enc1, $enc2, $anid);
        self::ajaxResponse( );
    }
	
	
	
	public function AutoArrangeTiles()
    {
        self::setAjaxMode();     
        $wagonid = self::getArg( "wagonid", AT_alphanum, true );
        $tileid1 = self::getArg( "tileid1", AT_alphanum, true );
        $posid1 = self::getArg( "posid1", AT_alphanum, true );
        $tileid2 = self::getArg( "tileid2", AT_alphanum, true );
        $posid2 = self::getArg( "posid2", AT_alphanum, true );
        $tileid3 = self::getArg( "tileid3", AT_alphanum, true );
        $posid3 = self::getArg( "posid3", AT_alphanum, true );
        $x1 = self::getArg( "x1", AT_alphanum, true );
        $y1 = self::getArg( "y1", AT_alphanum, true );
        $x2 = self::getArg( "x2", AT_alphanum, true );
        $y2 = self::getArg( "y2", AT_alphanum, true );
        $x3 = self::getArg( "x3", AT_alphanum, true );
        $y3 = self::getArg( "y3", AT_alphanum, true );

        $result = $this->game->gAutoArrangeTiles($wagonid,$tileid1,$posid1,$tileid2,$posid2,$tileid3,$posid3,$x1,$y1,$x2,$y2,$x3,$y3);
        self::ajaxResponse( );
    }
	
	public function ArrangeTiles()
    {
        self::setAjaxMode();     
        $tileid = self::getArg( "tileid", AT_alphanum, true );
        $wagonid = self::getArg( "wagonid", AT_alphanum, true );
        $posid = self::getArg( "posid", AT_alphanum, true );
        $x = self::getArg( "x", AT_alphanum, true );
        $y = self::getArg( "y", AT_alphanum, true );
        $pid = self::getArg( "pid", AT_alphanum, true );

        $result = $this->game->gArrangeTiles($tileid,$wagonid,$posid,$x,$y,$pid);
        self::ajaxResponse( );
    }
	
	public function MoveTile()
    {
        self::setAjaxMode();     
        $tileid = self::getArg( "tileid", AT_alphanum, true );
        $pid = self::getArg( "pid", AT_alphanum, true );
        $x0 = self::getArg( "x0", AT_alphanum, true );
        $y0 = self::getArg( "y0", AT_alphanum, true );
        $x1 = self::getArg( "x1", AT_alphanum, true );
        $y1 = self::getArg( "y1", AT_alphanum, true );

        $result = $this->game->gMoveTile($tileid,$pid,$x0,$y0,$x1,$y1);
        self::ajaxResponse( );
    }
	
	public function BuyTile()
    {
        self::setAjaxMode();     
        $tileid = self::getArg( "tileid", AT_alphanum, true );
        $pid = self::getArg( "pid", AT_alphanum, true );
        $x0 = self::getArg( "x0", AT_alphanum, true );
        $y0 = self::getArg( "y0", AT_alphanum, true );
        $x1 = self::getArg( "x1", AT_alphanum, true );
        $y1 = self::getArg( "y1", AT_alphanum, true );

        $result = $this->game->gBuyTile($tileid,$pid,$x0,$y0,$x1,$y1);
        self::ajaxResponse( );
    }
	
	public function ConfirmArrangement()
    {
        self::setAjaxMode();     
        $result = $this->game->gConfirmArrangement();
        self::ajaxResponse( );
    }
	
	
	public function GoBack()
    {
        self::setAjaxMode();     
        $x = self::getArg( "x", AT_alphanum, true );
        $result = $this->game->gGoBack($x);
        self::ajaxResponse( );
    }
	
	public function Reset()
    {
        self::setAjaxMode();     
        $result = $this->game->gReset();
        self::ajaxResponse( );
    }
	
	public function BuyEnclosure()
    {
        self::setAjaxMode();     
        $result = $this->game->gBuyEnclosure();
        self::ajaxResponse( );
    }
	
	public function Move()
    {
        self::setAjaxMode();     
        $result = $this->game->gMove();
        self::ajaxResponse( );
    }
	public function Swap()
    {
        self::setAjaxMode();     
        $result = $this->game->gSwap();
        self::ajaxResponse( );
    }
	public function Buy()
    {
        self::setAjaxMode();     
        $result = $this->game->gBuy();
        self::ajaxResponse( );
    }
	public function Discard()
    {
        self::setAjaxMode();     
        $result = $this->game->gDiscard();
        self::ajaxResponse( );
    }
	
	public function Back()
    {
        self::setAjaxMode();     
        $result = $this->game->gBack();
        self::ajaxResponse( );
    }
	
	
	

  }
  

