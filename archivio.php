<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Archivio</title>
  <link rel="stylesheet" href="assets/newspaper.css">
  <style>
    * { box-sizing: border-box; }
    body { background: #2c2c2c; font-family: 'Courier New', monospace; margin: 0; padding: 20px; color: #111; }
    .container { max-width: 1200px; margin: 0 auto; background: #fef9ef; padding: 20px; border: 1px solid #222; }
    h1 { color: #8b1f1f; border-left: 5px solid #8b1f1f; padding-left: 15px; margin: 0 0 0.6rem; font-size: 1.6rem; }
    h2 { font-size: 1.05rem; margin: 0 0 0.6rem; color: #8b1f1f; }
    a { color: #8b1f1f; }
    button, .btn {
      font: 0.8rem 'Courier New', monospace; padding: 5px 9px; margin: 2px; background: #8b1f1f; color: #fff;
      border: none; cursor: pointer; text-decoration: none; display: inline-block; border-radius: 2px;
    }
    button.secondary, .btn.secondary { background: #555; }
    button:hover, .btn:hover { filter: brightness(1.15); }
    button:focus-visible, .btn:focus-visible, input:focus-visible { outline: 2px solid #2c2c2c; outline-offset: 2px; }
    button:disabled { opacity: 0.5; cursor: wait; }
    input { font-family: monospace; padding: 6px; border: 1px solid #aaa; }
    .search { margin: 16px 0; display: flex; gap: 8px; flex-wrap: wrap; }
    .search input { flex: 1; min-width: 180px; }
    .keyword-cloud { margin: 16px 0; background: #f4efdf; padding: 10px; border: 1px solid #aaa; }
    .keyword { display: inline-block; margin: 4px; padding: 3px 8px; background: #ddd; border-radius: 12px; font-size: 0.85rem; }
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th, td { border: 1px solid #aaa; padding: 7px; text-align: left; vertical-align: top; }
    th { background: #e9e2cf; }
    td.num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    td.actions { min-width: 260px; }
    .empty { padding: 1.5rem; text-align: center; color: #666; }
    .tools { margin-top: 24px; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px; }
    .tool { border: 1px solid #aaa; background: #f4efdf; padding: 12px; }
    .tool p { font-size: 0.8rem; margin: 0 0 8px; }
    .result { font-size: 0.8rem; margin-top: 8px; white-space: pre-line; }
    .footer { margin-top: 20px; text-align: center; font-size: 0.8rem; }
    dialog { border: 1px solid #222; padding: 0; max-width: 760px; width: calc(100% - 32px); background: #fef9ef; }
    dialog::backdrop { background: rgba(0,0,0,0.5); }
    .dlg-head { display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: #e9e2cf; border-bottom: 1px solid #aaa; }
    .dlg-body { padding: 14px; max-height: 70vh; overflow: auto; }
    .rev { display: flex; justify-content: space-between; gap: 10px; align-items: center; border-bottom: 1px dashed #bbb; padding: 7px 0; font-size: 0.85rem; }
    .pub { font-size: 0.75rem; white-space: nowrap; }
    .pub.on { color: #1c6544; font-weight: bold; }
    .pub.stale { color: #8a5200; font-weight: bold; }
    .tool.wide { grid-column: 1 / -1; }
    .tool input[type=url] { width: 100%; margin: 4px 0; }
    .note { font-size: 0.75rem; background: #fff1c9; border: 1px solid #d9b44a; padding: 6px 8px; margin: 8px 0; }
    /* senza questa regola "display: inline-block" dei pulsanti vince su hidden */
    [hidden] { display: none !important; }
  </style>
</head>
<body>
<div class="container">
  <h1 id="pageTitle">📚 Archivio uscite</h1>
  <p><a href="index.html">← Torna all'editor</a></p>

  <div class="search" role="search">
    <input type="search" id="searchTitle" placeholder="Cerca nel titolo…" aria-label="Cerca nel titolo">
    <input type="search" id="searchKeyword" placeholder="Parola chiave (nel testo degli articoli)" aria-label="Parola chiave nel testo">
    <button id="searchBtn">🔍 Cerca</button>
    <button id="resetBtn" class="secondary">⟳ Mostra tutti</button>
  </div>

  <div class="keyword-cloud">
    <strong>📊 Parole più frequenti nell'archivio:</strong> <span id="cloudSpan">Caricamento…</span>
  </div>

  <div class="table-wrap">
    <table id="archiveTable">
      <thead>
        <tr><th>ID</th><th>Titolo</th><th>Data uscita</th><th>Ultima modifica</th><th>Parole</th><th>Caratteri</th><th>Sito</th><th>Azioni</th></tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
  <div class="empty" id="emptyMsg" hidden>Nessuna uscita in archivio.</div>

  <div class="tools">
    <div class="tool wide">
      <h2>🌐 Sito pubblico</h2>
      <p>Le uscite pubblicate formano un sito statico, che <b>questo server non serve mai</b>: lo scarichi e lo carichi su un hosting statico o un servizio onion. Ogni pagina è autonoma e non fa contattare a chi legge nessun sito terzo.</p>
      <p id="pubSummary">Caricamento…</p>
      <div class="note" id="pubContactNote" hidden></div>
      <label style="display:block;font-size:0.8rem">Indirizzo pubblico del sito (facoltativo: serve solo al feed RSS)
        <input type="url" id="pubUrl" placeholder="https://… oppure http://….onion/"></label>
      <label style="display:block;font-size:0.8rem;margin-top:4px"><input type="checkbox" id="pubNoindex"> Escludi dai motori di ricerca</label>
      <button id="pubSaveBtn" class="secondary">Salva impostazioni</button>
      <a class="btn" id="pubDownload" href="api/site_package.php">Scarica il sito (.zip)</a>
      <div class="result" id="pubResult"></div>
    </div>
    <div class="tool">
      <h2>💾 Backup</h2>
      <p>Un unico file .zip con uscite, cronologia delle versioni, impostazioni della testata e immagini caricate.</p>
      <a class="btn" href="api/backup.php">Scarica il backup</a>
    </div>
    <div class="tool">
      <h2>♻️ Ripristino</h2>
      <p>Aggiunge le uscite contenute nel backup senza cancellare nulla. Quelle già presenti vengono saltate.</p>
      <input type="file" id="restoreFile" accept=".zip,application/zip" aria-label="File di backup">
      <label style="display:block;font-size:0.8rem;margin-top:6px"><input type="checkbox" id="restoreSettings"> Sostituisci anche le impostazioni della testata</label>
      <button id="restoreBtn">Ripristina</button>
      <div class="result" id="restoreResult"></div>
    </div>
    <div class="tool">
      <h2>🧹 Immagini inutilizzate</h2>
      <p>Le immagini caricate e poi tolte dagli articoli restano sul server. Qui le trovi e le elimini.</p>
      <button id="cleanupCheckBtn" class="secondary">Controlla</button>
      <button id="cleanupDoBtn" hidden>Elimina</button>
      <div class="result" id="cleanupResult"></div>
    </div>
  </div>

  <div class="footer">Le parole più frequenti danno un'idea dei temi ricorrenti nell'archivio.</div>
</div>

<dialog id="revDialog" aria-labelledby="revTitle">
  <div class="dlg-head"><strong id="revTitle">Cronologia</strong><button class="secondary" id="revClose">Chiudi</button></div>
  <div class="dlg-body" id="revBody"></div>
</dialog>

<script src="assets/render.js"></script>
<script>
(() => {
  'use strict';
  const $ = id => document.getElementById(id);
  let allIssues = [];
  let settings = Ezine.masthead();
  let published = new Map();   // issue_id -> pubblicazione

  async function api(path, opts = {}) {
    const res = await fetch(path, opts);
    const out = await res.json().catch(() => ({}));
    if (!res.ok) { const e = new Error(out.error || `Errore HTTP ${res.status}`); e.status = res.status; throw e; }
    return out;
  }
  const postJson = (path, body) => api(path, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });

  const fmtDate = s => {
    if (!s) return '';
    const d = new Date(s.replace(' ', 'T') + 'Z');   // SQLite salva in UTC
    return isNaN(d) ? s : d.toLocaleString('it-IT', { dateStyle: 'short', timeStyle: 'short' });
  };
  const fmtBytes = b => b > 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';

  function renderTable(issues) {
    const tbody = document.querySelector('#archiveTable tbody');
    tbody.innerHTML = '';
    $('emptyMsg').hidden = issues.length > 0;
    for (const issue of issues) {
      const row = tbody.insertRow();
      row.insertCell().textContent = issue.id;
      row.insertCell().textContent = issue.title;
      row.insertCell().textContent = issue.data || '';
      row.insertCell().textContent = fmtDate(issue.updated_at);
      const w = row.insertCell(); w.className = 'num'; w.textContent = issue.word_count ?? '';
      const c = row.insertCell(); c.className = 'num'; c.textContent = issue.char_count ?? '';
      const pub = published.get(issue.id);
      const sc = row.insertCell();
      sc.className = 'pub' + (pub ? (pub.stale ? ' stale' : ' on') : '');
      sc.textContent = pub ? (pub.stale ? 'da aggiornare' : 'pubblicata') : '—';
      if (pub) sc.title = `${pub.slug}.html · pubblicata il ${fmtDate(pub.published_at)}${pub.stale ? ' · modificata dopo la pubblicazione' : ''}`;
      const actions = row.insertCell();
      actions.className = 'actions';
      const add = (label, fn, cls) => {
        const b = document.createElement('button');
        b.textContent = label;
        if (cls) b.className = cls;
        b.addEventListener('click', fn);
        actions.appendChild(b);
      };
      add('👁️ Visualizza', () => openRendered(issue.id, false));
      add('✏️ Modifica', () => { location.href = `index.html?edit=${issue.id}`; });
      add('📄 Duplica', () => { location.href = `index.html?from=${issue.id}`; }, 'secondary');
      add('🕘 Cronologia', () => showRevisions(issue));
      add('🖨️ Stampa', () => openRendered(issue.id, true));
      if (pub) {
        add('🔁 Ripubblica', () => publish(issue));
        add('⛔ Ritira', () => unpublish(issue), 'secondary');
      } else {
        add('🌐 Pubblica', () => publish(issue));
      }
      add('🗑️ Elimina', () => deleteIssue(issue));
    }
  }

  async function loadPublications() {
    try {
      const r = await api('api/publications.php');
      published = new Map(r.items.map(p => [p.issue_id, p]));
      $('pubUrl').value = r.config.publicUrl;
      $('pubNoindex').checked = r.config.noindex;
      const n = r.items.length, stale = r.items.filter(p => p.stale).length;
      $('pubSummary').textContent = n
        ? `${n} uscit${n === 1 ? 'a pubblicata' : 'e pubblicate'}${stale ? `, di cui ${stale} modificat${stale === 1 ? 'a' : 'e'} dopo la pubblicazione (va ripubblicat${stale === 1 ? 'a' : 'e'} per aggiornare il sito)` : ''}.`
        : 'Nessuna uscita pubblicata: usa “🌐 Pubblica” nella tabella.';
      $('pubDownload').hidden = n === 0;
      const contatto = (settings.contact || '').trim();
      $('pubContactNote').hidden = !contatto;
      $('pubContactNote').textContent = `Le pagine pubblicate mostrano il contatto “${contatto}” impostato nella testata. Se non vuoi diffonderlo, svuota il campo nelle impostazioni dell'editor e ripubblica.`;
    } catch (e) { $('pubSummary').textContent = 'Stato della pubblicazione non disponibile: ' + e.message; }
  }

  function externalImages(content) {
    const c = Ezine.normalize(content);
    const arts = [c.fullWidth, ...c.colLeft, ...c.colRight, ...c.pages.flatMap(p => p.articles)].filter(Boolean);
    return arts.filter(a => /^https?:\/\//i.test(a.image || '')).map(a => a.image);
  }

  async function publish(issue) {
    try {
      const r = await api(`api/load_issue.php?id=${issue.id}`);
      const ext = externalImages(r.content);
      if (ext.length && !confirm(`L'uscita contiene ${ext.length} immagin${ext.length === 1 ? 'e esterna' : 'i esterne'}.\n\nNella versione pubblica non verr${ext.length === 1 ? 'à mostrata' : 'anno mostrate'}: i lettori dovrebbero scaricarle da siti terzi, che vedrebbero il loro indirizzo IP. Per includerle, caricale dal computer nell'editor.\n\nPubblicare comunque?`)) return;
      const mh = r.content.masthead || settings;
      const h = Ezine.normalize(r.content).header;
      const html = await Ezine.exportDocument(r.content, mh, { title: `${Ezine.masthead(mh).fullName} – Anno ${h.anno} N. ${h.numero}` });
      const out = await postJson('api/publish.php', { issue_id: issue.id, html });
      await loadPublications();
      renderTable(allIssues);
      $('pubResult').textContent = `Uscita #${issue.id} pubblicata come ${out.slug}.html. Scarica di nuovo il sito per aggiornare la copia online.`;
    } catch (e) { alert('Pubblicazione non riuscita: ' + e.message); }
  }

  async function unpublish(issue) {
    if (!confirm(`Ritirare l'uscita #${issue.id} dal sito pubblico?\n\nSparirà dal prossimo pacchetto scaricato. Le copie già caricate online vanno aggiornate a mano.`)) return;
    try {
      await postJson('api/publish.php', { issue_id: issue.id, action: 'unpublish' });
      await loadPublications();
      renderTable(allIssues);
      $('pubResult').textContent = `Uscita #${issue.id} ritirata.`;
    } catch (e) { alert('Ritiro non riuscito: ' + e.message); }
  }

  async function savePublishing() {
    try {
      const r = await postJson('api/publications.php', { config: { publicUrl: $('pubUrl').value.trim(), noindex: $('pubNoindex').checked } });
      $('pubUrl').value = r.config.publicUrl;
      $('pubResult').textContent = 'Impostazioni del sito salvate.' + (r.config.publicUrl ? ' Il pacchetto includerà il feed RSS.' : ' Senza indirizzo pubblico il pacchetto non include il feed RSS.');
    } catch (e) { $('pubResult').textContent = 'Impostazioni non salvate: ' + e.message; }
  }

  async function loadStats() {
    await loadPublications();
    try {
      allIssues = await api('api/list_issues.php');
      renderTable(allIssues);
    } catch (e) {
      $('emptyMsg').hidden = false;
      $('emptyMsg').textContent = 'Archivio non disponibile: ' + e.message;
    }
    loadKeywords();
  }

  async function loadKeywords() {
    const span = $('cloudSpan');
    try {
      const words = await api('api/keywords.php');
      span.innerHTML = '';
      if (!words.length) { span.textContent = 'nessuna parola ancora.'; return; }
      for (const { word, count } of words) {
        const s = document.createElement('span');
        s.className = 'keyword';
        s.textContent = `${word} (${count})`;
        span.appendChild(s);
      }
    } catch (e) { span.textContent = 'non disponibile.'; }
  }

  // Apre l'uscita in una finestra; le uscite archiviate usano la testata con cui
  // sono state salvate, non quella attuale.
  async function openRendered(id, print, revisionId) {
    const w = window.open('', '_blank');
    if (!w) { alert('Il browser ha bloccato la finestra: consenti i pop-up per questo sito.'); return; }
    w.document.write('<p style="font:16px sans-serif;padding:2rem">Caricamento…</p>');
    try {
      const r = revisionId ? await api(`api/revisions.php?id=${revisionId}`) : await api(`api/load_issue.php?id=${id}`);
      const mh = r.content && r.content.masthead ? r.content.masthead : settings;
      const title = revisionId ? `Uscita ${id} – versione del ${fmtDate(r.saved_at)}` : `Uscita ${id} – ${r.title}`;
      const html = print
        ? await Ezine.printDocument(r.content, mh, { title })
        : await Ezine.exportDocument(r.content, mh, { title });
      w.document.open(); w.document.write(html); w.document.close();
    } catch (e) {
      w.close();
      alert(`Impossibile aprire l'uscita ${id}: ${e.message}`);
    }
  }

  async function deleteIssue(issue) {
    const pubNote = published.has(issue.id) ? '\nÈ pubblicata: sparirà anche dal sito pubblico al prossimo pacchetto.' : '';
    if (!confirm(`Eliminare l'uscita #${issue.id} «${issue.title}»?\n\nVerrà eliminata anche la sua cronologia. Le immagini restano finché non usi la pulizia.${pubNote}`)) return;
    try { await postJson('api/delete_issue.php', { id: issue.id }); loadStats(); }
    catch (e) { alert('Eliminazione non riuscita: ' + e.message); }
  }

  async function showRevisions(issue) {
    $('revTitle').textContent = `Cronologia di #${issue.id} «${issue.title}»`;
    const body = $('revBody');
    body.textContent = 'Caricamento…';
    $('revDialog').showModal();
    try {
      const revs = await api(`api/revisions.php?issue_id=${issue.id}`);
      body.innerHTML = '';
      if (!revs.length) { body.textContent = 'Nessuna versione precedente: la cronologia si riempie a ogni “Aggiorna”.'; return; }
      const intro = document.createElement('p');
      intro.style.fontSize = '0.8rem';
      intro.textContent = 'Ogni aggiornamento conserva la versione che stava sostituendo (fino a 30). Ripristinarne una salva prima quella attuale, quindi si può sempre tornare indietro.';
      body.appendChild(intro);
      for (const r of revs) {
        const row = document.createElement('div');
        row.className = 'rev';
        const info = document.createElement('span');
        info.textContent = `${fmtDate(r.saved_at)} · ${r.title}${r.reason ? ` · ${r.reason}` : ''}`;
        const btns = document.createElement('span');
        const see = document.createElement('button');
        see.className = 'secondary'; see.textContent = '👁️ Visualizza';
        see.addEventListener('click', () => openRendered(issue.id, false, r.id));
        const rest = document.createElement('button');
        rest.textContent = '↩️ Ripristina';
        rest.addEventListener('click', async () => {
          if (!confirm(`Ripristinare la versione del ${fmtDate(r.saved_at)}?`)) return;
          try {
            await postJson('api/restore_revision.php', { revision_id: r.id });
            $('revDialog').close();
            loadStats();
            alert('Versione ripristinata. Quella che c\'era prima è ora nella cronologia.');
          } catch (e) { alert('Ripristino non riuscito: ' + e.message); }
        });
        btns.append(see, rest);
        row.append(info, btns);
        body.appendChild(row);
      }
    } catch (e) { body.textContent = 'Cronologia non disponibile: ' + e.message; }
  }

  async function search() {
    const titleFilter = $('searchTitle').value.toLowerCase();
    const keyword = $('searchKeyword').value.trim();
    let filtered = allIssues;
    if (keyword) {
      try { filtered = await api('api/search_issues.php?q=' + encodeURIComponent(keyword)); }
      catch (e) { alert('Ricerca non riuscita: ' + e.message); return; }
    }
    if (titleFilter) filtered = filtered.filter(i => (i.title || '').toLowerCase().includes(titleFilter));
    renderTable(filtered);
  }

  async function restore() {
    const f = $('restoreFile').files[0];
    if (!f) { alert('Scegli prima un file di backup (.zip).'); return; }
    const fd = new FormData();
    fd.append('backup', f);
    if ($('restoreSettings').checked) fd.append('restore_settings', '1');
    $('restoreBtn').disabled = true;
    $('restoreResult').textContent = 'Ripristino in corso…';
    try {
      const r = await api('api/restore_backup.php', { method: 'POST', body: fd });
      $('restoreResult').textContent =
        `Uscite aggiunte: ${r.issues_imported} (già presenti, saltate: ${r.issues_skipped})\n` +
        `Versioni in cronologia: ${r.revisions_imported}\n` +
        `Pubblicazioni: ${r.publications_imported}\n` +
        `Immagini aggiunte: ${r.images_imported} (già presenti: ${r.images_skipped}${r.images_invalid ? `, non valide: ${r.images_invalid}` : ''})\n` +
        `Impostazioni: ${r.settings_restored ? 'ripristinate' : 'invariate'}`;
      loadStats();
    } catch (e) { $('restoreResult').textContent = 'Ripristino non riuscito: ' + e.message; }
    finally { $('restoreBtn').disabled = false; }
  }

  async function cleanupCheck() {
    $('cleanupResult').textContent = 'Controllo…';
    try {
      const r = await api('api/cleanup_images.php');
      $('cleanupDoBtn').hidden = r.count === 0;
      $('cleanupResult').textContent = r.count
        ? `${r.count} immagini non più usate (${fmtBytes(r.bytes)}). ${r.in_use} in uso, non verranno toccate.`
        : `Nessuna immagine inutilizzata. ${r.in_use} in uso.`;
    } catch (e) { $('cleanupResult').textContent = 'Controllo non riuscito: ' + e.message; }
  }

  async function cleanupDo() {
    if (!confirm('Eliminare definitivamente le immagini inutilizzate? Non è reversibile, se non da un backup.')) return;
    try {
      const r = await postJson('api/cleanup_images.php', { confirm: true });
      $('cleanupDoBtn').hidden = true;
      $('cleanupResult').textContent = `Eliminate ${r.deleted} immagini (${fmtBytes(r.bytes)}).`;
    } catch (e) { $('cleanupResult').textContent = 'Eliminazione non riuscita: ' + e.message; }
  }

  $('searchBtn').addEventListener('click', search);
  for (const id of ['searchTitle', 'searchKeyword']) $(id).addEventListener('keydown', e => { if (e.key === 'Enter') search(); });
  $('resetBtn').addEventListener('click', () => { $('searchTitle').value = ''; $('searchKeyword').value = ''; renderTable(allIssues); });
  $('revClose').addEventListener('click', () => $('revDialog').close());
  $('restoreBtn').addEventListener('click', restore);
  $('cleanupCheckBtn').addEventListener('click', cleanupCheck);
  $('cleanupDoBtn').addEventListener('click', cleanupDo);
  $('pubSaveBtn').addEventListener('click', savePublishing);

  (async () => {
    try { settings = Ezine.masthead((await api('api/settings.php')).masthead); } catch (e) { /* valori neutri */ }
    document.title = `Archivio – ${settings.fullName}`;
    $('pageTitle').textContent = `📚 Archivio uscite – ${settings.fullName}`;
    Ezine.favicon(settings.nameA || settings.fullName, '#8b1f1f');
    loadStats();
  })();
})();
</script>
</body>
</html>
