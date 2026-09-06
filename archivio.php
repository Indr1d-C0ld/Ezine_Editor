<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archivio – La Mia Ezine</title>
    <link rel="icon" type="image/png" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAADoElEQVR4nO2bz0sbQRzFX2KsJpAaI1JUiKlRUER6qLQlYKBHDzlZ8CIIXjzkoAdz9R9oPetREMSDBRUKBfGgYNAW1PoDexASQ2maQmnqVsQkJj2tDXF3MzOZze4m+zkps3znvbff3Zlks4BJbWOp5GRvOzvzpMeGLy8rok3VSWgMl0KtQFQpytN4MbyD4FZMTdNy8AjDykOIFuZ5zVtWgloZl4K1G5g7QE/mAXY9TAHozbwIiy7qAPRqXoRWH1UAejcvQqOTOACjmBch1UsUgNHMi5DoLhmAUc2LlNKvGIDRzYso+eCyEzQysrsn0rM/PDeH/pERpsk/zszgdHVVcszt82Fia4uozjuvl+g4qd2iZAdUS+sXI+VLsgPKCSB0eAh7c7Pk2PnaGj5MTzPVfT4xgdezswCA9+PjiG5vM9Up7oKavwc8CKBa21+k2J/ZAYX/VPvZFyn0aXaA1gK0xgxA/KNWrn8R0a/ZAVoL0BozAK0FaI0ZAPeKeYXFxML+IMpi/S81rzQHJdwDyNzcyI7V2+3MdesdDqI5aOEewK0gyI4VmqClMLzbqyvmOsXwD0BBnLO9nbnu444Oojlo4R7Ar4sL2TGXxwOrzcZUt7mrCwCQvr7G32SSqYYU9wHw+uXFj6Mj+clsNnQMDlLXbGxqQmtvLwAgeXKCfC7HKu8e0S/3Dojv7SmuBL3BIHXNnuHh+875tr/PrE0K7gH8iccR3dmRHR8YHYXb5yOuV+9wwD81BQDIZbM4XlkpW2MhqmyEPs3Py3aB1WbDm6UltPT0lKzT6HJhZHERzrY2AMDXjQ0IiQRXrQ+ue14fi4fCYbwMhWTHc9ksztfXcbG5ieTxMW5SKeQyGTS6XGjp7oY3EMCzsTE0OJ0AgFQshqVgUHGZJaXwfqdaAJa6OgyFw3gxOVnWDhAAfp6dYSMUQioW4yFNOQCA75cjHr8fr0IhePx+6iCERAJflpfxeWEBd5kMFz3Fqx3bokxBPBJBPBKBy+vF00AATwYG0NrXB7vbjQanE48cDmTTaaQFAbeCgN/RKJKnp/h+cIDL3V3k7+5U1ad6ACKpWAyHnFqYJ5KrQKV+qFxpiJ8Oyx1sZOT8mF+IKA1WSxco+SjZAUYPoZR+okvAqCGQ6Ca+BxgtBFK9VDdBo4RAo5N6FdB7CLT6mJZBvYbAoot5H6C3EFj1cDGh5aP1ck8El52gVt3AY17zvUGexYqp2TdH5dDju8M1zz/rTU5pTXeKhgAAAABJRU5ErkJggg==">
    <style>
        body { background: #2c2c2c; font-family: 'Courier New', monospace; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: #fef9ef; padding: 20px; border: 1px solid #222; }
        h1 { color: #8b1f1f; border-left: 5px solid #8b1f1f; padding-left: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #aaa; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #e9e2cf; }
        .actions button { margin: 2px; padding: 4px 8px; background: #8b1f1f; color: white; border: none; cursor: pointer; }
        .search { margin: 20px 0; display: flex; gap: 10px; flex-wrap: wrap; }
        .search input, .search select { padding: 6px; font-family: monospace; }
        .keyword-cloud { margin: 20px 0; background: #f4efdf; padding: 10px; border: 1px solid #aaa; }
        .keyword { display: inline-block; margin: 5px; padding: 3px 8px; background: #ddd; border-radius: 12px; cursor: pointer; }
        .keyword:hover { background: #8b1f1f; color: white; }
        .footer { margin-top: 20px; text-align: center; }
        a { color: #8b1f1f; text-decoration: none; }
    </style>
    <style id="newspaper-css">
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #e6e3db; font-family: 'Courier New', Courier, 'Lucida Sans Typewriter', 'Lucida Typewriter', monospace; line-height: 1.35; color: #111; padding: 1.5rem; }
        .newspaper { max-width: 1100px; margin: 0 auto; background: #fef9ef; padding: 2rem 1.5rem; box-shadow: 0 0 15px rgba(0,0,0,0.2); border: 1px solid #222; }
        .header { text-align: center; border-bottom: 4px double #111; margin-bottom: 1.2rem; padding-bottom: 0.8rem; }
        .title-wrapper { display: flex; align-items: center; justify-content: center; gap: 15px; flex-wrap: wrap; margin-bottom: 0.2rem; }
        .header-logo { height: 60px; width: auto; filter: grayscale(0.1); }
        .title { font-size: 4.2rem; font-weight: 800; letter-spacing: -1px; font-family: 'Georgia', 'Clarendon', 'Times New Roman', serif; text-transform: uppercase; line-height: 1.1; text-shadow: 0.02em 0.02em 0px rgba(0,0,0,0.08), -0.02em -0.01em 0px rgba(0,0,0,0.05), 0.04em 0.03em 0px rgba(0,0,0,0.02); transform: rotate(0.2deg); }
        .title .underground { color: #8b1f1f; display: inline-block; }
        .title .observer { color: #000000; display: inline-block; }
        .motto { font-style: italic; font-size: 0.7rem; letter-spacing: 0px; font-family: 'Courier New', Courier, monospace; margin-top: 0.2rem; color: #444; }
        .subhead { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; font-weight: bold; border-top: 1px solid #333; border-bottom: 1px solid #333; display: inline-block; padding: 0.2rem 0; margin-top: 0.2rem; font-family: 'Courier New', Courier, monospace; }
        .edition-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.7rem;
            font-family: monospace;
            margin-top: 0.5rem;
            border-top: 1px solid #888;
            padding-top: 0.5rem;
        }
        .edition-info span:first-child { flex: 1; text-align: left; }
        .edition-info span:last-child { flex: 1; text-align: right; }
        .edition-info span:not(:first-child):not(:last-child) { flex: 1; text-align: center; }
        article { margin-bottom: 1rem; }
        .full-width { margin-bottom: 1.5rem; border-bottom: 1px dashed #aaa; padding-bottom: 0.8rem; }
        .fullwidth-columns {
            column-gap: 1.8rem;
            column-rule: 1px solid #ccc;
            font-size: 0.82rem;
        }
        .fullwidth-columns p { break-inside: avoid; margin-bottom: 0.6rem; }
        h2 { font-size: 1.5rem; font-weight: 800; text-transform: uppercase; border-left: 5px solid #c00; padding-left: 0.5rem; margin: 0.7rem 0 0.4rem 0; }
        .kicker { font-size: 0.7rem; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #a00; }
        .byline { font-size: 0.7rem; font-weight: bold; border-bottom: 1px dotted #aaa; margin-bottom: 0.4rem; display: inline-block; }
        p { font-size: 0.82rem; text-align: justify; margin-bottom: 0.6rem; }
        .feature-box { border: 2px solid #111; padding: 0.8rem; background: #f4efdf; margin: 1rem 0; font-family: 'Courier New', Courier, monospace; }
        .feature-box h3 { font-size: 1rem; font-weight: 800; text-transform: uppercase; background: #111; color: #fef9ef; display: inline-block; padding: 0.1rem 0.4rem; letter-spacing: 1px; margin-bottom: 0.5rem; }
        hr { margin: 1rem 0; border: none; border-top: 1px solid #222; }
        .fake-ad { font-size: 0.7rem; text-align: center; border: 1px dashed #444; padding: 0.4rem; margin: 0.6rem 0; background: #e9e2cf; }
        .fake-ad-colored { background: #d4b8b8 !important; border: 1px solid #8b1f1f !important; color: #2c0a0a; }
        footer { margin-top: 2rem; border-top: 2px solid #111; padding-top: 0.8rem; font-size: 0.65rem; text-align: center; font-family: monospace; }
        @media screen { .columns-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; margin: 1.2rem 0; } .columns-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin: 1rem 0; } }
        @media print { body { background: white; padding: 0; margin: 0; } .newspaper { max-width: 100%; padding: 0.8rem; box-shadow: none; background: white; } .columns-2, .columns-3 { display: block; column-count: 2; column-gap: 1.8rem; column-rule: 1px solid #aaa; } .columns-3 { column-count: 3; } article, .fake-ad { break-inside: avoid; page-break-inside: avoid; } .feature-box, .header, footer, hr { break-inside: avoid; } .title { transform: none; } }
        .article-img { max-width: 100%; margin: 0.5rem 0; border: 1px solid #aaa; display: block; }
        .float-left { float: left; margin: 0.5rem 1rem 0.5rem 0; max-width: 45%; }
        .float-right { float: right; margin: 0.5rem 0 0.5rem 1rem; max-width: 45%; }
        .clearfix::after { content: ""; clear: both; display: table; }
        .img-woodcut { filter: grayscale(1) contrast(300%) brightness(90%) !important; image-rendering: crisp-edges; }
        .img-bitmap { filter: grayscale(1) contrast(200%) brightness(80%) !important; image-rendering: pixelated; }
        .caption { font-size: 0.65rem; font-style: italic; text-align: center; margin-top: -0.2rem; margin-bottom: 0.5rem; color: #444; }
        .image-wrapper { margin: 0.5rem 0; display: inline-block; width: 100%; }
        .float-left .image-wrapper, .float-right .image-wrapper { display: block; width: auto; }
    </style>
</head>
<body>
<div class="container">
    <h1>📚 Archivio uscite – La Mia Ezine</h1>
    <p><a href="index.html">← Torna all'editor</a></p>

    <div class="search">
        <input type="text" id="searchTitle" placeholder="Cerca nel titolo...">
        <input type="text" id="searchKeyword" placeholder="Parola chiave (nel contenuto)">
        <button id="searchBtn">🔍 Cerca</button>
        <button id="resetBtn">⟳ Mostra tutti</button>
    </div>

    <div id="keywordCloud" class="keyword-cloud">
        <strong>📊 Parole più frequenti (globali):</strong> <span id="cloudSpan">Caricamento...</span>
    </div>

    <table id="archiveTable">
        <thead>
            <tr><th>ID</th><th>Titolo</th><th>Data uscita</th><th>Caratteri</th><th>Parole</th><th>Dimensione (KB)</th><th>Azioni</th></tr>
        </thead>
        <tbody></tbody>
    </table>
    <div class="footer">Archivio dinamico – Clicca su una parola chiave per filtrare le uscite che la contengono più frequentemente.</div>
</div>

<script>
    let allIssues = [];

    async function loadStats() {
        const res = await fetch('stats.php');
        allIssues = await res.json();
        renderTable(allIssues);
        computeGlobalKeywords(allIssues);
    }

    function renderTable(issues) {
        const tbody = document.querySelector('#archiveTable tbody');
        tbody.innerHTML = '';
        for (let issue of issues) {
            const row = tbody.insertRow();
            row.insertCell(0).innerText = issue.id;
            row.insertCell(1).innerText = issue.title;
            row.insertCell(2).innerText = issue.data;
            row.insertCell(3).innerText = issue.char_count;
            row.insertCell(4).innerText = issue.word_count;
            row.insertCell(5).innerText = issue.size_kb;
            const actions = row.insertCell(6);
            const viewBtn = document.createElement('button');
            viewBtn.innerText = '👁️ Visualizza';
            viewBtn.onclick = () => viewIssue(issue.id);
            const editBtn = document.createElement('button');
            editBtn.innerText = '✏️ Modifica';
            editBtn.onclick = () => editIssue(issue.id);
            const printBtn = document.createElement('button');
            printBtn.innerText = '🖨️ Stampa';
            printBtn.onclick = () => printIssue(issue.id);
            const deleteBtn = document.createElement('button');
            deleteBtn.innerText = '🗑️ Elimina';
            deleteBtn.onclick = () => deleteIssue(issue.id);
            actions.append(viewBtn, editBtn, printBtn, deleteBtn);
        }
    }

    function renderFullNewspaper(data) {
        let html = '';

        const h = data.header;
        const titleColor = h.titleColor || "#8b1f1f";
        html += `
            <div class="header">
                <div class="title-wrapper">
                    <img class="header-logo" src="UO LOGO.png" alt="UO Logo" onerror="this.style.display='none'">
                    <div class="title">
                        <span class="underground" style="color: ${titleColor};">UNDERGROUND</span><span class="observer"> OBSERVER</span>
                    </div>
                </div>
                <div class="motto">“Il tuo giornale, a modo tuo”</div>
                <div class="subhead">INDIPENDENTE • LIBERO • PERSONALE</div>
                <div class="edition-info">
                    <span>Anno ${h.anno} – Numero ${h.numero}</span>
                    <span>${h.data}</span>
                    <span>Libero</span>
                </div>
                <div class="edition-info">
                    <span>📧 email@esempio.it</span>
                    <span>La Mia Ezine © ${new Date().getFullYear()}</span>
                </div>
            </div>`;

        function getImageClass(style) {
            if (style === 'woodcut') return 'img-woodcut';
            if (style === 'bitmap') return 'img-bitmap';
            return '';
        }
        function renderImageBlock(art) {
            if (!art.image) return '';
            const imgClass = getImageClass(art.imageStyle);
            const floatClass = (art.imageFloat === 'left' || art.imageFloat === 'right') ? `float-${art.imageFloat}` : '';
            const captionHtml = art.imageCaption ? `<div class="caption">${art.imageCaption}</div>` : '';
            return `<div class="image-wrapper ${floatClass}"><img class="article-img ${imgClass}" src="${art.image}" alt="illustrazione">${captionHtml}</div>`;
        }
        function insertImageInline(text, imgBlock) {
            if (!imgBlock) return text;
            const mid = Math.floor(text.length / 2);
            let pos = text.lastIndexOf(' ', mid);
            if (pos === -1) pos = mid;
            return text.slice(0, pos) + ' ' + imgBlock + ' ' + text.slice(pos);
        }
        function renderArticleContent(art, isFullWidth = false) {
            if (!art) return '';
            let content = '';
            if (art.kicker) content += `<div class="kicker">${art.kicker}</div>`;
            if (art.title) {
                if (isFullWidth && art.fwTitleSize) {
                    content += `<h2 style="font-size: ${art.fwTitleSize}; text-align: ${art.fwTitleAlign || 'center'}; border-left: none; padding-left: 0;">${art.title}</h2>`;
                } else {
                    content += `<h2>${art.title}</h2>`;
                }
            }
            if (art.byline) content += `<div class="byline">${art.byline}</div>`;
            const imgBlock = renderImageBlock(art);
            const pos = art.imagePosition || 'top';
            const isFloated = (art.imageFloat === 'left' || art.imageFloat === 'right');
            const textAlignStyle = (isFullWidth && art.fwTextAlign) ? `style="text-align: ${art.fwTextAlign};"` : '';

            if (isFullWidth && art.fwColumns && art.fwColumns !== '1') {
                const columnCount = art.fwColumns;
                if (pos === 'top') {
                    if (imgBlock) content += imgBlock;
                    content += `<div class="fullwidth-columns" style="column-count: ${columnCount};">${art.text.replace(/\n/g, '<br>')}</div>`;
                } else if (pos === 'bottom') {
                    content += `<div class="fullwidth-columns" style="column-count: ${columnCount};">${art.text.replace(/\n/g, '<br>')}</div>`;
                    if (imgBlock) content += imgBlock;
                } else {
                    if (imgBlock) {
                        const textWithImage = insertImageInline(art.text, imgBlock);
                        content += `<div class="fullwidth-columns" style="column-count: ${columnCount};">${textWithImage.replace(/\n/g, '<br>')}</div>`;
                    } else {
                        content += `<div class="fullwidth-columns" style="column-count: ${columnCount};">${art.text.replace(/\n/g, '<br>')}</div>`;
                    }
                }
            } else {
                if (pos === 'top') {
                    if (imgBlock) content += imgBlock;
                    content += `<p ${textAlignStyle}>${art.text}</p>`;
                } else if (pos === 'bottom') {
                    content += `<p ${textAlignStyle}>${art.text}</p>`;
                    if (imgBlock) content += imgBlock;
                } else {
                    if (imgBlock) {
                        if (isFloated) {
                            const textWithImage = insertImageInline(art.text, imgBlock);
                            content += `<p ${textAlignStyle}>${textWithImage}</p>`;
                        } else {
                            const parts = art.text.split(/\n\s*\n/);
                            if (parts.length > 1) {
                                parts.splice(1, 0, imgBlock);
                                content += `<p ${textAlignStyle}>${parts.join('</p><p ' + textAlignStyle + '>')}</p>`;
                            } else {
                                const mid = Math.floor(art.text.length / 2);
                                let pos = art.text.lastIndexOf(' ', mid);
                                if (pos === -1) pos = mid;
                                content += `<p ${textAlignStyle}>${art.text.slice(0, pos)}</p>${imgBlock}<p ${textAlignStyle}>${art.text.slice(pos)}</p>`;
                            }
                        }
                    } else {
                        content += `<p ${textAlignStyle}>${art.text}</p>`;
                    }
                }
                if (isFloated && (pos === 'top' || pos === 'middle')) content += `<div class="clearfix"></div>`;
            }
            return content;
        }

        if (data.fullWidth) {
            html += `<div class="full-width">${renderArticleContent(data.fullWidth, true)}</div>`;
        }

        html += `<div class="columns-2"><div>`;
        if (data.colLeft) {
            data.colLeft.forEach(art => { html += `<article>${renderArticleContent(art)}</article>`; });
        }
        html += `</div><div>`;
        if (data.colRight) {
            data.colRight.forEach(art => { html += `<article>${renderArticleContent(art)}</article>`; });
        }
        html += `</div></div>`;

        if (data.fakeAd && data.fakeAd.enabled) {
            const coloredClass = data.fakeAd.colored ? 'fake-ad-colored' : '';
            html += `<div class="fake-ad ${coloredClass}">${data.fakeAd.text}</div>`;
        }

        html += `<div class="feature-box"><h3>✊ STRAIGHT FROM THE MAN</h3><p>${data.straightFromTheMan.text}</p></div><hr>`;

        html += `<div class="columns-3">`;
        html += `<div><h2>🌍 ROUNDUP</h2><div>`;
        if (data.roundup) { data.roundup.forEach(item => { html += `<p>${item.text}</p>`; }); }
        html += `</div></div>`;
        html += `<div><h2>📬 LETTERE</h2><div>`;
        if (data.letters) { data.letters.forEach(item => { html += `<p>${item.text}</p>`; }); }
        html += `</div></div>`;
        html += `<div><h2>⚡ FIGHT THE POWER</h2><div>`;
        if (data.fight) { data.fight.forEach(item => { html += `<p>${item.text}</p>`; }); }
        html += `</div></div></div>`;

        html += `<footer><div class="edition-info">`;
        html += `<span>➤ PROSSIMO NUMERO: ${data.nextIssue || ''}</span>`;
        html += `<span>➤ RUBRICA FISSA: ${data.fixedRubric || ''}</span>`;
        html += `</div>`;
        html += `<p style="margin-top:0.8rem;">La Mia Ezine non si assume responsabilità per le allucinazioni uditive e visive derivanti dalla lettura ad alta voce di questo foglio. Vietato fotocopiare per fini commerciali – incoraggiato fotocopiare per fini sovversivi.</p></footer>`;
        return html;
    }

    async function viewIssue(id) {
        const res = await fetch(`load_issue.php?id=${id}`);
        const data = await res.json();
        if (data.error) { alert(data.error); return; }
        const fullHtml = renderFullNewspaper(data.content);
        const css = document.getElementById('newspaper-css').innerHTML;
        const win = window.open('', '_blank');
        win.document.write(`<!DOCTYPE html><html><head><title>Uscita ${id} – La Mia Ezine</title><style>${css}</style></head><body><div class="newspaper">${fullHtml}</div></body></html>`);
        win.document.close();
    }

    async function editIssue(id) { window.location.href = `index.html?edit=${id}`; }
    async function printIssue(id) {
        const res = await fetch(`load_issue.php?id=${id}`);
        const data = await res.json();
        const fullHtml = renderFullNewspaper(data.content);
        const css = document.getElementById('newspaper-css').innerHTML;
        const win = window.open('', '_blank');
        win.document.write(`<!DOCTYPE html><html><head><title>Stampa uscita ${id}</title><style>${css}</style><style>body{margin:0;padding:1rem;background:white;}</style></head><body><div class="newspaper">${fullHtml}</div></body></html>`);
        win.document.close();
        win.print();
    }
    async function deleteIssue(id) {
        if (confirm(`Eliminare l'uscita ID ${id}?`)) {
            const res = await fetch('delete_issue.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id: id}) });
            const result = await res.json();
            if (result.success) loadStats();
            else alert('Errore: ' + result.error);
        }
    }

    async function computeGlobalKeywords(issues) {
        const stopwords = new Set(['il','lo','la','i','gli','le','un','uno','una','un','e','ed','o','ma','per','con','su','tra','fra','da','a','in','di','che','è','non','si','ci','ciò','questo','questa','questi','queste','quello','quella','quelli','quelle','io','tu','lui','lei','noi','voi','loro','mio','tuo','suo','nostro','vostro','loro','me','te','se','ne','gli','della','delle','dei','degli','alla','alle','ai','agli','dalla','dalle','dai','dagli','sulla','sulle','sui','sugli','essere','avere','fare','dire','potere','volere','sapere','stare','andare','venire','parte','cosa','tempo','anno','giorno','persona','modo','casa','vita','mondo','paese','stato','città','punto','fine','nome','fatto','caso','forza','valore','libro','parola','mano','occhio','testa','cuore','aria','acqua','fuoco','terra','cielo','mare','sole','luna','stella']);
        let wordFreq = new Map();
        for (let issue of issues) {
            const res = await fetch(`load_issue.php?id=${issue.id}`);
            const data = await res.json();
            const text = JSON.stringify(data.content);
            const words = text.toLowerCase().match(/\b[a-zàèéìòù]{4,}\b/g) || [];
            for (let w of words) {
                if (!stopwords.has(w) && w.length > 3) {
                    wordFreq.set(w, (wordFreq.get(w) || 0) + 1);
                }
            }
        }
        const sorted = Array.from(wordFreq.entries()).sort((a,b) => b[1] - a[1]).slice(0, 30);
        const cloudSpan = document.getElementById('cloudSpan');
        cloudSpan.innerHTML = '';
        for (let [word, count] of sorted) {
            const span = document.createElement('span');
            span.className = 'keyword';
            span.innerText = `${word} (${count})`;
            span.onclick = () => filterByKeyword(word);
            cloudSpan.appendChild(span);
        }
    }
    function filterByKeyword(keyword) { alert(`Funzionalità avanzata: mostrare solo uscite con alta frequenza di "${keyword}" richiede implementazione server-side.`); }

    document.getElementById('searchBtn').onclick = () => {
        const titleFilter = document.getElementById('searchTitle').value.toLowerCase();
        const keyword = document.getElementById('searchKeyword').value.toLowerCase();
        let filtered = allIssues;
        if (titleFilter) filtered = filtered.filter(i => i.title.toLowerCase().includes(titleFilter));
        if (keyword) filtered = filtered.filter(i => JSON.stringify(i).toLowerCase().includes(keyword));
        renderTable(filtered);
    };
    document.getElementById('resetBtn').onclick = () => { document.getElementById('searchTitle').value = ''; document.getElementById('searchKeyword').value = ''; renderTable(allIssues); };

    loadStats();
</script>
</body>
</html>
