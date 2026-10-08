// Test del motore di impaginazione (assets/render.js), eseguiti con Node senza
// browser: render.js viene caricato in un contesto isolato con un "window"
// minimo. Le funzioni che usano il DOM (misura dell'A4, favicon, logo) restano
// fuori: si verificano nel browser.
//
// uso: node tests/render_test.mjs   (lo avvia tests/run.sh)

import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import vm from 'node:vm';

const radice = join(dirname(fileURLToPath(import.meta.url)), '..');
// fetch finto: exportDocument legge il foglio di stile, qui basta un segnaposto
const contesto = { window: {}, fetch: async () => ({ ok: true, text: async () => '/* css */' }) };
vm.createContext(contesto);
vm.runInContext(readFileSync(join(radice, 'assets/render.js'), 'utf8'), contesto);
const E = contesto.window.Ezine;

let passati = 0;
const falliti = [];
const sezione = t => console.log(`\n── ${t}`);
function verifica(descrizione, ok, dettaglio = '') {
  if (ok) { passati++; console.log(`  ✓ ${descrizione}`); return; }
  falliti.push(descrizione);
  console.log(`  ✗ ${descrizione}${dettaglio ? `\n      → ${dettaglio}` : ''}`);
}
const conta = (s, sotto) => s.split(sotto).length - 1;

// ===================================================================
sezione('Markdown');
verifica('**testo** diventa grassetto', E.inlineMd('un **forte** segnale') === 'un <strong>forte</strong> segnale');
verifica('*testo* diventa corsivo', E.inlineMd('un *lieve* segnale') === 'un <em>lieve</em> segnale');
verifica('_testo_ diventa corsivo', E.inlineMd('un _lieve_ segnale') === 'un <em>lieve</em> segnale');
verifica('i link https diventano collegamenti', E.inlineMd('[sito](https://esempio.it)') === '<a href="https://esempio.it">sito</a>');
verifica('un "_" dentro un URL non diventa corsivo',
  E.inlineMd('[doc](https://esempio.it/_a_b_)') === '<a href="https://esempio.it/_a_b_">doc</a>', E.inlineMd('[doc](https://esempio.it/_a_b_)'));
verifica('i link javascript: non diventano collegamenti', !E.inlineMd('[x](javascript:alert(1))').includes('<a'));
verifica('le virgolette nell\'URL vengono neutralizzate', !E.inlineMd('[x](https://a.it/"onmouseover="y)').includes('"onmouseover'));
verifica('un asterisco isolato resta tale', E.inlineMd('3 * 4 = 12') === '3 * 4 = 12');
verifica('l\'HTML dei contenuti già scritti resta valido', E.inlineMd('<strong>vecchio</strong>') === '<strong>vecchio</strong>');
const par = E.paragraphs('primo\nriga due\n\nsecondo');
verifica('una riga vuota separa i paragrafi', par.length === 2, JSON.stringify(par));
verifica('un a capo semplice diventa <br>', par[0] === 'primo<br>riga due');

// ===================================================================
sezione('Normalizzazione dei contenuti');
const n = E.normalize({ header: { anno: 'II' }, pages: [{ columns: 7 }, null] });
verifica('completa le sezioni mancanti', Array.isArray(n.colLeft) && Array.isArray(n.letters) && n.straightFromTheMan.text === '' && n.fakeAd.enabled === false);
verifica('conserva i valori presenti', n.header.anno === 'II' && n.header.titleColor === '#8b1f1f');
verifica('le colonne non valide tornano a 2', n.pages[0].columns === 2 && n.pages[1].columns === 2);
verifica('segna la versione dello schema', n.v === E.SCHEMA);
verifica('un contenuto assente non manda in errore', E.normalize(null).colLeft.length === 0);
let errore = null;
try { E.render({ header: {} }, null); } catch (e) { errore = e; }
verifica('il render di un contenuto parziale non lancia eccezioni', errore === null, errore && errore.message);

// ===================================================================
sezione('Testata');
const m = E.masthead({ nameA: 'NOME', sections: { roundup: 'BREVI' } });
verifica('le impostazioni parziali si fondono con quelle predefinite', m.nameA === 'NOME' && m.nameB === E.DEFAULT_MASTHEAD.nameB);
verifica('anche i titoli delle sezioni si fondono', m.sections.roundup === 'BREVI' && m.sections.fight === E.DEFAULT_MASTHEAD.sections.fight);
const ostile = E.render({}, { nameA: '<img src=x onerror=alert(1)>', contact: '"><script>x</script>', motto: '<b>m</b>' });
verifica('i campi della testata vengono neutralizzati (nessun HTML iniettato)',
  !ostile.includes('<img src=x') && !ostile.includes('<script>') && !ostile.includes('<b>m</b>'));
verifica('il contatto vuoto non viene mostrato', !E.render({}, { contact: '' }).includes('📧'));
verifica('{nome} nella nota viene sostituito col nome per esteso',
  E.render({}, { fullName: 'Il Foglio', disclaimer: '{nome} declina.' }).includes('Il Foglio declina.'));

// ===================================================================
sezione('Impaginazione');
const doc = {
  header: { anno: 'I', numero: '3', data: 'oggi' },
  colLeft: [{ title: 'Primo', text: 'uno' }, { title: 'Secondo', text: 'due' }],
  pages: [{ columns: 3, title: 'Interna', articles: [{ title: 'Dentro', text: 'tre' }] }],
  nextIssue: 'prossimo'
};
const pulito = E.render(doc, null);
const modifica = E.render(doc, null, { editable: true });
verifica('una pagina per la prima e una per ciascuna interna', conta(pulito, 'class="page ') === 2);
verifica('in stampa ed esportazione nessun comando di modifica', !pulito.includes('ez-tools') && !pulito.includes('data-act='));
verifica('nell\'editor ci sono i comandi di modifica', modifica.includes('data-act="edit"') && modifica.includes('draggable="true"'));
verifica('il piè di pagina sta solo sull\'ultima pagina', conta(pulito, '<footer') === 1 && pulito.lastIndexOf('<footer') > pulito.indexOf('page-inner'));
verifica('senza pagine interne il piè di pagina sta sulla prima', E.render({ header: {} }, null).includes('<footer'));
verifica('le pagine interne hanno la testatina con il numero di pagina', pulito.includes('pag. 2'));
verifica('le colonne della pagina interna vengono applicate', pulito.includes('column-count:3'));
const fantasma = E.render(doc, null, { editable: true, ghost: { loc: 'colLeft', index: 1 } });
verifica('l\'anteprima mentre si scrive evidenzia solo l\'elemento in corso', conta(fantasma, 'ez-ghost') === 1);
verifica('pagesOnly restituisce l\'HTML di ogni pagina', E.render(doc, null, { pagesOnly: true }).length === 2);
verifica('modalità bianco e nero', E.render(doc, null, { bw: true }).includes('class="newspaper bw"'));

// ===================================================================
sezione('Immagini');
const art = (extra) => E.renderArticle(Object.assign({ title: 'T', text: 'a b c d e f\n\ng h i', image: 'uploads/x.jpg', imagePosition: 'top' }, extra));
verifica('immagine in cima prima del testo', art({}).indexOf('<figure') < art({}).indexOf('<p'));
verifica('immagine in fondo dopo il testo', art({ imagePosition: 'bottom' }).indexOf('<figure') > art({ imagePosition: 'bottom' }).lastIndexOf('<p'));
const mezzo = art({ imagePosition: 'middle' });
verifica('immagine in mezzo fra due paragrafi', mezzo.indexOf('<p') < mezzo.indexOf('<figure') && mezzo.indexOf('<figure') < mezzo.lastIndexOf('<p'));
verifica('le immagini retinate non ricevono filtri CSS', art({ imageBaked: true, imageStyle: 'woodcut' }).includes('img-baked') && !art({ imageBaked: true, imageStyle: 'woodcut' }).includes('img-woodcut'));
verifica('le immagini esterne mantengono i filtri storici', art({ image: 'https://a.it/x.jpg', imageStyle: 'woodcut' }).includes('img-woodcut'));
verifica('le virgolette nel percorso dell\'immagine vengono neutralizzate', !E.renderArticle({ text: 'x', image: 'a.jpg" onerror="alert(1)' }).includes('" onerror="'));

// ===================================================================
sezione('Adattamento all\'A4');
verifica('attivo di base', E.normalize({}).autoFit === true);
verifica('disattivabile per uscita', E.normalize({ autoFit: false }).autoFit === false);
const nf = E.normalize({ fit: [0.5, 1.4, 'x', 0.82] });
verifica('le scale vengono riportate fra il minimo e 1', JSON.stringify(nf.fit) === JSON.stringify([E.FIT_MIN, 1, 1, 0.82]), JSON.stringify(nf.fit));
const ridotta = E.render({ header: {}, fit: [0.8] }, null);
verifica('la pagina adattata viene ridotta e allargata in proporzione', ridotta.includes('zoom:0.8;width:calc(190mm / 0.8)'));
verifica('con l\'adattamento spento nessuna riduzione', !E.render({ header: {}, fit: [0.8], autoFit: false }, null).includes('zoom:'));
verifica('una pagina che sta già nel foglio non viene toccata', !E.render({ header: {}, fit: [1] }, null).includes('zoom:'));
verifica('ogni pagina ha la sua scala', (() => {
  const h = E.render({ header: {}, pages: [{ articles: [] }], fit: [0.9, 0.78] }, null);
  return h.includes('zoom:0.9;') && h.includes('zoom:0.78;');
})());
verifica('la riduzione vale anche nel libretto', E.renderBooklet({ header: {}, fit: [0.85] }, null).html.includes('zoom:0.85;'));
const limite = 1000;
const lineare = k => 1250 * k;                 // altezza proporzionale alla scala
const conRiflusso = k => 1250 * k * k;         // riducendo, il testo si riimpagina su righe più lunghe
const kL = E.fitScale(lineare, limite), kR = E.fitScale(conRiflusso, limite);
verifica('trova la scala più grande che sta nel foglio (altezza lineare)', kL >= 0.79 && kL <= 0.8 && lineare(kL) <= limite, String(kL));
verifica('...anche quando il testo si riimpagina (altezza non lineare)', kR >= 0.89 && kR <= 0.895 && conRiflusso(kR) <= limite, String(kR));
verifica('una pagina che sta già nel foglio resta al 100%', E.fitScale(k => 800 * k, limite) === 1);
verifica('se nemmeno al minimo basta, si ferma al minimo leggibile', E.fitScale(k => 5000 * k, limite) === E.FIT_MIN);

// ===================================================================
sezione('Indirizzi email nelle pagine diffuse');
const ENT = { '&#64;': '@', '&#46;': '.', '&amp;': '&', '&lt;': '<', '&gt;': '>', '&quot;': '"' };
const decodifica = t => t.replace(/&#64;|&#46;|&amp;|&lt;|&gt;|&quot;/g, x => ENT[x]);
const RE_EMAIL = /[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)+/g;
// ciò che vede (e copia) chi legge: il browser non mostra gli elementi hidden
const visto = h => decodifica(h.replace(/<span class="ez-esca" hidden>[^<]*<\/span>/g, '').replace(/<[^>]*>/g, ''));
// raccoglitore che cerca indirizzi nel sorgente così com'è
const grezzo = h => h.match(RE_EMAIL) || [];
// raccoglitore più furbo: toglie i tag, decodifica le entità, poi cerca
const furbo = h => decodifica(h.replace(/<[^>]*>/g, '')).match(RE_EMAIL) || [];
const indirizzo = 'redazione.segreta@posta.esempio.org';
const off = E.obfuscateEmails(`<p>Scrivici: ${indirizzo}, grazie.</p>`);
verifica('chi legge vede l\'indirizzo esatto', visto(off) === `Scrivici: ${indirizzo}, grazie.`, visto(off));
verifica('nel sorgente l\'indirizzo intero non compare', !off.includes(indirizzo) && !off.includes('@'));
verifica('un raccoglitore che legge il sorgente non trova indirizzi', grezzo(off).length === 0, grezzo(off).join(', '));
verifica('nemmeno togliendo i tag e decodificando trova l\'indirizzo vero', !furbo(off).includes(indirizzo), furbo(off).join(', '));
verifica('i frammenti nascosti usano l\'attributo hidden', (off.match(/class="ez-esca" hidden/g) || []).length === 2);
const link = E.obfuscateEmails('<a href="mailto:a@b.it">scrivi</a>');
verifica('un link mailto: resta valido (gli attributi non si toccano)', link === '<a href="mailto:a@b.it">scrivi</a>');
verifica('il testo senza indirizzi resta identico', E.obfuscateEmails('<p>Nessun indirizzo, solo @menzioni e 3.14</p>') === '<p>Nessun indirizzo, solo @menzioni e 3.14</p>');
const due = E.obfuscateEmails('a@b.it e c.d@e.f.org');
verifica('più indirizzi nello stesso testo', visto(due) === 'a@b.it e c.d@e.f.org' && grezzo(due).length === 0, visto(due));
const esportato = await E.exportDocument({ header: { anno: 'I', numero: '1' } }, { contact: indirizzo });
verifica('l\'export protegge il contatto della testata', !esportato.includes(indirizzo) && visto(esportato).includes(indirizzo));
verifica('l\'anteprima dell\'editor e la stampa non vengono toccate', E.render({}, { contact: indirizzo }).includes(indirizzo));

// ===================================================================
sezione('Libretto');
const ordine = k => JSON.stringify(E.bookletOrder(k).sides);
verifica('8 pagine: [8|1] [2|7] [6|3] [4|5]', ordine(8) === '[[8,1],[2,7],[6,3],[4,5]]', ordine(8));
verifica('1 pagina viene completata a 4', E.bookletOrder(1).n === 4);
verifica('5 pagine vengono completate a 8', E.bookletOrder(5).n === 8);
verifica('ogni pagina compare una sola volta', [4, 8, 12, 16].every(k => {
  const tutte = E.bookletOrder(k).sides.flat().sort((a, b) => a - b);
  return JSON.stringify(tutte) === JSON.stringify(Array.from({ length: k }, (_, i) => i + 1));
}));
const lib = E.renderBooklet(doc, null);
verifica('il libretto di 2 pagine occupa 1 foglio, 2 facciate', lib.sheets === 1 && conta(lib.html, 'class="sheet-side"') === 2);
verifica('le pagine mancanti sono bianche', conta(lib.html, 'page-blank') === 2);

// ===================================================================
const totale = passati + falliti.length;
console.log('\n' + '─'.repeat(60));
if (falliti.length) {
  console.log(`IMPAGINAZIONE: ${passati}/${totale} superati, ${falliti.length} falliti:`);
  falliti.forEach(f => console.log(`  ✗ ${f}`));
  process.exit(1);
}
console.log(`IMPAGINAZIONE: tutti i ${totale} test superati`);
