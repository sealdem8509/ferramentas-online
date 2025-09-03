<?php
/**
 * Plugin Name: Gerador de Orçamentos Online (Tudo-em-Um)
 * Description: Crie, calcule e envie orçamentos com integração ao WhatsApp, impressão (PDF via navegador) e exportação CSV. Use o shortcode: [gerador_orcamento]
 * Version:     1.0.0
 * Author:      Sealdem Santos & ChatGPT
 * License:     GPLv2 or later
 * Text Domain: if-orc
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('IF_Orcamento_Plugin')) {

class IF_Orcamento_Plugin {
    const OPT_KEY = 'if_orc_options';
    const VER     = '1.0.0';

    public function __construct() {
        // Admin
        add_action('admin_menu',  [$this, 'admin_menu']);
        add_action('admin_init',  [$this, 'register_settings']);

        // Shortcode
        add_shortcode('gerador_orcamento', [$this, 'shortcode']);
    }

    /** ========== ADMIN ========== */

    public function admin_menu() {
        add_options_page(
            __('Gerador de Orçamentos', 'if-orc'),
            __('Gerador de Orçamentos', 'if-orc'),
            'manage_options',
            'if-orcamento',
            [$this, 'settings_page']
        );
    }

    public function get_defaults() {
        return [
            'empresa'           => '',
            'moeda'             => 'BRL',      // BRL | USD | EUR
            'simbolo'           => 'R$',
            'dec_sep'           => ',',        // separador decimal
            'mil_sep'           => '.',        // separador milhar
            'validade'          => 7,
            'num_orc'           => '',
            'ddi'               => '+55',
            'width_desktop'     => 90,         // %
            'width_mobile'      => 98,         // %
            'habilitar_imposto' => 1,
            'habilitar_frete'   => 1,
            'obs_padrao'        => '',
        ];
    }

    public function get_options() {
        $opt = get_option(self::OPT_KEY, []);
        $opt = is_array($opt) ? $opt : [];
        $opt = wp_parse_args($opt, $this->get_defaults());

        // Coerções seguras
        $opt['moeda']         = in_array($opt['moeda'], ['BRL','USD','EUR'], true) ? $opt['moeda'] : 'BRL';
        $opt['simbolo']       = (string) $opt['simbolo'];
        $opt['dec_sep']       = $opt['dec_sep'] === '.' ? '.' : ',';
        $opt['mil_sep']       = $opt['mil_sep'] === ',' ? ',' : '.';
        $opt['validade']      = absint($opt['validade']) ?: 7;
        $opt['num_orc']       = (string) $opt['num_orc'];
        $opt['ddi']           = (string) $opt['ddi'];
        $opt['width_desktop'] = min(100, max(40, intval($opt['width_desktop'])));
        $opt['width_mobile']  = min(100, max(60, intval($opt['width_mobile'])));
        $opt['habilitar_imposto'] = !empty($opt['habilitar_imposto']) ? 1 : 0;
        $opt['habilitar_frete']   = !empty($opt['habilitar_frete'])   ? 1 : 0;
        $opt['obs_padrao']    = (string) $opt['obs_padrao'];

        // Ajusta símbolo por moeda se vazio
        if ($opt['simbolo'] === '') {
            $opt['simbolo'] = $opt['moeda'] === 'USD' ? '$' : ($opt['moeda'] === 'EUR' ? '€' : 'R$');
        }

        return $opt;
    }

    public function register_settings() {
        register_setting(
            'if_orc_options_group',
            self::OPT_KEY,
            ['sanitize_callback' => [$this, 'sanitize_options']]
        );

        add_settings_section('if_orc_main', __('Configurações Principais', 'if-orc'), function () {
            echo '<p>' . esc_html__('Ajuste os padrões usados no formulário e no cálculo do orçamento.', 'if-orc') . '</p>';
        }, 'if-orcamento');

        $fields = [
            ['empresa', __('Empresa/Remetente', 'if-orc'), 'text'],
            ['moeda', __('Moeda padrão', 'if-orc'), 'select'],
            ['simbolo', __('Símbolo monetário', 'if-orc'), 'text'],
            ['dec_sep', __('Separador decimal', 'if-orc'), 'select_dec'],
            ['mil_sep', __('Separador de milhar', 'if-orc'), 'select_mil'],
            ['validade', __('Validade padrão (dias)', 'if-orc'), 'number'],
            ['num_orc', __('Nº padrão do orçamento (opcional)', 'if-orc'), 'text'],
            ['ddi', __('DDI padrão WhatsApp', 'if-orc'), 'text'],
            ['width_desktop', __('Largura do formulário em Desktop (%)', 'if-orc'), 'number'],
            ['width_mobile',  __('Largura do formulário em Mobile (%)', 'if-orc'),  'number'],
            ['habilitar_imposto', __('Habilitar campo de Impostos/Taxas (%)', 'if-orc'), 'checkbox'],
            ['habilitar_frete',   __('Habilitar campo de Acréscimos/Frete (R$)', 'if-orc'), 'checkbox'],
            ['obs_padrao', __('Observações padrão', 'if-orc'), 'textarea'],
        ];

        foreach ($fields as [$key, $label, $type]) {
            add_settings_field(
                'if_orc_' . $key,
                esc_html($label),
                [$this, 'render_field'],
                'if-orcamento',
                'if_orc_main',
                ['key' => $key, 'type' => $type]
            );
        }
    }

    public function sanitize_options($in) {
        $d = $this->get_defaults();
        $out = [];

        $out['empresa'] = isset($in['empresa']) ? sanitize_text_field($in['empresa']) : $d['empresa'];
        $out['moeda']   = isset($in['moeda']) && in_array($in['moeda'], ['BRL','USD','EUR'], true) ? $in['moeda'] : $d['moeda'];
        $out['simbolo'] = isset($in['simbolo']) ? sanitize_text_field($in['simbolo']) : '';
        $out['dec_sep'] = (isset($in['dec_sep']) && in_array($in['dec_sep'], [',','.'], true)) ? $in['dec_sep'] : $d['dec_sep'];
        $out['mil_sep'] = (isset($in['mil_sep']) && in_array($in['mil_sep'], ['.',''], true)) ? ($in['mil_sep'] ?: '.') : $d['mil_sep'];
        $out['validade']= isset($in['validade']) ? max(1, absint($in['validade'])) : $d['validade'];
        $out['num_orc'] = isset($in['num_orc']) ? sanitize_text_field($in['num_orc']) : '';
        $out['ddi']     = isset($in['ddi']) ? sanitize_text_field($in['ddi']) : $d['ddi'];

        $out['width_desktop'] = isset($in['width_desktop']) ? min(100, max(40, intval($in['width_desktop']))) : $d['width_desktop'];
        $out['width_mobile']  = isset($in['width_mobile'])  ? min(100, max(60, intval($in['width_mobile'])))  : $d['width_mobile'];

        $out['habilitar_imposto'] = !empty($in['habilitar_imposto']) ? 1 : 0;
        $out['habilitar_frete']   = !empty($in['habilitar_frete']) ? 1 : 0;

        $out['obs_padrao'] = isset($in['obs_padrao']) ? wp_kses_post($in['obs_padrao']) : '';

        // Símbolo padrão pela moeda se vazio
        if ($out['simbolo'] === '') {
            $out['simbolo'] = $out['moeda'] === 'USD' ? '$' : ($out['moeda'] === 'EUR' ? '€' : 'R$');
        }

        return $out;
    }

    public function render_field($args) {
        $opt = $this->get_options();
        $key = $args['key'];
        $type= $args['type'];
        $name= self::OPT_KEY . '[' . $key . ']';

        if ($type === 'text') {
            printf('<input type="text" class="regular-text" name="%s" value="%s" />', esc_attr($name), esc_attr($opt[$key]));
        } elseif ($type === 'number') {
            $min = ($key === 'width_desktop') ? 40 : (($key === 'width_mobile') ? 60 : 1);
            $max = 100;
            printf('<input type="number" min="%d" max="%d" step="1" name="%s" value="%s" />', $min, $max, esc_attr($name), esc_attr($opt[$key]));
        } elseif ($type === 'textarea') {
            printf('<textarea class="large-text" rows="4" name="%s">%s</textarea>', esc_attr($name), esc_textarea($opt[$key]));
        } elseif ($type === 'checkbox') {
            printf('<label><input type="checkbox" name="%s" value="1" %s /> %s</label>',
                esc_attr($name),
                checked(1, $opt[$key], false),
                esc_html__('Ativar', 'if-orc')
            );
        } elseif ($type === 'select') {
            echo '<select name="'. esc_attr($name) .'">';
            foreach (['BRL'=>'BRL (R$)','USD'=>'USD ($)','EUR'=>'EUR (€)'] as $val=>$lab) {
                printf('<option value="%s" %s>%s</option>', esc_attr($val), selected($opt[$key], $val, false), esc_html($lab));
            }
            echo '</select>';
        } elseif ($type === 'select_dec') {
            echo '<select name="'. esc_attr($name) .'">';
            foreach ([','=>__('Vírgula (,)', 'if-orc'), '.'=>__('Ponto (.)', 'if-orc')] as $val=>$lab) {
                printf('<option value="%s" %s>%s</option>', esc_attr($val), selected($opt[$key], $val, false), esc_html($lab));
            }
            echo '</select>';
        } elseif ($type === 'select_mil') {
            echo '<select name="'. esc_attr($name) .'">';
            // Permitir ponto como milhar; vírgula como milhar é incomum no BR, manter ponto
            foreach (['.'=>__('Ponto (.)', 'if-orc')] as $val=>$lab) {
                printf('<option value="%s" %s>%s</option>', esc_attr($val), selected($opt[$key], $val, false), esc_html($lab));
            }
            echo '</select>';
        }
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Gerador de Orçamentos Online', 'if-orc'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('if_orc_options_group');
                do_settings_sections('if-orcamento');
                submit_button();
                ?>
            </form>
            <hr>
            <h2><?php esc_html_e('Como usar', 'if-orc'); ?></h2>
            <ol>
                <li><?php esc_html_e('Crie/edite uma página no WordPress.', 'if-orc'); ?></li>
                <li><?php esc_html_e('Insira o shortcode', 'if-orc'); ?> <code>[gerador_orcamento]</code>.</li>
                <li><?php esc_html_e('Salve e visualize. Ajuste as opções nesta tela conforme necessário.', 'if-orc'); ?></li>
            </ol>
        </div>
        <?php
    }

    /** ========== FRONT: SHORTCODE ========== */

    public function shortcode($atts) {
        $opt = $this->get_options();
        $uid = 'iforc_' . wp_generate_password(8, false, false); // para isolar JS/CSS
        $empresa  = $opt['empresa'];
        $moeda    = $opt['moeda'];
        $simbolo  = $opt['simbolo'];
        $dec_sep  = $opt['dec_sep'];
        $mil_sep  = $opt['mil_sep'];
        $validade = $opt['validade'];
        $num_orc  = $opt['num_orc'];
        $ddi      = $opt['ddi'];
        $wdesk    = $opt['width_desktop'];
        $wmob     = $opt['width_mobile'];
        $hasImp   = (bool)$opt['habilitar_imposto'];
        $hasFre   = (bool)$opt['habilitar_frete'];
        $obs_pad  = $opt['obs_padrao'];

        ob_start();
        ?>
        <div id="<?php echo esc_attr($uid); ?>" class="if-orc-wrap" style="--if-orc-width-desktop:<?php echo esc_attr($wdesk); ?>%; --if-orc-width-mobile:<?php echo esc_attr($wmob); ?>%;">
            <style>
                #<?php echo $uid; ?>.if-orc-wrap{box-sizing:border-box;margin:20px auto;max-width:var(--if-orc-width-desktop);background:#fff;border:1px solid #e6e6e6;border-radius:16px;padding:20px;box-shadow:0 6px 16px rgba(0,0,0,.06)}
                #<?php echo $uid; ?> .if-orc-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
                #<?php echo $uid; ?> .if-orc-row-3{grid-template-columns:repeat(3,1fr)}
                #<?php echo $uid; ?> .if-orc-row-4{grid-template-columns:repeat(4,1fr)}
                #<?php echo $uid; ?> label{font-weight:600;margin-bottom:6px;display:block}
                #<?php echo $uid; ?> input[type=text],#<?php echo $uid; ?> input[type=number],#<?php echo $uid; ?> textarea,#<?php echo $uid; ?> select{width:100%;padding:10px 12px;border:1px solid #d9d9d9;border-radius:10px;font-size:14px}
                #<?php echo $uid; ?> textarea{min-height:80px;resize:vertical}
                #<?php echo $uid; ?> .if-orc-table{width:100%;border-collapse:collapse;border-spacing:0;margin-top:8px}
                #<?php echo $uid; ?> .if-orc-table th,#<?php echo $uid; ?> .if-orc-table td{border:1px solid #eaeaea;padding:8px}
                #<?php echo $uid; ?> .if-orc-table th{background:#f8f8f8;text-align:left}
                #<?php echo $uid; ?> .if-orc-actions{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0}
                #<?php echo $uid; ?> .if-btn{appearance:none;border:none;border-radius:10px;padding:10px 14px;font-weight:600;cursor:pointer}
                #<?php echo $uid; ?> .if-btn-primary{background:#1b5e20;color:#fff}
                #<?php echo $uid; ?> .if-btn-secondary{background:#efefef}
                #<?php echo $uid; ?> .if-btn-danger{background:#b71c1c;color:#fff}
                #<?php echo $uid; ?> .if-right{margin-left:auto}
                #<?php echo $uid; ?> .if-orc-totais{margin-top:14px;border-top:2px solid #f0f0f0;padding-top:12px}
                #<?php echo $uid; ?> .if-orc-totais .grid{display:grid;grid-template-columns:1fr auto;gap:8px}
                #<?php echo $uid; ?> .if-help{font-size:12px;color:#666}
                #<?php echo $uid; ?> .if-ddi-wrap{display:flex;align-items:center;gap:6px}
                #<?php echo $uid; ?> .if-ddi{background:#f3f3f3;border:1px solid #d9d9d9;border-radius:10px;padding:10px 12px}
                #<?php echo $uid; ?> .if-badge{display:inline-block;background:#eef7ee;color:#1b5e20;border:1px solid #d3ead4;border-radius:999px;padding:2px 8px;font-size:12px}
                #<?php echo $uid; ?> .if-linkbox{display:flex;gap:8px;align-items:center}
                #<?php echo $uid; ?> .if-linkbox input{flex:1}
                @media (max-width: 768px){
                    #<?php echo $uid; ?>.if-orc-wrap{max-width:var(--if-orc-width-mobile)}
                    #<?php echo $uid; ?> .if-orc-row,#<?php echo $uid; ?> .if-orc-row-3,#<?php echo $uid; ?> .if-orc-row-4{grid-template-columns:1fr}
                }
                /* PRINT */
                @media print {
                    #wpadminbar, .site-header, .site-footer { display:none !important; }
                    #<?php echo $uid; ?>.if-orc-wrap{box-shadow:none;border:none;padding:0;max-width:100%}
                    #<?php echo $uid; ?> .if-orc-actions, #<?php echo $uid; ?> .if-linkbox { display:none !important; }
                    #<?php echo $uid; ?> .if-orc-table th, #<?php echo $uid; ?> .if-orc-table td { border:1px solid #000; }
                }
            </style>

            <div class="if-header" style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                <h2 style="margin:0;"><?php esc_html_e('Gerador de Orçamentos', 'if-orc'); ?></h2>
                <span class="if-badge"><?php echo esc_html($moeda); ?></span>
            </div>

            <div class="if-orc-row">
                <div>
                    <label><?php esc_html_e('Empresa/Remetente', 'if-orc'); ?></label>
                    <input type="text" id="<?php echo $uid; ?>_empresa" value="<?php echo esc_attr($empresa); ?>" placeholder="<?php esc_attr_e('Ex.: Minha Empresa LTDA', 'if-orc'); ?>">
                </div>
                <div>
                    <label><?php esc_html_e('Cliente', 'if-orc'); ?> *</label>
                    <input type="text" id="<?php echo $uid; ?>_cliente" placeholder="<?php esc_attr_e('Nome do cliente', 'if-orc'); ?>">
                </div>
            </div>

            <div class="if-orc-row-3" style="margin-top:8px;">
                <div>
                    <label><?php esc_html_e('WhatsApp do cliente', 'if-orc'); ?> *</label>
                    <div class="if-ddi-wrap">
                        <span class="if-ddi" id="<?php echo $uid; ?>_ddi"><?php echo esc_html($ddi); ?></span>
                        <input type="text" id="<?php echo $uid; ?>_whats" inputmode="numeric" pattern="[0-9]*" placeholder="<?php esc_attr_e('Apenas números (DDD + número)', 'if-orc'); ?>">
                    </div>
                    <div class="if-help"><?php esc_html_e('Digite apenas números. O DDI será adicionado automaticamente.', 'if-orc'); ?></div>
                </div>
                <div>
                    <label><?php esc_html_e('Validade (dias)', 'if-orc'); ?></label>
                    <input type="number" min="1" step="1" id="<?php echo $uid; ?>_validade" value="<?php echo esc_attr($validade); ?>">
                </div>
                <div>
                    <label><?php esc_html_e('Nº do orçamento (opcional)', 'if-orc'); ?></label>
                    <input type="text" id="<?php echo $uid; ?>_numorc" value="<?php echo esc_attr($num_orc); ?>" placeholder="Ex.: 2025-001">
                </div>
            </div>

            <div class="if-orc-row-3" style="margin-top:8px;">
                <div>
                    <label><?php esc_html_e('Moeda', 'if-orc'); ?></label>
                    <select id="<?php echo $uid; ?>_moeda">
                        <option value="BRL" <?php selected($moeda,'BRL'); ?>>BRL (R$)</option>
                        <option value="USD" <?php selected($moeda,'USD'); ?>>USD ($)</option>
                        <option value="EUR" <?php selected($moeda,'EUR'); ?>>EUR (€)</option>
                    </select>
                </div>
                <div>
                    <label><?php esc_html_e('Símbolo', 'if-orc'); ?></label>
                    <input type="text" id="<?php echo $uid; ?>_simbolo" value="<?php echo esc_attr($simbolo); ?>" maxlength="3">
                </div>
                <div>
                    <label><?php esc_html_e('Separadores (decimal/milhar)', 'if-orc'); ?></label>
                    <div class="if-orc-row" style="grid-template-columns:1fr 1fr;">
                        <select id="<?php echo $uid; ?>_dec">
                            <option value="," <?php selected($dec_sep,','); ?>>,</option>
                            <option value="." <?php selected($dec_sep,'.'); ?>>.</option>
                        </select>
                        <select id="<?php echo $uid; ?>_mil">
                            <option value="." <?php selected($mil_sep,'.'); ?>>.</option>
                            <option value=""  <?php selected($mil_sep,''); ?>><?php esc_html_e('Sem milhar', 'if-orc'); ?></option>
                        </select>
                    </div>
                </div>
            </div>

            <h3 style="margin-top:18px;"><?php esc_html_e('Itens do Orçamento', 'if-orc'); ?></h3>
            <table class="if-orc-table" id="<?php echo $uid; ?>_table">
                <thead>
                    <tr>
                        <th style="width:50%"><?php esc_html_e('Descrição', 'if-orc'); ?></th>
                        <th style="width:12%"><?php esc_html_e('Qtd', 'if-orc'); ?></th>
                        <th style="width:18%"><?php esc_html_e('V. Unitário', 'if-orc'); ?></th>
                        <th style="width:18%"><?php esc_html_e('Subtotal', 'if-orc'); ?></th>
                        <th style="width:2%"></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

            <div class="if-orc-actions">
                <button type="button" class="if-btn if-btn-secondary" id="<?php echo $uid; ?>_add"><?php esc_html_e('+ Adicionar item', 'if-orc'); ?></button>
                <button type="button" class="if-btn if-btn-danger" id="<?php echo $uid; ?>_clear"><?php esc_html_e('Limpar itens', 'if-orc'); ?></button>
                <span class="if-right if-help"><?php esc_html_e('Dica: use vírgula ou ponto para decimais—o sistema reconhece ambos.', 'if-orc'); ?></span>
            </div>

            <div class="if-orc-row-3">
                <div>
                    <label><?php esc_html_e('Desconto (%)', 'if-orc'); ?></label>
                    <input type="number" id="<?php echo $uid; ?>_desc" min="0" step="0.01" value="0" inputmode="decimal">
                </div>
                <div <?php if(!$hasImp) echo 'style="display:none"'; ?>>
                    <label><?php esc_html_e('Impostos/Taxas (%)', 'if-orc'); ?></label>
                    <input type="number" id="<?php echo $uid; ?>_imp" min="0" step="0.01" value="0" inputmode="decimal">
                </div>
                <div <?php if(!$hasFre) echo 'style="display:none"'; ?>>
                    <label><?php esc_html_e('Acréscimos/Frete (valor)', 'if-orc'); ?></label>
                    <input type="text" id="<?php echo $uid; ?>_frete" inputmode="decimal" placeholder="<?php echo esc_attr($simbolo); ?> 0,00">
                </div>
            </div>

            <?php do_action('if_orc_before_results'); ?>

            <div class="if-orc-totais">
                <div class="grid">
                    <div><?php esc_html_e('Subtotal', 'if-orc'); ?></div>
                    <div><strong id="<?php echo $uid; ?>_st">0</strong></div>

                    <div><?php esc_html_e('Desconto', 'if-orc'); ?></div>
                    <div><strong id="<?php echo $uid; ?>_vd">0</strong></div>

                    <div <?php if(!$hasImp) echo 'style="display:none"'; ?>><?php esc_html_e('Impostos/Taxas', 'if-orc'); ?></div>
                    <div <?php if(!$hasImp) echo 'style="display:none"'; ?>><strong id="<?php echo $uid; ?>_vi">0</strong></div>

                    <div <?php if(!$hasFre) echo 'style="display:none"'; ?>><?php esc_html_e('Acréscimos/Frete', 'if-orc'); ?></div>
                    <div <?php if(!$hasFre) echo 'style="display:none"'; ?>><strong id="<?php echo $uid; ?>_vf">0</strong></div>

                    <div style="font-size:18px;"><?php esc_html_e('TOTAL', 'if-orc'); ?></div>
                    <div style="font-size:18px;"><strong id="<?php echo $uid; ?>_tt">0</strong></div>
                </div>
            </div>

            <?php do_action('if_orc_after_results'); ?>

            <div style="margin-top:14px;">
                <label><?php esc_html_e('Observações', 'if-orc'); ?></label>
                <textarea id="<?php echo $uid; ?>_obs" placeholder="<?php esc_attr_e('Instruções, prazos, condições, etc.', 'if-orc'); ?>"><?php echo esc_textarea($obs_pad); ?></textarea>
            </div>

            <div class="if-orc-actions" style="margin-top:14px;">
                <button type="button" class="if-btn if-btn-primary" id="<?php echo $uid; ?>_send"><?php esc_html_e('Enviar por WhatsApp', 'if-orc'); ?></button>
                <button type="button" class="if-btn if-btn-secondary" id="<?php echo $uid; ?>_genlink"><?php esc_html_e('Gerar link WhatsApp', 'if-orc'); ?></button>
                <button type="button" class="if-btn if-btn-secondary" id="<?php echo $uid; ?>_copymsg"><?php esc_html_e('Copiar mensagem', 'if-orc'); ?></button>
                <button type="button" class="if-btn if-btn-secondary" id="<?php echo $uid; ?>_print"><?php esc_html_e('Imprimir / Salvar PDF', 'if-orc'); ?></button>
                <button type="button" class="if-btn if-btn-secondary" id="<?php echo $uid; ?>_csv"><?php esc_html_e('Baixar Excel (CSV)', 'if-orc'); ?></button>
            </div>

            <div class="if-linkbox" style="display:none" id="<?php echo $uid; ?>_linkwrap">
                <input type="text" readonly id="<?php echo $uid; ?>_link">
                <button type="button" class="if-btn if-btn-secondary" id="<?php echo $uid; ?>_copylink"><?php esc_html_e('Copiar link', 'if-orc'); ?></button>
            </div>

            <div class="if-help" style="margin-top:8px;">
                <?php esc_html_e('Os cálculos acontecem em tempo real. Confira os valores antes de enviar ao cliente.', 'if-orc'); ?>
            </div>

            <script>
            (function(){
                const $ = (sel,ctx)=> (ctx||document).querySelector(sel);
                const $$= (sel,ctx)=> Array.from((ctx||document).querySelectorAll(sel));

                const uid   = <?php echo json_encode($uid); ?>;
                const root  = document.getElementById(uid);
                if(!root) return;

                const cfg = {
                    empresa: <?php echo json_encode($empresa); ?>,
                    dec: <?php echo json_encode($dec_sep); ?>,
                    mil: <?php echo json_encode($mil_sep); ?>,
                    moeda: <?php echo json_encode($moeda); ?>,
                    simb: <?php echo json_encode($simbolo); ?>,
                    ddi: <?php echo json_encode($ddi); ?>,
                    hasImp: <?php echo $hasImp ? 'true':'false'; ?>,
                    hasFre: <?php echo $hasFre ? 'true':'false'; ?>,
                };

                // Elos
                const tb = $('#'+uid+'_table tbody', root);
                const btnAdd  = $('#'+uid+'_add', root);
                const btnClr  = $('#'+uid+'_clear', root);

                const elEmpresa  = $('#'+uid+'_empresa', root);
                const elCliente  = $('#'+uid+'_cliente', root);
                const elWhats    = $('#'+uid+'_whats', root);
                const elValidade = $('#'+uid+'_validade', root);
                const elNumOrc   = $('#'+uid+'_numorc', root);

                const elMoeda    = $('#'+uid+'_moeda', root);
                const elSimb     = $('#'+uid+'_simbolo', root);
                const elDec      = $('#'+uid+'_dec', root);
                const elMil      = $('#'+uid+'_mil', root);

                const elDesc     = $('#'+uid+'_desc', root);
                const elImp      = $('#'+uid+'_imp', root);
                const elFrete    = $('#'+uid+'_frete', root);

                const st = $('#'+uid+'_st', root);
                const vd = $('#'+uid+'_vd', root);
                const vi = $('#'+uid+'_vi', root);
                const vf = $('#'+uid+'_vf', root);
                const tt = $('#'+uid+'_tt', root);

                const elObs      = $('#'+uid+'_obs', root);
                const btnSend    = $('#'+uid+'_send', root);
                const btnGen     = $('#'+uid+'_genlink', root);
                const btnCopy    = $('#'+uid+'_copymsg', root);
                const btnPrint   = $('#'+uid+'_print', root);
                const btnCSV     = $('#'+uid+'_csv', root);
                const linkWrap   = $('#'+uid+'_linkwrap', root);
                const elLink     = $('#'+uid+'_link', root);
                const btnCopyLink= $('#'+uid+'_copylink', root);
                const elDDI      = $('#'+uid+'_ddi', root);

                // Utils
                function toFloat(v){
                    if(typeof v !== 'string') v = String(v||'');
                    v = v.trim();
                    if(!v) return 0;
                    // remove milhar
                    const mil = elMil.value || cfg.mil;
                    if(mil) v = v.split(mil).join('');
                    // troca decimal
                    const dec = elDec.value || cfg.dec;
                    if(dec === ',') v = v.replace(',', '.');
                    return parseFloat(v) || 0;
                }
                function fmt(n){
                    const dec = elDec.value || cfg.dec;
                    const mil = elMil.value || cfg.mil;
                    const s   = elSimb.value || cfg.simb || '';
                    const opts = {minimumFractionDigits: 2, maximumFractionDigits: 2};
                    // formatação manual simples
                    let str = (Math.round((n + Number.EPSILON)*100)/100).toFixed(2);
                    let [int, fr] = str.split('.');
                    // milhar
                    if(mil){
                        int = int.replace(/\B(?=(\d{3})+(?!\d))/g, mil);
                    }
                    // decimal
                    if(dec === ','){ str = int + ',' + fr; } else { str = int + '.' + fr; }
                    return s ? (s+' '+str) : str;
                }
                function onlyDigits(s){ return String(s||'').replace(/\D+/g,''); }

                function addRow(desc='', q=1, v=''){
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><input type="text" class="if-desc" placeholder="<?php echo esc_js(__('Ex.: Serviço/Produto', 'if-orc')); ?>" value="${escapeHtml(desc)}"></td>
                        <td><input type="number" class="if-qtd" min="0" step="0.01" inputmode="decimal" value="${q}"></td>
                        <td><input type="text" class="if-val" inputmode="decimal" placeholder="0,00" value="${escapeHtml(v)}"></td>
                        <td><input type="text" class="if-sub" readonly></td>
                        <td><button type="button" class="if-btn if-btn-danger if-del" title="<?php echo esc_js(__('Excluir', 'if-orc')); ?>">×</button></td>
                    `;
                    tb.appendChild(tr);
                    bindRow(tr);
                    recalc();
                }

                function bindRow(tr){
                    const qtd = $('.if-qtd', tr);
                    const val = $('.if-val', tr);
                    const del = $('.if-del', tr);
                    qtd.addEventListener('input', recalc);
                    val.addEventListener('input', recalc);
                    del.addEventListener('click', function(){
                        tr.remove();
                        recalc();
                    });
                }

                function getRows(){
                    return $$('#'+uid+'_table tbody tr', root).map(tr=>{
                        const d = $('.if-desc', tr).value.trim();
                        const q = toFloat($('.if-qtd', tr).value);
                        const v = toFloat($('.if-val', tr).value);
                        const s = q * v;
                        return {d,q,v,s,tr};
                    });
                }

                function recalc(){
                    const rows = getRows();
                    let subtotal = 0;
                    rows.forEach(r=> subtotal += r.s);

                    // Desconto %
                    const descP = toFloat(elDesc.value);
                    let vdesc = subtotal * (descP/100);
                    if(!isFinite(vdesc)) vdesc = 0;

                    // Impostos %
                    const impP = cfg.hasImp ? toFloat(elImp.value) : 0;
                    let vimps = (subtotal - vdesc) * (impP/100);
                    if(!isFinite(vimps)) vimps = 0;

                    // Frete (valor)
                    const vfrete = cfg.hasFre ? toFloat(elFrete.value) : 0;

                    const total = subtotal - vdesc + vimps + vfrete;

                    // Atualiza subtotais linha
                    rows.forEach(r=>{
                        $('.if-sub', r.tr).value = fmt(r.s);
                    });

                    st.textContent = fmt(subtotal);
                    vd.textContent = fmt(vdesc);
                    if(cfg.hasImp) vi.textContent = fmt(vimps);
                    if(cfg.hasFre) vf.textContent = fmt(vfrete);
                    tt.textContent = fmt(total);
                }

                function escapeHtml(s){
                    return String(s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
                }

                function buildMessage(){
                    const empresa  = elEmpresa.value.trim() || cfg.empresa || '';
                    const cliente  = elCliente.value.trim();
                    const validade = parseInt(elValidade.value, 10) || 0;
                    const numorc   = elNumOrc.value.trim();
                    const simb     = elSimb.value || cfg.simb || '';
                    const moeda    = elMoeda.value || cfg.moeda || 'BRL';
                    const now      = new Date();
                    const data     = now.toLocaleString('pt-BR');

                    const rows = getRows().filter(r=> r.q>0 && r.v>0);
                    let subtotal = 0;
                    rows.forEach(r=> subtotal += r.s);
                    const descP = toFloat(elDesc.value);
                    let vdesc = subtotal * (descP/100);
                    const impP = cfg.hasImp ? toFloat(elImp.value) : 0;
                    let vimps = (subtotal - vdesc) * (impP/100);
                    const vfrete = cfg.hasFre ? toFloat(elFrete.value) : 0;
                    const total = subtotal - vdesc + vimps + vfrete;

                    let linhas = [];
                    linhas.push('Orçamento' + (numorc ? ' Nº '+numorc:'') + (empresa ? ' – '+empresa : ''));
                    linhas.push('Cliente: ' + cliente + ' – WhatsApp: +' + onlyDigits(cfg.ddi) + ' ' + onlyDigits(elWhats.value));
                    if(validade>0) linhas.push('Validade: ' + validade + ' dias');
                    linhas.push('Moeda: ' + moeda);
                    linhas.push('');
                    linhas.push('Itens:');
                    rows.forEach((r,i)=>{
                        linhas.push((i+1)+') ' + r.d + ' | Qtd: ' + r.q + ' | V. Unit: ' + (simb+' '+numFmt(r.v)) + ' | Subtotal: ' + (simb+' '+numFmt(r.s)));
                    });
                    linhas.push('');
                    linhas.push('Subtotal: ' + (simb+' '+numFmt(subtotal)));
                    linhas.push('Desconto: ' + (simb+' '+numFmt(vdesc)));
                    if(cfg.hasImp) linhas.push('Impostos/Taxas: ' + (simb+' '+numFmt(vimps)));
                    if(cfg.hasFre) linhas.push('Acréscimos/Frete: ' + (simb+' '+numFmt(vfrete)));
                    linhas.push('TOTAL: ' + (simb+' '+numFmt(total)));
                    linhas.push('');
                    const obs = elObs.value.trim();
                    if(obs) {
                        linhas.push('Observações:');
                        linhas.push(obs);
                        linhas.push('');
                    }
                    linhas.push('Gerado em ' + data + '.');

                    // Permitir filtro PHP (será efetivo quando o HTML for passado pelo servidor – aqui é client-side)
                    return linhas.join('\n');
                }

                function numFmt(n){
                    // retorna string com separadores configurados, sem símbolo
                    const dec = elDec.value || cfg.dec;
                    const mil = elMil.value || cfg.mil;
                    let str = (Math.round((n + Number.EPSILON)*100)/100).toFixed(2);
                    let [int, fr] = str.split('.');
                    if(mil){ int = int.replace(/\B(?=(\d{3})+(?!\d))/g, mil); }
                    return dec === ',' ? (int+','+fr) : (int+'.'+fr);
                }

                function buildWaLink(){
                    const ddi   = onlyDigits(cfg.ddi);
                    const fone  = onlyDigits(elWhats.value);
                    const numOk = fone.length >= 8 && fone.length <= 15;
                    if(!numOk) return null;
                    const msg = buildMessage();
                    const text= encodeURIComponent(msg);
                    return 'https://wa.me/'+ ddi + fone +'?text='+ text;
                }

                function ensureValid(){
                    // validações básicas
                    const clienteOk = elCliente.value.trim().length >= 2;
                    const whatsOk   = (onlyDigits(elWhats.value).length >= 8);
                    const rows = getRows().filter(r=> r.q>0 && r.v>0);
                    let subtotal = 0;
                    rows.forEach(r=> subtotal += r.s);
                    const total = subtotal - (subtotal * (toFloat(elDesc.value)/100))
                                  + ((subtotal - (subtotal * (toFloat(elDesc.value)/100))) * (cfg.hasImp?toFloat(elImp.value):0)/100)
                                  + (cfg.hasFre?toFloat(elFrete.value):0);

                    return clienteOk && whatsOk && total > 0 && rows.length>0;
                }

                // Bind gerais
                btnAdd.addEventListener('click', ()=> addRow('', 1, ''));
                btnClr.addEventListener('click', ()=>{
                    if(confirm('Tem certeza que deseja limpar todos os itens?')){
                        tb.innerHTML = '';
                        addRow();
                        recalc();
                    }
                });
                [elDesc, elImp, elFrete, elMoeda, elSimb, elDec, elMil].forEach(el=>{
                    if(el) el.addEventListener('input', recalc);
                });

                // Ações finais
                btnSend.addEventListener('click', ()=>{
                    if(!ensureValid()){
                        alert('Preencha cliente, WhatsApp (apenas números) e adicione pelo menos um item com total maior que zero.');
                        return;
                    }
                    const link = buildWaLink();
                    if(!link){ alert('WhatsApp inválido.'); return; }
                    window.open(link, '_blank');
                });

                btnGen.addEventListener('click', ()=>{
                    if(!ensureValid()){
                        alert('Preencha cliente, WhatsApp e itens corretamente para gerar o link.');
                        return;
                    }
                    const link = buildWaLink();
                    if(!link){ alert('WhatsApp inválido.'); return; }
                    linkWrap.style.display = '';
                    elLink.value = link;
                    elLink.focus(); elLink.select();
                });

                btnCopy.addEventListener('click', async ()=>{
                    const msg = buildMessage();
                    try {
                        await navigator.clipboard.writeText(msg);
                        alert('Mensagem copiada!');
                    } catch(e){
                        // fallback
                        const ta = document.createElement('textarea');
                        ta.value = msg; document.body.appendChild(ta);
                        ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
                        alert('Mensagem copiada!');
                    }
                });

                btnCopyLink.addEventListener('click', async ()=>{
                    try {
                        await navigator.clipboard.writeText(elLink.value);
                        alert('Link copiado!');
                    } catch(e){
                        elLink.focus(); elLink.select();
                        document.execCommand('copy');
                        alert('Link copiado!');
                    }
                });

                btnPrint.addEventListener('click', ()=>{
                    window.print();
                });

                btnCSV.addEventListener('click', ()=>{
                    const empresa  = elEmpresa.value.trim() || cfg.empresa || '';
                    const cliente  = elCliente.value.trim();
                    const validade = parseInt(elValidade.value, 10) || 0;
                    const numorc   = elNumOrc.value.trim();
                    const simb     = elSimb.value || cfg.simb || '';
                    const moeda    = elMoeda.value || cfg.moeda || 'BRL';

                    const rows = getRows().filter(r=> r.q>0 && r.v>0);
                    let subtotal = 0;
                    rows.forEach(r=> subtotal += r.s);
                    const descP = toFloat(elDesc.value);
                    let vdesc = subtotal * (descP/100);
                    const impP = cfg.hasImp ? toFloat(elImp.value) : 0;
                    let vimps = (subtotal - vdesc) * (impP/100);
                    const vfrete = cfg.hasFre ? toFloat(elFrete.value) : 0;
                    const total = subtotal - vdesc + vimps + vfrete;

                    let csv = [];
                    csv.push(['Orcamento', numorc, 'Empresa', empresa, 'Cliente', cliente, 'Moeda', moeda]);
                    csv.push(['Validade (dias)', validade]);
                    csv.push([]);
                    csv.push(['Descrição','Qtd','V.Unitário','Subtotal']);
                    rows.forEach(r=>{
                        csv.push([r.d, String(r.q).replace('.',','), simb+' '+numFmt(r.v), simb+' '+numFmt(r.s)]);
                    });
                    csv.push([]);
                    csv.push(['Subtotal', '', '', simb+' '+numFmt(subtotal)]);
                    csv.push(['Desconto', '', '', simb+' '+numFmt(vdesc)]);
                    if(cfg.hasImp) csv.push(['Impostos/Taxas', '', '', simb+' '+numFmt(vimps)]);
                    if(cfg.hasFre) csv.push(['Acréscimos/Frete', '', '', simb+' '+numFmt(vfrete)]);
                    csv.push(['TOTAL', '', '', simb+' '+numFmt(total)]);
                    csv.push([]);
                    const obs = elObs.value.trim();
                    if(obs){ csv.push(['Observações']); csv.push([obs]); }

                    // Monta CSV com ; (BR) e BOM para Excel
                    const sep = ';';
                    let out = '\uFEFF' + csv.map(row=> row.map(cell=>{
                        const s = String(cell==null?'':cell).replace(/"/g,'""');
                        return /[;"\n]/.test(s) ? `"${s}"` : s;
                    }).join(sep)).join('\n');

                    const blob = new Blob([out], {type:'text/csv;charset=utf-8;'});
                    const a = document.createElement('a');
                    const ts = new Date();
                    const pad = n=> String(n).padStart(2,'0');
                    const fname = `orcamento-${ts.getFullYear()}${pad(ts.getMonth()+1)}${pad(ts.getDate())}-${pad(ts.getHours())}${pad(ts.getMinutes())}.csv`;
                    a.href = URL.createObjectURL(blob);
                    a.download = fname;
                    document.body.appendChild(a);
                    a.click();
                    setTimeout(()=>{ URL.revokeObjectURL(a.href); a.remove(); }, 500);
                });

                // Preenche DDI quando focar no WhatsApp vazio
                elWhats.addEventListener('focus', ()=>{
                    if(!elWhats.value.trim()){
                        // nada a fazer aqui (campo aceita apenas número);
                        // DDI aparece visualmente no span e será adicionado ao link
                    }
                });

                // Troca de formatação
                [elDec, elMil, elSimb, elMoeda].forEach(el=>{
                    el.addEventListener('change', recalc);
                });

                // Inicialização
                addRow(); // começa com 1 linha
                recalc();
            })();
            </script>
        </div>
        <?php
        return ob_get_clean();
    }
}

new IF_Orcamento_Plugin();
}
