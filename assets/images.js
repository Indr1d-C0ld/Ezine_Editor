/* images.js – preparazione delle immagini nel browser, prima del caricamento.
 *
 * Ogni immagine viene ridisegnata su un canvas e ricodificata: questo elimina
 * già qui tutti i metadati (EXIF, posizione GPS, modello del telefono). Il
 * server la ricodifica comunque una seconda volta, per non dipendere dal client.
 *
 * Gli stili "retinati" lavorano sui pixel, non con filtri CSS: l'effetto resta
 * identico nell'export, in stampa e soprattutto in fotocopia, dove i filtri CSS
 * non esistono e i grigi si impastano.
 */
(function (global) {
  'use strict';

  const STYLES = {
    normal:   'Normale (fotografia)',
    halftone: 'Mezzatinta (retino a punti)',
    dither:   'Bitmap (diffusione dell\'errore)',
    woodcut:  'Xilografia (tratteggio inciso)'
  };

  function loadImage(src) {
    return new Promise((ok, ko) => {
      const img = new Image();
      img.onload = () => ok(img);
      img.onerror = () => ko(new Error('Immagine non leggibile'));
      img.src = src;
    });
  }

  // Il browser applica da solo l'orientamento EXIF quando disegna un <img>:
  // una foto scattata in verticale resta verticale anche dopo aver perso i metadati.
  function toCanvas(img, maxSide) {
    const k = Math.min(1, maxSide / Math.max(img.naturalWidth, img.naturalHeight));
    const c = document.createElement('canvas');
    c.width = Math.max(1, Math.round(img.naturalWidth * k));
    c.height = Math.max(1, Math.round(img.naturalHeight * k));
    const g = c.getContext('2d');
    g.fillStyle = '#fff';          // le zone trasparenti diventano carta, non nero
    g.fillRect(0, 0, c.width, c.height);
    g.drawImage(img, 0, 0, c.width, c.height);
    return c;
  }

  function luminance(c) {
    const { data } = c.getContext('2d').getImageData(0, 0, c.width, c.height);
    const out = new Float32Array(c.width * c.height);
    for (let i = 0, p = 0; i < data.length; i += 4, p++) {
      out[p] = 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
    }
    return out;
  }

  // Leggero aumento di contrasto: la retinatura funziona meglio su toni decisi.
  function contrast(lum, amount) {
    for (let i = 0; i < lum.length; i++) lum[i] = Math.max(0, Math.min(255, (lum[i] - 128) * amount + 128));
    return lum;
  }

  function fromBits(w, h, isBlack) {
    const c = document.createElement('canvas');
    c.width = w; c.height = h;
    const g = c.getContext('2d');
    const img = g.createImageData(w, h);
    for (let p = 0, i = 0; p < w * h; p++, i += 4) {
      const v = isBlack(p) ? 0 : 255;
      img.data[i] = img.data[i + 1] = img.data[i + 2] = v;
      img.data[i + 3] = 255;
    }
    g.putImageData(img, 0, 0);
    return c;
  }

  // Floyd–Steinberg: ogni pixel diventa bianco o nero e l'errore viene distribuito
  // ai vicini. Il risultato è il classico bitmap "da fanzine".
  function dither(src) {
    const w = src.width, h = src.height;
    const lum = contrast(luminance(src), 1.15);
    const black = new Uint8Array(w * h);
    for (let y = 0; y < h; y++) {
      for (let x = 0; x < w; x++) {
        const p = y * w + x;
        const v = lum[p] < 128 ? 0 : 255;
        black[p] = v === 0 ? 1 : 0;
        const err = lum[p] - v;
        if (x + 1 < w) lum[p + 1] += err * 7 / 16;
        if (y + 1 < h) {
          if (x > 0) lum[p + w - 1] += err * 3 / 16;
          lum[p + w] += err * 5 / 16;
          if (x + 1 < w) lum[p + w + 1] += err * 1 / 16;
        }
      }
    }
    return fromBits(w, h, p => black[p]);
  }

  // Mezzatinta: punti neri su griglia ruotata a 45°, con raggio proporzionale
  // allo scuro della zona. È il retino dei giornali stampati.
  function halftone(src, cell = 6) {
    const w = src.width, h = src.height;
    const lum = contrast(luminance(src), 1.1);
    const c = document.createElement('canvas');
    c.width = w; c.height = h;
    const g = c.getContext('2d');
    g.fillStyle = '#fff'; g.fillRect(0, 0, w, h);
    g.fillStyle = '#000';
    const cos = Math.SQRT1_2, sin = Math.SQRT1_2;
    const diag = Math.ceil(Math.hypot(w, h));
    for (let v = -diag; v < diag; v += cell) {
      for (let u = -diag; u < diag; u += cell) {
        const x = u * cos - v * sin + w / 2;
        const y = u * sin + v * cos + h / 2;
        if (x < -cell || y < -cell || x > w + cell || y > h + cell) continue;
        const sx = Math.min(w - 1, Math.max(0, Math.round(x)));
        const sy = Math.min(h - 1, Math.max(0, Math.round(y)));
        const dark = 1 - lum[sy * w + sx] / 255;
        // 0.66: anche nelle ombre resta un filo di bianco fra i punti, così il
        // dettaglio sopravvive alla fotocopia invece di chiudersi in nero pieno
        const r = Math.sqrt(dark) * cell * 0.66;
        if (r > 0.35) { g.beginPath(); g.arc(x, y, r, 0, Math.PI * 2); g.fill(); }
      }
    }
    return c;
  }

  // Xilografia: soglia modulata da righe ondulate, come il tratteggio di
  // un'incisione. Le zone scure diventano linee spesse, le chiare sottili.
  function woodcut(src, period = 5) {
    const w = src.width, h = src.height;
    const lum = contrast(luminance(src), 1.35);
    return fromBits(w, h, p => {
      const x = p % w, y = (p / w) | 0;
      const wave = Math.sin((y + Math.sin(x / 23) * 2.2) * (2 * Math.PI / period));
      return lum[p] < 128 + wave * 70;
    });
  }

  const toBlob = (c, type, q) => new Promise((ok, ko) =>
    c.toBlob(b => (b ? ok(b) : ko(new Error('Codifica non riuscita'))), type, q));

  /** Prepara un file scelto dall'utente: ridimensiona e ricodifica (senza metadati). */
  async function prepare(file, { maxSide = 1800, png = false } = {}) {
    if (!/^image\//.test(file.type)) throw new Error('Il file scelto non è un\'immagine');
    const url = URL.createObjectURL(file);
    try {
      const c = toCanvas(await loadImage(url), maxSide);
      return png ? toBlob(c, 'image/png') : toBlob(c, 'image/jpeg', 0.88);
    } finally {
      URL.revokeObjectURL(url);
    }
  }

  /** Applica uno stile retinato a un'immagine già caricata sul server. */
  async function stylize(src, style) {
    const fn = { halftone, dither, woodcut }[style];
    if (!fn) throw new Error('Stile sconosciuto');
    // ~900px: abbastanza per la stampa A4, e il retino resta visibile come tale.
    const base = toCanvas(await loadImage(src), 900);
    return toBlob(fn(base), 'image/png');
  }

  async function upload(blob, nome = 'immagine') {
    const fd = new FormData();
    fd.append('image', blob, nome + (blob.type === 'image/png' ? '.png' : '.jpg'));
    const res = await fetch('api/upload_image.php', { method: 'POST', body: fd });
    const out = await res.json().catch(() => ({}));
    if (!res.ok || !out.url) throw new Error(out.error || `Caricamento non riuscito (HTTP ${res.status})`);
    return out;
  }

  global.EzineImages = { STYLES, prepare, stylize, upload };
})(window);
