<?php
/**
 * Plugin Name: Contar dias úteis
 * Description: Calculadora para contar dias úteis entre duas datas (exclui fins de semana e considera automaticamente feriados nacionais do Brasil). Shortcode: [contar_dias_uteis]
 * Version: 1.1.0
 * Author: sealdem santos
 * Requires at least: 5.5
 * Requires PHP: 7.2
 * License: GPL-2.0-or-later
 * Text Domain: contar-dias-uteis
 */

if (!defined('ABSPATH')) { exit; }

class IF_Contar_Dias_Uteis {
    public function __construct() {
        add_shortcode('contar_dias_uteis', [$this, 'render_shortcode']);
    }

    public function render_shortcode($atts = []) {
        $uid = 'ifcdu_' . wp_generate_uuid4();
        ob_start();
        ?>
        <div id="<?php echo esc_attr($uid); ?>" class="cdu-wrap">
            <style>
                /* ===== Paleta & tokens ===== */
                #<?php echo $uid; ?>{
                    --blue-900:#0F2C59;
                    --blue-700:#1B3B6F;
                    --silver:#C0C0C0;
                    --silver-200:#E5E7EB;
                    --bg:#F6F8FB;
                    --text:#0B1220;
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
                    display:flex; gap:.6rem; align-items:center; color:#FFFFFF; text-shadow:0 1px 2px rgba(0,0,0,.25);
                }
                #<?php echo $uid; ?> .subtitle{
                    margin:6px 0 0; font-size:.95rem; color:#FFFFFF; font-weight:600; opacity:.98;
                }
                /* Corpo */
                #<?php echo $uid; ?> .body{padding:20px; background:var(--bg);}
                #<?php echo $uid; ?> .grid{display:grid; gap:16px; grid-template-columns:repeat(12,1fr);} 
                @media (max-width:780px){#<?php echo $uid; ?> .grid{grid-template-columns:1fr;}}
                #<?php echo $uid; ?> .field{grid-column:span 4 / span 4;}
                #<?php echo $uid; ?> .field.wide{grid-column:span 12 / span 12;}
                @media (max-width:780px){#<?php echo $uid; ?> .field{grid-column:1/-1;}}
                #<?php echo $uid; ?> label{display:block; font-size:.92rem; color:var(--blue-900); margin:2px 0 6px; font-weight:600;}
                #<?php echo $uid; ?> input[type="date"]{
                    width:100%; background:#fff; border:1px solid var(--silver-200);
                    border-radius:12px; padding:12px 14px; font-size:1rem; outline:none;
                    transition:border .2s, box-shadow .2s;
                }
                #<?php echo $uid; ?> input:focus{border-color:var(--blue-700); box-shadow:0 0 0 3px rgba(27,59,111,.15);} 
                #<?php echo $uid; ?> .help{font-size:.85rem; color:#465266; margin-top:6px;}
                #<?php echo $uid; ?> .inline{display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-top:6px;}
                #<?php echo $uid; ?> .checkbox{display:flex; align-items:center; gap:8px; font-size:.92rem; color:#0F2C59;}
                /* Ações */
                #<?php echo $uid; ?> .actions{display:flex; gap:10px; flex-wrap:wrap; margin-top:6px;}
                #<?php echo $uid; ?> .btn{border:none; cursor:pointer; padding:12px 16px; border-radius:12px; font-weight:700; letter-spacing:.3px; transition:transform .06s ease, box-shadow .2s;}
                #<?php echo $uid; ?> .btn:active{transform:translateY(1px);} 
                #<?php echo $uid; ?> .btn-primary{background:linear-gradient(135deg,var(--blue-900),var(--blue-700)); color:#fff; box-shadow:0 10px 24px rgba(27,59,111,.18);} 
                #<?php echo $uid; ?> .btn-secondary{background:#f9fafb; color:var(--blue-900); border:1px solid var(--silver-200);} 
                /* Alertas */
                #<?php echo $uid; ?> .alert{margin-top:12px; padding:12px; border-radius:12px; font-size:.92rem; display:none;}
                #<?php echo $uid; ?> .warn{background:#FFF7ED; color:#7C2D12; border:1px solid #FED7AA;}
                #<?php echo $uid; ?> .err{background:#FEF2F2; color:#7F1D1D; border:1px solid #FECACA;}
                /* Resultados */
                #<?php echo $uid; ?> .results{margin-top:18px; display:grid; gap:14px; grid-template-columns:repeat(12,1fr);} 
                #<?php echo $uid; ?> .kpi{grid-column:span 3 / span 3; background:#fff; border:1px solid var(--silver-200); border-radius:14px; padding:14px;}
                @media (max-width:780px){#<?php echo $uid; ?> .kpi{grid-column:1/-1;}}
                #<?php echo $uid; ?> .kpi .label{font-size:.85rem; color:#465266; margin-bottom:6px;}
                #<?php echo $uid; ?> .kpi .value{font-size:1.25rem; font-weight:800; color:var(--blue-900);} 
                /* Nota */
                #<?php echo $uid; ?> .note{margin-top:10px; font-size:.85rem; color:#465266; background:#fff; border:1px dashed var(--silver); padding:10px 12px; border-radius:12px;}
            </style>

            <div class="card" role="region" aria-label="Contador de dias úteis">
                <div class="header">
                    <h3 class="title">📅 Contar dias úteis</h3>
                    <p class="subtitle">Calcula automaticamente feriados nacionais do Brasil e exclui sábados e domingos.</p>
                </div>
                <div class="body">
                    <div class="grid">
                        <div class="field">
                            <label for="<?php echo $uid; ?>_ini">Data inicial</label>
                            <input type="date" id="<?php echo $uid; ?>_ini" aria-label="Data inicial">
                            <div class="help">Selecione a data de início do período.</div>
                        </div>
                        <div class="field">
                            <label for="<?php echo $uid; ?>_fim">Data final</label>
                            <input type="date" id="<?php echo $uid; ?>_fim" aria-label="Data final">
                            <div class="help">Selecione a data de término do período.</div>
                        </div>
                        <div class="field wide">
                            <div class="inline">
                                <label class="checkbox" for="<?php echo $uid; ?>_incluir_ini">
                                    <input type="checkbox" id="<?php echo $uid; ?>_incluir_ini" checked>
                                    Incluir a data inicial no cálculo
                                </label>
                                <label class="checkbox" for="<?php echo $uid; ?>_incluir_fim">
                                    <input type="checkbox" id="<?php echo $uid; ?>_incluir_fim" checked>
                                    Incluir a data final no cálculo
                                </label>
                            </div>
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
                            <div class="label">Dias totais</div>
                            <div class="value" id="<?php echo $uid; ?>_kpi_total">—</div>
                        </div>
                        <div class="kpi">
                            <div class="label">Dias úteis</div>
                            <div class="value" id="<?php echo $uid; ?>_kpi_uteis">—</div>
                        </div>
                        <div class="kpi">
                            <div class="label">Fins de semana</div>
                            <div class="value" id="<?php echo $uid; ?>_kpi_fds">—</div>
                        </div>
                        <div class="kpi">
                            <div class="label">Feriados</div>
                            <div class="value" id="<?php echo $uid; ?>_kpi_fer">—</div>
                        </div>
                    </div>

                    <div class="note">💡 Feriados nacionais (fixos e móveis) são considerados automaticamente. Feriados estaduais/municipais não estão inclusos.</div>
                </div>
            </div>

            <script>
                (function(){
                    const $ = (id)=>document.getElementById(id);
                    const ini = $('<?php echo $uid; ?>_ini');
                    const fim = $('<?php echo $uid; ?>_fim');
                    const incluirIni = $('<?php echo $uid; ?>_incluir_ini');
                    const incluirFim = $('<?php echo $uid; ?>_incluir_fim');

                    const warn = $('<?php echo $uid; ?>_warn');
                    const err  = $('<?php echo $uid; ?>_err');
                    const res  = $('<?php echo $uid; ?>_results');

                    const kTot = $('<?php echo $uid; ?>_kpi_total');
                    const kUte = $('<?php echo $uid; ?>_kpi_uteis');
                    const kFds = $('<?php echo $uid; ?>_kpi_fds');
                    const kFer = $('<?php echo $uid; ?>_kpi_fer');

                    function show(el, msg){ el.style.display='block'; el.textContent=msg; }
                    function hide(el){ el.style.display='none'; el.textContent=''; }

                    function parseDate(str){
                        if(!str) return null;
                        const [y,m,d] = str.split('-').map(Number);
                        if (!y || !m || !d) return null;
                        const dt = new Date(y, m-1, d);
                        dt.setHours(0,0,0,0);
                        return dt;
                    }

                    function addDays(date, n){
                        const d = new Date(date);
                        d.setDate(d.getDate() + n);
                        return d;
                    }

                    function fmtISO(date){
                        const y = date.getFullYear();
                        const m = String(date.getMonth()+1).padStart(2,'0');
                        const d = String(date.getDate()).padStart(2,'0');
                        return `${y}-${m}-${d}`;
                    }

                    function isWeekend(date){
                        const dow = date.getDay(); // 0=Dom, 6=Sáb
                        return dow === 0 || dow === 6;
                    }

                    // Algoritmo de Meeus/Jones/Butcher para data da Páscoa
                    function easterDate(year){
                        const a = year % 19;
                        const b = Math.floor(year / 100);
                        const c = year % 100;
                        const d = Math.floor(b / 4);
                        const e = b % 4;
                        const f = Math.floor((b + 8) / 25);
                        const g = Math.floor((b - f + 1) / 3);
                        const h = (19*a + b - d - g + 15) % 30;
                        const i = Math.floor(c / 4);
                        const k = c % 4;
                        const l = (32 + 2*e + 2*i - h - k) % 7;
                        const m = Math.floor((a + 11*h + 22*l) / 451);
                        const month = Math.floor((h + l - 7*m + 114) / 31); // 3=Março, 4=Abril
                        const day = ((h + l - 7*m + 114) % 31) + 1;
                        const dt = new Date(year, month - 1, day);
                        dt.setHours(0,0,0,0);
                        return dt;
                    }

                    function brazilHolidaysForYear(year){
                        const set = new Set();
                        const push = (m, d)=> set.add(`${year}-${String(m).padStart(2,'0')}-${String(d).padStart(2,'0')}`);

                        // Fixos (nacionais)
                        push(1,1);   // Confraternização Universal
                        push(4,21);  // Tiradentes
                        push(5,1);   // Dia do Trabalho
                        push(9,7);   // Independência
                        push(10,12); // Nossa Senhora Aparecida
                        push(11,2);  // Finados
                        push(11,15); // Proclamação da República
                        push(11,20); // Consciência Negra (Lei 14.861/2024)
                        push(12,25); // Natal

                        // Móveis (baseados na Páscoa)
                        const easter = easterDate(year);
                        const goodFriday = addDays(easter, -2);   // Sexta-feira Santa
                        const carnivalMon = addDays(easter, -48); // Segunda de Carnaval
                        const carnivalTue = addDays(easter, -47); // Terça de Carnaval
                        const corpusChristi = addDays(easter, 60); // Corpus Christi
                        [goodFriday, carnivalMon, carnivalTue, corpusChristi].forEach(dt=>{
                            push(dt.getMonth()+1, dt.getDate());
                        });

                        return set;
                    }

                    function holidaysBetweenYears(y1, y2){
                        const set = new Set();
                        for(let y = y1; y <= y2; y++){
                            for(const k of brazilHolidaysForYear(y)) set.add(k);
                        }
                        return set;
                    }

                    function countDays(){
                        hide(warn); hide(err);
                        const d1 = parseDate(ini.value);
                        const d2 = parseDate(fim.value);
                        if(!d1 || !d2){ show(err,'Informe as duas datas.'); res.style.display='none'; return; }

                        let start = d1, end = d2;
                        if (end < start){
                            const tmp = start; start = end; end = tmp;
                            show(warn,'As datas foram invertidas para o cálculo (a inicial era maior que a final).');
                        }

                        // Inclusões das extremidades
                        let curStart = new Date(start), curEnd = new Date(end);
                        if(!incluirIni.checked){ curStart = addDays(curStart, 1); }
                        if(!incluirFim.checked){ curEnd = addDays(curEnd, -1); }
                        if (curEnd < curStart){ show(err,'O intervalo resultante é vazio após aplicar as opções de inclusão.'); res.style.display='none'; return; }

                        // Conjunto de feriados nacionais automáticos para o(s) ano(s) do intervalo
                        const ferSet = holidaysBetweenYears(curStart.getFullYear(), curEnd.getFullYear());

                        // Totais
                        const totalDias = Math.floor((curEnd - curStart)/(1000*60*60*24)) + 1;
                        let uteis = 0, fds = 0, ferCount = 0;
                        for(let d=new Date(curStart); d <= curEnd; d = addDays(d,1)){
                            const isFds = isWeekend(d);
                            const isFer = ferSet.has(fmtISO(d));
                            if (isFds){ fds++; continue; }
                            if (isFer){ ferCount++; continue; }
                            uteis++;
                        }

                        kTot.textContent = totalDias.toLocaleString('pt-BR');
                        kUte.textContent = uteis.toLocaleString('pt-BR');
                        kFds.textContent = fds.toLocaleString('pt-BR');
                        kFer.textContent = ferCount.toLocaleString('pt-BR');
                        res.style.display='grid';
                    }

                    document.getElementById('<?php echo $uid; ?>_calc').addEventListener('click', (e)=>{ e.preventDefault(); countDays(); });
                    document.getElementById('<?php echo $uid; ?>_limpar').addEventListener('click', (e)=>{
                        e.preventDefault();
                        [ini,fim].forEach(el=> el.value='');
                        incluirIni.checked=true; incluirFim.checked=true;
                        hide(warn); hide(err); res.style.display='none';
                        [kTot,kUte,kFds,kFer].forEach(el=> el.textContent='—');
                    });
                })();
            </script>
        </div>
        <?php
        return ob_get_clean();
    }
}

new IF_Contar_Dias_Uteis();
