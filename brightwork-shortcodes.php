<?php
/**
 * BRIGHTWORK — Shortcodes & Component Library v4.0
 * File: brightwork-shortcodes.php
 *
 * INSTALL: Add ONE line to your child theme functions.php:
 *   require_once get_stylesheet_directory() . '/brightwork-shortcodes.php';
 *
 * HOW EFFECTS WORK (stars, waves, grid):
 *   1. Add a Shortcode widget to any Elementor section
 *   2. Type the shortcode (e.g. [bw_fx stars="yes" wave="yes"])
 *   3. The JavaScript at page load automatically moves the effect
 *      elements to be direct children of the parent section,
 *      bypassing Porto/Elementor's inner wrapper structure entirely.
 *   4. No "bw-section-relative" class needed. No "bw-no-pad" needed.
 *      No padding adjustments needed. Just place the shortcode.
 *
 * GRID WITHOUT A WIDGET:
 *   Add CSS class bw-grid-std (or bw-grid-tight, bw-grid-wide,
 *   bw-grid-diag, bw-grid-accent, bw-grid-sky) directly to the
 *   Elementor Section — no shortcode widget required.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ============================================================
   ENQUEUE ASSETS
   Fonts loaded here (NOT via @import in CSS which Porto ignores)
   ============================================================ */
add_action( 'wp_enqueue_scripts', 'brightwork_enqueue_assets' );
function brightwork_enqueue_assets() {
    // Google Fonts
    wp_enqueue_style(
        'brightwork-fonts',
        'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Barlow:wght@300;400;500;600&display=swap',
        array(), null
    );
    // Component CSS
    wp_enqueue_style(
        'brightwork-elementor',
        get_stylesheet_directory_uri() . '/brightwork-elementor.css',
        array( 'brightwork-fonts' ),
        '4.0.0'
    );
}

/* ============================================================
   FOOTER JAVASCRIPT
   All Brightwork JS in one block:
   1. Progress bar
   2. Scroll animations
   3. Smooth scroll
   4. Effect positioning (stars/wave/grid → moves to section)
   5. Card & heading style fallbacks (Porto CSS compatibility)
   ============================================================ */
add_action( 'wp_footer', 'brightwork_footer_js', 99 );
function brightwork_footer_js() { ?>
<script>
(function () {
  'use strict';

  /* ── 1. Progress bar ─────────────────────────────────────── */
  var bar = document.getElementById('bw-progress-bar');
  if (bar) {
    window.addEventListener('scroll', function () {
      var pct = window.scrollY / Math.max(document.body.scrollHeight - window.innerHeight, 1) * 100;
      bar.style.width = Math.min(pct, 100) + '%';
    }, { passive: true });
  }

  /* ── 2. Scroll reveal animations ─────────────────────────── */
  if ('IntersectionObserver' in window) {
    var revealObs = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add('bw-visible'); revealObs.unobserve(e.target); }
      });
    }, { threshold: 0.1 });
    document.querySelectorAll('[data-bw-animate]').forEach(function (el) { revealObs.observe(el); });
  } else {
    document.querySelectorAll('[data-bw-animate]').forEach(function (el) { el.classList.add('bw-visible'); });
  }

  /* ── 3. Smooth scroll ────────────────────────────────────── */
  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var t = document.querySelector(a.getAttribute('href'));
      if (t) { e.preventDefault(); t.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    });
  });

  /* ── 4. Effect positioning ───────────────────────────────── *
   *
   * Problem: Shortcode widgets are nested deep inside Elementor/
   * Porto's wrapper divs. position:absolute inside a shortcode
   * widget doesn't reach the section because Porto's wrappers
   * intercept the containing-block chain.
   *
   * Solution: After the page loads we physically MOVE each effect
   * element to be a direct child of its parent section, then apply
   * inline positioning. This works on every Porto + Elementor layout
   * regardless of which inner wrapper classes Porto uses.
   */
  function bwPositionEffects() {
    // Selectors that identify Elementor / Porto section elements
    var SECTION_SEL = [
      '.elementor-top-section',
      '.elementor-section',
      '.e-con',
      '.porto-row',
      '.elementor-element[data-element_type="section"]'
    ].join(', ');

    // ── Background fill effects (stars, grid wrappers, bw-fx-wrap)
    document.querySelectorAll('[data-bw-fill]').forEach(function (el) {
      var section = el.closest(SECTION_SEL);
      if (!section) return;

      // Make section the positioning context
      section.style.position = 'relative';

      // Move element to section (bypasses all Porto/Elementor wrappers)
      section.insertBefore(el, section.firstChild);

      // Fill the section
      el.style.position  = 'absolute';
      el.style.top       = '0';
      el.style.left      = '0';
      el.style.right     = '0';
      el.style.bottom    = '0';
      el.style.zIndex    = '0';
      el.style.pointerEvents = 'none';
      el.style.overflow  = 'hidden';

      // Lift Elementor's content container above the overlay
      // Try multiple selectors — Porto uses different structures
      var lifted = section.querySelector(
        ':scope > .elementor-container, :scope > .e-container,' +
        ':scope > .e-con-inner, :scope > .porto-container'
      );
      if (lifted) {
        lifted.style.position = 'relative';
        lifted.style.zIndex   = '1';
      }
    });

    // ── Animated wave (anchors to section bottom, sits above content)
    document.querySelectorAll('[data-bw-wave]').forEach(function (el) {
      var section = el.closest(SECTION_SEL);
      if (!section) return;
      section.style.position = 'relative';
      section.appendChild(el);           // append at end (above content in paint order)
      el.style.position      = 'absolute';
      el.style.bottom        = '0';
      el.style.left          = '0';
      el.style.right         = '0';
      el.style.top           = 'auto';
      el.style.zIndex        = '2';      // above content, wave is a transition element
      el.style.pointerEvents = 'none';
      el.style.overflow      = 'hidden';
      el.style.height        = '160px';
    });
  }

  /* ── 5. Card & heading fallbacks ─────────────────────────── *
   *
   * Porto overrides some Elementor CSS with its own theme styles.
   * Inline styles applied via JS have the highest possible specificity
   * and always win, so Porto can never override them.
   */
  function bwApplyFallbacks() {

    /* bw-heading-display — Cormorant Garamond display font */
    document.querySelectorAll('.bw-heading-display').forEach(function (el) {
      var targets = el.querySelectorAll('h1,h2,h3,h4,h5,h6');
      if (!targets.length) targets = [el];
      targets.forEach(function (t) {
        t.style.fontFamily    = "'Cormorant Garamond', Georgia, serif";
        t.style.fontWeight    = '300';
        t.style.lineHeight    = '1.15';
        t.style.letterSpacing = '-0.01em';
      });
    });

    /* bw-heading-serif */
    document.querySelectorAll('.bw-heading-serif').forEach(function (el) {
      var targets = el.querySelectorAll('h1,h2,h3,h4,h5,h6');
      if (!targets.length) targets = [el];
      targets.forEach(function (t) {
        t.style.fontFamily = "'Cormorant Garamond', Georgia, serif";
        t.style.fontWeight = '400';
        t.style.lineHeight = '1.2';
      });
    });

    /* bw-heading-on-dark */
    document.querySelectorAll('.bw-heading-on-dark').forEach(function (el) {
      var targets = el.querySelectorAll('h1,h2,h3,h4,h5,h6');
      if (!targets.length) targets = [el];
      targets.forEach(function (t) {
        t.style.fontFamily = "'Cormorant Garamond', Georgia, serif";
        t.style.color      = '#ffffff';
      });
    });

    /* bw-card-dark */
    document.querySelectorAll('.bw-card-dark').forEach(function (el) {
      el.style.background   = 'rgba(255,255,255,0.05)';
      el.style.border       = '1px solid rgba(255,255,255,0.1)';
      el.style.borderRadius = '12px';
      el.style.overflow     = 'hidden';
    });

    /* bw-card-accent-top — gradient top border via injected div */
    document.querySelectorAll('.bw-card-accent-top').forEach(function (el) {
      if (el.querySelector('.bwi-accent-bar')) return; // already done
      el.style.position     = 'relative';
      el.style.borderRadius = '10px';
      el.style.overflow     = 'hidden';
      var bar = document.createElement('div');
      bar.className = 'bwi-accent-bar';
      bar.style.cssText = 'position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#3D9B9A,#C8A96E);z-index:10;pointer-events:none;';
      el.insertBefore(bar, el.firstChild);
    });

    /* bw-card-left-border */
    document.querySelectorAll('.bw-card-left-border').forEach(function (el) {
      el.style.borderLeft   = '3px solid #3D9B9A';
      el.style.borderRadius = '0 6px 6px 0';
      el.style.background   = '#E8ECF0';
      el.style.padding      = '1.5rem';
    });
  }

  /* Run on DOMContentLoaded AND window.load (Elementor renders late) */
  function bwInit() {
    bwPositionEffects();
    bwApplyFallbacks();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bwInit);
  } else {
    bwInit();
  }
  window.addEventListener('load', bwInit);          // catch late Elementor render
  setTimeout(bwInit, 800);                           // safety net for Porto lazy-init

}());
</script>
<?php }


/* ============================================================
   [bw_progress_bar]
   Paste ONE shortcode widget at the very top of any page.
   ============================================================ */
add_shortcode( 'bw_progress_bar', function () {
    return '<div id="bw-progress-bar" aria-hidden="true"></div>';
} );


/* ============================================================
   [bw_wave from="fog" to="white"]

   FIXED v4: Single-path SVG approach eliminates the antialiasing
   line that appeared between two fills at the section boundary.
   SVG background = bottom colour. Single path = top colour shape.
   margin:-1px on wrapper closes any sub-pixel gap.

   from / to values:
     fog | white | navy | dark | deep | ink | slate
     gold | gold-warm | section-gold (alias for white)
   ============================================================ */
add_shortcode( 'bw_wave', function ( $atts ) {
    $a = shortcode_atts( array( 'from' => 'fog', 'to' => 'white' ), $atts );

    $map = array(
        'fog'          => '#F2F4F6',
        'white'        => '#FFFFFF',
        'navy'         => '#1A3550',
        'dark'         => '#0F2035',
        'deep'         => '#0F1F3D',
        'ink'          => '#212529',
        'slate'        => '#4A5568',
        'gold'         => '#D4A843',
        'gold-warm'    => '#B8922A',
        'section-gold' => '#FFFFFF',   // bw-section-gold uses white base
    );

    $top    = isset( $map[ $a['from'] ] ) ? $map[ $a['from'] ] : '#F2F4F6';
    $bottom = isset( $map[ $a['to'] ]   ) ? $map[ $a['to'] ]   : '#FFFFFF';

    $dark = array( 'navy', 'dark', 'deep', 'ink', 'slate', 'gold-warm' );
    $from_is_dark = in_array( $a['from'], $dark, true );

    // Path fills the TOP section's colour region.
    // The SVG itself is filled with the BOTTOM section's colour.
    // One fill = zero gap.
    $path = $from_is_dark
        ? 'M0,0 L0,34 C360,4 1080,54 1440,22 L1440,0 Z'   // dark top, light bottom
        : 'M0,0 L0,20 C360,54 1080,6 1440,30 L1440,0 Z';  // light top, dark bottom

    return sprintf(
        '<div style="line-height:0;overflow:hidden;display:block;font-size:0;margin-top:-1px;margin-bottom:-1px;">'
        . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 60" preserveAspectRatio="none"'
        . ' style="display:block;width:100%%;height:60px;background:%s;">'
        . '<path d="%s" fill="%s"/>'
        . '</svg></div>',
        esc_attr( $bottom ),
        esc_attr( $path ),
        esc_attr( $top )
    );
} );


/* ============================================================
   [bw_fx] — COMBINED EFFECTS SHORTCODE
   Outputs stars + animated wave (+ optional grid) in ONE widget.

   Usage:
     [bw_fx stars="yes" wave="yes"]
     [bw_fx grid="diag" stars="yes" wave="yes" wave_from="navy" wave_to="dark"]
     [bw_fx stars="yes" stars_colour="gold" wave="no"]

   The JS at page load moves the output directly onto the parent
   Elementor section — no CSS class setup required on the section.

   Attributes:
     id              — unique id, auto-generated if omitted
     grid            — no | yes | tight | mid | wide | diag | accent | sky
     stars           — yes | no
     stars_count     — integer (default 80)
     stars_mobile    — integer (default 45)
     stars_colour    — cream | gold | sky | silver | warm | teal
     stars_speed     — slow | medium | fast
     stars_top       — integer % from top (default 72, keeps stars above wave)
     wave            — yes | no
     wave_from       — colour key (default navy)
     wave_to         — colour key (default dark)
     wave_speed      — slow | medium | fast
   ============================================================ */
add_shortcode( 'bw_fx', function ( $atts ) {
    static $bw_fx_n = 0;
    $bw_fx_n++;

    $a = shortcode_atts( array(
        'id'           => '',
        'grid'         => 'no',
        'stars'        => 'yes',
        'stars_count'  => '80',
        'stars_mobile' => '45',
        'stars_colour' => 'cream',
        'stars_speed'  => 'medium',
        'stars_top'    => '72',
        'wave'         => 'yes',
        'wave_from'    => 'navy',
        'wave_to'      => 'dark',
        'wave_speed'   => 'medium',
    ), $atts );

    $uid = $a['id'] ? preg_replace( '/[^a-z0-9\-_]/i', '', $a['id'] ) : 'fx' . $bw_fx_n;

    /* ── Grid classes ── */
    $grid_class = '';
    if ( $a['grid'] !== 'no' && $a['grid'] !== '' ) {
        $style      = ( $a['grid'] === 'yes' ) ? 'mid' : sanitize_html_class( $a['grid'] );
        $grid_class = 'bw-grid bw-grid--' . $style;
    }

    /* ── Wave colours & speeds ── */
    $colour_map = array(
        'fog' => '#F2F4F6', 'white' => '#FFFFFF', 'navy' => '#1A3550',
        'dark' => '#0F2035', 'deep' => '#0F1F3D', 'ink' => '#212529',
        'slate' => '#4A5568', 'gold' => '#D4A843', 'gold-warm' => '#B8922A',
    );
    $speed_map = array(
        'slow'   => array( '18s', '26s', '14s' ),
        'medium' => array( '12s', '18s', '9s'  ),
        'fast'   => array( '8s',  '12s', '6s'  ),
    );
    $sp    = isset( $speed_map[ $a['wave_speed'] ] ) ? $speed_map[ $a['wave_speed'] ] : $speed_map['medium'];
    $from  = isset( $colour_map[ $a['wave_from'] ] ) ? $colour_map[ $a['wave_from'] ] : '#1A3550';
    $to    = isset( $colour_map[ $a['wave_to'] ]   ) ? $colour_map[ $a['wave_to'] ]   : '#0F2035';
    $from_rgb = brightwork_hex2rgb( $from );
    $to_rgb   = brightwork_hex2rgb( $to );

    /* ── Star colours & speeds ── */
    $star_colours = array(
        'cream' => 'rgba(240,234,216,0.9)', 'gold'   => 'rgba(212,168,67,0.9)',
        'sky'   => 'rgba(104,154,226,0.9)', 'silver' => 'rgba(226,232,240,0.9)',
        'warm'  => 'rgba(253,230,138,0.9)', 'teal'   => 'rgba(61,155,154,0.9)',
    );
    $star_speed_map = array(
        'slow'   => array( 3.0, 9.0, 6.0 ),
        'medium' => array( 2.0, 6.0, 4.0 ),
        'fast'   => array( 1.0, 4.0, 3.0 ),
    );
    $sc  = isset( $star_colours[ $a['stars_colour'] ] ) ? $star_colours[ $a['stars_colour'] ] : 'rgba(240,234,216,0.9)';
    $ssp = isset( $star_speed_map[ $a['stars_speed'] ] ) ? $star_speed_map[ $a['stars_speed'] ] : $star_speed_map['medium'];

    ob_start();
    ?>
    <?php /* data-bw-fill tells JS: move to section, fill it */ ?>
    <div class="bw-fx-wrap" data-bw-fill id="bwfx-<?php echo esc_attr( $uid ); ?>">

      <?php if ( $grid_class ) : ?>
        <div class="<?php echo esc_attr( $grid_class ); ?>"></div>
      <?php endif; ?>

      <?php if ( $a['stars'] === 'yes' ) : ?>
        <div class="bw-stars" id="bwstars-<?php echo esc_attr( $uid ); ?>"></div>
      <?php endif; ?>

    </div><?php /* bw-fx-wrap ends — wave is SEPARATE so JS can position it differently */ ?>

    <?php if ( $a['wave'] === 'yes' ) : ?>
    <?php /* data-bw-wave tells JS: move to section, anchor to bottom */ ?>
    <div class="bw-hero-waves" data-bw-wave id="bwwave-<?php echo esc_attr( $uid ); ?>">
      <svg class="bw-wave" viewBox="0 0 1440 160" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" style="animation-duration:<?php echo esc_attr( $sp[0] ); ?>">
        <path d="M0,75 C120,110 240,30 360,70 C480,110 600,30 720,70 C840,110 960,30 1080,70 C1200,110 1320,30 1440,70 L1440,160 L0,160 Z" fill="rgba(<?php echo esc_attr( $from_rgb ); ?>,0.5)"/>
      </svg>
      <svg class="bw-wave" viewBox="0 0 1440 160" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" style="animation-duration:<?php echo esc_attr( $sp[1] ); ?>">
        <path d="M0,95 C180,55 360,135 540,95 C720,55 900,135 1080,95 C1260,55 1380,115 1440,95 L1440,160 L0,160 Z" fill="rgba(<?php echo esc_attr( $to_rgb ); ?>,0.6)"/>
      </svg>
      <svg class="bw-wave" viewBox="0 0 1440 160" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" style="animation-duration:<?php echo esc_attr( $sp[2] ); ?>">
        <path d="M0,115 C240,75 480,145 720,115 C960,75 1200,145 1440,115 L1440,160 L0,160 Z" fill="rgba(196,151,58,0.04)"/>
      </svg>
    </div>
    <?php endif; ?>

    <?php if ( $a['stars'] === 'yes' ) : ?>
    <script>
    (function(){
      var wrap = document.getElementById('bwstars-<?php echo esc_js( $uid ); ?>');
      if (!wrap) return;
      var mobile  = window.innerWidth < 768;
      var total   = mobile ? <?php echo intval( $a['stars_mobile'] ); ?> : <?php echo intval( $a['stars_count'] ); ?>;
      var dMin    = <?php echo (float) $ssp[0]; ?>, dMax = <?php echo (float) $ssp[1]; ?>;
      var delMax  = <?php echo (float) $ssp[2]; ?>;
      var topPct  = <?php echo intval( $a['stars_top'] ); ?>;
      var colour  = '<?php echo esc_js( $sc ); ?>';
      var frag = document.createDocumentFragment();
      for (var i = 0; i < total; i++) {
        var s = document.createElement('div');
        var big = Math.random() < 0.1;
        var dur = dMin + Math.random() * (dMax - dMin);
        var del = Math.random() * delMax;
        var op  = 0.08 + Math.random() * 0.42;
        s.className = 'bw-star';
        s.style.cssText =
          'left:'         + (Math.random() * 100) + '%;' +
          'top:'          + (Math.random() * topPct) + '%;' +
          '--d:'          + dur.toFixed(2) + 's;' +
          '--delay:-'     + del.toFixed(2) + 's;' +
          'opacity:'      + op.toFixed(2) + ';' +
          'width:'        + (big ? '2px' : '1px') + ';' +
          'height:'       + (big ? '2px' : '1px') + ';' +
          'background:'   + colour + ';';
        frag.appendChild(s);
      }
      wrap.appendChild(frag);
    }());
    </script>
    <?php endif; ?>
    <?php
    return ob_get_clean();
} );

/* Helper: hex string → "r,g,b" */
function brightwork_hex2rgb( $hex ) {
    $hex = ltrim( $hex, '#' );
    if ( strlen( $hex ) === 3 ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    list( $r, $g, $b ) = array_map( 'hexdec', str_split( $hex, 2 ) );
    return "$r,$g,$b";
}


/* ============================================================
   [bw_stars id="1" count="80" colour="cream"]
   Standalone star-field (no wave). Uses same JS repositioning.
   ============================================================ */
add_shortcode( 'bw_stars', function ( $atts ) {
    static $n = 0; $n++;
    $a = shortcode_atts( array(
        'id'     => '',
        'count'  => '80', 'mobile' => '45',
        'colour' => 'cream', 'speed' => 'medium',
        'top'    => '90',
    ), $atts );

    $uid = $a['id'] ? preg_replace( '/[^a-z0-9\-_]/i', '', $a['id'] ) : 'st' . $n;
    $cmap = array(
        'cream'=>'rgba(240,234,216,0.9)','gold'=>'rgba(212,168,67,0.9)',
        'sky'=>'rgba(104,154,226,0.9)','silver'=>'rgba(226,232,240,0.9)',
        'warm'=>'rgba(253,230,138,0.9)','teal'=>'rgba(61,155,154,0.9)',
    );
    $smap = array('slow'=>array(3,9,6),'medium'=>array(2,6,4),'fast'=>array(1,4,3));
    $c    = $cmap[ $a['colour'] ] ?? 'rgba(240,234,216,0.9)';
    $sp   = $smap[ $a['speed']  ] ?? $smap['medium'];

    ob_start(); ?>
    <div class="bw-stars" data-bw-fill id="bwst-<?php echo esc_attr($uid); ?>"></div>
    <script>
    (function(){
      var w = document.getElementById('bwst-<?php echo esc_js($uid); ?>');
      if (!w) return;
      var mob = window.innerWidth < 768;
      var total = mob ? <?php echo intval($a['mobile']); ?> : <?php echo intval($a['count']); ?>;
      var frag = document.createDocumentFragment();
      for (var i = 0; i < total; i++) {
        var s = document.createElement('div');
        var big = Math.random() < 0.1;
        var dur = <?php echo (float)$sp[0]; ?> + Math.random() * <?php echo (float)($sp[1]-$sp[0]); ?>;
        var del = Math.random() * <?php echo (float)$sp[2]; ?>;
        s.className = 'bw-star';
        s.style.cssText = 'left:'+(Math.random()*100)+'%;top:'+(Math.random()*<?php echo intval($a['top']); ?>)+'%;--d:'+dur.toFixed(2)+'s;--delay:-'+del.toFixed(2)+'s;opacity:'+(0.08+Math.random()*0.42).toFixed(2)+';width:'+(big?'2px':'1px')+';height:'+(big?'2px':'1px')+';background:<?php echo esc_js($c); ?>;';
        frag.appendChild(s);
      }
      w.appendChild(frag);
    }());
    </script>
    <?php return ob_get_clean();
} );


/* ============================================================
   [bw_animated_wave id="1" from="navy" to="dark" speed="medium"]
   Standalone animated wave. Uses JS repositioning (data-bw-wave).
   ============================================================ */
add_shortcode( 'bw_animated_wave', function ( $atts ) {
    static $n = 0; $n++;
    $a = shortcode_atts( array(
        'id' => '', 'from' => 'navy', 'to' => 'dark', 'speed' => 'medium',
    ), $atts );

    $uid = $a['id'] ? preg_replace( '/[^a-z0-9\-_]/i', '', $a['id'] ) : 'aw' . $n;
    $cmap = array(
        'fog'=>'#F2F4F6','white'=>'#FFFFFF','navy'=>'#1A3550','dark'=>'#0F2035',
        'deep'=>'#0F1F3D','ink'=>'#212529','slate'=>'#4A5568','gold'=>'#D4A843','gold-warm'=>'#B8922A',
    );
    $spmap = array('slow'=>array('18s','26s','14s'),'medium'=>array('12s','18s','9s'),'fast'=>array('8s','12s','6s'));
    $sp   = $spmap[ $a['speed'] ] ?? $spmap['medium'];
    $from = brightwork_hex2rgb( $cmap[ $a['from'] ] ?? '#1A3550' );
    $to   = brightwork_hex2rgb( $cmap[ $a['to']   ] ?? '#0F2035' );

    return sprintf(
        '<div class="bw-hero-waves" data-bw-wave id="bwaw-%1$s">
           <svg class="bw-wave" viewBox="0 0 1440 160" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" style="animation-duration:%2$s"><path d="M0,75 C120,110 240,30 360,70 C480,110 600,30 720,70 C840,110 960,30 1080,70 C1200,110 1320,30 1440,70 L1440,160 L0,160 Z" fill="rgba(%3$s,0.5)"/></svg>
           <svg class="bw-wave" viewBox="0 0 1440 160" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" style="animation-duration:%4$s"><path d="M0,95 C180,55 360,135 540,95 C720,55 900,135 1080,95 C1260,55 1380,115 1440,95 L1440,160 L0,160 Z" fill="rgba(%5$s,0.6)"/></svg>
           <svg class="bw-wave" viewBox="0 0 1440 160" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" style="animation-duration:%6$s"><path d="M0,115 C240,75 480,145 720,115 C960,75 1200,145 1440,115 L1440,160 L0,160 Z" fill="rgba(196,151,58,0.04)"/></svg>
         </div>',
        esc_attr( $uid ),
        esc_attr( $sp[0] ), esc_attr( $from ),
        esc_attr( $sp[1] ), esc_attr( $to ),
        esc_attr( $sp[2] )
    );
} );


/* ============================================================
   [bw_grid size="mid" opacity="default" style="standard"]
   NOTE: For most uses, add CSS class bw-grid-std (or bw-grid-tight
   etc.) directly to the Elementor Section — no widget needed.
   Use this shortcode only when you need grid + stars + wave together
   via [bw_fx grid="yes" stars="yes" wave="yes"].
   ============================================================ */
add_shortcode( 'bw_grid', function ( $atts ) {
    $a = shortcode_atts( array(
        'size' => 'mid', 'opacity' => 'default', 'style' => 'standard',
    ), $atts );
    $cls = 'bw-grid';
    if ( $a['size']    !== 'mid'      ) $cls .= ' bw-grid--' . sanitize_html_class( $a['size'] );
    if ( $a['opacity'] !== 'default'  ) $cls .= ' bw-grid--' . sanitize_html_class( $a['opacity'] );
    if ( $a['style']   !== 'standard' ) $cls .= ' bw-grid--' . sanitize_html_class( $a['style'] );
    return '<div class="' . esc_attr( $cls ) . '" data-bw-fill aria-hidden="true"></div>';
} );


/* ============================================================
   LABEL / EYEBROW SHORTCODES
   ============================================================ */
add_shortcode('bw_eyebrow', function($a,$c=''){return '<div style="display:inline-flex;align-items:center;gap:0.6rem;font-family:var(--bw-font-b,Barlow,sans-serif);font-size:0.78rem;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:#3D9B9A;margin-bottom:1.25rem;"><span style="display:block;width:28px;height:1px;background:#3D9B9A;flex-shrink:0;"></span>'.wp_kses_post(do_shortcode($c)).'</div>';});
add_shortcode('bw_eyebrow_dark', function($a,$c=''){return '<div style="display:inline-flex;align-items:center;gap:0.6rem;font-family:var(--bw-font-b,Barlow,sans-serif);font-size:0.78rem;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:#C8A96E;margin-bottom:1.25rem;"><span style="display:block;width:28px;height:1px;background:#C8A96E;flex-shrink:0;"></span>'.wp_kses_post(do_shortcode($c)).'</div>';});
add_shortcode('bw_eyebrow_sky', function($a,$c=''){return '<div style="display:inline-flex;align-items:center;gap:0.6rem;font-family:var(--bw-font-b,Barlow,sans-serif);font-size:0.78rem;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:#689AE2;margin-bottom:1.25rem;"><span style="display:block;width:28px;height:1px;background:#689AE2;flex-shrink:0;"></span>'.wp_kses_post(do_shortcode($c)).'</div>';});
add_shortcode('bw_section_label', function($a,$c=''){return '<div style="display:inline-flex;align-items:center;gap:0.6rem;font-family:var(--bw-font-b,Barlow,sans-serif);font-size:0.78rem;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:#3D9B9A;margin-bottom:1rem;"><span style="display:block;width:20px;height:1px;background:#3D9B9A;flex-shrink:0;"></span>'.wp_kses_post(do_shortcode($c)).'</div>';});
add_shortcode('bw_section_label_centered', function($a,$c=''){return '<div style="display:flex;align-items:center;justify-content:center;gap:0.6rem;font-family:var(--bw-font-b,Barlow,sans-serif);font-size:0.78rem;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:#3D9B9A;margin-bottom:1rem;text-align:center;"><span style="display:block;width:20px;height:1px;background:#3D9B9A;flex-shrink:0;"></span>'.wp_kses_post(do_shortcode($c)).'<span style="display:block;width:20px;height:1px;background:#3D9B9A;flex-shrink:0;"></span></div>';});
add_shortcode('bw_section_label_accent', function($a,$c=''){return '<div style="display:flex;align-items:center;justify-content:center;gap:0.6rem;font-family:var(--bw-font-b,Barlow,sans-serif);font-size:0.78rem;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:#C8A96E;margin-bottom:1rem;text-align:center;"><span style="display:block;width:20px;height:1px;background:#C8A96E;flex-shrink:0;"></span>'.wp_kses_post(do_shortcode($c)).'<span style="display:block;width:20px;height:1px;background:#C8A96E;flex-shrink:0;"></span></div>';});
add_shortcode('bw_section_label_sky', function($a,$c=''){return '<div style="display:flex;align-items:center;justify-content:center;gap:0.6rem;font-family:var(--bw-font-b,Barlow,sans-serif);font-size:0.78rem;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:#689AE2;margin-bottom:1rem;text-align:center;"><span style="display:block;width:20px;height:1px;background:#689AE2;flex-shrink:0;"></span>'.wp_kses_post(do_shortcode($c)).'<span style="display:block;width:20px;height:1px;background:#689AE2;flex-shrink:0;"></span></div>';});
add_shortcode('bw_section_label_gold', function($a,$c=''){return '<div style="display:flex;align-items:center;justify-content:center;gap:0.6rem;font-family:var(--bw-font-b,Barlow,sans-serif);font-size:0.78rem;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:#D4A843;margin-bottom:1rem;text-align:center;"><span style="display:block;width:20px;height:1px;background:#D4A843;flex-shrink:0;"></span>'.wp_kses_post(do_shortcode($c)).'<span style="display:block;width:20px;height:1px;background:#D4A843;flex-shrink:0;"></span></div>';});

/* ============================================================
   QUOTE BLOCKS
   ============================================================ */
add_shortcode('bw_quote', function($a,$c=''){return '<blockquote style="background:#E8ECF0;border-left:3px solid #3D9B9A;padding:1.75rem;border-radius:0 6px 6px 0;margin:1.5rem 0;"><p style="font-family:var(--bw-font-d,\'Cormorant Garamond\',Georgia,serif);font-size:1.25rem;font-style:italic;font-weight:300;line-height:1.65;color:#1A3550;margin:0;">'.wp_kses_post(do_shortcode($c)).'</p></blockquote>';});
add_shortcode('bw_quote_dark', function($a,$c=''){return '<blockquote style="background:rgba(61,155,154,0.08);border:1px solid rgba(61,155,154,0.18);border-radius:10px;padding:1.75rem;margin:1.5rem 0;"><p style="font-family:var(--bw-font-d,\'Cormorant Garamond\',Georgia,serif);font-size:1.1rem;font-style:italic;font-weight:300;line-height:1.65;color:rgba(255,255,255,0.85);margin:0;">'.wp_kses_post(do_shortcode($c)).'</p></blockquote>';});
add_shortcode('bw_quote_sky', function($a,$c=''){return '<blockquote style="background:rgba(104,154,226,0.08);border-left:3px solid #689AE2;padding:1.75rem;border-radius:0 6px 6px 0;margin:1.5rem 0;"><p style="font-family:var(--bw-font-d,\'Cormorant Garamond\',Georgia,serif);font-size:1.25rem;font-style:italic;font-weight:300;line-height:1.65;color:#1A3550;margin:0;">'.wp_kses_post(do_shortcode($c)).'</p></blockquote>';});

/* ============================================================
   [bw_definition word="..." phonetic="..." pos="..." origin="..."]
   ============================================================ */
add_shortcode('bw_definition', function($atts,$c=''){
    $a=shortcode_atts(array('word'=>'Brightwork','phonetic'=>'/ˈbrīt-ˌwərk/','pos'=>'noun &nbsp;·&nbsp; maritime','origin'=>''),$atts);
    $o=$a['origin']?'<div style="font-size:0.82rem;color:#7A8F9E;line-height:1.6;padding-top:1rem;border-top:1px solid #D0D8DF;">'.esc_html($a['origin']).'</div>':'';
    return '<div style="background:#fff;border:1px solid #D0D8DF;border-radius:14px;padding:2.5rem;box-shadow:0 8px 32px rgba(26,53,80,0.11);position:relative;overflow:hidden;"><div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#3D9B9A,#C8A96E);"></div><div style="font-family:var(--bw-font-d,\'Cormorant Garamond\',Georgia,serif);font-size:2.2rem;font-weight:600;color:#1A3550;margin-bottom:0.2rem;">'.esc_html($a['word']).'</div><div style="font-size:0.82rem;color:#7A8F9E;font-style:italic;margin-bottom:1.25rem;">'.esc_html($a['phonetic']).'</div><div style="height:1px;background:#D0D8DF;margin-bottom:1.25rem;"></div><div style="font-size:0.72rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#3D9B9A;margin-bottom:0.6rem;">'.wp_kses_post($a['pos']).'</div><p style="font-family:var(--bw-font-d,\'Cormorant Garamond\',Georgia,serif);font-size:1.15rem;font-style:italic;font-weight:300;line-height:1.65;color:#1A3550;margin-bottom:1.25rem;">'.wp_kses_post(do_shortcode($c)).'</p>'.$o.'</div>';
});

/* ============================================================
   PILLAR ITEMS
   ============================================================ */
add_shortcode('bw_pillar', function($atts,$c=''){
    $a=shortcode_atts(array('icon'=>'compass','title'=>'','colour'=>'teal'),$atts);
    $ic=array('teal'=>array('rgba(61,155,154,0.12)','#3D9B9A'),'sky'=>array('rgba(104,154,226,0.12)','#689AE2'),'gold'=>array('rgba(212,168,67,0.12)','#D4A843'));
    list($bg,$col)=isset($ic[$a['colour']])?$ic[$a['colour']]:$ic['teal'];
    return '<div style="background:#F2F4F6;border:1px solid #D0D8DF;border-radius:10px;padding:1.75rem;display:flex;gap:1.25rem;align-items:flex-start;margin-bottom:1.25rem;"><div style="flex-shrink:0;width:42px;height:42px;background:'.$bg.';border-radius:10px;display:flex;align-items:center;justify-content:center;color:'.$col.';">'.brightwork_get_icon($a['icon']).'</div><div><h4 style="font-family:var(--bw-font-d,\'Cormorant Garamond\',Georgia,serif);font-size:1.2rem;font-weight:600;color:#1A3550;margin-bottom:0.4rem;">'.esc_html($a['title']).'</h4><p style="font-size:0.92rem;color:#7A8F9E;line-height:1.65;margin:0;">'.wp_kses_post(do_shortcode($c)).'</p></div></div>';
});

add_shortcode('bw_benefit_item', function($atts,$c=''){
    $a=shortcode_atts(array('icon'=>'star','title'=>'','colour'=>'teal'),$atts);
    $ic=array('teal'=>array('rgba(61,155,154,0.18)','#3D9B9A'),'sky'=>array('rgba(104,154,226,0.18)','#689AE2'),'gold'=>array('rgba(212,168,67,0.18)','#D4A843'));
    list($bg,$col)=isset($ic[$a['colour']])?$ic[$a['colour']]:$ic['teal'];
    return '<div style="display:flex;gap:1.25rem;align-items:flex-start;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);border-radius:6px;padding:1.35rem;margin-bottom:1.25rem;"><div style="flex-shrink:0;width:40px;height:40px;background:'.$bg.';border-radius:8px;display:flex;align-items:center;justify-content:center;color:'.$col.';">'.brightwork_get_icon($a['icon']).'</div><div><h4 style="font-family:var(--bw-font-d,\'Cormorant Garamond\',Georgia,serif);font-size:1rem;font-weight:600;color:#fff;margin-bottom:0.3rem;">'.esc_html($a['title']).'</h4><p style="font-size:0.9rem;color:rgba(255,255,255,0.62);line-height:1.6;margin:0;">'.wp_kses_post(do_shortcode($c)).'</p></div></div>';
});

/* ============================================================
   STAT CARDS
   ============================================================ */
add_shortcode('bw_stat', function($atts){
    $a=shortcode_atts(array('value'=>'0','label'=>'','colour'=>'teal'),$atts);
    $c=array('teal'=>'#3D9B9A','sky'=>'#689AE2','gold'=>'#D4A843');
    $col=isset($c[$a['colour']])?$c[$a['colour']]:'#3D9B9A';
    return '<div style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.09);border-radius:10px;padding:1.5rem;text-align:center;"><div style="font-family:var(--bw-font-d,\'Cormorant Garamond\',Georgia,serif);font-size:2.4rem;font-weight:500;color:'.$col.';line-height:1;margin-bottom:0.4rem;">'.esc_html($a['value']).'</div><div style="font-size:0.78rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:rgba(255,255,255,0.5);">'.esc_html($a['label']).'</div></div>';
});
add_shortcode('bw_stat_light', function($atts){
    $a=shortcode_atts(array('value'=>'0','label'=>'','colour'=>'teal'),$atts);
    $c=array('teal'=>'#3D9B9A','sky'=>'#689AE2','gold'=>'#D4A843');
    $col=isset($c[$a['colour']])?$c[$a['colour']]:'#3D9B9A';
    return '<div style="background:#fff;border:1px solid #D0D8DF;border-radius:10px;padding:1.5rem;text-align:center;"><div style="font-family:var(--bw-font-d,\'Cormorant Garamond\',Georgia,serif);font-size:2.4rem;font-weight:500;color:'.$col.';line-height:1;margin-bottom:0.4rem;">'.esc_html($a['value']).'</div><div style="font-size:0.78rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#7A8F9E;">'.esc_html($a['label']).'</div></div>';
});

/* ============================================================
   PROCESS STEPS
   ============================================================ */
add_shortcode('bw_process_card', function($atts,$c=''){
    $a=shortcode_atts(array('filename'=>'brightwork_process.flow'),$atts);
    return '<div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:14px;padding:2rem;"><div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;"><div style="width:10px;height:10px;border-radius:50%;background:#FF6B6B;"></div><div style="width:10px;height:10px;border-radius:50%;background:#FFD93D;"></div><div style="width:10px;height:10px;border-radius:50%;background:#6BCB77;"></div><span style="font-size:0.75rem;color:rgba(255,255,255,0.3);margin-left:0.5rem;font-family:monospace;">'.esc_html($a['filename']).'</span></div>'.do_shortcode($c).'</div>';
});
add_shortcode('bw_process_step', function($atts,$c=''){
    $a=shortcode_atts(array('num'=>'1','title'=>''),$atts);
    return '<div style="display:flex;gap:1rem;align-items:flex-start;margin-bottom:0.75rem;"><div style="flex-shrink:0;width:26px;height:26px;background:rgba(61,155,154,0.18);border:1px solid rgba(61,155,154,0.3);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.72rem;font-weight:700;color:#3D9B9A;font-family:monospace;">'.esc_html($a['num']).'</div><div><h5 style="font-size:0.88rem;font-weight:600;color:rgba(255,255,255,0.88);margin-bottom:0.2rem;">'.esc_html($a['title']).'</h5><p style="font-size:0.78rem;color:rgba(255,255,255,0.48);line-height:1.5;margin:0;">'.wp_kses_post(do_shortcode($c)).'</p></div></div>';
});
add_shortcode('bw_process_connector', function(){return '<div style="width:1px;height:16px;background:rgba(61,155,154,0.2);margin-left:13px;margin-bottom:0.2rem;"></div>';});

/* ============================================================
   BADGES
   ============================================================ */
add_shortcode('bw_goal_badge',      function($a){$t=shortcode_atts(array('text'=>''),$a);return '<span style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.5rem 1rem;border-radius:100px;background:rgba(61,155,154,0.1);border:1px solid rgba(61,155,154,0.25);color:#3D9B9A;font-size:0.8rem;font-weight:600;"><svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>'.esc_html($t['text']).'</span>';});
add_shortcode('bw_goal_badge_gold', function($a){$t=shortcode_atts(array('text'=>''),$a);return '<span style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.5rem 1rem;border-radius:100px;background:rgba(200,169,110,0.14);border:1px solid rgba(200,169,110,0.3);color:#C8A96E;font-size:0.8rem;font-weight:600;"><svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>'.esc_html($t['text']).'</span>';});
add_shortcode('bw_goal_badge_sky',  function($a){$t=shortcode_atts(array('text'=>''),$a);return '<span style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.5rem 1rem;border-radius:100px;background:rgba(104,154,226,0.12);border:1px solid rgba(104,154,226,0.28);color:#689AE2;font-size:0.8rem;font-weight:600;"><svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>'.esc_html($t['text']).'</span>';});
add_shortcode('bw_coming_soon',     function($a){$t=shortcode_atts(array('text'=>'Coming Soon'),$a);return '<span style="display:inline-flex;align-items:center;gap:0.5rem;background:rgba(200,169,110,0.15);border:1px solid rgba(200,169,110,0.35);color:#C8A96E;font-size:0.78rem;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;padding:0.35rem 0.9rem;border-radius:100px;margin-bottom:1.5rem;"><span style="display:block;width:6px;height:6px;background:#C8A96E;border-radius:50%;animation:bw-pulse 2s infinite;"></span>'.esc_html($t['text']).'<style>@keyframes bw-pulse{0%,100%{opacity:1;transform:scale(1);}50%{opacity:0.4;transform:scale(0.8);}}</style></span>';});

/* ============================================================
   TEXT LINKS & CONTACT STRIP
   ============================================================ */
add_shortcode('bw_text_link', function($atts){
    $a=shortcode_atts(array('url'=>'#','text'=>'Learn More','colour'=>'primary'),$atts);
    $col=$a['colour']==='sky'?'#689AE2':'#1E4976';
    return '<a href="'.esc_url($a['url']).'" style="display:inline-flex;align-items:center;gap:0.4rem;font-size:0.85rem;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:'.$col.';border-bottom:1px solid transparent;text-decoration:none;" onmouseover="this.style.borderColor=\''.$col.'\'" onmouseout="this.style.borderColor=\'transparent\'">'.esc_html($a['text']).'<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>';
});
add_shortcode('bw_contact_strip', function($atts){
    $a=shortcode_atts(array('phone'=>'434-282-7215','email'=>'contact@brightworkconsult.com'),$atts);
    $p=preg_replace('/[^0-9]/','', $a['phone']);
    return '<div style="display:flex;align-items:center;justify-content:center;gap:1.5rem;flex-wrap:wrap;margin-top:2rem;"><a href="tel:'.esc_attr($p).'" style="display:flex;align-items:center;gap:0.4rem;color:rgba(255,255,255,0.6);font-size:0.9rem;text-decoration:none;" onmouseover="this.style.color=\'#3D9B9A\'" onmouseout="this.style.color=\'rgba(255,255,255,0.6)\'">'.esc_html($a['phone']).'</a><span style="color:rgba(255,255,255,0.2);">|</span><a href="mailto:'.esc_attr($a['email']).'" style="display:flex;align-items:center;gap:0.4rem;color:rgba(255,255,255,0.6);font-size:0.9rem;text-decoration:none;" onmouseover="this.style.color=\'#3D9B9A\'" onmouseout="this.style.color=\'rgba(255,255,255,0.6)\'">'.esc_html($a['email']).'</a></div>';
});

/* ============================================================
   [bw_animate] — scroll reveal wrapper
   ============================================================ */
add_shortcode('bw_animate', function($a,$c=''){return '<div data-bw-animate>'.do_shortcode($c).'</div>';});

/* ============================================================
   [bw_cf7_form id="123"]
   ============================================================ */
add_shortcode('bw_cf7_form', function($atts){
    $a=shortcode_atts(array('id'=>'','title'=>'Contact Form'),$atts);
    if (empty($a['id'])) return '<p style="color:#7A8F9E;font-size:0.9rem;">Add id: [bw_cf7_form id="YOUR_CF7_ID"]</p>';
    if (!function_exists('wpcf7_contact_form')) return '<p style="color:#7A8F9E;font-size:0.9rem;">Please install Contact Form 7.</p>';
    return do_shortcode('[contact-form-7 id="'.intval($a['id']).'" title="'.esc_attr($a['title']).'"]');
});

/* ============================================================
   ICON HELPER
   ============================================================ */
function brightwork_get_icon($name){
    $i=array(
        'search'   =>'<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>',
        'layers'   =>'<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>',
        'compass'  =>'<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>',
        'users'    =>'<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>',
        'star'     =>'<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
        'lightning'=>'<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>',
        'chart'    =>'<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>',
        'tool'     =>'<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>',
        'anchor'   =>'<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><circle cx="12" cy="5" r="3"/><line x1="12" y1="8" x2="12" y2="22"/><path d="M5 15H2a10 10 0 0 0 20 0h-3"/></svg>',
        'home'     =>'<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
        'dollar'   =>'<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>',
        'shield'   =>'<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        'eye'      =>'<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
        'clock'    =>'<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'globe'    =>'<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>',
        'monitor'  =>'<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>',
        'code'     =>'<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
        'mail'     =>'<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
        'phone'    =>'<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2A19.86 19.86 0 013.08 4.18 2 2 0 015.07 2h3a2 2 0 012 1.72c.127.96.36 1.903.7 2.81a2 2 0 01-.45 2.11L9.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.34 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>',
        'map'      =>'<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>',
    );
    return isset($i[$name])?$i[$name]:$i['compass'];
}
