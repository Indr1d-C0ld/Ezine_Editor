// Test di annulla e ripeti (assets/history.js), eseguiti con Node senza
// browser. Il collegamento con l'editor (pulsanti, Ctrl+Z, ripristino della
// pagina) si verifica nel browser.
//
// uso: node tests/history_test.mjs   (lo avvia tests/run.sh)

import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import vm from 'node:vm';

const radice = join(dirname(fileURLToPath(import.meta.url)), '..');
const contesto = { window: {} };
vm.createContext(contesto);
vm.runInContext(readFileSync(join(radice, 'assets/history.js'), 'utf8'), contesto);
const H = contesto.window.EzineHistory;

let passati = 0;
const falliti = [];
const sezione = t => console.log(`\n── ${t}`);
function verifica(descrizione, ok, dettaglio = '') {
  if (ok) { passati++; console.log(`  ✓ ${descrizione}`); return; }
  falliti.push(descrizione);
  console.log(`  ✗ ${descrizione}${dettaglio !== '' ? `\n      → ${dettaglio}` : ''}`);
}

// Un "editor" minimo: lo stato si modifica sul posto, come fa index.html.
let orologio = 0;
const nuovo = (opts = {}) => H.create(Object.assign({ now: () => orologio }, opts));
const stato = (c, issue = { id: null }) => ({ content: c, issue, title: '', dirty: true });
function modifica(h, s, label, fn, key) { h.checkpoint(s, label, key); fn(s.content); return h.settle(s.content); }
const testo = s => JSON.stringify(s.content);

// ===================================================================
sezione('Annulla e ripeti');
let h = nuovo();
let s = stato({ articoli: ['a'] });
verifica('all\'inizio non c\'è niente da annullare né da ripetere', !h.canUndo() && !h.canRedo() && h.undo(s) === null && h.redo(s) === null);
modifica(h, s, 'aggiunta', c => c.articoli.push('b'));
modifica(h, s, 'eliminazione', c => c.articoli.shift());
verifica('ogni modifica è un passo', h.size().undo === 2);
verifica('l\'etichetta è quella dell\'ultima modifica', h.undoLabel() === 'eliminazione');
let r = h.undo(s); s = stato(r.state.content);
verifica('annulla torna allo stato precedente', testo(s) === '{"articoli":["a","b"]}', testo(s));
verifica('dopo annulla si può ripetere', h.canRedo() && h.redoLabel() === 'eliminazione');
r = h.undo(s); s = stato(r.state.content);
verifica('due annulla tornano all\'inizio', testo(s) === '{"articoli":["a"]}' && !h.canUndo());
r = h.redo(s); s = stato(r.state.content);
r = h.redo(s); s = stato(r.state.content);
verifica('ripeti rifà i passi nell\'ordine', testo(s) === '{"articoli":["b"]}' && !h.canRedo() && h.size().undo === 2, testo(s));

// ===================================================================
sezione('Casi limite');
h = nuovo(); s = stato({ articoli: ['a', 'b'] });
modifica(h, s, 'aggiunta', c => c.articoli.push('c'));
h.undo(s); s = stato({ articoli: ['a', 'b'] });
modifica(h, s, 'altra modifica', c => c.articoli.pop());
verifica('una modifica nuova dopo annulla cancella i passi da ripetere', !h.canRedo());
verifica('una modifica che non cambia nulla non diventa un passo', modifica(h, s, 'sposta su il primo', () => {}) === false && h.size().undo === 1);
h = nuovo(); s = stato({ articoli: ['a'] });
h.checkpoint(s, 'prima'); s.content.articoli.push('x'); h.settle(s.content);
const salvato = h.undo(s).state;
salvato.content.articoli.push('modificato dopo');
s = stato({ articoli: ['a'] });
verifica('le istantanee non cambiano se il contenuto viene poi modificato sul posto', testo(h.redo(s).state) === '{"articoli":["a","x"]}');
h = nuovo({ limit: 3 }); s = stato({ n: 0 });
for (let i = 1; i <= 5; i++) modifica(h, s, `passo ${i}`, c => { c.n = i; });
verifica('la cronologia tiene al massimo il limite di passi', h.size().undo === 3);
for (let i = 0; i < 3; i++) s = stato(h.undo(s).state.content);
verifica('annullando tutto si torna al passo più vecchio conservato', s.content.n === 2, JSON.stringify(s.content));
h.clear();
verifica('clear svuota tutto', !h.canUndo() && !h.canRedo());
h = nuovo(); s = stato({ n: 0 });
h.checkpoint(s, 'mai conclusa');
verifica('un checkpoint senza settle non diventa un passo', !h.canUndo() && h.settle({ n: 0 }) === false);

// ===================================================================
sezione('Digitazione');
h = nuovo({ coalesceMs: 1500 }); s = stato({ numero: '' }); orologio = 0;
for (const lettera of '12') { orologio += 200; modifica(h, s, 'numero della testata', c => { c.numero += lettera; }, 'header:numero'); }
verifica('tasti ravvicinati nello stesso campo sono un solo passo', h.size().undo === 1);
orologio += 5000;
modifica(h, s, 'numero della testata', c => { c.numero += '3'; }, 'header:numero');
verifica('dopo una pausa si apre un passo nuovo', h.size().undo === 2);
orologio += 100;
modifica(h, s, 'anno della testata', c => { c.anno = 'I'; }, 'header:anno');
verifica('un campo diverso apre un passo nuovo', h.size().undo === 3);
s = stato(h.undo(s).state.content); s = stato(h.undo(s).state.content);
verifica('annulla toglie il gruppo di tasti per intero', s.content.numero === '12' && !('anno' in s.content), JSON.stringify(s.content));
orologio += 100;
modifica(h, s, 'numero della testata', c => { c.numero += '9'; }, 'header:numero');
verifica('dopo annulla il tasto successivo non si fonde col passo annullato', h.size().undo === 2 && !h.canRedo());
h = nuovo({ coalesceMs: 1500 }); s = stato({ numero: '' }); orologio = 0;
modifica(h, s, 'numero della testata', c => { c.numero = '1'; }, 'header:numero');
orologio += 100; s = stato(h.undo(s).state.content);
orologio += 100; modifica(h, s, 'numero della testata', c => { c.numero = '5'; }, 'header:numero');
verifica('riscrivendo subito nello stesso campo dopo annulla, il nuovo testo si può annullare', h.canUndo() && h.undo(s).state.content.numero === '', JSON.stringify(h.size()));

// ===================================================================
sezione('Collegamento all\'uscita');
h = nuovo(); s = stato({ t: 'bozza' }, { id: 5, updatedAt: 'X' }); s.title = 'Uscita cinque'; s.dirty = false;
h.checkpoint(s, 'apertura dell\'uscita #7');
s = stato({ t: 'sette' }, { id: 7, updatedAt: 'Y' }); h.settle(s.content);
r = h.undo(s);
verifica('l\'istantanea conserva uscita, titolo e stato di salvataggio', r.state.issue.id === 5 && r.state.title === 'Uscita cinque' && r.state.dirty === false);
verifica('ripeti torna all\'uscita aperta', h.redo(stato(r.state.content, r.state.issue)).state.issue.id === 7);

// ===================================================================
sezione('Conservazione nella scheda');
h = nuovo(); s = stato({ n: 0 }); orologio = 0;
for (let i = 1; i <= 4; i++) modifica(h, s, `passo ${i}`, c => { c.n = i; });
s = stato(h.undo(s).state.content);
const salvata = JSON.parse(JSON.stringify(h.dump()));   // come passa da sessionStorage
let h2 = nuovo();
verifica('una cronologia salvata si riprende identica', h2.load(salvata) && JSON.stringify(h2.size()) === '{"undo":3,"redo":1}');
verifica('dopo la ripresa annulla funziona', h2.undo(s).state.content.n === 2);
verifica('...e ripeti pure', nuovo().load(salvata) && (() => { const h3 = nuovo(); h3.load(salvata); return h3.redo(s).state.content.n === 4; })());
verifica('dump con un massimo tiene solo gli ultimi passi', h.dump(2).undo.length === 2 && h.dump(2).undo[1].label === 'passo 3');
verifica('dump(0) non tiene niente', h.dump(0).undo.length === 0 && h.dump(0).redo.length === 0);
h2 = nuovo(); h2.load(salvata);
const prima = JSON.stringify(h2.size());
verifica('dati non validi vengono rifiutati senza toccare la cronologia',
  [null, {}, { undo: 'x', redo: [] }, { undo: [{ label: 'a', state: { content: '{rotto' } }], redo: [] },
   { undo: [{ label: 'a', state: {} }], redo: [] }].every(d => h2.load(d) === false) && JSON.stringify(h2.size()) === prima);
verifica('la ripresa rispetta il limite di passi', (() => { const h4 = nuovo({ limit: 2 }); h4.load(salvata); return h4.size().undo === 2; })());

// ===================================================================
const totale = passati + falliti.length;
console.log('\n' + '─'.repeat(60));
if (falliti.length) {
  console.log(`ANNULLA/RIPETI: ${passati}/${totale} superati, ${falliti.length} falliti:`);
  falliti.forEach(f => console.log(`  ✗ ${f}`));
  process.exit(1);
}
console.log(`ANNULLA/RIPETI: tutti i ${totale} test superati`);
