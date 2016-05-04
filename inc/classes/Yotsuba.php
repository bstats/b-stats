<?php
/**
 * "Yotsuba": Static class with static functions to deal with 4chan-related things.
 */
class Yotsuba {
    /**
     * Yotsuba::tripSplit will split < NameSync 2 name data into separate name and trip
     * 
     * @param string $nametrip string formatted like Name#tripcode
     * @param pointer $nam pointer to variable in which to store the name
     * @param pointer $trip pointer to variable in which to store the tripcode
     */
    public static function tripSplit($nametrip,&$nam,&$trip){
        $nme = explode('#',$nametrip);
        if(count($nme) > 1 || $nametrip[0] == "#"){
            $trip = array_pop($nme);
            $nam = implode($nme);
        }
        else{
            $nam = $nametrip;
            unset($trip);
        }
    }
    
    /**
     * Taken from some 4chan source code leak, modified slightly.
     * 
     * @param string $name string containing raw Name+trip (e.g. terrance#4TfJmbp`)
     * @return string|boolean the tripcode (sans-!) if there is a # in the string, otherwise false
     */
    public static function parseTripcode($name){
        $names=iconv("UTF-8", "CP932//IGNORE", $name); // convert to Windows Japanese #&#65355;&#65345;&#65357;&#65353;
        list ($name) = explode("#", $name);
 
        if(preg_match("/\#+$/", $names)){
            //$names = preg_replace("/\#+$/", "", $names);
        }
        if (preg_match("/\#/", $names)) {
            $names = str_replace("&#","&&",htmlspecialchars($names,null,"CP932")); # otherwise HTML numeric entities screw up explode()!
            list ($nametemp,$trip) = str_replace("&&", "&#", explode("#",$names,3));
            $names = $nametemp;

            if ($trip != "") {
                $salt = strtr(preg_replace("/[^\.-z]/",".",substr($trip."H.",1,2)),":;<=>?@[\\]^_`","ABCDEFGabcdef");
                $trip = substr(crypt($trip, $salt),-10);
                return $trip;
            }
        }
        return false;
    }
    
    /**
     * Removes most HTML formatting from a post.
     * 
     * Theoretically, this should leave you with the same thing as Javascript's
     * <code>element.innerText</code> attribute (<code>jQuery.text()</code>) does.
     * 
     * @param string $comment
     * @return string sanitized comment
     */
    public static function toPlainText($comment){
        $search[0] = '/<a href="(.*)" class="quotelink">&gt;&gt;([0-9]+)<\/a>/U';
        $search[1] = '/<span class="deadlink">&gt;&gt;([0-9]+)<\/span>/';
        $search[2] = '~<span class="quote">&gt;(.*)</span>~U';
        $search[3] = '~<a href="(.*)" class="quotelink">&gt;&gt;&gt;/([a-z]+)/</a>~U';
        $search[4] = '~<a href="/[a-z]+/res/[0-9]+#p([0-9]+)" class="quotelink">&gt;&gt;&gt;/([a-z]+)/([0-9]+)</a>~U';
        $search[5] = '~<a href="/[a-z]+/catalog#s=(\S+)" class="quotelink">&gt;&gt;&gt;/([a-z]+)/(\S+)</a>~U';
        $search[6] = '~<span class="deadlink">&gt;&gt;&gt;/([a-z]+)/([0-9]+)</span>~U';
        $search[7] = '~<a href="(.+)" target="_blank">(\S+)</a>~U';
        
        $replace[0] = '>>$2';
        $replace[1] = '>>$1';
        $replace[2] = ">$1";
        $replace[3] = ">>>/$2/";
        $replace[4] = ">>>/$2/$3";
        $replace[5] = ">>>/$2/$3";
        $replace[6] = ">>>/$1/$2";
        $replace[7] = "$2";
        $comment = preg_replace($search, $replace, $comment);
        $srch = array('&#039;',"<br>","<wbr>","&amp;","&quot;",'&lt;','&gt;');
        $rpl = array("'","\n",'','&','"','<','>');
        $comment = str_replace($srch,$rpl,$comment);
        return $comment;
    }

    /**
     * Converts an html-formatted comment to a bbcode formatted one.
     * 
     * Uses XSLT to do so. See https://stackoverflow.com/questions/4308734/how-to-convert-html-to-bbcode#4462090
     * 
     * @param string $html the html-formatted comment
     * @return the text in BBcode form.
     */
    public static function toBBCode(string $html):string {
      $doc = new DOMDocument();
      $doc->loadHTML($html);
      
      $transform = new DOMDocument();
      $transform->loadXML(self::$bbcode_transform);

      $proc = new XSLTProcessor();
      $proc->importStylesheet($transform);
      $transformed = $proc->transformToXml($doc);
      
      return $transformed;
    }
    
    /**
     * Formats a post with HTML.
     * 
     * Essentially, this performs the reverse of the
     * <code>Yotsuba::sanitize_comment()</code> function.
     * 
     * Notes:
     * - cross-board or cross-thread links are not fixed, but instead left as
     *   deadlinks.
     * - <code>&lt;wbr&gt;</code> tags are not replaced.
     * 
     * @param string $comment 
     * @return string HTML-formatted comment
     */
    public static function toHtml($comment,$posts){
        $search[0] = '~&gt;&gt;([0-9]{1,9})~'; // >>123 type post links
        $search[1] = "~^&gt;(.*)$~m"; // >greentext
        $search[2] = "~&gt;&gt;&gt;/([a-z]{1,4}/"; // >>>/board/ links
        $search[3] = "~&gt;&gt;&gt;/([a-z]{1,4}/([0-9]{1,9})~"; // >>>/board/123 type post links

        $replace[0] = '<a href="" class="quotelink">&gt;&gt;$1</a>';
        $replace[1] = '<span class="quote">&gt;$1</span>';
        $replace[2] = '<a href="/$1/" class="quotelink">&gt;&gt;&gt;/$1/</a>';
        $replace[3] = '<span class="deadlink">&gt;&gt;&gt;/$1/$2</span>';

        $srch = array('&',"'",'<','>','"');
        $rpl = array("&amp;",'&#039;','&lt;','&gt;',"&quot;");
        $htmlSpecialCharComment = str_replace($srch,$rpl,$comment);
        $initialTagComment = preg_replace($search, $replace, $htmlSpecialCharComment);


        /**
         * @todo Search DB to make Cross-Thread links
         */
        $formattedComment = preg_replace_callback(
                '~<a href="" class="quotelink">&gt;&gt;([0-9]{1,9})</a>~',
                function($matches) use($posts){
                    if(in_array($matches[1], $posts)){
                        return '<a href="'.$posts[0].'#p'.$matches[1].'" class="quotelink">&gt;&gt;'.$matches[1].'</a>';
                    }
                    else
                        return '<span class="deadlink">&gt;&gt;'.$matches[1].'</span>';
                },
                $initialTagComment);

        $finalComment = str_replace("\n","<br>",$formattedComment);
        return $finalComment;
    }
    
    static $bbcode_transform = <<<END
      <xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
        <xsl:output method="text"/>
        <xsl:template match="br"><xsl:text disable-output-escaping="yes">&#10;</xsl:text></xsl:template>
        <xsl:template match="b|strong|span[contains(@class, 'mu-s')]">[b]<xsl:apply-templates/>[/b]</xsl:template>
        <xsl:template match="span[contains(@class, 'mu-i')]">[i]<xsl:apply-templates/>[/i]</xsl:template>
        <xsl:template match="span[contains(@class, 'mu-r')]">[red]<xsl:apply-templates/>[/red]</xsl:template>
        <xsl:template match="span[contains(@class, 'mu-g')]">[green]<xsl:apply-templates/>[/green]</xsl:template>
        <xsl:template match="span[contains(@class, 'mu-b')]">[blue]<xsl:apply-templates/>[/blue]</xsl:template>
        <xsl:template match="s">[spoiler]<xsl:apply-templates/>[/spoiler]</xsl:template>
        <xsl:template match="text()"><xsl:value-of select="normalize-space(.)"/></xsl:template>
      </xsl:stylesheet>
END;
}