class Html {
  public static makeElem(ty: string, args: {id?: string, text?: string, attrs?: Record<string, string>, classes?: (string | string[]), style?: (string | string[])}, ...children: (HTMLElement | undefined) []): HTMLElement  {
    let e = document.createElement(ty);
    if (args.id) { e.id = args.id; }
    if (args.classes) {
      if (typeof(args.classes) == "string") { args.classes = [args.classes]; }
      args.classes.forEach(c => { if (c) e.classList.add(c) });
    }
    if (args.style) {
      if (typeof(args.style) == "string") { args.style = [args.style]; }
      e.style = args.style.join(';');
    }
    if (args.text) {
      e.innerText = args.text;
    }
    if (args.attrs) {
      Object.keys(args.attrs).forEach(k => e.setAttribute(k, args.attrs![k]!));
    }
    children.forEach(c => c && e.appendChild(c));
    return e;
  }

  public static div(args: {id?: string, text?: string, classes?: (string | string[])}, ...children: (HTMLElement | undefined) []) {
    return this.makeElem('div', args, ...children);
  }

  public static span(args: {id?: string, text?: string, attrs?: Record<string, string>, classes?: (string | string[]), style?: (string | string[])}, ...children: (HTMLElement | undefined) []) {
    return this.makeElem('span', args, ...children);
  }

  public static p(args: {id?: string, text?: string, attrs?: Record<string, string>, classes?: (string | string[]), style?: (string | string[])}, ...children: (HTMLElement | undefined) []) {
      return this.makeElem('p', args, ...children);
  }
}
