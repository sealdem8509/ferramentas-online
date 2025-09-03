<?php
/**
 * Plugin Name: Décimo Terceiro — Como Calcular
 * Description: Calculadora de décimo terceiro (parcial, completo e final do ano) de acordo com a CLT (regra dos 15 dias por mês). Shortcode: [decimo_terceiro]
 * Version: 1.0.0
 * Author: iFerramentaria
 * Requires at least: 5.5
 * Requires PHP: 7.2
 * License: GPL-2.0-or-later
 * Text Domain: decimo-terceiro
 */

if (!defined('ABSPATH')) { exit; }

class IF_Decimo_Terceiro {
    public function __construct(){
        add_shortcode('decimo_terceiro', [$this, 'render_shortcode']);
    }

    public function render_shortcode($atts = []){
        $uid = 'ifdt_' . wp_generate_uuid4();
        $ano = (int) date('Y');
        ob_start();
        ?>
        <div id="<?php echo esc_attr($uid); ?>" class="dt-wrap">
            <style>
                /* ===== Paleta & tokens (padrão visual pedido) ===== */
                #<?php echo $uid; ?>{ --blue-900:#0F2C59; --blue-700:#1B3B6F; --silver:#C0C0C0; --silver-200:#E5E7EB; --bg:#F6F8FB; --text:#0B1220; }
                #<?php echo $uid; ?> .card{ background:#fff; border:1px solid var(--silver-200); border-radius:16px; box-shadow:0 6px 24px rgba(15,44,89,.08); overflow:hidden; font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,Ubuntu,Cantarell,Noto Sans,sans-serif; }
                /* Cabeçalho visível e destacado */
                #<?php echo $uid; ?> .header{ background:linear-gradient(135deg,var(--blue-900),var(--blue-700)); color:#fff; padding:22px 22px 14px; }
                #<?php echo $uid; ?> .title{ margin:0; font-size:1.35rem; font-weight:900; line-height:1.25; display:flex; gap:.6rem; align-items:center; color:#fff; text-shadow:0 1px 2px rgba(0,0,0,.25); }
                #<?php echo $uid; ?> .subtitle{ margin:6px 0 0; font-size:.95rem; color:#fff; font-weight:600; opacity:.98; }
                /* Corpo */
                #<?php echo $uid; ?> .body{ padding:20px; background:var(--bg);} 
                #<?php echo $uid; ?> .grid{ display:grid; gap:16px; grid-template-columns:repeat(12,1fr);} 
                @media (max-width:900px){#<?php echo $uid; ?> .grid{ grid-template-columns:1fr; }}
                #<?php echo $uid; ?> .field{ grid-column:span 12 / span 12; }
                #<?php echo $uid; ?> .field.third{ grid-column:span 4 / span 4; }
                #<?php echo $uid; ?> .field.half{ grid-column:span 6 / span 6; }
                @media (max-width:900px){#<?php echo $uid; ?> .field.third, #<?php echo $uid; ?> .field.half{ grid-column:1/-1; }}
                #<?php echo $uid; ?> label{ display:block; font-size:.92rem; color:var(--blue-900); margin:2px 0 6px; font-weight:700; }
                #<?php echo $uid; ?> input[type="text"],
                #<?php echo $uid; ?> input[type="number"],
                #<?php echo $uid; ?> input[type="date"],
                #<?php echo $uid; ?> select{
                    width:100%; background:#fff; border:1px solid var(--silver-200); border-radius:12px; padding:12px 14px; font-size:1rem; outline:none; transition:border .2s, box-shadow .2s; }
                #<?php echo $uid; ?> input:focus, #<?php echo $uid; ?> select:focus{ border-color:var(--blue-700); box-shadow:0 0 0 3px rgba(27,59,111,.15);} 
                #<?php echo $uid; ?> .help{ font-size:.85rem; color:#465266; margin-top:6px; }
                #<?php echo $uid; ?> .inline{ display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
                #<?php echo $uid; ?> .radio{ display:flex; gap:14px; flex-wrap:wrap; }
                #<?php echo $uid; ?> .radio label{ display:flex; align-items:center; gap:8px; color:var(--blue-900); font-weight:600; }
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
                /* KPIs */
                #<?php echo $uid; ?> .results{ margin-top:18px; display:grid; gap:14px; grid-template-columns:repeat(12,1fr);} 
                #<?php echo $uid; ?> .kpi{ grid-column:span 3 / span 3; background:#fff; border:1px solid var(--silver-200); border-radius:14px; padding:14px; }
                @media (max-width:900px){#<?php echo $uid; ?> .kpi{ grid-column:1/-1; }}
                #<?php echo $uid; ?> .kpi .label{ font-size:.85rem; color:#465266; margin-bottom:6px; }
                #<?php echo $uid; ?> .kpi .value{ font-size:1.25rem; font-weight:800; color:var(--blue-900);} 
                /* Tabela/Lista */
                #<?php echo $uid; ?> .list{ margin-top:10px; background:#fff; border:1px solid var(--silver-200); border-radius:12px; overflow:hidden; }
                #<?php echo $uid; ?> .list-header{ background:#F3F4F6; color:#0F2C59; font-weight:900; padding:10px 12px; }
                #<?php echo $uid; ?> .row{ display:grid; grid-template-columns: 1.2fr 1fr 1fr; gap:8px; padding:10px 12px; border-top:1px dashed var(--silver-200); font-size:.95rem; }
                #<?php echo $uid; ?> .row strong{ color:var(--blue-900); }
                #<?php echo $uid; ?> .row:nth-child(odd){ background:#FAFAFA; }
                /* Nota */
                #<?php echo $uid; ?> .note{ margin-top:12px; font-size:.85rem; color:#465266; background:#fff; border:1px dashed var(--silver); padding:10px 12px; border-radius:12px; }
            </style>

            <div class="card" role="region" aria-label="Calculadora de décimo terceiro">
                <div class="header">
                    <h3 class="title">🎄 Décimo Terceiro — Como calcular</h3>
                    <p class="subtitle">Calcule o 13º <strong>parcial</strong>, <strong>completo</strong> e a <strong>2ª parcela</strong> conforme a regra dos <strong>15 dias por mês</strong>.</p>
                </div>
                <div class="body">
                    <div class="grid">
                        <div class="field third">
                            <label for="<?php echo $uid; ?>_sal">Salário-base (R$)</label>
                            <input type="text" id="<?php echo $uid; ?>_sal" placeholder="Ex.: 3.200,00">
                            <div class="help">Salário mensal atual (sem descontos). Use vírgula ou ponto — nós entendemos 😉</div>
                        </div>
                        <div class="field third">
                            <label for="<?php echo $uid; ?>_var">Média de variáveis (R$) <span style="font-weight:600;color:#6B7280;">(opcional)</span></label>
                            <input type="text" id="<?php echo $uid; ?>_var" placeholder="Horas extras, comissões...">
                            <div class="help">Se não tiver, deixe em branco.</div>
                        </div>
                        <div class="field third">
                            <label for="<?php echo $uid; ?>_ano">Ano de referência</label>
                            <input type="number" id="<?php echo $uid; ?>_ano" value="<?php echo $ano; ?>" min="2000" max="2100">
                            <div class="help">Ano para o qual o 13º será calculado.</div>
                        </div>

                        <div class="field">
                            <label>Método de cálculo</label>
                            <div class="radio">
                                <label for="<?php echo $uid; ?>_m1"><input type="radio" name="<?php echo $uid; ?>_m" id="<?php echo $uid; ?>_m1" value="periodo" checked> Por período (admissão/demissão)</label>
                                <label for="<?php echo $uid; ?>_m2"><input type="radio" name="<?php echo $uid; ?>_m" id="<?php echo $uid; ?>_m2" value="meses"> Informar meses trabalhados</label>
                            </div>
                        </div>

                        <div class="field half show-if-periodo">
                            <label for="<?php echo $uid; ?>_adm">Data de admissão</label>
                            <input type="date" id="<?php echo $uid; ?>_adm">
                            <div class="help">Conta o mês se trabalhou <strong>15 dias ou mais</strong>.</div>
                        </div>
                        <div class="field half show-if-periodo">
                            <label for="<?php echo $uid; ?>_dem">Data de demissão (opcional)</label>
                            <input type="date" id="<?php echo $uid; ?>_dem">
                            <div class="help">Se vazio, assume vínculo ativo até 31/12 do ano.</div>
                        </div>

                        <div class="field half show-if-meses" style="display:none;">
                            <label for="<?php echo $uid; ?>_meses">Meses trabalhados no ano (0–12)</label>
                            <input type="number" id="<?php echo $uid; ?>_meses" min="0" max="12" value="12">
                            <div class="help">Preencha manualmente, considerando a regra dos 15 dias/mês.</div>
                        </div>
                        <div class="field half">
                            <label for="<?php echo $uid; ?>_pago">Valor já recebido na 1ª parcela (R$) <span style="font-weight:600;color:#6B7280;">(opcional)</span></label>
                            <input type="text" id="<?php echo $uid; ?>_pago" placeholder="Se já adiantou, informe aqui">
                            <div class="help">Se vazio, sugerimos <strong>50%</strong> do 13º bruto como 1ª parcela.</div>
                        </div>

                        <div class="field">
                            <div class="actions">
                                <button class="btn btn-primary" id="<?php echo $uid; ?>_calc">Calcular</button>
                                <button class="btn btn-secondary" id="<?php echo $uid; ?>_limpar">Limpar</button>
                            </div>
                        </div>
                    </div>

                    <div class="alert warn" id="<?php echo $uid; ?>_warn"></div>
                    <div class="alert err" id="<?php echo $uid; ?>_err"></div>

                    <div class="results" id="<?php echo $uid; ?>_results" style="display:none;">
                        <div class="kpi">
                            <div class="label">Meses considerados</div>
                            <div class="value" id="<?php echo $uid; ?>_k_meses">—</div>
                        </div>
                        <div class="kpi">
                            <div class="label">13º bruto proporcional</div>
                            <div class="value" id="<?php echo $uid; ?>_k_bruto">—</div>
                        </div>
                        <div class="kpi">
                            <div class="label">1ª parcela (sugerida)</div>
                            <div class="value" id="<?php echo $uid; ?>_k_p1">—</div>
                        </div>
                        <div class="kpi">
                            <div class="label">2ª parcela (antes de descontos)</div>
                            <div class="value" id="<?php echo $uid; ?>_k_p2">—</div>
                        </div>
                    </div>

                    <div class="list" id="<?php echo $uid; ?>_lista" style="display:none;">
                        <div class="list-header">Detalhamento por mês (regra dos 15 dias)</div>
                        <div id="<?php echo $uid; ?>_rows"></div>
                    </div>

                    <div class="note">💡 <strong>Importante:</strong> A 1ª parcela do 13º é paga sem descontos. Os descontos de <strong>INSS</strong> e <strong>IRRF</strong> costumam incidir na <strong>2ª parcela</strong>, conforme faixas vigentes. Este simulador mostra valores <strong>brutos</strong> e não substitui o cálculo oficial do RH.</div>
                </div>
            </div>

            <script>
            (function(){
                const $ = (id)=>document.getElementById(id);
                const sal = $('<?php echo $uid; ?>_sal');
                const vari = $('<?php echo $uid; ?>_var');
                const ano = $('<?php echo $uid; ?>_ano');
                const m1 = $('<?php echo $uid; ?>_m1');
                const m2 = $('<?php echo $uid; ?>_m2');
                const adm = $('<?php echo $uid; ?>_adm');
                const dem = $('<?php echo $uid; ?>_dem');
                const meses = $('<?php echo $uid; ?>_meses');
                const pago = $('<?php echo $uid; ?>_pago');

                const warn = $('<?php echo $uid; ?>_warn');
                const err  = $('<?php echo $uid; ?>_err');
                const res  = $('<?php echo $uid; ?>_results');

                const kMes = $('<?php echo $uid; ?>_k_meses');
                const kBru = $('<?php echo $uid; ?>_k_bruto');
                const kP1  = $('<?php echo $uid; ?>_k_p1');
                const kP2  = $('<?php echo $uid; ?>_k_p2');

                const lista = $('<?php echo $uid; ?>_lista');
                const rows  = $('<?php echo $uid; ?>_rows');

                const show = (el, msg)=>{ el.style.display='block'; if(msg!==undefined) el.textContent = msg; };
                const hide = (el)=>{ el.style.display='none'; el.textContent=''; };
                const brl = (v)=> v.toLocaleString('pt-BR', {style:'currency', currency:'BRL'});

                // Alternância de método
                function toggleMethod(){
                    const usingPeriodo = m1.checked;
                    document.querySelectorAll('#<?php echo $uid; ?> .show-if-periodo').forEach(el=> el.style.display = usingPeriodo? 'block':'none');
                    document.querySelectorAll('#<?php echo $uid; ?> .show-if-meses').forEach(el=> el.style.display = usingPeriodo? 'none':'block');
                }
                m1.addEventListener('change', toggleMethod);
                m2.addEventListener('change', toggleMethod);
                toggleMethod();

                function parseMoneyBR(txt){
                    if(!txt) return 0;
                    // aceita "1.234,56" ou "1234.56" ou "1234"
                    const s = String(txt).trim().replace(/\s/g,'');
                    if(!s) return 0;
                    // Se tem vírgula como decimal
                    if(/,\d{1,2}$/.test(s)) {
                        return parseFloat(s.replace(/\./g,'').replace(',','.')) || 0;
                    }
                    return parseFloat(s.replace(/\./g,'')) || 0;
                }

                function dateFromInput(val){
                    if(!val) return null;
                    const [y,m,d] = val.split('-').map(Number);
                    if(!y||!m||!d) return null;
                    const dt = new Date(Date.UTC(y,m-1,d));
                    return new Date(dt.getUTCFullYear(), dt.getUTCMonth(), dt.getUTCDate());
                }
                function monthStart(y,m){ return new Date(y,m,1); }
                function monthEnd(y,m){ return new Date(y,m+1,0); }
                function clamp(a,b,c){ return a<b?b:(a>c?c:a); }

                function daysBetween(a,b){ return Math.floor((b - a)/(1000*60*60*24)) + 1; }

                function computeMonthsByPeriod(year, admDate, demDate){
                    const period = [];
                    const yearStart = new Date(year,0,1);
                    const yearEnd   = new Date(year,11,31);
                    const start = admDate? (admDate>yearStart?admDate:yearStart) : yearStart;
                    const end   = demDate? (demDate<yearEnd?demDate:yearEnd) : yearEnd;
                    if(end < start){ return {count:0, detail:period}; }
                    let counted = 0;
                    for(let m=0;m<12;m++){
                        const ms = monthStart(year,m), me = monthEnd(year,m);
                        const s = ms < start ? start : ms;
                        const e = me > end   ? end   : me;
                        let dias = 0, conta = false;
                        if(e >= s){ dias = daysBetween(s,e); conta = dias >= 15; }
                        period.push({mes: m+1, dias, conta});
                        if(conta) counted++;
                    }
                    return {count:counted, detail:period};
                }

                function calcular(){
                    hide(warn); hide(err); rows.innerHTML=''; lista.style.display='none';
                    const salario = parseMoneyBR(sal.value);
                    const variaveis = parseMoneyBR(vari.value);
                    const anoRef = parseInt(ano.value,10);
                    if(!salario || salario <= 0){ show(err,'Informe um salário-base válido.'); res.style.display='none'; return; }
                    if(!anoRef || anoRef<2000 || anoRef>2100){ show(err,'Informe um ano de referência válido.'); res.style.display='none'; return; }

                    let mesesConsiderados = 0; let detalhes = [];
                    if(m1.checked){
                        const dAdm = dateFromInput(adm.value);
                        const dDem = dateFromInput(dem.value);
                        const r = computeMonthsByPeriod(anoRef, dAdm, dDem);
                        mesesConsiderados = r.count; detalhes = r.detail;
                    } else {
                        const m = parseInt(meses.value,10);
                        if(isNaN(m) || m<0 || m>12){ show(err,'Meses trabalhados deve estar entre 0 e 12.'); res.style.display='none'; return; }
                        mesesConsiderados = m;
                        for(let i=1;i<=12;i++){ detalhes.push({mes:i, dias: i<=m? 30:0, conta: i<=m}); }
                    }

                    const base = salario + (variaveis || 0);
                    const decimoBruto = base * (mesesConsiderados/12);

                    let primeiraParcela = parseMoneyBR(pago.value);
                    if(!primeiraParcela){ primeiraParcela = decimoBruto * 0.5; }
                    if(primeiraParcela > decimoBruto){ primeiraParcela = decimoBruto; }
                    const segundaParcela = Math.max(0, decimoBruto - primeiraParcela);

                    // KPIs
                    kMes.textContent = mesesConsiderados.toString();
                    kBru.textContent = brl(decimoBruto);
                    kP1.textContent  = brl(primeiraParcela);
                    kP2.textContent  = brl(segundaParcela);
                    res.style.display='grid';

                    // Lista detalhada
                    if(detalhes && detalhes.length){
                        const mesesBR = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
                        for(const d of detalhes){
                            const row = document.createElement('div');
                            row.className='row';
                            row.innerHTML = `<div><strong>${mesesBR[d.mes-1]}/${anoRef}</strong></div>
                                             <div>${d.dias ? d.dias + ' dia(s)' : '—'}</div>
                                             <div>${d.conta ? '✔️ Conta (≥ 15 dias)' : '—'}</div>`;
                            rows.appendChild(row);
                        }
                        lista.style.display='block';
                    }
                }

                $('<?php echo $uid; ?>_calc').addEventListener('click', (e)=>{ e.preventDefault(); calcular(); });
                $('<?php echo $uid; ?>_limpar').addEventListener('click', (e)=>{
                    e.preventDefault();
                    sal.value=''; vari.value=''; pago.value='';
                    adm.value=''; dem.value=''; meses.value='12';
                    hide(warn); hide(err); res.style.display='none'; lista.style.display='none'; rows.innerHTML='';
                    [kMes,kBru,kP1,kP2].forEach(el=> el.textContent='—');
                });
            })();
            </script>
        </div>
        <?php
        return ob_get_clean();
    }
}

new IF_Decimo_Terceiro();
