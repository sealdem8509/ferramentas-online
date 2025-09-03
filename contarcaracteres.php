<?php
/**
 * Plugin Name: Contador de caracteres (com e sem espaço)
 * Description: Conta caracteres com espaço e sem espaço, além de palavras e linhas. Shortcode: [contador_caracteres]
 * Version: 1.0.0
 * Author: iFerramentaria
 * Requires at least: 5.5
 * Requires PHP: 7.2
 * License: GPL-2.0-or-later
 * Text Domain: contador-caracteres
 */

if (!defined('ABSPATH')) { exit; }

class IF_Contador_Caracteres {
    public function __construct() {
        add_shortcode('contador_caracteres', [$this, 'render_shortcode']);
    }

    public function render_shortcode($atts = []) {
        $uid = 'ifcc_' . wp_generate_uuid4();
        ob_start();
        ?>
        <div id="<?php echo esc_attr($uid); ?>" class="cc-wrap">
            <style>
                /* ===== Paleta & tokens (padrão) ===== */
                #<?php echo $uid; ?>{ --blue-900:#0F2C59; --blue-700:#1B3B6F; --silver:#C0C0C0; --silver-200:#E5E7EB; --bg:#F6F8FB; --text:#0B1220; }
                #<?php echo $uid; ?> .card{ background:#fff; border:1px solid var(--silver-200); border-radius:16px; box-shadow:0 6px 24px rgba(15,44,89,.08); overflow:hidden; font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,Ubuntu,Cantarell,Noto Sans,sans-serif; }
                /* Cabeçalho visível */
                #<?php echo $uid; ?> .header{ background:linear-gradient(135deg,var(--blue-900),var(--blue-700)); color:#fff; padding:22px 22px 14px; }
                #<?php echo $uid; ?> .title{ margin:0; font-size:1.35rem; font-weight:900; line-height:1.25; display:flex; gap:.6rem; align-items:center; color:#fff; text-shadow:0 1px 2px rgba(0,0,0,.25); }
                #<?php echo $uid; ?> .subtitle{ margin:6px 0 0; font-size:.95rem; color:#fff; font-weight:600; opacity:.98; }
                /* Corpo */
                #<?php echo $uid; ?> .body{ padding:20px; background:var(--bg);} 
                #<?php echo $uid; ?> .grid{ display:grid; gap:16px; grid-template-columns:repeat(12,1fr);} 
                @media (max-width:900px){#<?php echo $uid; ?> .grid{ grid-template-columns:1fr; }}
                #<?php echo $uid; ?> .field{ grid-column:span 12 / span 12; }
                #<?php echo $uid; ?> .field.half{ grid-column:span 6 / span 6; }
                @media (max-width:900px){#<?php echo $uid; ?> .field.half{ grid-column:1/-1; }}
                #<?php echo $uid; ?> label{ display:block; font-size:.92rem; color:var(--blue-900); margin:2px 0 6px; font-weight:700; }
                #<?php echo $uid; ?> textarea, #<?php echo $uid; ?> select, #<?php echo $uid; ?> input[type="text"]{ width:100%; background:#fff; border:1px solid var(--silver-200); border-radius:12px; padding:12px 14px; font-size:1rem; outline:none; transition:border .2s, box-shadow .2s; }
                #<?php echo $uid; ?> textarea{ min-height:180px; resize:vertical; }
                #<?php echo $uid; ?> textarea:focus, #<?php echo $uid; ?> select:focus{ border-color:var(--blue-700); box-shadow:0 0 0 3px rgba(27,59,111,.15);} 
                #<?php echo $uid; ?> .help{ font-size:.85rem; color:#465266; margin-top:6px; }
                #<?php echo $uid; ?> .inline{ display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
                #<?php echo $uid; ?> .checkbox{ display:flex; align-items:center; gap:8px; font-size:.92rem; color:#0F2C59; }
                /* Ações */
                #<?php echo $uid; ?> .actions{ display:flex; gap:10px; flex-wrap:wrap; margin-top:8px; }
                #<?php echo $uid; ?> .btn{ border:none; cursor:pointer; padding:12px 16px; border-radius:12px; font-weight:800; letter-spacing:.3px; transition:transform .06s ease, box-shadow .2s, opacity .2s; }
                #<?php echo $uid; ?> .btn:active{ transform:translateY(1px);} 
                #<?php echo $uid; ?> .btn[disabled]{ opacity:.6; cursor:not-allowed; }
                #<?php echo $uid; ?> .btn-primary{ background:linear-gradient(135deg,var(--blue-900),var(--blue-700)); color:#fff; box-shadow:0 10px 24px rgba(27,59,111,.18);} 
                #<?php echo $uid; ?> .btn-secondary{ background:#f9fafb; color:var(--blue-900); border:1px solid var(--silver-200);} 
                #<?php echo $uid; ?> .btn-ghost{ background:transparent; color:var(--blue-900); border:1px dashed var(--silver-200);} 
                /* Alertas */
                #<?php echo $uid; ?> .alert{ margin-top:12px; padding:12px; border-radius:12px; font-size:.92rem; display:none; }
                #<?php echo $uid; ?> .warn{ background:#FFF7ED; color:#7C2D12; border:1px solid #FED7AA; }
                #<?php echo $uid; ?> .err{ background:#FEF2F2; color:#7F1D1D; border:1px solid #FECACA; }
                /* Resultados (KPIs) */
                #<?php echo $uid; ?> .results{ margin-top:18px; display:grid; gap:14px; grid-template-columns:repeat(12,1fr);} 
                #<?php echo $uid; ?> .kpi{ grid-column:span 3 / span 3; background:#fff; border:1px solid var(--silver-200); border-radius:14px; padding:14px; }
                @media (max-width:900px){#<?php echo $uid; ?> .kpi{ grid-column:1/-1; }}
                #<?php echo $uid; ?> .kpi .label{ font-size:.85rem; color:#465266; margin-bottom:6px; }
                #<?php echo $uid; ?> .kpi .value{ font-size:1.25rem; font-weight:800; color:var(--blue-900);} 
                /* Saída */
                #<?php echo $uid; ?> .outwrap{ margin-top:12px; }
                #<?php echo $uid; ?> .outwrap .toolbar{ display:flex; gap:8px; align-items:center; margin-bottom:8px; }
                #<?php echo $uid; ?> .outwrap .switch{ display:flex; align-items:center; gap:6px; font-size:.9rem; color:#0F2C59; }
                #<?php echo $uid; ?> .out pre{ background:#fff; border:1px solid var(--silver-200); border-radius:12px; padding:14px; white-space:pre-wrap; word-wrap:break-word; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
                /* Nota */
                #<?php echo $uid; ?> .note{ margin-top:12px; font-size:.85rem; color:#465266; background:#fff; border:1px dashed var(--silver); padding:10px 12px; border-radius:12px; }
            </style>

            <div class="card" role="region" aria-label="Contador de caracteres">
                <div class="header">
                    <h3 class="title">🧮 Contador de Caracteres</h3>
                    <p class="subtitle">Conte caracteres <strong>com</strong> e <strong>sem</strong> espaço, além de palavras e linhas — rápido e responsivo.</p>
                </div>
                <div class="body">
                    <div class="grid">
                        <div class="field">
                            <label for="<?php echo $uid; ?>_texto">Texto</label>
                            <textarea id="<?php echo $uid; ?>_texto" placeholder="Cole ou digite seu texto aqui..."></textarea>
                            <div class="help">A contagem é feita no seu navegador, em tempo real.</div>
                        </div>
                        <div class="field half">
                            <label>Opções</label>
                            <div class="inline">
                                <label class="checkbox" for="<?php echo $uid; ?>_realtime">
                                    <input type="checkbox" id="<?php echo $uid; ?>_realtime" checked>
                                    Atualizar automaticamente enquanto digita
                                </label>
                                <label class="checkbox" for="<?php echo $uid; ?>_trim">
                                    <input type="checkbox" id="<?php echo $uid; ?>_trim" checked>
                                    Ignorar espaços extras nas pontas
                                </label>
                            </div>
                        </div>
                        <div class="field half">
                            <label>Ações</label>
                            <div class="actions">
                                <button class="btn btn-primary" id="<?php echo $uid; ?>_contar">Contar agora</button>
                                <button class="btn btn-secondary" id="<?php echo $uid; ?>_limpar">Limpar</button>
                                <button class="btn btn-ghost" id="<?php echo $uid; ?>_copiar" disabled>Copiar resultados</button>
                            </div>
                        </div>
                    </div>

                    <div class="alert warn" id="<?php echo $uid; ?>_warn"></div>
                    <div class="alert err" id="<?php echo $uid; ?>_err"></div>

                    <div class="results" id="<?php echo $uid; ?>_results" style="display:none;">
                        <div class="kpi">
                            <div class="label">Caracteres (com espaço)</div>
                            <div class="value" id="<?php echo $uid; ?>_k_com">—</div>
                        </div>
                        <div class="kpi">
                            <div class="label">Caracteres (sem espaço)</div>
                            <div class="value" id="<?php echo $uid; ?>_k_sem">—</div>
                        </div>
                        <div class="kpi">
                            <div class="label">Palavras</div>
                            <div class="value" id="<?php echo $uid; ?>_k_pal">—</div>
                        </div>
                        <div class="kpi">
                            <div class="label">Linhas</div>
                            <div class="value" id="<?php echo $uid; ?>_k_lin">—</div>
                        </div>
                    </div>

                    <div class="outwrap" id="<?php echo $uid; ?>_outwrap" style="display:none;">
                        <div class="toolbar">
                            <span class="switch"><input type="checkbox" id="<?php echo $uid; ?>_mostrar_texto"> <label for="<?php echo $uid; ?>_mostrar_texto">Exibir texto analisado</label></span>
                        </div>
                        <div class="out">
                            <label>Texto analisado</label>
                            <pre id="<?php echo $uid; ?>_saida"></pre>
                        </div>
                    </div>

                    <div class="note">💡 <strong>Dicas:</strong> "Sem espaço" remove todos os caracteres de espaço em branco (espaço, quebras de linha e tabulações). Palavras são contadas por separação em espaços e quebras de linha.</div>
                </div>
            </div>

            <script>
                (function(){
                    const $ = (id)=>document.getElementById(id);
                    const txt = $('<?php echo $uid; ?>_texto');
                    const realtime = $('<?php echo $uid; ?>_realtime');
                    const trim = $('<?php echo $uid; ?>_trim');
                    const btnContar = $('<?php echo $uid; ?>_contar');
                    const btnLimpar = $('<?php echo $uid; ?>_limpar');
                    const btnCopiar = $('<?php echo $uid; ?>_copiar');

                    const warn = $('<?php echo $uid; ?>_warn');
                    const err  = $('<?php echo $uid; ?>_err');
                    const res  = $('<?php echo $uid; ?>_results');

                    const kCom = $('<?php echo $uid; ?>_k_com');
                    const kSem = $('<?php echo $uid; ?>_k_sem');
                    const kPal = $('<?php echo $uid; ?>_k_pal');
                    const kLin = $('<?php echo $uid; ?>_k_lin');

                    const outwrap = $('<?php echo $uid; ?>_outwrap');
                    const saida = $('<?php echo $uid; ?>_saida');
                    const mostrarTexto = $('<?php echo $uid; ?>_mostrar_texto');

                    function show(el, msg){ el.style.display='block'; if (msg !== undefined) el.textContent=msg; }
                    function hide(el){ el.style.display='none'; if (el) el.textContent=''; }

                    function format(n){ return (n||0).toLocaleString('pt-BR'); }

                    function contar(){
                        hide(warn); hide(err);
                        let texto = txt.value || '';
                        if(trim.checked) texto = texto.replace(/^\s+|\s+$/g,'');
                        const comEspacos = texto.length; // inclui \n e \t
                        const semEspacos = texto.replace(/\s+/g, '').length; // remove qualquer whitespace
                        const palavras = texto.trim() ? (texto.trim().split(/\s+/).filter(Boolean).length) : 0;
                        const linhas = texto.length ? (texto.split(/\n/).length) : 0;

                        kCom.textContent = format(comEspacos);
                        kSem.textContent = format(semEspacos);
                        kPal.textContent = format(palavras);
                        kLin.textContent = format(linhas);
                        res.style.display='grid';

                        if(mostrarTexto.checked){
                            outwrap.style.display='block';
                            saida.textContent = texto;
                        } else {
                            outwrap.style.display='none';
                        }
                        btnCopiar.disabled = false;
                    }

                    btnContar.addEventListener('click', (e)=>{ e.preventDefault(); contar(); });
                    btnLimpar.addEventListener('click', (e)=>{ e.preventDefault(); txt.value=''; hide(warn); hide(err); res.style.display='none'; outwrap.style.display='none'; [kCom,kSem,kPal,kLin].forEach(el=> el.textContent='—'); btnCopiar.disabled = true; });
                    btnCopiar.addEventListener('click', async (e)=>{ e.preventDefault(); try{ const data = `Caracteres (com espaço): ${kCom.textContent}\nCaracteres (sem espaço): ${kSem.textContent}\nPalavras: ${kPal.textContent}\nLinhas: ${kLin.textContent}`; await navigator.clipboard.writeText(data); show(warn,'Resultados copiados para a área de transferência.'); setTimeout(()=>hide(warn), 1800); } catch{ show(err,'Não foi possível copiar automaticamente.'); }});
                    realtime.addEventListener('change', ()=>{ if(realtime.checked) contar(); });
                    txt.addEventListener('input', ()=>{ if(realtime.checked) contar(); });
                })();
            </script>
        </div>
        <?php
        return ob_get_clean();
    }
}

new IF_Contador_Caracteres();
