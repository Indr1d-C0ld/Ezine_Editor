// =============================================================================
// history.js – annulla e ripeti per l'editor.
//
// Conserva istantanee dello stato prima di ogni modifica. L'editor chiama
// checkpoint() PRIMA di cambiare il numero e settle() DOPO: se il contenuto
// non è cambiato davvero (es. "sposta su" sul primo articolo) l'istantanea
// viene scartata, così annulla non fa mai un passo a vuoto.
//
// Lo stato è un oggetto qualsiasi con un campo `content`; qui viene
// confrontato e salvato come testo JSON, quindi le istantanee non cambiano se
// l'editor modifica poi il contenuto sul posto.
//
// Le modifiche fatte digitando (un campo della testata, il titolo di una
// pagina) passano una chiave: i tasti ravvicinati con la stessa chiave
// diventano un solo passo, invece di uno per lettera.
//
// dump() e load() servono all'editor per conservarla nella scheda del browser
// (sessionStorage), così sopravvive al ricaricamento della pagina.
// =============================================================================
(function (global) {
  'use strict';

  function create(opts = {}) {
    const limit = opts.limit || 100;
    const coalesceMs = opts.coalesceMs || 1500;
    const now = opts.now || (() => Date.now());

    let undoStack = [], redoStack = [];
    let pending = null;
    let lastKey = null, lastAt = 0;

    const freeze = state => Object.assign({}, state, { content: JSON.stringify(state.content) });
    const thaw = state => Object.assign({}, state, { content: JSON.parse(state.content) });

    return {
      // Da chiamare prima di una modifica, con lo stato com'è ancora.
      checkpoint(state, label, key = null) {
        const t = now();
        if (key && key === lastKey && t - lastAt < coalesceMs) { lastAt = t; return; }
        lastKey = key; lastAt = t;
        pending = { state: freeze(state), label };
      },

      // Da chiamare dopo la modifica: registra il passo solo se il contenuto è cambiato.
      settle(content) {
        if (!pending) return false;
        const p = pending;
        pending = null;
        if (p.state.content === JSON.stringify(content)) { lastKey = null; return false; }
        undoStack.push(p);
        if (undoStack.length > limit) undoStack.shift();
        redoStack = [];
        return true;
      },

      // Restituiscono lo stato da ripristinare ({ state, label }) o null.
      undo(current) { return step(undoStack, redoStack, current); },
      redo(current) { return step(redoStack, undoStack, current); },

      canUndo: () => undoStack.length > 0,
      canRedo: () => redoStack.length > 0,
      undoLabel: () => (undoStack.length ? undoStack[undoStack.length - 1].label : ''),
      redoLabel: () => (redoStack.length ? redoStack[redoStack.length - 1].label : ''),
      size: () => ({ undo: undoStack.length, redo: redoStack.length }),
      clear() { undoStack = []; redoStack = []; pending = null; lastKey = null; },

      // Gli ultimi `max` passi in ciascuna direzione, pronti per JSON.stringify.
      dump(max = limit) {
        const ultimi = a => (max > 0 ? a.slice(-max) : []);
        return { undo: ultimi(undoStack), redo: ultimi(redoStack) };
      },
      // Riprende una cronologia salvata con dump(). Se qualcosa non torna non
      // tocca quella attuale e restituisce false.
      load(data) {
        const valide = a => Array.isArray(a) && a.every(e => e && typeof e.label === 'string' && e.state &&
          typeof e.state.content === 'string' && leggibile(e.state.content));
        if (!data || !valide(data.undo) || !valide(data.redo)) return false;
        undoStack = data.undo.slice(-limit);
        redoStack = data.redo.slice(-limit);
        pending = null;
        lastKey = null;
        return true;
      },
    };

    function leggibile(json) {
      try { JSON.parse(json); return true; } catch (e) { return false; }
    }

    function step(from, to, current) {
      pending = null;
      lastKey = null;           // il tasto successivo apre un passo nuovo
      if (!from.length) return null;
      const e = from.pop();
      to.push({ state: freeze(current), label: e.label });
      return { state: thaw(e.state), label: e.label };
    }
  }

  global.EzineHistory = { create };
})(typeof window !== 'undefined' ? window : globalThis);
