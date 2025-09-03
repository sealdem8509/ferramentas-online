<?php
/**
 * Plugin Name: Lotofácil Generator (Single File)
 * Description: Gera de 1 a 100 jogos da Lotofácil (15 dezenas) e permite excluir números. Shortcode: [lotofacil_generator].
 * Version:     1.0.0
 * Author:      Sealdem Santos 
 * License:     GPLv2 or later
 * Text Domain: lotofacil-generator
 */

if (!defined('ABSPATH')) exit;

class Lotofacil_Generator_Single {
    const VER = '1.0.0';
    const NUMBERS_PER_GAME = 15;
    const MIN_NUMBER = 1;
    const MAX_NUMBER = 25;

    private $handle_style  = 'lotofacil-gen-inline-style';
    private $handle_script = 'lotofacil-gen-inline-script';

    public function __construct() {
        add_shortcode('lotofacil_generator', [$this, 'shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_inline_assets']);
        add_action('wp_ajax_lf_generate', [$this, 'ajax_generate']);
        add_action('wp_ajax_nopriv_lf_generate', [$this, 'ajax_generate']);
    }

    /** ---------- UI (shortcode) ---------- */
    public function shortcode($atts) {
        ob_start(); ?>
        <div class="lf-wrap">
            <h3 class="lf-title">Gerador de Números da Lotofácil</h3>

            <form id="lf-form" class="lf-form" autocomplete="off">
                <div class="lf-row">
                    <label for="lf-qtd">Quantidade de jogos (1–100)</label>
                    <input type="number" id="lf-qtd" name="qtd" min="1" max="100" value="10" required>
                </div>

                <div class="lf-row lf-inline">
                    <label>Excluir dezenas (clique para alternar)</label>
                    <div class="lf-grid" aria-label="Grade de dezenas 1 a 25 para excluir">
                      <?php for ($n=self::MIN_NUMBER; $n<=self::MAX_NUMBER; $n++): ?>
                        <button type="button" class="lf-ball" data-n="<?php echo esc_attr($n); ?>" aria-pressed="false"><?php echo esc_html($n); ?></button>
                      <?php endfor; ?>
                    </div>
                </div>

                <div class="lf-row">
                    <label for="lf-exclude">Ou digite dezenas para excluir (ex.: 1,5,13,24)</label>
                    <input type="text" id="lf-exclude" name="exclude" placeholder="Ex.: 1,5,13,24">
                </div>

                <div class="lf-note">Cada jogo possui <strong><?php echo esc_html(self::NUMBERS_PER_GAME); ?></strong> dezenas de <?php echo esc_html(self::MIN_NUMBER); ?> a <?php echo esc_html(self::MAX_NUMBER); ?>. Uso recreativo.</div>

                <button type="submit" class="lf-btn">Gerar jogos</button>
            </form>

            <div id="lf-actions" class="lf-actions" hidden>
                <button id="lf-download" class="lf-btn-secondary">Baixar CSV</button>
            </div>

            <div id="lf-result" class="lf-result" aria-live="polite"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /** ---------- AJAX ---------- */
    public function ajax_generate() {
        check_ajax_referer('lf_nonce', 'nonce');

        $qtd = isset($_POST['qtd']) ? intval($_POST['qtd']) : 0;
        if ($qtd < 1 || $qtd > 100) {
            wp_send_json_error(['message' => 'Informe a quantidade de jogos entre 1 e 100.'], 400);
        }

        $exclude_raw = isset($_POST['exclude']) ? wp_unslash($_POST['exclude']) : '';
        $exclude_btn = isset($_POST['exclude_btn']) ? (array) $_POST['exclude_btn'] : [];

        $exclude = $this->parse_excluded($exclude_raw, $exclude_btn);
        $exclude = array_values(array_unique(array_filter($exclude, function($x){
            return $x >= self::MIN_NUMBER && $x <= self::MAX_NUMBER;
        })));
        sort($exclude);

        $all = range(self::MIN_NUMBER, self::MAX_NUMBER);
        $allowed = array_values(array_diff($all, $exclude));

        if (count($allowed) < self::NUMBERS_PER_GAME) {
            wp_send_json_error(['message' =>
                'Você excluiu dezenas demais. Deixe ao menos ' . self::NUMBERS_PER_GAME . ' disponíveis.'], 400);
        }

        $games = $this->generate_games($allowed, $qtd, self::NUMBERS_PER_GAME);
        $csv   = $this->to_csv($games);

        wp_send_json_success([
            'games' => $games,   // [[1,2,...,15], [...], ...]
            'csv'   => $csv,
        ]);
    }

    private function parse_excluded($text, $btnArray) {
        $out = [];
        // do texto
        if (is_string($text) && $text !== '') {
            $text = strtolower($text);
            $text = str_replace([';', ' '], [',', ''], $text);
            $parts = array_filter(explode(',', $text), fn($p) => $p !== '');
            foreach ($parts as $p) {
                $n = intval($p);
                $out[] = $n;
            }
        }
        // da grade de botões
        foreach ($btnArray as $b) {
            $out[] = intval($b);
        }
        return $out;
    }

    private function generate_games($allowed, $qtd, $k) {
        $games = [];
        $seen  = [];
        $attempts = 0;
        $maxAttempts = $qtd * 50; // segurança

        while (count($games) < $qtd && $attempts < $maxAttempts) {
            $attempts++;
            $sample = $this->sample_k($allowed, $k);
            sort($sample);
            $key = implode('-', $sample);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $games[] = $sample;
            }
        }
        return $games;
    }

    /** Fisher–Yates com random_int */
    private function secure_shuffle(&$array) {
        $n = count($array);
        for ($i = $n - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            if ($i !== $j) {
                $tmp = $array[$i];
                $array[$i] = $array[$j];
                $array[$j] = $tmp;
            }
        }
    }

    private function sample_k($array, $k) {
        $tmp = $array;
        $this->secure_shuffle($tmp);
        return array_slice($tmp, 0, $k);
    }

    private function to_csv($games) {
        $lines = [];
        $header = ['Jogo'];
        for ($i = 1; $i <= self::NUMBERS_PER_GAME; $i++) $header[] = 'D' . $i;
        $lines[] = implode(',', $header);
        $idx = 1;
        foreach ($games as $g) {
            $row = array_merge(["Jogo {$idx}"], $g);
            $lines[] = implode(',', $row);
            $idx++;
        }
        return implode("\n", $lines);
    }

    /** ---------- Assets inline ---------- */
    public function enqueue_inline_assets() {
        wp_register_style($this->handle_style, false, [], self::VER);
        wp_enqueue_style($this->handle_style);

        wp_register_script($this->handle_script, false, ['jquery'], self::VER, true);
        wp_enqueue_script($this->handle_script);

        wp_add_inline_script($this->handle_script, 'window.LFGEN = ' . wp_json_encode([
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('lf_nonce'),
        ]) . ';', 'before');

        wp_add_inline_style($this->handle_style, $this->css());
        wp_add_inline_script($this->handle_script, $this->js());
    }

    private function css() {
        return <<<CSS
:root{
  --lf-primary:#0F2C59; /* azul escuro iFerramentaria */
  --lf-accent:#1B3B6F;
  --lf-silver:#C0C0C0;
  --lf-bg:#f7f9fc;
  --lf-white:#fff;
}
.lf-wrap{
  background:var(--lf-white); border:1px solid #e6e9ef; border-radius:16px;
  padding:20px; box-shadow:0 6px 16px rgba(15,44,89,0.06); max-width:820px; margin:16px auto;
}
.lf-title{ margin:0 0 12px; color:var(--lf-primary); font-weight:700; }
.lf-form{
  display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;
}
.lf-row{ display:flex; flex-direction:column; }
.lf-inline{ grid-column: span 2; }
.lf-row label{ font-size:13px; color:#475569; margin-bottom:6px; }
.lf-row input{
  border:1px solid #d5d9e3; border-radius:10px; padding:10px 12px; font-size:14px; outline:none; background:var(--lf-bg);
}
.lf-grid{
  display:grid; grid-template-columns: repeat(10,minmax(38px,1fr)); gap:8px; background:#fff; padding:10px; border-radius:12px; border:1px solid #e6e9ef;
}
.lf-ball{
  border:1px solid var(--lf-primary); background:transparent; color:var(--lf-primary);
  border-radius:999px; height:36px; min-width:36px; font-weight:700; cursor:pointer;
}
.lf-ball[aria-pressed="true"], .lf-ball.active{
  background:var(--lf-primary); color:#fff;
}
.lf-btn{
  grid-column: span 2; padding:12px 16px; border:none; border-radius:12px;
  background:var(--lf-primary); color:#fff; font-weight:700; cursor:pointer;
  transition: transform .04s ease, box-shadow .2s ease;
  box-shadow:0 6px 14px rgba(15,44,89,0.18);
}
.lf-btn:hover{ background:var(--lf-accent); }
.lf-btn:active{ transform: translateY(1px); }
.lf-btn-secondary{
  padding:10px 14px; border:1px solid var(--lf-primary); border-radius:10px; background:transparent; color:var(--lf-primary); font-weight:700; cursor:pointer;
}
.lf-note{ font-size:12px; color:#64748b; grid-column: span 2; }
.lf-result{ margin-top:12px; display:grid; gap:10px; }
.lf-card{
  border:1px solid #e6e9ef; border-radius:12px; padding:10px 12px; background:#fff; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;
}
.lf-badges{ display:flex; flex-wrap:wrap; gap:6px; }
.lf-badge{
  background:#eef3ff; color:#1b2a57; border-radius:999px; padding:6px 10px; font-weight:700; min-width:34px; text-align:center;
}
.lf-actions{ display:flex; gap:8px; margin-bottom:8px; }
.lf-alert{
  padding:12px; background:#fff7ed; border:1px solid #fed7aa; border-radius:10px; color:#7c2d12;
}
@media (max-width:720px){
  .lf-form{ grid-template-columns:1fr; }
  .lf-btn{ grid-column: span 1; }
  .lf-grid{ grid-template-columns: repeat(5,minmax(38px,1fr)); }
}
CSS;
    }

    private function js() {
        return <<<JS
jQuery(function($){
  const \$form      = \$('#lf-form');
  const \$result    = \$('#lf-result');
  const \$actions   = \$('#lf-actions');
  const \$download  = \$('#lf-download');
  const \$excludeIn = \$('#lf-exclude');

  // toggle da grade
  \$(document).on('click', '.lf-ball', function(){
    const pressed = \$(this).attr('aria-pressed') === 'true';
    \$(this).attr('aria-pressed', pressed ? 'false' : 'true').toggleClass('active', !pressed);
    syncExcludeInput();
  });

  function syncExcludeInput(){
    const arr = [];
    \$('.lf-ball[aria-pressed="true"]').each(function(){
      arr.push(parseInt(\$(this).data('n'), 10));
    });
    const typed = (\$excludeIn.val() || '').trim();
    // apenas mantém grade no input se o usuário não estiver digitando nada
    if (typed === '') {
      \$excludeIn.val(arr.join(','));
    }
  }

  \$form.on('submit', function(e){
    e.preventDefault();
    \$result.html('<div class="lf-alert">Gerando jogos…</div>');
    \$actions.prop('hidden', true);

    const qtd = parseInt(\$('#lf-qtd').val(), 10);
    if (isNaN(qtd) || qtd < 1 || qtd > 100) {
      \$result.html('<div class="lf-alert">Informe a quantidade de jogos entre 1 e 100.</div>');
      return;
    }

    const excludeFromButtons = \$('.lf-ball[aria-pressed="true"]').map(function(){ return \$(this).data('n'); }).get();
    const excludeTyped = (\$excludeIn.val() || '').split(/[,;\\s]+/).filter(Boolean).map(n => parseInt(n,10)).filter(n => !isNaN(n));
    const exclude = Array.from(new Set([].concat(excludeFromButtons, excludeTyped)));

    \$.post(window.LFGEN.ajax_url, {
      action: 'lf_generate',
      nonce: window.LFGEN.nonce,
      qtd: qtd,
      exclude: excludeTyped.join(','),  // texto
      'exclude_btn[]': excludeFromButtons // grade
    })
    .done(function(resp){
      if(!resp || !resp.success || !resp.data){ 
        \$result.html('<div class="lf-alert">Não foi possível gerar. Tente novamente.</div>');
        return;
      }
      renderGames(resp.data.games);
      prepareCSV(resp.data.csv);
      \$actions.prop('hidden', false);
    })
    .fail(function(){
      \$result.html('<div class="lf-alert">Erro de conexão. Tente novamente.</div>');
    });
  });

  function renderGames(games){
    if(!games || !games.length){
      \$result.html('<div class="lf-alert">Nenhum jogo retornado.</div>');
      return;
    }
    const html = games.map((g,idx) => {
      const pads = g.map(n => ('0' + n).slice(-2)); // dois dígitos
      const badges = pads.map(n => '<span class="lf-badge">'+n+'</span>').join('');
      return '<div class="lf-card">'+
               '<div><strong>Jogo '+(idx+1)+'</strong></div>'+
               '<div class="lf-badges">'+badges+'</div>'+
               '<button class="lf-btn-secondary lf-copy" data-line="'+pads.join(', ')+'">Copiar</button>'+
             '</div>';
    }).join('');
    \$result.html(html);
  }

  \$(document).on('click', '.lf-copy', function(){
    const text = \$(this).data('line');
    copyToClipboard(text);
    \$(this).text('Copiado!');
    setTimeout(()=>\$(this).text('Copiar'), 1500);
  });

  function prepareCSV(csv){
    \$download.off('click').on('click', function(){
      const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
      const url  = URL.createObjectURL(blob);
      const a    = document.createElement('a');
      a.href = url;
      a.download = 'lotofacil_jogos.csv';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    });
  }

  function copyToClipboard(text){
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text);
    } else {
      const el = document.createElement('textarea');
      el.value = text;
      document.body.appendChild(el);
      el.select();
      document.execCommand('copy');
      document.body.removeChild(el);
    }
  }
});
JS;
    }
}

new Lotofacil_Generator_Single();
