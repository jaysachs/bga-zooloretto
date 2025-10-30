/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Zooloretto implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * zooloretto.js
 *
 * Zooloretto user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

	var jstpl_player_number='<div class="plnomoney"><div class="player_number" id="player_number_${player_number}">${player_number_text}</div><div id="money_${player_number}" class="plnomoney2"></div></div>';
	var jstpl_wagon3='<div class="wagon wagon${wagon_size}" id="wagon_${id}"><div class="cellwagon3" id="wagon_${id}_1" style="left:5%; top:14%"></div><div class="cellwagon3" id="wagon_${id}_2" style="left:36%; top:14%"></div><div class="cellwagon3" id="wagon_${id}_3" style="left:67%; top:14%"></div></div>';
	var jstpl_wagon2='<div class="wagon wagon${wagon_size}" id="wagon_${id}"><div class="cellwagon2" id="wagon_${id}_1" style="left:5%; top:14%"></div><div class="cellwagon2" id="wagon_${id}_2" style="left:54%; top:14%"></div></div>';
	var jstpl_wagon1='<div class="wagon wagon${wagon_size}" id="wagon_${id}"><div class="cellwagon1" id="wagon_${id}_1" style="left:5%; top:14%"></div></div>';
	var jstpl_money='<div class="money money${player_no}" id="money_instance_${player_no}_${id}"></div>';
	var jstpl_tile='<div class="tile tile${val}" id="tile_${player_no}_${id}_${val}_${x}_${y}"></div>';
	var jstpl_disk='<div class="disk" id="disk"></div>';
	var jstpl_back='<div class="back" id="backtile_${id}"></div>';
	var jstpl_back2='<div class="back" id="backtile2_${id}"></div>';
	var jstpl_lastround='<div class="head_info" id="zooloretto_last_round_1" style="height: auto; overflow: hidden"><div class="head_infomsg_close" id="zooloretto_last_round_2"><i class="fa fa-close" aria-hidden="true"></i></div><div class="head_infomsg_item">${message}</div></div>';
	var jstpl_tilesleft='<div class="tilesleft" id="tilesleft">${val}</div>';

define([
    "dojo","dojo/_base/declare",
    "dojo/fx",
    "dojo/_base/fx",
    "dojo/dom-style",
    "dojo/NodeList-traverse",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare, fx, baseFx, domStyle) {
    return declare("bgagame.zooloretto", ebg.core.gamegui, {
        constructor: function(){
            console.log('zooloretto constructor');

            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;
            this.TotalPlayers=0;
            this.PlayerNo=0;
            this.Wagons = null;
            this.Zoo = null;
            this.UnblockedZoo = null;
            this.StateNameValue = "";
            this.Money = 0;
            this.UZ = 0;
        },
        /*
            setup:

            This method must set up the game user interface according to current game situation specified
            in parameters.

            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)

            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
         AddPlayerNumber: function( playerid, player_number )
        {
            var player_board_div = $('player_board_'+playerid);
            if (player_number==1)
            {
                dojo.place( this.format_block( 'jstpl_player_number', {
                    player_number: player_number,
                    player_number_text: _('1st'),
                } ) , player_board_div );
            }
            else if (player_number==2)
            {
                dojo.place( this.format_block( 'jstpl_player_number', {
                    player_number: player_number,
                    player_number_text: _('2nd'),
                } ) , player_board_div );
            }
            else if (player_number==3)
            {
                dojo.place( this.format_block( 'jstpl_player_number', {
                    player_number: player_number,
                    player_number_text: _('3rd'),
                } ) , player_board_div );
            }
            else
            {
                dojo.place( this.format_block( 'jstpl_player_number', {
                    player_number: player_number,
                    player_number_text: player_number + _('th'),
                } ) , player_board_div );
            }
        },

        slideToObjectPos: function (t, i, n, o, a, s) {

            slideTo = function(/*Object*/ args){
                    var node = args.node = dojo.byId(args.node),
                        top = null, left = null;

                    var init = (function(n){
                        return function(){
                            var cs = dojo.getComputedStyle(n);
                            var pos = cs.position;
                            top = (pos == 'absolute' ? n.offsetTop : parseInt(cs.top) || 0);
                            left = (pos == 'absolute' ? n.offsetLeft : parseInt(cs.left) || 0);
                            if(pos != 'absolute' && pos != 'relative'){
                                var ret = geom.position(n, true);
                                top = ret.y;
                                left = ret.x;
                                n.style.position="absolute";
                                n.style.top=top+args.unit;
                                n.style.left=left+args.unit;
                            }
                        };
                    })(node);
                    init();

                    var anim = dojo.animateProperty(dojo.mixin({
                        properties: {
                            top: args.top || 0,
                            left: args.left || 0
                        }
                    }, args));



                    dojo.require("dojo.aspect");
                    dojo.aspect.after(anim, "beforeBegin", init, true);
                    dojo.aspect.after(anim, "onEnd", function()
                        {
                                document.getElementById(args.node.id).style.left = (dojo.style(args.node,'left') / (dojo.style(args.node.parentNode,'width') + dojo.style(args.node.parentNode,'padding-left') + dojo.style(args.node.parentNode,'padding-right')) * 100) + "%";
                                document.getElementById(args.node.id).style.top = (dojo.style(args.node,'top') / (dojo.style(args.node.parentNode,'height') + dojo.style(args.node.parentNode,'padding-top') + dojo.style(args.node.parentNode,'padding-bottom')) * 100)+ "%";
                        });
                    return anim; // dojo/_base/fx.Animation
                };
            if ('string' == typeof t) var r = $(t);
             else r = t;
            var l = this.disable3dIfNeeded(),
            d = dojo.position(i),
            c = dojo.position(t);
            void 0 === a && (a = 500);
            void 0 === s && (s = 0);
            if (this.instantaneousMode) {
              s = Math.min(1, s);
              a = Math.min(1, a)
            }
            var h = dojo.style(t, 'left'),
            u = dojo.style(t, 'top'),
            p = {
              x: d.x - c.x + toint(n),
              y: d.y - c.y + toint(o)
            },
            m = this.getAbsRotationAngle(r.parentNode),
            g = this.vector_rotate(p, m);
            h += g.x;
            u += g.y;
            this.enable3dIfNeeded(l);
            var f = slideTo({
              node: t,
              top: u,
              left: h,
              delay: s,
              duration: a,
              easing: dojo.fx.easing.cubicInOut,
              unit: 'px'
            });

            null !== l && (f = this.transformSlideAnimTo3d(f, r, a, s, g.x, g.y));
            return f
          },

        slideToObjectPos2: function (t, i, n, o, a, s) {

            slideTo = function(/*Object*/ args){
                    var node = args.node = dojo.byId(args.node),
                        top = null, left = null;

                    var init = (function(n){
                        return function(){
                            var cs = dojo.getComputedStyle(n);
                            var pos = cs.position;
                            top = (pos == 'absolute' ? n.offsetTop : parseInt(cs.top) || 0);
                            left = (pos == 'absolute' ? n.offsetLeft : parseInt(cs.left) || 0);
                            if(pos != 'absolute' && pos != 'relative'){
                                var ret = geom.position(n, true);
                                top = ret.y;
                                left = ret.x;
                                n.style.position="absolute";
                                n.style.top=top+args.unit;
                                n.style.left=left+args.unit;
                            }
                        };
                    })(node);
                    init();

                    var anim = dojo.animateProperty(dojo.mixin({
                        properties: {
                            top: args.top || 0,
                            left: args.left || 0
                        }
                    }, args));



                    dojo.require("dojo.aspect");
                    dojo.aspect.after(anim, "beforeBegin", init, true);
                    dojo.aspect.after(anim, "onEnd", function()
                        {
                                document.getElementById(args.node.id).style.left = "";
                                document.getElementById(args.node.id).style.top = "";
                        });
                    return anim; // dojo/_base/fx.Animation
                };
            if ('string' == typeof t) var r = $(t);
             else r = t;
            var l = this.disable3dIfNeeded(),
            d = dojo.position(i),
            c = dojo.position(t);
            void 0 === a && (a = 500);
            void 0 === s && (s = 0);
            if (this.instantaneousMode) {
              s = Math.min(1, s);
              a = Math.min(1, a)
            }
            var h = dojo.style(t, 'left'),
            u = dojo.style(t, 'top'),
            p = {
              x: d.x - c.x + toint(n),
              y: d.y - c.y + toint(o)
            },
            m = this.getAbsRotationAngle(r.parentNode),
            g = this.vector_rotate(p, m);
            h += g.x;
            u += g.y;
            this.enable3dIfNeeded(l);
            var f = slideTo({
              node: t,
              top: u,
              left: h,
              delay: s,
              duration: a,
              easing: dojo.fx.easing.cubicInOut,
              unit: 'px'
            });

            null !== l && (f = this.transformSlideAnimTo3d(f, r, a, s, g.x, g.y));
            return f
          },

        slideToObjectPos3: function (t, i, n, o, a, s) {

            slideTo = function(/*Object*/ args){
                    var node = args.node = dojo.byId(args.node),
                        top = null, left = null;

                    var init = (function(n){
                        return function(){
                            var cs = dojo.getComputedStyle(n);
                            var pos = cs.position;
                            top = (pos == 'absolute' ? n.offsetTop : parseInt(cs.top) || 0);
                            left = (pos == 'absolute' ? n.offsetLeft : parseInt(cs.left) || 0);
                            if(pos != 'absolute' && pos != 'relative'){
                                var ret = geom.position(n, true);
                                top = ret.y;
                                left = ret.x;
                                n.style.position="absolute";
                                n.style.top=top+args.unit;
                                n.style.left=left+args.unit;
                            }
                        };
                    })(node);
                    init();

                    var anim = dojo.animateProperty(dojo.mixin({
                        properties: {
                            top: args.top || 0,
                            left: args.left || 0
                        }
                    }, args));



                    return anim; // dojo/_base/fx.Animation
                };
            if ('string' == typeof t) var r = $(t);
             else r = t;
            var l = this.disable3dIfNeeded(),
            d = dojo.position(i),
            c = dojo.position(t);
            void 0 === a && (a = 500);
            void 0 === s && (s = 0);
            if (this.instantaneousMode) {
              s = Math.min(1, s);
              a = Math.min(1, a)
            }
            var h = dojo.style(t, 'left'),
            u = dojo.style(t, 'top'),
            p = {
              x: d.x - c.x + toint(n),
              y: d.y - c.y + toint(o)
            },
            m = this.getAbsRotationAngle(r.parentNode),
            g = this.vector_rotate(p, m);
            h += g.x;
            u += g.y;
            this.enable3dIfNeeded(l);
            var f = slideTo({
              node: t,
              top: u,
              left: h,
              delay: s,
              duration: a,
              easing: dojo.fx.easing.cubicInOut,
              unit: 'px'
            });

            null !== l && (f = this.transformSlideAnimTo3d(f, r, a, s, g.x, g.y));
            return f
          },

        setLoader(value, max)
        {
          this.inherited(arguments);
          if (!this.isLoadingComplete && value >= 100) {
            this.isLoadingComplete = true;
//            this.onLoadingComplete();
          }
        },

        onLoadingComplete()
        {
            console.log("Loading completed..."+this.player_id );

            var found = false;
            for( var player_id in this.gamedatas.players )
            {
                var player = this.gamedatas.players[player_id];
                if (player.no==this.PlayerNo)
                {
                    found = true;
                    if (gameui.players_metadata!=null &&
                        gameui.players_metadata[player_id]!=null && (
                        gameui.players_metadata[player_id].country_infos.code == "IT" ||
                        gameui.players_metadata[player_id].country_infos.code == "EN" ||
                        gameui.players_metadata[player_id].country_infos.code == "DE" ||
                        gameui.players_metadata[player_id].country_infos.code == "FR"))
                        {
                            dojo.addClass('playeraid','playeraid' + gameui.players_metadata[player_id].country_infos.code);
                        }
                    else
                    {
                        dojo.addClass('playeraid','playeraidEN');
                    }
                }
                else
                {
                    dojo.addClass('board_' + player.no,'zoom');
                }
            }
            if (!found)
            {
                dojo.addClass('playeraid','playeraidEN');
            }
            // var count=0;
            // for( var i in this.gamedatas.money )
            // {
                // var mon = this.gamedatas.money[i];
                // for (let i = 1; i <= mon.money; i++)
                // {
                    // count = count + 1;
                    // this.addMoneyPlayer(count, mon.player_no);
                // }
            // }
            // for( var i in this.gamedatas.drawntiles )
            // {
                // var drawntile = this.gamedatas.drawntiles[i];
                // this.addTile( 'tiles',0, drawntile.id, drawntile.val, 0, 0 );
            // }

            // for( var i in this.gamedatas.animalsthinking )
            // {
                // var animalthinking = this.gamedatas.animalsthinking[i];
                // this.addTile( 'cell_'+animalthinking.player_no+'_'+animalthinking.x+'_'+animalthinking.y,animalthinking.player_no, animalthinking.id, animalthinking.val, animalthinking.x, animalthinking.y );
                // dojo.addClass('tile_'+animalthinking.player_no+'_'+animalthinking.id+'_'+animalthinking.val+'_'+animalthinking.x+'_'+animalthinking.y,'boardsize');
                // dojo.addClass('tile_'+animalthinking.player_no+'_'+animalthinking.id+'_'+animalthinking.val+'_'+animalthinking.x+'_'+animalthinking.y,'thinking');
                // dojo.query( '#tile_'+animalthinking.player_no+'_'+animalthinking.id+'_'+animalthinking.val+'_'+animalthinking.x+'_'+animalthinking.y).connect( 'onclick', this, 'onClickTile' );

                // this.Zoo[animalthinking.player_no-1][animalthinking.x-1][animalthinking.y-1]=animalthinking.val+'_'+animalthinking.id+'_TH';
            // }

            // for( var i in this.gamedatas.animalsthinkingwagon )
            // {
                // var animalthinking = this.gamedatas.animalsthinkingwagon[i];
                // dojo.addClass('tile_0_'+animalthinking.id+'_'+animalthinking.val+'_'+animalthinking.x+'_'+animalthinking.y,'thinking');
            // }

            // for( var i in this.gamedatas.animalsplayed )
            // {
                // var animalplayed = this.gamedatas.animalsplayed[i];
                // this.addTile( 'cell_'+animalplayed.player_no+'_'+animalplayed.x+'_'+animalplayed.y,animalplayed.player_no, animalplayed.id, animalplayed.val, animalplayed.x, animalplayed.y );
                // dojo.addClass('tile_'+animalplayed.player_no+'_'+animalplayed.id+'_'+animalplayed.val+'_'+animalplayed.x+'_'+animalplayed.y,'boardsize');
                // dojo.query( '#tile_'+animalplayed.player_no+'_'+animalplayed.id+'_'+animalplayed.val+'_'+animalplayed.x+'_'+animalplayed.y).connect( 'onclick', this, 'onClickTile' );

                // this.Zoo[animalplayed.player_no-1][animalplayed.x-1][animalplayed.y-1]=animalplayed.val+'_'+animalplayed.id+'_PL';
            // }

            // for( var i in this.gamedatas.animalsstall )
            // {
                // var animalstall = this.gamedatas.animalsstall[i];
                // this.addTile( 'stall_' + animalstall.player_no,animalstall.player_no, animalstall.id, animalstall.val, animalstall.x, animalstall.y );
                // dojo.addClass('tile_'+animalstall.player_no+'_'+animalstall.id+'_'+animalstall.val+'_'+animalstall.x+'_'+animalstall.y,'stallsize');
                // dojo.query( '#tile_'+animalstall.player_no+'_'+animalstall.id+'_'+animalstall.val+'_'+animalstall.x+'_'+animalstall.y).connect( 'onclick', this, 'onClickTile' );
            // }

            // if (this.StateNameValue=="PlayerTurn" && this.isCurrentPlayerActive())
            // {
                // if (this.Money>=2)
                // {
                    // if (this.countTotalOtherBarn(this.PlayerNo)>0)
                    // {
                        // dojo.removeClass("buy","buttoninvisible");
                        // dojo.addClass("buy","buttonvisible");
                    // }
                    // if (this.countTotalOwnBarn(this.PlayerNo)>0)
                    // {
                        // dojo.removeClass("discard","buttoninvisible");
                        // dojo.addClass("discard","buttonvisible");
                    // }
                // }
                // if (this.Money>=1)
                // {
                    // if (this.countTotalTilesEnclosuresStall(this.PlayerNo)>0)
                    // {
                        // dojo.removeClass("move","buttoninvisible");
                        // dojo.addClass("move","buttonvisible");
                    // }
                    // if (this.countTotalZones(this.PlayerNo)>=2)
                    // {
                        // dojo.removeClass("swap","buttoninvisible");
                        // dojo.addClass("swap","buttonvisible");
                    // }
                // }
            // }
        },

        countWagonsSitFree: function()
        {
            var count = 0;
            for (let i=0; i<this.Wagons.length; i++)
            {
                for (let j=0; j<3; j++)
                {
                    if (this.Wagons[i][j]=="")
                    {
                        count = count + 1;
                    }
                }
            }
            return count;
        },

        countWagonsSitOccupied: function(wagonid)
        {
            var count = 0;
            for (let j=0; j<3; j++)
            {
                if (this.Wagons[wagonid-1][j]!="")
                {
                    count = count + 1;
                }
            }
            return count;
        },

        countWagonsSitOccupied: function(wagonid)
        {
            var count = 0;
            for (let j=0; j<3; j++)
            {
                if (this.Wagons[wagonid-1][j]!="" && this.Wagons[wagonid-1][j]!="X")
                {
                    count = count + 1;
                }
            }
            return count;
        },

        addMoneyPlayer: function( id, player_no )
        {
            var count = parseInt(id);

            while (document.getElementById("money_instance_"+player_no+"_"+count)!=null)
            {
                count = count + 1;
            }

            dojo.place( this.format_block( 'jstpl_money', {
                id: count,
                player_no: player_no
            } ) , 'money_' + player_no);
        },


        countTotalTilesEnclosuresStall: function (playerno)
        {
            var count = 0;
            for (let i=0; i<this.Zoo[playerno-1].length; i++)
            {
                for (let j=0; j<this.Zoo[playerno-1][i].length; j++)
                {
                    if (this.Zoo[playerno-1][i][j]!="" && this.Zoo[playerno-1][i][j]!="X")
                    {
                        count = count + 1;
                    }
                }
            }
            return count;
        },

        countTotalTilesEnclosures: function (playerno)
        {
            var count = 0;
            for (let i=0; i<this.Zoo[playerno-1].length; i++)
            {
                for (let j=0; j<this.Zoo[playerno-1][i].length; j++)
                {
                    if (this.Zoo[playerno-1][i][j]!="" && this.Zoo[playerno-1][i][j]!="X" && this.Zoo[playerno-1][i][j].search("Stall")<0)
                    {
                        count = count + 1;
                    }
                }
            }
            return count;
        },

        countTotalTilesStall: function (playerno)
        {
            var count = 0;
            for (let i=0; i<this.Zoo[playerno-1].length; i++)
            {
                for (let j=0; j<this.Zoo[playerno-1][i].length; j++)
                {
                    if (this.Zoo[playerno-1][i][j]!="" && this.Zoo[playerno-1][i][j]!="X" && this.Zoo[playerno-1][i][j].search("Stall")>=0)
                    {
                        count = count + 1;
                    }
                }
            }
            return count;
        },


        countTotalZones: function (playerno)
        {
            var count = 0;
            for (let i=0; i<this.Zoo[playerno-1].length; i++)
            {
                var found = false;
                for (let j=0; j<this.Zoo[playerno-1][i].length; j++)
                {
                    if (this.Zoo[playerno-1][i][j]!="" && this.Zoo[playerno-1][i][j]!="X" && this.Zoo[playerno-1][i][j].search("Stall")<0)
                    {
                        found = true;
                    }
                }
                if (found)
                {
                    count = count + 1;
                }
            }
            if (document.getElementById("stall_"+playerno).children.length>0)
            {
                count = count + 1;
            }
            return count;
        },


        countTotalOwnBarn: function (playerno)
        {
            return document.getElementById("stall_"+playerno).children.length;
        },

        countTotalOtherBarn: function (playerno)
        {
            var count = 0;
            for (let i=1;i<=this.TotalPlayers; i++)
            {
                if (i!=playerno)
                {
                    count = count + document.getElementById("stall_"+i).children.length;
                }
            }
            return count;
        },


        addWagon: function( id, size )
        {
            if (size==3)
            {
                dojo.place( this.format_block( 'jstpl_wagon3', {
                    wagon_size: size,
                    id: id
                } ) , 'wagons' );
            }
            else if (size==2)
            {
                dojo.place( this.format_block( 'jstpl_wagon2', {
                    wagon_size: size,
                    id: id
                } ) , 'wagons' );
            }
            else if (size==1)
            {
                dojo.place( this.format_block( 'jstpl_wagon1', {
                    wagon_size: size,
                    id: id
                } ) , 'wagons' );
            }
        },

        addTile: function( cont, player_no, id, val, x, y )
        {
            dojo.place( this.format_block( 'jstpl_tile', {
                player_no: player_no,
                id: id,
                x: x,
                y: y,
                val: val
            } ) ,  cont);
        },

        addDisk: function( id )
        {
            dojo.place( this.format_block( 'jstpl_disk', {
            } ) ,  'tilesdeck');
            var w = (document.getElementById('tilesdeck').getBoundingClientRect().width);
            this.slideToObjectPos3( 'disk', 'tilesdeck', id * 5* w/100, 55* w/100 + id * 5* w/100, 0).play();
        },

        addLastRound: function( message )
        {
            if (document.getElementById("zooloretto_last_round_1")==null)
            {
                dojo.place( this.format_block( 'jstpl_lastround', {
                    message: message,
                } ) ,  'head_infomsg');
                dojo.query( '#zooloretto_last_round_2' ).connect( 'onclick', this, 'onCloseLastRound' );
            }
            else
            {
                document.getElementById("zooloretto_last_round_1").style.visibility = '';
            }
        },

        onCloseLastRound: function( id )
        {
            //document.getElementById("zooloretto_last_round_1").style.visibility = 'hidden';
            dojo.destroy('zooloretto_last_round_1');
        },

        addBack: function( id )
        {
            dojo.place( this.format_block( 'jstpl_back', {
                id: id,
            } ) ,  'tilesdeck');

            var w = (document.getElementById('tilesdeck').getBoundingClientRect().width);
            this.slideToObjectPos3( 'backtile_' + id, 'tilesdeck', id * 5 * w/100, id * 5 * w/100, 0).play();
        },

        addTilesLeft: function( id , val)
        {
            dojo.place( this.format_block( 'jstpl_tilesleft', {
                val: val,
            } ) ,  'tilesdeck');

            var w = (document.getElementById('tilesdeck').getBoundingClientRect().width);
            this.slideToObjectPos3( 'tilesleft', 'tilesdeck', id * 5 * w/100, id * 5 * w/100, 0).play();
        },

        addBack2: function( id )
        {
            dojo.place( this.format_block( 'jstpl_back2', {
                id: id,
            } ) ,  'tilesdeck');
            var w = (document.getElementById('tilesdeck').getBoundingClientRect().width);
            this.slideToObjectPos3( 'backtile2_' + id, 'tilesdeck', id * 5 * w/100, 55 * w/100 + id * 5 * w/100, 0).play();
        },


        cellHtml: function(pno, x, y, left, top) {
            return `<div id="cell_${pno}_${x}_${y}" class="cell" style="left: ${left}%; top: ${top}%;"></div>
`;
        },

        playerHtml: function(pno) {
            var html = '';
            let player_count = Object.keys(this.gamedatas.players).length;
            var ratio = 1.0;
            var delta = 0.0;
            if (player_count == 2) {
		ratio = 0.82020423;
		delta = 17.979577;
            }
            var x = 1;
            var y = 1;

            html += this.cellHtml(pno, x, y++, delta + ratio*41.7, 10.3);
            html += this.cellHtml(pno, x, y++, delta + ratio*28.5, 23.5);
            html += this.cellHtml(pno, x, y++, delta + ratio*41.7, 21.2);
            html += this.cellHtml(pno, x, y++, delta + ratio*26, 34);
            html += this.cellHtml(pno, x, y++, delta + ratio*39, 32);

            x++;
            y = 1;
            html += this.cellHtml(pno, x, y++, delta + ratio*64.2, 7.3);
            html += this.cellHtml(pno, x, y++, delta + ratio*66.5, 18);
            html += this.cellHtml(pno, x, y++, delta + ratio*62, 28.5);
            html += this.cellHtml(pno, x, y++, delta + ratio*73, 34.5);

            x++;
            y = 1;
            html += this.cellHtml(pno, x, y++, delta + ratio*66, 51.6);
            html += this.cellHtml(pno, x, y++, delta + ratio*81.3, 54.8);
            html += this.cellHtml(pno, x, y++, delta + ratio*68.8, 62);
            html += this.cellHtml(pno, x, y++, delta + ratio*79.4, 65.6);
            html += this.cellHtml(pno, x, y++, delta + ratio*66, 72.6);
            html += this.cellHtml(pno, x, y++, delta + ratio*67.2, 83.6);

            x++;
            y = 1;
            html += this.cellHtml(pno, x, y++, delta + ratio*7.3, 23.5);
            html += this.cellHtml(pno, x, y++, delta + ratio*9, 34.2);
            html += this.cellHtml(pno, x, y++, delta + ratio*7.3, 44.5);
            html += this.cellHtml(pno, x, y++, delta + ratio*9, 55.5);
            html += this.cellHtml(pno, x, y++, delta + ratio*7.3, 66.2);

            if (player_count == 2) {
                x++;
                y = 1;
                html += this.cellHtml(pno, x, y++, 0 + ratio*7.3, 23.5);
                html += this.cellHtml(pno, x, y++, 0 + ratio*9, 34.2);
                html += this.cellHtml(pno, x, y++, 0 + ratio*7.3, 44.5);
                html += this.cellHtml(pno, x, y++, 0 + ratio*9, 55.5);
                html += this.cellHtml(pno, x, y++, 0 + ratio*7.3, 66.2);

                x++;
                y = 1;
                html += this.cellHtml(pno, x, y++, delta + ratio*24.5, 7.5);
                html += this.cellHtml(pno, x, y++, delta + ratio*83.6, 7.5);
                html += this.cellHtml(pno, x, y++, delta + ratio*83.6, 18);
                html += this.cellHtml(pno, x, y++, delta + ratio*83.6, 83.5);
                html += this.cellHtml(pno, x, y++, delta + ratio*7.3, 83.5);
                html += this.cellHtml(pno, x, y++, 0 + ratio*7.3, 83.5);
            } else {
                x++;
                y = 1;
                html += this.cellHtml(pno, x, y++, delta + ratio*24.5, 7.5);
                html += this.cellHtml(pno, x, y++, delta + ratio*83.6, 7.5);
                html += this.cellHtml(pno, x, y++, delta + ratio*83.6, 18);
                html += this.cellHtml(pno, x, y++, delta + ratio*83.6, 83.5);
                html += this.cellHtml(pno, x, y++, delta + ratio*7.3, 83.5);
            }
            return html;
        },

        baseHtml: function() {
            let player_count = this.gamedatas.players.length;
            let currentPno = this.gamedatas.current_player_no;
            var othersHtml = '';
            var j = 0;
            var ratio = 1.0;
            var delta = 0.0;
            if (player_count == 2) {
		ratio = 0.82020423;
		delta = 17.979577;
            }
            for (let i in this.gamedatas.players) {
                let x = this.gamedatas.players[i].no;
                if (x != currentPno) {
                    j++;
                    let left = 0;
                    let top = 13 + j * 35;
                    let a = delta + ratio*20;
                    let b = 82;
                    othersHtml += `
                <div id="playercards_${x}" class="playercards whiteblock" style="left: ${left}%; top: ${top}%;">
       <div id ="playername_${x}" class="playernameclass"></div>
          <div id="board_${x}" class="board">
` + this.playerHtml(x) + `
          <div id="stall_${x}" class="stall" style="left: ${a}%; top: ${b}%;"></div>
          </div>
        </div>`;
                }
            }
            return `<div class="container1" id = "container1">
  <div class="container2" id = "container2">
    <div class="wagons" id = "wagons"></div>
    <div class="tiles" id = "tiles"></div>
    <div class="tilesdeck" id = "tilesdeck"></div>
  </div>

  <div class="container3" id = "container3">
    <div id="board" class="board">
` + this.playerHtml(currentPno) + `
      <div id="stall" class="stall" style="left: 20%; top: 82%;"></div>
    </div>

    <div id="leftpanel" class="leftpanel">
      <div id="playercards" class="playercards">
` + othersHtml + `
      </div>
    </div>
  </div>

  <div class="playeraid" id = "playeraid"></div>
</div>
`;
        },

        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );

            // this.gamedatas = gamedatas;
            this.getGameAreaElement().insertAdjacentHTML('beforeend',
                                                         this.baseHtml());
            this.TotalPlayers = 0;
            // Setting up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                // TODO: Setting up players boards if needed
                this.AddPlayerNumber(player.id, player.no);
                var myColor = dojo.colorFromHex("#" + player.color);
                dojo.style("player_number_" + player.no,"color", myColor);
                if (player.no!=gamedatas.current_player_no)
                {
                    document.getElementById("playername_" + player.no).innerHTML = "<p>" + player.name + "</p>";
                    if (player.skipped=="Y")
                    {
                        document.getElementById("playername_" + player.no).innerHTML = document.getElementById("playername_" + player.no).innerHTML.replace('</p>', _(" - Took the wagon") + '</p>');
                    }
                    dojo.style("playername_" + player.no,"color", myColor);
                    dojo.style("playername_" + player.no,"font-size", "1.3vw");
                }
                this.TotalPlayers = this.TotalPlayers + 1;
            }
            this.UnblockedZoo = new Array(this.TotalPlayers);
            for( var i in this.gamedatas.money )
            {
                var mon = this.gamedatas.money[i];
                this.UnblockedZoo[mon.player_no-1]= mon.unblockedzoo;
            }

            if (this.TotalPlayers==2)
            {
                this.Wagons = Array.from(Array(3), () => new Array(3));
                this.Zoo = Array.from(Array(2), () => Array.from(Array(6), () => new Array(6)));
                for (let i=0; i<2; i++)
                {
                    for (let j=0; j<6; j++)
                    {
                        for (let k=0; k<6; k++)
                        {
                            if (j==0 && k==5) this.Zoo[i][j][k] = "X";
                            if (j==1 && k>=4) this.Zoo[i][j][k] = "X";

                            if (this.UnblockedZoo[i]==1 || this.UnblockedZoo[i]==2)
                            {
                                if (j==3 && k==5) this.Zoo[i][j][k] = "X";
                            }
                            else if (this.UnblockedZoo[i]==0)
                            {
                                if (j==3) this.Zoo[i][j][k] = "X";
                            }

                            if (this.UnblockedZoo[i]==2)
                            {
                                if (j==4 && k==5) this.Zoo[i][j][k] = "X";
                            }
                            else
                            {
                                if (j==4) this.Zoo[i][j][k] = "X";
                            }

                            if (this.UnblockedZoo[i]==0)
                            {
                                if (j==5 && k>=4) this.Zoo[i][j][k] = "X";
                            }
                            else if (this.UnblockedZoo[i]==1)
                            {
                                if (j==5 && k==5) this.Zoo[i][j][k] = "X";
                            }
                        }
                    }
                }
            }
            else
            {
                this.Wagons = Array.from(Array(this.TotalPlayers), () => new Array(3));
                this.Zoo = Array.from(Array(this.TotalPlayers), () => Array.from(Array(5), () => new Array(6)));
                for (let i=0; i<this.TotalPlayers; i++)
                {
                    for (let j=0; j<5; j++)
                    {
                        for (let k=0; k<6; k++)
                        {
                            if (j==0 && k==5) this.Zoo[i][j][k] = "X";
                            if (j==1 && k>=4) this.Zoo[i][j][k] = "X";

                            if (this.UnblockedZoo[i]==1)
                            {
                                if (j==3 && k==5) this.Zoo[i][j][k] = "X";
                            }
                            else if (this.UnblockedZoo[i]==0)
                            {
                                if (j==3) this.Zoo[i][j][k] = "X";
                            }
                            if (this.UnblockedZoo[i]==0)
                            {
                                if (j==4 && k>=4) this.Zoo[i][j][k] = "X";
                            }
                            else if (this.UnblockedZoo[i]==1)
                            {
                                if (j==4 && k==5) this.Zoo[i][j][k] = "X";
                            }
                        }
                    }
                }
            }
            for (let i=0; i<this.Zoo.length; i++)
            {
                for (let j=0; j<this.Zoo[i].length; j++)
                {
                    for (let k=0; k<this.Zoo[i][j].length; k++)
                    {
                        if (this.Zoo[i][j][k]==null) this.Zoo[i][j][k] = '';
                    }
                }
            }
            this.PlayerNo = gamedatas.current_player_no;
            document.getElementById("board").id = "board_" + this.PlayerNo;
            document.getElementById("stall").id = "stall_" + this.PlayerNo;

            for( var i in gamedatas.wagons )
            {
                var wagon = gamedatas.wagons[i];
                this.Wagons[wagon.id-1][0] = wagon.val1 == null ? '' : wagon.val1;
                if (wagon.size=="1")
                {
                    this.Wagons[wagon.id-1][1] = "X";
                }
                else
                {
                    this.Wagons[wagon.id-1][1] = wagon.val2 == null ? '' : wagon.val2;
                }
                if (wagon.size=="3")
                {
                    this.Wagons[wagon.id-1][2] = wagon.val3 == null ? '' : wagon.val3;
                }
                else
                {
                    this.Wagons[wagon.id-1][2] = "X";
                }
            }

            for( var i in this.gamedatas.wagons )
            {
                var wagon = this.gamedatas.wagons[i];
                this.addWagon( wagon.id, wagon.size);
            }

            for( var i in this.gamedatas.wagonstiles1 )
            {
                var wagon = this.gamedatas.wagonstiles1[i];
                if (this.Wagons[wagon.id-1][0]!="" && this.Wagons[wagon.id-1][0]!="X")
                {
                    this.addTile( 'wagon_' + (wagon.id) + '_1', 0, wagon.idd, wagon.val, wagon.id, 1 );
                    dojo.addClass('tile_0_' + (wagon.idd) + '_' + (wagon.val) + '_' + wagon.id + '_1','wagonsize');
                }
            }
            for( var i in this.gamedatas.wagonstiles2 )
            {
                var wagon = this.gamedatas.wagonstiles2[i];
                if (this.Wagons[wagon.id-1][1]!="" && this.Wagons[wagon.id-1][1]!="X")
                {
                    this.addTile( 'wagon_' + (wagon.id) + '_2', 0, wagon.idd, wagon.val, wagon.id, 2 );
                    dojo.addClass('tile_0_' + (wagon.idd) + '_' + (wagon.val) + '_' + wagon.id + '_2','wagonsize');
                }
            }
            for( var i in this.gamedatas.wagonstiles3 )
            {
                var wagon = this.gamedatas.wagonstiles3[i];
                if (this.Wagons[wagon.id-1][2]!="" && this.Wagons[wagon.id-1][2]!="X")
                {
                    this.addTile( 'wagon_' + (wagon.id) + '_3', 0, wagon.idd, wagon.val, wagon.id, 3 );
                    dojo.addClass('tile_0_' + (wagon.idd) + '_' + (wagon.val) + '_' + wagon.id + '_3','wagonsize');
                }
            }
            for( var i in this.gamedatas.wagonstaken )
            {
                var wagontaken = this.gamedatas.wagonstaken[i];
                dojo.addClass('wagon_' + wagontaken.id, 'highlighted');
            }

            for( var i in this.gamedatas.unblockedzoo )
            {
                var uz = this.gamedatas.unblockedzoo[i];

                if (this.TotalPlayers==2)
                {
                    dojo.removeClass('board_' + uz.player_no, 'board');
                    dojo.addClass('board_' + uz.player_no, 'board2' + uz.unblockedzoo);
                }
                else
                {
                    dojo.removeClass('board_' + uz.player_no, 'board');
                    dojo.addClass('board_' + uz.player_no, 'board' + uz.unblockedzoo);
                }
            }

            if (this.TotalPlayers==2)
            {
                dojo.removeClass('stall_1','stall');
                dojo.removeClass('stall_2','stall');
                dojo.addClass('stall_1','stall2');
                dojo.addClass('stall_2','stall2');
                document.getElementById("stall_" + this.PlayerNo).style.left = (17.979577 + 20 * 0.82020423) + "%";

                for (let i=1; i<=2; i++)
                {
                    for (let j=1; j<=6; j++)
                    {
                        for (let k=1; k<=6; k++)
                        {
                            if (document.getElementById('cell_' + i + '_' + j + '_' + k)!=null)
                            {
                                dojo.removeClass('cell_' + i + '_' + j + '_' + k,'cell');
                                dojo.addClass('cell_' + i + '_' + j + '_' + k,'cell2');
                            }
                        }
                    }
                }
            }


            dojo.query( '.cellwagon1' ).connect( 'onclick', this, 'onClickCellWagon' );
            dojo.query( '.cellwagon2' ).connect( 'onclick', this, 'onClickCellWagon' );
            dojo.query( '.cellwagon3' ).connect( 'onclick', this, 'onClickCellWagon' );
            if (this.TotalPlayers==2)
            {
                dojo.query( '.cell2' ).connect( 'onclick', this, 'onClickCellBoard' );
            }
            else
            {
                dojo.query( '.cell' ).connect( 'onclick', this, 'onClickCellBoard' );
            }

            // TODO: Set up your game interface here, according to "gamedatas"

            var count=0;
            for( var i in this.gamedatas.money )
            {
                var mon = this.gamedatas.money[i];
                for (let i = 1; i <= mon.money; i++)
                {
                    count = count + 1;
                    this.addMoneyPlayer(count, mon.player_no);
                }
            }
            for( var i in this.gamedatas.drawntiles )
            {
                var drawntile = this.gamedatas.drawntiles[i];
                this.addTile( 'tiles',0, drawntile.id, drawntile.val, 0, 0 );
            }

            for( var i in this.gamedatas.animalsthinking )
            {
                var animalthinking = this.gamedatas.animalsthinking[i];
                this.addTile( 'cell_'+animalthinking.player_no+'_'+animalthinking.x+'_'+animalthinking.y,animalthinking.player_no, animalthinking.id, animalthinking.val, animalthinking.x, animalthinking.y );
                dojo.addClass('tile_'+animalthinking.player_no+'_'+animalthinking.id+'_'+animalthinking.val+'_'+animalthinking.x+'_'+animalthinking.y,'boardsize');
                dojo.addClass('tile_'+animalthinking.player_no+'_'+animalthinking.id+'_'+animalthinking.val+'_'+animalthinking.x+'_'+animalthinking.y,'thinking');
                dojo.query( '#tile_'+animalthinking.player_no+'_'+animalthinking.id+'_'+animalthinking.val+'_'+animalthinking.x+'_'+animalthinking.y).connect( 'onclick', this, 'onClickTile' );

                this.Zoo[animalthinking.player_no-1][animalthinking.x-1][animalthinking.y-1]=animalthinking.val+'_'+animalthinking.id+'_TH';
            }

            for( var i in this.gamedatas.animalsthinkingwagon )
            {
                var animalthinking = this.gamedatas.animalsthinkingwagon[i];
                dojo.addClass('tile_0_'+animalthinking.id+'_'+animalthinking.val+'_'+animalthinking.x+'_'+animalthinking.y,'thinking');
            }

            for( var i in this.gamedatas.animalsplayed )
            {
                var animalplayed = this.gamedatas.animalsplayed[i];
                this.addTile( 'cell_'+animalplayed.player_no+'_'+animalplayed.x+'_'+animalplayed.y,animalplayed.player_no, animalplayed.id, animalplayed.val, animalplayed.x, animalplayed.y );
                dojo.addClass('tile_'+animalplayed.player_no+'_'+animalplayed.id+'_'+animalplayed.val+'_'+animalplayed.x+'_'+animalplayed.y,'boardsize');
                dojo.query( '#tile_'+animalplayed.player_no+'_'+animalplayed.id+'_'+animalplayed.val+'_'+animalplayed.x+'_'+animalplayed.y).connect( 'onclick', this, 'onClickTile' );

                this.Zoo[animalplayed.player_no-1][animalplayed.x-1][animalplayed.y-1]=animalplayed.val+'_'+animalplayed.id+'_PL';
            }

            for( var i in this.gamedatas.animalsstall )
            {
                var animalstall = this.gamedatas.animalsstall[i];
                this.addTile( 'stall_' + animalstall.player_no,animalstall.player_no, animalstall.id, animalstall.val, animalstall.x, animalstall.y );
                dojo.addClass('tile_'+animalstall.player_no+'_'+animalstall.id+'_'+animalstall.val+'_'+animalstall.x+'_'+animalstall.y,'stallsize');
                dojo.query( '#tile_'+animalstall.player_no+'_'+animalstall.id+'_'+animalstall.val+'_'+animalstall.x+'_'+animalstall.y).connect( 'onclick', this, 'onClickTile' );
            }

            var t1 = 0;
            if (parseInt(gamedatas.tilesleft)>5)
            {
                t1 = 5;
            }
            else
            {
                t1 = parseInt(gamedatas.tilesleft);
            }
            var t2 = 0;
            if (parseInt(gamedatas.tilesleft2)>5)
            {
                t2 = 5;
            }
            else
            {
                t2 = parseInt(gamedatas.tilesleft2);
            }
            var count = 0;
            for (let i=1; i<=t2; i++)
            {
                count = count + 1;
                this.addBack2(i);
            }
            if (parseInt(gamedatas.tilesleft2)>=15)
            {
                this.addDisk(count+1);
            }
            count = 0;
            for (let i=1; i<=t1; i++)
            {
                count = count + 1;
                this.addBack(i);
            }

            this.paramvalue = gamedatas.paramvalue;
            if (this.paramvalue=="2")
            {
                if (document.getElementById('tilesleft')!=null) dojo.destroy('tilesleft');
                this.addTilesLeft(count+2,gamedatas.tilesleft);
            }

            if (gamedatas.lastround=="Y")
            {
                this.addLastRound(_('This is the last round...'));
            }
            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },

        getTileValue: function (id)
        {
            return id.split('_')[3];
        },
        highlightCellBoard: function (plno, thisid, type, highlight)
        {
            cells = new Array();
            if (type.search("Stall")==-1)
            {
                for (let j=0; j<this.Zoo[plno-1].length-1; j++)
                {
                    var found = false;
                    var k=0;
                    var typefound = "";
                    while (!found && k<=5)
                    {
                        if (this.Zoo[plno-1][j][k]=="" && typefound=="")
                        {
                            cells.push((j+1)+'_'+(k+1));
                            if (highlight)
                            {
                                dojo.addClass('cell_'+plno+'_'+(j+1)+'_'+(k+1),'highlighted2');
                            }
                            found = true;
                        }
                        else if (this.Zoo[plno-1][j][k]=="" && typefound!="")
                        {
                            if (typefound==type.substr(0,1))
                            {
                                cells.push((j+1)+'_'+(k+1));
                                if (highlight)
                                {
                                    dojo.addClass('cell_'+plno+'_'+(j+1)+'_'+(k+1),'highlighted2');
                                }
                                found = true;
                            }
                        }
                        else if (this.Zoo[plno-1][j][k]!="" && this.Zoo[plno-1][j][k]!="X")
                        {
                            if (this.Zoo[plno-1][j][k].split('_')[1]==thisid)
                            {
                                k = 5;
                            }
                            else
                            {
                                typefound=this.Zoo[plno-1][j][k].substr(0,1);
                            }
                        }
                        k = k + 1;
                    }
                }
            }
            else
            {
                for (let k=0; k<this.Zoo[plno-1][this.Zoo[plno-1].length-1].length; k++)
                {
                    if (this.Zoo[plno-1][this.Zoo[plno-1].length-1][k]=="")
                    {
                        cells.push((this.Zoo[plno-1].length)+'_'+(k+1));
                        if (highlight)
                        {
                            dojo.addClass('cell_'+plno+'_'+(this.Zoo[plno-1].length)+'_'+(k+1),'highlighted2');
                        }
                    }
                }
            }
            return cells;
        },
        onClickTile: function( evt )
        {
            if (this.isCurrentPlayerActive() &&
                this.StateNameValue=="ArrangeZoo" &&
                evt.target.id.split('_')[0] == "tile" &&
                this.getTileValue(evt.target.id)!="Coin" &&
                (evt.target.id.split('_')[1] == 0 ||
                this.Zoo[this.PlayerNo-1][evt.target.id.split('_')[4]-1][evt.target.id.split('_')[5]-1].split('_')[2]=="TH"
                )
                )
            {
                var elements = document.getElementsByClassName('highlighted2');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted2');
                }
                dojo.addClass(evt.target.id,'highlighted2');
                this.highlightCellBoard(this.PlayerNo, evt.target.id.split('_')[2],this.getTileValue(evt.target.id), true);
            }
            else if (this.isCurrentPlayerActive() &&
                this.StateNameValue=="Move" &&
                evt.target.id.split('_')[0] == "tile" &&
                this.getTileValue(evt.target.id)!="Coin" &&
                evt.target.id.split('_')[1] == this.PlayerNo
                /*&&
                evt.target.id.split('_')[4] != 0 &&
                evt.target.id.split('_')[5] != 0*/
                )
            {
                var elements = document.getElementsByClassName('highlighted2');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted2');
                }
                dojo.addClass(evt.target.id,'highlighted2');

                if (evt.target.parentNode.id == 'stall_' + this.PlayerNo ||
                    evt.target.className.search("tileStall")>=0
                    )
                    {
                        this.highlightCellBoard(this.PlayerNo, evt.target.id.split('_')[2],this.getTileValue(evt.target.id), true);
                    }
            }
            else if (this.isCurrentPlayerActive() &&
                this.StateNameValue=="Buy" &&
                evt.target.id.split('_')[0] == "tile" &&
                this.getTileValue(evt.target.id)!="Coin" &&
                evt.target.id.split('_')[1] != this.PlayerNo &&
                evt.target.id.split('_')[4] == 0 &&
                evt.target.id.split('_')[5] == 0
                )
            {
                var elements = document.getElementsByClassName('highlighted2');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted2');
                }
                dojo.addClass(evt.target.id,'highlighted2');
                this.highlightCellBoard(this.PlayerNo, evt.target.id.split('_')[2],this.getTileValue(evt.target.id), true);
            }
            else if (this.isCurrentPlayerActive() &&
                this.StateNameValue=="Discard" &&
                evt.target.id.split('_')[0] == "tile" &&
                this.getTileValue(evt.target.id)!="Coin" &&
                evt.target.id.split('_')[1] == this.PlayerNo &&
                evt.target.id.split('_')[4] == 0 &&
                evt.target.id.split('_')[5] == 0
                )
            {
                var elements = document.getElementsByClassName('highlighted2');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted2');
                }
                dojo.addClass(evt.target.id,'highlighted2');

                dojo.removeClass("confirmdiscard","buttoninvisible");
                dojo.addClass("confirmdiscard","buttonvisible");
            }
            else if (this.isCurrentPlayerActive() &&
                this.StateNameValue=="Swap" &&
                evt.target.id.split('_')[0] == "tile" &&
                this.getTileValue(evt.target.id)!="Coin" &&
                evt.target.id.split('_')[1] == this.PlayerNo &&
                evt.target.id.split('_')[3].search("Stall")<0 &&
                evt.target.parentNode.className.search("highlighted3")<=0
                )
            {
                dojo.removeClass("confirmswap","buttonvisible");
                dojo.addClass("confirmswap","buttoninvisible");

                var elements = document.getElementsByClassName('highlighted2');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted2');
                }
                var elements = document.getElementsByClassName('highlighted3');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted3');
                }

                var encid = evt.target.id.split('_')[4];
                var set1 = 0;
                var size1 = 0;

                if (encid!=0)
                {
                    for (let i=1; i<=6; i++)
                    {
                        if (document.getElementById("cell_" + this.PlayerNo + "_" + encid + "_" + i)!=null)
                        {
                            size1 = size1 + 1;
                            if (document.getElementById("cell_" + this.PlayerNo + "_" + encid + "_" + i).children.length==1)
                            {
                                dojo.addClass(document.getElementById("cell_" + this.PlayerNo + "_" + encid + "_" + i).children[0].id,'highlighted2');
                                set1 = set1 + 1;
                            }
                        }
                    }
                }
                else
                {
                    for (let i=0; i<evt.target.parentNode.children.length; i++)
                    {
                        var animal = evt.target.id.split('_')[3].substring(0,1);
                        if (evt.target.parentNode.children[i].id.split('_')[3].substring(0,1)==animal)
                            {
                                dojo.addClass(evt.target.parentNode.children[i].id,'highlighted2');
                                set1 = set1 + 1;
                            }
                    }
                    size1 = 1000;
                }

                var maxencid = 3;
                if (this.UnblockedZoo[this.PlayerNo-1]==1) maxencid+=1;
                else if (this.UnblockedZoo[this.PlayerNo-1]==2) maxencid+=2;
                for (let i=1; i<=maxencid; i++)
                {
                    if (i!=encid)
                    {
                        var set2 = 0;
                        var size2 = 0;
                        for (let j=1; j<= 6; j++)
                        {
                            if (document.getElementById("cell_" + this.PlayerNo + "_" + i + "_" + j)!=null)
                            {
                                size2 = size2 + 1;
                                if (document.getElementById("cell_" + this.PlayerNo + "_" + i + "_" + j).children.length==1)
                                {
                                    set2 = set2 + 1;
                                }
                            }
                        }

                        if (set2<=size1 && set1<=size2 && set2>0)
                        {
                            for (let j=1; j<= 6; j++)
                            {
                                if (document.getElementById("cell_" + this.PlayerNo + "_" + i + "_" + j)!=null)
                                {
                                    dojo.addClass("cell_" + this.PlayerNo + "_" + i + "_" + j,'highlighted3');
                                }
                            }
                        }
                    }
                }
            }
        },
        onClickCellBoard: function( evt )
        {
            if (this.isCurrentPlayerActive() &&
                this.StateNameValue=="ArrangeZoo" &&
                evt.target.id.split('_')[0] == "cell" &&
                document.getElementById(evt.target.id).className.search("highlighted2")>=0)
            {
                var elements = document.getElementsByClassName('highlighted2');
                for (let i=0; i<elements.length; i++)
                {
                    if (elements[i].id.split('_')[0]=="cell")
                    {
                        dojo.removeClass(elements[i].id,'highlighted2');
                        i = i - 1;
                    }
                }
                dojo.addClass(evt.target.id,'highlighted2');

                if (document.getElementsByClassName("highlighted2").length==2)
                {
                    var tileid = "";
                    var x="";
                    var y="";
                    var wagonid="";
                    var posid="";
                    var pid="";
                    for (let i=0; i<document.getElementsByClassName("highlighted2").length; i++)
                    {
                        if (document.getElementsByClassName("highlighted2")[i].id.split('_')[0]=="cell")
                        {
                            x=document.getElementsByClassName("highlighted2")[i].id.split('_')[2];
                            y=document.getElementsByClassName("highlighted2")[i].id.split('_')[3];
                        }
                        else if (document.getElementsByClassName("highlighted2")[i].id.split('_')[0]=="tile")
                        {
                            pid=document.getElementsByClassName("highlighted2")[i].id.split('_')[1];
                            tileid=document.getElementsByClassName("highlighted2")[i].id.split('_')[2];
                            wagonid=document.getElementsByClassName("highlighted2")[i].id.split('_')[4];
                            posid=document.getElementsByClassName("highlighted2")[i].id.split('_')[5];
                        }
                    }

                    if( this.checkAction( 'actArrangeTiles' ) )    // Check that this action is possible at this moment
                    {
                        var elements = document.getElementsByClassName('highlighted2');
                        while(elements.length > 0)
                        {
                            dojo.removeClass(elements[0].id,'highlighted2');
                        }
                        var elements = document.getElementsByClassName('pointer');
                        while(elements.length > 0)
                        {
                            dojo.removeClass(elements[0].id,'pointer');
                        }
                        this.bgaPerformAction('actArrangeTiles', {
                            tileid: tileid,
                            wagonid: wagonid,
                            posid: posid,
                            x: x,
                            y: y,
                            pid: pid,
                        });
                    }
                }
            }
            else if (this.isCurrentPlayerActive() &&
                this.StateNameValue=="Move" &&
                evt.target.id.split('_')[0] == "cell" &&
                document.getElementById(evt.target.id).className.search("highlighted2")>=0)
            {
                var elements = document.getElementsByClassName('highlighted2');
                for (let i=0; i<elements.length; i++)
                {
                    if (elements[i].id.split('_')[0]=="cell")
                    {
                        dojo.removeClass(elements[i].id,'highlighted2');
                        i = i - 1;
                    }
                }
                dojo.addClass(evt.target.id,'highlighted2');

                if (document.getElementsByClassName("highlighted2").length==2)
                {
                    var tileid = "";
                    var pid = "";
                    var x0="";
                    var y0="";
                    var x1="";
                    var y1="";
                    for (let i=0; i<document.getElementsByClassName("highlighted2").length; i++)
                    {
                        if (document.getElementsByClassName("highlighted2")[i].id.split('_')[0]=="cell")
                        {
                            x1=document.getElementsByClassName("highlighted2")[i].id.split('_')[2];
                            y1=document.getElementsByClassName("highlighted2")[i].id.split('_')[3];
                        }
                        else if (document.getElementsByClassName("highlighted2")[i].id.split('_')[0]=="tile")
                        {
                            pid=document.getElementsByClassName("highlighted2")[i].id.split('_')[1];
                            tileid=document.getElementsByClassName("highlighted2")[i].id.split('_')[2];
                            x0=document.getElementsByClassName("highlighted2")[i].id.split('_')[4];
                            y0=document.getElementsByClassName("highlighted2")[i].id.split('_')[5];
                        }
                    }

                    console.log(x0);
                    console.log(x1);

                    if( this.checkAction( 'actMove' ) && (x1!=x0 ||
                                                        (this.TotalPlayers==2 && (x1==6 || x0==6))  ||
                                                        (this.TotalPlayers>2 && (x1==5 || x0==5))
                                                        )
                        )// Check that this action is possible at this moment
                    {
                        var elements = document.getElementsByClassName('highlighted2');
                        while(elements.length > 0)
                        {
                            dojo.removeClass(elements[0].id,'highlighted2');
                        }
                        var elements = document.getElementsByClassName('pointer');
                        while(elements.length > 0)
                        {
                            dojo.removeClass(elements[0].id,'pointer');
                        }
                        this.bgaPerformAction( "actMoveTile", {
                            tileid: tileid,
                            pid: pid,
                            x0: x0,
                            y0: y0,
                            x1: x1,
                            y1: y1,
                        });
                    }
                    else if (x1==x0)
                    {
                        dojo.removeClass(evt.target.id,'highlighted2');
                    }
                }
            }
            else if (this.isCurrentPlayerActive() &&
                this.StateNameValue=="Buy" &&
                evt.target.id.split('_')[0] == "cell" &&
                document.getElementById(evt.target.id).className.search("highlighted2")>=0)
            {
                var elements = document.getElementsByClassName('highlighted2');
                for (let i=0; i<elements.length; i++)
                {
                    if (elements[i].id.split('_')[0]=="cell")
                    {
                        dojo.removeClass(elements[i].id,'highlighted2');
                        i = i - 1;
                    }
                }
                dojo.addClass(evt.target.id,'highlighted2');

                if (document.getElementsByClassName("highlighted2").length==2)
                {
                    var tileid = "";
                    var pid = "";
                    var x0="";
                    var y0="";
                    var x1="";
                    var y1="";
                    for (let i=0; i<document.getElementsByClassName("highlighted2").length; i++)
                    {
                        if (document.getElementsByClassName("highlighted2")[i].id.split('_')[0]=="cell")
                        {
                            x1=document.getElementsByClassName("highlighted2")[i].id.split('_')[2];
                            y1=document.getElementsByClassName("highlighted2")[i].id.split('_')[3];
                        }
                        else if (document.getElementsByClassName("highlighted2")[i].id.split('_')[0]=="tile")
                        {
                            pid=document.getElementsByClassName("highlighted2")[i].id.split('_')[1];
                            tileid=document.getElementsByClassName("highlighted2")[i].id.split('_')[2];
                            x0=document.getElementsByClassName("highlighted2")[i].id.split('_')[4];
                            y0=document.getElementsByClassName("highlighted2")[i].id.split('_')[5];
                        }
                    }

                    if( this.checkAction( 'actBuyTile' ) )    // Check that this action is possible at this moment
                    {
                        var elements = document.getElementsByClassName('highlighted2');
                        while(elements.length > 0)
                        {
                            dojo.removeClass(elements[0].id,'highlighted2');
                        }
                        var elements = document.getElementsByClassName('pointer');
                        while(elements.length > 0)
                        {
                            dojo.removeClass(elements[0].id,'pointer');
                        }
                        this.bgaPerformAction( "actBuyTile", {
                            tileid: tileid,
                            pid: pid,
                            x0: x0,
                            y0: y0,
                            x1: x1,
                            y1: y1,
                        });
                    }
                }
            }
            else if (this.isCurrentPlayerActive() &&
                this.StateNameValue=="Swap" &&
                evt.target.id.split('_')[0] == "cell" &&
                document.getElementById(evt.target.id).className.search("highlighted3")>=0)
            {
                var elements = document.getElementsByClassName('highlighted3');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted3');
                }

                for (let i=1; i<=6; i++)
                {
                    if (document.getElementById("cell_"+this.PlayerNo+"_"+evt.target.id.split('_')[2]+"_"+i)!=null)
                    {
                        dojo.addClass("cell_"+this.PlayerNo+"_"+evt.target.id.split('_')[2]+"_"+i,"highlighted3");
                    }
                }
                dojo.removeClass("confirmswap","buttoninvisible");
                dojo.addClass("confirmswap","buttonvisible");
            }
            else if (this.isCurrentPlayerActive() &&
                this.StateNameValue=="Swap" &&
                evt.target.id.split('_')[0] == "tile" &&
                document.getElementById(evt.target.parentNode.id).className.search("highlighted3")>=0)
            {
                var elements = document.getElementsByClassName('highlighted3');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted3');
                }

                for (let i=1; i<=6; i++)
                {
                    if (document.getElementById("cell_"+this.PlayerNo+"_"+evt.target.parentNode.id.split('_')[2]+"_"+i)!=null)
                    {
                        dojo.addClass("cell_"+this.PlayerNo+"_"+evt.target.parentNode.id.split('_')[2]+"_"+i,"highlighted3");
                    }
                }
                dojo.removeClass("confirmswap","buttoninvisible");
                dojo.addClass("confirmswap","buttonvisible");
            }
        },
        onClickCellWagon: function( evt )
        {
            if (this.isCurrentPlayerActive() &&
                this.StateNameValue=="PlaceTile" &&
                evt.target.id.split('_')[0] == "wagon" &&
                this.Wagons[parseInt(evt.target.id.split('_')[1])-1][parseInt(evt.target.id.split('_')[2])-1]=="")
            {
                var elements = document.getElementsByClassName('highlighted');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted');
                }
                dojo.addClass(evt.target.id,'highlighted');

                dojo.removeClass("placetile","buttoninvisible");
                dojo.addClass("placetile","buttonvisible");
            }
            else if (this.isCurrentPlayerActive() &&
                this.StateNameValue=="PlayerTurn" &&
                !this.isInterfaceLocked())
            {
                var wagonid = "";
                if (evt.target.id.split('_')[0] == "wagon")
                {
                    wagonid = evt.target.id.split('_')[1];
                }
                else if (evt.target.id.split('_')[0] == "tile")
                {
                    wagonid = evt.target.id.split('_')[4];
                }
                if (this.countWagonsSitOccupied(wagonid)>0)
                {
                    var elements = document.getElementsByClassName('highlighted');
                    while(elements.length > 0)
                    {
                        dojo.removeClass(elements[0].id,'highlighted');
                    }
                    dojo.addClass('wagon_'+wagonid,'highlighted');

                    dojo.removeClass("takewagon","buttoninvisible");
                    dojo.addClass("takewagon","buttonvisible");
                    dojo.removeClass("back2","buttoninvisible");
                    dojo.addClass("back2","buttonvisible");
                    dojo.removeClass("drawtile","buttonvisible");
                    dojo.addClass("drawtile","buttoninvisible");

                    dojo.removeClass("buy","buttonvisible");
                    dojo.addClass("buy","buttoninvisible");
                    dojo.removeClass("move","buttonvisible");
                    dojo.addClass("move","buttoninvisible");
                    dojo.removeClass("swap","buttonvisible");
                    dojo.addClass("swap","buttoninvisible");
                    dojo.removeClass("discard","buttonvisible");
                    dojo.addClass("discard","buttoninvisible");
                    dojo.removeClass("buyenclosure","buttonvisible");
                    dojo.addClass("buyenclosure","buttoninvisible");
                }
            }
        },
        onDrawTile: function ()
        {
           if( this.checkAction( 'actDrawTile' ) )    // Check that this action is possible at this moment
           {
                dojo.removeClass("drawtile","buttonvisible");
                dojo.addClass("drawtile","buttoninvisible");
                dojo.removeClass("takewagon","buttonvisible");
                dojo.addClass("takewagon","buttoninvisible");
                dojo.removeClass("back2","buttonvisible");
                dojo.addClass("back2","buttoninvisible");
                dojo.removeClass("buyenclosure","buttonvisible");
                dojo.addClass("buyenclosure","buttoninvisible");

                dojo.removeClass("move","buttonvisible");
                dojo.addClass("move","buttoninvisible");
                dojo.removeClass("swap","buttonvisible");
                dojo.addClass("swap","buttoninvisible");
                dojo.removeClass("buy","buttonvisible");
                dojo.addClass("buy","buttoninvisible");
                dojo.removeClass("discard","buttonvisible");
                dojo.addClass("discard","buttoninvisible");

                var elements = document.getElementsByClassName('highlighted');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted');
                }
                var elements = document.getElementsByClassName('pointer');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'pointer');
                }
                this.bgaPerformAction('actDrawTile');
           }
        },

        onPlaceTile: function ()
        {
           if (document.getElementsByClassName("highlighted").length==1)
           {
               if( this.checkAction( 'actPlaceTile' ) )    // Check that this action is possible at this moment
               {
                    dojo.removeClass("placetile","buttonvisible");
                    dojo.addClass("placetile","buttoninvisible");
                    let x = document.getElementsByClassName("highlighted")[0].id.split('_')[1];
                    let y = document.getElementsByClassName("highlighted")[0].id.split('_')[2];
                    var elements = document.getElementsByClassName('highlighted');
                    while(elements.length > 0)
                    {
                        dojo.removeClass(elements[0].id,'highlighted');
                    }
                    var elements = document.getElementsByClassName('pointer');
                    while(elements.length > 0)
                    {
                        dojo.removeClass(elements[0].id,'pointer');
                    }

                    this.bgaPerformAction( "actPlaceTile", {
                        x: x,
                        y: y,
                    });
               }
           }
        },

        countNotMoneyinWagon: function()
        {
            var count = 0;
            for (let i=0; i<document.getElementsByClassName("highlighted")[0].children.length; i++)
            {
                for (let j=0; j<document.getElementsByClassName("highlighted")[0].children[i].children.length; j++)
                {
                    if (document.getElementsByClassName("highlighted")[0].children[i].children[j].id.split('_')[3]!="Coin")
                    {
                        count = count + 1;
                    }
                }
            }
            return count;
        },

        onConfirm: function (evt, nodialog = false)
        {
           if( this.checkAction( 'actConfirmArrangement' ) )    // Check that this action is possible at this moment
           {
                if (!nodialog && this.countNotMoneyinWagon() != 0)
                {
                    this.confirmationDialog( _('Are you sure you want to confirm the arrangement?'), dojo.hitch( this, function() {
                            this.onConfirm(evt, true);
                        } ) );
                }
                else
                {
                    dojo.removeClass("confirm","buttonvisible");
                    dojo.addClass("confirm","buttoninvisible");
                    dojo.removeClass("autoarrange","buttonvisible");
                    dojo.addClass("autoarrange","buttoninvisible");
                    dojo.removeClass("reset","buttonvisible");
                    dojo.addClass("reset","buttoninvisible");

                    var elements = document.getElementsByClassName('pointer');
                    while(elements.length > 0)
                    {
                        dojo.removeClass(elements[0].id,'pointer');
                    }
                    var elements = document.getElementsByClassName('highlighted');
                    while(elements.length > 0)
                    {
                        dojo.removeClass(elements[0].id,'highlighted');
                    }
                    var elements = document.getElementsByClassName('highlighted2');
                    while(elements.length > 0)
                    {
                        dojo.removeClass(elements[0].id,'highlighted2');
                    }

                    this.bgaPerformAction( "actConfirmArrangement", {
                        lock: true,
                    }, this, function( result ) {} );
                }
           }
        },
        onDiscard: function ()
        {
            if( this.checkAction( 'actDiscard' ) )    // Check that this action is possible at this moment
            {
                dojo.removeClass("drawtile","buttonvisible");
                dojo.addClass("drawtile","buttoninvisible");
                dojo.removeClass("takewagon","buttonvisible");
                dojo.addClass("takewagon","buttoninvisible");
                dojo.removeClass("back2","buttonvisible");
                dojo.addClass("back2","buttoninvisible");
                dojo.removeClass("buyenclosure","buttonvisible");
                dojo.addClass("buyenclosure","buttoninvisible");

                dojo.removeClass("move","buttonvisible");
                dojo.addClass("move","buttoninvisible");
                dojo.removeClass("swap","buttonvisible");
                dojo.addClass("swap","buttoninvisible");
                dojo.removeClass("buy","buttonvisible");
                dojo.addClass("buy","buttoninvisible");
                dojo.removeClass("discard","buttonvisible");
                dojo.addClass("discard","buttoninvisible");

                var elements = document.getElementsByClassName('highlighted');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted');
                }
                var elements = document.getElementsByClassName('pointer');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'pointer');
                }
                this.bgaPerformAction( "actDiscard", {} );
           }
        },
        onConfirmDiscard: function ()
        {
           if( this.checkAction( 'actConfirmDiscard' ) )    // Check that this action is possible at this moment
           {
                dojo.removeClass("back","buttonvisible");
                dojo.addClass("back","buttoninvisible");
                dojo.removeClass("confirmdiscard","buttonvisible");
                dojo.addClass("confirmdiscard","buttoninvisible");

                let tileid = document.getElementsByClassName("highlighted2")[0].id.split('_')[2];

                var elements = document.getElementsByClassName('highlighted');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted');
                }
                var elements = document.getElementsByClassName('highlighted2');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted2');
                }

                this.bgaPerformAction( "actConfirmDiscard", {
                    tileid: tileid,
                } );
           }
        },
        onConfirmSwap: function ()
        {
            if( this.checkAction( 'actSwapTiles' ) )
            {
                dojo.removeClass("back","buttonvisible");
                dojo.addClass("back","buttoninvisible");
                dojo.removeClass("confirmswap","buttonvisible");
                dojo.addClass("confirmswap","buttoninvisible");
                dojo.removeClass("reset2","buttonvisible");
                dojo.addClass("reset2","buttoninvisible");

                let enc1 = document.getElementsByClassName('highlighted2')[0].id.split('_')[4];
                let anid = document.getElementsByClassName('highlighted2')[0].id.split('_')[3].substring(0,1);
                let enc2 = document.getElementsByClassName('highlighted3')[0].id.split('_')[2];
                var elements = document.getElementsByClassName('highlighted2');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted2');
                }
                var elements = document.getElementsByClassName('highlighted3');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted3');
                }
                this.bgaPerformAction( "actSwapTiles", {
                    enc1: enc1,
                    enc2: enc2,
                    anid: anid,
                });
            }
        },
        onReset2: function ()
        {
            var elements = document.getElementsByClassName('highlighted2');
            while(elements.length > 0)
            {
                dojo.removeClass(elements[0].id,'highlighted2');
            }
            var elements = document.getElementsByClassName('highlighted3');
            while(elements.length > 0)
            {
                dojo.removeClass(elements[0].id,'highlighted3');
            }
            dojo.removeClass("confirmswap","buttonvisible");
            dojo.addClass("confirmswap","buttoninvisible");
        },
        onBuy: function ()
        {
            if( this.checkAction( 'actBuy' ) )
            {
                dojo.removeClass("drawtile","buttonvisible");
                dojo.addClass("drawtile","buttoninvisible");
                dojo.removeClass("takewagon","buttonvisible");
                dojo.addClass("takewagon","buttoninvisible");
                dojo.removeClass("back2","buttonvisible");
                dojo.addClass("back2","buttoninvisible");
                dojo.removeClass("buyenclosure","buttonvisible");
                dojo.addClass("buyenclosure","buttoninvisible");

                dojo.removeClass("move","buttonvisible");
                dojo.addClass("move","buttoninvisible");
                dojo.removeClass("swap","buttonvisible");
                dojo.addClass("swap","buttoninvisible");
                dojo.removeClass("buy","buttonvisible");
                dojo.addClass("buy","buttoninvisible");
                dojo.removeClass("discard","buttonvisible");
                dojo.addClass("discard","buttoninvisible");

                var elements = document.getElementsByClassName('highlighted');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted');
                }
                var elements = document.getElementsByClassName('pointer');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'pointer');
                }
                this.bgaPerformAction( "actBuy", {} );
           }
        },
        onBack2: function ()
        {
            dojo.removeClass("takewagon","buttonvisible");
            dojo.addClass("takewagon","buttoninvisible");
            dojo.removeClass("back2","buttonvisible");
            dojo.addClass("back2","buttoninvisible");
            if (this.isCurrentPlayerActive() && this.countWagonsSitFree()>0)
            {
                dojo.removeClass("drawtile","buttoninvisible");
                dojo.addClass("drawtile","buttonvisible");
            }
            var elements = document.getElementsByClassName('highlighted');
            while(elements.length > 0)
            {
                dojo.removeClass(elements[0].id,'highlighted');
            }

            if (this.isCurrentPlayerActive())
            {
                if (this.Money>=3)
                {
                    if (this.TotalPlayers==2)
                    {
                        if (this.UZ<=1)
                        {
                            dojo.removeClass("buyenclosure","buttoninvisible");
                            dojo.addClass("buyenclosure","buttonvisible");
                        }
                    }
                    else
                    {
                        if (this.UZ==0)
                        {
                            dojo.removeClass("buyenclosure","buttoninvisible");
                            dojo.addClass("buyenclosure","buttonvisible");
                        }
                    }
                }
                if (this.Money>=2)
                {
                    if (this.countTotalOtherBarn(this.PlayerNo)>0)
                    {
                        dojo.removeClass("buy","buttoninvisible");
                        dojo.addClass("buy","buttonvisible");
                    }
                    if (this.countTotalOwnBarn(this.PlayerNo)>0)
                    {
                        dojo.removeClass("discard","buttoninvisible");
                        dojo.addClass("discard","buttonvisible");
                    }
                }
                if (this.Money>=1)
                {
                    if (this.countTotalTilesEnclosuresStall(this.PlayerNo)>0)
                    {
                        dojo.removeClass("move","buttoninvisible");
                        dojo.addClass("move","buttonvisible");
                    }
                    if (this.countTotalZones(this.PlayerNo)>=2)
                    {
                        dojo.removeClass("swap","buttoninvisible");
                        dojo.addClass("swap","buttonvisible");
                    }
                }
            }
        },
        onBack: function ()
        {
           if( this.checkAction( 'actBack' ) )    // Check that this action is possible at this moment
           {
                dojo.removeClass("back","buttonvisible");
                dojo.addClass("back","buttoninvisible");
                dojo.removeClass("confirmdiscard","buttonvisible");
                dojo.addClass("confirmdiscard","buttoninvisible");

                var elements = document.getElementsByClassName('highlighted');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted');
                }
                var elements = document.getElementsByClassName('highlighted2');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted2');
                }
                var elements = document.getElementsByClassName('highlighted3');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted3');
                }

               this.bgaPerformAction( "actBack", { } );
           }
        },
        onMoveTile: function ()
        {
            if( this.checkAction( 'actMove' ) )    // Check that this action is possible at this moment
            {
                dojo.removeClass("drawtile","buttonvisible");
                dojo.addClass("drawtile","buttoninvisible");
                dojo.removeClass("takewagon","buttonvisible");
                dojo.addClass("takewagon","buttoninvisible");
                dojo.removeClass("back2","buttonvisible");
                dojo.addClass("back2","buttoninvisible");
                dojo.removeClass("buyenclosure","buttonvisible");
                dojo.addClass("buyenclosure","buttoninvisible");

                dojo.removeClass("move","buttonvisible");
                dojo.addClass("move","buttoninvisible");
                dojo.removeClass("swap","buttonvisible");
                dojo.addClass("swap","buttoninvisible");
                dojo.removeClass("buy","buttonvisible");
                dojo.addClass("buy","buttoninvisible");
                dojo.removeClass("discard","buttonvisible");
                dojo.addClass("discard","buttoninvisible");

                var elements = document.getElementsByClassName('highlighted');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted');
                }
                var elements = document.getElementsByClassName('pointer');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'pointer');
                }
                this.ajaxcall( "actMove", {} );
           }
        },
        onSwap: function ()
        {
           if( this.checkAction( 'actSwap' ) )    // Check that this action is possible at this moment
           {
                dojo.removeClass("drawtile","buttonvisible");
                dojo.addClass("drawtile","buttoninvisible");
                dojo.removeClass("takewagon","buttonvisible");
                dojo.addClass("takewagon","buttoninvisible");
                dojo.removeClass("back2","buttonvisible");
                dojo.addClass("back2","buttoninvisible");
                dojo.removeClass("buyenclosure","buttonvisible");
                dojo.addClass("buyenclosure","buttoninvisible");

                dojo.removeClass("move","buttonvisible");
                dojo.addClass("move","buttoninvisible");
                dojo.removeClass("swap","buttonvisible");
                dojo.addClass("swap","buttoninvisible");
                dojo.removeClass("buy","buttonvisible");
                dojo.addClass("buy","buttoninvisible");
                dojo.removeClass("discard","buttonvisible");
                dojo.addClass("discard","buttoninvisible");

                var elements = document.getElementsByClassName('highlighted');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted');
                }
                var elements = document.getElementsByClassName('pointer');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'pointer');
                }
               this.bgaPerformAction( "Swap", {} );
           }
        },
        onBuyEnclosure: function ()
        {
           if( this.checkAction( 'actBuyEnclosure' ) )    // Check that this action is possible at this moment
           {
                dojo.removeClass("drawtile","buttonvisible");
                dojo.addClass("drawtile","buttoninvisible");
                dojo.removeClass("takewagon","buttonvisible");
                dojo.addClass("takewagon","buttoninvisible");
                dojo.removeClass("back2","buttonvisible");
                dojo.addClass("back2","buttoninvisible");
                dojo.removeClass("buyenclosure","buttonvisible");
                dojo.addClass("buyenclosure","buttoninvisible");

                dojo.removeClass("move","buttonvisible");
                dojo.addClass("move","buttoninvisible");
                dojo.removeClass("swap","buttonvisible");
                dojo.addClass("swap","buttoninvisible");
                dojo.removeClass("buy","buttonvisible");
                dojo.addClass("buy","buttoninvisible");
                dojo.removeClass("discard","buttonvisible");
                dojo.addClass("discard","buttoninvisible");

                var elements = document.getElementsByClassName('highlighted');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted');
                }
                var elements = document.getElementsByClassName('pointer');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'pointer');
                }
               this.bgaPerformAction( "actBuyEnclosure", {} );
           }
        },
        onAutoArrange: function ()
        {
            if( this.checkAction( 'actAutoArrangeTiles' ) )    // Check that this action is possible at this moment
            {
                if (document.getElementsByClassName("highlighted").length==1)
                {
                    var elements = document.getElementsByClassName('highlighted2');
                    while(elements.length > 0)
                    {
                        dojo.removeClass(elements[0].id,'highlighted2');
                    }
                    var elements = document.getElementsByClassName('pointer');
                    while(elements.length > 0)
                    {
                        dojo.removeClass(elements[0].id,'pointer');
                    }

                    var wagonid="";
                    var tileid1 = "";
                    var posid1="";
                    var tileid2 = "";
                    var posid2="";
                    var tileid3 = "";
                    var posid3="";
                    var val1="";
                    var val2="";
                    var val3="";
                    var x1="";
                    var y1="";
                    var x2="";
                    var y2="";
                    var x3="";
                    var y3="";

                    var TileIds = new Array();
                    var Vals = new Array();
                    var Pos = new Array();

                    wagonid = document.getElementsByClassName("highlighted")[0].id.split('_')[1];
                    for (let i=0; i< document.getElementsByClassName("highlighted")[0].children.length; i++)
                    {
                        if (document.getElementsByClassName("highlighted")[0].children[i].children.length>=1)
                        {
                            TileIds.push(document.getElementsByClassName("highlighted")[0].children[i].children[0].id.split('_')[2]);
                            Vals.push(document.getElementsByClassName("highlighted")[0].children[i].children[0].id.split('_')[3]);
                            Pos.push(document.getElementsByClassName("highlighted")[0].children[i].children[0].id.split('_')[5]);
                        }
                    }

                    var ZooVals = new Array();
                    var ZooSpaces = new Array();

                    for (let i=0; i<this.Zoo[this.PlayerNo-1].length-1; i++)
                    {
                        var count = 0;
                        var found = false;
                        for (let j=0; j<this.Zoo[this.PlayerNo-1][i].length; j++)
                        {
                            if (this.Zoo[this.PlayerNo-1][i][j]=="")
                            {
                                count = count + 1;
                            }
                            if (this.Zoo[this.PlayerNo-1][i][j]!="X" && this.Zoo[this.PlayerNo-1][i][j]!="" && !found)
                            {
                                ZooVals.push(this.Zoo[this.PlayerNo-1][i][j].substring(0,1));
                                found = true;
                            }
                        }
                        if (!found)
                        {
                            ZooVals.push("");
                        }
                        ZooSpaces.push(count);
                    }

                    console.log(ZooVals);


                    if (/*Vals.length==1 ||
                        (Vals.length==2 && Vals[0].substring(0,1)!=Vals[1].substring(0,1)) ||
                        (Vals.length==3 && Vals[0].substring(0,1)!=Vals[1].substring(0,1) && Vals[0].substring(0,1)!=Vals[2].substring(0,1) && Vals[1].substring(0,1)!=Vals[2].substring(0,1))
                        */1==1)
                    {
                        for (let i=0; i<Vals.length; i++)
                        {
                            if (Vals[i].search("Stall")<0 && Vals[i].search("Coin")<0)
                            {
                                var found = false;
                                for (let j=0; j<ZooSpaces.length; j++)
                                {
                                    if (Vals[i].substring(0,1)==ZooVals[j] && ZooSpaces[j]>0 && !found)
                                    {
                                        var spaceid = 0;
                                        for (let x=0; x<this.Zoo[this.PlayerNo-1][j].length; x++)
                                        {
                                            if (this.Zoo[this.PlayerNo-1][j][x]=="" && spaceid==0)
                                            {
                                                spaceid = x + 1;
                                            }
                                        }

                                        if (i==0)
                                        {
                                            x1 = j+1;
                                            y1 = spaceid;
                                        }
                                        else if (i==1)
                                        {
                                            x2 = j+1;
                                            y2 = spaceid;
                                        }
                                        else if (i==2)
                                        {
                                            x3 = j+1;
                                            y3 = spaceid;
                                        }
                                        this.Zoo[this.PlayerNo-1][j][spaceid-1] = Vals[i] + '_' + TileIds[i] + '_TH';
                                        ZooSpaces[j] = ZooSpaces[j] - 1;
                                        found = true;
                                    }
                                }
                                if (!found)
                                {
                                    for (let j=0; j<ZooSpaces.length; j++)
                                    {
                                        if (ZooVals[j] == "" && ZooSpaces[j]>0 && !found)
                                        {
                                            var spaceid = 0;
                                            for (let x=0; x<this.Zoo[this.PlayerNo-1][j].length; x++)
                                            {
                                                if (this.Zoo[this.PlayerNo-1][j][x]=="" && spaceid==0)
                                                {
                                                    spaceid = x + 1;
                                                }
                                            }

                                            if (i==0)
                                            {
                                                x1 = j+1;
                                                y1 = spaceid;
                                            }
                                            else if (i==1)
                                            {
                                                x2 = j+1;
                                                y2 = spaceid;
                                            }
                                            else if (i==2)
                                            {
                                                x3 = j+1;
                                                y3 = spaceid;
                                            }
                                            this.Zoo[this.PlayerNo-1][j][spaceid-1] = Vals[i] + '_' + TileIds[i] + '_TH';
                                            ZooVals[j] = Vals[i].substring(0,1);
                                            ZooSpaces[j] = ZooSpaces[j] - 1;
                                            found = true;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (TileIds.length>=1) {tileid1 = TileIds[0]; posid1 = Pos[0]}
                    if (TileIds.length>=2) {tileid2 = TileIds[1]; posid2 = Pos[1]}
                    if (TileIds.length>=3) {tileid3 = TileIds[2]; posid3 = Pos[2]}

                    if (x1=="" && y1=="")
                    {
                        tileid1="";
                        val1="";
                    }
                    if (x2=="" && y2=="")
                    {
                        tileid2="";
                        val2="";
                    }
                    if (x3=="" && y3=="")
                    {
                        tileid3="";
                        val3="";
                    }

                    this.bgaPerformAction( "actAutoArrangeTiles", {
                        wagonid: wagonid,
                        tileid1: tileid1,
                        posid1: posid1,
                        tileid2: tileid2,
                        posid2: posid2,
                        tileid3: tileid3,
                        posid3: posid3,
                        x1: x1,
                        y1: y1,
                        x2: x2,
                        y2: y2,
                        x3: x3,
                        y3: y3,
                    });
                }
            }
        },
        onBackTakeWagon: function ()
        {
           if( this.checkAction( 'actGoBack' ) && document.getElementsByClassName("highlighted").length==1)    // Check that this action is possible at this moment
           {
                let x = document.getElementsByClassName("highlighted")[0].id.split('_')[1];
                dojo.removeClass("backtakewagon","buttonvisible");
                dojo.addClass("backtakewagon","buttoninvisible");
                dojo.removeClass("confirm","buttonvisible");
                dojo.addClass("confirm","buttoninvisible");
                dojo.removeClass("autoarrange","buttonvisible");
                dojo.addClass("autoarrange","buttoninvisible");
                dojo.removeClass("reset","buttonvisible");
                dojo.addClass("reset","buttoninvisible");

                this.bgaPerformAction( "actGoBack", {
                    x: x,
                });
           }
        },
        onReset: function ()
        {
           if( this.checkAction( 'actReset' ) )    // Check that this action is possible at this moment
           {
               this.bgaPerformAction( "actReset", {} );
           }
        },
        onTakeWagon: function ()
        {
           if (document.getElementsByClassName("highlighted").length==1)
           {
               if( this.checkAction( 'actTakeWagon' ) )    // Check that this action is possible at this moment
               {
                    dojo.removeClass("drawtile","buttonvisible");
                    dojo.addClass("drawtile","buttoninvisible");
                    dojo.removeClass("takewagon","buttonvisible");
                    dojo.addClass("takewagon","buttoninvisible");
                    dojo.removeClass("back2","buttonvisible");
                    dojo.addClass("back2","buttoninvisible");
                    dojo.removeClass("buyenclosure","buttonvisible");
                    dojo.addClass("buyenclosure","buttoninvisible");

                    dojo.removeClass("move","buttonvisible");
                    dojo.addClass("move","buttoninvisible");
                    dojo.removeClass("swap","buttonvisible");
                    dojo.addClass("swap","buttoninvisible");
                    dojo.removeClass("buy","buttonvisible");
                    dojo.addClass("buy","buttoninvisible");
                    dojo.removeClass("discard","buttonvisible");
                    dojo.addClass("discard","buttoninvisible");

                    let x = document.getElementsByClassName("highlighted")[0].id.split('_')[1];
                    var elements = document.getElementsByClassName('pointer');
                    while(elements.length > 0)
                    {
                        dojo.removeClass(elements[0].id,'pointer');
                    }

                    this.bgaPerformAction( "actTakeWagon", {
                        x: x,
                    });
               }
           }
        },
        ///////////////////////////////////////////////////
        //// Game & client states

        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName, args );
            this.StateNameValue=stateName;

            this.addActionButton('drawtile', _('Draw a tile'), 'onDrawTile');
            document.getElementById("drawtile").innerHTML=_("Draw a tile");
            dojo.removeClass("drawtile","buttonvisible");
            dojo.addClass("drawtile","buttoninvisible");

            this.addActionButton('placetile', _('Place tile'), 'onPlaceTile');
            document.getElementById("placetile").innerHTML=_("Place tile");
            dojo.removeClass("placetile","buttonvisible");
            dojo.addClass("placetile","buttoninvisible");

            this.addActionButton('takewagon', _('Take Wagon'), 'onTakeWagon');
            document.getElementById("takewagon").innerHTML=_("Take Wagon");
            dojo.removeClass("takewagon","buttonvisible");
            dojo.addClass("takewagon","buttoninvisible");


            this.addActionButton('confirm', _('Confirm Arrangement'), 'onConfirm');
            document.getElementById("confirm").innerHTML=_("Confirm Arrangement");
            dojo.removeClass("confirm","buttonvisible");
            dojo.addClass("confirm","buttoninvisible");

            this.addActionButton('autoarrange', _('Auto Arrange'), 'onAutoArrange');
            document.getElementById("autoarrange").innerHTML=_("Auto Arrange");
            dojo.removeClass("autoarrange","buttonvisible");
            dojo.addClass("autoarrange","buttoninvisible");

            this.addActionButton('reset', _('Reset'), 'onReset');
            document.getElementById("reset").innerHTML=_("Reset");
            dojo.removeClass("reset","buttonvisible");
            dojo.addClass("reset","buttoninvisible");

            this.addActionButton('buyenclosure', _('Expand the Zoo'), 'onBuyEnclosure');
            document.getElementById("buyenclosure").innerHTML=_("Expand the Zoo");
            dojo.removeClass("buyenclosure","buttonvisible");
            dojo.addClass("buyenclosure","buttoninvisible");

            this.addActionButton('move', _('Move'), 'onMoveTile');
            document.getElementById("move").innerHTML=_("Move");
            dojo.removeClass("move","buttonvisible");
            dojo.addClass("move","buttoninvisible");

            this.addActionButton('swap', _('Exchange'), 'onSwap');
            document.getElementById("swap").innerHTML=_("Exchange");
            dojo.removeClass("swap","buttonvisible");
            dojo.addClass("swap","buttoninvisible");

            this.addActionButton('buy', _('Purchase'), 'onBuy');
            document.getElementById("buy").innerHTML=_("Purchase");
            dojo.removeClass("buy","buttonvisible");
            dojo.addClass("buy","buttoninvisible");

            this.addActionButton('discard', _('Discard'), 'onDiscard');
            document.getElementById("discard").innerHTML=_("Discard");
            dojo.removeClass("discard","buttonvisible");
            dojo.addClass("discard","buttoninvisible");

            this.addActionButton('back', _('Back'), 'onBack');
            document.getElementById("back").innerHTML=_("Back");
            dojo.removeClass("back","buttonvisible");
            dojo.addClass("back","buttoninvisible");

            this.addActionButton('back2', _('Back'), 'onBack2');
            document.getElementById("back2").innerHTML=_("Back");
            dojo.removeClass("back2","buttonvisible");
            dojo.addClass("back2","buttoninvisible");

            this.addActionButton('confirmdiscard', _('Confirm'), 'onConfirmDiscard');
            document.getElementById("confirmdiscard").innerHTML=_("Confirm");
            dojo.removeClass("confirmdiscard","buttonvisible");
            dojo.addClass("confirmdiscard","buttoninvisible");

            this.addActionButton('reset2', _('Reset'), 'onReset2');
            document.getElementById("reset2").innerHTML=_("Reset");
            dojo.removeClass("reset2","buttonvisible");
            dojo.addClass("reset2","buttoninvisible");

            this.addActionButton('confirmswap', _('Confirm'), 'onConfirmSwap');
            document.getElementById("confirmswap").innerHTML=_("Confirm");
            dojo.removeClass("confirmswap","buttonvisible");
            dojo.addClass("confirmswap","buttoninvisible");

            this.addActionButton('backtakewagon', _('Go Back'), 'onBackTakeWagon');
            document.getElementById("backtakewagon").innerHTML=_("Go Back");
            dojo.removeClass("backtakewagon","buttonvisible");
            dojo.addClass("backtakewagon","buttoninvisible");

            switch( stateName )
            {


            case 'PlayerTurn':

                this.Money = parseInt(args.args.money);
                this.UZ = parseInt(args.args.unblockedzoo);
                for( var i in args.args.wagons )
                {
                    var wagon = args.args.wagons[i];
                    this.Wagons[wagon.id-1][0] = wagon.val1 == null ? '' : wagon.val1;
                    if (wagon.size=="1")
                    {
                        this.Wagons[wagon.id-1][1] = "X";
                    }
                    else
                    {
                        this.Wagons[wagon.id-1][1] = wagon.val2 == null ? '' : wagon.val2;
                    }
                    if (wagon.size=="3")
                    {
                        this.Wagons[wagon.id-1][2] = wagon.val3 == null ? '' : wagon.val3;
                    }
                    else
                    {
                        this.Wagons[wagon.id-1][2] = "X";
                    }
                }

                if (this.isCurrentPlayerActive() && this.countWagonsSitFree()>0)
                {
                    dojo.removeClass("drawtile","buttoninvisible");
                    dojo.addClass("drawtile","buttonvisible");
                }
                else
                {
                    dojo.removeClass("drawtile","buttonvisible");
                    dojo.addClass("drawtile","buttoninvisible");
                }
                for (let i=0; i<this.Wagons.length; i++)
                {
                    if (this.isCurrentPlayerActive() &&
                        this.countWagonsSitOccupied(i+1)>0 &&
                        document.getElementById('wagon_' + (i+1))!=null
                        )
                    {
                        dojo.addClass('wagon_' + (i+1),'pointer');
                    }
                }

                if (this.isCurrentPlayerActive())
                {
                    if (this.Money>=3)
                    {
                        if (this.TotalPlayers==2)
                        {
                            if (this.UZ<=1)
                            {
                                dojo.removeClass("buyenclosure","buttoninvisible");
                                dojo.addClass("buyenclosure","buttonvisible");
                            }
                        }
                        else
                        {
                            if (this.UZ==0)
                            {
                                dojo.removeClass("buyenclosure","buttoninvisible");
                                dojo.addClass("buyenclosure","buttonvisible");
                            }
                        }
                    }
                    if (this.Money>=2)
                    {
                        if (this.countTotalOtherBarn(this.PlayerNo)>0)
                        {
                            dojo.removeClass("buy","buttoninvisible");
                            dojo.addClass("buy","buttonvisible");
                        }
                        if (this.countTotalOwnBarn(this.PlayerNo)>0)
                        {
                            dojo.removeClass("discard","buttoninvisible");
                            dojo.addClass("discard","buttonvisible");
                        }
                    }
                    if (this.Money>=1)
                    {
                        if (this.countTotalTilesEnclosuresStall(this.PlayerNo)>0)
                        {
                            dojo.removeClass("move","buttoninvisible");
                            dojo.addClass("move","buttonvisible");
                        }
                        if (this.countTotalZones(this.PlayerNo)>=2)
                        {
                            dojo.removeClass("swap","buttoninvisible");
                            dojo.addClass("swap","buttonvisible");
                        }
                    }
                }
                break;

            case 'PlaceTile':

                var elements = document.getElementsByClassName('pointer');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'pointer');
                }
                if (this.isCurrentPlayerActive())
                {
                    for (let i=0; i<this.Wagons.length; i++)
                    {
                        for (let j=0; j<3; j++)
                        {
                            if (this.Wagons[i][j]=="")
                            {
                                dojo.addClass("wagon_" + (i+1) + "_" + (j+1), "pointer");
                            }
                        }
                    }
                }
                break;

            case 'ArrangeZoo':

                var elements = document.getElementsByClassName('pointer');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'pointer');
                }
                if (this.isCurrentPlayerActive() && document.getElementsByClassName("highlighted").length>0)
                {
                    var wagonid = document.getElementsByClassName("highlighted")[0].id.split('_')[1];
                    for (let j=0; j<3; j++)
                    {
                        if (this.Wagons[wagonid-1][j]!="" && this.Wagons[wagonid-1][j]!="X")
                        {
                            dojo.addClass("wagon_" + (wagonid) + "_" + (j+1), "pointer");
                            if (document.getElementById("wagon_" + (wagonid) + "_" + (j+1)).children.length>0)
                            {
                                dojo.addClass(document.getElementById("wagon_" + (wagonid) + "_" + (j+1)).firstChild.id, "pointer");
                                dojo.query( '#' + document.getElementById("wagon_" + (wagonid) + "_" + (j+1)).firstChild.id ).connect( 'onclick', this, 'onClickTile' );
                            }
                        }
                    }
                }
                if (this.isCurrentPlayerActive())
                {
                    dojo.removeClass("backtakewagon","buttoninvisible");
                    dojo.addClass("backtakewagon","buttonvisible");
                    dojo.removeClass("confirm","buttoninvisible");
                    dojo.addClass("confirm","buttonvisible");
                    dojo.removeClass("autoarrange","buttoninvisible");
                    dojo.addClass("autoarrange","buttonvisible");
                    dojo.removeClass("reset","buttoninvisible");
                    dojo.addClass("reset","buttonvisible");
                }
                break;

            case 'Move':
                if (this.isCurrentPlayerActive())
                {
                    dojo.removeClass("back","buttoninvisible");
                    dojo.addClass("back","buttonvisible");

                    for (let j=1; j<=6 ; j++)
                    {
                        for (let k=1; k<=6; k++)
                        {
                            if (document.getElementById('cell_'+this.PlayerNo+'_'+j+'_'+k)!=null &&
                                document.getElementById('cell_'+this.PlayerNo+'_'+j+'_'+k).children.length>0)
                            {
                                dojo.addClass(document.getElementById('cell_'+this.PlayerNo+'_'+j+'_'+k).children[0].id,'pointer');
                            }
                        }
                    }
                    for (let i=0; i<document.getElementById('stall_' + this.PlayerNo).children.length; i++)
                    {
                        dojo.addClass(document.getElementById('stall_' + this.PlayerNo).children[i].id,'pointer');
                    }
                }


                break;

            case 'Swap':
                if (this.isCurrentPlayerActive())
                {
                    dojo.removeClass("back","buttoninvisible");
                    dojo.addClass("back","buttonvisible");
                    dojo.removeClass("reset2","buttoninvisible");
                    dojo.addClass("reset2","buttonvisible");
                }

                if (this.isCurrentPlayerActive())
                {
                    dojo.removeClass("back","buttoninvisible");
                    dojo.addClass("back","buttonvisible");

                    for (let j=1; j<=6 ; j++)
                    {
                        for (let k=1; k<=6; k++)
                        {
                            if (document.getElementById('cell_'+this.PlayerNo+'_'+j+'_'+k)!=null &&
                                document.getElementById('cell_'+this.PlayerNo+'_'+j+'_'+k).children.length>0 &&
                                document.getElementById('cell_'+this.PlayerNo+'_'+j+'_'+k).children[0].id.search("Stall")<0)
                            {
                                dojo.addClass(document.getElementById('cell_'+this.PlayerNo+'_'+j+'_'+k).children[0].id,'pointer');
                            }
                        }
                    }
                    for (let i=0; i<document.getElementById('stall_' + this.PlayerNo).children.length; i++)
                    {
                        dojo.addClass(document.getElementById('stall_' + this.PlayerNo).children[i].id,'pointer');
                    }
                }
                break;
            case 'Buy':
                if (this.isCurrentPlayerActive())
                {
                    dojo.removeClass("back","buttoninvisible");
                    dojo.addClass("back","buttonvisible");
                }
                if (this.isCurrentPlayerActive())
                {
                    for (let pid = 1; pid <= this.TotalPlayers; pid ++)
                    {
                        if (pid!=this.PlayerNo)
                        {
                            for (let i=0; i<document.getElementById('stall_' + pid).children.length; i++)
                            {
                                dojo.addClass(document.getElementById('stall_' + pid).children[i].id,'pointer');
                            }
                        }
                    }
                }
                break;
            case 'Discard':
                if (this.isCurrentPlayerActive())
                {
                    dojo.removeClass("back","buttoninvisible");
                    dojo.addClass("back","buttonvisible");
                }

                if (this.isCurrentPlayerActive())
                {
                    for (let i=0; i<document.getElementById('stall_' + this.PlayerNo).children.length; i++)
                    {
                        dojo.addClass(document.getElementById('stall_' + this.PlayerNo).children[i].id,'pointer');
                    }
                }
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );

            var elements = document.getElementsByClassName('pointer');
            while(elements.length > 0)
            {
                dojo.removeClass(elements[0].id,'pointer');
            }


            switch( stateName )
            {


            case 'PlaceTile':

                var elements = document.getElementsByClassName('highlighted');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted');
                }

                break;

            case 'ArrangeZoo':

                var elements = document.getElementsByClassName('highlighted');
                while(elements.length > 0)
                {
                    dojo.removeClass(elements[0].id,'highlighted');
                }

                break;
            case 'NextTurn':
                for (let i=1; i<= this.TotalPlayers; i++)
                {
                    if (document.getElementById("playername_" + i)!=null)
                    {
                        document.getElementById("playername_" + i).innerHTML = document.getElementById("playername_" + i).innerHTML.replace( _(" - Took the wagon") ,'');
                    }
                }

                break;

            case 'dummmy':
                break;
            }
        },

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );

            if( this.isCurrentPlayerActive() )
            {
                switch( stateName )
                {
/*
                 Example:

                 case 'myGameState':

                    // Add 3 action buttons in the action status bar:

                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' );
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' );
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' );
                    break;
*/
                }
            }
        },

        ///////////////////////////////////////////////////
        //// Utility methods

        /*

            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.

        */


        ///////////////////////////////////////////////////
        //// Player's action

        /*

            Here, you are defining methods to handle player's action (ex: results of mouse click on
            game objects).

            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server

        */

        /* Example:

        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );

            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/zooloretto/zooloretto/myAction.html", {
                                                                    lock: true,
                                                                    myArgument1: arg1,
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 },
                         this, function( result ) {

                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)

                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );
        },

        */


        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:

            In this method, you associate each of your game notifications with your local method to handle it.

            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your zooloretto.game.php file.

        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );

            // TODO: here, associate your game notifications with local methods

            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );

            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            //

            dojo.subscribe( 'GoBackWagon', this, "notif_GoBackWagon" );
            this.notifqueue.setSynchronous( 'GoBackWagon', 1000 );
            dojo.subscribe( 'DrawTile', this, "notif_DrawTile" );
            this.notifqueue.setSynchronous( 'DrawTile', 1000 );
            dojo.subscribe( 'PlaceTile', this, "notif_PlaceTile" );
            this.notifqueue.setSynchronous( 'PlaceTile', 1000 );
            dojo.subscribe( 'TakeWagon', this, "notif_TakeWagon" );
            this.notifqueue.setSynchronous( 'TakeWagon', 1000 );
            dojo.subscribe( 'ArrangeTiles', this, "notif_ArrangeTiles" );
            this.notifqueue.setSynchronous( 'ArrangeTiles', 1000 );
            dojo.subscribe( 'AutoArrangeTiles', this, "notif_AutoArrangeTiles" );
            this.notifqueue.setSynchronous( 'AutoArrangeTiles', 1000 );
            dojo.subscribe( 'ConfirmArrangement', this, "notif_ConfirmArrangement" );
            this.notifqueue.setSynchronous( 'ConfirmArrangement', 1000 );
            dojo.subscribe( 'EndTurn', this, "notif_EndTurn" );
            this.notifqueue.setSynchronous( 'EndTurn', 1000 );
            dojo.subscribe( 'GetMoney', this, "notif_GetMoney" );
            this.notifqueue.setSynchronous( 'GetMoney', 1000 );
            dojo.subscribe( 'GotoStall', this, "notif_GotoStall" );
            this.notifqueue.setSynchronous( 'GotoStall', 1000 );
            dojo.subscribe( 'Babies', this, "notif_Babies" );
            this.notifqueue.setSynchronous( 'Babies', 1000 );
            dojo.subscribe( 'CoinsGained', this, "notif_CoinsGained" );
            this.notifqueue.setSynchronous( 'CoinsGained', 1000 );
            dojo.subscribe( 'Reset', this, "notif_Reset" );
            this.notifqueue.setSynchronous( 'Reset', 1000 );
            dojo.subscribe( 'BuyEnclosure', this, "notif_BuyEnclosure" );
            this.notifqueue.setSynchronous( 'BuyEnclosure', 1000 );
            dojo.subscribe( 'Move', this, "notif_Move" );
            this.notifqueue.setSynchronous( 'Move', 1000 );
            dojo.subscribe( 'Buy', this, "notif_Buy" );
            this.notifqueue.setSynchronous( 'Buy', 1000 );
            dojo.subscribe( 'ConfirmDiscard', this, "notif_ConfirmDiscard" );
            this.notifqueue.setSynchronous( 'ConfirmDiscard', 1000 );
            dojo.subscribe( 'SwapTiles', this, "notif_SwapTiles" );
            this.notifqueue.setSynchronous( 'SwapTiles', 1000 );
            dojo.subscribe( 'Score', this, "notif_Score" );
            this.notifqueue.setSynchronous( 'Score', 2000 );
            dojo.subscribe( 'LastRound', this, "notif_LastRound" );
            this.notifqueue.setSynchronous( 'LastRound', 1000 );
        },
        notif_LastRound: function( notif )
        {
            this.addLastRound(_('This is the last round...'));
        },
        notif_Score: function( notif )
        {
            var elements = document.getElementsByClassName('highlighted2');
            while(elements.length > 0)
            {
                dojo.removeClass(elements[0].id,'highlighted2');
            }

            this.scoreCtrl[ notif.args.player_id ].toValue( notif.args.player_score );
            if (notif.args.type=="1")
            {
                for (let i=1; i<=6; i++)
                {
                    if (document.getElementById('cell_'+notif.args.player_no+"_"+notif.args.enc+"_"+i)!=null)
                    {
                        if (document.getElementById('cell_'+notif.args.player_no+"_"+notif.args.enc+"_"+i).children.length==1)
                        {
                            dojo.addClass(document.getElementById('cell_'+notif.args.player_no+"_"+notif.args.enc+"_"+i).children[0].id,'highlighted2');
                        }
                    }
                }
            }
            else if (notif.args.type=="2")
            {
                for (let i=1; i<=6; i++)
                {
                    for (let j=1; j<=6; j++)
                    {
                        if (document.getElementById('cell_'+notif.args.player_no+"_"+i+"_"+j)!=null)
                        {
                            if (document.getElementById('cell_'+notif.args.player_no+"_"+i+"_"+j).children.length==1 &&
                                document.getElementById('cell_'+notif.args.player_no+"_"+i+"_"+j).children[0].className.search("Stall")>=0)
                            {
                                dojo.addClass(document.getElementById('cell_'+notif.args.player_no+"_"+i+"_"+j).children[0].id,'highlighted2');
                            }
                        }
                    }
                }
            }
            else if (notif.args.type=="3")
            {
                for (let i=0; i<document.getElementById('stall_' + notif.args.player_no).children.length; i++)
                {
                    if (document.getElementById('stall_' + notif.args.player_no).children[i].className.search(notif.args.key)>=0)
                    {
                        dojo.addClass(document.getElementById('stall_' + notif.args.player_no).children[i].id,'highlighted2');
                    }
                }
            }
        },
        notif_SwapTiles: function( notif )
        {
            var children = document.getElementById("money_" + notif.args.player_no).children;
            for (var i = 0; i < 1; i++)
            {
                var tableChild = children[i];
                this.fadeOutAndDestroy( tableChild.id, 1000, 0 );
            }

            for (var i=0; i<=5; i++)
            {
                if (notif.args.enc1!="0")
                {
                    if (this.Zoo[notif.args.player_no-1][notif.args.enc1-1][i]!="X") this.Zoo[notif.args.player_no-1][notif.args.enc1-1][i]="";
                }
                if (this.Zoo[notif.args.player_no-1][notif.args.enc2-1][i]!="X") this.Zoo[notif.args.player_no-1][notif.args.enc2-1][i]="";
            }

            var count = 0;
            for( var i in notif.args.tiles1 )
            {
                var tile1 = notif.args.tiles1[i];

                var newid = "";
                var cellid = ""
                count = count + 1;

                newid ='tile_' + notif.args.player_no + '_' + tile1.id + '_' + tile1.val +'_' + notif.args.enc2 + '_' + count;
                cellid = 'cell_' + notif.args.player_no + '_' + notif.args.enc2 + '_' + count;
                if (notif.args.enc1!="0")
                {
                    document.getElementById('tile_' + notif.args.player_no + '_' + tile1.id + '_' + tile1.val +'_' + tile1.x + '_' + tile1.y).id = newid;
                }
                else
                {
                    document.getElementById('tile_' + notif.args.player_no + '_' + tile1.id + '_' + tile1.val +'_0_0').id = newid;
                    dojo.removeClass(newid, 'stallsize');
                    dojo.addClass(newid, 'boardsize');
                }
                dojo.place(newid,cellid);
                var movesanim = new Array();
                if (notif.args.enc1!="0")
                {
                    movesanim.push(this.slideToObjectPos( newid, 'cell_' + notif.args.player_no + '_' + tile1.x + '_' + tile1.y , 0 , 0, 0));
                }
                else
                {
                    movesanim.push(this.slideToObjectPos( newid, 'stall_' + notif.args.player_no , 0 , 0, 0));
                }
                movesanim.push(this.slideToObjectPos2( newid, cellid , 0 , 0, 1000));
                fx.chain(movesanim).play();
                this.Zoo[notif.args.player_no-1][notif.args.enc2-1][count-1] = tile1.val + '_' + tile1.id + '_PL';
            }

            var count = 0;
            for( var i in notif.args.tiles2 )
            {
                var tile2 = notif.args.tiles2[i];

                var newid = "";
                var cellid = ""
                count = count + 1;
                var newid = '';
                var cellid = '';
                if (notif.args.enc1!="0")
                {
                    newid ='tile_' + notif.args.player_no + '_' + tile2.id + '_' + tile2.val +'_' + notif.args.enc1 + '_' + count;
                    cellid = 'cell_' + notif.args.player_no + '_' + notif.args.enc1 + '_' + count;
                    document.getElementById('tile_' + notif.args.player_no + '_' + tile2.id + '_' + tile2.val +'_' + tile2.x + '_' + tile2.y).id = newid;
                }
                else
                {
                    newid ='tile_' + notif.args.player_no + '_' + tile2.id + '_' + tile2.val +'_0_0';
                    cellid = 'stall_' + notif.args.player_no ;
                    document.getElementById('tile_' + notif.args.player_no + '_' + tile2.id + '_' + tile2.val +'_' + tile2.x + '_' + tile2.y).id = newid;
                    dojo.removeClass(newid, 'boardsize');
                    dojo.addClass(newid, 'stallsize');

                    dojo.query( '#' + newid).connect( 'onclick', this, 'onClickTile' );
                }

                dojo.place(newid,cellid);
                var movesanim = new Array();
                movesanim.push(this.slideToObjectPos( newid, 'cell_' + notif.args.player_no + '_' + tile2.x + '_' + tile2.y , 0 , 0, 0));
                if (notif.args.enc1!="0")
                {
                    movesanim.push(this.slideToObjectPos2( newid, cellid , 0 , 0, 1000));
                }
                else
                {
                    movesanim.push(this.slideToObjectPos2( newid, cellid , 0 , 0, 1000));
                }
                fx.chain(movesanim).play();
                if (notif.args.enc1!="0")
                {
                    this.Zoo[notif.args.player_no-1][notif.args.enc1-1][count-1] = tile2.val + '_' + tile2.id + '_PL';
                }
            }
        },
        notif_ConfirmDiscard: function( notif )
        {
            var children = document.getElementById("money_" + notif.args.player_no).children;
            for (var i = 0; i < 2; i++)
            {
                var tableChild = children[i];
                this.fadeOutAndDestroy( tableChild.id, 1000, 0 );
            }
            this.slideToObjectPos( 'tile_' + notif.args.player_no + '_' + notif.args.tileid + '_' + notif.args.val + '_0_0', 'overall_player_board_' + notif.args.player_id , 0 , 0, 500).play();
            this.fadeOutAndDestroy( 'tile_' + notif.args.player_no + '_' + notif.args.tileid + '_' + notif.args.val + '_0_0', 500, 500 );
        },
        notif_Buy: function( notif )
        {
            var children = document.getElementById("money_" + notif.args.player_no).children;
            for (var i = 0; i < 1; i++)
            {
                var tableChild = children[i];
                this.fadeOutAndDestroy( tableChild.id, 1000, 0 );
            }

            var monid = 'money_instance_' + notif.args.donor_player_no + '_' + notif.args.donor_money;
            document.getElementById("money_" + notif.args.player_no).children[1].id = monid;
            dojo.place(monid,'money_' + notif.args.donor_player_no);
            var movesanim2 = new Array();
            movesanim2.push(this.slideToObjectPos( monid, 'money_' + notif.args.player_no , 0 , 0, 0));
            movesanim2.push(this.slideToObjectPos2( monid, 'money_' + notif.args.donor_player_no , 0 , 0, 1000));
            fx.chain(movesanim2).play();

            var newid = 'tile_'+ notif.args.player_no +'_' + notif.args.tileid + '_' + notif.args.val +'_' + notif.args.x1 + '_' + notif.args.y1;
            var cellid = 'cell_' + notif.args.player_no  + '_' + notif.args.x1 + '_' + notif.args.y1;
            document.getElementById('tile_' + notif.args.donor_player_no + '_' + notif.args.tileid + '_' + notif.args.val + '_' + notif.args.x0 +'_' + notif.args.y0).id = newid;
            dojo.place(newid,cellid);
            dojo.removeClass(newid, 'stallsize');
            dojo.addClass(newid, 'boardsize');

            var movesanim = new Array();
            movesanim.push(this.slideToObjectPos( newid, 'stall_' + notif.args.donor_player_no , 0 , 0, 0));
            movesanim.push(this.slideToObjectPos( newid, cellid , 0 , 0, 1000));
            this.Zoo[notif.args.player_no-1][notif.args.x1-1][notif.args.y1-1] = notif.args.val + '_' + notif.args.tileid + '_PL';
            fx.chain(movesanim).play();
        },
        notif_Move: function( notif )
        {
            var children = document.getElementById("money_" + notif.args.player_no).children;
            for (var i = 0; i < 1; i++)
            {
                var tableChild = children[i];
                this.fadeOutAndDestroy( tableChild.id, 1000, 0 );
            }

            var newid = 'tile_'+ notif.args.player_no +'_' + notif.args.tileid + '_' + notif.args.val +'_' + notif.args.x1 + '_' + notif.args.y1;
            var cellid = 'cell_' + notif.args.player_no  + '_' + notif.args.x1 + '_' + notif.args.y1;
            document.getElementById('tile_' + notif.args.pid + '_' + notif.args.tileid + '_' + notif.args.val + '_' + notif.args.x0 +'_' + notif.args.y0).id = newid;
            dojo.place(newid,cellid);
            dojo.removeClass(newid, 'stallsize');
            dojo.addClass(newid, 'boardsize');

            var movesanim = new Array();
            if (notif.args.x0==0 && notif.args.y0==0)
            {
                movesanim.push(this.slideToObjectPos( newid, 'stall_' + notif.args.player_no , 0 , 0, 0));
            }
            else
            {
                movesanim.push(this.slideToObjectPos( newid, 'cell_' + notif.args.player_no + '_' + notif.args.x0 + '_' + notif.args.y0 , 0 , 0, 0));
            }
            movesanim.push(this.slideToObjectPos( newid, cellid , 0 , 0, 1000));
            if (notif.args.x0!=0 && notif.args.y0!=0)
            {
                this.Zoo[notif.args.player_no-1][notif.args.x0-1][notif.args.y0-1] = '';
            }
            this.Zoo[notif.args.player_no-1][notif.args.x1-1][notif.args.y1-1] = notif.args.val + '_' + notif.args.tileid + '_PL';
            fx.chain(movesanim).play();
        },
        notif_BuyEnclosure: function( notif )
        {

            var children = document.getElementById("money_" + notif.args.player_no).children;
            for (var i = 0; i < 3; i++)
            {
                var tableChild = children[i];
                this.fadeOutAndDestroy( tableChild.id, 1000, 0 );
            }

            this.UnblockedZoo[notif.args.player_no-1]=parseInt(this.UnblockedZoo[notif.args.player_no-1]) + 1;

            if (this.TotalPlayers==2)
            {
                dojo.removeClass('board_' + notif.args.player_no,'board2' + parseInt(parseInt(notif.args.unblockedzoo)-1));
                dojo.addClass('board_' + notif.args.player_no,'board2' + parseInt(notif.args.unblockedzoo));
                for (let i=parseInt(notif.args.player_no); i<=parseInt(notif.args.player_no); i++)
                {
                    for (let j=0; j<6; j++)
                    {
                        for (let k=0; k<6; k++)
                        {
                            if (parseInt(notif.args.unblockedzoo)==1)
                            {
                                if (j==3 && k<=4) this.Zoo[i-1][j][k] = "";
                                if (j==5 && k==4) this.Zoo[i-1][j][k] = "";
                            }
                            else if (parseInt(notif.args.unblockedzoo)==2)
                            {
                                if (j==4 && k<=4) this.Zoo[i-1][j][k] = "";
                                if (j==5 && k==5) this.Zoo[i-1][j][k] = "";
                            }
                        }
                    }
                }
            }
            else
            {
                dojo.removeClass('board_' + notif.args.player_no,'board' + parseInt(parseInt(notif.args.unblockedzoo)-1));
                dojo.addClass('board_' + notif.args.player_no,'board' + parseInt(notif.args.unblockedzoo));
                for (let i=parseInt(notif.args.player_no); i<=parseInt(notif.args.player_no); i++)
                {
                    for (let j=0; j<6; j++)
                    {
                        for (let k=0; k<6; k++)
                        {
                            if (parseInt(notif.args.unblockedzoo)==1)
                            {
                                if (j==3 && k<=4) this.Zoo[i-1][j][k] = "";
                                if (j==4 && k==4) this.Zoo[i-1][j][k] = "";
                            }
                        }
                    }
                }
            }
        },
        notif_Reset: function( notif )
        {
            var elements = document.getElementsByClassName('highlighted2');
            while(elements.length > 0)
            {
                dojo.removeClass(elements[0].id,'highlighted2');
            }

            var count = 0;
            for( var i in notif.args.thinkings )
            {
                var thinking = notif.args.thinkings[i];

                var newid = "";
                var cellid = ""
                count = count + 1;
                if (count == 1)
                {
                    newid ='tile_0_' + thinking.id + '_' + thinking.val +'_' + notif.args.wagonid + '_' + notif.args.pos1;
                    cellid = 'wagon_' + notif.args.wagonid + '_' + notif.args.pos1;
                }
                else if (count == 2)
                {
                    newid ='tile_0_' + thinking.id + '_' + thinking.val +'_' + notif.args.wagonid + '_' + notif.args.pos2;
                    cellid = 'wagon_' + notif.args.wagonid + '_' + notif.args.pos2;
                }
                else if (count == 3)
                {
                    newid ='tile_0_' + thinking.id + '_' + thinking.val +'_' + notif.args.wagonid + '_' + notif.args.pos3;
                    cellid = 'wagon_' + notif.args.wagonid + '_' + notif.args.pos3;
                }
                document.getElementById(thinking.thinking).id = newid;
                dojo.place(newid,cellid);
                dojo.addClass(newid, 'wagonsize');
                dojo.removeClass(newid, 'boardsize');
                dojo.addClass(newid, 'thinking');
                var movesanim = new Array();
                movesanim.push(this.slideToObjectPos( newid, 'cell_' + notif.args.player_no + '_' + thinking.x + '_' + thinking.y , 0 , 0, 0));
                movesanim.push(this.slideToObjectPos( newid, cellid , 0 , 0, 1000));
                fx.chain(movesanim).play();
                this.Zoo[notif.args.player_no-1][thinking.x-1][thinking.y-1] = '';
            }
        },
        notif_CoinsGained: function( notif )
        {
            for( let i=1; i<= parseInt(notif.args.coinsgained); i++)
            {
                this.addMoneyPlayer(parseInt(notif.args.coinsbefore) + i, notif.args.player_no);
                var movesanim = new Array();
                movesanim.push(this.slideToObjectPos( 'money_instance_'+notif.args.player_no+'_' + parseInt(parseInt(notif.args.coinsbefore) + i), 'cell_'+notif.args.player_no+'_' + notif.args.enclosure + '_1' , 0 , 0, 0));
                movesanim.push(this.slideToObjectPos2( 'money_instance_'+notif.args.player_no+'_' + parseInt(parseInt(notif.args.coinsbefore) + i), 'money_' + notif.args.player_no , 0 , 0, 1000));
                fx.chain(movesanim).play();
            }
        },
        notif_Babies: function( notif )
        {
            for( var i in notif.args.kidsstall )
            {
                var kidstall = notif.args.kidsstall[i];
                this.addTile( 'stall_' + notif.args.player_no,notif.args.player_no, kidstall.id, kidstall.val, kidstall.x, kidstall.y );

                var newid = 'tile_'+ notif.args.player_no +'_' + kidstall.id + '_' + kidstall.val +'_' + kidstall.x + '_' + kidstall.y;
                var cellid = 'stall_' + notif.args.player_no;
                dojo.place(newid,cellid);
                dojo.removeClass(newid, 'wagonsize');
                dojo.removeClass(newid, 'boardsize');
                dojo.removeClass(newid, 'thinking');
                dojo.addClass(newid, 'stallsize');
                var movesanim = new Array();
                movesanim.push(this.slideToObjectPos( newid, 'overall_player_board_' + notif.args.player_id , 0 , 0, 0));
                movesanim.push(this.slideToObjectPos2( newid, cellid , 0 , 0, 1000));
                fx.chain(movesanim).play();
                dojo.query( '#' + newid).connect( 'onclick', this, 'onClickTile' );
            }


            for( var i in notif.args.kids )
            {
                var kid = notif.args.kids[i];
                this.addTile( 'cell_' + notif.args.player_no + '_' + kid.x + '_' + kid.y,notif.args.player_no, kid.id, kid.val, kid.x, kid.y );

                var newid = 'tile_'+ notif.args.player_no +'_' + kid.id + '_' + kid.val +'_' + kid.x + '_' + kid.y;
                var cellid = 'cell_' + notif.args.player_no + '_' + kid.x + '_' + kid.y;
                dojo.place(newid,cellid);
                dojo.removeClass(newid, 'wagonsize');
                dojo.removeClass(newid, 'stallsize');
                dojo.removeClass(newid, 'thinking');
                dojo.addClass(newid, 'boardsize');
                var movesanim = new Array();
                movesanim.push(this.slideToObjectPos( newid, 'overall_player_board_' + notif.args.player_id , 0 , 0, 0));
                movesanim.push(this.slideToObjectPos( newid, cellid , 0 , 0, 1000));
                fx.chain(movesanim).play();

                this.Zoo[notif.args.player_no-1][kid.x-1][kid.y-1] = kid.val + '_' + kid.id + '_TH';
            }

            for( var i in notif.args.newparents )
            {
                var newparent = notif.args.newparents[i];
                if (document.getElementById(newparent.oldparenttile)!=null)
                {
                    document.getElementById(newparent.oldparenttile).id = newparent.parenttile
                    this.Zoo[newparent.player_no-1][newparent.x-1][newparent.y-1] = newparent.val + '_' + newparent.id + '_' + this.Zoo[newparent.player_no-1][newparent.x-1][newparent.y-1].split('_')[2];
                }
            }
        },
        notif_GotoStall: function( notif )
        {
            for( var i in notif.args.stalltiles )
            {
                var stalltile = notif.args.stalltiles[i];
                var newid = 'tile_'+ notif.args.player_no +'_' + stalltile.id + '_' + stalltile.val +'_0_0';
                var cellid = 'stall_' + notif.args.player_no;
                document.getElementById(stalltile.stalltiles).id = newid;
                dojo.place(newid,cellid);
                dojo.removeClass(newid, 'wagonsize');
                dojo.removeClass(newid, 'boardsize');
                dojo.removeClass(newid, 'thinking');
                dojo.addClass(newid, 'stallsize');
                var movesanim = new Array();
                movesanim.push(this.slideToObjectPos( newid, 'wagon_' + stalltile.x + '_' + stalltile.y , 0 , 0, 0));
                movesanim.push(this.slideToObjectPos2( newid, cellid , 0 , 0, 1000));
                fx.chain(movesanim).play();

                dojo.query( '#' + newid).connect( 'onclick', this, 'onClickTile' );
            }
        },
        notif_GetMoney: function( notif )
        {
            for( var i in notif.args.cointiles )
            {
                var cointile = notif.args.cointiles[i];
                dojo.removeClass(cointile.cointiles,'thinking');
                this.slideToObjectPos( cointile.cointiles, 'overall_player_board_' + notif.args.player_id , 0 , 0, 500).play();
                this.fadeOutAndDestroy( cointile.cointiles, 500, 500 );
                this.addMoneyPlayer(cointile.id, notif.args.player_no);
            }
        },
        notif_EndTurn: function( notif )
        {
            for (let i=1;i<=5;i++)
            {
                if (document.getElementById('wagon_'+i)!=null)
                {
                    dojo.destroy(document.getElementById('wagon_'+i));
                }
            }
            for( var i in notif.args.wagons )
            {
                var wagon = notif.args.wagons[i];
                this.addWagon( wagon.id, wagon.size);
            }

            for (let i=0; i<this.Wagons.length; i++)
            {
                if (this.Wagons[i][0]!="X") this.Wagons[i][0] = "";
                if (this.Wagons[i][1]!="X") this.Wagons[i][1] = "";
                if (this.Wagons[i][2]!="X") this.Wagons[i][2] = "";
            }

            dojo.query( '.cellwagon1' ).connect( 'onclick', this, 'onClickCellWagon' );
            dojo.query( '.cellwagon2' ).connect( 'onclick', this, 'onClickCellWagon' );
            dojo.query( '.cellwagon3' ).connect( 'onclick', this, 'onClickCellWagon' );
        },
        notif_ConfirmArrangement: function( notif )
        {
            var elements = document.getElementsByClassName('thinking');
            while(elements.length > 0)
            {
                dojo.removeClass(elements[0].id,'thinking');
            }
            var elements = document.getElementsByClassName('pointer');
            while(elements.length > 0)
            {
                dojo.removeClass(elements[0].id,'pointer');
            }
            var elements = document.getElementsByClassName('highlighted');
            while(elements.length > 0)
            {
                dojo.removeClass(elements[0].id,'highlighted');
            }
            var elements = document.getElementsByClassName('highlighted2');
            while(elements.length > 0)
            {
                dojo.removeClass(elements[0].id,'highlighted2');
            }
            for (let i=0; i<this.Zoo.length; i++)
            {
                for (let j=0; j<this.Zoo[i].length; j++)
                {
                    for (let k=0; k<this.Zoo[i][j].length; k++)
                    {
                        if (this.Zoo[i][j][k]!="" && this.Zoo[i][j][k]!="X" && this.Zoo[i][j][k].split('_')[2]=="TH")
                        {
                            this.Zoo[i][j][k] = this.Zoo[i][j][k].replace('TH','PL');
                        }
                    }
                }
            }
            if (this.Wagons[notif.args.wagonid-1][0]!="X") this.Wagons[notif.args.wagonid-1][0] = "PL";
            if (this.Wagons[notif.args.wagonid-1][1]!="X") this.Wagons[notif.args.wagonid-1][1] = "PL";
            if (this.Wagons[notif.args.wagonid-1][2]!="X") this.Wagons[notif.args.wagonid-1][2] = "PL";
            this.fadeOutAndDestroy( 'wagon_'+notif.args.wagonid, 1000, 100 );
        },
        notif_AutoArrangeTiles: function( notif )
        {
            if (notif.args.tileid1!="")
            {
                var newid = 'tile_'+ notif.args.player_no +'_' + notif.args.tileid1 + '_' + notif.args.val1 +'_' + notif.args.x1 + '_' + notif.args.y1;
                var cellid = 'cell_' + notif.args.player_no  + '_' + notif.args.x1 + '_' + notif.args.y1;
                document.getElementById('tile_0_' + notif.args.tileid1 + '_' + notif.args.val1 + '_' + notif.args.wagonid +'_' + notif.args.posid1).id = newid;
                dojo.place(newid,cellid);
                dojo.removeClass(newid, 'wagonsize');
                dojo.addClass(newid, 'boardsize');
                dojo.addClass(newid, 'thinking');
                var movesanim = new Array();
                movesanim.push(this.slideToObjectPos( newid, 'wagon_' + notif.args.wagonid + '_' + notif.args.posid1 , 0 , 0, 0));
                movesanim.push(this.slideToObjectPos( newid, cellid , 0 , 0, 1000));
                this.Zoo[notif.args.player_no-1][notif.args.x1-1][notif.args.y1-1] = notif.args.val1 + '_' + notif.args.tileid1 + '_TH';
                fx.chain(movesanim).play();
            }

            if (notif.args.tileid2!="")
            {
                var newid = 'tile_'+ notif.args.player_no +'_' + notif.args.tileid2 + '_' + notif.args.val2 +'_' + notif.args.x2 + '_' + notif.args.y2;
                var cellid = 'cell_' + notif.args.player_no  + '_' + notif.args.x2 + '_' + notif.args.y2;
                document.getElementById('tile_0_' + notif.args.tileid2 + '_' + notif.args.val2 + '_' + notif.args.wagonid +'_' + notif.args.posid2).id = newid;
                dojo.place(newid,cellid);
                dojo.removeClass(newid, 'wagonsize');
                dojo.addClass(newid, 'boardsize');
                dojo.addClass(newid, 'thinking');
                var movesanim = new Array();
                movesanim.push(this.slideToObjectPos( newid, 'wagon_' + notif.args.wagonid + '_' + notif.args.posid2 , 0 , 0, 0));
                movesanim.push(this.slideToObjectPos( newid, cellid , 0 , 0, 1000));
                this.Zoo[notif.args.player_no-1][notif.args.x2-1][notif.args.y2-1] = notif.args.val2 + '_' + notif.args.tileid2 + '_TH';
                fx.chain(movesanim).play();
            }

            if (notif.args.tileid3!="")
            {
                var newid = 'tile_'+ notif.args.player_no +'_' + notif.args.tileid3 + '_' + notif.args.val3 +'_' + notif.args.x3 + '_' + notif.args.y3;
                var cellid = 'cell_' + notif.args.player_no  + '_' + notif.args.x3 + '_' + notif.args.y3;
                document.getElementById('tile_0_' + notif.args.tileid3 + '_' + notif.args.val3 + '_' + notif.args.wagonid +'_' + notif.args.posid3).id = newid;
                dojo.place(newid,cellid);
                dojo.removeClass(newid, 'wagonsize');
                dojo.addClass(newid, 'boardsize');
                dojo.addClass(newid, 'thinking');
                var movesanim = new Array();
                movesanim.push(this.slideToObjectPos( newid, 'wagon_' + notif.args.wagonid + '_' + notif.args.posid3 , 0 , 0, 0));
                movesanim.push(this.slideToObjectPos( newid, cellid , 0 , 0, 1000));
                this.Zoo[notif.args.player_no-1][notif.args.x3-1][notif.args.y3-1] = notif.args.val3 + '_' + notif.args.tileid3 + '_TH';
                fx.chain(movesanim).play();
            }
        },

        notif_ArrangeTiles: function( notif )
        {
            var newid = 'tile_'+ notif.args.player_no +'_' + notif.args.tileid + '_' + notif.args.val +'_' + notif.args.x + '_' + notif.args.y;
            var cellid = 'cell_' + notif.args.player_no  + '_' + notif.args.x + '_' + notif.args.y;
            document.getElementById('tile_' + notif.args.pid + '_' + notif.args.tileid + '_' + notif.args.val + '_' + notif.args.wagonid +'_' + notif.args.posid).id = newid;
            dojo.place(newid,cellid);
            dojo.removeClass(newid, 'wagonsize');
            dojo.addClass(newid, 'boardsize');
            dojo.addClass(newid, 'thinking');
            var movesanim = new Array();
            if (notif.args.pid==0)
            {
                movesanim.push(this.slideToObjectPos( newid, 'wagon_' + notif.args.wagonid + '_' + notif.args.posid , 0 , 0, 0));
            }
            else
            {
                movesanim.push(this.slideToObjectPos( newid, 'cell_' + notif.args.player_no + '_' + notif.args.wagonid + '_' + notif.args.posid , 0 , 0, 0));
            }
            movesanim.push(this.slideToObjectPos( newid, cellid , 0 , 0, 1000));
            if (notif.args.pid!=0)
            {
                this.Zoo[notif.args.player_no-1][notif.args.wagonid-1][notif.args.posid-1] = '';
            }
            this.Zoo[notif.args.player_no-1][notif.args.x-1][notif.args.y-1] = notif.args.val + '_' + notif.args.tileid + '_TH';
            fx.chain(movesanim).play();
        },
        notif_GoBackWagon: function( notif )
        {
            dojo.removeClass('wagon_' + notif.args.x, 'highlighted');

            for( var i in notif.args.wagontiles )
            {
                var wagontile = notif.args.wagontiles[i];
                dojo.removeClass( wagontile.wagontile, 'thinking');
            }
            if (document.getElementById("playername_" + notif.args.player_no)!=null)
            {
                document.getElementById("playername_" + notif.args.player_no).innerHTML = document.getElementById("playername_" + notif.args.player_no).innerHTML.replace(_(" - Took the wagon") + '</p>','</p>');
            }
        },
        notif_TakeWagon: function( notif )
        {
            dojo.addClass('wagon_' + notif.args.x, 'highlighted');

            for( var i in notif.args.wagontiles )
            {
                var wagontile = notif.args.wagontiles[i];
                dojo.addClass( wagontile.wagontile, 'thinking');
            }
            if (document.getElementById("playername_" + notif.args.player_no)!=null)
            {
                document.getElementById("playername_" + notif.args.player_no).innerHTML = document.getElementById("playername_" + notif.args.player_no).innerHTML.replace('</p>', _(" - Took the wagon") + '</p>');
            }
        },
        notif_PlaceTile: function( notif )
        {
            var newid = 'tile_0_' + notif.args.id + '_' + notif.args.val + '_' + notif.args.x + '_' + notif.args.y;
            var wagonid = 'wagon_' + notif.args.x + '_' + notif.args.y;
            document.getElementById('tile_0_' + notif.args.id + '_' + notif.args.val + '_0_0').id = newid;
            dojo.place(newid,wagonid);
            dojo.addClass(newid, 'wagonsize');
            dojo.removeClass(newid, 'backtransition');
            var movesanim = new Array();
            movesanim.push(this.slideToObjectPos( newid, 'tiles' , 0 , 0, 0));
            movesanim.push(this.slideToObjectPos( newid, wagonid , 0 , 0, 1000));
            this.Wagons[notif.args.x-1][notif.args.y-1] = notif.args.id;
            fx.chain(movesanim).play();
        },
        notif_DrawTile: function( notif )
        {
            var elements = document.getElementsByClassName('highlighted');
            while(elements.length > 0)
            {
                dojo.removeClass(elements[0].id,'highlighted');
            }

            var found = false;
            var count = 6;
            var tiledrawn = "";
            var newid = 'tile_'+0+'_'+notif.args.id+'_'+notif.args.val+'_0_0';

            while (!found && count>0)
            {
                count = count -1
                if (document.getElementById("backtile_" + count)!=null)
                {
                    tiledrawn = "backtile_" + count;
                    found = true;
                }
            }
            if (found)
            {
                document.getElementById(tiledrawn).id = newid;
                if (parseInt(notif.args.tilesleft)>=5)
                {
                    this.addBack(5);
                }

                if (this.paramvalue=="2")
                {
                    if (document.getElementById('tilesleft')!=null) dojo.destroy('tilesleft');
                    this.addTilesLeft(count+2,notif.args.tilesleft);
                }
            }
            if (!found)
            {
                if (document.getElementById('tilesleft')!=null) dojo.destroy('tilesleft');
                var count = 6;
                while (!found && count>0)
                {
                    count = count -1
                    if (document.getElementById("backtile2_" + count)!=null)
                    {
                        tiledrawn = "backtile2_" + count;
                        found = true;
                    }
                }
                if (found)
                {
                    if (document.getElementById('disk')!=null)
                    {
                        this.fadeOutAndDestroy( 'disk', 1000, 0 );
                    }
                    document.getElementById(tiledrawn).id = newid;
                    if (parseInt(notif.args.tilesleft)>=5)
                    {
                        this.addBack2(5);
                    }
                }
            }

            dojo.place(newid, 'tiles');
            dojo.addClass(newid,'tile');
            dojo.addClass(newid,'tile' + notif.args.val);
            dojo.addClass(newid,'backtransition');
            dojo.removeClass(newid,'back');

            // this.addTile( 'tiles',0, notif.args.id, notif.args.val, 0, 0 );

            var movesanim = new Array();
            //movesanim.push(this.slideToObjectPos( 'tile_0_' + notif.args.id + '_' + notif.args.val + '_0_0', 'overall_player_board_' + notif.args.player_id , 0 , 0, 0));
            movesanim.push(this.slideToObjectPos( 'tile_0_' + notif.args.id + '_' + notif.args.val + '_0_0', 'tilesdeck' , 0 , 0, 0));
            movesanim.push(this.slideToObjectPos( 'tile_0_' + notif.args.id + '_' + notif.args.val + '_0_0', 'tiles' , 0 , 0, 1000));

            fx.chain(movesanim).play();
        },
        // TODO: from this point and below, you can write your game notifications handling methods

        /*
        Example:

        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );

            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call

            // TODO: play the card in the user interface.
        },

        */
   });
});
