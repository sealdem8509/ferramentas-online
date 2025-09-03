<?php
/**
 * Plugin Name: Calculadora de Rescisão Trabalhista
 * Description: Descubra em poucos passos o valor estimado da rescisão (saldo de salário, férias, 13º, aviso, FGTS e multa), seguindo regras usuais da CLT. Shortcode: [calc_rescisao]
 * Version: 1.0.0
 * Author: iFerramentaria
 * Requires at least: 5.5
 * Requires PHP: 7.2
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) { exit; }

class IF_Calc_Rescisao {
    public function __construct(){
        add_shortcode('calc_rescisao', [$this, 'render']);
    }

    private function uid(){ return 'ifcr_' . wp_generate_uuid4(); }

    public function render(){
        $uid = $this->uid();
        ob_start(); ?>
        <div id="<?php echo esc_attr($uid); ?>" class="cr-wrap">
            <style>
                /* ===== Paleta & tokens ===== */
                #<?php echo $uid; ?>{ --blue-900:#0F2C59; --blue-700:#1B3B6F; --silver:#C0C0C0; --silver-200:#E5E7EB; --bg:#F6F8FB; --text:#0B1220; }
                #<?php echo $uid; ?> .card{ background:#fff; border:1px solid var(--silver-200); border-radius:16px; box-shadow:0 6px 24px rgba(15,44,89,.08); overflow:hidden; font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,Ubuntu,Cantarell,Noto Sans,sans-serif; }
                /* Cabeçalho visível */
                #<?php echo $uid; ?> .header{ background:linear-gradient(135deg,var(--blue-900),var(--blue-700)); color:#fff; padding:20px 22px 14px; }
                #<?php echo $uid; ?> .title{ margin:0; font-size:1.35rem; font-weight:900; line-height:1.25; display:flex; gap:.6rem; align-items:center; text-shadow:0 1px 2px rgba(0,0,0,.25); }
                #<?php echo $uid; ?> .subtitle{ margin:6px 0 0; font-size:.95rem; color:#fff; font-weight:600; opacity:.98; }
                /* Corpo */
                #<?php echo $uid; ?> .body{ padding:20px; background:var(--bg);} 
                #<?php echo $uid; ?> .grid{ display:grid; gap:16px; grid-template-columns:repeat(12,1fr);} 
                @media (max-width:900px){#<?php echo $uid; ?> .grid{ grid-template-columns:1fr; }}
                #<?php echo $uid; ?> .field{ grid-column:span 6 / span 6; }
                #<?php echo $uid; ?> .field.third{ grid-column:span 4 / span 4; }
                #<?php echo $uid; ?> .field.full{ grid-column:1/-1; }
                #<?php echo $uid; ?> label{ display:block; font-size:.92rem; color:var(--blue-900); margin:2px 0 6px; font-weight:700; }
                #<?php echo $uid; ?> input[type="text"],
                #<?php echo $uid; ?> input[type="number"],
                #<?php echo $uid; ?> input[type="date"],
                #<?php echo $uid; ?> select{
                    width:100%; background:#fff; border:1px solid var(--silver-200); border-radius:12px; padding:12px 14px; font-size:1rem; outline:none; transition:border .2s, box-shadow .2s;
                }
                #<?php echo $uid; ?> input:focus, #<?php echo $uid; ?> select:focus{ border-color:var(--blue-700); box-shadow:0 0 0 3px rgba(27,59,111,.15);} 
                #<?php echo $uid; ?> .help{ font-size:.85rem; color:#465266; margin-top:6px; }
                #<?php echo $uid; ?> .inline{ display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
                /* Ações */
                #<?php echo $uid; ?> .actions{ display:flex; gap:10px; flex-wrap:wrap; margin-top:8px; }
                #<?php echo $uid; ?> .btn{ border:none; cursor:pointer; padding:12px 16px; border-radius:12px; font-weight:800; letter-spacing:.3px; transition:transform .06s ease, box-shadow .2s, opacity .2s; }
                #<?php echo $uid; ?> .btn:active{ transform:translateY(1px);} 
                #<?php echo $uid; ?> .btn-primary{ background:linear-gradient(135deg,var(--blue-900),var(--blue-700)); color:#fff; box-shadow:0 10px 24px rgba(27,59,111,.18);} 
                #<?php echo $uid; ?> .btn-secondary{ background:#f9fafb; color:var(--blue-900); border:1px solid var(--silver-200);} 
                /* Alertas */
                #<?php echo $uid; ?> .alert{ margin-top:12px; padding:12px; border-radius:12px; font-size:.92rem; display:none; }
                #<?php echo $uid; ?> .warn{ background:#FFF7ED; color:#7C2D12; border:1px solid #FED7AA; }
                #<?php echo $uid; ?> .err{ background:#FEF2F2; color:#7F1D1D; border:1px solid #FECACA; }
                /* KPIs */
                #<?php echo $uid; ?> .results{ margin-top:18px; display:grid; gap:14px; grid-template-columns:repeat(12,1fr);} 
                #<?php echo $uid; ?> .kpi{ grid-column:span 4 / span 4; background:#fff; border:1px solid var(--silver-200); border-radius:14px; padding:14px; }
                @media (max-width:900px){#<?php echo $uid; ?> .kpi{ grid-column:1/-1; }}
                #<?php echo $uid; ?> .kpi .label{ font-size:.85rem; color:#465266; margin-bottom:6px; }
                #<?php echo $uid; ?> .kpi .value{ font-size:1.1rem; font-weight:800; color:var(--blue-900);} 
                /* Tabela/Lista */
                #<?php echo $uid; ?> .list{ margin-top:10px; background:#fff; border:1px solid var(--silver-200); border-radius:12px; overflow:hidden; }
                #<?php echo $uid; ?> .list-header{ background:#F3F4F6; color:#0F2C59; font-weight:900; padding:10px 12px; }
                #<?php echo $uid; ?> .row{ display:grid; grid-template-columns: 1.5fr 1fr; gap:8px; padding:10px 12px; border-top:1px dashed var(--silver-200); font-size:.95rem; }
                #<?php echo $uid; ?> .row strong{ color:var(--blue-900); }
                #<?php echo $uid; ?> .row:nth-child(odd){ background:#FAFAFA; }
                /* Nota */
                #<?php echo $uid; ?> .note{ margin-top:12px; font-size:.85rem; color:#465266; background:#fff; border:1px dashed var(--silver); padding:10px 12px; border-radius:12px; }
            </style>

            <div class="card" role="region" aria-label="Calculadora de Rescisão Trabalhista">
                <div class="header">
                    <h3 class="title">📄 Calculadora de Rescisão Trabalhista</h3>
                    <p class="subtitle">Descubra em 2 minutos quanto você deve receber na sua rescisão!</p>
                </div>
                <div class="body">
                    <div class="grid">
                        <div class="field third">
                            <label for="<?php echo $uid; ?>_sal">Salário (R$)</label>
                            <input type="text" id="<?php echo $uid; ?>_sal" placeholder="Ex.: 3.200,00">
                        </div>
                        <div class="field third">
                            <label for="<?php echo $uid; ?>_adm">Data de Contratação</label>
                            <input type="date" id="<?php echo $uid; ?>_adm">
                        </div>
                        <div class="field third">
                            <label for="<?php echo $uid; ?>_dem">Data de Dispensa</label>
                            <input type="date" id="<?php echo $uid; ?>_dem">
                        </div>
                        <div class="field third">
                            <label for="<?php echo $uid; ?>_forma">Forma de Demissão</label>
                            <select id="<?php echo $uid; ?>_forma">
                                <option value="sem">Sem justa causa (empregador)</option>
                                <option value="pedido">Pedido de demissão (empregado)</option>
                                <option value="justa">Com justa causa</option>
                                <option value="acordo">Acordo (art. 484-A)</option>
                            </select>
                        </div>
                        <div class="field third">
                            <label for="<?php echo $uid; ?>_aviso">Aviso Prévio</label>
                            <select id="<?php echo $uid; ?>_aviso">
                                <option value="trabalhado">Trabalhado</option>
                                <option value="indenizado">Indenizado</option>
                                <option value="dispensado">Dispensado do aviso</option>
                                <option value="na">Não se aplica</option>
                            </select>
                            <div class="help">Sem justa: 30 dias + 3 dias/ano (máx. 90).</div>
                        </div>
                        <div class="field third">
                            <label for="<?php echo $uid; ?>_ferV">Tem Férias Vencidas?</label>
                            <select id="<?php echo $uid; ?>_ferV">
                                <option value="nao">Não</option>
                                <option value="sim">Sim (30 dias)</option>
                            </select>
                        </div>
                        <div class="field full">
                            <div class="actions">
                                <button class="btn btn-primary" id="<?php echo $uid; ?>_calc">Calcular Rescisão</button>
                                <button class="btn btn-secondary" id="<?php echo $uid; ?>_limpar">Limpar</button>
                            </div>
                        </div>
                    </div>

                    <div class="alert warn" id="<?php echo $uid; ?>_warn"></div>
                    <div class="alert err" id="<?php echo $uid; ?>_err"></div>

                    <div class="list" id="<?php echo $uid; ?>_lista" style="display:none;">
                        <div class="list-header">Resumo da Rescisão</div>
                        <div class="row"><div><strong>Saldo de Salário</strong></div><div id="<?php echo $uid; ?>_r_saldo">—</div></div>
                        <div class="row"><div><strong>Férias Proporcionais</strong></div><div id="<?php echo $uid; ?>_r_fp">—</div></div>
                        <div class="row"><div><strong>1/3 de Férias Proporcionais</strong></div><div id="<?php echo $uid; ?>_r_fp13">—</div></div>
                        <div class="row"><div><strong>Férias Vencidas</strong></div><div id="<?php echo $uid; ?>_r_fv">—</div></div>
                        <div class="row"><div><strong>1/3 de Férias Vencidas</strong></div><div id="<?php echo $uid; ?>_r_fv13">—</div></div>
                        <div class="row"><div><strong>13º Proporcional</strong></div><div id="<?php echo $uid; ?>_r_13p">—</div></div>
                        <div class="row"><div><strong>Aviso Prévio</strong></div><div id="<?php echo $uid; ?>_r_aviso">—</div></div>
                        <div class="row"><div><strong>FGTS (estimado)</strong></div><div id="<?php echo $uid; ?>_r_fgts">—</div></div>
                        <div class="row"><div><strong>Multa FGTS</strong></div><div id="<?php echo $uid; ?>_r_multa">—</div></div>
                        <div class="row"><div><strong>Valor a Receber de Rescisão (acerto)</strong></div><div id="<?php echo $uid; ?>_r_acerto">—</div></div>
                        <div class="row"><div><strong>Total Geral (Rescisão + FGTS + Multa FGTS)</strong></div><div id="<?php echo $uid; ?>_r_total">—</div></div>
                    </div>

                    <div class="note">⚖️ <strong>Importante:</strong> Este simulador aplica regras usuais da CLT: férias proporcionais (1/12 ao mês ≥15 dias), 1/3 constitucional, 13º proporcional (por mês ≥15 dias), aviso prévio (30 dias + 3 por ano, máx. 90). <strong>Tributação:</strong> estima INSS/IRRF quando aplicável. <strong>FGTS:</strong> valor estimado com base em 8%/mês do salário + 8% do 13º e, quando indenizado, sobre o aviso. A multa é de 40% (sem justa causa) ou 20% (acordo). Resultados são estimativas e podem variar por políticas internas/convênios. Consulte o RH para o cálculo oficial.</div>
                </div>
            </div>

            <script>
            (function(){
                const $ = (id)=>document.getElementById(id);
                const sal = $('<?php echo $uid; ?>_sal');
                const adm = $('<?php echo $uid; ?>_adm');
                const dem = $('<?php echo $uid; ?>_dem');
                const forma = $('<?php echo $uid; ?>_forma');
                const aviso = $('<?php echo $uid; ?>_aviso');
                const ferV = $('<?php echo $uid; ?>_ferV');

                const warn = $('<?php echo $uid; ?>_warn');
                const err  = $('<?php echo $uid; ?>_err');
                const lista= $('<?php echo $uid; ?>_lista');

                const out = {
                    saldo: $('<?php echo $uid; ?>_r_saldo'), fp: $('<?php echo $uid; ?>_r_fp'), fp13: $('<?php echo $uid; ?>_r_fp13'),
                    fv: $('<?php echo $uid; ?>_r_fv'), fv13: $('<?php echo $uid; ?>_r_fv13'), d13: $('<?php echo $uid; ?>_r_13p'),
                    aviso: $('<?php echo $uid; ?>_r_aviso'), fgts: $('<?php echo $uid; ?>_r_fgts'), multa: $('<?php echo $uid; ?>_r_multa'),
                    acerto: $('<?php echo $uid; ?>_r_acerto'), total: $('<?php echo $uid; ?>_r_total')
                };

                function show(el,msg){ el.style.display='block'; if(msg!==undefined) el.textContent = msg; }
                function hide(el){ el.style.display='none'; el.textContent=''; }
                const brl = v=> v.toLocaleString('pt-BR',{style:'currency',currency:'BRL'});
                const parseMoneyBR = (txt)=>{ if(!txt) return 0; const s=String(txt).trim().replace(/\s/g,''); if(/,\d{1,2}$/.test(s)) return parseFloat(s.replace(/\./g,'').replace(',','.'))||0; return parseFloat(s.replace(/\./g,''))||0; };
                const dFrom = (val)=>{ if(!val) return null; const [y,m,d]=val.split('-').map(Number); if(!y||!m||!d) return null; const dt=new Date(Date.UTC(y,m-1,d)); return new Date(dt.getUTCFullYear(), dt.getUTCMonth(), dt.getUTCDate()); };
                const daysInMonth = (y,m)=> new Date(y,m+1,0).getDate();
                const addDays = (date,n)=>{ const d=new Date(date); d.setDate(d.getDate()+n); return d; };

                function monthsBetweenInclusive(d1,d2){ // conta meses cheios entre datas
                    let y=d1.getFullYear(), m=d1.getMonth();
                    let y2=d2.getFullYear(), m2=d2.getMonth();
                    return (y2-y)*12 + (m2-m) + 1;
                }
                function yearsOfService(d1,d2){ let y = d2.getFullYear()-d1.getFullYear(); const adj = (d2.getMonth()<d1.getMonth() || (d2.getMonth()==d1.getMonth() && d2.getDate()<d1.getDate()))?1:0; return Math.max(0,y-adj); }

                function mesesProporcionais13(ano, endDate){ // meses no ano até a dispensa, contando mês com >=15 dias trabalhados
                    let total=0;
                    for(let m=0;m<12;m++){
                        const ms=new Date(ano,m,1), me=new Date(ano,m+1,0);
                        if (endDate < ms) break; // passou
                        const s=ms; // assume início do mês (simples)
                        const e = endDate<me? endDate: me;
                        const dias = Math.floor((e - s)/(1000*60*60*24)) + 1;
                        if (dias >= 15) total++;
                    }
                    return total;
                }

                // ===== Tabelas 2025 (estimativas) =====
                const INSS = [ {limit:1518.00, rate:0.075}, {limit:2793.88, rate:0.09}, {limit:4190.83, rate:0.12}, {limit:8157.41, rate:0.14} ];
                const IRRF = [ {min:0, max:2428.80, rate:0.00, ded:0.00}, {min:2428.81, max:2826.65, rate:0.075, ded:182.16}, {min:2826.66, max:3751.05, rate:0.15, ded:394.16}, {min:3751.06, max:4664.68, rate:0.225, ded:675.49}, {min:4664.69, max:Infinity, rate:0.275, ded:908.73} ];
                const DED_DEP = 189.59; const DESCONTO_SIMPLIFICADO = 607.20;
                function calcINSS(base){ let restante=Math.max(0, Math.min(base, INSS[INSS.length-1].limit)); let prev=0,total=0; for(const b of INSS){ const parcela=Math.max(0, Math.min(restante, b.limit - prev)); if(parcela<=0) break; total+= parcela*b.rate; prev=b.limit; } return total; }
                function calcIRRF(base){ const f=IRRF.find(x=> base>=x.min && base<=x.max); if(!f) return 0; return Math.max(0, base*f.rate - f.ded); }

                function calcular(){
                    hide(warn); hide(err);
                    const salario = parseMoneyBR(sal.value);
                    const dAdm = dFrom(adm.value); const dDem = dFrom(dem.value);
                    if(!salario || salario<=0){ show(err,'Informe um salário válido.'); lista.style.display='none'; return; }
                    if(!dAdm || !dDem || dDem < dAdm){ show(err,'Datas inválidas (a dispensa deve ser igual ou posterior à contratação).'); lista.style.display='none'; return; }

                    // Saldo de salário (dias trabalhados no mês da dispensa, até a data informada)
                    const y = dDem.getFullYear(), m = dDem.getMonth();
                    const dim = daysInMonth(y,m);
                    const diasTrabalhados = dDem.getDate(); // do dia 1 até a data
                    let saldoSalarioBruto = salario * (diasTrabalhados / dim);

                    // Férias proporcionais: 1/12 por mês >=15 dias no ciclo aquisitivo (simplificação: últimos 12 meses até a dispensa)
                    const inicioCiclo = new Date(dDem); inicioCiclo.setFullYear(dDem.getFullYear()-1); inicioCiclo.setDate(inicioCiclo.getDate()+1);
                    let mesesFerias = 0; // conta meses com >=15 dias
                    for(let i=0;i<12;i++){
                        const ms = new Date(inicioCiclo.getFullYear(), inicioCiclo.getMonth()+i, 1);
                        const me = new Date(ms.getFullYear(), ms.getMonth()+1, 0);
                        const s = ms; const e = dDem<me? dDem: me;
                        if (e < s) break;
                        const dias = Math.floor((e - s)/(1000*60*60*24)) + 1;
                        if (dias >= 15) mesesFerias++;
                    }
                    let feriasPropBruto = salario * (mesesFerias/12);
                    let umTercoFeriasProp = feriasPropBruto/3;

                    // Férias vencidas (opcional)
                    let feriasVenc = 0, umTercoFeriasVenc = 0;
                    if (ferV.value === 'sim'){ feriasVenc = salario; umTercoFeriasVenc = salario/3; }

                    // 13º proporcional (no ano da dispensa)
                    const meses13 = mesesProporcionais13(dDem.getFullYear(), dDem);
                    const decimoPropBruto = salario * (meses13/12);

                    // Aviso prévio
                    const anos = yearsOfService(dAdm, dDem);
                    let diasAviso = 30 + Math.min(60, 3*anos); // máx 90
                    let avisoValor = 0; // pode ser positivo (indenizado) ou 0
                    if (forma.value === 'sem' || forma.value === 'acordo'){
                        if (aviso.value === 'indenizado') avisoValor = salario * (diasAviso/30);
                        if (aviso.value === 'trabalhado' || aviso.value === 'dispensado') avisoValor = 0; // sem verba indenizatória
                    } else if (forma.value === 'pedido'){
                        // se empregado não cumprir aviso, pode haver DESCONTO
                        if (aviso.value === 'dispensado') avisoValor = -salario * (30/30); // desconto de 30 dias (padrão)
                        else avisoValor = 0;
                    } else if (forma.value === 'justa'){
                        avisoValor = 0; // não há aviso indenizado devido ao empregado
                    }

                    // FGTS estimado (8%/mês do salário + 8% do 13º + 8% do aviso indenizado)
                    const mesesContrato = monthsBetweenInclusive(dAdm, dDem);
                    let fgts = 0.08 * (salario * mesesContrato + decimoPropBruto + Math.max(0,avisoValor));

                    // Multa do FGTS
                    let multaFgts = 0;
                    if (forma.value === 'sem') multaFgts = fgts * 0.40;
                    if (forma.value === 'acordo') multaFgts = fgts * 0.20;

                    // Regras por forma de desligamento
                    if (forma.value === 'pedido'){
                        // mantém férias proporcionais + 1/3 e 13º prop; sem multa FGTS; aviso pode virar desconto
                    } else if (forma.value === 'justa'){
                        // justa causa: sem férias proporcionais, sem 13º proporcional, sem aviso indenizado, sem multa FGTS
                        feriasPropBruto = 0; umTercoFeriasProp = 0; fgts = fgts; multaFgts = 0; avisoValor = 0; // saldo de salário e férias vencidas permanecem
                    }

                    // ===== Tributos (estimativa) =====
                    // INSS/IR sobre 13º (cobrados na 2ª/única geralmente)
                    const inss13 = calcINSS(decimoPropBruto);
                    const baseIR13sem = Math.max(0, decimoPropBruto - inss13);
                    const baseIR13com = Math.max(0, baseIR13sem - DESCONTO_SIMPLIFICADO);
                    const ir13 = Math.min(calcIRRF(baseIR13sem), calcIRRF(baseIR13com));

                    // INSS/IR sobre saldo de salário (competência mensal proporcional)
                    const inssSaldo = calcINSS(saldoSalarioBruto);
                    const baseIRSaldoSem = Math.max(0, saldoSalarioBruto - inssSaldo);
                    const baseIRSaldoCom = Math.max(0, baseIRSaldoSem - DESCONTO_SIMPLIFICADO);
                    const irSaldo = Math.min(calcIRRF(baseIRSaldoSem), calcIRRF(baseIRSaldoCom));

                    // Férias indenizadas: não sofrem INSS; IR pode incidir sobre férias gozadas, mas sobre indenizadas há isenção (tratamento simplificado: sem IR/INSS)

                    // Líquidos (onde aplicável)
                    const saldoSalario = Math.max(0, saldoSalarioBruto - inssSaldo - irSaldo);
                    const feriasProp = feriasPropBruto; // indenizadas
                    const feriasProp13 = umTercoFeriasProp; // indenizadas
                    const feriasVencLiq = feriasVenc; // indenizadas
                    const feriasVenc13Liq = umTercoFeriasVenc;
                    const decimoProp = Math.max(0, decimoPropBruto - inss13 - ir13);
                    const avisoLiq = avisoValor; // indenizado não tem INSS/IR; desconto se negativo

                    // Acerto (sem FGTS/multa)
                    const acerto = saldoSalario + feriasProp + feriasProp13 + feriasVencLiq + feriasVenc13Liq + decimoProp + avisoLiq;

                    // Total geral
                    const totalGeral = acerto + fgts + multaFgts;

                    // Output
                    out.saldo.textContent = brl(saldoSalario);
                    out.fp.textContent = brl(feriasProp);
                    out.fp13.textContent = brl(feriasProp13);
                    out.fv.textContent = brl(feriasVencLiq);
                    out.fv13.textContent = brl(feriasVenc13Liq);
                    out.d13.textContent = brl(decimoProp);
                    out.aviso.textContent = brl(avisoLiq);
                    out.fgts.textContent = brl(fgts);
                    out.multa.textContent = brl(multaFgts);
                    out.acerto.textContent = brl(acerto);
                    out.total.textContent = brl(totalGeral);
                    lista.style.display='block';
                }

                $('<?php echo $uid; ?>_calc').addEventListener('click', (e)=>{ e.preventDefault(); calcular(); });
                $('<?php echo $uid; ?>_limpar').addEventListener('click', (e)=>{
                    e.preventDefault();
                    sal.value=''; adm.value=''; dem.value=''; forma.value='sem'; aviso.value='trabalhado'; ferV.value='nao';
                    hide(warn); hide(err); lista.style.display='none';
                    Object.values(out).forEach(el=> el.textContent='—');
                });
            })();
            </script>
        </div>
        <?php return ob_get_clean(); }
}

new IF_Calc_Rescisao();

// Shortcode: [calc_rescisao]
