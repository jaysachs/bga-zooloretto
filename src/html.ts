/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Zooloretto implementation : © Jay Sachs <vagabond@covariant.org>
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

export interface AttrLike {
    toRecord(): Record<string, string>;
}

export class Html {
  public static makeElem(ty: string, args: {id?: string, text?: string, title?: string, attrs?: Record<string, string> | AttrLike , classes?: (string | string[]), style?: (string | string[])}, ...children: (HTMLElement | undefined) []): HTMLElement  {
    const e = document.createElement(ty);
    if (args.id) { e.id = args.id; }
    if (args.classes) {
      if (typeof(args.classes) == "string") { args.classes = [args.classes]; }
      args.classes.forEach(c => { if (c) e.classList.add(c) });
    }
    if (args.title) {
        e.title = args.title;
    }
    if (args.style) {
      if (typeof(args.style) == "string") { args.style = [args.style]; }
      e.style = args.style.join(';');
    }
    if (args.text) {
      e.innerText = args.text;
    }
    if (args.attrs) {
      var r: Record<string,string>;
      if (typeof ((args.attrs) as any).toRecord == "function") {
        r = (args.attrs as AttrLike).toRecord();
      } else {
        r = args.attrs as Record<string,string>;
      }
      Object.keys(r).forEach(k => e.setAttribute(k, r![k]!));
    }
    children.forEach(c => c && e.appendChild(c));
    return e;
  }

  public static div(args: {id?: string, text?: string, title?: string, attrs?: Record<string, string> | AttrLike, classes?: (string | string[])}, ...children: (HTMLElement | undefined) []) {
    return this.makeElem('div', args, ...children);
  }

  public static span(args: {id?: string, text?: string, title?: string, attrs?: Record<string, string> | AttrLike, classes?: (string | string[]), style?: (string | string[])}, ...children: (HTMLElement | undefined) []) {
    return this.makeElem('span', args, ...children);
  }

  public static p(args: {id?: string, text?: string, title?: string, attrs?: Record<string, string> | AttrLike, classes?: (string | string[]), style?: (string | string[])}, ...children: (HTMLElement | undefined) []) {
      return this.makeElem('p', args, ...children);
  }
}
