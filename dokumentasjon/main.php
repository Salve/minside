<?php
die('Du har glemt å fjerne andre linje i main.php (tpl-mappen)...'); // Fjern denne linjen om siden kopieres inn i tpl...
/**
 * MinSide-Sidebar template til DokuWiki
 *
 * Template er kopi av default template fra Anteater
 * med minimum av modifikasjoner nødvendig for å loade sidebaren
 *
 * @link   http://dokuwiki.org/templates
 * @author Andreas Gohr <andi@splitbrain.org>
 * @author Salve Spinnangr <salve@salves.net>
 */

// must be run from within DokuWiki
if (!defined('DOKU_INC')) die();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $conf['lang']?>"
 lang="<?php echo $conf['lang']?>" dir="<?php echo $lang['direction']?>">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>
    <?php tpl_pagetitle()?>
    [<?php echo strip_tags($conf['title'])?>]
  </title>

  <?php tpl_metaheaders()?>

  <link rel="shortcut icon" href="<?php echo DOKU_TPL?>images/favicon.ico" />

  <?php /*old includehook*/ @include(dirname(__FILE__).'/meta.html')?>
</head>

<body>
<?php /*old includehook*/ @include(dirname(__FILE__).'/topheader.html')?>
<div class="dokuwiki">
  <?php html_msgarea()?>

  <div class="stylehead">

    <div class="header">
      <div class="pagename">
        [[<?php tpl_link(wl($ID,'do=backlink'),tpl_pagetitle($ID,true),'title="'.$lang['btn_backlink'].'"')?>]]
      </div>
      <div class="logo">
        <?php tpl_link(wl(),$conf['title'],'name="dokuwiki__top" id="dokuwiki__top" accesskey="h" title="[H]"')?>
      </div>

      <div class="clearer"></div>
    </div>

    <?php /*old includehook*/ @include(dirname(__FILE__).'/header.html')?>

    <div class="bar" id="bar__top">
      <div class="bar-left" id="bar__topleft">
        <?php tpl_button('edit')?>
        <?php tpl_button('history')?>
      </div>

      <div class="bar-right" id="bar__topright">
        <?php tpl_button('recent')?>
        <?php tpl_searchform()?>&nbsp;
      </div>

      <div class="clearer"></div>
    </div>

    <?php if($conf['breadcrumbs']){?>
    <div class="breadcrumbs">
      <?php tpl_breadcrumbs()?>
      <?php //tpl_youarehere() //(some people prefer this)?>
    </div>
    <?php }?>

    <?php if($conf['youarehere']){?>
    <div class="breadcrumbs">
      <?php tpl_youarehere() ?>
    </div>
    <?php }?>

  </div>
  <?php tpl_flush()?>
  
  <div class="mssidebar">
<?php
    // MinSide-edit start
    
    // Starter nytt layer med output buffering, for å ta vare på
    // output fra tpl_content(), som genererer alt innhold i en wikiside,
    // dette inkluderer normal output fra MinSide som gir output til DW
    // via en plugin hook.
    
    ob_start();
    tpl_content();
    $keep_tpl_content = ob_get_clean();
    
    // Så loader vi MinSide-classen og genererer/outputer sidebar
    // Dette gjøres altså etter hovedside-content er generert, men før
    // det er outputet.
    
    // Loader ikke sidebar dersom bruker ikke er logget inn.
    if (!empty($_SERVER['REMOTE_USER'])) {
        require_once(DOKU_PLUGIN.'minside/minside/minside.php');
        $objMinSide = MinSide::getInstance();
        try {
            echo $objMinSide->genModul('sidebar', 'show'); 
        } catch(Exception $e) {
            echo 'Klarte ikke å generere sidebar: ' . $e->getMessage();
        }
    }
    
    // MinSide-edit slutt
?>
  </div>
  
  <?php tpl_flush()?>
  
  <?php /*old includehook*/ @include(dirname(__FILE__).'/pageheader.html')?>

  <div class="page">
    <!-- wikipage start -->
    <?php /* Output content vi cacha over */ echo $keep_tpl_content ?>
    <!-- wikipage stop -->
  </div>

  <div class="clearer">&nbsp;</div>

  <?php tpl_flush()?>

  <div class="stylefoot">

    <div class="meta">
      <div class="user">
        <?php tpl_userinfo()?>
      </div>
      <div class="doc">
        <?php tpl_pageinfo()?>
      </div>
    </div>

   <?php /*old includehook*/ @include(dirname(__FILE__).'/pagefooter.html')?>

    <div class="bar" id="bar__bottom">
      <div class="bar-left" id="bar__bottomleft">
        <?php tpl_button('edit')?>
        <?php tpl_button('history')?>
        <?php tpl_button('revert')?>
      </div>
      <div class="bar-right" id="bar__bottomright">
        <?php tpl_button('subscribe')?>
        <?php tpl_button('admin')?>
        <?php tpl_button('profile')?>
        <?php tpl_button('login')?>
        <?php tpl_button('index')?>
        <?php tpl_button('top')?>&nbsp;
      </div>
      <div class="clearer"></div>
    </div>

  </div>

  <?php tpl_license(false);?>

</div>
<?php /*old includehook*/ /* MinSide edit, disabler linje med donasjon osv @include(dirname(__FILE__).'/footer.html') */ ?>

<div class="no"><?php /* provide DokuWiki housekeeping, required in all templates */ tpl_indexerWebBug()?></div>
</body>
</html>
