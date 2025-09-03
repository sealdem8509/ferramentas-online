<?php
/**
 * Plugin Name: Corretor de Texto (LanguageTool)
 * Description: Corrige ortografia e pontuação de um texto usando a API pública do LanguageTool. Shortcode: [corretor_texto]
 * Version: 1.0.0
 * Author: iFerramentaria
 * Requires at least: 5.5
 * Requires PHP: 7.2
 * License: GPL-2.0-or-later
 * Text Domain: corretor-texto-lt
 */

if (!defined('ABSPATH')) { exit; }

class IF_Corretor_Texto_LT {
    public function __construct() {
        add_shortcode('corretor_texto', [$this, 'render_shortcode']);
    }

    public function render_shortcode($atts = []) {
        $uid = 'iflt_' . wp_generate_uuid4();
        ob_start();
        ?>
        <div id="<?php echo esc_attr($uid); ?>" class="lt-wrap">
            <style>
                /* ===== Paleta & tokens ===== */
                #<?php echo $uid; ?>{
                    --blue-900:#0F2C59;  /* azul-escuro */
                    --blue-700:#1B3B6F;  /* azul */
                    --silver:#C0C0C0;    /* prata */
                    --silver-200:#E5E7EB;
                    --bg:#F6F8FB;
                    --text:#0B1220;
                    --ok:#0E9F6E;
                    --warn:#B45309;
                    --err:#B91C1C;
                }
                #<?php echo $uid; ?> .card{
                    background:#fff; border:1px solid var(--silver-200);
                    border-radius:16px; box-shadow:0 6px 24px rgba(15,44,89,.08);
                    overflow:hidden; font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,Ubuntu,Cantarell,Noto Sans,sans-serif;
                }
                /* Cabeçalho visível */
                #<?php echo $uid; ?> .header{
                    background:linear-gradient(135deg,var(--blue-900),var(--blue-700));
                    color:#fff; padding:22px 22px 14px;
                }
                #<?php echo $uid; ?> .title{
                    margin:0; font-size:1.35rem; font-weight:900; line-height:1.25;
                    display:flex; gap:.6rem; align-items:center; color:#fff; text-shadow:0 1px 2px rgba(0,0,0,.25);
                }
                #<?php echo $uid; ?> .subtitle{margin:6px 0 0; font-size:.95rem; color:#fff; font-weight:600; opacity:.98;}
                
                /* Corpo */
                #<?php echo $uid; ?> .body{padding:20px; background:var(--bg);} 
                #<?php echo $uid; ?> .grid{display:grid; gap:16px; grid-template-columns:repeat(12,1fr);} 
                @media (max-width:900px){#<?php echo $uid; ?> .grid{grid-template-columns:1fr;}}
                #<?php echo $uid; ?> .field{grid-column:span 12 / span 12;}
                #<?php echo $uid; ?> .field.half{grid-column:span 6 / span 6;}
                @media (max-width:900px){#<?php echo $uid; ?> .field.half{grid-column:1/-1;}}
                #<?php echo $uid; ?> label{display:block; font-size:.92rem; color:var(--blue-900); margin:2px 0 6px; font-weight:700;}
                #<?php echo $uid; ?> textarea,
                #<?php echo $uid; ?> select,
                #<?php echo $uid; ?> input[type="text"]{
                    width:100%; background:#fff; border:1px solid var(--silver-200);
                    border-radius:12px; padding:12px 14px; font-size:1rem; outline:none;
                    transition:border .2s, box-shadow .2s;
                }
                #<?php echo $uid; ?> textarea{min-height:160px; resize:vertical;}
                #<?php echo $uid; ?> textarea[readonly]{background:#F9FAFB;}
                #<?php echo $uid; ?> textarea:focus, #<?php echo $uid; ?> select:focus{border-color:var(--blue-700); box-shadow:0 0 0 3px rgba(27,59,111,.15);} 
                #<?php echo $uid; ?> .help{font-size:.85rem; color:#465266; margin-top:6px;}
                #<?php echo $uid; ?> .inline{display:flex; align-items:center; gap:12px; flex-wrap:wrap;}
                #<?php echo $uid; ?> .checkbox{display:flex; align-items:center; gap:8px; font-size:.92rem; color:#0F2C59;}
                
                /* Ações */
                #<?php echo $uid; ?> .actions{display:flex; gap:10px; flex-wrap:wrap; margin-top:8px;}
                #<?php echo $uid; ?> .btn{border:none; cursor:pointer; padding:12px 16px; border-radius:12px;
                    font-weight:800; letter-spacing:.3px; transition:transform .06s ease, box-shadow .2s, opacity .2s;}
                #<?php echo $uid; ?> .btn:active{transform:translateY(1px);} 
                #<?php echo $uid; ?> .btn[disabled]{opacity:.6; cursor:not-allowed;}
                #<?php echo $uid; ?> .btn-primary{background:linear-gradient(135deg,var(--blue-900),var(--blue-700)); color:#fff; box-shadow:0 10px 24px rgba(27,59,111,.18);} 
                #<?php echo $uid; ?> .btn-secondary{background:#f9fafb; color:var(--blue-900); border:1px solid var(--silver-200);} 
                #<?php echo $uid; ?> .btn-ghost{background:transparent; color:var(--blue-900); border:1px dashed var(--silver-200);} 
                
                /* Alertas */
                #<?php echo $uid; ?> .alert{margin-top:12px; padding:12px; border-radius:12px; font-size:.92rem; display:none;}
                #<?php echo $uid; ?> .warn{background:#FFF7ED; color:#7C2D12; border:1px solid #FED7AA;}
                #<?php echo $uid; ?> .err{background:#FEF2F2; color:#7F1D1D; border:1px solid #FECACA;}
                
                /* Resultados (KPIs) */
                #<?php echo $uid; ?> .results{margin-top:18px; display:grid; gap:14px; grid-template-columns:repeat(12,1fr);} 
                #<?php echo $uid; ?> .kpi{grid-column:span 3 / span 3; background:#fff; border:1px solid var(--silver-200); border-radius:14px; padding:14px;}
                @media (max-width:900px){#<?php echo $uid; ?> .kpi{grid-column:1/-1;}}
                #<?php echo $uid; ?> .kpi .label{font-size:.85rem; color:#465266; margin-bottom:6px;}
                #<?php echo $uid; ?> .kpi .value{font-size:1.25rem; font-weight:800; color:var(--blue-900);} 
                
                /* Saída corrigida */
                #<?php echo $uid; ?> .outwrap{margin-top:12px;}
                #<?php echo $uid; ?> .outwrap .toolbar{display:flex; gap:8px; align-items:center; margin-bottom:8px;}
                #<?php echo $uid; ?> .outwrap .switch{display:flex; align-items:center; gap:6px; font-size:.9rem; color:#0F2C59;}
                #<?php echo $uid; ?> .outarea{display:grid; gap:16px; grid-template-columns:repeat(12,1fr);} 
                @media (max-width:900px){#<?php echo $uid; ?> .outarea{grid-template-columns:1fr;}}
                #<?php echo $uid; ?> .out{grid-column:span 12 / span 12;}
                #<?php echo $uid; ?> .out pre{background:#fff; border:1px solid var(--silver-200); border-radius:12px; padding:14px; white-space:pre-wrap; word-wrap:break-word; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;}
                #<?php echo $uid; ?> mark.chg{background:#FDF2F8; padding:2px 3px; border-radius:6px; border:1px solid #FBCFE8;}
                #<?php echo $uid; ?> .list{margin-top:10px; background:#fff; border:1px solid var(--silver-200); border-radius:12px; padding:10px 12px;}
                #<?php echo $uid; ?> .list h4{margin:0 0 8px 0; color:var(--blue-900); font-size:1rem; font-weight:900;}
                #<?php echo $uid; ?> .issue{border-top:1px dashed var(--silver-200); padding:8px 0; font-size:.92rem;}
                #<?php echo $uid; ?> .issue:first-child{border-top:none;}
                #<?php echo $uid; ?> .issue .msg{color:#0B1220;}
                #<?php echo $uid; ?> .issue .from{color:#6B7280;}
                #<?php echo $uid; ?> .issue .to{color:#0F2C59; font-weight:700;}
                
                /* Nota */
                #<?php echo $uid; ?> .note{margin-top:12px; font-size:.85rem; color:#465266; background:#fff; border:1px dashed var(--silver); padding:10px 12px; border-radius:12px;}
            </style>

            <div class="card" role="region" aria-label="Corretor de texto (LanguageTool)">
                <div class="header">
                    <h3 class="title">📝 Corretor de Texto</h3>
                    <p class="subtitle">Ortografia e pontuação com LanguageTool — rápido, automático e gratuito.</p>
                </div>
                <div class="body">
                    <div class="grid">
                        <div class="field">
                            <label for="<?php echo $uid; ?>_texto">Texto para correção</label>
                            <textarea id="<?php echo $uid; ?>_texto" placeholder="Cole ou digite seu texto aqui..."></textarea>
                            <div class="help">O conteúdo permanece no seu navegador. A verificação é feita pela API pública do LanguageTool.</div>
                        </div>
                        <div class="field half">
                            <label for="<?php echo $uid; ?>_lang">Idioma</label>
                            <select id="<?php echo $uid; ?>_lang">
                                <option value="pt-BR" selected>Português (Brasil)</option>
                                <option value="pt-PT">Português (Portugal)</option>
                                <option value="en-US">Inglês (EUA)</option>
                                <option value="es">Espanhol</option>
                            </select>
                            <div class="help">Selecione o idioma principal do texto.</div>
                        </div>
                        <div class="field half">
                            <label>Opções</label>
                            <div class="inline">
                                <label class="checkbox" for="<?php echo $uid; ?>_auto">
                                    <input type="checkbox" id="<?php echo $uid; ?>_auto" checked>
                                    Aplicar automaticamente a primeira sugestão
                                </label>
                            </div>
                        </div>
                        <div class="field">
                            <div class="actions">
                                <button class="btn btn-primary" id="<?php echo $uid; ?>_corrigir">Corrigir</button>
                                <button class="btn btn-secondary" id="<?php echo $uid; ?>_limpar">Limpar</button>
                                <button class="btn btn-ghost" id="<?php echo $uid; ?>_copiar" disabled>Copiar texto corrigido</button>
                            </div>
                        </div>
                    </div>

                    <div class="alert warn" id="<?php echo $uid; ?>_warn"></div>
                    <div class="alert err" id="<?php echo $uid; ?>_err"></div>

                    <div class="results" id="<?php echo $uid; ?>_results" style="display:none;">
                        <div class="kpi">
                            <div class="label">Erros encontrados</div>
                            <div class="value" id="<?php echo $uid; ?>_k_total">—</div>
                        </div>
                        <div class="kpi">
                            <div class="label">Correções aplicadas</div>
                            <div class="value" id="<?php echo $uid; ?>_k_aplicadas">—</div>
                        </div>
                        <div class="kpi">
                            <div class="label">Ortografia</div>
                            <div class="value" id="<?php echo $uid; ?>_k_typo">—</div>
                        </div>
                        <div class="kpi">
                            <div class="label">Pontuação/Gramática</div>
                            <div class="value" id="<?php echo $uid; ?>_k_punct">—</div>
                        </div>
                    </div>

                    <div class="outwrap" id="<?php echo $uid; ?>_outwrap" style="display:none;">
                        <div class="toolbar">
                            <span class="switch"><input type="checkbox" id="<?php echo $uid; ?>_mostrar_diffs" checked> <label for="<?php echo $uid; ?>_mostrar_diffs">Destacar alterações</label></span>
                        </div>
                        <div class="outarea">
                            <div class="out">
                                <label>Texto corrigido</label>
                                <pre id="<?php echo $uid; ?>_saida"></pre>
                            </div>
                        </div>

                        <div class="list" id="<?php echo $uid; ?>_lista" style="display:none;">
                            <h4>Sugestões aplicadas</h4>
                            <div id="<?php echo $uid; ?>_itens"></div>
                        </div>
                    </div>

                    <div class="note">💡 Dica: a API pública do LanguageTool possui limites de uso. Para grandes volumes, considere um endpoint próprio (LanguageTool Server) ou divida o texto em partes.</div>
                </div>
            </div>

            <script>
                (function(){
                    const $ = (id)=>document.getElementById(id);
                    const txt = $('<?php echo $uid; ?>_texto');
                    const lang = $('<?php echo $uid; ?>_lang');
                    const auto = $('<?php echo $uid; ?>_auto');
                    const btnCorr = $('<?php echo $uid; ?>_corrigir');
                    const btnLimpar = $('<?php echo $uid; ?>_limpar');
                    const btnCopiar = $('<?php echo $uid; ?>_copiar');

                    const warn = $('<?php echo $uid; ?>_warn');
                    const err  = $('<?php echo $uid; ?>_err');
                    const res  = $('<?php echo $uid; ?>_results');

                    const kTotal = $('<?php echo $uid; ?>_k_total');
                    const kAplic = $('<?php echo $uid; ?>_k_aplicadas');
                    const kTypo  = $('<?php echo $uid; ?>_k_typo');
                    const kPunct = $('<?php echo $uid; ?>_k_punct');

                    const outwrap = $('<?php echo $uid; ?>_outwrap');
                    const saida = $('<?php echo $uid; ?>_saida');
                    const lista = $('<?php echo $uid; ?>_lista');
                    const itens = $('<?php echo $uid; ?>_itens');
                    const mostrarDiffs = $('<?php echo $uid; ?>_mostrar_diffs');

                    function show(el, msg){ el.style.display='block'; if (msg !== undefined) el.textContent=msg; }
                    function hide(el){ el.style.display='none'; if (el) el.textContent=''; }

                    function classify(match){
                        const catId = (match.rule && match.rule.category && (match.rule.category.id || '')).toUpperCase();
                        const ruleId = (match.rule && match.rule.id || '').toUpperCase();
                        let type = 'other';
                        if (catId.includes('TYPOS') || ruleId.includes('MORFOLOGIK') || ruleId.includes('SPELLING')) type='typo';
                        else if (catId.includes('PUNCT') || ruleId.includes('COMMA') || ruleId.includes('UPPERCASE_SENTENCE_START') || ruleId.includes('WHITESPACE')) type='punct';
                        else if (catId.includes('GRAMMAR')) type='punct';
                        return type;
                    }

                    function applyCorrections(original, matches){
                        // Cria lista de correções com offset/length e primeira sugestão
                        const corrs = [];
                        for(const m of matches){
                            if(!m.replacements || !m.replacements.length) continue;
                            const rep = m.replacements[0].value || '';
                            if(rep === '') continue;
                            corrs.push({offset:m.offset, length:m.length, replacement:rep, match:m});
                        }
                        // Ordena do fim para o início para não quebrar offsets
                        corrs.sort((a,b)=> b.offset - a.offset);

                        let text = original;
                        const segments = []; // para construir HTML com <mark>
                        let cursor = text.length;
                        let applied = 0;

                        for(const c of corrs){
                            const start = c.offset;
                            const end = c.offset + c.length;
                            if(start < 0 || end > text.length || start > end) continue;
                            // Empilha segmento não alterado (tail)
                            segments.push({type:'text', value: text.slice(end, cursor)});
                            // Segmento alterado
                            const originalFrag = text.slice(start, end);
                            segments.push({type:'change', value: c.replacement, original: originalFrag});
                            // Aplica na string base
                            text = text.slice(0, start) + c.replacement + text.slice(end);
                            cursor = start;
                            applied++;
                        }
                        // Cabeça restante
                        segments.push({type:'text', value: text.slice(0, cursor)});
                        // Reconstrói com ordem correta
                        segments.reverse();
                        const html = segments.map(s=>{
                            if(s.type==='change') return `<mark class="chg" title="Original: ${escapeHtml(s.original)}">${escapeHtml(s.value)}</mark>`;
                            return escapeHtml(s.value);
                        }).join('');

                        return {text, html, applied};
                    }

                    function escapeHtml(s){
                        return String(s)
                            .replace(/&/g,'&amp;')
                            .replace(/</g,'&lt;')
                            .replace(/>/g,'&gt;')
                            .replace(/\"/g,'&quot;')
                            .replace(/'/g,'&#39;');
                    }

                    async function checkText(){
                        hide(warn); hide(err); lista.style.display='none'; itens.innerHTML='';
                        const original = txt.value || '';
                        if(!original.trim()){ show(err,'Digite ou cole um texto para corrigir.'); return; }
                        btnCorr.disabled = true; btnCorr.textContent = 'Verificando...';
                        btnCopiar.disabled = true;
                        try{
                            const params = new URLSearchParams();
                            params.append('language', lang.value || 'pt-BR');
                            params.append('text', original);
                            // Ajuda a identificar: respeita a política de uso do LanguageTool
                            params.append('level','picky');

                            const resp = await fetch('https://api.languagetool.org/v2/check',{
                                method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: params
                            });
                            if(!resp.ok){ throw new Error('Falha ao conectar à API ('+resp.status+')'); }
                            const data = await resp.json();
                            const matches = Array.isArray(data.matches) ? data.matches : [];

                            // KPIs
                            let total = matches.length, typo=0, punct=0;
                            matches.forEach(m=>{ const t=classify(m); if(t==='typo') typo++; else if(t==='punct') punct++; });

                            let applied = 0; let resultHTML = escapeHtml(original); let resultText = original;
                            if (auto.checked && total>0){
                                const out = applyCorrections(original, matches);
                                resultText = out.text; resultHTML = out.html; applied = out.applied;
                            }

                            kTotal.textContent = total.toLocaleString('pt-BR');
                            kAplic.textContent = applied.toLocaleString('pt-BR');
                            kTypo.textContent  = typo.toLocaleString('pt-BR');
                            kPunct.textContent = punct.toLocaleString('pt-BR');
                            res.style.display='grid';

                            saida.innerHTML = mostrarDiffs.checked ? resultHTML : escapeHtml(resultText);
                            outwrap.style.display='block';
                            btnCopiar.disabled = false;

                            // Lista de sugestões (apenas uma amostra útil)
                            if(matches.length){
                                const top = matches.slice(0, 100); // limite para não ficar gigante
                                for(const m of top){
                                    const from = original.substr(m.offset, m.length);
                                    const to = (m.replacements && m.replacements[0]) ? m.replacements[0].value : '(sugestão indisponível)';
                                    const div = document.createElement('div');
                                    div.className='issue';
                                    const msg = (m.message||'Sugestão');
                                    div.innerHTML = `<div class="msg">${escapeHtml(msg)}</div>
                                                    <div><span class="from">“${escapeHtml(from)}”</span> → <span class="to">“${escapeHtml(to)}”</span></div>`;
                                    itens.appendChild(div);
                                }
                                lista.style.display='block';
                            }

                        } catch(e){
                            show(err, 'Erro: ' + e.message + '. Tente novamente em instantes.');
                        } finally {
                            btnCorr.disabled = false; btnCorr.textContent = 'Corrigir';
                        }
                    }

                    btnCorr.addEventListener('click', (ev)=>{ ev.preventDefault(); checkText(); });
                    btnLimpar.addEventListener('click', (ev)=>{
                        ev.preventDefault();
                        txt.value=''; hide(warn); hide(err);
                        res.style.display='none'; outwrap.style.display='none'; itens.innerHTML=''; lista.style.display='none';
                        kTotal.textContent='—'; kAplic.textContent='—'; kTypo.textContent='—'; kPunct.textContent='—';
                        btnCopiar.disabled = true;
                    });
                    btnCopiar.addEventListener('click', async (ev)=>{
                        ev.preventDefault();
                        const textOnly = saida.textContent || '';
                        try { await navigator.clipboard.writeText(textOnly); show(warn,'Texto corrigido copiado para a área de transferência.'); setTimeout(()=>hide(warn), 1800); }
                        catch{ show(err,'Não foi possível copiar automaticamente. Selecione e copie manualmente.'); }
                    });
                    mostrarDiffs.addEventListener('change', ()=>{
                        // Re-render com ou sem marcações
                        const txtPlain = saida.textContent || '';
                        if(mostrarDiffs.checked){ // não temos as marcações antigas; reprocessar chamando novamente seria caro
                            // Sem refazer a chamada, mostramos como está (sem marcações)
                            // O usuário pode clicar em Corrigir novamente para ver destaques.
                            show(warn,'Para reativar os destaques, clique em “Corrigir” novamente.');
                            setTimeout(()=>hide(warn), 2000);
                        } else {
                            // Mostra como texto plano
                            saida.textContent = txtPlain;
                        }
                    });
                })();
            </script>
        </div>
        <?php
        return ob_get_clean();
    }
}

new IF_Corretor_Texto_LT();
