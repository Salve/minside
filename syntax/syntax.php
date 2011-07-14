<?php
/**
 * Syntax plugin for MinSide
 * 
 * @author     Salve Spinnangr <salve.spinnangr@gmail.com>
 */

if(!defined('DOKU_INC')) die();
 
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'syntax.php';
 

class syntax_plugin_minside_syntax extends DokuWiki_Syntax_Plugin {
 
    // DokuWiki krever info, vises i admin panel
    function getInfo() {
        return array(
            'author' => 'Salve Spinnangr, Njål Kollbotn',
            'email'  => 'salve.spinnangr@gmail.com',
            'date'   => '2010-09-20',
            'name'   => 'Min Side - Syntax plugin',
            'desc'   => 'Muliggjør visning av MinSide output i dw-sider ved bruk av egen syntax.',
            'url'    => ''
        );
    }
 
    function getType() { return 'substition'; }
    function getPType() { return 'block'; }
    function getSort() { return 32; }
 
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{minside:[a-zA-Z0-9\_\:\=\?\&\[\]\"\?]*?\}\}',$mode,'plugin_minside_syntax');
    }
 
    function handle($match, $state, $pos, &$handler) {
        preg_match_all('/(?:\:([a-zA-Z0-9\_\=\?\&\[\]\"\?]+\b))/i',$match,$matches, PREG_PATTERN_ORDER);
        $data['modul'] = $matches[1][0];
        $data['act'] = $matches[1][1];
        $data['param'] = array_slice($matches[1], 2);
        return $data;
    }
 
    function render($mode, &$renderer, $data) {
        if($mode == 'xhtml'){
            require_once(DOKU_PLUGIN.'minside/minside/minside.php');
            try {
                $objMinSide = MinSide::getInstance();
                $msoutput = $objMinSide->genModul($data['modul'], $data['act'], $data['param']);
            } catch (Exception $e) {
                $msoutput = '<div class="mswarningbar">Klare ikke å laste MinSide-modul!<br /><br /><p>Feil: '.$e->getMessage().'</p></div>';
            }
            $renderer->doc .= $output = '<div class="minside">' . $msoutput . '<div class="msclearer"></div></div>';
            return true;
        }
        return false;
    }
}
