//
// game-specific HTML structures
//

class Attrs implements AttrLike {
  toRecord(): Record<string, string> {
    return this.r;
  }
  private r: any = {};

  static readonly EXTENSIONS ='zoo-extensions';
  static readonly TILE ='zoo-tile';
  static readonly ENCLOSURE ='zoo-enclosure';
  static enclosure(enc : number): Attrs {
    return new Attrs().enclosure(enc);
  }
  enclosure(enc : number): Attrs {
    this.r[Attrs.ENCLOSURE] = "" + enc;
    return this;
  }
  static extensions(ext: number): Attrs {
    return new Attrs().extensions(ext);
  }
  extensions(ext: number): Attrs {
    this.r[Attrs.EXTENSIONS] = ""+ext;
    return this;
  }

  static tile(tile_type: string) : Attrs {
    return new Attrs().tile(tile_type);
  }
  tile(tile_type: string): Attrs {
    this.r[Attrs.TILE] = tile_type;
    return this;
  }
}

class IDS {
  static readonly GAME = 'zoo-game'; // top-level element
  static readonly PRIMARY_PILE_TILES = 'zoo-primary-pile-tiles';
  static readonly ENDGAME_PILE_TILES = 'zoo-endgame-pile-tiles';
  static readonly PRIMARY_PILE_COUNT = 'zoo-primary-pile-count';
  static readonly ENDGAME_PILE_COUNT = 'zoo-endgame-pile-count';
  static readonly OFF_BOARD = 'overall-footer';
  static readonly BANK_MONEY = 'zoo-bank-money';
  static readonly DISK = 'zoo-disk';
  static readonly SCORE_SHEET = 'zoo-score-sheet';

  static depotSpace(truck_id: number) { return `zoo-depot-space-${truck_id}`}
  static truck(id : number) { return `truck-${id}`; }
  static truckSpace(truck_id : number, pos: number) { return `truckspace-${truck_id}-${pos}`; }
  static enclosure(player_id: number, enclosure_id: number): string { return `enclosure-${player_id}-${enclosure_id}`; }
  static enclosureSpace(player_id: number, enclosure_id: number, pos: number): string { return `enclosure-${player_id}-${enclosure_id}-${pos}`; }
  static takenTruck(player_id: number): string { return `zoo-taken-truck-${player_id}`; }
  static money(player_id: number): string { return `playermoney-counter-${player_id}` };
  static boardId(player_id: number): string { return `zoo-board-${player_id}`; }
  static tile(t : Tile): string { return `zoo-tile-${t.id}`; }
}

class CSS {
  static readonly TRUCK = 'zoo-truck';
  static readonly TARGETABLE = 'zoo-targetable';
  static readonly SELECTABLE = 'zoo-selectable';
  static readonly SELECTED = 'zoo-selected';
  static readonly MOVED = 'zoo-moved';
  static readonly DEPOT_SPACE = 'zoo-depot-space';
  static readonly PILE = 'zoo-pile';
  static readonly PARENT = 'zoo-parent';
}

class Elements {
  static tile(tile: Tile): HTMLElement | undefined {
    return $(IDS.tile(tile));
  }

  static drawnTile(endgame: boolean): HTMLElement {
    return $(endgame ? IDS.ENDGAME_PILE_TILES : IDS.PRIMARY_PILE_TILES).lastElementChild as HTMLElement;
  }

  static truck(truck_id: number) : HTMLElement {
    return $(IDS.truck(truck_id));
  }

  static truckSpace(truck_id: number, truck_pos: number) : HTMLElement{
    return $(IDS.truckSpace(truck_id, truck_pos))
  }

  static truckTile(truck_id: number, truck_pos: number) : (HTMLElement | undefined) {
    return this.truckSpace(truck_id, truck_pos).firstChild as (HTMLElement | undefined);
  }

  static enclosureSpace(player_id: number, space: Space) : HTMLElement {
    return $(IDS.enclosureSpace(player_id, space.enclosure_id, space.pos));
  }

  static enclosureTile(player_id: number, space: Space) : HTMLElement | undefined {
    return this.enclosureSpace(player_id, space).firstElementChild as HTMLElement;
  }

}

class ZoolorettoHtml {
  constructor(gamedatas: ZGamedatas, player_id: number) {
    this.gamedatas = gamedatas;
    this.player_id = player_id;
    this.twoPlayer = Object.keys(gamedatas.players).length == 2;
  }
  private readonly gamedatas: ZGamedatas;
  private readonly player_id: number;
  private readonly twoPlayer: boolean;

  public static range(start: number, end: number) {
    return Array.from({length: (end - start + 1)}, (v, k) => k + start);
  }

  private playerBoardDiv(player?: ZPlayer): HTMLElement | undefined {
    if (!player) { return undefined; }

    let enclosure = (e: number, n: number): HTMLElement => {
      return Html.div({id: IDS.enclosure(player.player_id, e), attrs: Attrs.enclosure(e) },
        ... ZoolorettoHtml.range(1, n).map(i => Html.div({ id: IDS.enclosureSpace(player.player_id, e, i), classes: "zoo-cell"} ))
      );
    };

    return Html
      .div({ id: `zoo-playerboard-${player.player_id}`, classes: [ "zoo-playerboard"] },
        Html.div({ id: `zoo-playername-${player.player_id}`},
          Html.span({ text: player.name, style: `color: #${player.color}`, classes: ["player-name","whiteblock","zoo-playername"]})
        ),
        Html.div({ id: IDS.boardId(player.player_id), classes: [ 'zoo-board' ], attrs: Attrs.extensions(player.purchased_extensions)},
          enclosure(0, 20), // the barn
          enclosure(1, 6),
          enclosure(2, 6),
          enclosure(3, 7),
          enclosure(4, 6),
          this.twoPlayer ? enclosure(5, 6) : undefined
        )
      );
  }

  baseStructure(): HTMLElement {
    let currentPlayer = this.gamedatas.players[this.player_id];
    let otherplayers = Object.values(this.gamedatas.players).filter((p) => p != currentPlayer);

    return Html
      .div({id: IDS.GAME, classes: this.twoPlayer ? 'zoo-2p' : ''},
        Html.div({id: 'zoo-boards' },
          this.playerBoardDiv(currentPlayer),
          ... otherplayers.map((p) => this.playerBoardDiv(p))
        ),
        Html.div({ id: 'zoo-shared-container' },
          Html.div({ id: 'zoo-stock-and-bank' },
            Html.div({ id: 'zoo-primary-pile' },
              Html.div({ id: IDS.PRIMARY_PILE_COUNT, text: "??" }),
              Html.div({ id: IDS.PRIMARY_PILE_TILES, classes: CSS.PILE }),
            ),
            Html.div({ id: 'zoo-endgame-pile' },
              Html.div({ id: IDS.ENDGAME_PILE_COUNT }),
              Html.div({ id: IDS.ENDGAME_PILE_TILES, classes: CSS.PILE }),
            ),
            Html.div({ id: 'zoo-bank' },
              Html.div({ id: IDS.BANK_MONEY, text: '27' })
            )
          ),
          ... this.gamedatas.trucks.map(truck =>
            Html.div({id: IDS.depotSpace(truck.truck_id), classes: CSS.DEPOT_SPACE },
              Html.div({ id: IDS.truck(truck.truck_id), classes: CSS.TRUCK },
                ... truck.contents.map((contents, i) =>
                Html.div({ id: IDS.truckSpace(truck.truck_id, contents.pos) })
                )
              )
            )
          )
        ),
        // Html.div({id: 'zoo-playeraid' }),
        Html.div({id: IDS.SCORE_SHEET})
    );
  }

  playerPanel(player: ZPlayer): HTMLElement {
    const playerId = player.player_id;
    console.log('Setting up panel for player ' + player.player_id);
    return Html
      .div({ classes: 'zoo-player-panel-ext'},
        Html.span({ classes: 'zoo-money'},
          Html.span({classes: 'zoo-money-label'}),
          Html.span({text: ': '}),
          Html.span({id: IDS.money(playerId)})),
        Html.div({ classes: CSS.DEPOT_SPACE, id: IDS.takenTruck(playerId)}),
      );
  }
}
