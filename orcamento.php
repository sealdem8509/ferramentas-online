<?php
/**
 * Plugin Name: Orçamento Online + WhatsApp (Single File)
 * Description: Gere orçamentos online e envie por WhatsApp com mensagem pronta. Shortcode: [orcamento_online]
 * Version:     1.0.0
 * Author:      Sealdem Santos & ChatGPT
 * License:     GPLv2 or later
 * Text Domain: orcamento-whatsapp
 */

if (!defined('ABSPATH')) exit;

class IF_Orcamento_WhatsApp_Single {
    const VER = '1.0.0';
    private $handle_style  = 'if-orc-inline-style';
    private $handle_script = 'if-orc-inline-script';

    public function __construct() {
        add_shortcode('orcamento_online', [$this, 'shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_inline_assets']);
    }

    public function shortcode($atts) {
        $site_name = get_bloginfo('name');
        ob_start(); ?>
        <div class="orc-wrap">
            <h3 class="orc-title">Orçamento Online</h3>

            <form id="orc-form" class="orc-form" autocomplete="off">
                <fieldset class="orc-box">
                    <legend>Dados do orçamento</legend>
                    <div class="orc-grid-3">
                        <div class="orc-field">
                            <label for="orc-empresa">Sua empresa / remetente</label>
                            <input type="text" id="orc-empresa" placeholder="<?php echo esc_attr($site_name); ?>" value="<?php echo esc_attr($site_name); ?>">
                        </div>
                        <div class="orc-field">
                            <label for="orc-cliente">Cliente</label>
                            <input type="text" id="orc-cliente" placeholder="Nome do cliente">
                        </div>
                        <div class="orc-field">
                            <label for="orc-fone">WhatsApp do cliente (somente números)</label>
                            <input type="tel" id="orc-fone" inputmode="numeric" pattern="[0-9]*" placeholder="Ex.: 5591987654321">
                        </div>
                    </div>
                    <div class="orc-grid-3">
                        <div class="orc-field">
                            <label for="orc-moeda">Moeda</label>
                            <input type="text" id="orc-moeda" value="R$">
                        </div>
                        <div class="orc-field">
                            <label for="orc-valid">Validade (dias)</label>
                            <input type="number" id="orc-valid" min="1" step="1" value="7">
                        </div>
                        <div class="orc-field">
                            <label for="orc-numero">Nº do orçamento (opcional)</label>
                            <input type="text" id="orc-numero" placeholder="Ex.: 2025-0001">
                        </div>
                    </div>
                </fieldset>

                <fieldset class="orc-box">
                    <legend>Itens</legend>

                    <div class="orc-items-head">
                        <div>Descrição</div>
                        <div>Qtd</div>
                        <div>V. Unitário</div>
                        <div>Subtotal</div>
                        <div></div>
                    </div>
                    <div id="orc-items" class="orc-items"></div>

                    <div class="orc-items-actions">
                        <button type="button" id="orc-add" class="orc-btn-secondary">+ Adicionar item</button>
                        <button type="button" id="orc-clear-items" class="orc-btn-tertiary">Limpar itens</button>
                    </div>
                </fieldset>

                <fieldset class="orc-box">
                    <legend>Ajustes</legend>
                    <div class="orc-grid-4">
                        <div class="orc-field">
                            <label for="orc-desc">Desconto (%)</label>
                            <input type="number" id="orc-desc" min="0" step="0.01" value="0">
                        </div>
                        <div class="orc-field">
                            <label for="orc-taxa">Imposto/Taxa (%)</label>
                            <input type="number" id="orc-taxa" min="0" step="0.01" value="0">
                        </div>
                        <div class="orc-field">
                            <label for="orc-extra">Acréscimos/Frete (R$)</label>
                            <input type="number" id="orc-extra" min="0" step="0.01" value="0">
                        </div>
                        <div class="orc-field">
                            <label for="orc-obs">Observações</label>
                            <input type="text" id="orc-obs" placeholder="Prazo, forma de pagamento, etc.">
                        </div>
                    </div>
                </fieldset>

                <div class="orc-totais orc-box">
                    <div class="orc-row"><span>Subtotal</span><strong id="orc-subtotal">R$ 0,00</strong></div>
                    <div class="orc-row"><span>Desconto</span><strong id="orc-desconto">- R$ 0,00</strong></div>
                    <div class="orc-row"><span>Impostos/Taxas</span><strong id="orc-impostos">R$ 0,00</strong></div>
                    <div class="orc-row"><span>Acréscimos/Frete</span><strong id="orc-acrescimos">R$ 0,00</strong></div>
                    <div class="orc-row orc-total"><span>Total</span><strong id="orc-total">R$ 0,00</strong></div>
                </div>

                <div class="orc-actions">
                    <button type="button" id="orc-whats" class="orc-btn">Enviar por WhatsApp</button>
                    <button type="button" id="orc-link"  class="orc-btn-secondary">Gerar link WhatsApp</button>
                    <button type="button" id="orc-copy"  class="orc-btn-secondary">Copiar mensagem</button>
                    <button type="button" id="orc-print" class="orc-btn-secondary">Imprimir / Salvar PDF</button>
                    <button type="button" id="orc-csv"   class="orc-btn-secondary">Baixar CSV</button>
                </div>

                <div id="orc-link-out" class="orc-link-out" hidden>
                    <label>Link do WhatsApp (clique para copiar):</label>
                    <input type="text" id="orc-link-input" readonly>
                </div>

                <p class="orc-hint">Dica: se o WhatsApp do cliente for do Brasil e você digitar apenas DDD + número, o plugin tenta adicionar automaticamente o DDI <strong>55</strong>.</p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function enqueue_inline_assets() {
        wp_register_style($this->handle_style, false, [], self::VER);
        wp_enqueue_style($this->handle_style);
        wp_register_script($this->handle_script, false, [], self::VER, true);
        wp_enqueue_script($this->handle_script);

        wp_add_inline_style($this->handle_style, $this->css());
        wp_add_inline_script($this->handle_script, $this->js());
    }

    private function css() {
        return <<<CSS
:root{
  --orc-primary:#0F2C59;
  --orc-accent:#1B3B6F;
  --orc-bg:#f7f9fc;
  --orc-white:#fff;
}
.orc-wrap{
  background:var(--orc-white); border:1px solid #e6e9ef; border-radius:16px;
  padding:20px; box-shadow:0 6px 16px rgba(15,44,89,0.06); max-width:980px; margin:16px auto;
}
.orc-title{ margin:0 0 12px; color:var(--orc-primary); font-weight:700; }
.orc-form{ display:grid; gap:14px; }
.orc-box{ border:1px solid #e6e9ef; border-radius:12px; padding:12px; background:#fff; }
.orc-box > legend{ padding:0 6px; font-weight:700; color:var(--orc-primary); }
.orc-grid-3{ display:grid; grid-template-columns: repeat(3,1fr); gap:12px; }
.orc-grid-4{ display:grid; grid-template-columns: repeat(4,1fr); gap:12px; }
.orc-field{ display:flex; flex-direction:column; }
.orc-field label{ font-size:13px; color:#475569; margin-bottom:6px; }
.orc-field input{
  border:1px solid #d5d9e3; border-radius:10px; padding:10px 12px; font-size:14px; outline:none; background:var(--orc-bg);
}

/* Itens */
.orc-items-head{
  display:grid; grid-template-columns: 1fr 100px 160px 160px 60px;
  gap:8px; font-weight:700; color:#334155; margin-bottom:8px;
}
.orc-items{ display:grid; gap:8px; }
.orc-item{
  display:grid; grid-template-columns: 1fr 100px 160px 160px 60px;
  gap:8px; align-items:center;
}
.orc-item input[type="text"]{ width:100%; }
.orc-item input[type="number"]{ width:100%; }
.orc-item .orc-sub{ text-align:right; padding:10px 12px; background:#fff; border:1px solid #e6e9ef; border-radius:10px; font-weight:700; }
.orc-item .orc-del{
  border:1px solid #ef4444; color:#ef4444; background:transparent; border-radius:10px; padding:8px; cursor:pointer;
}
.orc-items-actions{ display:flex; gap:8px; margin-top:8px; }

/* Totais */
.orc-totais .orc-row{ display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px dashed #e6e9ef; }
.orc-totais .orc-row:last-child{ border-bottom:none; }
.orc-totais .orc-total strong{ font-size:18px; color:#0f172a; }

/* Botões */
.orc-actions{ display:flex; flex-wrap:wrap; gap:8px; }
.orc-btn{
  padding:12px 16px; border:none; border-radius:12px;
  background:var(--orc-primary); color:#fff; font-weight:700; cursor:pointer;
  box-shadow:0 6px 14px rgba(15,44,89,0.18);
}
.orc-btn:hover{ background:var(--orc-accent); }
.orc-btn-secondary{
  padding:10px 14px; border:1px solid var(--orc-primary); border-radius:10px; background:transparent; color:var(--orc-primary); font-weight:700; cursor:pointer;
}
.orc-btn-tertiary{
  padding:10px 14px; border:1px dashed var(--orc-primary); border-radius:10px; background:transparent; color:var(--orc-primary); font-weight:600; cursor:pointer; font-size:12px;
}

.orc-link-out{ display:grid; gap:6px; }
.orc-link-out input{
  border:1px solid #d5d9e3; border-radius:8px; padding:10px 12px; font-size:14px; background:#fff;
}
.orc-hint{ font-size:12px; color:#64748b; }

@media (max-width:960px){
  .orc-grid-3{ grid-template-columns:1fr; }
  .orc-grid-4{ grid-template-columns:1fr; }
  .orc-items-head, .orc-item{
     grid-template-columns: 1fr 1fr 1fr 1fr 60px;
  }
}
CSS;
    }

    private function js() {
        return <<<JS
(function(){
  function onReady(fn){
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn, {once:true});
    else fn();
  }
  onReady(function(){
    const $ = (s) => document.querySelector(s);
    const $$ = (s) => Array.from(document.querySelectorAll(s));

    const itemsBox = $('#orc-items');
    const addBtn = $('#orc-add');
    const clearItemsBtn = $('#orc-clear-items');

    const inDesc = $('#orc-desc');
    const inTaxa = $('#orc-taxa');
    const inExtra = $('#orc-extra');

    const outSubtotal = $('#orc-subtotal');
    const outDesc = $('#orc-desconto');
    const outImp = $('#orc-impostos');
    const outAcresc = $('#orc-acrescimos');
    const outTotal = $('#orc-total');

    const btnWhats = $('#orc-whats');
    const btnLink = $('#orc-link');
    const btnCopy = $('#orc-copy');
    const btnPrint = $('#orc-print');
    const btnCSV = $('#orc-csv');

    const linkWrap = $('#orc-link-out');
    const linkInput = $('#orc-link-input');

    // Helpers
    const moedaEl = $('#orc-moeda');
    const empresaEl = $('#orc-empresa');
    const clienteEl = $('#orc-cliente');
    const foneEl = $('#orc-fone');
    const validadeEl = $('#orc-valid');
    const numeroEl = $('#orc-numero');
    const obsEl = $('#orc-obs');

    function parseMoney(v){
      if (typeof v !== 'string') v = String(v ?? '');
      // aceita "1.234,56" ou "1234.56"
      v = v.replace(/\\s/g,'').replace(/\\./g,'').replace(',', '.');
      const n = parseFloat(v);
      return isNaN(n) ? 0 : n;
    }
    function fmtBRL(n, simb){
      try{
        const s = (n||0).toLocaleString('pt-BR',{minimumFractionDigits:2, maximumFractionDigits:2});
        return (simb||'R$') + ' ' + s;
      }catch(e){
        return (simb||'R$') + ' ' + (Math.round((n||0)*100)/100).toFixed(2).replace('.',',');
      }
    }

    function addItem(desc='', qtd=1, vunit=0){
      const row = document.createElement('div');
      row.className = 'orc-item';
      row.innerHTML = `
        <input type="text" class="i-desc" placeholder="Descrição do item" value="${desc.replace(/"/g,'&quot;')}">
        <input type="number" class="i-qtd"  min="0" step="1" value="${qtd}">
        <input type="number" class="i-vu"   min="0" step="0.01" value="${vunit}">
        <div class="orc-sub">R$ 0,00</div>
        <button type="button" class="orc-del" title="Remover item">Remover</button>
      `;
      itemsBox.appendChild(row);
      bindRow(row);
      recalc();
    }

    function bindRow(row){
      const iq = row.querySelector('.i-qtd');
      const iv = row.querySelector('.i-vu');
      const id = row.querySelector('.i-desc');
      const del = row.querySelector('.orc-del');
      [iq, iv, id].forEach(el => el.addEventListener('input', recalc));
      del.addEventListener('click', () => { row.remove(); recalc(); });
    }

    function getItems(){
      return $$('.orc-item').map(r => {
        const desc = r.querySelector('.i-desc').value.trim();
        const qtd  = parseMoney(r.querySelector('.i-qtd').value);
        const vu   = parseMoney(r.querySelector('.i-vu').value);
        return {desc, qtd, vu, sub: Math.max(0, qtd*vu)};
      }).filter(it => it.desc !== '' && it.qtd > 0 && it.vu >= 0);
    }

    function recalc(){
      const moeda = (moedaEl.value || 'R$').trim();
      const itens = getItems();

      // atualizar subtotais por linha
      $$('.orc-item').forEach((r,i)=>{
        const s = r.querySelector('.orc-sub');
        const item = itens[i];
        const sub = item ? item.sub : 0;
        s.textContent = fmtBRL(sub, moeda);
      });

      const subtotal = itens.reduce((a,b)=>a+b.sub,0);
      const descPerc = Math.max(0, parseMoney(inDesc.value));
      const descVal  = subtotal * (descPerc/100);

      const taxaPerc = Math.max(0, parseMoney(inTaxa.value));
      const impVal   = (subtotal - descVal) * (taxaPerc/100);

      const extraVal = Math.max(0, parseMoney(inExtra.value));

      const total = Math.max(0, subtotal - descVal + impVal + extraVal);

      outSubtotal.textContent = fmtBRL(subtotal, moeda);
      outDesc.textContent     = '- ' + fmtBRL(descVal, moeda);
      outImp.textContent      = fmtBRL(impVal, moeda);
      outAcresc.textContent   = fmtBRL(extraVal, moeda);
      outTotal.textContent    = fmtBRL(total, moeda);
    }

    function ensureSomeRows(){
      if ($$('.orc-item').length === 0) { addItem(); addItem(); addItem(); }
    }

    function normalizePhone(raw){
      // mantém apenas dígitos
      let n = String(raw||'').replace(/\\D/g,'');
      // se parece BR sem DDI (10-11 dígitos), prefixa 55
      if (n.length === 10 || n.length === 11) n = '55' + n;
      return n;
    }

    function buildMessage(){
      const moeda = (moedaEl.value || 'R$').trim();
      const empresa = (empresaEl.value || '').trim();
      const cliente = (clienteEl.value || '').trim();
      const numero  = (numeroEl.value || '').trim();
      const valid   = parseInt(validadeEl.value||'0',10);
      const obs     = (obsEl.value || '').trim();
      const hoje = new Date();
      const data = hoje.toLocaleDateString('pt-BR');

      const itens = getItems();
      const subtotal = itens.reduce((a,b)=>a+b.sub,0);
      const descPerc = Math.max(0, parseMoney(inDesc.value));
      const descVal  = subtotal * (descPerc/100);
      const taxaPerc = Math.max(0, parseMoney(inTaxa.value));
      const impVal   = (subtotal - descVal) * (taxaPerc/100);
      const extraVal = Math.max(0, parseMoney(inExtra.value));
      const total = Math.max(0, subtotal - descVal + impVal + extraVal);

      let linhas = [];
      linhas.push(`*Orçamento*${numero?` #${numero}`:''}${empresa?` — ${empresa}`:''}`);
      linhas.push(`Data: ${data}${valid>0?` | Validade: ${valid} dia(s)`:''}`);
      if (cliente) linhas.push(`Cliente: ${cliente}`);
      linhas.push('');
      linhas.push('*Itens:*');
      itens.forEach((it,idx)=>{
        const vu = fmtBRL(it.vu, moeda);
        const sb = fmtBRL(it.sub, moeda);
        linhas.push(`${idx+1}) ${it.desc} — Qtd: ${it.qtd} × ${vu} = ${sb}`);
      });
      linhas.push('');
      linhas.push(`Subtotal: ${fmtBRL(subtotal, moeda)}`);
      if (descVal>0) linhas.push(`Desconto (${descPerc.toFixed(2)}%): - ${fmtBRL(descVal, moeda)}`);
      if (impVal>0)  linhas.push(`Impostos/Taxas (${taxaPerc.toFixed(2)}%): ${fmtBRL(impVal, moeda)}`);
      if (extraVal>0)linhas.push(`Acréscimos/Frete: ${fmtBRL(extraVal, moeda)}`);
      linhas.push(`*Total: ${fmtBRL(total, moeda)}*`);
      if (obs) { linhas.push(''); linhas.push(`Obs.: ${obs}`); }

      return linhas.join('\\n');
    }

    function buildWhatsLink(phone, text){
      const base = 'https://wa.me';
      const enc  = encodeURIComponent(text);
      const p    = String(phone||'').trim();
      if (p) return `${base}/${p}?text=${enc}`;
      return `${base}/?text=${enc}`;
    }

    // Eventos
    addBtn.addEventListener('click', ()=> addItem());
    clearItemsBtn.addEventListener('click', ()=>{
      $$('#orc-items .orc-item').forEach(n => n.remove());
      recalc();
    });

    [inDesc, inTaxa, inExtra, moedaEl].forEach(el => el.addEventListener('input', recalc));
    ensureSomeRows();
    recalc();

    btnWhats.addEventListener('click', ()=>{
      const text = buildMessage();
      const phone = normalizePhone(foneEl.value);
      const link = buildWhatsLink(phone, text);
      window.open(link, '_blank');
    });

    btnLink.addEventListener('click', ()=>{
      const text = buildMessage();
      const phone = normalizePhone(foneEl.value);
      const link = buildWhatsLink(phone, text);
      linkWrap.hidden = false;
      linkInput.value = link;
      linkInput.focus(); linkInput.select();
      try { document.execCommand('copy'); } catch(e){}
    });

    btnCopy.addEventListener('click', ()=>{
      const text = buildMessage();
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text);
      } else {
        const ta = document.createElement('textarea'); ta.value = text;
        document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
      }
      btnCopy.textContent = 'Copiado!';
      setTimeout(()=>btnCopy.textContent='Copiar mensagem', 1500);
    });

    btnPrint.addEventListener('click', ()=> window.print());

    btnCSV.addEventListener('click', ()=>{
      const itens = getItems();
      const rows = [['Descrição','Qtd','Valor Unitário','Subtotal']];
      itens.forEach(it => rows.push([it.desc, String(it.qtd).replace('.',','), it.vu.toFixed(2).replace('.',','), it.sub.toFixed(2).replace('.',',')]));
      const csv = rows.map(r => r.map(x => `"${String(x).replace(/"/g,'""')}"`).join(',')).join('\\n');
      const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = 'orcamento_itens.csv';
      document.body.appendChild(a); a.click(); document.body.removeChild(a);
      URL.revokeObjectURL(url);
    });
  });
})();
JS;
    }
}

new IF_Orcamento_WhatsApp_Single();
