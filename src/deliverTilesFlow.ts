import { ZooFlow } from "./zflow";
import { Destination } from "./zgametypes";
import { Attrs, Elements, encOf, posOf } from "./zhtml";

interface PossibleDelivery {
    truck_pos: number;
    /*
    tile: string;
    tile_id: number;
    */
    dests: Destination[];
}

interface DeliverTilesArgs {
  truck_id: number;
  possible_deliveries: PossibleDelivery[];
}

export class DeliverTilesFlow extends ZooFlow<DeliverTilesArgs> {
  protected async start(args: DeliverTilesArgs) {
    const restart = {
      restart: async () => this.bga.actions.performAction('actUndo', {}),
      post: async () =>  Elements.truck(args.truck_id).removeAttribute(Attrs.MARK)
    };
    if (!args.possible_deliveries || args.possible_deliveries.length == 0) {
      this.initStatusBar(_('Confirm your truck tile deliveries'));
      this.addConfirmAndRestartActionButtons('actConfirmDelivery', {}, restart);
    }
    else {
      this.initStatusBar(_('Choose a tile to deliver from the selected truck'));
      args.possible_deliveries.forEach((pp: PossibleDelivery) => {
        const elem = Elements.truckSpace(args.truck_id, pp.truck_pos);
        this.addSelectableOnclick(elem, async () => {
          this.callUndoably("chooseDest", () => this.chooseDestination(pp, args.truck_id));
        });
      });
      this.addRestartAndUndoButtons(restart);
    }
  }

  private async chooseDestination(pp: PossibleDelivery, truck_id: number) {
    this.initStatusBar(_('Choose a destination for the selected tile'));

    pp.dests.forEach((dest: Destination) => {
      const encElem = Elements.enclosureSpace(this.player_id, dest.space);
      this.addSelectableOnclick(encElem, async (evt:MouseEvent) => {
        const tileElem = Elements.truckSpace(truck_id, pp.truck_pos).firstElementChild as HTMLElement;
        await this.slide(tileElem,encElem).then(async () => {
          this.offspringSlide(dest.offspring).then(async () => {
            this.updateMoneyDelta(dest.money_delta);
            await this.bga.actions.performAction('actDeliverTile', { truck_pos: pp.truck_pos, enclosure_id: encOf(dest.space), enclosure_pos: posOf(dest.space), confirm_if_done: false })
          });
        });
      });
    });
    this.addRestartAndUndoButtons({
      restart: async () => this.bga.actions.performAction('actUndo', {})});
  }
}
