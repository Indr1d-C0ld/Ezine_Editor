/* render.js – rendering condiviso del giornale.
 *
 * Usato da editor (index.html), archivio (archivio.php), esportazione HTML e
 * stampa. Prima la stessa logica esisteva in due copie, da modificare sempre
 * insieme: ogni differenza fra editor e archivio nasceva lì.
 *
 * Il testo degli articoli accetta Markdown essenziale (**grassetto**,
 * *corsivo*, [link](https://...), riga vuota = nuovo paragrafo) e, per
 * compatibilità con i contenuti già scritti, anche HTML.
 */
(function (global) {
  'use strict';

  const SCHEMA = 2;

  // Valori neutri: la testata reale vive nelle impostazioni salvate sul server.
  const DEFAULT_MASTHEAD = {
    nameA: 'LA MIA',
    nameB: 'EZINE',
    fullName: 'La Mia Ezine',
    motto: 'Il tuo giornale, a modo tuo',
    subhead: 'INDIPENDENTE • LIBERO • PERSONALE',
    contact: '',
    price: 'Libero',
    disclaimer: '{nome} non si assume responsabilità per le allucinazioni uditive e visive derivanti dalla lettura ad alta voce di questo foglio. Vietato fotocopiare per fini commerciali – incoraggiato fotocopiare per fini sovversivi.',
    logo: '',
    exportName: 'ezine',
    sections: {
      straight: '✊ STRAIGHT FROM THE MAN',
      roundup: '🌍 ROUNDUP',
      letters: '📬 LETTERE',
      fight: '⚡ FIGHT THE POWER'
    }
  };

  // ---------- dati ----------

  function masthead(m) {
    const out = Object.assign({}, DEFAULT_MASTHEAD, m || {});
    out.sections = Object.assign({}, DEFAULT_MASTHEAD.sections, (m && m.sections) || {});
    return out;
  }

  // Completa un contenuto con tutte le sezioni previste. Un'uscita salvata da una
  // versione precedente (o inviata a mano all'API) può non averle: senza questo
  // passaggio il render lancia un TypeError e non mostra nulla.
  function normalize(c) {
    const d = Object.assign({ fullWidth: null, nextIssue: '', fixedRubric: '' }, c || {});
    d.header = Object.assign({ anno: '', numero: '', data: '', titleColor: '#8b1f1f' }, d.header);
    d.straightFromTheMan = Object.assign({ text: '' }, d.straightFromTheMan);
    d.fakeAd = Object.assign({ text: '', enabled: false, colored: false }, d.fakeAd);
    for (const k of ['colLeft', 'colRight', 'roundup', 'letters', 'fight']) {
      if (!Array.isArray(d[k])) d[k] = [];
    }
    d.pages = (Array.isArray(d.pages) ? d.pages : []).map(p => ({
      title: (p && p.title) || '',
      columns: [1, 2, 3].includes(Number(p && p.columns)) ? Number(p.columns) : 2,
      articles: Array.isArray(p && p.articles) ? p.articles : []
    }));
    d.v = SCHEMA;
    return d;
  }

  const clone = o => JSON.parse(JSON.stringify(o));

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  // ---------- Markdown essenziale ----------

  function inlineMd(s) {
    // I link vengono messi da parte prima delle enfasi: un "_" dentro un URL non
    // deve diventare corsivo.
    const link = [];
    s = s.replace(/\[([^\]]+)\]\(((?:https?:\/\/|mailto:)[^\s)]+)\)/g, (_, t, u) => {
      link.push(`<a href="${esc(u)}">${t}</a>`);
      return `\u0000${link.length - 1}\u0000`;
    });
    s = s
      .replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>')
      .replace(/(^|[^\w*])\*([^*\n]+)\*(?![\w*])/g, '$1<em>$2</em>')
      .replace(/(^|[^\w])_([^_\n]+)_(?!\w)/g, '$1<em>$2</em>');
    return s.replace(/\u0000(\d+)\u0000/g, (_, i) => link[i]);
  }

  function paragraphs(text) {
    return String(text || '')
      .split(/\n\s*\n/)
      .map(p => p.trim())
      .filter(Boolean)
      .map(p => inlineMd(p).replace(/\n/g, '<br>'));
  }

  // Divide un paragrafo HTML circa a metà, su uno spazio fuori dai tag.
  function splitHtml(html) {
    const mid = html.length / 2;
    let inTag = false, best = -1;
    for (let i = 0; i < html.length; i++) {
      const ch = html[i];
      if (ch === '<') inTag = true;
      else if (ch === '>') inTag = false;
      else if (ch === ' ' && !inTag && (best === -1 || Math.abs(i - mid) < Math.abs(best - mid))) best = i;
    }
    return best === -1 ? [html, ''] : [html.slice(0, best), html.slice(best + 1)];
  }

  // ---------- articoli ----------

  function renderImage(art) {
    if (!art.image) return '';
    const legacy = !art.imageBaked && art.imageStyle === 'woodcut' ? 'img-woodcut'
                 : !art.imageBaked && art.imageStyle === 'bitmap' ? 'img-bitmap' : '';
    const cls = ['article-img', legacy, art.imageBaked ? 'img-baked' : ''].filter(Boolean).join(' ');
    const fl = art.imageFloat === 'left' || art.imageFloat === 'right' ? ` float-${art.imageFloat}` : '';
    const cap = art.imageCaption ? `<figcaption class="caption">${art.imageCaption}</figcaption>` : '';
    return `<figure class="image-wrapper${fl}"><img class="${cls}" src="${esc(art.image)}" alt="${esc(art.imageCaption || 'illustrazione')}">${cap}</figure>`;
  }

  function renderArticle(art, opts = {}) {
    if (!art) return '';
    const fw = !!opts.fullWidth;
    let h = '';
    if (art.kicker) h += `<div class="kicker">${art.kicker}</div>`;
    if (art.title) {
      h += fw && art.fwTitleSize
        ? `<h2 class="fw-title" style="font-size:${esc(art.fwTitleSize)};text-align:${esc(art.fwTitleAlign || 'center')}">${art.title}</h2>`
        : `<h2>${art.title}</h2>`;
    }
    if (art.byline) h += `<div class="byline">${art.byline}</div>`;

    const img = renderImage(art);
    const pos = art.imagePosition || 'top';
    const floated = art.imageFloat === 'left' || art.imageFloat === 'right';
    const align = fw && art.fwTextAlign ? ` style="text-align:${esc(art.fwTextAlign)}"` : '';
    const IMG = '\u0001';

    let paras = paragraphs(art.text);
    if (img && pos === 'middle') {
      if (paras.length >= 2) {
        const at = Math.ceil(paras.length / 2);
        paras = [...paras.slice(0, at), IMG, ...paras.slice(at)];
      } else if (paras.length === 1) {
        const [a, b] = splitHtml(paras[0]);
        paras = b ? [a, IMG, b] : [a, IMG];
      } else {
        paras = [IMG];
      }
    }
    const body = paras.map(p => (p === IMG ? img : `<p${align}>${p}</p>`)).join('');
    const cols = fw ? Number(art.fwColumns || 1) : 1;
    const wrapped = cols > 1 ? `<div class="fullwidth-columns" style="column-count:${cols}">${body}</div>` : body;

    if (img && pos === 'top') h += img + wrapped;
    else if (img && pos === 'bottom') h += wrapped + img;
    else h += wrapped;
    if (floated) h = `<div class="clearfix">${h}</div>`;
    return h;
  }

  // ---------- strumenti di modifica (solo nell'editor) ----------

  function tools(loc, index, actions) {
    const label = { edit: '✏️ Modifica', delete: '🗑️ Elimina', up: '⬆️', down: '⬇️', hide: '🙈 Nascondi',
                    enable: '➕ Attiva', reset: '⟳ Default', add: '➕ Aggiungi', addFw: '➕ Aggiungi full-width' };
    const idx = index == null ? '' : ` data-index="${index}"`;
    return `<div class="ez-tools">${actions.map(a =>
      `<button type="button" class="ez-btn" data-act="${a}" data-loc="${loc}"${idx}>${label[a] || a}</button>`).join('')}</div>`;
  }

  function isGhost(o, loc, index) {
    return o.ghost && o.ghost.loc === loc && (o.ghost.index == null || o.ghost.index === index);
  }

  function articleList(list, loc, o) {
    return list.map((art, i) => {
      const ghost = isGhost(o, loc, i) ? ' ez-ghost' : '';
      const drag = o.editable ? ` draggable="true" data-loc="${loc}" data-index="${i}"` : '';
      const acts = ['edit', 'delete'];
      if (i > 0) acts.push('up');
      if (i < list.length - 1) acts.push('down');
      return `<article class="ez-item${ghost}"${drag}>${renderArticle(art)}${o.editable ? tools(loc, i, acts) : ''}</article>`;
    }).join('');
  }

  function listSection(d, key, title, o) {
    let h = `<div><h2>${title}</h2><div class="ez-drop" data-loc="${key}">`;
    d[key].forEach((item, i) => {
      const ghost = isGhost(o, key, i) ? ' ez-ghost' : '';
      h += `<p class="ez-item${ghost}">${inlineMd(String(item.text || ''))}${o.editable ? tools(key, i, ['edit', 'delete']) : ''}</p>`;
    });
    if (o.editable) h += tools(key, null, ['add']);
    return h + '</div></div>';
  }

  // ---------- pagine ----------

  function titleFontSize(text, hasLogo) {
    // Il titolo resta su una riga: la dimensione scende con la lunghezza del nome.
    const avail = hasLogo ? 600 : 690;
    const px = Math.max(26, Math.min(64, avail / (Math.max(text.length, 1) * 0.74)));
    return Math.round(px) + 'px';
  }

  function renderHeader(d, m) {
    const h = d.header;
    const full = `${m.nameA} ${m.nameB}`.trim();
    const logo = m.logo ? `<img class="header-logo" src="${esc(m.logo)}" alt="" onerror="this.style.display='none'">` : '';
    const contact = m.contact ? `<span>📧 ${esc(m.contact)}</span>` : '';
    return `<div class="header">
      <div class="title-wrapper">${logo}
        <div class="title" style="font-size:${titleFontSize(full, !!m.logo)}"><span class="name-a">${esc(m.nameA)}</span>${m.nameB ? ` <span class="name-b">${esc(m.nameB)}</span>` : ''}</div>
      </div>
      ${m.motto ? `<div class="motto">“${esc(m.motto)}”</div>` : ''}
      ${m.subhead ? `<div class="subhead">${esc(m.subhead)}</div>` : ''}
      <div class="edition-info">
        <span>Anno ${esc(h.anno)} – Numero ${esc(h.numero)}</span>
        <span>${esc(h.data)}</span>
        <span>${esc(m.price)}</span>
      </div>
      <div class="edition-info">${contact}<span>${esc(m.fullName)} © ${new Date().getFullYear()}</span></div>
    </div>`;
  }

  function renderFooter(d, m, o) {
    let h = '<footer><div class="edition-info">';
    h += `<span class="ez-item${isGhost(o, 'nextIssue') ? ' ez-ghost' : ''}">➤ PROSSIMO NUMERO: ${inlineMd(d.nextIssue || '')}${o.editable ? tools('nextIssue', null, ['edit', 'delete']) : ''}</span>`;
    h += `<span class="ez-item${isGhost(o, 'rubric') ? ' ez-ghost' : ''}">➤ RUBRICA FISSA: ${inlineMd(d.fixedRubric || '')}${o.editable ? tools('rubric', null, ['edit', 'delete']) : ''}</span>`;
    h += '</div>';
    if (m.disclaimer) h += `<p>${esc(m.disclaimer.replace(/\{nome\}/g, m.fullName))}</p>`;
    return h + '</footer>';
  }

  function renderFront(d, m, o) {
    let h = renderHeader(d, m);

    if (d.fullWidth) {
      h += `<div class="full-width ez-item${isGhost(o, 'fullWidth') ? ' ez-ghost' : ''}">${renderArticle(d.fullWidth, { fullWidth: true })}${o.editable ? tools('fullWidth', null, ['edit', 'delete']) : ''}</div>`;
    } else if (o.editable) {
      h += `<div class="full-width ez-empty"><em>(Nessun articolo full-width.)</em>${tools('fullWidth', null, ['addFw'])}</div>`;
    }

    h += `<div class="columns-2"><div class="ez-drop" data-loc="colLeft">${articleList(d.colLeft, 'colLeft', o)}</div>`;
    h += `<div class="ez-drop" data-loc="colRight">${articleList(d.colRight, 'colRight', o)}</div></div>`;

    if (d.fakeAd.enabled) {
      h += `<div class="fake-ad ez-item${d.fakeAd.colored ? ' fake-ad-colored' : ''}${isGhost(o, 'fakeAd') ? ' ez-ghost' : ''}">${inlineMd(d.fakeAd.text || '')}${o.editable ? tools('fakeAd', null, ['edit', 'hide']) : ''}</div>`;
    } else if (o.editable) {
      h += `<div class="fake-ad ez-empty"><em>Consiglio disabilitato</em>${tools('fakeAd', null, ['enable'])}</div>`;
    }

    h += `<div class="feature-box ez-item${isGhost(o, 'straight') ? ' ez-ghost' : ''}"><h3>${esc(m.sections.straight)}</h3>`;
    h += paragraphs(d.straightFromTheMan.text).map(p => `<p>${p}</p>`).join('');
    if (o.editable) h += tools('straight', null, ['edit', 'reset']);
    h += '</div><hr>';

    h += '<div class="columns-3">';
    h += listSection(d, 'roundup', esc(m.sections.roundup), o);
    h += listSection(d, 'letters', esc(m.sections.letters), o);
    h += listSection(d, 'fight', esc(m.sections.fight), o);
    h += '</div>';
    return h;
  }

  function renderInner(d, m, page, i, o) {
    const n = i + 2;
    let h = `<div class="running-head"><span><b>${esc(m.fullName)}</b></span><span>Anno ${esc(d.header.anno)} – Numero ${esc(d.header.numero)}</span><span>pag. ${n}</span></div>`;
    if (page.title) h += `<h1 class="page-title">${page.title}</h1>`;
    const loc = `page:${i}`;
    h += `<div class="flow ez-drop" data-loc="${loc}" style="column-count:${page.columns}">${articleList(page.articles, loc, o)}</div>`;
    if (o.editable && !page.articles.length) {
      h += `<div class="ez-empty"><em>Pagina vuota: scegli “Pagina ${n}” come destinazione nel pannello per aggiungere articoli.</em></div>`;
    }
    return h;
  }

  /**
   * Rende un'uscita.
   *  opts.editable  mostra i comandi di modifica (solo editor)
   *  opts.ghost     {loc, index} elemento in anteprima mentre lo si scrive
   *  opts.bw        modalità fotocopia in bianco e nero
   *  opts.pagesOnly restituisce un array con l'HTML di ciascuna pagina
   */
  function render(content, mh, opts = {}) {
    const d = normalize(content);
    const m = masthead(mh);
    const o = Object.assign({ editable: false, ghost: null }, opts);

    const pages = [];
    const lastInner = d.pages.length - 1;
    let front = renderFront(d, m, o);
    if (lastInner < 0) front += renderFooter(d, m, o);
    pages.push(`<section class="page page-front"><div class="page-body">${front}</div></section>`);
    d.pages.forEach((p, i) => {
      let inner = renderInner(d, m, p, i, o);
      if (i === lastInner) inner += renderFooter(d, m, o);
      pages.push(`<section class="page page-inner" data-page="${i}"><div class="page-body">${inner}</div></section>`);
    });

    if (o.pagesOnly) return pages;
    const cls = ['newspaper', o.editable ? 'editing' : '', o.bw ? 'bw' : ''].filter(Boolean).join(' ');
    return `<div class="${cls}" style="--accent:${esc(d.header.titleColor || '#8b1f1f')}">${pages.join('')}</div>`;
  }

  // ---------- libretto ----------

  // Ordine di stampa per un libretto pinzato al centro: n pagine (multiplo di 4,
  // completato con pagine bianche), due per facciata, fronte e retro.
  // Esempio con 8: [8|1] [2|7] [6|3] [4|5].
  function bookletOrder(count) {
    const n = Math.max(4, Math.ceil(count / 4) * 4);
    const sides = [];
    for (let s = 0; s < n / 4; s++) {
      sides.push([n - 2 * s, 2 * s + 1]);
      sides.push([2 * s + 2, n - 2 * s - 1]);
    }
    return { n, sides };
  }

  function renderBooklet(content, mh, opts = {}) {
    const pages = render(content, mh, Object.assign({}, opts, { pagesOnly: true, editable: false }));
    const { n, sides } = bookletOrder(pages.length);
    const blank = '<section class="page page-blank"></section>';
    const at = k => pages[k - 1] || blank;
    const html = sides.map(([l, r]) => `<div class="sheet-side"><div class="slot">${at(l)}</div><div class="slot">${at(r)}</div></div>`).join('');
    const d = normalize(content);
    return {
      html: `<div class="newspaper booklet${opts.bw ? ' bw' : ''}" style="--accent:${esc(d.header.titleColor || '#8b1f1f')}">${html}</div>`,
      pages: pages.length, padded: n, sheets: n / 4
    };
  }

  // ---------- adattamento all'A4 ----------

  // Quanto ogni pagina eccede l'area stampabile di un A4 (190 × 277 mm).
  function measureOverflow(root) {
    const probe = document.createElement('div');
    probe.style.cssText = 'position:absolute;visibility:hidden;height:277mm;width:1px';
    document.body.appendChild(probe);
    const limit = probe.offsetHeight;
    probe.remove();
    return Array.from(root.querySelectorAll('.page')).map((page, i) => {
      const body = page.querySelector('.page-body');
      const h = body ? body.offsetHeight : 0;
      return { page: i + 1, el: page, limitPx: limit, heightPx: h, ratio: h / limit };
    });
  }

  // ---------- documenti autonomi (esportazione e stampa) ----------

  let cssCache = null;
  async function css() {
    if (cssCache) return cssCache;
    const res = await fetch('assets/newspaper.css', { cache: 'no-cache' });
    if (!res.ok) throw new Error('Foglio di stile non disponibile');
    return (cssCache = await res.text());
  }

  const toDataUri = blob => new Promise((ok, ko) => {
    const fr = new FileReader();
    fr.onload = () => ok(fr.result);
    fr.onerror = () => ko(fr.error);
    fr.readAsDataURL(blob);
  });

  // Il logo originale può pesare molto: nell'intestazione è alto 60px, quindi
  // ne basta una versione a 120px.
  async function logoUri(src) {
    try {
      // onload e non img.decode(): in una scheda non visibile decode() viene
      // rimandato a tempo indeterminato, e "Esporta" o "Pubblica" resterebbero
      // fermi finché non si torna sulla scheda.
      const img = await new Promise((ok, ko) => {
        const i = new Image();
        const t = setTimeout(() => ko(new Error('logo non caricato')), 8000);
        i.onload = () => { clearTimeout(t); ok(i); };
        i.onerror = () => { clearTimeout(t); ko(new Error('logo non leggibile')); };
        i.src = src;
      });
      const h = 120, k = h / img.naturalHeight;
      const c = document.createElement('canvas');
      c.width = Math.round(img.naturalWidth * k);
      c.height = h;
      c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
      return c.toDataURL('image/png');
    } catch (e) {
      return null;
    }
  }

  // Sostituisce immagini caricate e logo con data URI: il file risultante si
  // apre ovunque, senza dipendere dal sito. Le immagini esterne restano link.
  async function inlineAssets(html, mh) {
    const m = masthead(mh);
    if (m.logo && !/^data:/.test(m.logo)) {
      const uri = await logoUri(m.logo);
      html = html.split(`src="${esc(m.logo)}"`).join(uri ? `src="${uri}"` : 'src=""');
    }
    const locali = [...new Set((html.match(/src="(uploads\/[a-f0-9]{24}\.(?:png|jpg))"/g) || [])
      .map(s => s.slice(5, -1)))];
    for (const path of locali) {
      try {
        const res = await fetch(path);
        if (!res.ok) continue;
        const uri = await toDataUri(await res.blob());
        html = html.split(`src="${path}"`).join(`src="${uri}"`);
      } catch (e) { /* resta il percorso: meglio un'immagine mancante che un export fallito */ }
    }
    return html;
  }

  const PAGE_RULE = {
    normal: '@page { size: A4 portrait; margin: 10mm; }',
    booklet: '@page { size: A4 landscape; margin: 0; }'
  };

  function shell(title, styles, body, extraHead = '') {
    return `<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>${esc(title)}</title><style>${styles}</style>${extraHead}</head>
<body style="margin:0;padding:8mm 0;background:#e6e3db">${body}</body></html>`;
  }

  async function exportDocument(content, mh, opts = {}) {
    const m = masthead(mh);
    const body = await inlineAssets(render(content, m, { bw: opts.bw }), m);
    const styles = (await css()) + '\n' + PAGE_RULE.normal + '\n@media print { body { padding: 0 !important; background: #fff !important; } }';
    return shell(opts.title || m.fullName, styles, body);
  }

  async function printDocument(content, mh, opts = {}) {
    const m = masthead(mh);
    let body, rule, banner = '';
    if (opts.mode === 'booklet') {
      const b = renderBooklet(content, m, { bw: opts.bw });
      body = b.html;
      rule = PAGE_RULE.booklet;
      banner = `<div class="ez-istruzioni">Libretto: ${b.pages} pagine${b.padded > b.pages ? ` + ${b.padded - b.pages} bianche` : ''} su ${b.sheets} fogli A4.
        Stampa <b>fronte/retro</b> con rilegatura sul <b>lato corto</b>, piega i fogli a metà tutti insieme e pinza al centro.</div>`;
    } else {
      body = render(content, m, { bw: opts.bw });
      rule = PAGE_RULE.normal;
    }
    body = await inlineAssets(body, m);
    const styles = (await css()) + '\n' + rule + `
      .ez-istruzioni { max-width: 297mm; margin: 0 auto 6mm; font: 14px/1.4 system-ui, sans-serif; background: #fff8d6; border: 1px solid #c9b45c; padding: 10px 14px; }
      @media print { .ez-istruzioni { display: none; } body { padding: 0 !important; background: #fff !important; } }`;
    const script = '<script>window.addEventListener("load",function(){setTimeout(function(){window.print()},200)});<\/script>';
    return shell(opts.title || m.fullName, styles, banner + body, script);
  }

  // ---------- favicon ----------

  function favicon(letter, color) {
    const c = document.createElement('canvas');
    c.width = c.height = 64;
    const g = c.getContext('2d');
    g.fillStyle = color || '#8b1f1f';
    g.beginPath(); g.arc(32, 32, 30, 0, Math.PI * 2); g.fill();
    g.fillStyle = '#fff';
    g.font = 'bold 40px Georgia, serif';
    g.textAlign = 'center'; g.textBaseline = 'middle';
    g.fillText((letter || 'E').charAt(0).toUpperCase(), 32, 35);
    let link = document.querySelector('link[rel="icon"]');
    if (!link) { link = document.createElement('link'); link.rel = 'icon'; document.head.appendChild(link); }
    link.href = c.toDataURL('image/png');
  }

  global.Ezine = {
    SCHEMA, DEFAULT_MASTHEAD, masthead, normalize, clone, esc,
    inlineMd, paragraphs, renderArticle, render, renderBooklet, bookletOrder,
    measureOverflow, exportDocument, printDocument, inlineAssets, favicon
  };
})(window);
